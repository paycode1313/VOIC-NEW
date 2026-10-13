<?php

namespace Tests\Feature;

use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeHistoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Unauthenticated users are redirected to login when visiting history page.
     */
    public function test_guest_cannot_access_history_page(): void
    {
        $response = $this->get(route('practice.history'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Authenticated user can view the session history page.
     */
    public function test_authenticated_user_can_view_history_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('practice.history'));

        $response->assertOk();
        $response->assertViewIs('practice.history');
        $response->assertViewHas('sessions');
        $response->assertViewHas('roleOptions');
    }

    /**
     * History page shows the user's own sessions.
     */
    public function test_history_displays_own_sessions(): void
    {
        $user = User::factory()->create();
        $role = AiRole::factory()->create();

        PracticeSession::factory()->count(3)->create([
            'user_id' => $user->id,
            'ai_role_id' => $role->id,
        ]);

        // Another user's session should not appear
        PracticeSession::factory()->create([
            'user_id' => User::factory(),
            'ai_role_id' => $role->id,
        ]);

        $response = $this->actingAs($user)->get(route('practice.history'));

        $response->assertOk();
        $this->assertCount(3, $response->viewData('sessions'));
    }

    /**
     * History page can filter by AI role type.
     */
    public function test_history_filters_by_role_type(): void
    {
        $user = User::factory()->create();
        $dosen = AiRole::factory()->create(['role_type' => 'dosen_penguji']);
        $hrd = AiRole::factory()->create(['role_type' => 'hrd']);

        PracticeSession::factory()->count(2)->create([
            'user_id' => $user->id,
            'ai_role_id' => $dosen->id,
        ]);
        PracticeSession::factory()->create([
            'user_id' => $user->id,
            'ai_role_id' => $hrd->id,
        ]);

        $response = $this->actingAs($user)->get(route('practice.history', ['role' => 'dosen_penguji']));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('sessions'));
    }

    /**
     * History page can search by scenario type.
     */
    public function test_history_searches_by_scenario_type(): void
    {
        $user = User::factory()->create();
        PracticeSession::factory()->create([
            'user_id' => $user->id,
            'scenario_type' => 'Sidang Skripsi',
        ]);
        PracticeSession::factory()->create([
            'user_id' => $user->id,
            'scenario_type' => 'Wawancara Kerja',
        ]);

        $response = $this->actingAs($user)->get(route('practice.history', ['search' => 'Skripsi']));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('sessions'));
    }

    /**
     * History page can filter by minimum score.
     */
    public function test_history_filters_by_minimum_score(): void
    {
        $user = User::factory()->create();
        PracticeSession::factory()->create([
            'user_id' => $user->id,
            'overall_score' => 90.5,
        ]);
        PracticeSession::factory()->create([
            'user_id' => $user->id,
            'overall_score' => 55.0,
        ]);

        $response = $this->actingAs($user)->get(route('practice.history', ['min_score' => 80]));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('sessions'));
    }

    /**
     * Authenticated user can delete their own practice session.
     */
    public function test_user_can_delete_own_session(): void
    {
        $user = User::factory()->create();
        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->deleteJson(route('practice.destroy', $session));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('practice_sessions', ['id' => $session->id]);
    }

    /**
     * User cannot delete another user's practice session.
     */
    public function test_user_cannot_delete_other_users_session(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $session = PracticeSession::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)->deleteJson(route('practice.destroy', $session));

        $response->assertForbidden();
        $this->assertDatabaseHas('practice_sessions', ['id' => $session->id]);
    }

    /**
     * Guest cannot delete a session.
     */
    public function test_guest_cannot_delete_session(): void
    {
        $session = PracticeSession::factory()->create();

        $response = $this->deleteJson(route('practice.destroy', $session));

        $response->assertUnauthorized();
    }

    /**
     * Sessions are paginated at 15 per page.
     */
    public function test_history_paginates_results(): void
    {
        $user = User::factory()->create();
        PracticeSession::factory()->count(20)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('practice.history'));

        $response->assertOk();
        $sessions = $response->viewData('sessions');
        $this->assertCount(15, $sessions);
        $this->assertEquals(20, $sessions->total());
    }
}
