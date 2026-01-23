@echo off
echo Resetting passwords to 1234@1234...
echo.

REM Try to find PHP
set "PHP_PATH="
if exist "C:\xampp\php\php.exe" (
    set "PHP_PATH=C:\xampp\php\php.exe"
) else if exist "C:\wamp64\bin\php" (
    for /d %%i in (C:\wamp64\bin\php\php*) do (
        if exist "%%i\php.exe" (
            set "PHP_PATH=%%i\php.exe"
            goto :found_php
        )
    )
) else if exist "C:\laragon\bin\php" (
    for /d %%i in (C:\laragon\bin\php\php*) do (
        if exist "%%i\php.exe" (
            set "PHP_PATH=%%i\php.exe"
            goto :found_php
        )
    )
)

:found_php
if defined PHP_PATH (
    "%PHP_PATH%" reset_passwords.php
) else (
    echo ERROR: PHP not found. Please run: php reset_passwords.php
    pause
)

pause
