<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use App\Models\Language;
use Illuminate\Support\Facades\Cache;

class SetLocale
{
    /**
     * Handle an incoming request.
     * Thiết lập ngôn ngữ hệ thống dựa trên tùy chọn của người dùng hoặc cấu hình mặc định.
     */
    public function handle(Request $request, Closure $next)
    {
        // Bỏ qua khi đang trong quá trình cài đặt (DB chưa sẵn sàng)
        if (config('app.installing')) {
            return $next($request);
        }

        // 1. Kiểm tra xem người dùng có chọn ngôn ngữ và lưu trong Session hay không
        if (Session::has('locale')) {
            $locale = Session::get('locale');
            App::setLocale($locale);
        } else {
            // 2. Nếu không có trong Session, truy vấn ngôn ngữ mặc định được đánh dấu trong CSDL
            // Sử dụng Cache 60 phút để tối ưu hóa hiệu năng, tránh query DB liên tục ở mọi Request
            $defaultLocale = Cache::remember('system_default_locale', 3600, function () {
                $defaultLang = Language::where('is_active', true)->where('is_default', true)->first();
                return $defaultLang ? $defaultLang->code : config('app.locale', 'vi');
            });
            
            App::setLocale($defaultLocale);
            Session::put('locale', $defaultLocale);
        }

        return $next($request);
    }
}
