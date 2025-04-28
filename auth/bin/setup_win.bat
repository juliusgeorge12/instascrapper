@echo off
REM Set the path to the chromedriver
set CHROME_DRIVER_PATH=%~dp0chromedriver-win64\chromedriver.exe

REM Set the port and any other configuration (like environment variables, etc.)
set PORT=9515

REM Start ChromeDriver
echo Starting ChromeDriver on port %PORT%...
start "" "%CHROME_DRIVER_PATH%" --port=%PORT%

REM Wait for ChromeDriver to start up
timeout /t 5 /nobreak

REM Check if ChromeDriver is running
tasklist /FI "IMAGENAME eq chromedriver.exe" 2>NUL | find /I "chromedriver.exe" >NUL
if %ERRORLEVEL% equ 0 (
    echo ChromeDriver started successfully.
) else (
    echo Failed to start ChromeDriver.
)

pause
