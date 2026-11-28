<?php

namespace Tests\Feature;

use App\Models\PracticeSession;
use App\Models\User;
use App\Services\CredentialVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CertificateSerialTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A printed or shared certificate keeps matching its verification page: the serial depends on what is
     * stored, not on the app's timezone. Serials made while the app ran on UTC stay exactly the same.
     */
    public function test_a_certificate_serial_survives_a_change_of_timezone(): void
    {
        $user = User::factory()->create();
        $session = PracticeSession::factory()->create(['user_id' => $user->id]);
        DB::table('practice_sessions')->where('id', $session->id)->update(['created_at' => '2026-11-20 08:15:00']);

        // What the app printed before the timezone became Asia/Jakarta.
        $printed = 'VOIC-CERT-'.strtoupper(substr(hash('sha256', $session->id.'|2026-11-20T08:15:00.000000Z|'.$user->id), 0, 10));

        foreach (['UTC', 'Asia/Jakarta'] as $timezone) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);

            $this->assertSame($printed, app(CredentialVerificationService::class)->generateCertificateSerial($session->fresh()), "Serial changed under {$timezone}.");
        }
    }
}
