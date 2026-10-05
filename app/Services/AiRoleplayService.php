<?php

namespace App\Services;

use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\SessionMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiRoleplayService
{
    /**
     * AI service base URL.
     */
    protected string $aiServiceUrl;

    /**
     * Timeout for AI service requests.
     */
    protected int $timeout;

    /**
     * Initialize service configuration.
     */
    public function __construct()
    {
        $this->aiServiceUrl = (string) Config::get('services.ai_service.url', 'http://127.0.0.1:8001');
        $this->timeout = (int) Config::get('services.ai_service.timeout', 4);
    }

    /**
     * Get the introductory opening greeting for an AI character to kick off the session.
     *
     * @return array{message: string, audio_url: ?string}
     */
    public function getInitialGreeting(AiRole $role, ?string $topic = null): array
    {
        $hasCustomTopic = ! empty($topic) && $topic !== $role->name;

        if ($hasCustomTopic) {
            $greetings = [
                'dosen_penguji' => "Selamat datang di ruang sidang tugas akhir dengan topik '{$topic}'. Silakan atur posisi duduk tegak dan tatap kamera dengan tenang. Silakan perkenalkan diri Anda dan jelaskan rumusan masalah serta metode utama penelitian Anda.",
                'hrd' => "Halo! Senang bisa bertemu dengan Anda di sesi wawancara untuk posisi '{$topic}'. Tarik napas santai dan tetap percaya diri. Bisakah Anda menceritakan latar belakang Anda dan motivasi terbesar melamar di posisi ini?",
                'investor' => "Halo, salam kenal. Waktu pitching sangat berharga untuk startup di bidang '{$topic}'. Langsung ke intinya: jelaskan dalam 1 menit problem riil pasar dan model monetisasi solusi produk Anda.",
            ];
        } else {
            $greetings = [
                'dosen_penguji' => 'Selamat datang di ruang sidang tugas akhir. Silakan atur posisi duduk yang tegak dan tatap kamera dengan tenang. Silakan perkenalkan diri Anda dan jelaskan apa rumusan masalah serta metode utama penelitian Anda.',
                'hrd' => 'Halo! Senang bisa bertemu dengan Anda di sesi wawancara ini. Tarik napas santai dan tetap percaya diri. Untuk memulai, bisakah Anda menceritakan latar belakang Anda dan apa motivasi terbesar Anda melamar di posisi ini?',
                'investor' => 'Halo, salam kenal. Waktu pitching sangat berharga. Langsung ke intinya: jelaskan dalam 1 menit problem riil apa yang dihadapi pasar dan bagaimana solusi produk Anda menghasilkan pendapatan.',
            ];
        }

        $message = $greetings[$role->role_type] ?? "Halo, saya {$role->name}. Mari kita mulai simulasi hari ini. Silakan sampaikan pembuka Anda.";

        return [
            'message' => $message,
            'audio_url' => null,
        ];
    }

    /**
     * Process a real-time conversational turn from the user.
     *
     * @param  array<string, mixed>  $facialStatus
     * @return array{
     *     user_message: SessionMessage,
     *     ai_message: SessionMessage,
     *     response_text: string,
     *     audio_url: ?string,
     *     facial_critique: ?string
     * }
     */
    public function processTurn(
        PracticeSession $session,
        string $userMessage,
        array $facialStatus,
        int $timestampSeconds
    ): array {
        // 1. Record User's spoken turn with facial telemetry
        $userSessionMessage = $session->messages()->create([
            'sender' => 'user',
            'message' => $userMessage,
            'facial_status' => $facialStatus,
            'timestamp_seconds' => $timestampSeconds,
        ]);

        $role = $session->aiRole;
        $roleType = $role?->role_type ?? 'dosen_penguji';
        $roleName = $role?->name ?? 'AI Evaluator';
        $systemPrompt = $role?->system_prompt ?? 'Anda adalah evaluator profesional.';

        // 2. Format past conversation history (last 6 messages)
        $history = $session->messages()
            ->where('id', '!=', $userSessionMessage->id)
            ->latest('id')
            ->take(6)
            ->get()
            ->reverse()
            ->map(fn (SessionMessage $msg) => [
                'role' => $msg->sender === 'user' ? 'user' : 'assistant',
                'content' => $msg->message,
            ])
            ->values()
            ->toArray();

        // 3. Attempt calling Local FastAPI AI Service
        $aiReplyText = null;
        $audioUrl = null;
        $facialCritique = null;

        try {
            $response = Http::timeout($this->timeout)->post("{$this->aiServiceUrl}/chat", [
                'role_name' => $roleName,
                'role_type' => $roleType,
                'system_prompt' => $systemPrompt,
                'user_message' => $userMessage,
                'facial_status' => [
                    'emotion' => $facialStatus['status'] ?? null,
                    'eye_contact_ratio' => isset($facialStatus['eye_contact_score']) ? (float) $facialStatus['eye_contact_score'] : null,
                    'smile_detected' => isset($facialStatus['is_smiling']) ? (bool) $facialStatus['is_smiling'] : null,
                    'face_detected' => isset($facialStatus['face_detected']) ? (bool) $facialStatus['face_detected'] : true,
                ],
                'conversation_history' => $history,
                'generate_voice' => true,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $aiReplyText = $data['response_text'] ?? null;
                $facialCritique = $data['facial_critique'] ?? null;
                if (! empty($data['audio_file'])) {
                    $audioUrl = "{$this->aiServiceUrl}/audio/".$data['audio_file'];
                }
            }
        } catch (\Throwable $e) {
            Log::info('Local AI service unreachable, using persona fallback: '.$e->getMessage());
        }

        // 4. Intelligent contextual persona fallback if AI service was offline
        if (! $aiReplyText) {
            $fallback = $this->generatePersonaFallback($roleType, $userMessage, $facialStatus);
            $aiReplyText = $fallback['text'];
            $facialCritique = $fallback['critique'];
        }

        // 5. Record AI's response turn
        $aiSessionMessage = $session->messages()->create([
            'sender' => 'ai',
            'message' => $aiReplyText,
            'audio_url' => $audioUrl,
            'facial_status' => null,
            'timestamp_seconds' => $timestampSeconds + 2,
        ]);

        return [
            'user_message' => $userSessionMessage,
            'ai_message' => $aiSessionMessage,
            'response_text' => $aiReplyText,
            'audio_url' => $audioUrl,
            'facial_critique' => $facialCritique,
        ];
    }

    /**
     * Generate persona-tailored response and real-time facial reprimand.
     *
     * @param  array<string, mixed>  $facialStatus
     * @return array{text: string, critique: ?string}
     */
    protected function generatePersonaFallback(string $roleType, string $userMessage, array $facialStatus): array
    {
        $status = $facialStatus['status'] ?? 'neutral';
        $eyeContact = (int) ($facialStatus['eye_contact_score'] ?? 80);
        $isSmiling = (bool) ($facialStatus['is_smiling'] ?? false);

        $critiquePrefix = '';
        $critique = null;

        if ($status === 'tegang' || $eyeContact < 70) {
            if ($roleType === 'dosen_penguji') {
                $critiquePrefix = 'Catatan penguji: Bahu Anda tampak tegang dan tatapan mata Anda melenceng dari kamera. Dalam sidang ilmiah, ketenangan visual mencerminkan penguasaan materi. ';
            } elseif ($roleType === 'hrd') {
                $critiquePrefix = 'Sedikit masukan langsung: Anda terlihat cukup tegang dan jarang menatap mata pewawancara. Coba rilekskan ekspresi dan tatap layar dengan ramah. ';
            } else {
                $critiquePrefix = 'Satu hal: tatapan Anda terlihat ragu-ragu saat menyampaikan angka tersebut. Founder harus memancarkan keyakinan penuh. ';
            }
            $critique = 'Perlu perbaikan kontak mata dan relaksasi gestur.';
        } elseif ($isSmiling && $eyeContact >= 80) {
            if ($roleType === 'hrd') {
                $critiquePrefix = 'Kontak mata dan keramahan senyum Anda sangat baik, pertahankan. ';
            }
        }

        $dialogues = [
            'dosen_penguji' => [
                'Hmm, oke poin pengantar Saudara saya catat. Tapi tolong jelaskan secara konseptual, apa dasar teori utama yang mendukung validitas algoritma ini pada bab dua?',
                'Sebentar Saudara, metodologi yang Anda sebutkan tadi perlu pembuktian empiris. Bagaimana Anda memastikan dataset yang digunakan bebas dari bias sampling?',
                'Secara konseptual menarik. Namun coba buktikan kepada dewan penguji, apa novelty atau kebaruan nyata penelitian ini dibandingkan jurnal rujukan terdahulu?',
                'Pemaparan Anda cukup runut, tapi batasan masalahnya masih mengambang. Mengapa Anda tidak menguji skenario data ekstrem pada sistem ini?',
                'Baik. Sekarang coba tunjukkan apa metrik evaluasi utama yang Anda pakai untuk menyatakan sistem ini berhasil?',
            ],
            'hrd' => [
                'Wah, menarik sekali ceritanya. Bisakah kamu berikan satu contoh situasi kerja nyata di mana inisiatif mandiri kamu berhasil menyelamatkan target tim?',
                'Oke baik, saya bisa bayangkan situasinya. Nah, jika kamu berada dalam kondisi rekan satu tim menolak solusi yang kamu tawarkan, bagaimana pendekatan komunikasimu?',
                'Keren ya pengalamannya. Lalu bagaimana caramu mengelola prioritas saat dihadapkan pada beberapa deadline mendesak yang datang bersamaan?',
                'Saya suka antusiasmemu menceritakan hal itu. Bisakah kamu ceritakan kegagalan terbesar dalam pekerjaanmu dan apa pelajaran terpenting yang kamu petik?',
                'Menarik sekali. Menurutmu, lingkungan kerja seperti apa yang paling bisa memicu potensimu berkembang maksimal?',
            ],
            'investor' => [
                'Oke, problem pasarnya dapet. Tapi singkat aja ya, berapa perkiraan Customer Acquisition Cost kamu dan bagaimana kamu menjaga retensi pengguna tetap tinggi?',
                'Gini lho, solusinya masuk akal. Tapi apa moat atau benteng pertahananmu kalau kompetitor besar dengan modal melimpah bikin fitur serupa bulan depan?',
                'Idenya berani, saya suka. Tapi tolong jelaskan unit economics-nya: butuh berapa lama sampai startup kamu mencapai titik impas atau profit?',
                'Pasarnya memang besar, tapi eksekusi itu kuncinya. Milestone operasional konkret apa yang ingin kamu capai dalam enam bulan ke depan?',
                'Bagus energi pitching-nya! Tapi sebutkan satu alasan paling kuat kenapa kami harus berinvestasi di tim kamu sekarang?',
            ],
        ];

        $pool = $dialogues[$roleType] ?? $dialogues['dosen_penguji'];
        $chosen = $pool[array_rand($pool)];

        return [
            'text' => $critiquePrefix.$chosen,
            'critique' => $critique,
        ];
    }

    /**
     * Generate comprehensive final conclusion from the AI Role's perspective.
     *
     * @param  array<string, mixed>  $feedbackNotes
     */
    public function generateFinalConclusion(
        PracticeSession $session,
        float $faceScore,
        float $voiceScore,
        float $overallScore,
        array $feedbackNotes = []
    ): string {
        $role = $session->aiRole;
        $roleType = $role?->role_type ?? 'dosen_penguji';
        $roleName = $role?->name ?? 'Evaluator VOIC';
        $systemPrompt = $role?->system_prompt ?? 'Anda adalah evaluator profesional.';

        // Retrieve last turns of conversation for real context
        $history = $session->messages()
            ->latest('id')
            ->take(8)
            ->get()
            ->reverse()
            ->map(fn (SessionMessage $msg) => [
                'role' => $msg->sender === 'user' ? 'user' : 'assistant',
                'content' => $msg->message,
            ])
            ->values()
            ->toArray();

        // 1. Try local AI service via Ollama
        try {
            $response = Http::timeout($this->timeout)->post("{$this->aiServiceUrl}/conclude", [
                'role_name' => $roleName,
                'role_type' => $roleType,
                'system_prompt' => $systemPrompt,
                'face_score' => $faceScore,
                'voice_score' => $voiceScore,
                'overall_score' => $overallScore,
                'duration_seconds' => (int) $session->duration_seconds,
                'conversation_history' => $history,
                'feedback_notes' => $feedbackNotes,
            ]);

            if ($response->successful()) {
                $conclusion = $response->json('conclusion');
                if (! empty($conclusion)) {
                    return $conclusion;
                }
            }
        } catch (\Throwable $e) {
            Log::info('Local AI service /conclude unavailable: '.$e->getMessage());
        }

        // 2. Persona verdict based on REAL measurements
        $userTurnsCount = $session->messages()->where('sender', 'user')->count();
        $eyeContact = $feedbackNotes['eye_contact_score'] ?? round($faceScore);
        $wpm = $feedbackNotes['pace_wpm'] ?? null;
        $paceText = $wpm ? " dengan ritme {$wpm} kata/menit" : '';

        if ($overallScore >= 85) {
            if ($roleType === 'dosen_penguji') {
                return "Hasil Evaluasi {$roleName}: Mahasiswa menunjukkan argumentasi ilmiah yang sangat matang dalam {$userTurnsCount} respon{$paceText}. Kontak mata stabil (skor optik {$faceScore}/100) dan artikulasi suara mantap (skor vokal {$voiceScore}/100). Direkomendasikan siap menuju sidang sesungguhnya.";
            } elseif ($roleType === 'hrd') {
                return "Hasil Evaluasi {$roleName}: Kandidat memiliki kompetensi komunikasi yang luar biasa. Sangat percaya diri, ekspresi ramah profesional (skor wajah {$faceScore}/100), dan jawaban mengalir runtut{$paceText}. Nilai total: {$overallScore}/100 (Sangat Layak).";
            } else {
                return "Hasil Evaluasi {$roleName}: Pitching sangat persuasif dan padat! Energi vokal meyakinkan ({$voiceScore}/100) dengan kontak mata mantap ({$faceScore}/100). Problem-solution fit terartikulasi dengan tajam{$paceText}. Skor investasi: {$overallScore}/100.";
            }
        } elseif ($overallScore >= 70) {
            if ($roleType === 'dosen_penguji') {
                return "Hasil Evaluasi {$roleName}: Pemahaman substansi tugas akhir sudah cukup baik, namun perlu peningkatan ketenangan gestur saat dihadapkan pertanyaan mendadak. Skor optik wajah {$faceScore}/100, skor artikulasi suara {$voiceScore}/100{$paceText}.";
            } elseif ($roleType === 'hrd') {
                return "Hasil Evaluasi {$roleName}: Kemampuan komunikasi baik dan materi jawaban relevan. Tingkatkan kontak mata langsung ke arah kamera ({$eyeContact}%) agar kesan antusiasme dan komitmen terasa lebih kuat.";
            } else {
                return "Hasil Evaluasi {$roleName}: Ide bisnis memiliki potensi, tetapi tempo berbicara ({$wpm} WPM) dan ketenangan visual ({$faceScore}/100) perlu terus dilatih agar pesan keunggulan produk tidak terkesan terburu-buru.";
            }
        }

        return "Hasil Evaluasi {$roleName}: Performa latihan menunjukkan Anda perlu membiasakan diri berbicara di depan kamera (skor optik {$faceScore}/100, skor vokal {$voiceScore}/100). Latih pernapasan diafragma dan tatap lensa kamera secara berkesinambungan untuk mengatasi rasa gugup.";
    }
}
