<?php

namespace Tests\Feature;

use App\Models\PracticeSession;
use App\Models\StudyAttempt;
use App\Models\StudySurvey;
use App\Services\Exam\ExamFlowService;
use App\Services\Study\UserStudy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserStudyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response([], 503)]);
    }

    /**
     * Start and finish one short (part 1 only) exam as a guest with a participant code.
     */
    private function practise(?string $code, string $answer): PracticeSession
    {
        $this->post(route('exam.start', 'wawancara'), array_filter([
            'topic' => 'Data Analyst', 'language' => 'id', 'parts' => [1], 'consent' => '1', 'study_code' => $code,
        ]))->assertRedirect();

        $session = PracticeSession::latest('id')->firstOrFail();
        for ($i = 0; $i < 10 && $session->refresh()->exam_state['phase'] !== ExamFlowService::PHASE_COMPLETED; $i++) {
            $this->postJson(route('exam.answer', $session), ['answer' => $answer, 'duration_seconds' => 30, 'timestamp_seconds' => $i * 30])->assertOk();
        }
        $this->postJson(route('exam.finish', $session), ['duration_seconds' => 200])->assertOk();

        return $session->refresh();
    }

    public function test_participant_code_is_normalized_remembered_and_scores_are_logged_anonymously(): void
    {
        $session = $this->practise(' p-01 ', 'Eee saya, eh, analis data, anu, pakai Excel.');

        $this->assertSame('P-01', $session->feedback_notes['study_code']);
        $this->assertSame('P-01', session('study_code'));
        $this->get(route('exam.setup', 'sidang'))->assertSee('value="P-01"', false);

        $attempt = StudyAttempt::sole();
        $this->assertSame('P-01', $attempt->code);
        $this->assertSame($session->feedback_notes['exam_report']['overall_band'], $attempt->overall_band);
        $this->assertSame(['fluency', 'vocabulary', 'structure', 'delivery'], array_keys($attempt->criteria));
        $this->assertNotNull($attempt->fillers_per_minute);

        $this->get(route('exam.result', $session))->assertSee('latihan ke-1 dari 3');

        // The trial data has no name or transcript and outlives the automatic deletion of guest sessions.
        $this->travel(30)->days();
        $this->artisan('voic:prune')->assertSuccessful();
        $this->assertSame(0, PracticeSession::count());
        $this->assertNull(StudyAttempt::sole()->practice_session_id);
    }

    public function test_sessions_without_a_code_are_not_part_of_the_trial(): void
    {
        $session = $this->practise(null, 'Saya analis data yang terbiasa memakai SQL dan Python untuk laporan mingguan.');

        $this->assertArrayNotHasKey('study_code', $session->feedback_notes);
        $this->assertSame(0, StudyAttempt::count());
        $this->get(route('exam.result', $session))->assertDontSee('Ikut uji coba VOIC?')->assertDontSee('latihan ke-');
    }

    public function test_questionnaire_after_the_third_practice_and_the_study_report(): void
    {
        $weak = 'Eee, ya, anu, saya, eh, kurang tahu.';
        $strong = 'Saya analis data selama dua tahun di e-commerce. Contohnya, saya membuat dasbor penjualan dengan SQL dan Python yang menurunkan waktu laporan dari tiga hari menjadi dua jam, sehingga tim pemasaran bisa mengambil keputusan lebih cepat.';

        $this->practise('P01', $weak);
        $second = $this->practise('P01', $weak);
        $this->post(route('exam.survey', $second), [])->assertNotFound();

        $third = $this->practise('P01', $strong);
        $this->get(route('exam.result', $third))->assertOk()->assertSee('Langkah terakhir: 6 pertanyaan singkat');

        $this->post(route('exam.survey', $third), ['answers' => ['confidence' => 5]])->assertSessionHasErrors('answers.coherent');
        $answers = array_fill_keys(array_keys(UserStudy::QUESTIONS), 4);
        $this->post(route('exam.survey', $third), ['answers' => $answers, 'comment' => 'Seru!'])->assertRedirect(route('exam.result', $third));
        $this->assertSame('Seru!', StudySurvey::sole()->comment);

        // Once per participant.
        $this->get(route('exam.result', $third))->assertDontSee('Langkah terakhir');
        $this->post(route('exam.survey', $third), ['answers' => $answers])->assertNotFound();

        $dir = 'storage/framework/testing/study_'.uniqid();
        $this->artisan('voic:study-report', ['--output' => $dir])
            ->expectsOutputToContain('1 peserta, 1 sudah 3 kali latihan, 1 mengisi kuesioner.')
            ->expectsOutputToContain('Kalimat untuk pitch deck')
            ->assertSuccessful();

        $csv = File::get(base_path($dir.'/peserta.csv'));
        $this->assertStringContainsString('P01;3;wawancara', $csv);
        $this->assertStringContainsString('<svg', File::get(base_path($dir.'/grafik.html')));
        $this->assertStringContainsString('Seru!', File::get(base_path($dir.'/kuesioner.csv')));
        File::deleteDirectory(base_path($dir));

        $attempts = StudyAttempt::orderBy('id')->get();
        $this->assertGreaterThan($attempts->first()->overall_band, $attempts->last()->overall_band);
    }

    public function test_study_report_never_claims_an_improvement_the_data_does_not_show(): void
    {
        $strong = 'Saya analis data selama dua tahun di e-commerce. Contohnya, saya membuat dasbor penjualan dengan SQL dan Python yang menurunkan waktu laporan dari tiga hari menjadi dua jam, sehingga tim pemasaran bisa mengambil keputusan lebih cepat.';
        $this->practise('P02', $strong);
        $this->practise('P02', $strong);
        $this->practise('P02', 'Eee, ya, anu, saya, eh, kurang tahu.');

        $dir = 'storage/framework/testing/study_'.uniqid();
        $this->artisan('voic:study-report', ['--output' => $dir])
            ->expectsOutputToContain('Skor rata-rata belum naik')
            ->doesntExpectOutputToContain('Kalimat untuk pitch deck')
            ->assertSuccessful();
        File::deleteDirectory(base_path($dir));
    }

    public function test_study_report_without_data_explains_how_to_start(): void
    {
        $this->artisan('voic:study-report')->expectsOutputToContain('Kode peserta')->assertFailed();
    }
}
