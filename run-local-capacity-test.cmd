@echo off
setlocal
title Smart Campus - Local Capacity Test
cd /d "%~dp0"

echo.
echo ============================================================
echo       SMART CAMPUS LOCAL CAPACITY TEST
echo ============================================================
echo.
echo This is a safe LOCAL test. It will not test Hostinger.
echo Make sure Laragon is open and Start All is running.
echo.

set "WAYFINDING_NODE="
for /d %%D in ("C:\laragon\bin\nodejs\node-*") do (
    if exist "%%D\node.exe" set "WAYFINDING_NODE=%%D\node.exe"
)

if not defined WAYFINDING_NODE (
    for /f "delims=" %%N in ('where node.exe 2^>nul') do (
        if not defined WAYFINDING_NODE set "WAYFINDING_NODE=%%N"
    )
)

if not defined WAYFINDING_NODE (
    echo ERROR: Node.js was not found.
    echo Open Laragon, install or enable Node.js, then try again.
    echo.
    pause
    exit /b 1
)

set "WAYFINDING_LOAD_BASE_URL=http://wayfinding.test"
set "WAYFINDING_LOAD_USERS=10"
set "WAYFINDING_LOAD_CONCURRENCY=5"
set "WAYFINDING_LOAD_THINK_MS=100"
set "WAYFINDING_LOAD_SEARCH_QUERY=information technology"
set "WAYFINDING_LOAD_USERS_FILE="
set "WAYFINDING_LOAD_USER_EMAIL=user@gmail.com"
set "WAYFINDING_LOAD_USER_PASSWORD=111"
echo Using the seeded LOCAL test account: user@gmail.com
echo Custom credential files are ignored by this beginner-friendly local test.

echo.
echo Running 10 virtual users with 5 users at the same time...
echo Please wait. Do not close this window.
echo.

"%WAYFINDING_NODE%" "%~dp0scripts\load-test-wayfinding.mjs"
set "WAYFINDING_TEST_EXIT=%ERRORLEVEL%"

echo.
echo ============================================================
if "%WAYFINDING_TEST_EXIT%"=="0" (
    echo TEST FINISHED: No failed requests were detected.
    echo Look for "failed": 0 in the result above.
) else (
    echo TEST FOUND A PROBLEM.
    echo Look above for HTTP 429, 500, 503, timeout, or connection errors.
)
echo ============================================================
echo.
echo You may screenshot this window and send it to Codex for analysis.
echo.
pause
exit /b %WAYFINDING_TEST_EXIT%
