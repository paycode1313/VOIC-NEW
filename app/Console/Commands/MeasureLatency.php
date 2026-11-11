<?php

namespace App\Console\Commands;

use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\SessionMessage;
use App\Models\User;
use App\Services\Exam\ExamBlueprint;
use App\Services\Exam\ExamFlowService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Signature('voic:latency
    {--live : Measure now: run simulated turns through the real AI and voice on this laptop}
    {--turns=6 : Turns to simulate with --live}
    {--days=14 : With recorded data: only turns from the last N days}')]
#[Description('How long the candidate waits for the examiner: from recorded turns, or measured live on this laptop')]
class MeasureLatency extends Command
{
    /** Waiting longer than this for the first sound feels like the examiner froze. */
    public const TARGET_MS = 3000;

    /**
     * Answers of different lengths, as candidates really give them.
     *
     * @var list<string>
     */
    protected const ANSWERS = [
        'Halo, saya Rizki, lulusan sistem informasi. Saya pernah magang setahun sebagai data analyst di perusahaan e-commerce, kebanyakan membuat dasbor penjualan.',
        'Saya tertarik karena ingin bekerja dengan data yang dipakai langsung untuk keputusan bisnis.',
        'Waktu magang, laporan mingguan butuh tiga hari karena datanya dari banyak file Excel. Saya membuat skrip Python dan SQL sehingga laporannya bisa jadi dalam dua jam, dan tim pemasaran jadi lebih cepat menentukan promo.',
        'Eee, kurang tahu juga sih Kak.',
        'Kekurangan saya kadang terlalu detail, jadi sekarang saya belajar menentukan prioritas dengan membuat daftar tugas harian.',
        'Lima tahun lagi saya ingin memimpin tim analitik kecil dan membantu perusahaan memakai data dengan lebih baik.',
    ];

    /**
     * Execute the console command.
     */
    public function handle(ExamFlowService $flow, ExamBlueprint $blueprint): int
    {
        $rows = $this->option('live') ? $this->live($flow, $blueprint) : $this->recorded();
        if ($rows === []) {
            $this->components->warn($this->option('live')
                ? 'Tidak ada giliran yang berhasil diukur.'
                : 'Belum ada data jeda. Data tercatat otomatis dari setiap jawaban di ruang ujian, atau ukur sekarang dengan: php artisan voic:latency --live');

            return self::FAILURE;
        }

        $this->summarize($rows);

        return self::SUCCESS;
    }

    /**
     * Latency recorded on real turns (set by the streaming answer endpoint).
     *
     * @return list<array<string, mixed>>
     */
    protected function recorded(): array
    {
        return SessionMessage::query()
            ->where('sender', 'ai')
            ->where('created_at', '>=', now()->subDays(max(1, (int) $this->option('days'))))
            ->whereNotNull('meta')
            ->orderBy('id')
            ->get(['id', 'meta'])
            ->filter(fn (SessionMessage $message) => isset($message->meta['latency']['total_ms']))
            ->map(fn (SessionMessage $message) => $message->meta['latency'] + ['engine' => $message->meta['engine'] ?? '?'])
            ->values()
            ->all();
    }

    /**
     * Simulated turns through the real pipeline (AI model + text-to-speech). Speech-to-text is not
     * included because there is no recording; it is measured on real turns.
     *
     * @return list<array<string, mixed>>
     */
    protected function live(ExamFlowService $flow, ExamBlueprint $blueprint): array
    {
        $turns = max(1, (int) $this->option('turns'));
        $definition = $blueprint->mode('wawancara');
        $session = PracticeSession::create([
            'user_id' => $this->benchmarkUser()->id,
            'ai_role_id' => AiRole::where('role_type', $definition['role_type'])->value('id'),
            'scenario_type' => 'Uji jeda (voic:latency)',
            'exam_mode' => 'wawancara',
            'duration_seconds' => 0,
            'feedback_notes' => ['language' => 'id', 'topic' => 'Data Analyst di e-commerce', 'synthetic' => true],
        ]);
        $flow->start($session, 'wawancara', [1, 3], 'id', 'Data Analyst di e-commerce');
        $this->components->info("Mengukur {$turns} giliran di laptop ini (model AI + suara penguji)…");

        $rows = [];
        try {
            for ($i = 0; $i < $turns; $i++) {
                if (($session->refresh()->exam_state['phase'] ?? null) === ExamFlowService::PHASE_COMPLETED) {
                    break;
                }
                $startedAt = hrtime(true);
                $elapsed = fn (): int => (int) round((hrtime(true) - $startedAt) / 1e6);
                $row = ['stt_ms' => null, 'first_sentence_ms' => null, 'first_sound_ms' => null];
                $flow->answer($session, self::ANSWERS[$i % count(self::ANSWERS)], 20, null, $i * 30, function (string $text, ?string $audioUrl) use (&$row, $elapsed) {
                    $row['first_sentence_ms'] ??= $elapsed();
                    if ($audioUrl !== null) {
                        $row['first_sound_ms'] ??= $elapsed();
                    }
                });
                $row['total_ms'] = $elapsed();
                $row['engine'] = $session->messages()->where('sender', 'ai')->latest('id')->value('meta')['engine'] ?? '?';
                $rows[] = $row;
                $this->line(sprintf('  giliran %d · suara pertama %s · selesai %s · %s', $i + 1, $this->seconds($row['first_sound_ms'] ?? $row['first_sentence_ms']), $this->seconds($row['total_ms']), $row['engine']));
            }
        } finally {
            $session->deleteWithRecordings();
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function summarize(array $rows): void
    {
        $columns = [
            'stt_ms' => 'Suara peserta → teks',
            'first_sentence_ms' => 'Sampai kalimat pertama penguji',
            'first_sound_ms' => 'Sampai suara penguji terdengar',
            'total_ms' => 'Sampai balasan selesai dibuat',
        ];
        $table = [];
        foreach ($columns as $key => $label) {
            $values = array_values(array_filter(array_column($rows, $key), fn ($value) => $value !== null));
            if ($values === []) {
                continue;
            }
            $table[] = [$label, count($values), $this->seconds($this->percentile($values, 50)), $this->seconds($this->percentile($values, 90))];
        }
        $this->newLine();
        $this->table(['Jeda', 'Giliran', 'Median', '90% giliran di bawah'], $table);

        $engines = array_count_values(array_map('strval', array_column($rows, 'engine')));
        arsort($engines);
        $this->line('  Mesin AI: '.implode(', ', array_map(fn ($engine, $count) => "{$engine} ({$count})", array_keys($engines), $engines)));

        // What the candidate feels: from finishing their answer until the examiner starts talking
        // (all timings count from the moment the answer arrived, so speech-to-text is included).
        $waits = array_values(array_filter(array_map(fn ($row) => $row['first_sound_ms'] ?? $row['first_sentence_ms'], $rows), fn ($value) => $value !== null));
        if ($waits === []) {
            return;
        }
        $median = $this->percentile($waits, 50);
        $p90 = $this->percentile($waits, 90);
        $this->newLine();
        if ($p90 <= self::TARGET_MS) {
            $this->components->info(sprintf('Bagus: 90%% giliran, penguji mulai bicara dalam %s (target < %s).', $this->seconds($p90), $this->seconds(self::TARGET_MS)));
        } else {
            $this->components->warn(sprintf('Median %s, 90%% giliran %s: di atas target %s. Lihat baris paling lambat di tabel di atas.', $this->seconds($median), $this->seconds($p90), $this->seconds(self::TARGET_MS)));
            $this->line('  Tips: matikan mode berpikir model, pakai model lebih kecil (qwen3:4b), atau jalankan setup_models.py --gpu bila ada GPU NVIDIA.');
        }
        $this->line(sprintf('  Untuk pitch deck: "Penguji mulai menjawab rata-rata %s setelah peserta selesai bicara (median, %d giliran)."', $this->seconds($median), count($waits)));
        if ($this->option('live')) {
            $this->line('  <fg=gray>Mode --live tanpa rekaman suara, jadi waktu suara → teks (biasanya 0,5-2 dtk) belum termasuk. Angka dari ujian sungguhan sudah termasuk.</>');
        }
    }

    /**
     * @param  list<int|float>  $values
     */
    protected function percentile(array $values, int $percent): float
    {
        sort($values);
        $index = ($percent / 100) * (count($values) - 1);
        $lower = (int) floor($index);
        $upper = (int) ceil($index);

        return $values[$lower] + ($values[$upper] - $values[$lower]) * ($index - $lower);
    }

    protected function seconds(int|float|null $ms): string
    {
        return $ms === null ? '-' : number_format($ms / 1000, 1, ',', '.').' dtk';
    }

    /**
     * Same account as the teacher data: never counted as a booth visitor.
     */
    protected function benchmarkUser(): User
    {
        $user = User::firstOrNew(['email' => PracticeSession::TEACHER_EMAIL]);
        if (! $user->exists) {
            $user->forceFill([
                'name' => 'VOIC Teacher (data latih)',
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
                'has_completed_onboarding' => true,
            ])->save();
        }

        return $user;
    }
}
