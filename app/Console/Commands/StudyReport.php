<?php

namespace App\Console\Commands;

use App\Services\Exam\ExamReportService;
use App\Services\Study\UserStudy;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('voic:study-report
    {--output=storage/app/study : Folder for the CSV files and the chart}')]
#[Description('User trial results: practice 1 vs practice 3 per participant, questionnaire averages, CSV and a chart for the pitch deck')]
class StudyReport extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(UserStudy $study): int
    {
        $participants = $study->participants();
        if ($participants === []) {
            $this->components->warn('Belum ada data uji coba. Peserta perlu mengisi "Kode peserta" di halaman persiapan latihan.');

            return self::FAILURE;
        }

        $complete = array_values(array_filter($participants, fn ($p) => $p['attempts'] >= UserStudy::ATTEMPTS_FOR_SURVEY));
        $dir = base_path((string) $this->option('output'));
        File::ensureDirectoryExists($dir);

        $this->writeParticipantsCsv($dir.'/peserta.csv', $participants);
        $this->writeSurveysCsv($dir.'/kuesioner.csv', $participants);

        $this->components->info(sprintf(
            '%d peserta, %d sudah %d kali latihan, %d mengisi kuesioner.',
            count($participants), count($complete), UserStudy::ATTEMPTS_FOR_SURVEY, count(array_filter($participants, fn ($p) => $p['survey'] !== null))
        ));

        $rows = [];
        $summary = null;
        if ($complete !== []) {
            $summary = $this->summary($complete);
            foreach ($summary['metrics'] as $metric) {
                $rows[] = [$metric['label'], $this->number($metric['before']), $this->number($metric['after']), $this->change($metric)];
            }
            $this->table(['Ukuran (rata-rata)', 'Latihan ke-1', 'Latihan ke-'.UserStudy::ATTEMPTS_FOR_SURVEY, 'Perubahan'], $rows);
            $this->line(sprintf('  Skor naik pada <fg=green>%d dari %d</> peserta.', $summary['improved'], count($complete)));
        } else {
            $this->components->warn('Belum ada peserta yang menyelesaikan '.UserStudy::ATTEMPTS_FOR_SURVEY.' latihan, perbandingan belum bisa dihitung.');
        }

        $survey = $this->surveyAverages($participants);
        if ($survey !== []) {
            $this->newLine();
            $this->table(['Kuesioner (1-5)', 'Rata-rata', 'Setuju (4-5)'], array_map(
                fn ($row) => [$row['question'], $this->number($row['average']), $row['agree'].'%'],
                $survey
            ));
        }

        if ($summary !== null) {
            File::put($dir.'/grafik.html', $this->chart($summary, count($complete)));
            $overall = $summary['metrics']['overall_band'];
            $sentence = sprintf(
                'Dari %d peserta yang mencoba %d kali, skor rata-rata naik dari %s ke %s (band 0-9, %s), dan skor %d peserta meningkat.',
                count($complete), UserStudy::ATTEMPTS_FOR_SURVEY, $this->number($overall['before']), $this->number($overall['after']),
                $this->change($overall), $summary['improved']
            );
            $this->newLine();
            $this->line('  <options=bold>Kalimat untuk pitch deck:</>');
            $this->line('  '.$sentence);
        }

        $this->newLine();
        $this->line('  <fg=gray>→</> '.$this->option('output').'/peserta.csv, kuesioner.csv'.($summary ? ', grafik.html (buka di browser, lalu screenshot untuk slide)' : ''));

        return self::SUCCESS;
    }

    /**
     * Averages of practice 1 and practice 3 over the participants who did both.
     *
     * @param  list<array<string, mixed>>  $complete
     * @return array{metrics: array<string, array{label: string, before: ?float, after: ?float, lower_is_better: bool}>, improved: int}
     */
    protected function summary(array $complete): array
    {
        $labels = [
            'overall_band' => 'Skor keseluruhan (band 0-9)',
            'fluency' => 'Kelancaran & Alur',
            'vocabulary' => 'Kosakata & Istilah',
            'structure' => 'Isi & Struktur',
            'delivery' => 'Delivery & Kepercayaan Diri',
            'fillers_per_minute' => 'Kata pengisi per menit',
            'average_wpm' => 'Kata per menit',
        ];
        $metrics = [];
        foreach ($labels as $key => $label) {
            $value = fn ($attempt) => in_array($key, ExamReportService::CRITERIA, true) ? ($attempt->criteria[$key] ?? null) : $attempt->{$key};
            // Paired: only participants who have the value in both practices.
            $pairs = array_filter(array_map(fn ($p) => [$value($p['first']), $value($p['last'])], $complete), fn ($pair) => $pair[0] !== null && $pair[1] !== null);
            $metrics[$key] = [
                'label' => $label,
                'before' => $pairs === [] ? null : array_sum(array_column($pairs, 0)) / count($pairs),
                'after' => $pairs === [] ? null : array_sum(array_column($pairs, 1)) / count($pairs),
                'lower_is_better' => $key === 'fillers_per_minute',
            ];
        }

        return [
            'metrics' => $metrics,
            'improved' => count(array_filter($complete, fn ($p) => $p['last']->overall_band > $p['first']->overall_band)),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $participants
     * @return list<array{question: string, average: float, agree: int}>
     */
    protected function surveyAverages(array $participants): array
    {
        $surveys = array_values(array_filter(array_column($participants, 'survey')));
        if ($surveys === []) {
            return [];
        }

        $rows = [];
        foreach (UserStudy::QUESTIONS as $key => $question) {
            $values = array_values(array_filter(array_map(fn ($survey) => $survey->answers[$key] ?? null, $surveys)));
            if ($values === []) {
                continue;
            }
            $rows[] = [
                'question' => $question['id'],
                'average' => array_sum($values) / count($values),
                'agree' => (int) round(count(array_filter($values, fn ($v) => $v >= 4)) / count($values) * 100),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $participants
     */
    protected function writeParticipantsCsv(string $path, array $participants): void
    {
        $lines = [['kode', 'jumlah_latihan', 'mode', 'band_1', 'band_3', 'kelancaran_1', 'kelancaran_3', 'kosakata_1', 'kosakata_3', 'struktur_1', 'struktur_3', 'delivery_1', 'delivery_3', 'pengisi_per_menit_1', 'pengisi_per_menit_3', 'wpm_1', 'wpm_3']];
        foreach ($participants as $p) {
            [$first, $last] = [$p['first'], $p['attempts'] >= UserStudy::ATTEMPTS_FOR_SURVEY ? $p['last'] : null];
            $line = [$p['code'], $p['attempts'], $first->mode, $first->overall_band, $last?->overall_band];
            foreach (ExamReportService::CRITERIA as $criterion) {
                $line[] = $first->criteria[$criterion] ?? null;
                $line[] = $last?->criteria[$criterion] ?? null;
            }
            array_push($line, $first->fillers_per_minute, $last?->fillers_per_minute, $first->average_wpm, $last?->average_wpm);
            $lines[] = $line;
        }
        $this->writeCsv($path, $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $participants
     */
    protected function writeSurveysCsv(string $path, array $participants): void
    {
        $lines = [array_merge(['kode'], array_keys(UserStudy::QUESTIONS), ['komentar'])];
        foreach ($participants as $p) {
            if ($p['survey'] === null) {
                continue;
            }
            $lines[] = array_merge([$p['code']], array_map(fn ($key) => $p['survey']->answers[$key] ?? null, array_keys(UserStudy::QUESTIONS)), [$p['survey']->comment]);
        }
        $this->writeCsv($path, $lines);
    }

    /**
     * @param  list<list<mixed>>  $lines
     */
    protected function writeCsv(string $path, array $lines): void
    {
        $handle = fopen($path, 'w');
        fwrite($handle, "\xEF\xBB\xBF"); // BOM so Excel opens the file as UTF-8
        foreach ($lines as $line) {
            fputcsv($handle, $line, ';', escape: '');
        }
        fclose($handle);
    }

    /**
     * A self-contained bar chart (practice 1 vs 3 per criterion), ready to screenshot for a slide.
     *
     * @param  array{metrics: array<string, array{label: string, before: ?float, after: ?float, lower_is_better: bool}>, improved: int}  $summary
     */
    protected function chart(array $summary, int $participants): string
    {
        $keys = ['overall_band', 'fluency', 'vocabulary', 'structure', 'delivery'];
        $groupWidth = 150;
        $height = 260;
        $top = 40;
        $scale = fn (float $band) => $band / 9 * $height;
        $bars = '';
        foreach ($keys as $index => $key) {
            $metric = $summary['metrics'][$key];
            $x = 70 + $index * $groupWidth;
            foreach ([['before', '#94a3b8', 0], ['after', '#2563eb', 52]] as [$field, $color, $offset]) {
                if ($metric[$field] === null) {
                    continue;
                }
                $barHeight = $scale($metric[$field]);
                $y = $top + $height - $barHeight;
                $bars .= sprintf('<rect x="%d" y="%.1f" width="44" height="%.1f" rx="6" fill="%s"/>', $x + $offset, $y, $barHeight, $color);
                $bars .= sprintf('<text x="%d" y="%.1f" text-anchor="middle" font-size="15" font-weight="700" fill="#0f172a">%s</text>', $x + $offset + 22, $y - 8, $this->number($metric[$field]));
            }
            $label = str_replace(' (band 0-9)', '', explode(' & ', $metric['label'])[0]);
            $bars .= sprintf('<text x="%d" y="%d" text-anchor="middle" font-size="14" fill="#334155">%s</text>', $x + 48, $top + $height + 26, e($label));
        }
        $width = 70 + count($keys) * $groupWidth;
        $axis = sprintf('<line x1="60" y1="%d" x2="%d" y2="%d" stroke="#cbd5e1"/>', $top + $height, $width, $top + $height);
        $title = e(sprintf('Skor rata-rata latihan ke-1 vs ke-%d (band 0-9, n = %d)', UserStudy::ATTEMPTS_FOR_SURVEY, $participants));

        return <<<HTML
            <!doctype html>
            <html lang="id"><head><meta charset="utf-8"><title>VOIC: hasil uji coba</title></head>
            <body style="font-family: system-ui, sans-serif; margin: 40px; color: #0f172a">
            <h1 style="font-size: 22px">{$title}</h1>
            <svg viewBox="0 0 {$width} 340" width="{$width}" role="img" aria-label="{$title}">{$axis}{$bars}</svg>
            <p style="font-size: 15px"><span style="color:#94a3b8">■</span> Latihan ke-1 &nbsp; <span style="color:#2563eb">■</span> Latihan ke-{$this->attempts()}</p>
            </body></html>
            HTML;
    }

    protected function attempts(): int
    {
        return UserStudy::ATTEMPTS_FOR_SURVEY;
    }

    /**
     * @param  array{before: ?float, after: ?float, lower_is_better: bool}  $metric
     */
    protected function change(array $metric): string
    {
        if ($metric['before'] === null || $metric['after'] === null || $metric['before'] == 0) {
            return '-';
        }
        $percent = ($metric['after'] - $metric['before']) / $metric['before'] * 100;

        return sprintf('%s%s%%', $percent >= 0 ? '+' : '', number_format($percent, 0, ',', '.'));
    }

    protected function number(?float $value): string
    {
        return $value === null ? '-' : number_format($value, 1, ',', '.');
    }
}
