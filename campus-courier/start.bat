@echo off
chcp 65001 >nul
echo ==========================================
echo   校园快递代取系统 - 本地服务器
echo ==========================================
echo.

php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [错误] 未检测到 PHP，请先安装 PHP
    pause
    exit /b 1
)

echo [成功] PHP 已检测到
php -v
echo.

if not exist "index.php" (
    echo [错误] 请在项目根目录下运行此脚本
    pause
    exit /b 1
)

echo [信息] 正在启动 PHP 内置服务器...
echo [信息] 服务器地址: http://localhost:8080
echo [信息] 按 Ctrl+C 停止服务器
echo.
echo ===========================================
echo.

php -S localhost:8080

pause