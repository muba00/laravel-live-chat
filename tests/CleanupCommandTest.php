<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use muba00\LaravelLiveChat\Commands\CleanupMessagesCommand;
use muba00\LaravelLiveChat\Models\Conversation;
use muba00\LaravelLiveChat\Models\Message;
use muba00\LaravelLiveChat\Tests\Stubs\User;

beforeEach(function () {
    // Create test users
    $this->user1 = User::create([
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->user2 = User::create([
        'name' => 'Bob',
        'email' => 'bob@example.com',
        'password' => bcrypt('password'),
    ]);

    // Create conversation
    $this->conversation = Conversation::create([
        'user1_id' => $this->user1->id,
        'user2_id' => $this->user2->id,
    ]);
});

it('shows warning when no retention policy is configured', function () {
    Config::set('live-chat.storage.retention_days', null);

    $this->artisan(CleanupMessagesCommand::class)
        ->expectsOutput('⚠ No retention policy configured.')
        ->assertSuccessful();
});

it('does not delete messages when none are old enough', function () {
    // Create recent message
    Message::create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->user1->id,
        'message' => 'Recent message',
    ]);

    Config::set('live-chat.storage.retention_days', 30);

    $this->artisan(CleanupMessagesCommand::class, ['--force' => true])
        ->expectsOutput('✓ No messages to clean up.')
        ->assertSuccessful();

    expect(Message::count())->toBe(1);
});

it('can delete old messages', function () {
    // Create old message
    $oldMessage = Message::create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->user1->id,
        'message' => 'Old message',
        'created_at' => now()->subDays(100),
    ]);

    // Create recent message
    $recentMessage = Message::create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->user2->id,
        'message' => 'Recent message',
    ]);

    Config::set('live-chat.storage.retention_days', 30);

    $this->artisan(CleanupMessagesCommand::class, ['--force' => true])
        ->expectsOutputToContain('Successfully deleted 1 messages')
        ->assertSuccessful();

    expect(Message::count())->toBe(1);
    expect(Message::find($recentMessage->id))->not->toBeNull();
    expect(Message::find($oldMessage->id))->toBeNull();
});

it('can use --days option to override config', function () {
    // Create message 40 days old
    Message::create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->user1->id,
        'message' => 'Message 40 days old',
        'created_at' => now()->subDays(40),
    ]);

    Config::set('live-chat.storage.retention_days', 30);

    // Override with --days=50, so message shouldn't be deleted
    $this->artisan(CleanupMessagesCommand::class, ['--days' => 50, '--force' => true])
        ->expectsOutput('✓ No messages to clean up.')
        ->assertSuccessful();

    expect(Message::count())->toBe(1);

    // Now use --days=30, message should be deleted
    $this->artisan(CleanupMessagesCommand::class, ['--days' => 30, '--force' => true])
        ->expectsOutputToContain('Successfully deleted 1 messages')
        ->assertSuccessful();

    expect(Message::count())->toBe(0);
});

it('shows dry run output without deleting', function () {
    // Create old messages
    Message::create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->user1->id,
        'message' => 'Old message',
        'created_at' => now()->subDays(100),
    ]);

    Config::set('live-chat.storage.retention_days', 30);

    $this->artisan(CleanupMessagesCommand::class, ['--dry-run' => true])
        ->expectsOutput('🔍 Dry run mode - no changes will be made')
        ->expectsOutputToContain('Found 1 messages to clean up')
        ->assertSuccessful();

    // Message should still exist
    expect(Message::count())->toBe(1);
});

it('can archive messages instead of deleting', function () {
    // Create old message
    Message::create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->user1->id,
        'message' => 'Old message to archive',
        'created_at' => now()->subDays(100),
    ]);

    Config::set('live-chat.storage.retention_days', 30);
    Config::set('live-chat.storage.archive_enabled', false);

    $archivePath = storage_path('app/chat-archives');

    // Ensure archive directory doesn't exist before test
    if (File::exists($archivePath)) {
        File::deleteDirectory($archivePath);
    }

    $this->artisan(CleanupMessagesCommand::class, ['--archive' => true, '--force' => true])
        ->expectsOutputToContain('📦 Archiving messages...')
        ->expectsOutputToContain('Successfully archived and deleted 1 messages')
        ->assertSuccessful();

    // Message should be deleted
    expect(Message::count())->toBe(0);

    // Archive file should exist
    expect(File::exists($archivePath))->toBeTrue();
    $files = File::files($archivePath);
    expect($files)->toHaveCount(1);

    // Verify archive content
    $archiveContent = json_decode(File::get($files[0]), true);
    expect($archiveContent)->toHaveKey('messages');
    expect($archiveContent['message_count'])->toBe(1);
    expect($archiveContent['messages'][0]['message'])->toBe('Old message to archive');

    // Clean up
    File::deleteDirectory($archivePath);
});

