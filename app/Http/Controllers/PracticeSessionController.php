<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePracticeSessionRequest;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PracticeSessionController extends Controller
{
    /**
     * Show the practice setup and live AI camera session interface.
     */
    public function create(Request $request): View
    {
        $scenarios = [
            [
                'id' => 'sidang-skripsi',
                'name' => 'Sidang Skripsi',
                'icon' => '🎓',
                'badge' => 'Akademik',
                'badge_color' => 'indigo',
                'description' => 'Latihan presentasi tugas akhir/skripsi. Fokus pada ketenangan, kejelasan metodologi, dan kontak mata meyakinkan ke arah penguji.',
                'prompt_guide' => 'Jelaskan latar belakang, rumusan masalah, serta kebaruan (novelty) dari penelitian Anda dalam waktu 2-3 menit.',
                'target_duration' => '180 detik',
            ],
            [
                'id' => 'pitching-startup',
                'name' => 'Pitching Startup',
                'icon' => '🚀',
                'badge' => 'Bisnis',
                'badge_color' => 'purple',
                'description' => 'Simulasi presentasi elevator pitch di hadapan investor/juri Innofest. Fokus pada antusiasme tinggi, pemaparan solusi, dan artikulasi vokal yang tajam.',
                'prompt_guide' => 'Sampaikan elevator pitch: masalah pasar yang dihadapi, keunikan solusi produk Anda, dan model bisnis dalam 1-2 menit.',
                'target_duration' => '90 detik',
            ],
            [
                'id' => 'presentasi-umum',
                'name' => 'Presentasi Umum',
                'icon' => '🎙️',
                'badge' => 'Public Speaking',
                'badge_color' => 'emerald',
                'description' => 'Latihan berbicara di depan publik/seminar. Fokus pada transisi kalimat yang mengalir, senyum ramah, dan keterikatan audiens.',
                'prompt_guide' => 'Buka presentasi Anda dengan hook cerita menarik, lalu jelaskan poin inti materi yang ingin Anda sampaikan.',
                'target_duration' => '120 detik',
            ],
            [
                'id' => 'wawancara-kerja',
                'name' => 'Wawancara Kerja',
                'icon' => '💼',
                'badge' => 'Karier',
                'badge_color' => 'amber',
                'description' => 'Simulasi menjawab pertanyaan interview HRD/User. Fokus pada ketenangan gestur wajah, tidak terburu-buru, dan vokal yang tegas.',
                'prompt_guide' => 'Jawab pertanyaan: "Ceritakan salah satu tantangan terbesar yang pernah Anda hadapi dan bagaimana Anda mengatasinya?"',
                'target_duration' => '120 detik',
            ],
        ];

        return view('practice.create', [
            'scenarios' => $scenarios,
        ]);
    }

    /**
     * Store a newly created practice session result in MySQL.
     */
    public function store(StorePracticeSessionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $session = $user->practiceSessions()->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Sesi latihan berhasil disimpan.',
            'session_id' => $session->id,
            'redirect_url' => route('practice.show', $session),
        ], 201);
    }

    /**
     * Display detailed post-practice feedback and performance metrics.
     */
    public function show(PracticeSession $practiceSession): View
    {
        Gate::authorize('view', $practiceSession);

        return view('practice.show', [
            'session' => $practiceSession,
        ]);
    }
}
