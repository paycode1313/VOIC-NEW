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
        $local = in_array($status['engine'], ['local_llm', 'ollama_cloud', 'openrouter'], true);
        $ok = $this->report(
            (match ($status['engine']) {
                'ollama_cloud' => 'Otak AI cloud lewat Ollama', 'openrouter' => 'Otak AI lewat OpenRouter', default => 'Otak AI lokal (Ollama)'
            }).($status['model'] ? " · {$status['model']}" : ''),
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

        // 3. Timeout settings: Laravel must wait at least as long as a slow laptop needs.
        $timeout = (int) Config::get('services.ai_service.timeout', 90);
        if ($timeout < 60) {
            $this->line("  <fg=yellow>!</> AI_SERVICE_TIMEOUT={$timeout} detik terlalu pendek untuk laptop CPU.");
            $this->line('     <fg=gray>Perbaiki: ubah di .env menjadi AI_SERVICE_TIMEOUT=90 lalu php artisan config:clear</>');
        }

        // 4. Load the model first, then time one real examiner turn on the warm model.
        if ($local) {
            $this->newLine();
            $this->line('  Memuat model ke memori (bisa 10-60 detik di laptop tanpa GPU)…');
            try {
                $warm = Http::timeout(240)->post($url.'/exam/warmup');
                if ($warm->status() === 404) {
                    $this->line('     <fg=yellow>AI service masih versi lama. Tutup jendela VOIC_AI_SERVICE lalu jalankan ulang python ai_service\\ai_service.py</>');
                } elseif ($warm->json('ok')) {
                    $this->line('  <fg=green>✓</> Model '.$warm->json('model').' siap ('.$warm->json('seconds').' dtk'
                        .($warm->json('stats.load_ms') ? ', memuat '.round($warm->json('stats.load_ms') / 1000, 1).' dtk' : '').')');
                } else {
                    $ok = $this->report('Model dimuat', false, (string) $warm->json('error')) && $ok;
                }
            } catch (\Throwable $exception) {
                $ok = $this->report('Model dimuat', false, 'Memuat model lebih dari 4 menit: '.$exception->getMessage()) && $ok;
            }

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
                $response = Http::timeout(180)->post($url.'/exam/respond', [
                    'mode' => 'wawancara', 'role_type' => 'hrd', 'language' => 'id', 'directive' => 'follow_up',
                    'part' => 1, 'question' => $turn['question'], 'answer' => $turn['answer'],
                    'messages' => $examiner->buildMessages($turn),
                ]);
                $seconds = round(microtime(true) - $started, 1);
                $reply = trim((string) $response->json('reply', ''));
                $error = $response->json('error');
                $speed = $response->json('stats.tokens_per_second');

                $ok = $this->report("Balasan AI dalam {$seconds} detik", $reply !== '', $error ? (string) $error : 'Lihat jendela VOIC_AI_SERVICE untuk pesan error-nya.') && $ok;
                if ($reply !== '') {
                    $this->line("     <fg=cyan>Peserta:</> {$turn['answer']}");
                    $this->line("     <fg=cyan>Penguji:</> {$reply}");
                }
                if ($speed !== null) {
                    $this->line('     <fg=gray>Kecepatan: '.$speed.' token/dtk · prompt '.round(((int) $response->json('stats.prompt_ms')) / 1000, 1).' dtk untuk '.$response->json('stats.prompt_tokens').' token</>');
                }
                if ($issues = array_diff((array) $response->json('issues', []), ['empty'])) {
                    $this->line('     <fg=yellow>Catatan kualitas: '.implode(', ', $issues).'</>');
                }
                $this->adviseOnSpeed($seconds, $speed, (string) $response->json('model', $status['model'] ?? ''), $error);
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
     * Turn the measured speed into concrete advice for the booth laptop.
     */
    protected function adviseOnSpeed(float $seconds, mixed $tokensPerSecond, string $model, mixed $error): void
    {
        $slow = ($tokensPerSecond !== null && (float) $tokensPerSecond < 8) || $seconds > 10 || str_starts_with((string) $error, 'timeout');
        if (! $slow) {
            return;
        }

        $this->newLine();
        $this->line('  <fg=yellow>Model terlalu lambat untuk ngobrol yang terasa natural (target: balasan < 5 detik, > 10 token/dtk).</>');
        $this->line('  <fg=gray>1. Cek apakah model jalan di GPU atau CPU:</>  ollama ps   <fg=gray>(kolom PROCESSOR)</>');
        $this->line('  <fg=gray>2. Cek model dasarnya:</>  ollama show voic-coach');
        if (str_contains($model, '7b') || str_contains($model, '8b')) {
            $this->line('  <fg=gray>3. Model 7B/8B di CPU terlalu berat. Buat ulang voic-coach dari 3B (mode CPU):</>  cd ai_service && python setup_models.py');
        } else {
            $this->line('  <fg=gray>3. Laptop tanpa GPU: coba model lebih kecil.</>  ollama pull qwen2.5:1.5b  <fg=gray>lalu set</>  VOIC_EXAM_MODEL=qwen2.5:1.5b  <fg=gray>sebelum menjalankan ai_service.py</>');
        }
        $this->line('  <fg=gray>4. Tutup aplikasi berat lain (Chrome dengan banyak tab, game) dan colok charger (mode performa).</>');
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
