<?php

namespace App\Console\Commands;

use App\Services\Exam\OpenRouterClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

#[Signature('voic:compare-models
    {--a=voic-qwen : Model before training (Ollama)}
    {--b=voic-coach : Model after training (Ollama)}
    {--eval=ai_service/datasets/sft/voic_exam_eval.jsonl : Held-out examples (from voic:export-training-data)}
    {--limit=30 : Number of examples}
    {--judge= : Optional judge model, e.g. openrouter/nvidia/nemotron-3-ultra-550b-a55b:free (otherwise only human rating)}
    {--score : Read the filled-in rating sheet and show the result}
    {--output=storage/app/compare : Folder for the rating sheet and results}')]
#[Description('Before vs after training: both models answer the same held-out turns; rated blind by people (and optionally a judge model)')]
class CompareModels extends Command
{
    /** Rating criteria, each 1-5. */
    public const CRITERIA = [
        'nyambung' => 'Menanggapi hal spesifik dari jawaban peserta',
        'alami' => 'Terdengar seperti penguji sungguhan, enak diucapkan',
        'tepat' => 'Mengikuti instruksi giliran (panjang, satu pertanyaan, dsb.)',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dir = base_path((string) $this->option('output'));

        return $this->option('score') ? $this->score($dir) : $this->generate($dir);
    }

