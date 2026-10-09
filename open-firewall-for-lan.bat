@echo off
title Configure Windows Firewall for LAN Access

:: Check for Administrator permissions
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [INFO] Requesting Administrator permission to configure Windows Firewall...
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process cmd.exe -ArgumentList '/k \"\"%~f0\"\"' -Verb RunAs"
    exit /b
)

echo =================================================================
echo       FMBAP PORTAL - WINDOWS FIREWALL CONFIGURATION
echo =================================================================
echo.
echo Allowing inbound connections through Windows Firewall...
echo.

:: 1. Add TCP Port 8000 rule (Laravel Web Server)
netsh advfirewall firewall delete rule name="FMBAP Portal LAN (Port 8000)" >nul 2>&1
netsh advfirewall firewall add rule name="FMBAP Portal LAN (Port 8000)" dir=in action=allow protocol=TCP localport=8000 profile=any >nul
if %errorlevel% equ 0 (
    echo  [OK] Inbound TCP Port 8000 (Laravel Web Server) allowed.
) else (
    echo  [ERROR] Failed to allow Port 8000.
)

:: 2. Add TCP Port 5173 rule (Vite Dev Server)
netsh advfirewall firewall delete rule name="FMBAP Portal Vite (Port 5173)" >nul 2>&1
netsh advfirewall firewall add rule name="FMBAP Portal Vite (Port 5173)" dir=in action=allow protocol=TCP localport=5173 profile=any >nul
if %errorlevel% equ 0 (
    echo  [OK] Inbound TCP Port 5173 (Vite Dev Server) allowed.
) else (
    echo  [ERROR] Failed to allow Port 5173.
)

:: 3. Add PHP Executable rule
if exist "C:\xampp\php\php.exe" (
    netsh advfirewall firewall delete rule name="FMBAP Portal PHP Server" >nul 2>&1
    netsh advfirewall firewall add rule name="FMBAP Portal PHP Server" dir=in action=allow program="C:\xampp\php\php.exe" enable=yes profile=any >nul
    if %errorlevel% equ 0 (
        echo  [OK] PHP Executable (C:\xampp\php\php.exe) allowed.
    )
)

echo.
echo =================================================================
echo   SUCCESS! Windows Firewall is now configured for LAN access.
echo   Other devices on the same Wi-Fi / LAN can now connect.
echo =================================================================
echo.
pause
