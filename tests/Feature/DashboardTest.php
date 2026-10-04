<?php

namespace Tests\Feature;

use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guests cannot access the dashboard and are redirected to login.
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * Test that authenticated users can view the dashboard.
     */
    public function test_authenticated_users_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertViewIs('dashboard');
        $response->assertViewHas('totalSessions', 0);
    }

    /**
     * Test that dashboard renders user practice sessions and stats accurately.
     */
    public function test_dashboard_displays_user_practice_sessions_and_stats(): void
    {
        $user = User::factory()->create();

        PracticeSession::factory()->create([
            'user_id' => $user->id,
            'scenario_type' => 'Sidang Skripsi',
            'overall_score' => 90.0,
            'duration_seconds' => 300,
        ]);

        PracticeSession::factory()->create([
            'user_id' => $user->id,
            'scenario_type' => 'Pitching Startup',
            'overall_score' => 80.0,
            'duration_seconds' => 180,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('totalSessions', 2);
        $response->assertViewHas('averageScore', 85.0);
        $response->assertViewHas('bestScore', 90.0);
        $response->assertViewHas('totalDurationSeconds', 480);
        $response->assertSee('Sidang Skripsi');
        $response->assertSee('Pitching Startup');
    }
}
