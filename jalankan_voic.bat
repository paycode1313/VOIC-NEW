@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"
chcp 65001 >nul
title VOIC Prep - Launcher
color 0F

rem =====================================================================
rem  VOIC Prep - satu file untuk menjalankan semuanya.
rem
rem    jalankan_voic.bat           pilih mode (otomatis Lokal)
rem    jalankan_voic.bat lokal     booth / jaringan Wi-Fi yang sama
rem    jalankan_voic.bat online    + link publik HTTPS (Cloudflare Tunnel)
rem
rem  Yang dilakukan: cek prasyarat, persiapan pertama kali (composer,
rem  .env, database, build aset, paket Python), menyalakan Ollama, AI
rem  service, web Laravel, penjadwal hapus-data, lalu cek kesiapan AI
rem  dan membuka browser. Tekan tombol apa saja untuk mematikan semua.
rem =====================================================================

set "MODE=%~1"
set "MISSING=0"
set "STARTED_OLLAMA=0"
set "LANIP="

echo.
echo  ==============================================================
echo     VOIC Prep  -  Latihan wawancara, sidang, dan pitching
echo  ==============================================================
echo.

if /i "%MODE%"=="lokal" goto MODE_OK
if /i "%MODE%"=="online" goto MODE_OK
echo  Pilih mode:
echo    [1] Lokal  - laptop booth dan HP di Wi-Fi yang sama  (default)
echo    [2] Online - tambah link publik HTTPS lewat Cloudflare Tunnel
choice /c 12 /t 8 /d 1 /n /m "  Pilihan [otomatis 1 dalam 8 detik]: "
if errorlevel 2 (set "MODE=online") else (set "MODE=lokal")
:MODE_OK
echo.
echo  Mode: %MODE%
echo.

rem ---------------------------------------------------------------------
echo [1/7] Memeriksa prasyarat...
call :need php "PHP belum ada di PATH. Buka Laragon lalu klik Start All, atau tambahkan PHP ke PATH."
call :need python "Python belum terpasang. Unduh dari python.org dan centang Add Python to PATH."
where ollama >nul 2>&1
if errorlevel 1 (
    echo   [*] Ollama belum terpasang. AI akan memakai jawaban cadangan.
    echo       Unduh dari https://ollama.com/download
) else (
    echo   [OK] ollama
)
if "%MISSING%"=="1" goto FAIL
echo.

rem ---------------------------------------------------------------------
echo [2/7] Persiapan aplikasi web...
if exist vendor\autoload.php goto VENDOR_OK
echo   [..] Pertama kali: memasang paket PHP lewat composer...
where composer >nul 2>&1
if errorlevel 1 (
    echo   [X] Composer belum ada. Pasang dari getcomposer.org atau pakai terminal Laragon.
    goto FAIL
)
call composer install --no-interaction
if errorlevel 1 goto FAIL
:VENDOR_OK

if exist .env goto ENV_OK
echo   [..] Membuat file .env dari .env.example...
copy /y .env.example .env >nul
php artisan key:generate --no-interaction
:ENV_OK

findstr /b /c:"DB_CONNECTION=mysql" .env >nul 2>&1
if errorlevel 1 goto DB_SQLITE
call :port_open 3306
if errorlevel 1 (
    echo   [*] MySQL belum aktif di port 3306. Klik Start All di Laragon, lalu jalankan file ini lagi.
    goto FAIL
)
goto DB_MIGRATE
:DB_SQLITE
if not exist database\database.sqlite type nul > database\database.sqlite
:DB_MIGRATE
php artisan migrate --force --no-interaction >nul
if errorlevel 1 (
    echo   [X] Migrasi database gagal. Jalankan manual untuk melihat errornya: php artisan migrate
    goto FAIL
)
echo   [OK] Database siap

if exist public\hot del /f /q public\hot
if exist public\build\manifest.json goto ASSETS_OK
echo   [..] Pertama kali: membangun tampilan web dengan npm...
where npm >nul 2>&1
if errorlevel 1 (
    echo   [X] Node.js/npm belum ada. Pasang Node.js LTS dari nodejs.org.
    goto FAIL
)
call npm install
call npm run build
if not exist public\build\manifest.json goto FAIL
:ASSETS_OK
echo   [OK] Aset web siap
php artisan config:clear >nul
echo.

