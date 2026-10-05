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
    public function getInitialGreeting(AiRole $role, ?string $topic = null, string $language = 'id'): array
    {
        $hasCustomTopic = ! empty($topic) && $topic !== $role->name;

        if ($language === 'en') {
            if ($hasCustomTopic) {
                $greetings = [
                    'dosen_penguji' => "Welcome to your thesis defense examination focusing on '{$topic}'. Please sit upright, maintain direct eye contact with the camera, and present your core research problem along with your primary methodology.",
                    'hrd' => "Hello! Glad to connect with you for this interview regarding the '{$topic}' position. Take a deep breath and stay confident. Could you walk me through your background and key motivations for this role?",
                    'investor' => "Hello, great to meet you. Pitch time is valuable in '{$topic}'. Cut straight to the point: explain your market problem, your 10x solution, and your monetization model in 60 seconds.",
                ];
            } else {
                $greetings = [
                    'dosen_penguji' => 'Welcome to your thesis defense examination. Please sit upright, maintain direct eye contact with the camera, and introduce yourself along with your core research problem and methodology.',
                    'hrd' => 'Hello! Glad to meet you in this interview session today. Take a calm breath and stay confident. To begin, could you share your background and your strongest motivation for joining us?',
                    'investor' => 'Hello, nice to meet you. Pitching time is extremely precious. Let us get right to the essence: explain the real problem in your market and how your product produces scalable revenue.',
                ];
            }
            $defaultMsg = "Hello, I am {$role->name}. Let us begin today's session. Please present your opening remarks.";
        } else {
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
            $defaultMsg = "Halo, saya {$role->name}. Mari kita mulai simulasi hari ini. Silakan sampaikan pembuka Anda.";
        }

        $message = $greetings[$role->role_type] ?? $defaultMsg;

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
        int $timestampSeconds,
        ?string $language = null
    ): array {
        $lang = $language ?: ($session->feedback_notes['language'] ?? 'id');

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
                'language' => $lang,
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
            $fallback = $this->generatePersonaFallback($roleType, $userMessage, $facialStatus, $lang);
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
    protected function generatePersonaFallback(string $roleType, string $userMessage, array $facialStatus, string $language = 'id'): array
    {
        $status = $facialStatus['status'] ?? 'neutral';
        $eyeContact = (int) ($facialStatus['eye_contact_score'] ?? 80);
        $isSmiling = (bool) ($facialStatus['is_smiling'] ?? false);

        $critiquePrefix = '';
        $critique = null;

        if ($language === 'en') {
            if ($status === 'tegang' || $eyeContact < 70) {
                if ($roleType === 'dosen_penguji') {
                    $critiquePrefix = 'Examiner note: Your shoulders appear tense and your eye contact deflected from the lens. Composure reflects academic confidence. ';
                } elseif ($roleType === 'hrd') {
                    $critiquePrefix = 'Quick observation: You seem slightly nervous with inconsistent eye contact. Relax your posture and look into the camera with warmth. ';
                } else {
                    $critiquePrefix = 'One thing: your gaze seemed hesitant when delivering those numbers. A founder must project total conviction. ';
                }
                $critique = 'Needs improved eye contact and body language relaxation.';
            } elseif ($isSmiling && $eyeContact >= 80) {
                if ($roleType === 'hrd') {
                    $critiquePrefix = 'Your eye contact and genuine smile are remarkable, keep that up. ';
                }
            }

            $dialogues = [
                'dosen_penguji' => [
                    'Right, I note your introductory point. But could you explain the theoretical foundation in chapter two that justifies your chosen methodology?',
                    'Hold on, your methodology needs empirical backing. How did you verify that your dataset is completely free from sampling bias?',
                    'Conceptually interesting. However, demonstrate to the examination committee: what is the genuine novelty of this research compared to past state-of-the-art papers?',
                    'Your delivery is structured, but your problem boundaries are too broad. Why did you not evaluate edge-case boundary scenarios on this model?',
                    'Alright. Now tell us: what primary evaluation metrics did you benchmark to prove this solution is truly superior?',
                ],
                'hrd' => [
                    'That is really fascinating. Could you provide a specific work example where your individual initiative directly saved a critical project milestone?',
                    'Great, I can picture that scenario. When a senior team member strongly pushes back on your recommendation, what communication strategy do you use?',
                    'Impressive background. How do you maintain composure and prioritize tasks when several urgent deadlines land at once?',
                    'I appreciate your enthusiasm. Could you share your greatest professional setback and the pivotal takeaway you learned from it?',
                    'Wonderful. What kind of engineering culture and team environment allows your potential to thrive best?',
                ],
                'investor' => [
                    'Got the problem statement. To keep it crisp: what is your estimated Customer Acquisition Cost and how do you retain your users?',
                    'The solution makes sense. But what is your defensible moat if a well-funded incumbent copies your feature set next month?',
                    'Bold vision. Can you break down your unit economics and how long until your startup reaches operational break-even?',
                    'Huge addressable market, but execution is king. What concrete operational milestones are you hitting in the next six months?',
                    'Great pitching energy! Give me the single most compelling reason why we should write you a check right now?',
                ],
            ];
        } else {
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
        }

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
        array $feedbackNotes = [],
        ?string $language = null
    ): string {
        $lang = $language ?: ($feedbackNotes['language'] ?? 'id');
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
                'language' => $lang,
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

        if ($lang === 'en') {
            $paceText = $wpm ? " with a delivery rhythm of {$wpm} words/minute" : '';
            if ($overallScore >= 85) {
                if ($roleType === 'dosen_penguji') {
                    return "Evaluation Verdict from {$roleName}: The student demonstrated robust academic argumentation across {$userTurnsCount} responses{$paceText}. Stable eye contact ({$faceScore}/100) and assertive voice clarity ({$voiceScore}/100). Highly recommended for the final defense.";
                } elseif ($roleType === 'hrd') {
                    return "Evaluation Verdict from {$roleName}: Candidate showed exceptional communicative competence, calm confidence, and a professional persona (facial score {$faceScore}/100, vocal score {$voiceScore}/100){$paceText}. Overall score: {$overallScore}/100 (Strong Hire).";
                } else {
                    return "Evaluation Verdict from {$roleName}: Persuasive and crisp pitching! Resonant vocal energy ({$voiceScore}/100) backed by unwavering eye contact ({$faceScore}/100). Problem-solution fit articulated sharply{$paceText}. Investment score: {$overallScore}/100.";
                }
            } elseif ($overallScore >= 70) {
                if ($roleType === 'dosen_penguji') {
                    return "Evaluation Verdict from {$roleName}: Substance comprehension is solid, though greater visual poise is required under unexpected questions. Facial optics: {$faceScore}/100, vocal projection: {$voiceScore}/100{$paceText}.";
                } elseif ($roleType === 'hrd') {
                    return "Evaluation Verdict from {$roleName}: Good communication and relevant answers. Maintain direct eye contact with the camera lens ({$eyeContact}%) to project maximum engagement and leadership presence.";
                } else {
                    return "Evaluation Verdict from {$roleName}: Promising commercial premise, but speech pacing ({$wpm} WPM) and composure ({$faceScore}/100) require calibration so the product edge is conveyed convincingly.";
                }
            }

            return "Evaluation Verdict from {$roleName}: Practice indicates a need to build natural camera presence (optics {$faceScore}/100, voice {$voiceScore}/100). Practice diaphragmatic breathing and continuous eye contact to conquer nervousness.";
        }

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
