<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotConfig extends Model
{
    protected $fillable = [
        'type',
        'is_enabled',
        'bot_token',
        'webhook_secret',
        'bot_username',
        'welcome_message',
        'cashback_template',
        'not_found_message',
        'login_required_message',
        'extra',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'extra' => 'array',
    ];

    public static function forType(string $type): self
    {
        return self::firstOrCreate(
            ['type' => $type],
            [
                'is_enabled' => false,
                'welcome_message' => 'Xin chào! Hãy gửi link sản phẩm Shopee hoặc TikTok Shop để nhận link hoàn tiền ngay.',
                'cashback_template' => $type === 'zalo'
                    ? "✅ Link hoàn tiền:\n\n🛍️ {product_name}\n💰 Hoàn tiền ước tính: {cashback_amount}đ\n🔗 {cashback_link}"
                    : "✅ <b>Link hoàn tiền của bạn:</b>\n\n🛍️ <b>{product_name}</b>\n💰 Hoàn tiền ước tính: <b>{cashback_amount}đ</b>\n🔗 {cashback_link}",
                'not_found_message' => $type === 'zalo'
                    ? '❌ Không tìm thấy thông tin sản phẩm. Vui lòng thử lại với link Shopee hoặc TikTok Shop hợp lệ.'
                    : '❌ Không tìm thấy thông tin sản phẩm. Vui lòng thử lại với link Shopee hoặc TikTok Shop hợp lệ.',
                'login_required_message' => $type === 'zalo'
                    ? "⚠️ <b>Tài khoản của bạn chưa được liên kết với Zalo Bot.</b>\n\nĐể nhận hoàn tiền tự động về ví của bạn, vui lòng liên kết tài khoản theo các bước sau:\n1. Đăng nhập vào website và vào phần Hồ sơ.\n2. Sao chép Khóa API Token cá nhân của bạn.\n3. Gửi tin nhắn tại đây theo cú pháp:\n/link MÃ_LIÊN_KẾT\n\nSau khi liên kết thành công, bạn chỉ cần gửi link Shopee hoặc TikTok Shop để nhận hoa hồng hoàn tiền ngay lập tức!"
                    : "⚠️ <b>Tài khoản của bạn chưa được liên kết với Telegram Bot.</b>\n\nĐể nhận hoàn tiền tự động về ví của bạn, vui lòng liên kết tài khoản theo các bước sau:\n1️⃣ Đăng nhập vào website và vào phần <b>Hồ sơ</b>.\n2️⃣ Sao chép <b>Khóa API Token cá nhân</b> của bạn.\n3️⃣ Gửi tin nhắn tại đây theo cú pháp:\n<code>/link MÃ_LIÊN_KẾT</code>\n\n<i>Sau khi liên kết thành công, bạn chỉ cần gửi link Shopee hoặc TikTok Shop để nhận hoa hồng hoàn tiền ngay lập tức!</i>",
            ]
        );
    }
}
