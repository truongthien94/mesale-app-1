<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

/**
 * InstallerCheck Middleware
 * 
 * Kiểm tra trạng thái cài đặt hệ thống:
 * - Nếu chưa cài đặt (chưa có file installed.lock): Chuyển hướng đến trang installer
 * - Nếu đã cài đặt: Cho phép truy cập bình thường
 * - Khi đang ở installer route: Đặt flag để các middleware phía sau (SetLocale, SetCurrency)
 *   biết mà skip query DB (tránh lỗi khi DB chưa được thiết lập)
 * 
 * File lock: storage/installed.lock (tạo tự động khi hoàn tất Step 5)
 */
class InstallerCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        $installerRoutes = [
            'installer.step1',
            'installer.step2',
            'installer.step3',
            'installer.step4',
            'installer.step5',
            'installer.step3.process',
            'installer.step3.test',
            'installer.step4.process',
            'installer.step5.process',
            'installer.status',
        ];

        $currentRoute = $request->route()?->getName();
        // Kiểm tra an toàn: Nhận diện route cài đặt dựa trên danh sách cấu hình, tiền tố tên route hoặc cấu trúc URL
        $isInstallerRoute = in_array($currentRoute, $installerRoutes) 
            || ($currentRoute && str_starts_with($currentRoute, 'installer.'))
            || $request->is('install') 
            || $request->is('install/*');
        $isInstalled = File::exists(storage_path('installed.lock'));

        // Trường hợp 1: Đang ở installer route
        // Đặt flag runtime để SetLocale & SetCurrency skip query DB (tránh crash khi chưa có DB)
        if ($isInstallerRoute) {
            // Chặn truy cập installer nếu đã cài xong
            if ($isInstalled) {
                abort(403, 'Hệ thống đã được cài đặt. Nếu cần cài lại, hãy xóa file storage/installed.lock.');
            }

            // Đánh dấu đang trong quá trình cài đặt (dùng config runtime, không persist)
            config(['app.installing' => true]);

            return $next($request);
        }

        // Trường hợp 2: CHƯA cài đặt + đang truy cập route thường
        // => Buộc chuyển hướng về trang cài đặt
        if (!$isInstalled) {
            return redirect()->route('installer.step1');
        }

        // Trường hợp 3: Đã cài đặt + truy cập route thường => cho phép
        return $next($request);
    }
}
