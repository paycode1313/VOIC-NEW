<?php

namespace App\Services\Exam;

use App\Models\PracticeSession;
use App\Models\User;

/**
 * Band-score history of a user's finished mock exams, oldest first.
 */
class ExamProgressService
{
    /**
     * Suggested parts to practise for each weak criterion.
     *
     * @var array<string, list<int>>
     */
    public const DRILL_PARTS = [
        'fluency' => [2],      // a 2-minute long turn trains steady, linked speech
        'vocabulary' => [2, 3],
        'structure' => [3],    // discussion questions force answer-reason-example
        'delivery' => [1],     // short answers with the camera on
    ];

    public function __construct(protected ExamBlueprint $blueprint) {}

    /**
     * @return array{
     *     points: list<array{id: int, date: string, label: string, mode: string, overall: float, criteria: array<string, float>, url: string}>,
     *     labels: array<string, string>,
     *     latest: ?float,
     *     delta: ?float,
     *     best: ?float,
     *     weakest: ?string
     * }
     */
    public function forUser(User $user, int $limit = 12): array
    {
        $sessions = $user->practiceSessions()
            ->whereNotNull('exam_mode')
            ->latest()
            ->take($limit * 2)
            ->get()
            ->filter(fn (PracticeSession $session) => isset($session->feedback_notes['exam_report']))
            ->take($limit)
            ->reverse()
            ->values();

        $labels = [];
        $points = $sessions->map(function (PracticeSession $session) use (&$labels) {
            $report = $session->feedback_notes['exam_report'];
            foreach ($report['criteria'] as $key => $criterion) {
                $labels[$key] ??= $criterion['label'];
            }

            return [
                'id' => $session->id,
                'date' => $session->created_at->locale('id')->translatedFormat('d M'),
                'label' => $this->blueprint->text($this->blueprint->mode($session->exam_mode)['title'], $report['language'] ?? 'id'),
                'mode' => $session->exam_mode,
                'overall' => (float) $report['overall_band'],
                'criteria' => array_map(fn ($criterion) => (float) $criterion['band'], $report['criteria']),
                'url' => route('exam.result', $session),
            ];
        })->all();

        $latest = $points === [] ? null : end($points);
        $previous = count($points) > 1 ? $points[count($points) - 2] : null;

        $weakest = null;
        if ($latest !== null) {
            $criteria = $latest['criteria'];
            asort($criteria);
            $weakest = array_key_first($criteria);
        }

        return [
            'points' => $points,
            'labels' => $labels,
            'latest' => $latest['overall'] ?? null,
            'delta' => $latest && $previous ? round($latest['overall'] - $previous['overall'], 1) : null,
            'best' => $points === [] ? null : max(array_column($points, 'overall')),
            'weakest' => $weakest,
        ];
    }

    /**
     * Setup URL that pre-selects the parts that train a criterion.
     */
    public function drillUrl(string $mode, string $criterion): string
    {
        return route('exam.setup', ['mode' => $mode, 'parts' => self::DRILL_PARTS[$criterion] ?? [1, 2, 3], 'focus' => $criterion]);
    }
}
