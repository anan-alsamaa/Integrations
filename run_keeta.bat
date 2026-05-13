@echo off
:loop
php artisan KeetaProcessSDMOrders
timeout /t 1 /nobreak >nul
goto loop