rem ---------------------------------------------------------------------
echo [3/7] Paket Python untuk AI...
python -m pip install -q -r ai_service\requirements.txt
if errorlevel 1 (
    echo   [*] Sebagian paket Python gagal dipasang. Fitur suara lokal mungkin nonaktif.
) else (
    echo   [OK] Paket Python lengkap
)
echo.

rem ---------------------------------------------------------------------
echo [4/7] Otak AI - Ollama...
where ollama >nul 2>&1
if errorlevel 1 goto OLLAMA_DONE
call :port_open 11434
if not errorlevel 1 (
    echo   [OK] Ollama sudah berjalan
    goto OLLAMA_DONE
)
echo   [..] Menyalakan Ollama...
start "VOIC_OLLAMA" /min cmd /c "ollama serve"
set "STARTED_OLLAMA=1"
set /a WAIT=0
:WAIT_OLLAMA
call :port_open 11434
if not errorlevel 1 (
    echo   [OK] Ollama berjalan
    goto OLLAMA_DONE
)
set /a WAIT+=1
if !WAIT! geq 15 (
    echo   [*] Ollama lambat menyala, melanjutkan...
    goto OLLAMA_DONE
)
timeout /t 2 /nobreak >nul
goto WAIT_OLLAMA
:OLLAMA_DONE
echo.

rem ---------------------------------------------------------------------
echo [5/7] AI service - telinga, suara, dan penguji...
call :port_open 8001
if not errorlevel 1 (
    echo   [OK] AI service sudah berjalan. Jika baru update kode, tutup jendela VOIC_AI_SERVICE lalu jalankan file ini lagi.
    goto AI_DONE
)
rem cmd /k: jendela tetap terbuka jika ada error, supaya pesannya bisa dibaca
start "VOIC_AI_SERVICE" /min cmd /k "python ai_service\ai_service.py"
set /a WAIT=0
:WAIT_AI
call :port_open 8001
if not errorlevel 1 (
    echo   [OK] AI service berjalan di http://127.0.0.1:8001
    goto AI_DONE
)
set /a WAIT+=1
if !WAIT! geq 30 (
    echo   [*] AI service belum menyala setelah 60 detik. Buka jendela VOIC_AI_SERVICE untuk melihat errornya.
    goto AI_DONE
)
timeout /t 2 /nobreak >nul
goto WAIT_AI
:AI_DONE
echo.

rem ---------------------------------------------------------------------
echo [6/7] Web Laravel dan penjadwal...
for /f "delims=" %%i in ('powershell -NoProfile -Command "(Get-NetIPConfiguration).Where({ $_.IPv4DefaultGateway -ne $null })[0].IPv4Address.IPAddress" 2^>nul') do set "LANIP=%%i"

call :port_open 8000
if not errorlevel 1 (
    echo   [OK] Web sudah berjalan di port 8000
    goto WEB_DONE
)
rem 0.0.0.0 agar HP pengunjung di Wi-Fi yang sama bisa membuka QR rapor
start "VOIC_LARAVEL" /min cmd /k "php artisan serve --host=0.0.0.0 --port=8000"
set /a WAIT=0
:WAIT_WEB
call :port_open 8000
if not errorlevel 1 (
    echo   [OK] Web berjalan
    goto WEB_DONE
)
set /a WAIT+=1
if !WAIT! geq 15 (
    echo   [*] Web lambat menyala. Buka jendela VOIC_LARAVEL untuk melihat errornya.
    goto WEB_DONE
)
timeout /t 1 /nobreak >nul
goto WAIT_WEB
:WEB_DONE

rem Hapus data tamu dan rekaman yang sudah lewat masa simpan (laptop sering mati di malam hari)
php artisan voic:prune
rem Penjadwal: hapus otomatis data tamu dan rekaman lama setiap jam (voic:prune)
start "VOIC_SCHEDULER" /min cmd /c "php artisan schedule:work"
echo   [OK] Penjadwal hapus-data otomatis berjalan

