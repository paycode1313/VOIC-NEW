<?php

namespace Tests\Feature;

use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    /**
     * "Hapus akun" promises the recordings go too: the database cascade alone left the voice files on disk.
     */
    public function test_deleting_the_account_removes_the_recorded_answers_from_disk(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $mine = PracticeSession::factory()->create(['user_id' => $user->id, 'exam_mode' => 'sidang']);
        $other = PracticeSession::factory()->create(['user_id' => User::factory()->create()->id, 'exam_mode' => 'sidang']);
        Storage::disk('local')->put($mine->recordingsDirectory().'/1.webm', 'audio');
        Storage::disk('local')->put($other->recordingsDirectory().'/2.webm', 'audio');

        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertRedirect('/');

        $this->assertModelMissing($mine);
        Storage::disk('local')->assertMissing($mine->recordingsDirectory());
        Storage::disk('local')->assertExists($other->recordingsDirectory().'/2.webm');
    }

    /**
     * Folders left behind by deletions before the fix above are cleaned up by the hourly prune.
     */
    public function test_prune_removes_recordings_whose_session_no_longer_exists(): void
    {
        Storage::fake('local');
        $kept = PracticeSession::factory()->create(['user_id' => User::factory()->create()->id, 'exam_mode' => 'sidang']);
        Storage::disk('local')->put($kept->recordingsDirectory().'/1.webm', 'audio');
        Storage::disk('local')->put('exam-recordings/99999/5.webm', 'audio');
        Storage::disk('local')->put('exam-recordings/'.($kept->id + 1).'/pending-abc.webm', 'audio');

        $this->artisan('voic:prune')->assertSuccessful();

        Storage::disk('local')->assertExists($kept->recordingsDirectory().'/1.webm');
        Storage::disk('local')->assertMissing('exam-recordings/99999');
        Storage::disk('local')->assertMissing('exam-recordings/'.($kept->id + 1));
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
