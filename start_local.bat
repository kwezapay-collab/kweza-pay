@echo off
TITLE Kweza Pay Local Setup

echo ===========================================
echo       Kweza Pay - Local Setup Script
echo ===========================================
echo.

:: 1. Check PHP
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] PHP is not installed or not in your PATH.
    echo Please install PHP before continuing.
    pause
    exit /b
)
echo [OK] PHP is installed.
echo.

:: 2. Create Directories
echo [STEP 1] Creating Upload Directories...
php setup_directories.php
echo.

:: 3. Initialize Database
set /p initdb="[STEP 2] Do you want to initialize/reset the database? (y/n): "
if /i "%initdb%"=="y" (
    echo.
    echo Initializing database...
    cd backend
    php init_db.php
    cd ..
    echo.
) else (
    echo Skipping database initialization.
    echo.
)

:: 4. Run Migrations
echo [STEP 3] Running Database Migrations...
php run_migration.php
echo.

:: 5. Start Server
echo [STEP 4] Starting Local Server...
echo.
echo The application will be available at: http://localhost:8000/frontend/index.php
echo Press Ctrl+C to stop the server.
echo.
php -S localhost:8000
