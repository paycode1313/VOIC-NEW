<?php

namespace Tests\Feature;

use App\Models\PracticeSession;
use App\Services\Exam\ExaminerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BoothTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['*' => Http::response([], 503)]);
        config(['exam.booth_pin' => '2468', 'exam.public_url' => 'http://192.168.1.50:8000']);
    }

    public function test_operator_page_needs_the_pin(): void
    {
        $this->get(route('booth.index'))->assertOk()->assertSee('PIN operator')->assertDontSee('Pengunjung berikutnya');
        $this->get(route('booth.board'))->assertRedirect(route('booth.index'));

        $this->post(route('booth.unlock'), ['pin' => '0000'])->assertSessionHasErrors('pin');
        $this->post(route('booth.unlock'), ['pin' => '2468'])->assertRedirect(route('booth.index'));

        $this->get(route('booth.index'))->assertOk()
            ->assertSee('Pengunjung berikutnya')
            ->assertSee('http://192.168.1.50:8000/latihan');
    }

    public function test_operator_page_is_disabled_without_a_configured_pin(): void
    {
        config(['exam.booth_pin' => null]);

        $this->get(route('booth.index'))->assertOk()->assertSee('VOIC_BOOTH_PIN');
        $this->post(route('booth.unlock'), ['pin' => ''])->assertSessionHasErrors('pin');
    }

    public function test_leaderboard_shows_todays_best_results_with_first_names_only(): void
    {
        foreach ([['Budi Santoso', 6.5], ['Rina Wulandari', 7.5], ['Kemarin Saja', 9.0]] as [$name, $band]) {
            $session = PracticeSession::create([
                'user_id' => null,
                'scenario_type' => 'Sidang Skripsi',
                'exam_mode' => 'sidang',
                'feedback_notes' => ['guest_name' => $name, 'exam_report' => ['overall_band' => $band]],
            ]);
            if ($name === 'Kemarin Saja') {
                $session->forceFill(['created_at' => now()->subDay()])->save();
            }
        }

        $this->withSession(['booth_unlocked' => true])->get(route('booth.board'))->assertOk()
            ->assertSeeInOrder(['Rina', '7.5', 'Budi', '6.5'])
            ->assertDontSee('Wulandari')
            ->assertDontSee('Kemarin');
    }

    public function test_a_visitor_who_tries_again_is_listed_once_with_their_best_band(): void
    {
        // Rina tried three times; anonymous visitors are different people and all stay.
        foreach ([['Rina Wulandari', 'UB', 5.5], ['Rina Wulandari', 'UB', 7.0], ['rina wulandari', 'ub', 6.0], ['Budi Santoso', 'UM', 6.5], ['Pengunjung', null, 4.0], ['Pengunjung', null, 4.5]] as [$name, $campus, $band]) {
            PracticeSession::create([
                'user_id' => null,
                'scenario_type' => 'Wawancara Kerja',
                'exam_mode' => 'wawancara',
                'feedback_notes' => ['guest_name' => $name, 'campus' => $campus, 'exam_report' => ['overall_band' => $band]],
            ]);
        }

        $page = $this->withSession(['booth_unlocked' => true])->get(route('booth.board'))->assertOk()
            ->assertSeeInOrder(['Rina', '7.0', 'Budi', '6.5']);
        $this->assertSame(1, substr_count($page->getContent(), '>Rina<'), 'Rina is listed once');
        $this->assertStringNotContainsString('5.5', $page->getContent());
        $this->assertSame(2, substr_count($page->getContent(), '>Pengunjung<'));
    }

    public function test_next_visitor_clears_the_guest_session(): void
    {
        $this->withSession([
            'booth_unlocked' => true,
            'active_guest_session_id' => 5,
            'guest_session_token' => 'abc',
            'guest_name' => 'Budi',
        ])->post(route('booth.next'))->assertRedirect(route('exam.index'));

        $this->assertNull(session('guest_session_token'));
        $this->assertNull(session('active_guest_session_id'));
        $this->assertTrue(session('booth_unlocked'));
    }

    public function test_welcome_screen_is_public_and_shows_the_examiners_calling(): void
    {
        $this->get(route('attract'))->assertOk()
            ->assertSee('Berani ditelepon')
            ->assertSee('VOIC-INTERVIEW')
            ->assertSee('VOIC-SIDANG')
            ->assertSee('VOIC-BISNIS')
            ->assertSee('Angkat')
            ->assertSee('http://192.168.1.50:8000/latihan')
            ->assertSee("sessionStorage.setItem('voic_kiosk', '1')", false);
    }

    public function test_picking_up_forgets_the_previous_visitor_and_opens_that_examiner(): void
    {
        $this->withSession([
            'active_guest_session_id' => 5,
            'guest_session_token' => 'abc',
            'guest_name' => 'Budi',
            'study_code' => 'X1',
        ])->post(route('attract.pickup'), ['mode' => 'pitching'])->assertRedirect(route('exam.setup', 'pitching'));

        $this->assertNull(session('active_guest_session_id'));
        $this->assertNull(session('guest_session_token'));
        $this->assertNull(session('guest_name'));
        $this->assertNull(session('study_code'));

        $this->post(route('attract.pickup'), ['mode' => 'nope'])->assertNotFound();
    }

    public function test_pages_send_an_idle_kiosk_back_to_the_welcome_screen_but_not_the_call(): void
    {
        $this->get(route('exam.index'))->assertSee("sessionStorage.getItem('voic_kiosk')", false)
            // Kiosk hides sign-in and site links, and offers a way back to the welcome screen.
            ->assertSee("classList.add('voic-kiosk')", false)
            ->assertSee('data-kiosk-hide href="'.route('register').'"', false)
            ->assertSee('data-kiosk-href="'.route('attract').'"', false);
        $this->get(route('attract'))->assertDontSee('Masih di sini?');
    }

    public function test_welcome_screen_shows_an_online_or_offline_chip_for_the_operator(): void
    {
        $this->get(route('attract'))->assertSee('sambut\/status', false)->assertSee('Mode offline');

        $this->getJson(route('attract.status'))->assertOk()
            ->assertJsonStructure(['internet', 'cloud_ai', 'online_voice', 'ai_service']);
    }

    public function test_next_visitor_requires_the_operator(): void
    {
        $this->post(route('booth.next'))->assertForbidden();
    }

    public function test_the_operator_sees_who_answered_the_visitors_lately(): void
    {
        Cache::flush();
        $this->get(route('booth.health'))->assertForbidden();

        $session = PracticeSession::create(['user_id' => null, 'scenario_type' => 'Wawancara Kerja', 'exam_mode' => 'wawancara']);
        foreach (['cloud_llm', 'cloud_llm', 'cloud_llm', 'offline_composer', null] as $engine) {
            $session->messages()->create(['sender' => 'ai', 'message' => 'Halo', 'meta' => array_filter(['engine' => $engine])]);
        }
        // An hour ago: not part of the picture any more.
        $session->messages()->create(['sender' => 'ai', 'message' => 'Lama', 'meta' => ['engine' => 'offline_composer']])
            ->forceFill(['created_at' => now()->subHour()])->save();
        ExaminerService::noteQuotaFull();
        ExaminerService::noteQuotaFull();

        $this->withSession(['booth_unlocked' => true])->getJson(route('booth.health'))->assertOk()
            ->assertJsonPath('turns', ['cloud' => 3, 'local' => 0, 'composer' => 1, 'total' => 4])
            ->assertJsonPath('quota_full', 2)
            ->assertJsonPath('level', 'amber');

        // Mostly the fallback while the quota is full: red, with advice for the operator.
        foreach (range(1, 4) as $i) {
            $session->messages()->create(['sender' => 'ai', 'message' => 'Halo', 'meta' => ['engine' => 'offline_composer']]);
        }
        Cache::put('voic.connectivity', ['internet' => true, 'cloud_ai' => true, 'online_voice' => true, 'ai_service' => true], 60);
        $this->withSession(['booth_unlocked' => true])->getJson(route('booth.health'))
            ->assertJsonPath('level', 'red')
            ->assertJsonPath('advice', fn ($advice) => str_contains((string) $advice, 'Kuota AI cloud gratis penuh'));
    }
}
