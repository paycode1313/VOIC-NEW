<?php

namespace App\Console\Commands;

use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\User;
use App\Services\Exam\ExamBlueprint;
use App\Services\Exam\ExamFlowService;
use App\Services\Exam\ExaminerService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

#[Signature('voic:teacher-generate
    {--sessions=10 : Number of simulated sessions}
    {--teacher= : Teacher model in Ollama (default VOIC_TEACHER_MODEL, e.g. kimi-k3:cloud)}
    {--candidate= : Model that plays the candidate (default VOIC_TEACHER_CANDIDATE_MODEL)}
    {--mode=* : Only these modes (wawancara, sidang, pitching)}
    {--language=id : id or en}
    {--coach-share=0.3 : Share of sessions that are coaching chats instead of exams}
    {--max-turns=16 : Candidate answers per session at most}
    {--seed= : Random seed, to repeat the same mix of topics and candidates}')]
#[Description('Distillation data: run simulated sessions where a strong teacher model (Kimi K3) is the examiner')]
class GenerateTeacherSessions extends Command
{
    /**
     * Topics per mode, so the teacher sees varied, realistic sessions.
     *
     * @var array<string, list<string>>
     */
    protected const TOPICS = [
        'wawancara' => [
            'Frontend Developer di startup fintech', 'Staf administrasi rumah sakit', 'Management Trainee bank',
            'Data Analyst di e-commerce', 'Guru SD swasta', 'Marketing Executive perusahaan FMCG',
            'Customer Service maskapai penerbangan', 'Akuntan junior di kantor akuntan publik',
        ],
        'sidang' => [
            'Sistem rekomendasi produk UMKM berbasis collaborative filtering', 'Deteksi cacat kain dengan YOLOv8',
            'Pengaruh media sosial terhadap minat beli Gen Z', 'Analisis sentimen ulasan aplikasi transportasi online',
            'Efektivitas pembelajaran daring di SMA', 'Sistem informasi inventaris berbasis web untuk puskesmas',
            'Pengaruh gaya kepemimpinan terhadap kinerja karyawan',
        ],
        'pitching' => [
            'Aplikasi kasir untuk warung kelontong', 'Marketplace jasa tukang bangunan', 'Platform les privat online',
            'Pupuk organik dari limbah kopi', 'Aplikasi antrean klinik', 'Rental alat camping online',
            'Camilan sehat dari bahan lokal',
        ],
    ];

    /**
     * Kinds of candidates, from strong to struggling: the examiner must learn to handle all of them.
     *
     * @var list<array{profile: string, length: string}>
     */
    protected const CANDIDATES = [
        ['profile' => 'lancar dan percaya diri, selalu memberi contoh konkret dengan angka', 'length' => '3 sampai 5 kalimat'],
        ['profile' => 'cukup baik tetapi kadang kurang detail dan lupa memberi contoh', 'length' => '2 sampai 3 kalimat'],
        ['profile' => 'gugup, jawabannya pendek dan ragu-ragu, sesekali memakai kata "eee"', 'length' => '1 sampai 2 kalimat'],
        ['profile' => 'kadang bilang tidak tahu, atau meminta pertanyaan dijelaskan ulang', 'length' => '1 sampai 2 kalimat'],
        ['profile' => 'bertele-tele dan kurang terstruktur, poin utamanya baru muncul di akhir', 'length' => '4 sampai 6 kalimat'],
        ['profile' => 'sangat singkat, sering hanya menjawab satu kalimat', 'length' => '1 kalimat'],
    ];