findstr /b /c:"VOIC_PUBLIC_URL=http" .env >nul 2>&1
if errorlevel 1 (
    if defined LANIP (
        echo   [*] QR rapor belum bisa dibuka dari HP. Tambahkan baris ini di file .env:
        echo       VOIC_PUBLIC_URL=http://!LANIP!:8000
    )
)
findstr /b /c:"VOIC_BOOTH_PIN=" .env | findstr /r /c:"=..*" >nul 2>&1
if errorlevel 1 echo   [i] Panel operator /booth nonaktif. Isi VOIC_BOOTH_PIN di .env untuk mengaktifkan.
echo.

rem ---------------------------------------------------------------------
if /i not "%MODE%"=="online" goto SKIP_TUNNEL
echo [+] Link publik - Cloudflare Tunnel...
set "CLOUDFLARED="
if exist "c:\laragon\bin\cloudflared.exe" set "CLOUDFLARED=c:\laragon\bin\cloudflared.exe"
if not defined CLOUDFLARED (
    where cloudflared >nul 2>&1
    if not errorlevel 1 set "CLOUDFLARED=cloudflared"
)
if not defined CLOUDFLARED (
    echo   [*] cloudflared tidak ditemukan. Unduh dari github.com/cloudflare/cloudflared/releases
    echo       lalu simpan sebagai c:\laragon\bin\cloudflared.exe. Melanjutkan dalam mode lokal.
    goto SKIP_TUNNEL
)
start "VOIC_TUNNEL" cmd /k ""%CLOUDFLARED%" tunnel --url http://localhost:8000"
echo   [OK] Link https://....trycloudflare.com muncul di jendela VOIC_TUNNEL.
echo       Agar QR rapor ikut memakai link itu, salin ke .env sebagai VOIC_PUBLIC_URL.
echo.
:SKIP_TUNNEL

rem ---------------------------------------------------------------------
echo [7/7] Mengecek kesiapan AI - model dimuat dulu, bisa sampai 1 menit...
echo.
php artisan voic:doctor
echo.
start "" "http://localhost:8000/latihan"

echo  ==============================================================
echo   VOIC Prep berjalan
echo   - Latihan        : http://localhost:8000/latihan
if defined LANIP echo   - Dari HP/Wi-Fi   : http://!LANIP!:8000/latihan
echo   - Operator booth : http://localhost:8000/booth
echo   - Papan skor     : http://localhost:8000/booth/papan
echo   - Status AI      : php artisan voic:doctor
echo  ==============================================================
echo.
echo  Biarkan jendela ini terbuka selama pameran.
echo  Tekan tombol apa saja di sini untuk MEMATIKAN semua layanan VOIC.
pause >nul
goto STOP

rem ---------------------------------------------------------------------
:STOP
echo.
echo  Mematikan layanan VOIC...
call :kill VOIC_TUNNEL
call :kill VOIC_SCHEDULER
call :kill VOIC_LARAVEL
call :kill VOIC_AI_SERVICE
if "%STARTED_OLLAMA%"=="1" call :kill VOIC_OLLAMA
echo  Selesai. Sampai jumpa.
timeout /t 2 /nobreak >nul
exit /b 0

:FAIL
echo.
echo  [X] Launcher berhenti. Perbaiki hal di atas lalu jalankan file ini lagi.
pause
exit /b 1

rem ---------------------------------------------------------------------
rem Helper: cek program ada di PATH
:need
where %1 >nul 2>&1
if errorlevel 1 (
    echo   [X] %~2
    set "MISSING=1"
) else (
    echo   [OK] %1
)
exit /b 0

rem Helper: errorlevel 0 jika ada proses yang LISTENING di port %1
:port_open
netstat -ano | findstr /r /c:":%1 .*LISTENING" >nul 2>&1
exit /b %errorlevel%

rem Helper: tutup jendela yang dibuka launcher berdasarkan judulnya
:kill
taskkill /fi "WINDOWTITLE eq %1*" /t /f >nul 2>&1
taskkill /fi "WINDOWTITLE eq Administrator:  %1*" /t /f >nul 2>&1
exit /b 0
