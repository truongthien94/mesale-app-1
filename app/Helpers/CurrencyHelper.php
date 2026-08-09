<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use App\Models\Currency;

class CurrencyHelper
{
    /**
     * Lấy thông tin tiền tệ hiện tại đang được áp dụng.
     * Ưu tiên lấy từ Session, nếu chưa có thì lấy tiền tệ mặc định trong CSDL.
     * Sử dụng cache 60 phút để tối ưu hóa hiệu năng truy vấn.
     */
    public static function getCurrentCurrency()
    {
        // 1. Kiểm tra xem session có lưu mã tiền tệ không
        $sessionCode = Session::get('currency');

        if ($sessionCode) {
            // Thử lấy thông tin tiền tệ từ Cache dựa trên mã code
            return Cache::remember('currency_info_' . $sessionCode, 3600, function () use ($sessionCode) {
                return Currency::where('code', $sessionCode)->where('is_active', true)->first();
            }) ?: self::getDefaultCurrency();
        }

        return self::getDefaultCurrency();
    }

    /**
     * Lấy tiền tệ mặc định của hệ thống từ CSDL.
     */
    public static function getDefaultCurrency()
    {
        return Cache::remember('system_default_currency', 3600, function () {
            $defaultCurr = Currency::where('is_active', true)->where('is_default', true)->first();
            // Nếu DB không có tiền tệ mặc định nào, trả về VND làm fallback mặc định
            if (!$defaultCurr) {
                $defaultCurr = new Currency([
                    'name' => 'Việt Nam Đồng',
                    'code' => 'VND',
                    'symbol' => '₫',
                    'exchange_rate' => 1.0000,
                    'symbol_position' => 'after',
                    'is_active' => true,
                    'is_default' => true
                ]);
            }
            return $defaultCurr;
        });
    }

    /**
     * Chuyển đổi số tiền từ VND (đồng tiền mặc định của hệ thống) sang tiền tệ hiện tại.
     * Công thức: Số tiền quy đổi = Số tiền VND / Tỷ giá ngoại tệ.
     * 
     * @param float $amountVnd Số tiền bằng VND cần quy đổi
     * @return float Số tiền sau quy đổi
     */
    public static function convert($amountVnd)
    {
        $currency = self::getCurrentCurrency();
        
        if (!$currency || $currency->code === 'VND') {
            return (float) $amountVnd;
        }

        $exchangeRate = (float) $currency->exchange_rate;
        if ($exchangeRate <= 0) {
            $exchangeRate = 1.0000;
        }

        return (float) $amountVnd / $exchangeRate;
    }

    /**
     * Định dạng số tiền kèm ký hiệu tiền tệ hiện tại sau khi đã quy đổi.
     * Ví dụ: 250,000 VND -> $10.00 hoặc 250,000₫
     * 
     * @param float $amountVnd Số tiền gốc bằng VND
     * @return string Chuỗi tiền tệ đã định dạng
     */
    public static function format($amountVnd)
    {
        $currency = self::getCurrentCurrency();
        $convertedAmount = self::convert($amountVnd);

        // Thiết lập số chữ số thập phân hiển thị (VND thì bằng 0, ngoại tệ khác thì hiển thị 2 chữ số lẻ)
        $decimals = ($currency->code === 'VND') ? 0 : 2;

        // Cắt bỏ (làm tròn xuống) phần lẻ vượt quá số chữ số thập phân được phép hiển thị.
        // Mục đích: không bao giờ hiển thị số dư lớn hơn số dư thật, vì nếu làm tròn lên thì thành viên
        // sẽ nhập đúng con số nhìn thấy để rút tiền và bị hệ thống báo lỗi vượt quá số dư.
        // Lưu ý: với VND thì mọi số tiền trong cơ sở dữ liệu đã là số nguyên (xem App\Helpers\MoneyHelper)
        // nên phép cắt này không còn tác dụng gì, nó chỉ thực sự cần thiết cho các ngoại tệ có tỷ giá quy đổi.
        $multiplier = pow(10, $decimals);
        $truncatedAmount = floor($convertedAmount * $multiplier) / $multiplier;

        $formattedNumber = number_format($truncatedAmount, $decimals, '.', ',');

        // Đặt ký hiệu tiền tệ trước hoặc sau số tiền tùy thuộc vào cấu hình
        if ($currency->symbol_position === 'before') {
            return $currency->symbol . $formattedNumber;
        }

        return $formattedNumber . $currency->symbol;
    }
}
