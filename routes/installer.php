<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstallerController;

/*
|--------------------------------------------------------------------------
| Installer Routes - Các tuyến đường cho Trình cài đặt hệ thống
|--------------------------------------------------------------------------
| Route này KHÔNG qua middleware InstallerCheck để tránh vòng lặp redirect.
| Bản thân InstallerController và middleware sẽ xử lý logic chặn/cho phép.
*/

Route::prefix('install')->name('installer.')->group(function () {

    // Bước 1: Kiểm tra yêu cầu môi trường (PHP version, extensions)
    Route::get('/', [InstallerController::class, 'stepRequirements'])->name('step1');

    // Bước 2: Kiểm tra quyền ghi thư mục
    Route::get('/permissions', [InstallerController::class, 'stepPermissions'])->name('step2');

    // Bước 3: Cấu hình Database
    Route::get('/database', [InstallerController::class, 'stepDatabase'])->name('step3');
    Route::post('/database', [InstallerController::class, 'stepDatabaseProcess'])->name('step3.process');
    Route::post('/database/test', [InstallerController::class, 'testDatabaseConnection'])->name('step3.test');

    // Bước 4: Tạo tài khoản Admin
    Route::get('/admin', [InstallerController::class, 'stepAdmin'])->name('step4');
    Route::post('/admin', [InstallerController::class, 'stepAdminProcess'])->name('step4.process');

    // Bước 5: Hoàn tất cài đặt
    Route::get('/finish', [InstallerController::class, 'stepFinish'])->name('step5');
    Route::post('/finish', [InstallerController::class, 'stepFinishProcess'])->name('step5.process');

    // API kiểm tra trạng thái cài đặt
    Route::get('/status', [InstallerController::class, 'checkStatus'])->name('status');
});
