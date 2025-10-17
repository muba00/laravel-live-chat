<?php

namespace muba00\LaravelLiveChat\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use muba00\LaravelLiveChat\Models\Message;

class CleanupMessagesCommand extends Command
{
    public $signature = 'live-chat:cleanup
                        {--days= : Number of days to retain messages (overrides config)}
                        {--archive : Archive messages instead of deleting}
                        {--dry-run : Show what would be deleted without actually deleting}
                        {--force : Skip confirmation prompt}';

    public $description = 'Clean up old chat messages based on retention policy';

    public function handle(): int
    {
        $this->info('┌────────────────────────────────────────┐');
        $this->info('│  Laravel Live Chat - Message Cleanup  │');
        $this->info('└────────────────────────────────────────┘');
        $this->newLine();

        // Get retention days from option or config
        $retentionDays = $this->option('days') ?? config('live-chat.storage.retention_days');

        if (! $retentionDays) {
            $this->warn('⚠ No retention policy configured.');
            $this->info('Set CHAT_RETENTION_DAYS in .env or use --days option.');

            return self::SUCCESS;
        }

        $this->info("🔍 Retention policy: Delete messages older than {$retentionDays} days");

        // Calculate cutoff date
        $cutoffDate = now()->subDays($retentionDays);
        $this->line("  Cutoff date: {$cutoffDate->format('Y-m-d H:i:s')}");
        $this->newLine();

        // Count messages to be deleted
        $messagesCount = Message::where('created_at', '<', $cutoffDate)->count();

        if ($messagesCount === 0) {
            $this->info('✓ No messages to clean up.');

            return self::SUCCESS;
        }

        $this->warn("📊 Found {$messagesCount} messages to clean up");

        if ($this->option('dry-run')) {
            $this->info('🔍 Dry run mode - no changes will be made');
            $this->showSampleMessages($cutoffDate);

            return self::SUCCESS;
        }

        // Confirm deletion unless --force is used or non-interactive mode
        if (! $this->option('force') && $this->input->isInteractive()) {
            if (! $this->confirm("Are you sure you want to delete {$messagesCount} messages?", false)) {
                $this->info('Cleanup cancelled.');

                return self::SUCCESS;
            }
        }

        // Archive if requested
        if ($this->option('archive') || config('live-chat.storage.archive_enabled')) {
            return $this->archiveMessages($cutoffDate, $messagesCount);
        }

        // Delete messages
        return $this->deleteMessages($cutoffDate, $messagesCount);
    }

    protected function showSampleMessages($cutoffDate): void
    {
        $samples = Message::where('created_at', '<', $cutoffDate)
            ->with(['sender:id,name,email', 'conversation'])
            ->limit(5)
            ->get();

        if ($samples->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->info('Sample messages to be deleted:');
        $this->newLine();

        foreach ($samples as $message) {
            $sender = $message->sender?->name ?? 'Unknown';
            $preview = \Illuminate\Support\Str::limit($message->message, 50);
            $date = $message->created_at->format('Y-m-d H:i:s');
            $this->line("  [{$date}] {$sender}: {$preview}");
        }

        $this->newLine();
    }

    protected function deleteMessages($cutoffDate, $messagesCount): int
    {
        $this->info('🗑️  Deleting messages...');

        try {
            DB::beginTransaction();

            $deleted = Message::where('created_at', '<', $cutoffDate)->delete();

            DB::commit();

            $this->info("✓ Successfully deleted {$deleted} messages");
            $this->newLine();

            // Show statistics
            $this->showStatistics($deleted);

            return self::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();

            $this->error('✗ Error deleting messages: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function archiveMessages($cutoffDate, $messagesCount): int
    {
        $this->info('📦 Archiving messages...');

        try {
            // Get messages to archive
            $messages = Message::where('created_at', '<', $cutoffDate)
                ->with(['sender:id,name,email', 'conversation'])
                ->get();

            // Create archive directory if it doesn't exist
            $archivePath = config('live-chat.storage.archive_path', storage_path('app/chat-archives'));
            File::ensureDirectoryExists($archivePath);

            // Create archive file
            $archiveFile = $archivePath.'/messages_'.now()->format('Y-m-d_His').'.json';
            $archiveData = [
                'archived_at' => now()->toIso8601String(),
                'cutoff_date' => $cutoffDate->toIso8601String(),
                'message_count' => $messages->count(),
                'messages' => $messages->toArray(),
            ];

            File::put($archiveFile, json_encode($archiveData, JSON_PRETTY_PRINT));

            $this->line("  ✓ Archive created: {$archiveFile}");

            // Now delete the messages
            DB::beginTransaction();
            $deleted = Message::where('created_at', '<', $cutoffDate)->delete();
            DB::commit();

            $this->info("✓ Successfully archived and deleted {$deleted} messages");
            $this->newLine();

            // Show statistics
            $this->showStatistics($deleted, $archiveFile);

            return self::SUCCESS;
        } catch (\Exception $e) {
            if (isset($archiveFile) && File::exists($archiveFile)) {
                File::delete($archiveFile);
            }

            DB::rollBack();

            $this->error('✗ Error archiving messages: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function showStatistics(int $deleted, ?string $archiveFile = null): void
    {
        $this->info('📊 Cleanup Statistics:');
        $this->line("  Messages deleted: {$deleted}");

        if ($archiveFile) {
            $size = File::size($archiveFile);
            $sizeFormatted = $this->formatBytes($size);
            $this->line("  Archive size: {$sizeFormatted}");
            $this->line("  Archive location: {$archiveFile}");
        }

        $remaining = Message::count();
        $this->line("  Remaining messages: {$remaining}");
        $this->newLine();

        $this->info('💡 Tip: You can schedule this command in your app\'s Kernel.php:');
        $this->line('   $schedule->command(\'live-chat:cleanup\')->daily();');
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
