@echo off
REM =============================================================================
REM INSTALADOR QR MANAGER PARA WINDOWS + XAMPP
REM Instalación automática y configuración
REM =============================================================================

title QR Manager - Instalador Windows
color 0A
cls

echo.
echo  ==========================================
echo  ^|       QR MANAGER INSTALLER            ^|
echo  ^|       Windows + XAMPP                 ^|
echo  ==========================================
echo.

REM Verificar que estamos en el directorio correcto
if not exist "config.php" (
    echo [ERROR] No se encuentra config.php
    echo.
    echo Ejecuta este instalador desde el directorio qr-manager
    echo Ejemplo: C:\xampp\htdocs\qr-manager\
    echo.
    pause
    exit /b 1
)

echo [INFO] Directorio verificado correctamente
echo.

REM =============================================================================
REM DETECTAR XAMPP
REM =============================================================================

echo [1/6] Detectando XAMPP...

set XAMPP_PATH=
set PHP_PATH=

REM Buscar XAMPP en ubicaciones comunes
for %%i in (C:\xampp D:\xampp E:\xampp C:\Server\xampp) do (
    if exist "%%i\php\php.exe" (
        set XAMPP_PATH=%%i
        set PHP_PATH=%%i\php\php.exe
        goto :xampp_found
    )
)

REM No se encontró XAMPP
echo [ERROR] XAMPP no encontrado
echo.
echo Instala XAMPP desde: https://www.apachefriends.org/
echo Ubicaciones verificadas:
echo - C:\xampp
echo - D:\xampp  
echo - E:\xampp
echo - C:\Server\xampp
echo.
pause
exit /b 1

:xampp_found
echo [OK] XAMPP encontrado en: %XAMPP_PATH%
echo [OK] PHP encontrado en: %PHP_PATH%
echo.

REM =============================================================================
REM VERIFICAR PHP
REM =============================================================================

echo [2/6] Verificando PHP...

REM Verificar versión PHP
for /f "tokens=2 delims= " %%i in ('"%PHP_PATH%" -v 2^>nul ^| findstr /R "PHP [0-9]"') do (
    set PHP_VERSION=%%i
)

if "%PHP_VERSION%"=="" (
    echo [ERROR] No se pudo determinar la versión de PHP
    pause
    exit /b 1
)

echo [OK] PHP Version: %PHP_VERSION%

REM Verificar extensiones
echo [INFO] Verificando extensiones PHP...
"%PHP_PATH%" -m | findstr /i "json" >nul
if %errorlevel% neq 0 (
    echo [WARNING] Extensión JSON no detectada
)

"%PHP_PATH%" -m | findstr /i "curl" >nul
if %errorlevel% neq 0 (
    echo [WARNING] Extensión CURL no detectada
)

"%PHP_PATH%" -m | findstr /i "gd" >nul
if %errorlevel% neq 0 (
    echo [WARNING] Extensión GD no detectada
)

echo.

REM =============================================================================
REM VERIFICAR APACHE
REM =============================================================================

echo [3/6] Verificando Apache...

REM Verificar si Apache está ejecutándose
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe" >nul
if %errorlevel% equ 0 (
    echo [OK] Apache está ejecutándose
) else (
    echo [WARNING] Apache no está ejecutándose
    echo [INFO] Inicia Apache desde XAMPP Control Panel
)

REM Verificar mod_rewrite
if exist "%XAMPP_PATH%\apache\conf\httpd.conf" (
    findstr /C:"LoadModule rewrite_module" "%XAMPP_PATH%\apache\conf\httpd.conf" | findstr /V "#" >nul
    if %errorlevel% equ 0 (
        echo [OK] mod_rewrite está habilitado
        set USE_MOD_REWRITE=1
    ) else (
        echo [WARNING] mod_rewrite no está habilitado
        set USE_MOD_REWRITE=0
    )
)

echo.

REM =============================================================================
REM CREAR ESTRUCTURA DE DIRECTORIOS
REM =============================================================================

echo [4/6] Creando estructura de directorios...

if not exist "qr" (
    mkdir "qr"
    echo [OK] Directorio qr/ creado
) else (
    echo [OK] Directorio qr/ ya existe
)

if not exist "logs" (
    mkdir "logs"
    echo [OK] Directorio logs/ creado
) else (
    echo [OK] Directorio logs/ ya existe
)

echo.

REM =============================================================================
REM CONFIGURAR APLICACIÓN
REM =============================================================================

echo [5/6] Configurando aplicación...

REM Crear archivo de configuración de mod_rewrite
if %USE_MOD_REWRITE% equ 1 (
    echo enabled > .mod_rewrite_config
    echo [OK] Configurado para usar mod_rewrite
) else (
    echo disabled > .mod_rewrite_config
    echo [OK] Configurado para funcionar sin mod_rewrite
)

