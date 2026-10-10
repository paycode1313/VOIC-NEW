<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guest users cannot access onboarding endpoints.
     */
    public function test_guests_cannot_complete_or_reset_onboarding(): void
    {
        $response = $this->postJson(route('onboarding.complete'), [
            'onboarding_goal' => 'sidang_skripsi',
            'onboarding_experience' => 'pemula',
        ]);
        $response->assertUnauthorized();

        $resetResponse = $this->postJson(route('onboarding.reset'));
        $resetResponse->assertUnauthorized();
    }

    /**
     * Test that authenticated user can complete onboarding with skripsi goal.
     */
    public function test_user_can_complete_onboarding_with_skripsi_goal(): void
    {
        $user = User::factory()->create([
            'has_completed_onboarding' => false,
        ]);

        $response = $this->actingAs($user)->postJson(route('onboarding.complete'), [
            'onboarding_goal' => 'sidang_skripsi',
            'onboarding_experience' => 'pemula',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'recommended_role' => 'dosen_penguji',
        ]);

        $user->refresh();
        $this->assertTrue($user->has_completed_onboarding);
        $this->assertEquals('sidang_skripsi', $user->onboarding_goal);
        $this->assertEquals('pemula', $user->onboarding_experience);
    }

    /**
     * Test that authenticated user can complete onboarding with hrd goal.
     */
    public function test_user_can_complete_onboarding_with_hrd_goal(): void
    {
        $user = User::factory()->create([
            'has_completed_onboarding' => false,
        ]);

        $response = $this->actingAs($user)->postJson(route('onboarding.complete'), [
            'onboarding_goal' => 'wawancara_kerja',
            'onboarding_experience' => 'menengah',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'recommended_role' => 'hrd',
        ]);

        $user->refresh();
        $this->assertTrue($user->has_completed_onboarding);
        $this->assertEquals('wawancara_kerja', $user->onboarding_goal);
        $this->assertEquals('menengah', $user->onboarding_experience);
    }

    /**
     * Test that user can reset onboarding to view the tutorial again.
     */
    public function test_user_can_reset_onboarding(): void
    {
        $user = User::factory()->create([
            'has_completed_onboarding' => true,
            'onboarding_goal' => 'pitching_startup',
            'onboarding_experience' => 'mahir',
        ]);

        $response = $this->actingAs($user)->postJson(route('onboarding.reset'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $user->refresh();
        $this->assertFalse($user->has_completed_onboarding);
    }

    /**
     * Test that new user on dashboard receives initialOpen true in onboarding wizard.
     */
    public function test_new_user_sees_onboarding_modal_on_dashboard(): void
    {
        $newUser = User::factory()->create([
            'has_completed_onboarding' => false,
        ]);

        $response = $this->actingAs($newUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('initialOpen: true', false);
        $response->assertSee('Panduan Orientasi & Personalisasi', false);
        $response->assertSee('Panduan Pemakaian');
    }

    /**
     * Test that returning user who completed onboarding has initialOpen false.
     */
    public function test_returning_user_does_not_auto_open_onboarding(): void
    {
        $completedUser = User::factory()->create([
            'has_completed_onboarding' => true,
            'onboarding_goal' => 'sidang_skripsi',
            'onboarding_experience' => 'pemula',
        ]);

        $response = $this->actingAs($completedUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('initialOpen: false', false);
        $response->assertSee('Panduan & Tutorial', false);
    }
}
