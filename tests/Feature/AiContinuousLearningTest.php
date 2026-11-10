<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiContinuousLearningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that user model returns default AI learning profile structure.
     */
    public function test_user_has_default_ai_learning_profile(): void
    {
        $user = User::factory()->create();

        $profile = $user->getAiLearningProfile();

        $this->assertIsArray($profile);
        $this->assertEquals(1, $profile['learning_level']);
        $this->assertEquals(0, $profile['total_turns_learned']);
        $this->assertArrayHasKey('identified_weaknesses', $profile);
        $this->assertArrayHasKey('mastered_topics', $profile);
    }
}
