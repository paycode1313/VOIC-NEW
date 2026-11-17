<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CompareModelsTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = 'storage/framework/testing/compare_'.uniqid();
        File::ensureDirectoryExists(base_path($this->dir));

        $examples = [];
        foreach (range(1, 4) as $i) {
            $examples[] = json_encode(['messages' => [
                ['role' => 'system', 'content' => 'Kamu adalah Kak Nadia.'],
                ['role' => 'assistant', 'content' => "Pertanyaan nomor {$i}?"],
                ['role' => 'user', 'content' => "Jawaban {$i}.\n[Bagian 1 - Perkenalan. Balas maksimal 2 kalimat.]"],
                ['role' => 'assistant', 'content' => 'Target.'],
            ], 'meta' => ['answer' => "Jawaban {$i}."]]);
        }
        File::put(base_path($this->dir.'/eval.jsonl'), implode("\n", $examples)."\n");

        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'voic-qwen:latest'], ['name' => 'voic-coach:latest']]]),
            '*/api/chat' => fn ($request) => Http::response(['message' => ['content' => $request['model'] === 'voic-coach' ? 'BARU nyambung.' : 'LAMA umum.']]),
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path($this->dir));
        parent::tearDown();
    }

    public function test_blind_rating_sheet_is_unblinded_when_scored(): void
    {
        $this->artisan('voic:compare-models', ['--eval' => $this->dir.'/eval.jsonl', '--output' => $this->dir])
            ->expectsOutputToContain('4 contoh')
            ->assertSuccessful();

        $sheet = array_map(fn ($line) => str_getcsv($line, ';', '"', ''), file(base_path($this->dir.'/penilaian.csv'), FILE_IGNORE_NEW_LINES));
        $header = array_shift($sheet);
        $this->assertSame(['no', 'konteks', 'jawaban_peserta', 'balasan_1', 'balasan_2'], array_slice(array_map(fn ($h) => ltrim($h, "\u{FEFF}"), $header), 0, 5));
        $this->assertSame('Pertanyaan nomor 1?', $sheet[0][1]);
        // The sheet never names the models.
        $this->assertStringNotContainsString('voic-coach', File::get(base_path($this->dir.'/penilaian.csv')));

        // A rater who always prefers the newer, more specific reply.
        $header[0] = 'no';
        $filled = [implode(';', $header)];
        foreach ($sheet as $row) {
            $row = array_combine($header, $row);
            foreach ([1, 2] as $column) {
                $value = str_starts_with($row["balasan_{$column}"], 'BARU') ? 5 : 2;
                foreach (['nyambung', 'alami', 'tepat'] as $criterion) {
                    $row["{$criterion}_{$column}"] = $value;
                }
            }
            $filled[] = implode(';', $row);
        }
        File::put(base_path($this->dir.'/penilaian_rina.csv'), implode("\n", $filled));
        File::delete(base_path($this->dir.'/penilaian.csv'));

        $this->artisan('voic:compare-models', ['--score' => true, '--output' => $this->dir])
            ->expectsOutputToContain('B lebih baik 4 kali (100%)')
            ->expectsOutputToContain('skor "nyambung" naik dari 2,0 ke 5,0')
            ->assertSuccessful();
    }

    public function test_missing_model_or_eval_set_is_explained(): void
    {
        $this->artisan('voic:compare-models', ['--eval' => $this->dir.'/tidak-ada.jsonl', '--output' => $this->dir])
            ->expectsOutputToContain('voic:export-training-data')
            ->assertFailed();

        $this->artisan('voic:compare-models', ['--eval' => $this->dir.'/eval.jsonl', '--b' => 'model-lain', '--output' => $this->dir])
            ->expectsOutputToContain('Model model-lain belum ada di Ollama')
            ->assertFailed();
    }
}
