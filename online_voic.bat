@echo off
title VOIC - Online Cloudflare Tunnel (Live Public Access)
color 0a

echo ================================================================
echo    VOIC - Voice ^& Optics Intelligent Coach (Online Mode)
echo ================================================================
echo.
echo [1/3] Memeriksa status AI Local (Ollama ^& FastAPI)...
netstat -ano | findstr /R ":8001 " >nul 2>&1
if "%ERRORLEVEL%"=="0" (
    echo   [OK] AI Service FastAPI aktif di port 8001.
) else (
    echo   [..] Menjalankan AI Service Python di latar belakang...
    start "VOIC_AI_SERVICE" /min cmd /c "python ai_service\ai_service.py"
)

echo.
echo [2/3] Memeriksa status Web Server & Asset Produksi...
if exist public\hot del /f /q public\hot
netstat -ano | findstr /R ":80 " >nul 2>&1
if "%ERRORLEVEL%"=="0" (
    echo   [OK] Laragon Web Server aktif di port 80.
) else (
    echo   [..] Menjalankan server Laravel di port 8000...
    start "VOIC_LARAVEL" /min cmd /c "php artisan serve --port=8000"
)

echo.
echo [3/3] Menghubungkan ke Cloudflare Tunnel (Membuka Link Publik HTTPS)...
echo ----------------------------------------------------------------
echo  Link publik HTTPS Anda akan muncul di bawah ini dalam beberapa detik:
echo  (Salin link tersebut untuk dibagikan ke dosen, juri, atau penguji)
echo  (Tekan Ctrl+C untuk menutup akses online)
echo ----------------------------------------------------------------
echo.

c:\laragon\bin\cloudflared.exe tunnel --url http://localhost:80 --http-host-header voic-new.test
pause
