<?php

/*
|--------------------------------------------------------------------------
| VOIC Mock Exam Blueprints
|--------------------------------------------------------------------------
|
| Every practice mode follows the same three-part oral exam rhythm:
|   Part 1: short warm-up questions to settle the candidate in.
|   Part 2: a cue card long turn (prep time, then one uninterrupted talk).
|   Part 3: a deeper two-way discussion where the examiner probes.
|
| Each question is bilingual (id / en). "followups" is the maximum number
| of follow-up probes the examiner may ask before moving on.
|
*/

return [

    'prep_seconds' => 60,

    /*
    | Coaching sessions: a free, friendly 10-minute conversation where the coach
    | teaches how to answer (wording, structure, delivery) instead of testing.
    */
    'coach_minutes' => (int) env('VOIC_COACH_MINUTES', 10),

    /*
    | Public base URL for QR codes (LAN IP or tunnel), because "localhost" cannot be
    | opened from a visitor's phone. Example: http://192.168.1.50:8000
    */
    'public_url' => env('VOIC_PUBLIC_URL'),

    /*
    | Guest sessions and recorded answers are deleted after this many days (voic:prune).
    */
    'retention_days' => (int) env('VOIC_RETENTION_DAYS', 7),

    /*
    | PIN for the booth operator page (/booth). Leave empty to disable it.
    */
    'booth_pin' => env('VOIC_BOOTH_PIN'),

    /*
    | Ollama model for the examiner. Empty = the local "voic-coach" model (fully offline).
    | A cloud model such as "kimi-k3:cloud" runs on Ollama's servers (internet, `ollama signin`);
    | when it fails, the local model answers instead.
    */
    'ai_model' => env('VOIC_AI_MODEL'),

    /*
    | "offline" (default): everything runs on this laptop (private, no internet), speed depends on the hardware.
    | "online": for a laptop without a GPU at a booth with internet. A fast cloud model (GROQ_API_KEY or
    | OPENAI_API_KEY) answers first and the browser recognises speech, so the examiner replies in 1-2 seconds.
    | The local AI stays as the automatic fallback when the internet drops. Voice stays local (Piper).
    */
    'speed_mode' => env('VOIC_SPEED_MODE', 'offline'),

    /*
    | Teacher model for `php artisan voic:teacher-generate` (distillation data), and the
    | local model that plays the candidate in those simulated sessions.
    */
    'teacher_model' => env('VOIC_TEACHER_MODEL', 'kimi-k3:cloud'),
    'teacher_candidate_model' => env('VOIC_TEACHER_CANDIDATE_MODEL', 'qwen3:4b'),

    'long_turn_seconds' => 120,

    'modes' => [

        'wawancara' => [
            'role_type' => 'hrd',
            'icon' => 'briefcase',
            'accent' => 'amber',
            'title' => ['id' => 'Wawancara Kerja', 'en' => 'Job Interview'],
            'tagline' => [
                'id' => 'Latihan interview dengan HR recruiter: dari perkenalan sampai pertanyaan situasional.',
                'en' => 'Practise with an HR recruiter, from introductions to situational questions.',
            ],
            'examiner' => ['name' => 'Kak Nadia', 'title' => ['id' => 'Senior HR Recruiter', 'en' => 'Senior HR Recruiter'], 'avatar' => 'images/avatars/hrd-portrait.webp',
                // Face landmarks as fractions of the portrait, used by the live avatar (resources/js/avatar.js).
                'rig' => ['eyes' => [[0.4395, 0.3164], [0.5586, 0.3164]], 'eyeW' => 0.0508, 'eyeH' => 0.0254, 'mouth' => [0.5, 0.4229], 'mouthW' => 0.1133, 'chin' => 0.5078]],
            'topic_label' => ['id' => 'Posisi yang dilamar', 'en' => 'Position you are applying for'],
            'topic_placeholder' => ['id' => 'Contoh: Frontend Developer di startup fintech', 'en' => 'e.g. Frontend Developer at a fintech startup'],
            'context_label' => ['id' => 'Ringkasan CV / pengalaman (opsional)', 'en' => 'CV summary / experience (optional)'],
            'coach' => [
                'title' => ['id' => 'Coach Wawancara', 'en' => 'Interview Coach'],
                'opening' => [
                    'id' => 'Halo, aku Kak Nadia! Sepuluh menit ke depan kita ngobrol santai ya, ini bukan tes kok. Aku bantu kamu latihan cara ngomong dan milih kata yang pas waktu wawancara. Cerita dulu dong, kamu lagi siap-siap wawancara buat posisi apa, dan bagian mana yang paling bikin deg-degan?',
                    'en' => "Hi, I'm Nadia! For the next ten minutes we'll just chat, this isn't a test. I'll help you practise how to speak and which words to use in interviews. Tell me first, what role are you preparing for, and which part makes you most nervous?",
                ],
                'skills' => [
                    'id' => 'memperkenalkan diri dengan menarik, menjawab dengan pola STAR (situasi, tugas, aksi, hasil), memilih kata yang profesional tapi tetap natural, mengurangi kata pengisi seperti eh dan ehm, serta menjawab pertanyaan sulit seperti kelemahan, alasan resign, dan gaji',
                    'en' => 'introducing yourself well, answering with STAR (situation, task, action, result), choosing professional but natural wording, cutting fillers, and handling hard questions such as weaknesses, leaving a job, and salary',
                ],
            ],
            'parts' => [
                1 => [
                    'title' => ['id' => 'Perkenalan', 'en' => 'Introduction'],
                    'subtitle' => ['id' => 'Pertanyaan singkat seputar diri dan motivasi', 'en' => 'Short questions about you and your motivation'],
                    'question_count' => 3,
                    'followups' => 1,
                    'questions' => [
                        ['id' => 'Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.', 'en' => 'Could you start by introducing yourself and your background?'],
                        ['id' => 'Apa yang bikin kamu tertarik melamar posisi :topic ini?', 'en' => 'What made you interested in this :topic role?'],
                        ['id' => 'Kalau teman dekatmu diminta menggambarkan kamu dalam tiga kata, kira-kira apa jawabannya?', 'en' => 'If a close friend described you in three words, what would they say?'],
                        ['id' => 'Apa yang kamu ketahui tentang perusahaan atau bidang yang kamu lamar?', 'en' => 'What do you know about the company or field you are applying to?'],
                        ['id' => 'Di luar pekerjaan, kegiatan apa yang paling kamu nikmati, dan apa yang kamu pelajari dari situ?', 'en' => 'Outside work, what do you enjoy most and what has it taught you?'],
                        ['id' => 'Bagaimana kamu biasanya menjelaskan pekerjaan atau jurusanmu ke orang awam?', 'en' => 'How do you usually explain your work or major to someone outside the field?'],
                        ['id' => 'Apa satu keterampilan yang sedang kamu pelajari sekarang, dan kenapa?', 'en' => 'What is one skill you are learning right now, and why?'],
                        ['id' => 'Lingkungan kerja seperti apa yang bikin kamu paling produktif?', 'en' => 'What kind of work environment makes you most productive?'],
                        ['id' => 'Siapa orang yang paling memengaruhi cara kamu bekerja?', 'en' => 'Who has influenced the way you work the most?'],
                        ['id' => 'Kalau hari ini kamu diterima, apa yang ingin kamu pelajari di bulan pertama?', 'en' => 'If you were hired today, what would you want to learn in your first month?'],
                        ['id' => 'Apa kelebihan yang menurutmu jarang dimiliki kandidat lain?', 'en' => 'What strength do you have that most candidates do not?'],
                        ['id' => 'Apa kekurangan yang sedang kamu perbaiki?', 'en' => 'What weakness are you currently working on?'],
                    ],
                ],
                2 => [
                    'title' => ['id' => 'Cerita Pengalaman', 'en' => 'Experience Story'],
                    'subtitle' => ['id' => 'Satu menit persiapan, dua menit bercerita tanpa disela', 'en' => 'One minute to prepare, two minutes to talk'],
                    'followups' => 1,
                    'cue_cards' => [
                        [
                            'prompt' => ['id' => 'Ceritakan saat kamu menghadapi tantangan besar dalam sebuah tim.', 'en' => 'Describe a time you faced a big challenge in a team.'],
                            'points' => [
                                'id' => ['Situasinya seperti apa', 'Apa tanggung jawabmu', 'Langkah konkret yang kamu ambil', 'Hasilnya dan apa yang kamu pelajari'],
                                'en' => ['What the situation was', 'What your responsibility was', 'The concrete steps you took', 'The result and what you learned'],
                            ],
                        ],
                        [
                            'prompt' => ['id' => 'Ceritakan pencapaian yang paling kamu banggakan.', 'en' => 'Describe the achievement you are most proud of.'],
                            'points' => [
                                'id' => ['Apa pencapaiannya', 'Kenapa itu sulit', 'Bagaimana kamu mencapainya', 'Dampaknya untuk orang lain atau tim'],
                                'en' => ['What the achievement was', 'Why it was difficult', 'How you achieved it', 'Its impact on others or the team'],
                            ],
                        ],
                        [
                            'prompt' => ['id' => 'Ceritakan saat kamu membuat kesalahan di pekerjaan atau organisasi.', 'en' => 'Describe a time you made a mistake at work or in an organisation.'],
                            'points' => [
                                'id' => ['Kesalahannya apa', 'Bagaimana kamu menyadarinya', 'Cara kamu memperbaikinya', 'Apa yang kamu lakukan berbeda sekarang'],
                                'en' => ['What the mistake was', 'How you realised it', 'How you fixed it', 'What you do differently now'],
                            ],
                        ],
                        [
                            'prompt' => ['id' => 'Ceritakan saat kamu harus belajar sesuatu yang baru dalam waktu singkat.', 'en' => 'Describe a time you had to learn something new very quickly.'],
                            'points' => [
                                'id' => ['Apa yang harus dipelajari', 'Kenapa waktunya singkat', 'Cara kamu belajar', 'Hasilnya'],
                                'en' => ['What you had to learn', 'Why time was short', 'How you learned it', 'The outcome'],
                            ],
                        ],
                        [
                            'prompt' => ['id' => 'Ceritakan saat kamu meyakinkan orang lain untuk mengikuti idemu.', 'en' => 'Describe a time you convinced others to follow your idea.'],
                            'points' => [
                                'id' => ['Idenya apa', 'Siapa yang perlu diyakinkan', 'Cara kamu meyakinkan', 'Apa yang terjadi kemudian'],
                                'en' => ['What the idea was', 'Who had to be convinced', 'How you persuaded them', 'What happened next'],
                            ],
                        ],
                        [
                            'prompt' => ['id' => 'Ceritakan proyek atau kegiatan yang paling kamu nikmati.', 'en' => 'Describe the project or activity you enjoyed the most.'],
                            'points' => [
                                'id' => ['Proyeknya apa', 'Peranmu', 'Bagian yang paling seru', 'Apa yang kamu bawa ke pekerjaan berikutnya'],
                                'en' => ['What the project was', 'Your role', 'The most exciting part', 'What you took away from it'],
                            ],
                        ],
                    ],
                ],
                3 => [
                    'title' => ['id' => 'Diskusi Mendalam', 'en' => 'Deep Discussion'],
                    'subtitle' => ['id' => 'Pertanyaan situasional dan cara berpikirmu', 'en' => 'Situational questions and how you think'],
                    'question_count' => 3,
                    'followups' => 2,
                    'questions' => [
                        ['id' => 'Kalau ada rekan satu tim yang terus telat menyelesaikan bagiannya, apa yang akan kamu lakukan?', 'en' => 'If a teammate kept missing their deadlines, what would you do?'],
                        ['id' => 'Bagaimana caramu mengatur prioritas kalau ada tiga tenggat penting di minggu yang sama?', 'en' => 'How would you prioritise three important deadlines in the same week?'],
                        ['id' => 'Menurutmu, apa bedanya tim yang sekadar bekerja bareng dengan tim yang benar-benar solid?', 'en' => 'What separates a group that works together from a truly strong team?'],
                        ['id' => 'Lima tahun lagi kamu ingin berada di posisi seperti apa, dan bagaimana peran ini membantumu ke sana?', 'en' => 'Where do you want to be in five years, and how does this role help?'],
                        ['id' => 'Berapa ekspektasi gaji kamu, dan apa dasar angka tersebut?', 'en' => 'What is your salary expectation and how did you arrive at it?'],
                        ['id' => 'Bagaimana kamu merespons kalau atasan memberi kritik di depan tim?', 'en' => 'How would you respond if your manager criticised you in front of the team?'],
                        ['id' => 'Ceritakan bagaimana kamu akan menolak permintaan rekan kerja tanpa merusak hubungan.', 'en' => 'How would you say no to a colleague without damaging the relationship?'],
                        ['id' => 'Kalau target tim terlihat mustahil, apa yang akan kamu lakukan di minggu pertama?', 'en' => 'If a team target looked impossible, what would you do in the first week?'],
                        ['id' => 'Menurutmu, kapan sebaiknya seseorang pindah kerja?', 'en' => 'When do you think someone should change jobs?'],
                        ['id' => 'Bagaimana kamu memastikan pekerjaanmu tetap berkualitas saat dikejar waktu?', 'en' => 'How do you keep quality high when time is short?'],
                        ['id' => 'Apa yang kamu lakukan kalau tidak setuju dengan keputusan atasan?', 'en' => 'What do you do when you disagree with your manager\'s decision?'],
                        ['id' => 'Teknologi AI makin banyak dipakai di kantor. Bagaimana kamu melihat itu untuk peranmu?', 'en' => 'AI is used more and more at work. How do you see that affecting your role?'],
                    ],
                ],
            ],
            'keywords' => [
                'structure' => ['situasi', 'saat itu', 'waktu itu', 'tugas', 'tanggung jawab', 'tantangan', 'masalah', 'saya memutuskan', 'langkah', 'akhirnya', 'hasilnya', 'berhasil', 'dampak', 'belajar', 'pelajaran', 'situation', 'task', 'challenge', 'i decided', 'action', 'result', 'learned', 'impact'],
                'domain' => ['tim', 'kolaborasi', 'komunikasi', 'deadline', 'tenggat', 'proyek', 'klien', 'target', 'inisiatif', 'memimpin', 'koordinasi', 'prioritas', 'evaluasi', 'feedback', 'team', 'collaboration', 'project', 'client', 'deadline', 'lead', 'priority', 'stakeholder'],
            ],
        ],

        'sidang' => [
            'role_type' => 'dosen_penguji',
            'icon' => 'academic',
            'accent' => 'indigo',
            'title' => ['id' => 'Sidang Skripsi', 'en' => 'Thesis Defense'],
            'tagline' => [
                'id' => 'Simulasi sidang dengan dosen penguji: presentasi inti lalu tanya jawab metodologi.',
                'en' => 'Defend your thesis: core presentation, then methodology Q&A.',
            ],
            'examiner' => ['name' => 'Prof. Hendra', 'title' => ['id' => 'Dosen Penguji Utama', 'en' => 'Lead Examiner'], 'avatar' => 'images/avatars/dosen-portrait.webp',
                // Face landmarks as fractions of the portrait, used by the live avatar (resources/js/avatar.js).
                'rig' => ['eyes' => [[0.4268, 0.3457], [0.5713, 0.3457]], 'eyeW' => 0.0449, 'eyeH' => 0.0215, 'mouth' => [0.501, 0.5068], 'mouthW' => 0.1367, 'chin' => 0.6104]],
            'topic_label' => ['id' => 'Judul skripsi', 'en' => 'Thesis title'],
            'topic_placeholder' => ['id' => 'Contoh: Deteksi cacat kain menggunakan YOLOv8', 'en' => 'e.g. Fabric defect detection using YOLOv8'],
            'context_label' => ['id' => 'Abstrak (opsional, membuat pertanyaan lebih spesifik)', 'en' => 'Abstract (optional, makes questions more specific)'],
            'coach' => [
                'title' => ['id' => 'Dosen Pembimbing', 'en' => 'Thesis Mentor'],
                'opening' => [
                    'id' => 'Halo, saya Prof. Hendra. Sepuluh menit ini anggap saja sesi bimbingan sebelum sidang, bukan ujian. Kita latih cara Anda menjelaskan penelitian dengan runtut dan meyakinkan. Coba ceritakan dulu, penelitian Anda tentang apa, dan pertanyaan penguji seperti apa yang paling Anda khawatirkan?',
                    'en' => 'Hello, I am Professor Hendra. Think of these ten minutes as a supervision session before your defense, not an exam. We will practise explaining your research clearly and convincingly. First, what is your research about, and which examiner questions worry you most?',
                ],
                'skills' => [
                    'id' => 'menjelaskan latar belakang, rumusan masalah, dan metode secara runtut, memakai istilah ilmiah yang tepat tanpa bertele-tele, menjawab kritik penguji dengan tenang, serta mengakui keterbatasan penelitian dengan elegan',
                    'en' => 'explaining background, problem and method in order, using precise academic terms without rambling, answering criticism calmly, and acknowledging limitations gracefully',
                ],
            ],
            'parts' => [
                1 => [
                    'title' => ['id' => 'Pembukaan', 'en' => 'Opening'],
                    'subtitle' => ['id' => 'Latar belakang dan motivasi penelitian', 'en' => 'Background and motivation'],
                    'question_count' => 3,
                    'followups' => 1,
                    'questions' => [
                        ['id' => 'Silakan perkenalkan diri dan sebutkan judul penelitian Anda.', 'en' => 'Please introduce yourself and state your thesis title.'],
                        ['id' => 'Apa yang membuat Anda tertarik meneliti topik :topic ini?', 'en' => 'What drew you to research :topic?'],
                        ['id' => 'Coba jelaskan dengan bahasa sederhana, masalah apa yang ingin diselesaikan penelitian ini?', 'en' => 'In plain words, what problem does this research solve?'],
                        ['id' => 'Siapa yang paling diuntungkan kalau penelitian Anda berhasil?', 'en' => 'Who benefits most if your research succeeds?'],
                        ['id' => 'Bagaimana Anda menemukan celah penelitian ini?', 'en' => 'How did you find the research gap?'],
                        ['id' => 'Apa tujuan utama penelitian Anda dalam satu kalimat?', 'en' => 'What is the main objective of your research in one sentence?'],
                        ['id' => 'Mengapa topik ini penting untuk diteliti sekarang?', 'en' => 'Why is this topic important to study now?'],
                        ['id' => 'Apa batasan ruang lingkup yang Anda tetapkan?', 'en' => 'What scope limits did you set?'],
                        ['id' => 'Penelitian terdahulu mana yang paling memengaruhi Anda?', 'en' => 'Which previous study influenced you the most?'],
                        ['id' => 'Apa kesulitan terbesar selama mengerjakan penelitian ini?', 'en' => 'What was the hardest part of doing this research?'],
                        ['id' => 'Kalau dijelaskan ke orang tua Anda, apa manfaat penelitian ini?', 'en' => 'If you explained it to your parents, what is the benefit of this research?'],
                    ],
                ],
                2 => [
                    'title' => ['id' => 'Presentasi Inti', 'en' => 'Core Presentation'],
                    'subtitle' => ['id' => 'Satu menit persiapan, dua menit memaparkan penelitian', 'en' => 'One minute to prepare, two minutes to present'],
                    'followups' => 1,
                    'cue_cards' => [
                        [
                            'prompt' => ['id' => 'Paparkan inti penelitian Anda di hadapan dewan penguji.', 'en' => 'Present the core of your research to the committee.'],
                            'points' => [
                                'id' => ['Rumusan masalah', 'Metode dan data yang digunakan', 'Hasil utama yang didapat', 'Kontribusi atau kebaruan penelitian'],
                                'en' => ['Problem statement', 'Method and data used', 'Main findings', 'Contribution or novelty'],
                            ],
                        ],
                        [
                            'prompt' => ['id' => 'Jelaskan alur metodologi penelitian Anda dari awal sampai akhir.', 'en' => 'Walk through your methodology from start to finish.'],
                            'points' => [
                                'id' => ['Desain atau pendekatan penelitian', 'Cara pengumpulan data', 'Teknik analisis atau pengujian', 'Cara Anda memvalidasi hasil'],
                                'en' => ['Research design or approach', 'How data was collected', 'Analysis or testing technique', 'How you validated the results'],
                            ],
                        ],
                        [
                            'prompt' => ['id' => 'Jelaskan hasil utama penelitian Anda dan maknanya.', 'en' => 'Explain your main results and what they mean.'],
                            'points' => [
                                'id' => ['Temuan utama', 'Angka atau bukti pendukung', 'Perbandingan dengan penelitian lain', 'Makna bagi bidang Anda'],
                                'en' => ['Main finding', 'Supporting numbers or evidence', 'Comparison with other studies', 'Meaning for your field'],
                            ],
                        ],
                        [
                            'prompt' => ['id' => 'Jelaskan kontribusi dan keterbatasan penelitian Anda.', 'en' => 'Explain the contribution and limitations of your research.'],
                            'points' => [
                                'id' => ['Kontribusi teoretis', 'Kontribusi praktis', 'Keterbatasan utama', 'Saran penelitian lanjutan'],
                                'en' => ['Theoretical contribution', 'Practical contribution', 'Main limitation', 'Future work'],
                            ],
                        ],
                    ],
                ],
                3 => [
                    'title' => ['id' => 'Tanya Jawab Penguji', 'en' => 'Examiner Q&A'],
                    'subtitle' => ['id' => 'Pengujian metodologi, validitas, dan keterbatasan', 'en' => 'Methodology, validity and limitations'],
                    'question_count' => 3,
                    'followups' => 2,
                    'questions' => [
                        ['id' => 'Kenapa Anda memilih metode tersebut dan bukan alternatif lain?', 'en' => 'Why did you choose that method over the alternatives?'],
                        ['id' => 'Bagaimana Anda memastikan data yang digunakan cukup dan representatif?', 'en' => 'How do you know your data is sufficient and representative?'],
                        ['id' => 'Apa keterbatasan terbesar penelitian ini, dan bagaimana pengaruhnya terhadap kesimpulan?', 'en' => 'What is the biggest limitation, and how does it affect your conclusions?'],
                        ['id' => 'Apa yang membedakan penelitian Anda dengan penelitian sebelumnya?', 'en' => 'How does your work differ from previous studies?'],
                        ['id' => 'Kalau penelitian ini dilanjutkan, saran apa yang Anda berikan untuk peneliti berikutnya?', 'en' => 'If this research continued, what would you advise the next researcher?'],
                        ['id' => 'Bagaimana Anda menangani data yang hilang atau tidak lengkap?', 'en' => 'How did you handle missing or incomplete data?'],
                        ['id' => 'Apakah hasil Anda bisa direplikasi oleh peneliti lain? Bagaimana caranya?', 'en' => 'Can other researchers replicate your results? How?'],
                        ['id' => 'Variabel apa yang mungkin memengaruhi hasil tetapi tidak Anda kendalikan?', 'en' => 'Which variables might affect the results but were not controlled?'],
                        ['id' => 'Bagaimana Anda memastikan tidak terjadi bias dalam pengambilan data?', 'en' => 'How did you make sure there was no bias in data collection?'],
                        ['id' => 'Jika hasilnya ternyata tidak signifikan, apa yang akan Anda simpulkan?', 'en' => 'If the results were not significant, what would you conclude?'],
                        ['id' => 'Apa implikasi praktis temuan Anda bagi industri atau masyarakat?', 'en' => 'What are the practical implications of your findings?'],
                        ['id' => 'Bagian mana dari penelitian ini yang paling ingin Anda perbaiki jika mengulang?', 'en' => 'Which part would you improve most if you did it again?'],
                    ],
                ],
            ],
            'keywords' => [
                'structure' => ['rumusan masalah', 'tujuan', 'metode', 'metodologi', 'data', 'hasil', 'kesimpulan', 'kontribusi', 'kebaruan', 'novelty', 'problem statement', 'objective', 'method', 'results', 'conclusion', 'contribution'],
                'domain' => ['sampel', 'dataset', 'variabel', 'hipotesis', 'validasi', 'akurasi', 'presisi', 'uji', 'pengujian', 'literatur', 'penelitian sebelumnya', 'keterbatasan', 'signifikan', 'kuesioner', 'responden', 'algoritma', 'model', 'baseline', 'sample', 'variable', 'hypothesis', 'validation', 'accuracy', 'literature', 'limitation', 'significant'],
            ],
        ],

        'pitching' => [
            'role_type' => 'investor',
            'icon' => 'rocket',
            'accent' => 'violet',
            'title' => ['id' => 'Pitching Startup', 'en' => 'Startup Pitch'],
            'tagline' => [
                'id' => 'Pitch di depan investor: problem, solusi, lalu dibedah habis di sesi due diligence.',
                'en' => 'Pitch to an investor: problem, solution, then a due-diligence grilling.',
            ],
            'examiner' => ['name' => 'Pak William', 'title' => ['id' => 'Partner Venture Capital', 'en' => 'Venture Capital Partner'], 'avatar' => 'images/avatars/investor-portrait.webp',
                // Face landmarks as fractions of the portrait, used by the live avatar (resources/js/avatar.js).
                'rig' => ['eyes' => [[0.4463, 0.2979], [0.5537, 0.2979]], 'eyeW' => 0.0449, 'eyeH' => 0.0195, 'mouth' => [0.5, 0.4111], 'mouthW' => 0.1035, 'chin' => 0.4951]],
            'topic_label' => ['id' => 'Nama dan bidang startup', 'en' => 'Startup name and sector'],
            'topic_placeholder' => ['id' => 'Contoh: KasirKu, aplikasi kasir untuk UMKM', 'en' => 'e.g. KasirKu, a POS app for small shops'],
            'context_label' => ['id' => 'Ringkasan pitch deck (opsional)', 'en' => 'Pitch deck summary (optional)'],
            'coach' => [
                'title' => ['id' => 'Mentor Startup', 'en' => 'Startup Mentor'],
                'opening' => [
                    'id' => 'Halo, saya William. Sepuluh menit ini kita ngobrol santai seperti sesi mentoring sebelum demo day, bukan penilaian. Saya bantu Anda bercerita tentang startup dengan cara yang bikin investor tertarik. Ceritakan dulu startup Anda dalam satu atau dua kalimat, dan bagian mana dari pitch yang paling sulit buat Anda?',
                    'en' => 'Hi, I am William. These ten minutes are a relaxed mentoring chat before demo day, not an evaluation. I will help you tell your startup story in a way investors care about. Describe your startup in one or two sentences, and tell me which part of the pitch is hardest for you.',
                ],
                'skills' => [
                    'id' => 'membuka pitch dengan masalah yang kuat, menjelaskan solusi dengan bahasa sederhana, menyebut angka traksi dan unit economics, menjawab pertanyaan investor dengan lugas, serta menutup dengan permintaan pendanaan yang jelas',
                    'en' => 'opening with a strong problem, explaining the solution simply, quoting traction and unit economics, answering investor questions directly, and closing with a clear ask',
                ],
            ],
            'parts' => [
                1 => [
                    'title' => ['id' => 'Founder & Masalah', 'en' => 'Founder & Problem'],
                    'subtitle' => ['id' => 'Siapa kamu dan masalah apa yang kamu selesaikan', 'en' => 'Who you are and the problem you solve'],
                    'question_count' => 3,
                    'followups' => 1,
                    'questions' => [
                        ['id' => 'Perkenalkan diri dan tim Anda. Kenapa kalian orang yang tepat membangun :topic?', 'en' => 'Introduce yourself and your team. Why are you the right people to build :topic?'],
                        ['id' => 'Masalah apa yang Anda selesaikan, dan seberapa sakit masalah itu bagi pelanggan?', 'en' => 'What problem are you solving, and how painful is it for customers?'],
                        ['id' => 'Siapa pelanggan pertama Anda, dan bagaimana mereka mengatasi masalah ini sebelum ada produk Anda?', 'en' => 'Who is your first customer and how did they cope before your product?'],
                        ['id' => 'Kenapa sekarang? Apa yang berubah sehingga ini saat yang tepat?', 'en' => 'Why now? What changed that makes this the right time?'],
                        ['id' => 'Bagaimana Anda pertama kali menemukan masalah ini?', 'en' => 'How did you first discover this problem?'],
                        ['id' => 'Apa produk Anda dalam satu kalimat yang dimengerti anak SMA?', 'en' => 'What is your product in one sentence a high schooler would understand?'],
                        ['id' => 'Sudah berapa lama tim Anda mengerjakan ini, dan apa yang sudah terbukti?', 'en' => 'How long has your team worked on this, and what has been proven?'],
                        ['id' => 'Siapa yang paling sering mengeluhkan masalah ini?', 'en' => 'Who complains about this problem the most?'],
                        ['id' => 'Kenapa pelanggan belum menyelesaikan masalah ini sendiri?', 'en' => 'Why have customers not solved this themselves?'],
                        ['id' => 'Apa insight unik yang Anda miliki dan tidak dimiliki orang lain?', 'en' => 'What unique insight do you have that others do not?'],
                        ['id' => 'Apa yang membuat Anda yakin ini bukan sekadar fitur, tapi bisnis?', 'en' => 'What makes you sure this is a business and not just a feature?'],
                    ],
                ],
                2 => [
                    'title' => ['id' => 'Elevator Pitch', 'en' => 'Elevator Pitch'],
                    'subtitle' => ['id' => 'Satu menit persiapan, dua menit pitching tanpa disela', 'en' => 'One minute to prepare, two minutes to pitch'],
                    'followups' => 1,
                    'cue_cards' => [
                        [
                            'prompt' => ['id' => 'Sampaikan pitch startup Anda seolah sedang demo day.', 'en' => 'Deliver your pitch as if it were demo day.'],
                            'points' => [
                                'id' => ['Masalah dan target pasar', 'Solusi dan keunggulan produk', 'Model bisnis dan traksi', 'Berapa dana yang dicari dan untuk apa'],
                                'en' => ['Problem and target market', 'Solution and edge', 'Business model and traction', 'How much you are raising and why'],
                            ],
                        ],
                        [
                            'prompt' => ['id' => 'Yakinkan saya bahwa startup Anda bisa tumbuh besar.', 'en' => 'Convince me your startup can grow big.'],
                            'points' => [
                                'id' => ['Ukuran pasar yang realistis', 'Strategi akuisisi pelanggan', 'Apa yang membuat kompetitor sulit meniru', 'Target 12 bulan ke depan'],
                                'en' => ['Realistic market size', 'Customer acquisition strategy', 'What makes you hard to copy', 'Goals for the next 12 months'],
                            ],
                        ],
                        [
                            'prompt' => ['id' => 'Ceritakan perjalanan startup Anda sejauh ini.', 'en' => 'Tell me the story of your startup so far.'],
                            'points' => [
                                'id' => ['Awal mula ide', 'Validasi pertama', 'Titik balik terbesar', 'Apa yang terjadi berikutnya'],
                                'en' => ['How the idea started', 'First validation', 'Biggest turning point', 'What comes next'],
                            ],
                        ],
                        [
                            'prompt' => ['id' => 'Jelaskan bagaimana startup Anda menghasilkan uang.', 'en' => 'Explain how your startup makes money.'],
                            'points' => [
                                'id' => ['Siapa yang membayar', 'Berapa dan seberapa sering', 'Biaya terbesar', 'Kapan mencapai titik impas'],
                                'en' => ['Who pays', 'How much and how often', 'Biggest costs', 'When you break even'],
                            ],
                        ],
                        [
                            'prompt' => ['id' => 'Jelaskan kenapa tim Anda yang akan menang di pasar ini.', 'en' => 'Explain why your team will win this market.'],
                            'points' => [
                                'id' => ['Latar belakang tim', 'Keunggulan tidak adil (unfair advantage)', 'Bukti eksekusi sejauh ini', 'Apa yang akan Anda lakukan dengan pendanaan'],
                                'en' => ['Team background', 'Unfair advantage', 'Execution so far', 'What you will do with funding'],
                            ],
                        ],
                    ],
                ],
                3 => [
                    'title' => ['id' => 'Due Diligence', 'en' => 'Due Diligence'],
                    'subtitle' => ['id' => 'Angka, kompetisi, dan risiko bisnis', 'en' => 'Numbers, competition and risk'],
                    'question_count' => 3,
                    'followups' => 2,
                    'questions' => [
                        ['id' => 'Berapa biaya akuisisi satu pelanggan, dan berapa nilai yang dihasilkan pelanggan itu?', 'en' => 'What does it cost to acquire a customer, and what are they worth to you?'],
                        ['id' => 'Siapa kompetitor terdekat Anda, dan kenapa pelanggan memilih Anda?', 'en' => 'Who is your closest competitor and why do customers choose you?'],
                        ['id' => 'Kalau perusahaan besar meluncurkan fitur yang sama besok, apa yang terjadi pada bisnis Anda?', 'en' => 'If a big company launched the same feature tomorrow, what happens to you?'],
                        ['id' => 'Apa risiko terbesar yang bisa membuat startup ini gagal?', 'en' => 'What is the biggest risk that could kill this startup?'],
                        ['id' => 'Kalau saya investasi hari ini, milestone apa yang Anda capai dalam 12 bulan?', 'en' => 'If I invest today, what milestones will you hit in 12 months?'],
                        ['id' => 'Bagaimana Anda menentukan harga produk, dan apakah pelanggan sudah mau membayar?', 'en' => 'How did you set your price, and are customers already paying?'],
                        ['id' => 'Berapa runway yang Anda miliki sekarang?', 'en' => 'How much runway do you have right now?'],
                        ['id' => 'Kalau pertumbuhan stagnan tiga bulan, apa yang akan Anda ubah?', 'en' => 'If growth stalled for three months, what would you change?'],
                        ['id' => 'Siapa anggota tim yang paling Anda butuhkan berikutnya, dan kenapa?', 'en' => 'Who is the next team member you need most, and why?'],
                        ['id' => 'Bagaimana Anda mengukur kepuasan pelanggan?', 'en' => 'How do you measure customer satisfaction?'],
                        ['id' => 'Regulasi apa yang bisa menghambat bisnis Anda?', 'en' => 'Which regulations could slow your business down?'],
                        ['id' => 'Kenapa Anda butuh investor, bukan tumbuh dari pendapatan sendiri?', 'en' => 'Why do you need investors instead of growing from revenue?'],
                    ],
                ],
            ],
            'keywords' => [
                'structure' => ['masalah', 'solusi', 'pasar', 'pelanggan', 'model bisnis', 'traksi', 'tim', 'pendanaan', 'problem', 'solution', 'market', 'customer', 'business model', 'traction', 'funding', 'raise'],
                'domain' => ['revenue', 'pendapatan', 'margin', 'cac', 'ltv', 'retensi', 'churn', 'kompetitor', 'diferensiasi', 'skala', 'pengguna aktif', 'mrr', 'arr', 'valuasi', 'unit economics', 'payback', 'pilot', 'b2b', 'b2c', 'subscription', 'langganan', 'retention', 'competitor', 'growth', 'users'],
            ],
        ],
    ],

    /*
    | Literal Tailwind classes per accent (kept literal so the JIT compiler sees them).
    */
    'accents' => [
        'amber' => ['soft' => 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300', 'solid' => 'bg-amber-500', 'text' => 'text-amber-600 dark:text-amber-300', 'border' => 'hover:border-amber-400/70'],
        'indigo' => ['soft' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-400/15 dark:text-indigo-300', 'solid' => 'bg-indigo-500', 'text' => 'text-indigo-600 dark:text-indigo-300', 'border' => 'hover:border-indigo-400/70'],
        'violet' => ['soft' => 'bg-violet-100 text-violet-700 dark:bg-violet-400/15 dark:text-violet-300', 'solid' => 'bg-violet-500', 'text' => 'text-violet-600 dark:text-violet-300', 'border' => 'hover:border-violet-400/70'],
    ],

    /*
    | Filler words that hurt fluency. Matched as whole words.
    */
    'fillers' => ['eh', 'ehm', 'em', 'emm', 'hmm', 'hm', 'anu', 'um', 'umm', 'uh', 'uhm', 'er', 'erm', 'apa ya', 'gimana ya', 'apa namanya', 'like', 'you know'],

    /*
    | Discourse markers that signal a well-organised answer.
    */
    'connectors' => ['karena', 'sehingga', 'oleh karena itu', 'maka', 'pertama', 'kedua', 'ketiga', 'selain itu', 'namun', 'tetapi', 'contohnya', 'misalnya', 'akhirnya', 'kesimpulannya', 'intinya', 'dengan demikian', 'di sisi lain', 'sebagai contoh', 'because', 'therefore', 'so', 'first', 'second', 'however', 'for example', 'for instance', 'finally', 'in short', 'on the other hand'],
];
