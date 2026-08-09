<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Cập nhật email_content_cashback_created
        $createdSetting = Setting::where('key', 'email_content_cashback_created')->first();
        if ($createdSetting) {
            $content = $createdSetting->value;
            // Kiểm tra xem đã có biến {platform} trong nội dung chưa, nếu chưa thì chèn vào
            if (strpos($content, '{platform}') === false) {
                // Tìm vị trí của thẻ đóng tr chứa order_id
                // Ta tìm đoạn chứa order_id: {order_id}</td>\s*</tr>
                $pattern = '/(\{order_id\}\s*<\/td>\s*<\/tr>)/i';
                $replacement = "$1\n                    <tr style=\"border-bottom: 1px solid #f3f4f6;\">\n                        <td style=\"padding: 10px 0; color: #6b7280;\">Nền tảng:<\/td>\n                        <td style=\"padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right;\">{platform}<\/td>\n                    <\/tr>";
                $newContent = preg_replace($pattern, $replacement, $content);
                if ($newContent && $newContent !== $content) {
                    $createdSetting->value = $newContent;
                    $createdSetting->save();
                }
            }
        }

        // 2. Cập nhật email_content_cashback_approved
        $approvedSetting = Setting::where('key', 'email_content_cashback_approved')->first();
        if ($approvedSetting) {
            $content = $approvedSetting->value;
            if (strpos($content, '{platform}') === false) {
                $pattern = '/(\{order_id\}\s*<\/td>\s*<\/tr>)/i';
                $replacement = "$1\n                    <tr style=\"border-bottom: 1px solid #dcfce7;\">\n                        <td style=\"padding: 10px 0; color: #15803d;\">Nền tảng:<\/td>\n                        <td style=\"padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right;\">{platform}<\/td>\n                    <\/tr>";
                $newContent = preg_replace($pattern, $replacement, $content);
                if ($newContent && $newContent !== $content) {
                    $approvedSetting->value = $newContent;
                    $approvedSetting->save();
                }
            }
        }

        // Xóa cache settings để cập nhật giá trị mới
        Cache::forget('setting.email_content_cashback_created');
        Cache::forget('setting.email_content_cashback_approved');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không cần khôi phục vì đây là cập nhật nội dung văn bản tùy chỉnh
    }
};
