<?php

namespace Tests\Feature;

use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeSessionFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guests cannot access the practice room.
     */
    public function test_guests_cannot_access_practice_room(): void
    {
        $response = $this->get('/practice');

        $response->assertRedirect('/login');
    }

    /**
     * Test that authenticated users can view the practice room.
     */
    public function test_authenticated_users_can_access_practice_room(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/practice');

        $response->assertOk();
        $response->assertViewIs('practice.create');
        $response->assertSee('Sidang Skripsi');
        $response->assertSee('Pitching Startup');
    }

    /**
     * Test that authenticated users can submit practice session data and receive JSON redirect.
     */
    public function test_authenticated_users_can_submit_practice_session(): void
    {
        $user = User::factory()->create();

        $payload = [
            'scenario_type' => 'Sidang Skripsi',
            'duration_seconds' => 180,
            'overall_score' => 88.50,
            'feedback_notes' => [
                'summary' => 'Presentasi sangat baik dan percaya diri.',
                'eye_contact_score' => 85,
                'smile_rate' => 78,
                'pace_wpm' => 135,
                'clarity_score' => 90,
                'strengths' => ['Kontak mata fokus'],
                'improvements' => ['Tingkatkan dinamika intonasi'],
            ],
        ];

        $response = $this->actingAs($user)
            ->postJson('/practice', $payload);

        $response->assertCreated();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('practice_sessions', [
            'user_id' => $user->id,
            'scenario_type' => 'Sidang Skripsi',
            'duration_seconds' => 180,
        ]);
    }

    /**
     * Test that validation fails if required fields are missing.
     */
    public function test_submission_fails_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/practice', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['scenario_type', 'duration_seconds', 'overall_score']);
    }

    /**
     * Test that a user can view their practice report.
     */
    public function test_user_can_view_their_own_practice_report(): void
    {
        $user = User::factory()->create();
        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'scenario_type' => 'Pitching Startup',
            'overall_score' => 92.00,
        ]);

        $response = $this->actingAs($user)
            ->get(route('practice.show', $session));

        $response->assertOk();
        $response->assertViewIs('practice.show');
        $response->assertSee('Pitching Startup');
        $response->assertSee('92.0');
    }

    /**
     * Test that a user cannot view another user's practice report (403 Forbidden).
     */
    public function test_user_cannot_view_another_users_practice_report(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $session = PracticeSession::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->get(route('practice.show', $session));

        $response->assertForbidden();
    }
}
