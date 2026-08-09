<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Tự động khởi tạo file .env từ .env.example nếu chưa tồn tại (tránh lỗi 500 do thiếu key mã hóa lúc chạy installer)
// Chỉ tự động tạo khi hệ thống chưa cài đặt (chưa có file installed.lock) để đảm bảo an toàn bảo mật tuyệt đối
if (!file_exists(__DIR__.'/../.env') && !file_exists(__DIR__.'/../storage/installed.lock')) {
    if (file_exists(__DIR__.'/../.env.example')) {
        copy(__DIR__.'/../.env.example', __DIR__.'/../.env');
        
        $envContent = file_get_contents(__DIR__.'/../.env');
        $randomKey = 'base64:'.base64_encode(random_bytes(32));
        $envContent = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$randomKey, $envContent);
        
        if (!str_contains($envContent, 'APP_KEY=')) {
            $envContent .= "\nAPP_KEY=".$randomKey;
        }
        
        file_put_contents(__DIR__.'/../.env', $envContent);
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
