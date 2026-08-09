<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\Currency;

class CurrencyController extends Controller
{
    /**
     * Thay đổi tiền tệ hiển thị hệ thống.
     * Lưu tùy chọn tiền tệ vào Session và chuyển hướng quay lại.
     */
    public function changeCurrency(string $code)
    {
        // 1. Kiểm tra xem tiền tệ có tồn tại và đang được kích hoạt trong CSDL hay không
        $currency = Currency::where('code', strtoupper($code))->where('is_active', true)->first();

        if ($currency) {
            // 2. Lưu tiền tệ đã chọn vào Session
            Session::put('currency', $currency->code);
            return redirect()->back()->with('success', 'Thay đổi tiền tệ hiển thị thành công sang ' . $currency->name . '!');
        }

        return redirect()->back()->with('error', 'Tiền tệ này hiện không khả dụng.');
    }
}
