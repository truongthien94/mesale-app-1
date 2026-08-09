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
            $oldText = 'Hệ thống đã nhận được thông tin đơn hàng Shopee của bạn';
            $newText = 'Hệ thống đã nhận được thông tin đơn hàng của bạn';
            if (strpos($content, $oldText) !== false) {
                $createdSetting->value = str_replace($oldText, $newText, $content);
                $createdSetting->save();
            }
        }

        // 2. Cập nhật email_content_cashback_approved
        $approvedSetting = Setting::where('key', 'email_content_cashback_approved')->first();
        if ($approvedSetting) {
            $content = $approvedSetting->value;
            $oldText = 'Đơn hàng Shopee của bạn đã được đối soát thành công và phê duyệt hoàn tiền. Thông tin chi tiết như sau:';
            $newText = 'Đơn hàng của bạn đã được đối soát thành công và phê duyệt hoàn tiền. Thông tin chi tiết như sau:';
            if (strpos($content, $oldText) !== false) {
                $approvedSetting->value = str_replace($oldText, $newText, $content);
                $approvedSetting->save();
            }
        }

        // Xóa cache settings
        Cache::forget('setting.email_content_cashback_created');
        Cache::forget('setting.email_content_cashback_approved');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không cần khôi phục
    }
};
