@echo off
setlocal enabledelayedexpansion
chcp 65001 >nul 2>&1
title Academy Platform - Auto Startup
color 0A
cls

REM ========================================
REM   Academy Platform - Complete Auto Startup
REM   Handles everything automatically!
REM ========================================

echo.
echo ========================================
echo   Academy Platform - Auto Startup
echo ========================================
echo.
echo [INFO] Initializing system...
echo.

REM ========================================
REM   Step 1: Find PHP
REM ========================================
echo [STEP 1/7] Locating PHP...
set "PHP_PATH="
set "PHP_FOUND=0"

REM Check common locations
if exist "C:\xampp\php\php.exe" (
    set "PHP_PATH=C:\xampp\php\php.exe"
    set "PHP_FOUND=1"
    goto :found_php
)

if exist "C:\wamp64\bin\php" (
    for /d %%i in (C:\wamp64\bin\php\php*) do (
        if exist "%%i\php.exe" (
            set "PHP_PATH=%%i\php.exe"
            set "PHP_FOUND=1"
            goto :found_php
        )
    )
)

if exist "C:\laragon\bin\php" (
    for /d %%i in (C:\laragon\bin\php\php*) do (
        if exist "%%i\php.exe" (
            set "PHP_PATH=%%i\php.exe"
            set "PHP_FOUND=1"
            goto :found_php
        )
    )
)

REM Check if PHP is in PATH
where php >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    php -v >nul 2>&1
    if %ERRORLEVEL% EQU 0 (
        set "PHP_PATH=php"
        set "PHP_FOUND=1"
        goto :found_php
    )
)

:found_php
if !PHP_FOUND! EQU 0 (
    echo [ERROR] PHP not found!
    echo [INFO] Please install XAMPP, WAMP, or Laragon
    pause
    exit /b 1
)

echo [OK] PHP found: %PHP_PATH%
REM Get PHP version - simplified method
for /f "tokens=2" %%i in ('"%PHP_PATH%" -r "echo PHP_VERSION;" 2^>nul') do set "PHP_VERSION=%%i"
if not defined PHP_VERSION (
    for /f "tokens=2 delims= " %%i in ('"%PHP_PATH%" -v 2^>nul ^| findstr /i "PHP"') do (
        set "PHP_VERSION=%%i"
        goto :php_ver_done
    )
)
:php_ver_done
if not defined PHP_VERSION set "PHP_VERSION=Unknown"
echo [INFO] PHP Version: !PHP_VERSION!
echo.

REM ========================================
REM   Step 2: Find MySQL
REM ========================================
echo [STEP 2/7] Locating MySQL...
set "MYSQL_CMD="
set "MYSQL_FOUND=0"

if exist "C:\xampp\mysql\bin\mysql.exe" (
    set "MYSQL_CMD=C:\xampp\mysql\bin\mysql.exe"
    set "MYSQLD_CMD=C:\xampp\mysql\bin\mysqld.exe"
    set "MYSQL_FOUND=1"
    goto :found_mysql
)

if exist "C:\wamp64\bin\mysql" (
    for /d %%i in (C:\wamp64\bin\mysql\mysql*) do (
        if exist "%%i\bin\mysql.exe" (
            set "MYSQL_CMD=%%i\bin\mysql.exe"
            set "MYSQLD_CMD=%%i\bin\mysqld.exe"
            set "MYSQL_FOUND=1"
            goto :found_mysql
        )
    )
)

where mysql >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    set "MYSQL_CMD=mysql"
    set "MYSQL_FOUND=1"
    goto :found_mysql
)

:found_mysql
if !MYSQL_FOUND! EQU 0 (
    echo [WARNING] MySQL client not found in standard locations
    set "MYSQL_CMD=mysql"
) else (
    echo [OK] MySQL found: %MYSQL_CMD%
)
echo.

REM ========================================
REM   Step 3: Start MySQL Service
REM ========================================
echo [STEP 3/7] Starting MySQL...
set "MYSQL_PASS=1234"
set "MYSQL_READY=0"

REM Check if MySQL is already running
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if %ERRORLEVEL% EQU 0 (
    echo [INFO] MySQL process detected, checking connection...
    REM Test connection with password
    "%MYSQL_CMD%" -u root -p%MYSQL_PASS% -e "SELECT 1;" >nul 2>&1
    if %ERRORLEVEL% EQU 0 (
        set "MYSQL_READY=1"
        echo [OK] MySQL is running and accessible!
        goto :mysql_ready
    )
    REM Try without password (no -p flag)
    "%MYSQL_CMD%" -u root -e "SELECT 1;" >nul 2>&1
    if %ERRORLEVEL% EQU 0 (
        echo [INFO] MySQL running without password, will set password...
        set "MYSQL_READY=1"
        set "MYSQL_PASS="
        goto :mysql_ready
    )
)

REM Try to start MySQL service
sc query MySQL >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo [INFO] Starting MySQL service...
    net start MySQL >nul 2>&1
    if %ERRORLEVEL% EQU 0 (
        echo [OK] MySQL service started!
        timeout /t 3 /nobreak >nul 2>&1
        set "MYSQL_READY=1"
        goto :mysql_ready
    )
)

