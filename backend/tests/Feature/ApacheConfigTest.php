<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ApacheConfigTest extends TestCase
{
    public function test_it_writes_a_booth_config_for_a_fake_apache_folder(): void
    {
        $apache = storage_path('framework/testing/apache-'.uniqid());
        File::ensureDirectoryExists($apache.'/bin');
        File::put($apache.'/bin/httpd.exe', '');
        $phpModule = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'php8apache2_4.dll';

        try {
            $exit = $this->artisan('voic:apache', ['--port' => 8123, '--apache-dir' => $apache]);
            if (! PHP_ZTS || ! is_file($phpModule)) {
                // A PHP build without mod_php: the launcher falls back to `php artisan serve`.
                $exit->assertFailed()->run();

                return;
            }
            // run() now: the folder is removed in finally, before a pending command would run on its own.
            $exit->expectsOutput(str_replace('\\', '/', $apache).'/bin/httpd.exe')->assertSuccessful()->run();

            $config = File::get(storage_path('app/apache/httpd.conf'));
            $this->assertStringContainsString('Listen 0.0.0.0:8123', $config);
            $this->assertStringContainsString('ThreadsPerChild 64', $config);
            $this->assertStringContainsString('DocumentRoot "'.str_replace('\\', '/', public_path()).'"', $config);
            $this->assertStringContainsString('php8apache2_4.dll', $config);
            // The examiner's streamed reply must not be held back by compression.
            $this->assertStringNotContainsString('ndjson', explode('AddOutputFilterByType', $config)[1]);
        } finally {
            File::deleteDirectory($apache);
        }
    }

    public function test_it_fails_without_apache_so_the_launcher_uses_php_artisan_serve(): void
    {
        $this->artisan('voic:apache', ['--apache-dir' => storage_path('framework/testing/no-apache')])->assertFailed();
    }
}
