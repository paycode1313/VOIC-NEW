<?php

namespace App\Console\Commands;

use App\Models\StudyAttempt;
use App\Models\StudySurvey;
use App\Services\Study\UserStudy;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('voic:study-links
    {--count=20 : Number of participant codes (P01, P02, ...)}
    {--prefix=P : Letter in front of the number}')]
#[Description('Invitation links for the user trial: one link per participant code, plus how far each participant is')]
class StudyLinks extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = max(1, min(200, (int) $this->option('count')));
        $prefix = UserStudy::normalizeCode((string) $this->option('prefix')) ?? 'P';
        // Phones on the booth Wi-Fi cannot open "localhost": prefer the public URL when it is set.
        $base = rtrim((string) (config('exam.public_url') ?: config('app.url')), '/').'/latihan?kode=';

        $attempts = StudyAttempt::selectRaw('code, count(*) as total')->groupBy('code')->pluck('total', 'code');
        $surveys = StudySurvey::pluck('code')->flip();

        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $code = $prefix.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $done = (int) ($attempts[$code] ?? 0);
            $rows[] = [
                $code,
                $base.$code,
                $done.' / '.UserStudy::ATTEMPTS_FOR_SURVEY,
                isset($surveys[$code]) ? 'sudah' : ($done >= UserStudy::ATTEMPTS_FOR_SURVEY ? 'BELUM' : '-'),
            ];
        }

        $this->table(['Kode', 'Tautan undangan', 'Latihan', 'Kuesioner'], $rows);
        $this->components->info('Kirim satu tautan ke tiap peserta. Kodenya tersimpan otomatis dan latihan dibuka dalam mode ujian.');
        if (! config('exam.public_url')) {
            $this->components->warn('VOIC_PUBLIC_URL belum diatur: tautan memakai APP_URL, yang mungkin tidak bisa dibuka dari HP lain.');
        }

        return self::SUCCESS;
    }
}
