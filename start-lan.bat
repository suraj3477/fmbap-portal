@echo off
setlocal enabledelayedexpansion
title FMBAP Portal - LAN Server

echo =========================================================
echo       FMBAP PORTAL - LAN PRESENTATION LAUNCHER
echo =========================================================
echo.

:: 1. Detect Primary LAN IP
set PRIMARY_IP=
for /f "delims=" %%a in ('powershell -NoProfile -Command "(Get-NetIPConfiguration | Where-Object IPv4DefaultGateway | Select-Object -ExpandProperty IPv4Address | Select-Object -ExpandProperty IPAddress)"') do (
    if "!PRIMARY_IP!"=="" set PRIMARY_IP=%%a
)

if "%PRIMARY_IP%"=="" set PRIMARY_IP=10.177.22.41
echo [INFO] Detected Primary LAN IP: %PRIMARY_IP%

:: 2. Free Port 8000 if an old PHP process is still running
for /f "tokens=5" %%p in ('netstat -aon ^| findstr ":8000" ^| findstr "LISTENING"') do (
    echo [INFO] Freeing Port 8000 (PID: %%p)...
    taskkill /F /PID %%p >nul 2>&1
)

:: 3. Remove stale public/hot so compiled production bundle is used cleanly over LAN
if exist "public\hot" (
    echo [INFO] Removing stale public\hot...
    del /f /q "public\hot" >nul 2>&1
)

:: 4. Verify compiled assets exist
if not exist "public\build\manifest.json" (
    echo [INFO] Compiling front-end assets for production...
    call npm run build
)

:: 5. Update .env APP_URL to match detected LAN IP
powershell -NoProfile -Command "(Get-Content .env) -replace '^APP_URL=.*', 'APP_URL=http://%PRIMARY_IP%:8000' | Set-Content .env"

echo.
echo =========================================================
echo   LIVE PRESENTATION URLS (Open on Phone / Laptop / Tablet):
powershell -NoProfile -Command "Get-NetIPConfiguration | Where-Object IPv4DefaultGateway | ForEach-Object { Write-Host ('   * ' + $PSItem.InterfaceAlias + ': http://' + $PSItem.IPv4Address.IPAddress + ':8000') -ForegroundColor Green }"
echo =========================================================
echo.
echo Requirements to access from another device:
echo   1. The phone or laptop must be on the SAME Wi-Fi router or office LAN.
echo   2. Open Chrome/Safari/Edge on that device and go to:
echo        - If on Wi-Fi:    http://192.168.1.190:8000
echo        - If on Ethernet: http://10.177.22.41:8000
echo.
echo Server is running on all interfaces (0.0.0.0:8000)...
echo Press Ctrl+C to stop.
echo.

php artisan serve --host 0.0.0.0 --port 8000
pause
