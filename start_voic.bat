@echo off
title VOIC - Voice ^& Optics Intelligent Coach (Launcher)
color 0b

echo ================================================================
echo    VOIC - Voice ^& Optics Intelligent Coach (Innofest 2026)
echo ================================================================
echo.
echo [1/4] Memeriksa status mesin AI Ollama...
tasklist /FI "IMAGENAME eq ollama.exe" 2>NUL | find /I /N "ollama.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo   [OK] Ollama sudah aktif di latar belakang.
) else (
    echo   [..] Menjalankan Ollama Serve di port 11434...
    start "Ollama Engine" /min cmd /c "ollama serve"
)

echo.
echo [2/4] Menjalankan AI Service Python (FastAPI + Qwen 2.5 + TTS)...
tasklist /FI "WINDOWTITLE eq VOIC_AI_SERVICE*" 2>NUL | find /I /N "cmd.exe">NUL
start "VOIC_AI_SERVICE" /min cmd /c "python ai_service\ai_service.py"
echo   [OK] AI Service aktif di http://127.0.0.1:8001

echo.
echo [3/4] Menjalankan Server Web Laravel (Port 8000)...
start "VOIC_LARAVEL_SERVER" /min cmd /c "php artisan serve --port=8000"
echo   [OK] Laravel aktif di http://localhost:8000

echo.
echo [4/4] Membuka Browser Aplikasi VOIC...
timeout /t 3 /nobreak >nul
start http://localhost:8000/login

echo.
echo ================================================================
echo  SEMUA LAYANAN VOIC TELAH BERJALAN!
echo  - Web Application : http://localhost:8000
echo  - AI FastAPI      : http://127.0.0.1:8001
echo  - Ollama LLM      : http://127.0.0.1:11434 (Qwen 2.5)
echo.
echo  Akun Demo Booth:
echo    Email    : test@example.com
echo    Password : password
echo    (Atau klik tombol 'Isi Otomatis' di halaman login)
echo ================================================================
echo.
echo Tekan tombol apa saja untuk menutup jendela launcher ini...
pause >nul
