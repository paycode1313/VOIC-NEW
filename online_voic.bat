@echo off
setlocal
cd /d "%~dp0"
title VOIC - Online Cloudflare Tunnel (Live Public Access)
color 0a

echo ================================================================
echo    VOIC - Voice ^& Optics Intelligent Coach (Online Mode)
echo ================================================================
echo.
echo [1/3] Memeriksa status AI Local (Ollama ^& FastAPI)...
netstat -ano | findstr /R ":11434 " >nul 2>&1
if "%ERRORLEVEL%"=="0" (
    echo   [OK] Ollama sudah aktif di port 11434.
) else (
    tasklist /FI "IMAGENAME eq ollama.exe" 2>NUL | find /I /N "ollama.exe">NUL
    if "%ERRORLEVEL%"=="0" (
        echo   [OK] Ollama sudah aktif di latar belakang.
    ) else (
        echo   [..] Menjalankan Ollama di latar belakang...
        start "VOIC_OLLAMA" /min cmd /c "ollama serve"
    )
)

netstat -ano | findstr /R ":8001 " >nul 2>&1
if "%ERRORLEVEL%"=="0" (
    echo   [OK] AI Service FastAPI aktif di port 8001.
) else (
    echo   [..] Menjalankan AI Service Python di latar belakang...
    start "VOIC_AI_SERVICE" /min cmd /c "python ai_service\ai_service.py"
)

echo.
echo [2/3] Memeriksa status Web Server ^& Asset Produksi...
if exist public\hot del /f /q public\hot
netstat -ano | findstr /R ":80 " >nul 2>&1
if "%ERRORLEVEL%"=="0" goto RUN_PORT_80
goto RUN_PORT_8000

:RUN_PORT_80
echo   [OK] Laragon Web Server aktif di port 80.
echo.
echo [3/3] Menghubungkan ke Cloudflare Tunnel (Membuka Link Publik HTTPS)...
echo ----------------------------------------------------------------
echo  Link publik HTTPS Anda akan muncul di bawah ini dalam beberapa detik:
echo  (Salin link tersebut untuk dibagikan ke dosen, juri, atau penguji)
echo  (PENTING: Jangan tutup jendela ini agar website tetap online!)
echo ----------------------------------------------------------------
echo.
c:\laragon\bin\cloudflared.exe tunnel --url http://localhost:80 --http-host-header voic-new.test
goto FINISH

:RUN_PORT_8000
echo   [!] Laragon Port 80 tidak aktif. Menjalankan fallback Laravel di port 8000...
start "VOIC_LARAVEL" /min cmd /c "php artisan serve --port=8000"
timeout /t 2 >nul
echo.
echo [3/3] Menghubungkan ke Cloudflare Tunnel via port 8000...
echo ----------------------------------------------------------------
echo  Link publik HTTPS Anda akan muncul di bawah ini dalam beberapa detik:
echo  (PENTING: Jangan tutup jendela ini agar website tetap online!)
echo ----------------------------------------------------------------
echo.
c:\laragon\bin\cloudflared.exe tunnel --url http://localhost:8000
goto FINISH

:FINISH
pause



