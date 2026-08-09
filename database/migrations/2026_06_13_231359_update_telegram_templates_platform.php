<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Giải thích: Cập nhật các bản ghi cấu hình telegram template của đơn hàng cashback mới và duyệt cashback trong CSDL
     * để hiển thị thông tin nền tảng (platform) nếu chúng chưa được định cấu hình.
     */
    public function up(): void
    {
        // 1. Cập nhật cho telegram_template_cashback_created
        $templateCreated = Setting::where('key', 'telegram_template_cashback_created')->first();
        if ($templateCreated) {
            $value = $templateCreated->value;
            if (strpos($value, '{platform}') === false) {
                // Thêm 🌐 Nền tảng: {platform} sau tiêu đề
                $pattern = "🛍️ <b>[ĐƠN HÀNG CASHBACK CHỜ DUYỆT]</b>\n";
                if (strpos($value, $pattern) !== false) {
                    $newValue = str_replace($pattern, $pattern . "🌐 Nền tảng: {platform}\n", $value);
                } else {
                    $newValue = "🌐 Nền tảng: {platform}\n" . $value;
                }
                $templateCreated->update(['value' => $newValue]);
            }
        }

        // 2. Cập nhật cho telegram_template_cashback_approved
        $templateApproved = Setting::where('key', 'telegram_template_cashback_approved')->first();
        if ($templateApproved) {
            $value = $templateApproved->value;
            if (strpos($value, '{platform}') === false) {
                // Thêm 🌐 Nền tảng: {platform} sau tiêu đề
                $pattern = "🎉 <b>[DUYỆT ĐƠN HÀNG CASHBACK]</b>\n";
                if (strpos($value, $pattern) !== false) {
                    $newValue = str_replace($pattern, $pattern . "🌐 Nền tảng: {platform}\n", $value);
                } else {
                    $newValue = "🌐 Nền tảng: {platform}\n" . $value;
                }
                $templateApproved->update(['value' => $newValue]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không cần rollback vì đây là cập nhật cấu hình dữ liệu
    }
};
