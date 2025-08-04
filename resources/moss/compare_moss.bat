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
set "FILELIST=files.txt"
del "%FILELIST%" 2>nul

set INCLUDE_DIRS=app\Http\Controllers app\Models resources\views

:: ✅ جمع الملفات من المشروع الأول
for %%i in (%INCLUDE_DIRS%) do (
    set "SCAN_DIR=%PROJ1%\%%i"
    call :scanDir "!SCAN_DIR!"
)

:: ✅ جمع الملفات من المشروع الثاني
for %%i in (%INCLUDE_DIRS%) do (
    set "SCAN_DIR=%PROJ2%\%%i"
    call :scanDir "!SCAN_DIR!"
)

if not exist "%FILELIST%" (
    echo ❌ No student files were collected. Check project structure.
    exit /b
)

:: ✅ تشغيل MOSS باستخدام ascii + m1
set CMD=perl moss.pl -l ascii -m 1 -d
for /f "usebackq delims=" %%i in ("%FILELIST%") do (
    set CMD=!CMD! "%%i"
)

echo 🚀 Running MOSS (student code only)...
%CMD%

if exist "%FILELIST%" del "%FILELIST%"
endlocal
exit /b

:scanDir
set "TARGET=%~1"
if exist "%TARGET%" (
    echo ✅ Scanning folder: %TARGET%
    for /r "%TARGET%" %%f in (*.php *.blade.php *.css *.js) do (
        echo [ADD] %%f
        >>"%FILELIST%" echo %%f
    )
)
exit /b