    /**
     * Ask both models, write a blind rating sheet (A/B order shuffled per row) and the hidden key.
     */
    protected function generate(string $dir): int
    {
        $evalPath = base_path((string) $this->option('eval'));
        if (! File::exists($evalPath)) {
            $this->components->error("Set uji belum ada: {$this->option('eval')}. Jalankan dulu: php artisan voic:export-training-data");

            return self::FAILURE;
        }

        $models = ['A' => (string) $this->option('a'), 'B' => (string) $this->option('b')];
        $installed = $this->ollamaModels();
        if ($installed === null) {
            $this->components->error('Ollama tidak berjalan di '.$this->ollamaUrl().'.');

            return self::FAILURE;
        }
        foreach ($models as $model) {
            if (! in_array($model, $installed, true) && ! in_array($model.':latest', $installed, true)) {
                $this->components->error("Model {$model} belum ada di Ollama. Tersedia: ".implode(', ', $installed));

                return self::FAILURE;
            }
        }

        $examples = array_slice(array_values(array_filter(array_map(
            fn ($line) => json_decode($line, true),
            file($evalPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
        ))), 0, max(1, (int) $this->option('limit')));

        $judge = $this->option('judge') ? (string) $this->option('judge') : null;
        if ($judge !== null && ! OpenRouterClient::handles($judge)) {
            $this->components->error('Juri harus model OpenRouter, mis. openrouter/nvidia/nemotron-3-ultra-550b-a55b:free');

            return self::FAILURE;
        }

        $this->components->info(sprintf('%d contoh · A = %s · B = %s%s', count($examples), $models['A'], $models['B'], $judge ? " · juri {$judge}" : ''));

        $sheet = [array_merge(['no', 'konteks', 'jawaban_peserta', 'balasan_1', 'balasan_2'], $this->ratingColumns())];
        $key = [];
        $times = ['A' => [], 'B' => []];
        $judged = [];
        foreach ($examples as $index => $example) {
            $messages = array_slice($example['messages'], 0, -1);
            $replies = [];
            foreach ($models as $label => $model) {
                $startedAt = microtime(true);
                $replies[$label] = $this->reply($model, $messages);
                $times[$label][] = (int) round((microtime(true) - $startedAt) * 1000);
            }

            // Blind: the rater never knows which column is which model.
            $order = random_int(0, 1) === 0 ? ['A', 'B'] : ['B', 'A'];
            $key[$index + 1] = $order;
            $instruction = (string) end($messages)['content'];
            $sheet[] = array_merge(
                [$index + 1, $this->examinerQuestion($messages), (string) ($example['meta']['answer'] ?? ''), $replies[$order[0]], $replies[$order[1]]],
                array_fill(0, count($this->ratingColumns()), '')
            );

            if ($judge !== null) {
                $judged[$index + 1] = $this->judge($judge, $instruction, $replies);
            }
            $this->line(sprintf('  %d/%d · A %s dtk · B %s dtk', $index + 1, count($examples), number_format(end($times['A']) / 1000, 1, ',', ''), number_format(end($times['B']) / 1000, 1, ',', '')));
        }

        File::ensureDirectoryExists($dir);
        $this->writeCsv($dir.'/penilaian.csv', $sheet);
        File::put($dir.'/kunci.json', json_encode(['models' => $models, 'order' => $key, 'times_ms' => $times, 'judge' => $judge, 'judged' => $judged], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->newLine();
        $this->line('  <options=bold>Langkah berikutnya:</>');
        $this->line('  1. Buka '.$this->option('output').'/penilaian.csv di Excel. Isi nilai 1-5 untuk balasan_1 dan balasan_2 di setiap kolom kriteria.');
        $this->line('     Penilai tidak boleh membuka kunci.json, supaya penilaiannya buta. Makin banyak penilai, makin kuat (salin file per penilai, mis. penilaian_rina.csv).');
        $this->line('  2. Jalankan: php artisan voic:compare-models --score');
        if ($judge !== null) {
            $this->line('  (Nilai dari juri '.$judge.' sudah tersimpan dan ikut ditampilkan saat --score.)');
        }

        return self::SUCCESS;
    }

    /**
     * Average the filled-in sheets (every penilaian*.csv in the folder) per model, unblinded with the key.
     */
    protected function score(string $dir): int
    {
        if (! File::exists($dir.'/kunci.json')) {
            $this->components->error('kunci.json tidak ditemukan. Jalankan dulu tanpa --score.');

            return self::FAILURE;
        }
        $key = json_decode(File::get($dir.'/kunci.json'), true);
        $totals = ['A' => [], 'B' => []];
        $wins = ['A' => 0, 'B' => 0, 'seri' => 0];
        $raters = 0;

        foreach (File::glob($dir.'/penilaian*.csv') as $file) {
            $rows = $this->readCsv($file);
            $header = array_shift($rows);
            $filled = 0;
            foreach ($rows as $row) {
                $row = array_combine($header, array_pad($row, count($header), ''));
                $order = $key['order'][$row['no']] ?? null;
                if ($order === null) {
                    continue;
                }
                $sums = [];
                foreach ([1, 2] as $column) {
                    $label = $order[$column - 1];
                    foreach (array_keys(self::CRITERIA) as $criterion) {
                        $value = (int) ($row["{$criterion}_{$column}"] ?? 0);
                        if ($value >= 1 && $value <= 5) {
                            $totals[$label][$criterion][] = $value;
                            $sums[$label] = ($sums[$label] ?? 0) + $value;
                        }
                    }
                }
                if (count($sums) === 2) {
                    $filled++;
                    $wins[$sums['A'] === $sums['B'] ? 'seri' : ($sums['A'] > $sums['B'] ? 'A' : 'B')]++;
                }
            }
            if ($filled > 0) {
                $raters++;
            }
        }

        if ($raters === 0) {
            $this->components->warn('Belum ada nilai yang diisi di penilaian*.csv.');

            return self::FAILURE;
        }

        $models = $key['models'];
        $rows = [];
        foreach (self::CRITERIA as $criterion => $label) {
            $rows[] = [$label, $this->average($totals['A'][$criterion] ?? []), $this->average($totals['B'][$criterion] ?? [])];
        }
        $rows[] = ['Waktu menjawab (median)', $this->median($key['times_ms']['A']), $this->median($key['times_ms']['B'])];
        $this->table(['Penilaian manusia (1-5)', "A: {$models['A']}", "B: {$models['B']}"], $rows);
        $comparisons = array_sum($wins);
        $this->line(sprintf('  %d penilai · %d perbandingan · B lebih baik %d kali (%d%%), A lebih baik %d kali, seri %d.',
            $raters, $comparisons, $wins['B'], round($wins['B'] / max(1, $comparisons) * 100), $wins['A'], $wins['seri']));

        if (! empty($key['judged'])) {
            $judgeTotals = ['A' => [], 'B' => []];
            foreach ($key['judged'] as $scores) {
                foreach (['A', 'B'] as $label) {
                    if (isset($scores[$label])) {
                        $judgeTotals[$label][] = $scores[$label];
                    }
                }
            }
            $this->line(sprintf('  Juri %s (rata-rata 1-5): A %s · B %s', $key['judge'], $this->average($judgeTotals['A']), $this->average($judgeTotals['B'])));
        }

        $nyambungA = $this->mean($totals['A']['nyambung'] ?? []);
        $nyambungB = $this->mean($totals['B']['nyambung'] ?? []);
        if ($nyambungA !== null && $nyambungB !== null) {
            $this->newLine();
            $this->line('  <options=bold>Kalimat untuk pitch deck:</>');
            $this->line(sprintf('  Dinilai buta oleh %d penilai pada %d percakapan uji, skor "nyambung" naik dari %s ke %s (skala 1-5) setelah model dilatih, dan balasan model baru dipilih lebih baik pada %d%% perbandingan.',
                $raters, count($key['order']), number_format($nyambungA, 1, ',', ''), number_format($nyambungB, 1, ',', ''), round($wins['B'] / max(1, $comparisons) * 100)));
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    protected function reply(string $model, array $messages): string
    {
        try {
            $response = Http::timeout(180)->post($this->ollamaUrl().'/api/chat', [
                'model' => $model,
                'messages' => $messages,
                'stream' => false,
                'think' => false,
                'keep_alive' => '30m',
                'options' => ['temperature' => 0.4, 'num_predict' => 120, 'num_ctx' => 4096],
            ]);

            return trim((string) preg_replace('/<think>.*?<\/think>/s', '', (string) $response->json('message.content', '')));
        } catch (\Throwable $exception) {
            return '(gagal: '.$exception->getMessage().')';
        }
    }

    /**
     * Judge model: scores both replies 1-5 on how well they follow up on the candidate's answer.
     *
     * @param  array{A: string, B: string}  $replies
     * @return array{A?: int, B?: int}
     */
    protected function judge(string $judge, string $instruction, array $replies): array
    {
        // Shuffle for the judge too, so position bias does not favour one model.
        $first = random_int(0, 1) === 0 ? 'A' : 'B';
        $second = $first === 'A' ? 'B' : 'A';
        $prompt = "Kamu menilai balasan penguji AI dalam simulasi ujian lisan berbahasa Indonesia.\n\n"
            ."Konteks giliran (pertanyaan, jawaban peserta, dan instruksi untuk penguji):\n{$instruction}\n\n"
            ."Balasan X: {$replies[$first]}\nBalasan Y: {$replies[$second]}\n\n"
            .'Beri nilai 1-5 untuk masing-masing: seberapa nyambung dengan jawaban peserta, alami diucapkan, dan mengikuti instruksi. '
            .'Jawab HANYA dengan JSON: {"X": <nilai>, "Y": <nilai>}';
        $raw = (string) app(OpenRouterClient::class)->chat([['role' => 'user', 'content' => $prompt]], $judge, 0.1);
        if (! preg_match('/\{[^}]*\}/', $raw, $match)) {
            return [];
        }
        $scores = json_decode($match[0], true) ?: [];

        return array_filter([
            $first => isset($scores['X']) ? max(1, min(5, (int) $scores['X'])) : null,
            $second => isset($scores['Y']) ? max(1, min(5, (int) $scores['Y'])) : null,
        ]);
    }

    /**
     * @return list<string>
     */
    protected function ratingColumns(): array
    {
        $columns = [];
        foreach (array_keys(self::CRITERIA) as $criterion) {
            $columns[] = "{$criterion}_1";
            $columns[] = "{$criterion}_2";
        }

        return $columns;
    }

    /**
     * The examiner's last line (the question being answered), short enough for a spreadsheet cell.
     *
     * @param  list<array{role: string, content: string}>  $messages
     */
    protected function examinerQuestion(array $messages): string
    {
        // The question being answered is the examiner message right before the candidate's answer.
        $question = $messages[count($messages) - 2] ?? null;

        return mb_substr((string) (($question['role'] ?? null) === 'assistant' ? $question['content'] : end($messages)['content']), 0, 400);
    }

    /**
     * @return list<string>|null
     */
    protected function ollamaModels(): ?array
    {
        try {
            return collect(Http::timeout(5)->get($this->ollamaUrl().'/api/tags')->throw()->json('models', []))->pluck('name')->all();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function ollamaUrl(): string
    {
        return rtrim((string) Config::get('services.ollama.url', 'http://127.0.0.1:11434'), '/');
    }

    /**
     * @param  list<list<mixed>>  $lines
     */
    protected function writeCsv(string $path, array $lines): void
    {
        $handle = fopen($path, 'w');
        fwrite($handle, "\xEF\xBB\xBF");
        foreach ($lines as $line) {
            fputcsv($handle, $line, ';', escape: '');
        }
        fclose($handle);
    }

    /**
     * Reads ";" or "," separated sheets (Excel may save either, depending on the region setting).
     *
     * @return list<list<string>>
     */
    protected function readCsv(string $path): array
    {
        $content = (string) preg_replace('/^\xEF\xBB\xBF/', '', File::get($path));
        $firstLine = strtok($content, "\n");
        $separator = substr_count((string) $firstLine, ';') >= substr_count((string) $firstLine, ',') ? ';' : ',';
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $content);
        rewind($handle);
        $rows = [];
        while (($row = fgetcsv($handle, null, $separator, escape: '')) !== false) {
            if ($row !== [null]) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @param  list<int>  $values
     */
    protected function mean(array $values): ?float
    {
        return $values === [] ? null : array_sum($values) / count($values);
    }

    /**
     * @param  list<int>  $values
     */
    protected function average(array $values): string
    {
        $mean = $this->mean($values);

        return $mean === null ? '-' : number_format($mean, 2, ',', '');
    }

    /**
     * @param  list<int>  $values
     */
    protected function median(array $values): string
    {
        if ($values === []) {
            return '-';
        }
        sort($values);
        $middle = intdiv(count($values), 2);
        $median = count($values) % 2 ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;

        return number_format($median / 1000, 1, ',', '').' dtk';
    }
}
