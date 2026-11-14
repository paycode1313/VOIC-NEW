<?php

namespace Tests\Feature;

use App\Models\PracticeSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->get(route('exam.index'))->assertSee("sessionStorage.getItem('voic_kiosk')", false);
        $this->get(route('attract'))->assertDontSee('Masih di sini?');
    }

    public function test_next_visitor_requires_the_operator(): void
    {
        $this->post(route('booth.next'))->assertForbidden();
    }
}
