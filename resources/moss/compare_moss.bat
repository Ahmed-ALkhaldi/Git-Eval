@echo off
setlocal enabledelayedexpansion

if "%~1"=="" (
    echo Usage: compare_moss "project_1_path" "project_2_path"
    exit /b
)
if "%~2"=="" (
    echo Usage: compare_moss "project_1_path" "project_2_path"
    exit /b
)

set "PROJ1=%~1"
set "PROJ2=%~2"
set "MERGED1=%~dp0merged_project1.php"
set "MERGED2=%~dp0merged_project2.php"
set "RESULT_FILE=%~dp0moss_result.txt"

del "%MERGED1%" 2>nul
del "%MERGED2%" 2>nul
del "%RESULT_FILE%" 2>nul
del "%~dp0moss_output.log" 2>nul

set INCLUDE_DIRS=app\Http\Controllers app\Models resources\views

echo 🔍 [DEBUG] Collecting files from Project 1...
for %%i in (%INCLUDE_DIRS%) do (
    set "SCAN_DIR=%PROJ1%\%%i"
    if exist "!SCAN_DIR!" call :merge "!SCAN_DIR!" "%MERGED1%"
)

echo 🔍 [DEBUG] Collecting files from Project 2...
for %%i in (%INCLUDE_DIRS%) do (
    set "SCAN_DIR=%PROJ2%\%%i"
    if exist "!SCAN_DIR!" call :merge "!SCAN_DIR!" "%MERGED2%"
)

if not exist "%MERGED1%" (
    echo ❌ No files found for Project 1.
    exit /b
)
if not exist "%MERGED2%" (
    echo ❌ No files found for Project 2.
    exit /b
)

echo 🚀 Running MOSS (only key files)...
"C:\Strawberry\perl\bin\perl.exe" "%~dp0moss.pl" -l ascii -m 10 "%MERGED1%" "%MERGED2%" > "%~dp0moss_output.log" 2>&1

:: ✅ البحث عن الرابط داخل الملف
findstr /R "http[s]*://moss" "%~dp0moss_output.log" > "%RESULT_FILE%"

:: ✅ إذا لم يتم العثور على الرابط، انسخ السجل بالكامل للفحص اليدوي
if %ERRORLEVEL% NEQ 0 (
    type "%~dp0moss_output.log" >> "%RESULT_FILE%"
)

type "%RESULT_FILE%"
exit /b

:merge
set "TARGET=%~1"
set "OUTFILE=%~2"
echo ✅ Collecting from: %TARGET%
for /r "%TARGET%" %%f in (*.php *.blade.php *.css *.js) do (
    type "%%f" >> "%OUTFILE%"
    echo.>>"%OUTFILE%"
)
exit /b