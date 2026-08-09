<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Chèn tiêu đề email tạo đơn đổi quà mặc định
        DB::table('settings')->updateOrInsert(
            ['key' => 'email_subject_gift_created'],
            ['value' => 'Yêu cầu đổi quà tặng đang chờ duyệt - Hoàn Tiền Shopee']
        );

        // Chèn nội dung email tạo đơn đổi quà mặc định dạng HTML
        DB::table('settings')->updateOrInsert(
            ['key' => 'email_content_gift_created'],
            ['value' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #ffb037 0%, #ff8c00 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <div style="width: 48px; height: 48px; background-color: #fffbeb; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                <span style="font-size: 24px; color: #f59e0b;">🎁</span>
            </div>
            <h2 style="color: #f59e0b; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Yêu Cầu Đổi Quà</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Hệ thống đã nhận được yêu cầu quy đổi của bạn</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 20px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Yêu cầu đổi quà tặng từ số dư tích lũy của bạn đã được ghi nhận trên hệ thống và đang chờ phê duyệt từ phía quản trị viên:</p>
            
            <div style="background-color: #fffbeb; border-radius: 12px; border: 1px solid #fef3c7; padding: 20px; margin: 24px 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr style="border-bottom: 1px solid #fde68a;">
                        <td style="padding: 10px 0; color: #b45309; width: 120px;">Tên quà tặng:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right;">{gift_title}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #fde68a;">
                        <td style="padding: 10px 0; color: #b45309;">Số tiền quy đổi:</td>
                        <td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right;">{gift_price}đ</td>
                    </tr>
                </table>
            </div>
            
            <p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.6;">Yêu cầu quy đổi sẽ được quản trị viên duyệt và phản hồi trong thời gian sớm nhất. Thông tin nhận quà (mã code/vận đơn) sẽ được cập nhật trực tiếp trong email tiếp theo và phần lịch sử đổi quà của bạn.</p>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa cấu hình email tạo đơn đổi quà khỏi database
        DB::table('settings')->whereIn('key', ['email_subject_gift_created', 'email_content_gift_created'])->delete();
    }
};