    /**
     * Execute the console command.
     */
    public function handle(ExamBlueprint $blueprint): int
    {
        $teacher = (string) ($this->option('teacher') ?: Config::get('exam.teacher_model'));
        $candidate = (string) ($this->option('candidate') ?: Config::get('exam.teacher_candidate_model'));
        $language = $this->option('language') === 'en' ? 'en' : 'id';
        $modes = array_values(array_intersect($this->option('mode') ?: array_keys(self::TOPICS), array_keys(self::TOPICS)));
        $sessions = max(1, (int) $this->option('sessions'));
        $maxTurns = max(2, (int) $this->option('max-turns'));
        $coachShare = max(0.0, min(1.0, (float) $this->option('coach-share')));
        if ($this->option('seed') !== null) {
            mt_srand((int) $this->option('seed'));
        }

        // 1. Ollama must have the teacher (signed in) and, ideally, a local model for the candidate.
        $available = $this->ollamaModels();
        if ($available === null) {
            $this->components->error('Ollama tidak berjalan di '.$this->ollamaUrl().'. Buka aplikasi Ollama dulu.');

            return self::FAILURE;
        }
        if (! in_array($teacher, $available, true)) {
            $this->components->error("Model guru {$teacher} belum ada di Ollama.");
            $this->line('  Jalankan: <fg=yellow>ollama signin</> lalu <fg=yellow>ollama pull '.$teacher.'</>');
            if (ExaminerService::isCloudModel($teacher)) {
                $this->line('  Catatan: model cloud seperti Kimi K3 membutuhkan akun Ollama dengan langganan yang mendukungnya.');
            }

            return self::FAILURE;
        }
        if (! in_array($candidate, $available, true)) {
            $this->components->warn("Model peserta {$candidate} tidak ada, peserta juga diperankan oleh {$teacher} (memakai kuota guru).");
            $candidate = $teacher;
        }

        // 2. The examiner turns go through the AI service, exactly like a live session.
        $aiService = rtrim((string) Config::get('services.ai_service.url', 'http://127.0.0.1:8001'), '/');
        try {
            Http::connectTimeout(2)->timeout(5)->get($aiService.'/voice/status')->throw();
        } catch (\Throwable) {
            $this->components->error("AI service tidak bisa dihubungi di {$aiService}. Jalankan: python ai_service/ai_service.py");

            return self::FAILURE;
        }

        /** @var ExaminerService $examiner */
        $examiner = app(ExaminerService::class);
        $examiner->useModel($teacher);
        app()->instance(ExaminerService::class, $examiner);
        /** @var ExamFlowService $flow */
        $flow = app(ExamFlowService::class);

        $owner = $this->teacherUser();
        $this->components->info("Guru: {$teacher} · Peserta: {$candidate} · {$sessions} sesi");
        $this->line('  <fg=gray>Model guru yang berpikir dulu (Kimi K3) bisa butuh 10-40 detik per giliran.</>');

        $teacherTurns = 0;
        $fallbackTurns = 0;
        for ($i = 0; $i < $sessions; $i++) {
            $mode = $modes[$i % count($modes)];
            $topic = self::TOPICS[$mode][mt_rand(0, count(self::TOPICS[$mode]) - 1)];
            $person = self::CANDIDATES[mt_rand(0, count(self::CANDIDATES) - 1)];
            $coach = mt_rand() / mt_getrandmax() < $coachShare;

            $session = $this->startSession($flow, $blueprint, $owner, $mode, $language, $topic, $person, $coach);
            $this->line(sprintf('  [%d/%d] %s · %s · %s', $i + 1, $sessions, $coach ? 'coaching' : 'ujian', $mode, $topic));

            [$byTeacher, $byFallback] = $this->runSession($flow, $session, $candidate, $person, $language, $maxTurns);
            $teacherTurns += $byTeacher;
            $fallbackTurns += $byFallback;
            $this->line("        <fg=gray>{$byTeacher} giliran dari guru".($byFallback ? ", {$byFallback} dari cadangan (tidak dipakai untuk training)" : '').'</>');
        }

        $this->newLine();
        $this->components->info("Selesai: {$teacherTurns} balasan penguji dari {$teacher}.");
        if ($teacherTurns === 0) {
            $this->components->warn('Tidak ada balasan dari model guru. Cek `ollama signin`, kuota Ollama, dan jendela ai_service.');

            return self::FAILURE;
        }
        $this->line('  Langkah berikutnya:');
        $this->line('  <fg=yellow>php artisan voic:export-training-data</>   (membuat ai_service/datasets/sft/*.jsonl)');
        $this->line('  Lalu unggah kedua file .jsonl ke notebook Colab: ai_service/colab/latih_voic_coach.ipynb');

        return self::SUCCESS;
    }