REM Try to start MySQL directly
if defined MYSQLD_CMD (
    if exist "!MYSQLD_CMD!" (
        echo [INFO] Starting MySQL server directly...
        start /B "" "!MYSQLD_CMD!" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone --console >nul 2>&1
        echo [INFO] Waiting for MySQL to initialize (up to 30 seconds)...
        set "RETRY_COUNT=0"
        :wait_mysql_start
        timeout /t 2 /nobreak >nul 2>&1
        set /a RETRY_COUNT+=1
        REM Try without password first (XAMPP default)
        "%MYSQL_CMD%" -u root -e "SELECT 1;" >nul 2>&1
        if %ERRORLEVEL% EQU 0 (
            set "MYSQL_READY=1"
            set "MYSQL_PASS="
            echo [OK] MySQL is ready!
            goto :mysql_ready
        )
        REM Try with password
        "%MYSQL_CMD%" -u root -p%MYSQL_PASS% -e "SELECT 1;" >nul 2>&1
        if %ERRORLEVEL% EQU 0 (
            set "MYSQL_READY=1"
            echo [OK] MySQL is ready!
            goto :mysql_ready
        )
        if !RETRY_COUNT! LSS 15 (
            goto :wait_mysql_start
        )
        echo [WARNING] MySQL did not become ready, but continuing...
    )
)

:mysql_ready
if !MYSQL_READY! EQU 0 (
    echo [WARNING] Could not verify MySQL connection
    echo [INFO] Will attempt to configure MySQL password...
)

REM ========================================
REM   Step 4: Reset MySQL Root Password
REM ========================================
echo [STEP 4/7] Configuring MySQL root password...
set "PASSWORD_SET=0"

REM Verify password works (only if MYSQL_PASS is set)
if defined MYSQL_PASS (
    if not "!MYSQL_PASS!"=="" (
        "%MYSQL_CMD%" -u root -p%MYSQL_PASS% -e "SELECT 1;" >nul 2>&1
        if %ERRORLEVEL% EQU 0 (
            set "PASSWORD_SET=1"
            echo [OK] MySQL root password is configured!
            goto :password_done
        )
    )
)

REM Try without password
"%MYSQL_CMD%" -u root -e "SELECT 1;" >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo [INFO] MySQL accessible without password
    echo [INFO] Password will be set during database initialization...
    set "MYSQL_PASS="
    set "PASSWORD_SET=1"
) else (
    echo [WARNING] Could not verify MySQL connection, but continuing...
)

:password_done
echo.

REM ========================================
REM   Step 5: Initialize Database
REM ========================================
echo [STEP 5/7] Initializing database...
echo [INFO] Running database setup...

if exist "init_db.php" (
    "%PHP_PATH%" init_db.php >nul 2>&1
    if %ERRORLEVEL% EQU 0 (
        echo [OK] Database initialized successfully!
    ) else (
        echo [WARNING] Database initialization had issues, but continuing...
    )
) else (
    echo [WARNING] init_db.php not found, skipping database initialization...
)
echo.

REM ========================================
REM   Step 6: Verify Database Connection
REM ========================================
echo [STEP 6/7] Verifying database connection...
"%PHP_PATH%" test_db_connection.php >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo [OK] Database connection verified!
) else (
    echo [WARNING] Database connection test failed, but server will start anyway...
)
echo.

REM ========================================
REM   Step 7: Create Required Directories
REM ========================================
echo [STEP 7/7] Setting up directories...
if not exist "tmp" mkdir tmp >nul 2>&1
if not exist "uploads" mkdir uploads >nul 2>&1
if not exist "uploads\finance" mkdir "uploads\finance" >nul 2>&1
if not exist "tmp\mpdf" mkdir "tmp\mpdf" >nul 2>&1
echo [OK] Directories ready
echo.

REM ========================================
REM   Start PHP Server
REM ========================================
echo ========================================
echo   Server Information
echo ========================================
echo PHP Path: %PHP_PATH%
echo PHP Version: !PHP_VERSION!
echo Server URL: http://localhost:8000
echo Document Root: %CD%
if !MYSQL_READY! EQU 1 (
    echo MySQL Status: Running and Ready
) else (
    echo MySQL Status: Will retry on page load
)
echo.
echo ========================================
echo   Starting Server...
echo ========================================
echo.
echo [INFO] Server starting on http://localhost:8000
echo [INFO] Press Ctrl+C to stop the server
echo.

REM Wait a moment before opening browser
timeout /t 2 /nobreak >nul 2>&1

REM Open browser
start "" "http://localhost:8000" >nul 2>&1

REM Start PHP server
"%PHP_PATH%" -S localhost:8000 -t "%CD%" -d display_errors=1 -d error_reporting=E_ALL -d log_errors=1 -d error_log=php_errors.log

REM If we get here, server stopped
echo.
echo ========================================
echo   Server Stopped
echo ========================================
echo.
pause
exit /b 0
