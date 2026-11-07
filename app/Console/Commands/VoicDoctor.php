<?php

namespace App\Console\Commands;

use App\Services\Exam\ExaminerService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

#[Signature('voic:doctor')]
#[Description('Check that the local AI examiner (Ollama, ai_service.py, Whisper, Piper) is ready for the booth')]
class VoicDoctor extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ExaminerService $examiner): int
    {
        $ok = true;
        $url = rtrim((string) Config::get('services.ai_service.url', 'http://127.0.0.1:8001'), '/');

        $this->components->info("Memeriksa AI lokal VOIC ({$url})");

        // 1. Web app basics.
        $migrated = Schema::hasColumn('practice_sessions', 'exam_state');
        $ok = $this->report('Database sudah dimigrasi', $migrated, 'php artisan migrate') && $ok;
        $built = file_exists(public_path('build/manifest.json'));
        $ok = $this->report('Aset web sudah di-build', $built, 'npm install && npm run build') && $ok;

        // 2. AI service, Ollama, voice.
        Cache::forget('voic.examiner.availability');
        $status = $examiner->diagnose();
        $local = $status['engine'] === 'local_llm';
        $ok = $this->report(
            'Otak AI lokal (Ollama)'.($status['model'] ? " · {$status['model']}" : ''),
            $local,
            $status['fix'] ?? 'Jalankan Ollama dan python ai_service\\ai_service.py'
        ) && $ok;
        if ($status['reason'] !== null) {
            $this->line("     <fg=yellow>{$status['reason']}</>");
            if ($local && $status['fix']) {
                $this->line("     <fg=gray>Saran: {$status['fix']}</>");
            }
        }
        $this->report('Telinga lokal (Whisper)', $status['stt_local'], 'cd ai_service && pip install -r requirements.txt && python setup_models.py', optional: true);
        $this->report('Suara lokal (Piper)', $status['tts_local'], 'cd ai_service && python setup_models.py', optional: true);

        // 3. One real examiner turn, timed.
        if ($local) {
            $this->newLine();
            $this->line('  Menguji satu giliran penguji…');
            $turn = [
                'mode' => 'wawancara', 'role_type' => 'hrd', 'examiner_name' => 'Kak Nadia', 'language' => 'id',
                'topic' => 'Data Analyst', 'context_material' => null, 'part' => 1, 'part_title' => 'Perkenalan',
                'directive' => ExaminerService::DIRECTIVE_FOLLOW_UP,
                'question' => 'Boleh perkenalkan diri dulu?',
                'answer' => 'Halo kak, saya Rina, lulusan statistika dan setahun magang jadi analis data di startup e-commerce.',
                'history' => [['role' => 'assistant', 'content' => 'Boleh perkenalkan diri dulu?']],
                'scripted' => '', 'planned_question' => null, 'transition' => null, 'turn' => 1, 'session_id' => 0,
            ];
            $started = microtime(true);
            try {
                $response = Http::timeout(60)->post($url.'/exam/respond', [
                    'mode' => 'wawancara', 'role_type' => 'hrd', 'language' => 'id', 'directive' => 'follow_up',
                    'part' => 1, 'question' => $turn['question'], 'answer' => $turn['answer'],
                    'messages' => $examiner->buildMessages($turn),
                ]);
                $seconds = round(microtime(true) - $started, 1);
                $reply = trim((string) $response->json('reply', ''));
                $ok = $this->report("Balasan AI dalam {$seconds} detik", $reply !== '', 'Lihat jendela VOIC_AI_SERVICE; model mungkin gagal dimuat (RAM kurang?)') && $ok;
                if ($reply !== '') {
                    $this->line("     <fg=cyan>Peserta:</> {$turn['answer']}");
                    $this->line("     <fg=cyan>Penguji:</> {$reply}");
                    if ($seconds > 8) {
                        $this->line('     <fg=yellow>Lambat. Pakai GPU, model lebih kecil, atau tutup aplikasi lain.</>');
                    }
                }
                if ($issues = (array) $response->json('issues', [])) {
                    $this->line('     <fg=yellow>Catatan kualitas: '.implode(', ', $issues).'</>');
                }
            } catch (\Throwable $exception) {
                $ok = $this->report('Balasan AI', false, $exception->getMessage()) && $ok;
            }
        }

        $this->newLine();
        if ($ok) {
            $this->components->info('Siap untuk pameran. Buka http://localhost:8000/latihan');

            return self::SUCCESS;
        }

        $this->components->error('Ada yang perlu diperbaiki (lihat tanda ✗ di atas).');

        return self::FAILURE;
    }

    /**
     * Print one check line and return whether it passed.
     */
    protected function report(string $label, bool $passed, string $fix, bool $optional = false): bool
    {
        $mark = $passed ? '<fg=green>✓</>' : ($optional ? '<fg=yellow>–</>' : '<fg=red>✗</>');
        $this->line("  {$mark} {$label}");
        if (! $passed) {
            $this->line("     <fg=gray>Perbaiki: {$fix}</>".($optional ? ' <fg=gray>(opsional, ada cadangan)</>' : ''));
        }

        return $passed || $optional;
    }
}