    /**
     * Model names Ollama knows about, or null when Ollama is not running.
     *
     * @return list<string>|null
     */
    protected function ollamaModels(): ?array
    {
        try {
            $response = Http::connectTimeout(2)->timeout(5)->get($this->ollamaUrl().'/api/tags');

            return $response->successful()
                ? array_values(array_map(fn ($model) => (string) $model['name'], (array) $response->json('models', [])))
                : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function ollamaUrl(): string
    {
        return rtrim((string) Config::get('services.ollama.url', 'http://127.0.0.1:11434'), '/');
    }

    /**
     * The account that owns simulated sessions, so they never show up as real visitors.
     */
    protected function teacherUser(): User
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

    /**
     * @param  array{profile: string, length: string}  $person
     */
    protected function startSession(ExamFlowService $flow, ExamBlueprint $blueprint, User $owner, string $mode, string $language, string $topic, array $person, bool $coach): PracticeSession
    {
        $definition = $blueprint->mode($mode);

        /** @var PracticeSession $session */
        $session = PracticeSession::create([
            'user_id' => $owner->id,
            'ai_role_id' => AiRole::where('role_type', $definition['role_type'])->value('id'),
            'scenario_type' => 'Data latih · '.$blueprint->text($coach ? $definition['coach']['title'] : $definition['title'], $language).' · '.Str::limit($topic, 60),
            'exam_mode' => $mode,
            'duration_seconds' => 0,
            'feedback_notes' => ['language' => $language, 'topic' => $topic, 'synthetic' => true, 'candidate_profile' => $person['profile']],
        ]);

        $coach ? $flow->startCoach($session, $mode, $language, $topic) : $flow->start($session, $mode, [1, 2, 3], $language, $topic);

        return $session;
    }

    /**
     * Answer as the simulated candidate until the session ends.
     *
     * @param  array{profile: string, length: string}  $person
     * @return array{0: int, 1: int} Examiner turns written by the teacher, and by a fallback engine.
     */
    protected function runSession(ExamFlowService $flow, PracticeSession $session, string $candidate, array $person, string $language, int $maxTurns): array
    {
        $clock = 5;
        for ($turn = 0; $turn < $maxTurns; $turn++) {
            $state = $session->refresh()->exam_state;
            if (($state['phase'] ?? null) === ExamFlowService::PHASE_COMPLETED) {
                break;
            }

            $longTurn = ($state['phase'] ?? null) === ExamFlowService::PHASE_CUE_CARD;
            $answer = $this->candidateAnswer($session, $candidate, $person, $language, $longTurn);
            if ($answer === '') {
                break;
            }

            $seconds = max(3, (int) round(str_word_count($answer) / 2.2));
            $clock += $seconds + 4;
            $flow->answer($session, $answer, $seconds, null, $clock);
        }

        // A coaching chat ends on time in real life; close it so the goodbye turn is learned too.
        $state = $session->refresh()->exam_state;
        if (ExamFlowService::isCoach($state) && ($state['phase'] ?? null) !== ExamFlowService::PHASE_COMPLETED) {
            $flow->answer($session, '', 0, null, $clock + 1, timeUp: true);
        }

        $engines = $session->messages()->where('sender', 'ai')->get()->map(fn ($message) => $message->meta['engine'] ?? null);

        return [$engines->filter(fn ($engine) => $engine === 'ollama_cloud')->count(), $engines->filter(fn ($engine) => in_array($engine, ['local_llm', 'cloud_llm', 'offline_composer'], true))->count()];
    }

    /**
     * What the simulated candidate says next, written by the candidate model.
     *
     * @param  array{profile: string, length: string}  $person
     */
    protected function candidateAnswer(PracticeSession $session, string $model, array $person, string $language, bool $longTurn): string
    {
        $isEn = $language === 'en';
        $topic = (string) ($session->exam_state['topic'] ?? '');
        $length = $longTurn ? ($isEn ? '7 to 10 sentences, an uninterrupted talk about the topic card' : '7 sampai 10 kalimat, bicara panjang tanpa jeda tentang kartu topik') : $person['length'];

        $system = $isEn
            ? "You play a CANDIDATE in a spoken mock exam about: {$topic}. Your character: {$person['profile']}. Answer only as the candidate, in natural spoken English, {$length}. No lists, no markdown, no name labels. Never act as the examiner."
            : "Kamu memerankan PESERTA dalam simulasi ujian lisan tentang: {$topic}. Karaktermu: {$person['profile']}. Jawab hanya sebagai peserta, dalam bahasa Indonesia lisan yang natural, {$length}. Tanpa daftar, tanpa markdown, tanpa label nama. Jangan pernah berperan sebagai penguji.";

        // From the candidate's point of view the examiner is the "user".
        $messages = [['role' => 'system', 'content' => $system]];
        foreach ($session->messages()->orderBy('id')->get(['sender', 'message']) as $message) {
            $messages[] = ['role' => $message->sender === 'ai' ? 'user' : 'assistant', 'content' => $message->message];
        }

        $payload = ['model' => $model, 'messages' => $messages, 'stream' => false, 'options' => ['temperature' => 0.9]];
        if (! ExaminerService::isCloudModel($model)) {
            $payload['think'] = false;
        }

        try {
            $response = Http::connectTimeout(3)->timeout(180)->post($this->ollamaUrl().'/api/chat', $payload);
            $text = (string) $response->json('message.content', '');
        } catch (\Throwable $exception) {
            $this->components->warn('Model peserta gagal: '.$exception->getMessage());

            return '';
        }

        $text = preg_replace('/<think>.*?(<\/think>|$)/s', '', $text) ?? '';
        $text = preg_replace('/^\s*(peserta|kandidat|candidate|saya)\s*:\s*/i', '', trim($text)) ?? '';

        return trim(preg_replace('/\s+/', ' ', str_replace(['*', '#'], '', $text)) ?? '');
    }
}