REM Crear .htaccess apropiado
if %USE_MOD_REWRITE% equ 0 (
    echo [INFO] Creando .htaccess sin mod_rewrite...
    (
        echo # =============================================================================
        echo # QR MANAGER - CONFIGURACIÓN SIN MOD_REWRITE
        echo # =============================================================================
        echo.
        echo # Configuración básica de seguridad
        echo Options -Indexes
        echo ServerSignature Off
        echo.
        echo # Proteger archivos JSON
        echo ^<Files "*.json"^>
        echo     Require all denied
        echo ^</Files^>
        echo.
        echo # Proteger archivos de logs
        echo ^<Files "*.log"^>
        echo     Require all denied
        echo ^</Files^>
        echo.
        echo # DirectoryIndex
        echo DirectoryIndex index.php
        echo.
        echo # NO usar mod_rewrite - URLs serán /qr/id/index.php
    ) > .htaccess
    echo [OK] .htaccess creado sin mod_rewrite
)

echo.

REM =============================================================================
REM VERIFICAR INSTALACIÓN
REM =============================================================================

echo [6/6] Verificando instalación...

REM Crear script de verificación
echo ^<?php > verify-windows.php
echo error_reporting(E_ALL); >> verify-windows.php
echo ini_set('display_errors', 1); >> verify-windows.php
echo echo "^<h2^>Verificación QR Manager - Windows/XAMPP^</h2^>"; >> verify-windows.php
echo echo "^<p^>PHP: " . PHP_VERSION . "^</p^>"; >> verify-windows.php
echo echo "^<p^>Servidor: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'No detectado') . "^</p^>"; >> verify-windows.php
echo echo "^<p^>Directorio QR: " . (file_exists('qr') ? 'Existe' : 'No existe') . "^</p^>"; >> verify-windows.php
echo echo "^<p^>^<a href='index.php'^>Ir a QR Manager^</a^>^</p^>"; >> verify-windows.php
echo ?^> >> verify-windows.php

echo [OK] Archivo de verificación creado
echo.

REM =============================================================================
REM CREAR DOCUMENTACIÓN
REM =============================================================================

echo [INFO] Creando documentación...

(
    echo INSTALACIÓN COMPLETADA - QR MANAGER
    echo ===================================
    echo.
    echo XAMPP Path: %XAMPP_PATH%
    echo PHP Version: %PHP_VERSION%
    echo mod_rewrite: %USE_MOD_REWRITE%
    echo.
    echo URLS DE ACCESO:
    echo - Aplicación: http://localhost/qr-manager/
    echo - Verificación: http://localhost/qr-manager/verify-windows.php
    echo.
    echo LOGIN INICIAL:
    echo - Usuario: admin
    echo - Contraseña: password
    echo.
    echo ESTRUCTURA DE URLS:
    if %USE_MOD_REWRITE% equ 1 (
        echo - QR URLs: http://localhost/qr-manager/qr/ID
    ) else (
        echo - QR URLs: http://localhost/qr-manager/qr/ID/index.php
    )
    echo.
    echo PRÓXIMOS PASOS:
    echo 1. Abrir XAMPP Control Panel
    echo 2. Iniciar Apache (si no está iniciado)
    echo 3. Ir a http://localhost/qr-manager/
    echo 4. Hacer login con admin/password
    echo 5. Cambiar la contraseña por defecto
    echo 6. Crear tu primer código QR
    echo.
    echo ARCHIVOS IMPORTANTES:
    echo - config.php: Configuración principal
    echo - %XAMPP_PATH%\php\php.ini: Configuración PHP
    echo - %XAMPP_PATH%\apache\conf\httpd.conf: Configuración Apache
    echo.
    echo SOPORTE:
    echo - Si Apache no inicia, verificar que no haya otro servidor web usando el puerto 80
    echo - Si hay problemas con extensiones PHP, editar %XAMPP_PATH%\php\php.ini
    echo - Para cambiar el puerto de Apache, editar httpd.conf y cambiar "Listen 80"
    echo.
    echo ¡INSTALACIÓN EXITOSA!
) > INSTALACION-WINDOWS-COMPLETADA.txt

echo [OK] Documentación creada: INSTALACION-WINDOWS-COMPLETADA.txt
echo.

REM =============================================================================
REM FINALIZAR
REM =============================================================================

echo  ==========================================
echo  ^|      INSTALACIÓN COMPLETADA!          ^|
echo  ==========================================
echo.
echo  [OK] QR Manager instalado correctamente
echo  [OK] Configurado para XAMPP en Windows
echo.
echo  ACCESO A LA APLICACIÓN:
echo  -----------------------
echo  URL: http://localhost/qr-manager/
echo  Usuario: admin
echo  Contraseña: password
echo.
echo  VERIFICACIÓN:
echo  -------------
echo  http://localhost/qr-manager/verify-windows.php
echo.
if %USE_MOD_REWRITE% equ 1 (
    echo  URLS DE QR: http://localhost/qr-manager/qr/ID
) else (
    echo  URLS DE QR: http://localhost/qr-manager/qr/ID/index.php
)
echo.
echo  [INFO] Lee INSTALACION-WINDOWS-COMPLETADA.txt para más detalles
echo.
echo  ¡LISTO PARA USAR!
echo.
pause

REM Intentar abrir la aplicación en el navegador
echo [INFO] Intentando abrir la aplicación...
start http://localhost/qr-manager/