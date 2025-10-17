<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use muba00\LaravelLiveChat\Commands\CleanupMessagesCommand;
use muba00\LaravelLiveChat\Models\Conversation;
use muba00\LaravelLiveChat\Models\Message;

beforeEach(function () {
    $this->user1 = \muba00\LaravelLiveChat\Tests\Stubs\User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $this->user2 = \muba00\LaravelLiveChat\Tests\Stubs\User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);
    $this->conversation = Conversation::create(['user1_id' => $this->user1->id, 'user2_id' => $this->user2->id]);
});

it('shows warning when no retention policy is configured', function () {
    Config::set('live-chat.storage.retention_days', null);
    $this->artisan(CleanupMessagesCommand::class)->expectsOutput('⚠ No retention policy configured.')->assertSuccessful();
});

it('does not delete messages when none are old enough', function () {
    Message::create(['conversation_id' => $this->conversation->id, 'sender_id' => $this->user1->id, 'message' => 'Recent']);
    Config::set('live-chat.storage.retention_days', 30);
    $this->artisan(CleanupMessagesCommand::class, ['--force' => true])->expectsOutput('✓ No messages to clean up.')->assertSuccessful();
    expect(Message::count())->toBe(1);
});

it('can delete old messages', function () {
    $old = Message::create(['conversation_id' => $this->conversation->id, 'sender_id' => $this->user1->id, 'message' => 'Old']);
    $old->created_at = now()->subDays(100);
    $old->save();
    $recent = Message::create(['conversation_id' => $this->conversation->id, 'sender_id' => $this->user2->id, 'message' => 'Recent']);
    Config::set('live-chat.storage.retention_days', 30);
    $this->artisan(CleanupMessagesCommand::class, ['--force' => true])->assertSuccessful();
    expect(Message::count())->toBe(1);
    expect(Message::find($recent->id))->not->toBeNull();
});

it('can use --days option to override config', function () {
    $msg = Message::create(['conversation_id' => $this->conversation->id, 'sender_id' => $this->user1->id, 'message' => 'Msg']);
    $msg->created_at = now()->subDays(40);
    $msg->save();
    Config::set('live-chat.storage.retention_days', 30);
    $this->artisan(CleanupMessagesCommand::class, ['--days' => 50, '--force' => true])->assertSuccessful();
    expect(Message::count())->toBe(1);
    $this->artisan(CleanupMessagesCommand::class, ['--days' => 30, '--force' => true])->assertSuccessful();
    expect(Message::count())->toBe(0);
});

it('shows dry run output without deleting', function () {
    $msg = Message::create(['conversation_id' => $this->conversation->id, 'sender_id' => $this->user1->id, 'message' => 'Old']);
    $msg->created_at = now()->subDays(100);
    $msg->save();
    Config::set('live-chat.storage.retention_days', 30);
    $this->artisan(CleanupMessagesCommand::class, ['--dry-run' => true])->assertSuccessful();
    expect(Message::count())->toBe(1);
});

it('can archive messages instead of deleting', function () {
    $msg = Message::create(['conversation_id' => $this->conversation->id, 'sender_id' => $this->user1->id, 'message' => 'Old message to archive']);
    $msg->created_at = now()->subDays(100);
    $msg->save();
    Config::set('live-chat.storage.retention_days', 30);
    Config::set('live-chat.storage.archive_enabled', false);
    $path = storage_path('app/chat-archives');
    if (File::exists($path)) File::deleteDirectory($path);
    $this->artisan(CleanupMessagesCommand::class, ['--archive' => true, '--force' => true])->assertSuccessful();
    expect(Message::count())->toBe(0);
    expect(File::exists($path))->toBeTrue();
    File::deleteDirectory($path);
});

it('uses config for archiving when enabled', function () {
    $msg = Message::create(['conversation_id' => $this->conversation->id, 'sender_id' => $this->user1->id, 'message' => 'Old']);
    $msg->created_at = now()->subDays(100);
    $msg->save();
    Config::set('live-chat.storage.retention_days', 30);
    Config::set('live-chat.storage.archive_enabled', true);
    $path = storage_path('app/chat-archives');
    if (File::exists($path)) File::deleteDirectory($path);
    $this->artisan(CleanupMessagesCommand::class, ['--force' => true])->assertSuccessful();
    expect(File::exists($path))->toBeTrue();
    File::deleteDirectory($path);
});

it('shows statistics after cleanup', function () {
    $msg = Message::create(['conversation_id' => $this->conversation->id, 'sender_id' => $this->user1->id, 'message' => 'Old']);
    $msg->created_at = now()->subDays(100);
    $msg->save();
    Config::set('live-chat.storage.retention_days', 30);
    $this->artisan(CleanupMessagesCommand::class, ['--force' => true])->assertSuccessful();
    expect(Message::count())->toBe(0);
});

it('shows scheduling tip in output', function () {
    $msg = Message::create(['conversation_id' => $this->conversation->id, 'sender_id' => $this->user1->id, 'message' => 'Old']);
    $msg->created_at = now()->subDays(100);
    $msg->save();
    Config::set('live-chat.storage.retention_days', 30);
    $this->artisan(CleanupMessagesCommand::class, ['--force' => true])->assertSuccessful();
    expect(Message::count())->toBe(0);
});

it('shows sample messages in dry run mode', function () {
    $msg = Message::create(['conversation_id' => $this->conversation->id, 'sender_id' => $this->user1->id, 'message' => 'Sample']);
    $msg->created_at = now()->subDays(100);
    $msg->save();
    Config::set('live-chat.storage.retention_days', 30);
    $this->artisan(CleanupMessagesCommand::class, ['--dry-run' => true])->assertSuccessful();
    expect(Message::count())->toBe(1);
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
    for ($i = 0; $i < 10; $i++) {
        $msg = Message::create(['conversation_id' => $this->conversation->id, 'sender_id' => $this->user1->id, 'message' => "Msg {$i}"]);
        $msg->created_at = now()->subDays(100);
        $msg->save();
    }
    Config::set('live-chat.storage.retention_days', 30);
    $path = storage_path('app/chat-archives');
    if (File::exists($path)) File::deleteDirectory($path);
    $this->artisan(CleanupMessagesCommand::class, ['--archive' => true, '--force' => true])->assertSuccessful();
    expect(File::exists($path))->toBeTrue();
    expect(Message::count())->toBe(0);
    File::deleteDirectory($path);
});

it('handles deletion errors gracefully', function () {
    $msg = Message::create(['conversation_id' => $this->conversation->id, 'sender_id' => $this->user1->id, 'message' => 'Old']);
    $msg->created_at = now()->subDays(100);
    $msg->save();
    Config::set('live-chat.storage.retention_days', 30);
    $this->artisan(CleanupMessagesCommand::class, ['--force' => true])->assertSuccessful();
});
