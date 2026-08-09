<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $oldDefault = '⚠️ Vui lòng gửi mã thành viên của bạn (ví dụ: REF123456) trước khi lấy link hoàn tiền.';

        $newTelegramDefault = "⚠️ <b>Tài khoản của bạn chưa được liên kết với Telegram Bot.</b>\n\nĐể nhận hoàn tiền tự động về ví của bạn, vui lòng liên kết tài khoản theo các bước sau:\n1️⃣ Đăng nhập vào website và vào phần <b>Hồ sơ</b>.\n2️⃣ Sao chép <b>Mã giới thiệu</b> của bạn (Ví dụ: <code>USER123</code>).\n3️⃣ Gửi tin nhắn tại đây theo cú pháp:\n<code>/link MÃ_GIỚI_THIỆU</code> (Ví dụ: <code>/link USER123</code>)\n\n<i>Sau khi liên kết thành công, bạn chỉ cần gửi link Shopee hoặc TikTok Shop để nhận hoa hồng hoàn tiền ngay lập tức!</i>";

        $newZaloDefault = "⚠️ <b>Tài khoản của bạn chưa được liên kết với Zalo Bot.</b>\n\nĐể nhận hoàn tiền tự động về ví của bạn, vui lòng liên kết tài khoản theo các bước sau:\n1. Đăng nhập vào website và vào phần Hồ sơ.\n2. Sao chép Mã giới thiệu của bạn (Ví dụ: USER123).\n3. Gửi tin nhắn tại đây theo cú pháp:\n/link MÃ_GIỚI_THIỆU (Ví dụ: /link USER123)\n\nSau khi liên kết thành công, bạn chỉ cần gửi link Shopee hoặc TikTok Shop để nhận hoa hồng hoàn tiền ngay lập tức!";

        DB::table('bot_configs')
            ->where('type', 'telegram')
            ->where(function($query) use ($oldDefault) {
                $query->whereNull('login_required_message')
                      ->orWhere('login_required_message', '')
                      ->orWhere('login_required_message', $oldDefault);
            })
            ->update(['login_required_message' => $newTelegramDefault]);

        DB::table('bot_configs')
            ->where('type', 'zalo')
            ->where(function($query) use ($oldDefault) {
                $query->whereNull('login_required_message')
                      ->orWhere('login_required_message', '')
                      ->orWhere('login_required_message', $oldDefault);
            })
            ->update(['login_required_message' => $newZaloDefault]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse data updates
    }
};
