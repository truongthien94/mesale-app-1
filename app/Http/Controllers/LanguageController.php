<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\Language;

class LanguageController extends Controller
{
    /**
     * Thay đổi ngôn ngữ hiển thị hệ thống.
     * Lưu tùy chọn ngôn ngữ vào Session và chuyển hướng quay lại.
     */
    public function changeLanguage(string $locale)
    {
        // 1. Kiểm tra xem ngôn ngữ có tồn tại và đang được kích hoạt trong CSDL hay không
        $lang = Language::where('code', $locale)->where('is_active', true)->first();

        if ($lang) {
            // 2. Lưu ngôn ngữ đã chọn vào Session
            Session::put('locale', $locale);
            return redirect()->back()->with('success', 'Thay đổi ngôn ngữ hiển thị thành công sang ' . $lang->name . '!');
        }

        return redirect()->back()->with('error', 'Ngôn ngữ này hiện không khả dụng.');
    }
}
