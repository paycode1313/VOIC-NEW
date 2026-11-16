<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AiRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiRoleController extends Controller
{
    /**
     * Get active AI roles and practice scenarios for mobile selection.
     */
    public function index(Request $request): JsonResponse
    {
        $roles = AiRole::where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(function (AiRole $role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'role_type' => $role->role_type,
                    'difficulty_level' => $role->difficulty_level,
                    'voice_id' => $role->voice_id,
                    'description' => $role->description,
                    'avatar' => $role->avatar,
                    'personality_traits' => $role->personality_traits,
                ];
            });

        $scenarios = [
            [
                'id' => 'sidang-skripsi',
                'name' => 'Sidang Skripsi',
                'icon' => '🎓',
                'badge' => 'Akademik',
                'badge_color' => 'indigo',
                'role_type' => 'dosen_penguji',
                'description' => 'Latihan presentasi tugas akhir/skripsi bersama Dosen Penguji senior. Fokus pada ketenangan, metodologi, dan argumentasi ilmiah.',
                'prompt_guide' => 'Jelaskan latar belakang, rumusan masalah, serta kebaruan (novelty) dari penelitian Anda dalam waktu 2-3 menit.',
                'target_duration' => '180 detik',
            ],
            [
                'id' => 'wawancara-kerja',
                'name' => 'Wawancara Kerja',
                'icon' => '💼',
                'badge' => 'Karier',
                'badge_color' => 'amber',
                'role_type' => 'hrd',
                'description' => 'Simulasi menjawab pertanyaan interview bersama HRD Recruiter dengan metode STAR. Fokus pada ekspresi ramah, kontak mata, dan kejelasan jawaban.',
                'prompt_guide' => 'Ceritakan pengalaman nyata saat menghadapi tantangan besar dalam tim dan bagaimana Anda mengatasinya.',
                'target_duration' => '120 detik',
            ],
            [
                'id' => 'pitching-startup',
                'name' => 'Pitching Startup',
                'icon' => '🚀',
                'badge' => 'Bisnis',
                'badge_color' => 'purple',
                'role_type' => 'investor',
                'description' => 'Simulasi presentasi elevator pitch di hadapan Venture Capitalist/Angel Investor. Fokus pada problem-solution fit, bisnis model, dan daya tarik pasar.',
                'prompt_guide' => 'Sampaikan elevator pitch: masalah pasar, keunikan solusi produk Anda, dan model bisnis dalam 1-2 menit.',
                'target_duration' => '90 detik',
            ],
            [
                'id' => 'presentasi-umum',
                'name' => 'Presentasi Umum',
                'icon' => '🎙️',
                'badge' => 'Public Speaking',
                'badge_color' => 'emerald',
                'role_type' => 'dosen_penguji',
                'description' => 'Latihan berbicara di depan publik/seminar. Fokus pada transisi kalimat yang mengalir, senyum ramah, dan keterikatan audiens.',
                'prompt_guide' => 'Buka presentasi Anda dengan hook cerita menarik, lalu jelaskan poin inti materi yang ingin Anda sampaikan.',
                'target_duration' => '120 detik',
            ],
        ];

        return response()->json([
            'success' => true,
            'roles' => $roles,
            'scenarios' => $scenarios,
        ]);
    }
}
