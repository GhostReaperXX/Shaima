@echo off
setlocal enabledelayedexpansion
chcp 65001 >nul
title AI Chatbot Server
color 0B
cls

echo ========================================
echo   Starting AI Chatbot Server
echo ========================================
echo.

where python >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo Python is not installed or not in PATH.
    echo Please install Python 3.8+ from https://www.python.org/
    echo.
    pause
    exit /b 1
)

if not exist "venv" (
    echo Creating virtual environment...
    python -m venv venv
)

echo Activating virtual environment...
call venv\Scripts\activate.bat

echo Installing dependencies...
pip install -q -r requirements.txt

echo.
echo ========================================
echo   Chatbot Server Starting
echo ========================================
echo Server: http://localhost:5000
echo API Endpoint: http://localhost:5000/api/chat
echo.
echo Press Ctrl+C to stop
echo ========================================
echo.

python chatbot_api.py





