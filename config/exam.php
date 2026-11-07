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
            'examiner' => ['name' => 'Kak Nadia', 'title' => ['id' => 'Senior HR Recruiter', 'en' => 'Senior HR Recruiter'], 'avatar' => 'images/avatars/hrd.webp'],
            'topic_label' => ['id' => 'Posisi yang dilamar', 'en' => 'Position you are applying for'],
            'topic_placeholder' => ['id' => 'Contoh: Frontend Developer di startup fintech', 'en' => 'e.g. Frontend Developer at a fintech startup'],
            'context_label' => ['id' => 'Ringkasan CV / pengalaman (opsional)', 'en' => 'CV summary / experience (optional)'],
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
            'examiner' => ['name' => 'Prof. Hendra', 'title' => ['id' => 'Dosen Penguji Utama', 'en' => 'Lead Examiner'], 'avatar' => 'images/avatars/dosen.webp'],
            'topic_label' => ['id' => 'Judul skripsi', 'en' => 'Thesis title'],
            'topic_placeholder' => ['id' => 'Contoh: Deteksi cacat kain menggunakan YOLOv8', 'en' => 'e.g. Fabric defect detection using YOLOv8'],
            'context_label' => ['id' => 'Abstrak (opsional, membuat pertanyaan lebih spesifik)', 'en' => 'Abstract (optional, makes questions more specific)'],
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
            'examiner' => ['name' => 'Pak William', 'title' => ['id' => 'Partner Venture Capital', 'en' => 'Venture Capital Partner'], 'avatar' => 'images/avatars/investor.webp'],
            'topic_label' => ['id' => 'Nama dan bidang startup', 'en' => 'Startup name and sector'],
            'topic_placeholder' => ['id' => 'Contoh: KasirKu, aplikasi kasir untuk UMKM', 'en' => 'e.g. KasirKu, a POS app for small shops'],
            'context_label' => ['id' => 'Ringkasan pitch deck (opsional)', 'en' => 'Pitch deck summary (optional)'],
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
