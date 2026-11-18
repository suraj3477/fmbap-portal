@echo off
title Deploy FMBAP Portal to Ubuntu VM
color 0b

echo =================================================================
echo       FMBAP PORTAL - AUTOMATED VM DEPLOYMENT LAUNCHER
echo       Target VM: 10.103.79.44 (Ubuntu 24.04)
echo =================================================================
echo.

:: 1. Check network connectivity to VM
echo [1/3] Testing network connectivity to 10.103.79.44:22...
powershell -NoProfile -Command "if (!(Test-NetConnection -ComputerName 10.103.79.44 -Port 22 -InformationLevel Quiet)) { exit 1 }"
if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Cannot reach 10.103.79.44 on Port 22!
    echo.
    echo Please make sure:
    echo   1. Your VPN (Cisco AnyConnect or NCP Secure Client) is CONNECTED, OR
    echo   2. Your office LAN / Ethernet cable is connected and enabled.
    echo.
    pause
    exit /b 1
)

echo [OK] VM is reachable over Port 22!
echo.
echo [2/3] Connecting and triggering automated deployment on the VM...
echo Please wait, this will install Nginx, PHP 8.3, Node.js, clone the repo, and build assets...
echo.

"C:\Program Files\PuTTY\plink.exe" -ssh -batch -l ipmcloud -pw "IPMCloud123#" 10.103.79.44 "curl -sSL https://raw.githubusercontent.com/suraj3477/fmbap-portal/main/deploy-vm.sh | sudo bash"

if %errorlevel% equ 0 (
    echo.
    echo =================================================================
    echo   DEPLOYMENT FINISHED SUCCESSFULLY!
    echo   You can now open: http://10.103.79.44 in your browser.
    echo =================================================================
) else (
    echo.
    echo [WARNING] Script exited with code %errorlevel%.
)

echo.
pause
