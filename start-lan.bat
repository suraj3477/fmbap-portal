@echo off
setlocal enabledelayedexpansion
title FMBAP Portal - LAN Server

echo =========================================================
echo       FMBAP PORTAL - LAN PRESENTATION LAUNCHER
echo =========================================================
echo.

:: Detect LAN IP address from active route
for /f "tokens=4" %%a in ('route print ^| findstr 0.0.0.0 ^| findstr /v "0.0.0.0.*0.0.0.0.*0.0.0.0"') do (
    set IP=%%a
    goto :found_ip
)

:: Fallback
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address"') do (
    set IP=%%a
    set IP=!IP: =!
    goto :found_ip
)

:found_ip
if "%IP%"=="" (
    set IP=10.177.22.41
)

echo [INFO] Detected LAN IP: %IP%

:: Remove stale public/hot so compiled production bundle is used cleanly
if exist "public\hot" (
    echo [INFO] Removing stale public\hot...
    del /f /q "public\hot" >nul 2>&1
)

:: Ensure .env APP_URL matches LAN IP
powershell -NoProfile -Command "(Get-Content .env) -replace '^APP_URL=.*', 'APP_URL=http://%IP%:8000' | Set-Content .env"

echo.
echo =========================================================
echo   LIVE PRESENTATION URL (Share with LAN / Phone / Laptop):
echo   http://%IP%:8000
echo =========================================================
echo.
echo Server is starting... (Press Ctrl+C to stop)
echo.

php artisan serve --host 0.0.0.0 --port 8000
pause
