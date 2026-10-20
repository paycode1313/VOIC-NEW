@echo off
setlocal enabledelayedexpansion
cd /d "%~dp0"
title VOIC - Online Cloudflare Tunnel (Live Public Access)
color 0a

echo ================================================================
echo    VOIC - Voice ^& Optics Intelligent Coach (Online Mode)
echo ================================================================
echo.

:: ------------------------------------------------------------------
:: [1/3] AI Services (Ollama & Python FastAPI)
:: ------------------------------------------------------------------
echo [1/3] Memeriksa status AI Local (Ollama ^& FastAPI)...

:: Periksa Ollama
netstat -ano | findstr /R ":11434 " >nul 2>&1
if "%ERRORLEVEL%"=="0" (
    echo   [OK] Ollama Engine sudah aktif di port 11434.
    goto CHECK_PYTHON
)

echo   [..] Menjalankan Ollama di latar belakang...
start "VOIC_OLLAMA" /min cmd /c "ollama serve"

set OLLAMA_WAIT=0
:WAIT_OLLAMA
netstat -ano | findstr /R ":11434 " >nul 2>&1
if "%ERRORLEVEL%"=="0" (
    echo   [OK] Ollama Engine berhasil aktif di port 11434.
    goto CHECK_PYTHON
)
set /a OLLAMA_WAIT+=1
if %OLLAMA_WAIT% gtr 12 (
    echo   [!] Catatan: Ollama butuh waktu untuk inisialisasi, melanjutkan...
    goto CHECK_PYTHON
)
ping 127.0.0.1 -n 2 >nul
goto WAIT_OLLAMA

:CHECK_PYTHON
:: Periksa Python AI Service
netstat -ano | findstr /R ":8001 " >nul 2>&1
if "%ERRORLEVEL%"=="0" (
    echo   [OK] AI Service FastAPI sudah aktif di port 8001.
    goto AI_DONE
)

echo   [..] Menjalankan AI Service Python di latar belakang...
start "VOIC_AI_SERVICE" /min cmd /c "python ai_service\ai_service.py"

set PY_WAIT=0
:WAIT_PY
netstat -ano | findstr /R ":8001 " >nul 2>&1
if "%ERRORLEVEL%"=="0" (
    echo   [OK] AI Service FastAPI berhasil aktif di port 8001.
    goto AI_DONE
)
set /a PY_WAIT+=1
if %PY_WAIT% gtr 15 (
    echo   [!] Catatan: AI Service sedang loading di background pada port 8001.
    goto AI_DONE
)
ping 127.0.0.1 -n 2 >nul
goto WAIT_PY

:AI_DONE
echo.

:: ------------------------------------------------------------------
:: [2/3] Web Server Laravel & Asset Produksi
:: ------------------------------------------------------------------
echo [2/3] Memeriksa status Web Server Laravel ^& Asset Produksi...
if exist public\hot del /f /q public\hot

netstat -ano | findstr /R ":8000 " >nul 2>&1
if "%ERRORLEVEL%"=="0" (
    echo   [OK] Server Laravel aktif di port 8000.
) else (
    echo   [..] Menjalankan Server Laravel di port 8000...
    start "VOIC_LARAVEL" /min cmd /c "php artisan serve --port=8000"
    ping 127.0.0.1 -n 3 >nul
    echo   [OK] Server Laravel aktif di port 8000.
)
echo.

:: ------------------------------------------------------------------
:: [3/3] Cloudflare Tunnel
:: ------------------------------------------------------------------
echo [3/3] Menghubungkan ke Cloudflare Tunnel (Membuka Link Publik HTTPS)...
echo ----------------------------------------------------------------------
echo  Sedang menghubungkan ke Cloudflare Network (mohon tunggu 5-10 detik)...
echo  Link publik HTTPS Anda (https://xxxx.trycloudflare.com) akan muncul di bawah:
echo  (Salin link tersebut untuk dibagikan ke dosen, juri, atau penguji)
echo  (PENTING: Jangan tutup jendela ini agar website tetap online!)
echo ----------------------------------------------------------------------
echo.

set CLOUDFLARED_BIN=c:\laragon\bin\cloudflared.exe
if not exist "%CLOUDFLARED_BIN%" (
    where cloudflared.exe >nul 2>&1
    if "%ERRORLEVEL%"=="0" (
        set CLOUDFLARED_BIN=cloudflared.exe
    ) else (
        echo [ERROR] cloudflared.exe tidak ditemukan di c:\laragon\bin maupun PATH sistem.
        pause
        exit /b 1
    )
)

"%CLOUDFLARED_BIN%" tunnel --url http://localhost:8000

echo.
echo ================================================================
echo  Koneksi Cloudflare Tunnel telah dihentikan.
echo ================================================================
pause
