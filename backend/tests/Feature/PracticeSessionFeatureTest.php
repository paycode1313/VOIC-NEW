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

        $this->actingAs($user)->get('/practice')->assertRedirect(route('exam.index'));

        $this->actingAs($user)->get(route('exam.index'))
            ->assertOk()
            ->assertSee('Sidang Skripsi')
            ->assertSee('Pitching Startup');
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
