@echo off
REM SJ LegalSuite — servidor PHP 8.2 en puerto 8082 (si Apache aun no recarga con mod_php 8.2).
set PHP=C:\laragon\bin\php\php-8.2.29-Win32-vs16-x64\php.exe
cd /d C:\laragon\www\SJ_LegalSuite
"%PHP%" artisan serve --host=0.0.0.0 --port=8082
