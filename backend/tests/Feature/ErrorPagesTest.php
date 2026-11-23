<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_missing_page_is_explained_in_indonesian(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halamannya tidak ketemu')
            ->assertSee('href="/latihan"', false)
            ->assertDontSee('Not Found');
    }

    public function test_expired_form_tells_the_visitor_to_reload(): void
    {
        Route::get('/_test/expired', fn () => abort(419));

        $this->get('/_test/expired')->assertStatus(419)->assertSee('kelamaan terbuka');
    }

    public function test_too_many_requests_says_how_long_to_wait(): void
    {
        Route::get('/_test/throttled', fn () => throw new HttpException(429, '', null, ['Retry-After' => 42]));

        $this->get('/_test/throttled')->assertStatus(429)->assertSee('Coba lagi dalam 42 detik.');
    }

    public function test_server_error_page_works_without_the_frontend_build(): void
    {
        config(['app.debug' => false]);
        Route::get('/_test/broken', fn () => throw new \RuntimeException('boom'));

        $this->get('/_test/broken')
            ->assertStatus(500)
            ->assertSee('Ada yang salah di sisi kami')
            ->assertDontSee('boom')
            ->assertDontSee('/build/', false);
    }

    public function test_api_errors_stay_json(): void
    {
        $this->getJson('/api/v1/tidak-ada')->assertNotFound()->assertJsonStructure(['message']);
    }
}
