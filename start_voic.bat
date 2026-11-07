@echo off
cd /d "%~dp0"
title VOIC - Voice ^& Optics Intelligent Coach (Launcher)
color 0b

echo ================================================================
echo    VOIC - Voice ^& Optics Intelligent Coach (Innofest 2026)
echo ================================================================
echo.
echo [1/5] Memeriksa status Database MySQL (Laragon)...
netstat -ano | findstr /R ":3306 " >nul 2>&1
if "%ERRORLEVEL%"=="0" (
    echo   [OK] Database MySQL aktif di port 3306.
) else (
    echo   [!] PERHATIAN: MySQL belum terdeteksi aktif di port 3306.
    echo       Pastikan Anda sudah mengklik tombol "Start All" di Laragon!
)

echo.
echo [2/5] Memeriksa status mesin AI Ollama...
netstat -ano | findstr /R ":11434 " >nul 2>&1
if "%ERRORLEVEL%"=="0" (
    echo   [OK] Ollama sudah aktif dan siap melayani di port 11434.
) else (
    tasklist /FI "IMAGENAME eq ollama.exe" 2>NUL | find /I /N "ollama.exe">NUL
    if "%ERRORLEVEL%"=="0" (
        echo   [OK] Ollama sudah aktif di latar belakang.
    ) else (
        echo   [..] Menjalankan Ollama Serve di port 11434...
        start "Ollama Engine" /min cmd /c "ollama serve"
    )
)

echo.
echo [3/5] Memeriksa model lokal (otak, telinga, suara, mata)...
python ai_service\setup_models.py --check
if not "%ERRORLEVEL%"=="0" (
    echo   [!] Ada model lokal yang belum siap. Saat masih ada internet, jalankan:
    echo       python ai_service\setup_models.py
    echo       Tanpa model ini, sistem memakai cadangan (suara browser / jawaban cadangan).
)
echo.
echo     Menjalankan AI Service Python (Ollama + Whisper + Piper)...
netstat -ano | findstr /R ":8001 " >nul 2>&1
if "%ERRORLEVEL%"=="0" (
    echo   [OK] AI Service Python sudah aktif di http://127.0.0.1:8001.
) else (
    start "VOIC_AI_SERVICE" /min cmd /c "python ai_service\ai_service.py"
    echo   [OK] Menjalankan AI Service di http://127.0.0.1:8001
)

echo.
echo [4/5] Menjalankan Server Web Laravel (Port 8000)...
start "VOIC_LARAVEL_SERVER" /min cmd /c "php artisan serve --port=8000"
echo   [OK] Laravel aktif di http://localhost:8000

echo.
echo [5/5] Membuka Browser Aplikasi VOIC...
ping 127.0.0.1 -n 4 >nul
start http://localhost:8000/latihan

echo.
echo ================================================================
echo  SEMUA LAYANAN VOIC TELAH BERJALAN!
echo  - Web Application : http://localhost:8000
echo  - Latihan ujian   : http://localhost:8000/latihan
echo  - AI FastAPI      : http://127.0.0.1:8001  (status: /voice/status)
echo  - Ollama LLM      : http://127.0.0.1:11434 (voic-coach GoPrep Engine)
echo.
echo  Pengunjung bisa langsung latihan tanpa akun. Pakai headset agar fitur menyela berfungsi.
echo ================================================================
echo.
echo Tekan tombol apa saja untuk menutup jendela launcher ini...
pause >nul
