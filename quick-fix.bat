@echo off
echo ========================================
echo   Todo x AI Assistant - Quick Fix
echo ========================================
echo.

echo [1/4] Clearing all caches...
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo.
echo [2/4] Checking database connection...
php artisan migrate:status

echo.
echo [3/4] Checking users...
php artisan tinker --execute="echo 'Total users: ' . App\Models\User::count() . PHP_EOL;"

echo.
echo [4/4] Test credentials:
echo =====================================
echo Email: test@example.com
echo Password: password
echo =====================================
echo.

echo All clear! Now you can:
echo 1. Open browser: http://localhost:8000/login
echo 2. Login with credentials above
echo.

pause
