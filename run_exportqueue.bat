@echo off
cd /d C:\inetpub\wwwroot\Integrations
:loop
php artisan queue:work --timeout=3600 --tries=1 --stop-when-empty
timeout /t 5 /nobreak >nul
goto loop