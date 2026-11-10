<?php

namespace App\Console\Commands;

use App\Models\PracticeSession;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('voic:prune {--days= : Override VOIC_RETENTION_DAYS}')]
#[Description('Delete guest exam sessions and recorded answers older than the retention period')]
class PruneExamData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('exam.retention_days', 7));
        $cutoff = now()->subDays($days);

        // Guests have no account to keep their history in: remove everything.
        $guestSessions = 0;
        PracticeSession::query()
            ->whereNull('user_id')
            ->whereNotNull('exam_mode')
            ->where('created_at', '<', $cutoff)
            ->chunkById(100, function ($sessions) use (&$guestSessions) {
                foreach ($sessions as $session) {
                    $session->deleteWithRecordings();
                    $guestSessions++;
                }
            });

        // Registered users keep their scores and transcript; only the raw voice is removed.
        $recordings = 0;
        PracticeSession::query()
            ->whereNotNull('user_id')
            ->whereNotNull('exam_mode')
            ->where('created_at', '<', $cutoff)
            ->chunkById(100, function ($sessions) use (&$recordings) {
                foreach ($sessions as $session) {
                    if (Storage::disk('local')->exists($session->recordingsDirectory())) {
                        Storage::disk('local')->deleteDirectory($session->recordingsDirectory());
                        $recordings++;
                    }
                }
            });

        $this->components->info("Dihapus: {$guestSessions} sesi tamu, rekaman dari {$recordings} sesi pengguna (lebih dari {$days} hari).");

        return self::SUCCESS;
    }
}