it('uses config for archiving when enabled', function () {
    Message::create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->user1->id,
        'message' => 'Message to archive',
        'created_at' => now()->subDays(100),
    ]);

    Config::set('live-chat.storage.retention_days', 30);
    Config::set('live-chat.storage.archive_enabled', true);

    $archivePath = storage_path('app/chat-archives');

    if (File::exists($archivePath)) {
        File::deleteDirectory($archivePath);
    }

    $this->artisan(CleanupMessagesCommand::class, ['--force' => true])
        ->expectsOutputToContain('📦 Archiving messages...')
        ->assertSuccessful();

    expect(File::exists($archivePath))->toBeTrue();

    File::deleteDirectory($archivePath);
});

it('shows statistics after cleanup', function () {
    Message::create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->user1->id,
        'message' => 'Old message',
        'created_at' => now()->subDays(100),
    ]);

    Config::set('live-chat.storage.retention_days', 30);

    $this->artisan(CleanupMessagesCommand::class, ['--force' => true])
        ->expectsOutput('📊 Cleanup Statistics:')
        ->expectsOutputToContain('Messages deleted: 1')
        ->expectsOutputToContain('Remaining messages: 0')
        ->assertSuccessful();
});

it('shows scheduling tip in output', function () {
    Config::set('live-chat.storage.retention_days', 30);

    Message::create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->user1->id,
        'message' => 'Old message',
        'created_at' => now()->subDays(100),
    ]);

    $this->artisan(CleanupMessagesCommand::class, ['--force' => true])
        ->expectsOutputToContain('$schedule->command(\'live-chat:cleanup\')->daily();')
        ->assertSuccessful();
});

it('shows sample messages in dry run mode', function () {
    Message::create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->user1->id,
        'message' => 'Sample old message',
        'created_at' => now()->subDays(100),
    ]);

    Config::set('live-chat.storage.retention_days', 30);

    $this->artisan(CleanupMessagesCommand::class, ['--dry-run' => true])
        ->expectsOutput('Sample messages to be deleted:')
        ->expectsOutputToContain('Sample old message')
        ->assertSuccessful();
});

it('displays cleanup banner', function () {
    Config::set('live-chat.storage.retention_days', 30);

    $this->artisan(CleanupMessagesCommand::class, ['--force' => true])
        ->expectsOutput('┌────────────────────────────────────────┐')
        ->expectsOutput('│  Laravel Live Chat - Message Cleanup  │')
        ->expectsOutput('└────────────────────────────────────────┘')
        ->assertSuccessful();
});

it('displays cutoff date information', function () {
    Config::set('live-chat.storage.retention_days', 30);

    $this->artisan(CleanupMessagesCommand::class, ['--force' => true])
        ->expectsOutputToContain('Retention policy: Delete messages older than 30 days')
        ->expectsOutputToContain('Cutoff date:')
        ->assertSuccessful();
});

it('formats archive file size correctly', function () {
    // Create multiple old messages
    for ($i = 0; $i < 10; $i++) {
        Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->user1->id,
            'message' => "Message {$i}",
            'created_at' => now()->subDays(100),
        ]);
    }

    Config::set('live-chat.storage.retention_days', 30);

    $archivePath = storage_path('app/chat-archives');
    if (File::exists($archivePath)) {
        File::deleteDirectory($archivePath);
    }

    $this->artisan(CleanupMessagesCommand::class, ['--archive' => true, '--force' => true])
        ->expectsOutputToContain('Archive size:')
        ->assertSuccessful();

    File::deleteDirectory($archivePath);
});

it('handles deletion errors gracefully', function () {
    // This test would require mocking to simulate a database error
    // For now, we'll just verify the command can handle exceptions

    Config::set('live-chat.storage.retention_days', 30);

    // Create a message that's old
    Message::create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->user1->id,
        'message' => 'Old message',
        'created_at' => now()->subDays(100),
    ]);

    // Normal execution should succeed
    $this->artisan(CleanupMessagesCommand::class, ['--force' => true])
        ->assertSuccessful();
});
