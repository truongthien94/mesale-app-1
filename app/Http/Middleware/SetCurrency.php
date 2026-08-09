<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use App\Models\Currency;

class SetCurrency
{
    /**
     * Handle an incoming request.
     * Thiết lập tiền tệ hiển thị dựa trên lựa chọn của người dùng trong Session hoặc cấu hình mặc định.
     */
    public function handle(Request $request, Closure $next)
    {
        // Bỏ qua khi đang trong quá trình cài đặt (DB chưa sẵn sàng)
        if (config('app.installing')) {
            return $next($request);
        }

        // 1. Kiểm tra xem người dùng có chọn tiền tệ hiển thị lưu trong Session không
        if (!Session::has('currency')) {
            // 2. Nếu không có trong Session, lấy tiền tệ mặc định trong CSDL
            // Sử dụng Cache 60 phút để tối ưu hóa hiệu năng
            $defaultCurrency = Cache::remember('system_default_currency_code', 3600, function () {
                $defaultCurr = Currency::where('is_active', true)->where('is_default', true)->first();
                return $defaultCurr ? $defaultCurr->code : 'VND';
            });
            
            Session::put('currency', $defaultCurrency);
        }

        return $next($request);
    }
}
