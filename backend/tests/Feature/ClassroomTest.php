<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClassroomTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A student finishes a real one-part exam through the same engine the web uses.
     */
    protected function finishedExam(User $student): PracticeSession
    {
        Http::fake(['*' => Http::response([], 503)]);
        Sanctum::actingAs($student);
        $id = $this->postJson(route('api.v1.exam.start', 'sidang'), ['parts' => [1], 'consent' => true])->json('session_id');
        for ($i = 0; $i < 8; $i++) {
            $turn = $this->postJson(route('api.v1.exam.answer', $id), [
                'answer' => 'Penelitian saya memakai YOLOv8 dengan 2000 gambar dari pabrik tekstil karena butuh deteksi cepat dan akurat.',
                'duration_seconds' => 15,
            ]);
            if ($turn->json('completed')) {
                break;
            }
        }
        $this->postJson(route('api.v1.exam.finish', $id))->assertOk();

        return PracticeSession::findOrFail($id);
    }

    public function test_mentor_creates_a_class_and_a_student_joins_with_the_code(): void
    {
        $mentor = User::factory()->create();
        $student = User::factory()->create(['name' => 'Rina Mahasiswa']);

        $this->actingAs($mentor)->post(route('classrooms.store'), ['name' => 'Bimbingan Skripsi TI'])->assertRedirect();
        $classroom = Classroom::firstOrFail();
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{6}$/', $classroom->join_code);
        $this->get(route('classrooms.show', $classroom))->assertOk()->assertSee($classroom->join_code)->assertSee('Belum ada mahasiswa');

        // The link fills the code in; joining needs explicit consent.
        $this->actingAs($student)->get(route('classrooms.join', strtolower($classroom->join_code)))->assertOk()->assertSee('Bimbingan Skripsi TI');
        $this->post(route('classrooms.join.store'), ['code' => $classroom->join_code])->assertSessionHasErrors('consent');
        $this->post(route('classrooms.join.store'), ['code' => 'ZZZZZZ', 'consent' => '1'])->assertSessionHasErrors('code');
        $this->post(route('classrooms.join.store'), ['code' => ' '.strtolower($classroom->join_code).' ', 'consent' => '1'])->assertRedirect(route('classrooms.index'));

        $this->assertTrue($classroom->members()->whereKey($student->id)->exists());
        $this->assertTrue($mentor->mentors($student));
        $this->get(route('classrooms.index'))->assertSee('Bimbingan Skripsi TI')->assertSee('Keluar');
    }

    public function test_mentor_follows_a_students_progress_and_reads_reports_but_cannot_change_them(): void
    {
        $mentor = User::factory()->create();
        $student = User::factory()->create(['name' => 'Rina Mahasiswa']);
        $session = $this->finishedExam($student);
        $classroom = Classroom::create(['owner_id' => $mentor->id, 'name' => 'Kelas A', 'join_code' => Classroom::newJoinCode()]);
        $classroom->members()->attach($student->id);

        $band = number_format((float) $session->feedback_notes['exam_report']['overall_band'], 1);
        $this->actingAs($mentor)->get(route('classrooms.show', $classroom))->assertOk()->assertSee('Rina Mahasiswa')->assertSee($band);
        $this->get(route('classrooms.member', [$classroom, $student]))->assertOk()->assertSee('Perkembangan band')->assertSee(route('exam.result', $session), false);

        $this->actingAs($student)->get(route('exam.result', $session))->assertOk()->assertSee('Hapus data saya');

        // The mentor reads the report, but cannot delete it.
        $this->actingAs($mentor);
        $this->get(route('exam.result', $session))->assertOk()->assertDontSee('Hapus data saya');
        $this->delete(route('exam.destroy', $session))->assertForbidden();
        $this->assertNotNull($session->fresh());
    }

    public function test_reports_stay_private_without_membership_and_after_leaving(): void
    {
        $mentor = User::factory()->create();
        $student = User::factory()->create();
        $session = $this->finishedExam($student);
        $classroom = Classroom::create(['owner_id' => $mentor->id, 'name' => 'Kelas A', 'join_code' => Classroom::newJoinCode()]);

        $this->actingAs($mentor)->get(route('exam.result', $session))->assertForbidden();
        $this->get(route('classrooms.member', [$classroom, $student]))->assertNotFound();

        $classroom->members()->attach($student->id);
        $this->get(route('exam.result', $session))->assertOk();

        $this->actingAs($student)->delete(route('classrooms.leave', $classroom))->assertRedirect();
        $this->actingAs($mentor)->get(route('exam.result', $session))->assertForbidden();
    }

    public function test_only_the_owner_manages_a_class(): void
    {
        $mentor = User::factory()->create();
        $other = User::factory()->create();
        $student = User::factory()->create();
        $classroom = Classroom::create(['owner_id' => $mentor->id, 'name' => 'Kelas A', 'join_code' => Classroom::newJoinCode()]);
        $classroom->members()->attach($student->id);

        $this->actingAs($other)->get(route('classrooms.show', $classroom))->assertForbidden();
        $this->get(route('classrooms.member', [$classroom, $student]))->assertForbidden();
        $this->delete(route('classrooms.member.remove', [$classroom, $student]))->assertForbidden();
        $this->delete(route('classrooms.destroy', $classroom))->assertForbidden();

        $this->actingAs($mentor)->delete(route('classrooms.member.remove', [$classroom, $student]))->assertRedirect();
        $this->assertFalse($mentor->mentors($student));
        $this->delete(route('classrooms.destroy', $classroom))->assertRedirect(route('classrooms.index'));
        $this->assertNull($classroom->fresh());
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('classrooms.index'))->assertRedirect(route('login'));
        $this->get(route('classrooms.join', 'ABC123'))->assertRedirect(route('login'));
    }
}
