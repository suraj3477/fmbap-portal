@echo off
setlocal enabledelayedexpansion
title FMBAP Portal - LAN Server

echo =========================================================
echo       FMBAP PORTAL - LAN PRESENTATION LAUNCHER
echo =========================================================
echo.

:: 1. Detect LAN IP address from active network adapter connected to gateway
for /f "usebackq tokens=*" %%a in (`powershell -NoProfile -Command "(Get-NetIPConfiguration | Where-Object { $_.IPv4DefaultGateway -ne $null } | Select-Object -First 1).IPv4Address.IPAddress"`) do (
    set IP=%%a
)

:: Fallback if PowerShell returns empty
if "%IP%"=="" (
    for /f "tokens=4" %%a in ('route print ^| findstr 0.0.0.0 ^| findstr /v "0.0.0.0.*0.0.0.0.*0.0.0.0"') do (
        set IP=%%a
        goto :found_ip
    )
)

:found_ip
if "%IP%"=="" (
    set IP=10.177.22.41
)

echo [INFO] Detected LAN IP: %IP%

:: 2. Check if Windows Firewall rule exists for Port 8000
netsh advfirewall firewall show rule name="FMBAP Portal LAN (Port 8000)" >nul 2>&1
if %errorlevel% neq 0 (
    echo [NOTICE] Windows Firewall rule for Port 8000 not detected.
    echo [NOTICE] Opening firewall helper. If Windows UAC prompts, please click "Yes"...
    start /wait "" "%~dp0open-firewall-for-lan.bat"
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

:: 5. Ensure .env APP_URL matches LAN IP
powershell -NoProfile -Command "(Get-Content .env) -replace '^APP_URL=.*', 'APP_URL=http://%IP%:8000' | Set-Content .env"

echo.
echo =========================================================
echo   LIVE PRESENTATION URL (Share with LAN / Phone / Laptop):
echo   http://%IP%:8000
echo =========================================================
echo.
echo Requirements for other devices to connect:
echo   1. The other device must be on the SAME Wi-Fi or LAN router.
echo   2. Open a browser and enter: http://%IP%:8000
echo.
echo Server is starting... (Press Ctrl+C to stop)
echo.

php artisan serve --host 0.0.0.0 --port 8000
pause
