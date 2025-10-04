@echo off
REM SonarQube Analysis Batch Script
REM Usage: sonar_analyze.bat PROJECT_DIR PROJECT_KEY SONAR_HOST SONAR_TOKEN SCANNER_BIN JAVA_HOME TIMEOUT

setlocal

REM Parse arguments
set PROJECT_DIR=%~1
set PROJECT_KEY=%~2
set SONAR_HOST=%~3
set SONAR_TOKEN=%~4
set SCANNER_BIN=%~5
set JAVA_HOME_ARG=%~6
set TIMEOUT_ARG=%~7

REM Validate required arguments
if "%PROJECT_DIR%"=="" (
    echo ERROR: PROJECT_DIR is required
    exit /b 1
)
if "%PROJECT_KEY%"=="" (
    echo ERROR: PROJECT_KEY is required
    exit /b 1
)
if "%SONAR_HOST%"=="" (
    echo ERROR: SONAR_HOST is required
    exit /b 1
)
if "%SONAR_TOKEN%"=="" (
    echo ERROR: SONAR_TOKEN is required
    exit /b 1
)
if "%SCANNER_BIN%"=="" (
    echo ERROR: SCANNER_BIN is required
    exit /b 1
)

REM Set default values
if "%JAVA_HOME_ARG%"=="" set JAVA_HOME_ARG=C:\Program Files\Java\jdk-17
if "%TIMEOUT_ARG%"=="" set TIMEOUT_ARG=600

echo [INFO] Starting SonarQube Analysis...
echo [INFO] Project Directory: %PROJECT_DIR%
echo [INFO] Project Key: %PROJECT_KEY%
echo [INFO] SonarQube Host: %SONAR_HOST%
echo [INFO] Scanner Binary: %SCANNER_BIN%
echo [INFO] Java Home: %JAVA_HOME_ARG%
echo [INFO] Timeout: %TIMEOUT_ARG%s

REM Check if project directory exists
if not exist "%PROJECT_DIR%" (
    echo ERROR: Project directory does not exist: %PROJECT_DIR%
    exit /b 1
)

REM Check if scanner binary exists
if not exist "%SCANNER_BIN%" (
    echo ERROR: Scanner binary does not exist: %SCANNER_BIN%
    exit /b 1
)

REM Set environment variables for Windows proxy bypass
set NO_PROXY=localhost,127.0.0.1
set no_proxy=localhost,127.0.0.1

REM Ensure temp directories are set to user temp (not system temp)
set TEMP=%USERPROFILE%\AppData\Local\Temp
set TMP=%USERPROFILE%\AppData\Local\Temp

REM Create temp directory if it doesn't exist
if not exist "%TEMP%" mkdir "%TEMP%"

REM Set SONAR_SCANNER_OPTS for better performance and Windows compatibility (Java 8+)
REM Removed -XX:MaxPermSize as it's not supported in Java 8+
REM Set temp directory to user temp to avoid Windows permission issues
REM Use fully qualified path for tmpdir to avoid Windows socket issues
set SONAR_SCANNER_OPTS=-Xmx2048m -Xms512m -Djava.net.useSystemProxies=false -Dfile.encoding=UTF-8 -Djava.io.tmpdir=%USERPROFILE%\AppData\Local\Temp

REM Backup original PATH and JAVA_HOME
set ORIGINAL_PATH=%PATH%
set ORIGINAL_JAVA_HOME=%JAVA_HOME%

REM Set JAVA_HOME and update PATH temporarily
set JAVA_HOME=%JAVA_HOME_ARG%
set PATH=%JAVA_HOME%\bin;%PATH%

echo [INFO] Environment configured successfully
echo [INFO] Using Java from: %JAVA_HOME%

REM Change to project directory
cd /d "%PROJECT_DIR%"
if errorlevel 1 (
    echo ERROR: Failed to change to project directory: %PROJECT_DIR%
    exit /b 1
)

REM Create sonar-project.properties file
echo [INFO] Creating sonar-project.properties...
(
echo sonar.projectKey=%PROJECT_KEY%
echo sonar.projectName=%PROJECT_KEY%
echo sonar.projectVersion=1.0
echo sonar.sources=.
echo sonar.sourceEncoding=UTF-8
echo sonar.inclusions=**/*.php
echo sonar.exclusions=vendor/**,node_modules/**,storage/**,bootstrap/**,public/**,tests/**
echo sonar.scm.disabled=true
echo sonar.host.url=%SONAR_HOST%
echo sonar.connectionTimeout=60000
echo sonar.socketTimeout=60000
) > sonar-project.properties

if errorlevel 1 (
    echo ERROR: Failed to create sonar-project.properties
    exit /b 1
)

echo [INFO] sonar-project.properties created successfully

REM Run SonarQube Scanner
echo [INFO] Running SonarQube Scanner...
"%SCANNER_BIN%" -D sonar.host.url=%SONAR_HOST% -D sonar.token=%SONAR_TOKEN%

set SCANNER_EXIT_CODE=%errorlevel%

REM Clean up sonar-project.properties
if exist "sonar-project.properties" (
    del "sonar-project.properties"
    echo [INFO] Cleaned up sonar-project.properties
)

REM Restore original environment
set PATH=%ORIGINAL_PATH%
set JAVA_HOME=%ORIGINAL_JAVA_HOME%

REM Check scanner result
if %SCANNER_EXIT_CODE% neq 0 (
    echo ERROR: SonarQube Scanner failed with exit code %SCANNER_EXIT_CODE%
    exit /b %SCANNER_EXIT_CODE%
)

echo [SUCCESS] SonarQube Analysis completed successfully!
exit /b 0