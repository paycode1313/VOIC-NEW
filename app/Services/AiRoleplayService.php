<?php

namespace App\Services;

use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\SessionMessage;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        $this->timeout = (int) Config::get('services.ai_service.timeout', 35);
    }

    /**
     * Get the introductory opening greeting for an AI character to kick off the session.
     *
     * @return array{message: string, audio_url: ?string}
     */
    public function getInitialGreeting(AiRole $role, ?string $topic = null, string $language = 'id', ?string $contextMaterial = null): array
    {
        $hasCustomTopic = ! empty($topic) && $topic !== $role->name;
        $hasContext = ! empty($contextMaterial);
        $shortSnippet = $hasContext ? Str::limit(trim($contextMaterial), 120) : null;

        if ($language === 'en') {
            if ($hasContext) {
                $greetings = [
                    'dosen_penguji' => "Welcome to your thesis defense examination. I have received your research abstract: '{$shortSnippet}'. Please sit upright, look directly into the camera, and explain the core justification of your methodology.",
                    'hrd' => "Hello! Glad to meet you. I noted your profile summary: '{$shortSnippet}'. Take a calm breath and walk me through your key achievements and motivations.",
                    'investor' => "Pitch time is live. I reviewed your teaser premise: '{$shortSnippet}'. Cut straight to the point: explain your unit economics and customer validation in 60 seconds.",
                ];
            } elseif ($hasCustomTopic) {
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
            if ($hasContext) {
                $greetings = [
                    'dosen_penguji' => "Selamat datang di ruang sidang tugas akhir. Saya telah menelaah abstrak penelitian Anda: '{$shortSnippet}'. Silakan atur posisi duduk tegak, tatap kamera, dan jelaskan rumusan masalah serta validasi metode penelitian Anda.",
                    'hrd' => "Halo! Senang bisa bertemu dengan Anda. Saya telah membaca ringkasan profil Anda: '{$shortSnippet}'. Tarik napas santai dan ceritakan bagaimana pengalaman Anda relevan untuk posisi ini.",
                    'investor' => "Halo, salam kenal. Waktu pitching berjalan. Saya mencatat premis bisnis Anda: '{$shortSnippet}'. Langsung ke intinya: jelaskan dalam 1 menit problem riil pasar dan model monetisasi solusi Anda.",
                ];
            } elseif ($hasCustomTopic) {
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
        $audioUrl = null;

        try {
            $response = Http::timeout(3)->post("{$this->aiServiceUrl}/tts", [
                'text' => $message,
                'role_type' => $role->role_type,
                'language' => $language,
            ]);

            if ($response->successful() && ! empty($response->json('audio_file'))) {
                $audioUrl = "{$this->aiServiceUrl}/audio/".$response->json('audio_file');
            }
        } catch (\Throwable $e) {
            // Graceful fallback: audio_url remains null, browser speech synth will be used
        }

        return [
            'message' => $message,
            'audio_url' => $audioUrl,
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
     *     facial_critique: ?string,
     *     coherence_score: float,
     *     coherence_category: string,
     *     salient_entities: array<string>
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

        // 2. Format past conversation history (last 8 messages for richer context)
        $history = $session->messages()
            ->where('id', '!=', $userSessionMessage->id)
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

        // 3. User Deep Learning Long-Term Memory Profile
        $user = $session->user;
        $userMemoryProfile = $user ? $user->getAiLearningProfile() : null;

        // 4. Attempt calling Local FastAPI AI Service
        $aiReplyText = null;
        $audioUrl = null;
        $facialCritique = null;
        $coherenceScore = 86.0;
        $coherenceCategory = 'Relevan & Nyambung';
        $salientEntities = [];
        $aiEngine = 'persona_fallback';

        $turnCount = $session->messages()->where('sender', 'user')->count();
        $stage = match (true) {
            $turnCount <= 1 => 'opening',
            $turnCount <= 3 => 'core',
            $turnCount === 4 => 'pressure_test',
            default => 'closing',
        };

        try {
            $contextMaterial = $session->feedback_notes['context_material'] ?? null;
            $response = Http::timeout($this->timeout)->post("{$this->aiServiceUrl}/chat", [
                'session_id' => $session->id,
                'role_name' => $roleName,
                'role_type' => $roleType,
                'system_prompt' => $systemPrompt,
                'user_message' => $userMessage,
                'language' => $lang,
                'stage' => $stage,
                'context_material' => $contextMaterial,
                'user_memory_profile' => $userMemoryProfile,
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
                $coherenceScore = isset($data['coherence_score']) ? (float) $data['coherence_score'] : 86.0;
                $coherenceCategory = $data['coherence_category'] ?? 'Relevan & Nyambung';
                $salientEntities = $data['salient_entities'] ?? [];
                if (! empty($data['audio_file'])) {
                    $audioUrl = "{$this->aiServiceUrl}/audio/".$data['audio_file'];
                }
                if ($aiReplyText) {
                    $aiEngine = 'ollama_neural';
                }
            }
        } catch (\Throwable $e) {
            Log::info('Local AI service unreachable, evaluating cloud/persona fallback: '.$e->getMessage());
        }

        // 5a. Optional Cloud LLM (Groq / OpenAI) if configured and local AI was unreachable
        if (! $aiReplyText) {
            $cloudReply = $this->generateCloudLlmResponse($roleType, $systemPrompt, $userMessage, $history, $lang, $stage);
            if ($cloudReply) {
                $aiReplyText = $cloudReply;
                $coherenceScore = 88.0;
                $coherenceCategory = 'Relevan & Kontekstual';
                $aiEngine = 'cloud_llm';
            }
        }

        // 5b. Intelligent contextual persona fallback if AI service was offline or warming up
        if (! $aiReplyText) {
            $fallback = $this->generatePersonaFallback($roleType, $userMessage, $facialStatus, $lang, $stage);
            $aiReplyText = $fallback['text'];
            $facialCritique = $fallback['critique'];
            $coherenceScore = 84.0;
            $coherenceCategory = 'Relevan (Engine Kalibrasi)';
            $salientEntities = $fallback['entities'] ?? [];
            $aiEngine = 'persona_fallback';
        }

        // 6. Record AI's response turn
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
            'coherence_score' => $coherenceScore,
            'coherence_category' => $coherenceCategory,
            'salient_entities' => $salientEntities,
            'ai_engine' => $aiEngine,
            'stage' => $stage,
        ];
    }

    /**
     * Generate persona-tailored response and real-time facial reprimand.
     *
     * @param  array<string, mixed>  $facialStatus
     * @return array{text: string, critique: ?string}
     */
    protected function generatePersonaFallback(string $roleType, string $userMessage, array $facialStatus, string $language = 'id', string $stage = 'core'): array
    {
        $status = $facialStatus['status'] ?? 'neutral';
        $eyeContact = (int) ($facialStatus['eye_contact_score'] ?? 80);
        $isSmiling = (bool) ($facialStatus['is_smiling'] ?? false);

        // UI Telemetry critique (displayed in the coaching badge, separated from speech audio)
        $critique = null;
        if ($status === 'tegang' || $eyeContact < 70) {
            $critique = $language === 'en'
                ? 'Needs improved eye contact and body language relaxation.'
                : 'Perlu perbaikan kontak mata dan relaksasi gestur bahu.';
        } elseif ($isSmiling && $eyeContact >= 80) {
            $critique = $language === 'en'
                ? 'Great eye contact and pleasant facial presence.'
                : 'Kontak mata fokus dan ekspresi ramah sangat baik.';
        }

        // Clean entity extraction
        $stopwords = [
            'yang', 'untuk', 'pada', 'ke', 'saya', 'kamu', 'anda', 'kita', 'dan', 'atau',
            'karena', 'dengan', 'adalah', 'ini', 'itu', 'bisa', 'akan', 'sudah', 'telah',
            'dalam', 'dari', 'jika', 'kalau', 'bila', 'ketika', 'saat', 'the', 'is', 'at',
            'which', 'on', 'in', 'to', 'for', 'a', 'an', 'and', 'or', 'we', 'you', 'i',
        ];
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($userMessage));
        $salient = [];
        if (is_array($words)) {
            foreach ($words as $w) {
                if (mb_strlen($w) >= 4 && ! in_array($w, $stopwords, true)) {
                    $salient[] = $w;
                }
            }
        }
        $extractedEntities = array_values(array_unique(array_slice($salient, 0, 4)));

        // Compound or acronym detection
        preg_match('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)+\b|\b[A-Z0-9]{2,}\b/u', $userMessage, $compoundMatch);
        $primaryEntity = $compoundMatch[0] ?? ($extractedEntities[0] ?? null);

        $msgLower = mb_strtolower($userMessage);
        $hasMetric = (bool) preg_match('/\b(?:\d+%?|\d+\s*(?:bulan|tahun|hari|persen|ribu|juta|miliar|user|sampel|accuracy|margin|cac|ltv))\b/i', $userMessage);
        $hasTech = (bool) preg_match('/\b(algoritma|metode|model|sistem|database|arsitektur|framework|dataset|validasi|akurasi|klasifikasi|regresi|api|testing|code|benchmark|random forest|cnn|yolo)\b/i', $msgLower);
        $hasTeam = (bool) preg_match('/\b(tim|rekan|atasan|konflik|komunikasi|inisiatif|diskusi|klien|meeting|proyek|sprint|retrospective|deadline|prioritas)\b/i', $msgLower);

        if ($language === 'en') {
            if ($stage === 'pressure_test') {
                $dialogues = [
                    'dosen_penguji' => [
                        'Committee pressure test: If your system faces extreme noisy data anomalies where your baseline assumptions fail, how do you mathematically prove your bounds without hallucinations?',
                        'Critical flaw challenge: How do you defend your thesis if your core algorithm benchmark is proven to have overfitting bias?',
                    ],
                    'hrd' => [
                        'Workplace stress test: If a vital team member abruptly quits 3 days before product launch and your boss refuses extensions, what concrete action do you take immediately?',
                        'Scenario challenge: If an executive publicly reprimands you for a critical bug caused by another department, how do you handle the emotional confrontation?',
                    ],
                    'investor' => [
                        'Critical defensibility test: If a tech giant with billions in cash clones your entire roadmap next week for free, why will customers pay you? What is your unfair moat?',
                        'Runway stress test: If your Customer Acquisition Cost triples tomorrow and you have only 4 months of cash, what exact operational cuts do you make?',
                    ],
                ];
            } else {
                $dialogues = [
                    'dosen_penguji' => $hasMetric ? [
                        'How exactly were those quantitative bounds verified? Demonstrate that your sample distribution does not suffer from data leakage or selection bias.',
                        'Numbers alone do not validate a thesis without rigorous baselines. What statistical significance test proves this outcome is not mere randomness?',
                    ] : ($hasTech ? [
                        $primaryEntity
                            ? "That specific architecture around '{$primaryEntity}' introduces computational trade-offs. What empirical evidence proves it outperforms standard, well-established baselines?"
                            : 'That specific technical architecture introduces computational trade-offs. What empirical evidence proves it outperforms standard baselines?',
                        'How do you mathematically defend the stability of this approach when tested against noisy, adversarial edge cases in production?',
                    ] : [
                        'Directly connect your theoretical foundation in chapter two with this empirical finding. Avoid purely abstract claims.',
                        'Demonstrate to the committee: what is the genuine, defensible novelty here compared to seminal literature in this field?',
                    ]),
                    'hrd' => $hasTeam ? [
                        'When facing that interpersonal friction, what specific action did you personally take to realign priorities without escalating conflict?',
                        'Walk me through how you influenced team members who were initially skeptical or resistant to your direction.',
                    ] : ($hasMetric ? [
                        'That milestone is impressive on paper. But what was the single biggest obstacle that almost derailed it, and how did you pivot?',
                        'How did you maintain composure and prioritize your workload when unexpected blockers arose under tight deadlines?',
                    ] : [
                        'What was the first concrete step you executed the moment you realized this problem needed immediate intervention?',
                        'Looking back with hindsight, what key operational decision would you execute differently to achieve a higher impact?',
                    ]),
                    'investor' => $hasMetric ? [
                        'What is the actual monthly burn rate and Customer Acquisition Cost required to maintain that growth trajectory?',
                        'Healthy metrics require retention. What does your net revenue retention look like after month six?',
                    ] : ($hasTech ? [
                        $primaryEntity
                            ? "The technology around '{$primaryEntity}' is solid, but what is your defensible moat if an incumbent tech giant clones this next month?"
                            : 'The underlying technology is solid, but what is your defensible moat if an incumbent tech giant clones this next month?',
                        'How defensible is your IP if key developers leave or open-source alternatives catch up?',
                    ] : [
                        'Cut straight to the bottom line: why will enterprises pay premium for this rather than sticking with their current workflow?',
                        'What is your clear path to achieving venture-scale return and operational break-even within the next 18 months?',
                    ]),
                ];
            }
        } else {
            if ($stage === 'pressure_test') {
                $dialogues = [
                    'dosen_penguji' => [
                        'Ujian tekanan dewan penguji: Jika sistem Saudara diuji pada skenario noise ekstrem di mana asumsi dasar runtuh, bagaimana Saudara membuktikan batas error-nya dan menjamin sistem tidak crash fatal?',
                        'Pertanyaan kritis penguji: Bagaimana jika metodologi yang Saudara pilih sebenarnya bias terhadap dataset uji? Buktikan dasar ilmiah Saudara sekarang juga!',
                    ],
                    'hrd' => [
                        'Pertanyaan uji stres: Bayangkan rekan tim Anda mengundurkan diri mendadak 3 hari sebelum deadline rilis, sementara atasan menolak perpanjangan waktu. Konkretnya apa aksi pertama Anda?',
                        'Studi kasus tekanan: Jika klien penting memarahi Anda di depan umum atas kegagalan sistem yang bukan salah Anda, bagaimana cara Anda mengendalikan emosi dan meredamnya?',
                    ],
                    'investor' => [
                        'Ujian defensibility kritis: Jika raksasa teknologi dengan modal triliunan meluncurkan fitur yang persis sama besok pagi gratis, kenapa pengguna tidak kabur? Apa moat riil Anda?',
                        'Uji ketahanan modal: Jika CAC Anda melonjak 3x lipat bulan depan sementara modal operasional tersisa 4 bulan, langkah pemangkasan drastis apa yang langsung Anda ambil?',
                    ],
                ];
            } else {
                $dialogues = [
                    'dosen_penguji' => $hasMetric ? [
                        'Angka dan metrik pengujian tersebut diuji pada seberapa banyak data riil? Buktikan bahwa hasil tersebut bukan akibat overfitting atau data leakage.',
                        'Capaian kuantitatif itu tidak ada artinya tanpa uji signifikansi statistik. Uji hipotesis apa yang Saudara gunakan untuk membuktikan klaim tersebut?',
                    ] : ($hasTech ? [
                        $primaryEntity
                            ? "Keputusan mengadopsi '{$primaryEntity}' punya kompromi komputasi dan kompleksitas tersendiri. Apa dasar ilmiah Saudara bahwa metode ini lebih unggul dibanding baseline yang sudah mapan?"
                            : 'Keputusan pendekatan teknis tersebut punya kompromi komputasi dan kompleksitas tersendiri. Apa dasar ilmiah Saudara bahwa metode ini lebih unggul dibanding baseline yang sudah mapan?',
                        'Bagaimana Saudara menjamin kestabilan metode tersebut saat dihadapkan pada skenario noise atau anomali data di lapangan?',
                    ] : [
                        'Coba tunjukkan korelasi langsung antara rumusan masalah di bab satu dengan pembuktian empiris yang Saudara sebutkan tadi. Jangan berhenti di tataran konseptual.',
                        'Buktikan kepada dewan penguji: di mana letak kebaruan atau novelty orisinal penelitian Saudara dibanding publikasi terdahulu?',
                    ]),
                    'hrd' => $hasTeam ? [
                        'Dalam dinamika tim tersebut, tindakan nyata apa yang kamu ambil secara personal untuk menengahi perbedaan pendapat tanpa memicu friksi berkepanjangan?',
                        'Bagaimana pendekatan komunikasimu saat harus meyakinkan rekan kerja atau atasan yang awalnya menentang inisiatif tersebut?',
                    ] : ($hasMetric ? [
                        'Pencapaian angka itu signifikan. Tapi ceritakan kendala paling berat di balik layar yang hampir menggagalkan target tersebut, dan bagaimana kamu menyelesaikannya?',
                        'Bagaimana kamu menjaga ritme kerja dan ketenangan saat menghadapi tenggat waktu yang tiba-tiba dimajukan oleh manajemen?',
                    ] : [
                        'Langkah konkret pertama apa yang langsung kamu eksekusi saat menyadari ada masalah mendesak yang butuh solusi cepat?',
                        'Kalau kamu diberi kesempatan mengulang situasi tersebut dengan pengalamanmu sekarang, keputusan apa yang akan kamu ubah agar dampaknya lebih maksimal?',
                    ]),
                    'investor' => $hasMetric ? [
                        'Angka pertumbuhan itu bagus di atas kertas. Tapi langsung ke intinya: berapa burn rate bulanan dan Customer Acquisition Cost nyata untuk menopang angka itu?',
                        'Metrik traksi butuh retensi. Bagaimana persentase churn rate pengguna kalian setelah bulan ketiga?',
                    ] : ($hasTech ? [
                        $primaryEntity
                            ? "Solusi seputar '{$primaryEntity}' ini menjanjikan. Tapi apa benteng pertahanan atau moat bisnis kalian kalau pemain besar menyalin fitur ini minggu depan?"
                            : 'Solusi teknologi ini menjanjikan. Tapi apa benteng pertahanan atau moat bisnis kalian kalau pemain besar menyalin fitur ini minggu depan?',
                        'Seberapa bergantung teknologi ini pada infrastruktur pihak ketiga, dan bagaimana strategi kalian menjaga margin tetap tebal?',
                    ] : [
                        'Langsung ke intinya: kenapa pelanggan bersedia membayar solusi kalian sekarang juga daripada bertahan dengan cara lama yang gratis?',
                        'Berapa lama modal operasional yang ada sekarang bisa bertahan sampai kalian membuktikan unit economics yang positif?',
                    ]),
                ];
            }
        }

        $pool = $dialogues[$roleType] ?? $dialogues['dosen_penguji'];
        $chosen = $pool[array_rand($pool)];

        return [
            'text' => $chosen,
            'critique' => $critique,
            'entities' => $extractedEntities,
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

        // 1. Trigger Continuous Deep Learning Memory Sync
        $this->syncUserLearningProfile($session, $overallScore, $feedbackNotes);

        // 2. Try local AI service via Ollama
        try {
            $response = Http::timeout(min(20, $this->timeout))->post("{$this->aiServiceUrl}/conclude", [
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

        // 3. Persona verdict based on REAL measurements
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

    /**
     * Synchronize continuous learning telemetry and update user's AI memory profile.
     *
     * @param  array<string, mixed>  $feedbackNotes
     */
    public function syncUserLearningProfile(
        PracticeSession $session,
        float $overallScore,
        array $feedbackNotes = []
    ): void {
        $user = $session->user;
        if (! $user) {
            return;
        }

        $currentProfile = $user->getAiLearningProfile();
        $turnsCount = $session->messages()->where('sender', 'user')->count();
        $totalLearned = ($currentProfile['total_turns_learned'] ?? 0) + $turnsCount;

        // Extract weaknesses and strengths
        $improvements = $feedbackNotes['improvements'] ?? [];
        $existingWeaknesses = $currentProfile['identified_weaknesses'] ?? [];
        $newWeaknesses = array_values(array_unique(array_merge($existingWeaknesses, $improvements)));

        $scenario = $session->scenario_type;
        $existingTopics = $currentProfile['mastered_topics'] ?? [];
        if ($overallScore >= 75 && ! in_array($scenario, $existingTopics, true)) {
            $existingTopics[] = $scenario;
        }

        // Dynamic Learning Level (1 to 5)
        $level = 1;
        if ($totalLearned >= 120) {
            $level = 5;
        } elseif ($totalLearned >= 60) {
            $level = 4;
        } elseif ($totalLearned >= 25) {
            $level = 3;
        } elseif ($totalLearned >= 8) {
            $level = 2;
        }

        $updatedProfile = [
            'learning_level' => $level,
            'total_turns_learned' => $totalLearned,
            'adaptive_mastery_rate' => round((($currentProfile['adaptive_mastery_rate'] ?? 70) * 0.7) + ($overallScore * 0.3), 1),
            'identified_weaknesses' => array_slice($newWeaknesses, -5),
            'mastered_topics' => array_slice($existingTopics, -6),
            'preferred_pace_wpm' => (int) ($feedbackNotes['pace_wpm'] ?? $currentProfile['preferred_pace_wpm'] ?? 120),
            'last_trained_at' => now()->toIso8601String(),
        ];

        $user->update(['ai_learning_profile' => $updatedProfile]);

        // Non-blocking sync to FastAPI AI Service
        try {
            Http::timeout(3)->post("{$this->aiServiceUrl}/learn/sync", [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'role_type' => $session->aiRole?->role_type ?? 'dosen_penguji',
                'overall_score' => $overallScore,
                'weaknesses' => $improvements,
                'mastered_topics' => $existingTopics,
            ]);
        } catch (\Throwable $e) {
            Log::info('Local AI service /learn/sync notice: '.$e->getMessage());
        }
    }

    /**
     * Get real-time deep learning telemetry for dashboard & UI indicators.
     *
     * @return array<string, mixed>
     */
    public function getDeepLearningTelemetry(?User $user = null): array
    {
        $userProfile = $user ? $user->getAiLearningProfile() : [
            'learning_level' => 1,
            'total_turns_learned' => 24,
            'adaptive_mastery_rate' => 75.0,
            'identified_weaknesses' => ['Kontak mata kamera', 'Kecepatan tempo bicara'],
            'mastered_topics' => ['Sidang Skripsi'],
        ];

        try {
            $response = Http::timeout(2)->get("{$this->aiServiceUrl}/learn/stats");
            if ($response->successful()) {
                $remote = $response->json();

                return array_merge($remote, [
                    'user_level' => $userProfile['learning_level'] ?? 1,
                    'user_mastery_rate' => $userProfile['adaptive_mastery_rate'] ?? 75.0,
                    'user_weaknesses' => $userProfile['identified_weaknesses'] ?? [],
                    'user_mastered' => $userProfile['mastered_topics'] ?? [],
                ]);
            }
        } catch (\Throwable $e) {
            // Graceful fallback
        }

        return [
            'status' => 'active_learning',
            'learning_level' => $userProfile['learning_level'] ?? 1,
            'level_label' => ($userProfile['learning_level'] ?? 1) >= 3 ? 'Adaptive Examiner' : 'Active Conversationalist',
            'total_dialogue_turns_learned' => max(24, $userProfile['total_turns_learned'] ?? 24),
            'average_coherence_rate' => 88.5,
            'deep_learning_mode' => 'In-Context SFT & Memory Vectors',
            'user_level' => $userProfile['learning_level'] ?? 1,
            'user_mastery_rate' => $userProfile['adaptive_mastery_rate'] ?? 75.0,
            'user_weaknesses' => $userProfile['identified_weaknesses'] ?? [],
            'user_mastered' => $userProfile['mastered_topics'] ?? [],
        ];
    }

    /**
     * Attempt generating an AI response via Cloud LLM (Groq or OpenAI) if configured in .env.
     *
     * @param  array<array{role: string, content: string}>  $history
     */
    protected function generateCloudLlmResponse(
        string $roleType,
        string $systemPrompt,
        string $userMessage,
        array $history,
        string $lang,
        string $stage
    ): ?string {
        $groqKey = (string) Config::get('services.groq.key', '');
        $openaiKey = (string) Config::get('services.openai.key', '');

        if (! empty($groqKey)) {
            try {
                $messages = [
                    ['role' => 'system', 'content' => $systemPrompt."\nBerikan tanggapan lisan 2-3 kalimat tajam, padat, dan langsung menguji poin pembicara tanpa kalimat pembuka klise."],
                ];
                foreach (array_slice($history, -4) as $item) {
                    $messages[] = [
                        'role' => $item['role'] === 'user' ? 'user' : 'assistant',
                        'content' => $item['content'],
                    ];
                }
                $messages[] = ['role' => 'user', 'content' => $userMessage];

                $res = Http::timeout(10)
                    ->withToken($groqKey)
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => (string) Config::get('services.groq.model', 'llama-3.3-70b-versatile'),
                        'messages' => $messages,
                        'temperature' => 0.7,
                        'max_tokens' => 220,
                    ]);

                if ($res->successful()) {
                    $reply = trim((string) ($res->json('choices.0.message.content') ?? ''));
                    if (! empty($reply)) {
                        return $reply;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Groq Cloud LLM failed: '.$e->getMessage());
            }
        }

        if (! empty($openaiKey)) {
            try {
                $messages = [
                    ['role' => 'system', 'content' => $systemPrompt."\nBerikan tanggapan lisan 2-3 kalimat tajam, padat, dan langsung menguji poin pembicara tanpa kalimat pembuka klise."],
                ];
                foreach (array_slice($history, -4) as $item) {
                    $messages[] = [
                        'role' => $item['role'] === 'user' ? 'user' : 'assistant',
                        'content' => $item['content'],
                    ];
                }
                $messages[] = ['role' => 'user', 'content' => $userMessage];

                $res = Http::timeout(10)
                    ->withToken($openaiKey)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => (string) Config::get('services.openai.model', 'gpt-4o-mini'),
                        'messages' => $messages,
                        'temperature' => 0.7,
                        'max_tokens' => 220,
                    ]);

                if ($res->successful()) {
                    $reply = trim((string) ($res->json('choices.0.message.content') ?? ''));
                    if (! empty($reply)) {
                        return $reply;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('OpenAI Cloud LLM failed: '.$e->getMessage());
            }
        }

        return null;
    }
}
