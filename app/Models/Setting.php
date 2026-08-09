<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    // Chỉ định bảng tương ứng trong DB
    protected $table = 'settings';

    // Danh sách cột điền thông tin
    protected $fillable = [
        'key',
        'value',
        'description'
    ];

    public static function getVal(string $key, $default = null)
    {
        try {
            return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
                try {
                    $setting = self::where('key', $key)->first();
                    return $setting ? $setting->value : $default;
                } catch (\Throwable $e) {
                    return $default;
                }
            });
        } catch (\Throwable $e) {
            // Nếu Cache sập (ví dụ cấu hình Redis sai hoặc Redis sập), fallback truy vấn trực tiếp DB
            try {
                $setting = self::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            } catch (\Throwable $e2) {
                // Nếu cả DB cũng chưa cấu hình hoặc sập, trả về giá trị mặc định
                return $default;
            }
        }
    }

    /**
     * Thiết lập hoặc cập nhật cấu hình theo key và tự động xoá cache cũ.
     *
     * @param string $key Khoá cấu hình
     * @param mixed $value Giá trị cấu hình
     * @param string|null $description Mô tả cấu hình
     * @return self
     */
    public static function setVal(string $key, $value, ?string $description = null)
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'description' => $description]
        );
        Cache::forget("setting.{$key}");
        return $setting;
    }

    /**
     * Tự động điều chỉnh sắc thái của màu HEX (nhạt đi, đậm lên, hoặc chuyển sang opacity).
     *
     * @param string $hex Mã màu Hex (ví dụ: #ee4d2d hoặc ee4d2d)
     * @param float $percent Tỷ lệ đổi màu (-1.0: tối nhất/đen, 1.0: sáng nhất/trắng)
     * @return string Mã màu Hex mới
     */
    public static function adjustBrightness(string $hex, float $percent): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        if ($percent > 0) {
            // Sáng hơn (Lighten)
            $r = round($r + (255 - $r) * $percent);
            $g = round($g + (255 - $g) * $percent);
            $b = round($b + (255 - $b) * $percent);
        } else {
            // Tối hơn (Darken)
            $r = round($r * (1 + $percent));
            $g = round($g * (1 + $percent));
            $b = round($b * (1 + $percent));
        }

        $r = max(0, min(255, $r));
        $g = max(0, min(255, $g));
        $b = max(0, min(255, $b));

        return '#' . sprintf('%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Gửi thông báo đến Telegram Bot dựa vào cấu hình hệ thống.
     *
     * @param string $message Nội dung tin nhắn gửi đi
     * @param string|null $chatId Ghi đè Chat ID (nếu muốn gửi sang nhóm khác)
     * @return bool Trả về true nếu gửi thành công, false nếu thất bại hoặc chưa cấu hình
     */
    public static function sendTelegram(string $message, ?string $chatId = null): bool
    {
        // Kiểm tra xem tính năng gửi thông báo Telegram Bot có được bật hay không
        // Nếu admin tắt (telegram_status = 0), hệ thống sẽ ngưng gửi để tránh spam hoặc tiết kiệm tài nguyên
        if (self::getVal('telegram_status', '1') !== '1') {
            return false;
        }

        $botToken = self::getVal('telegram_bot_token');
        $targetChatId = $chatId ?? self::getVal('telegram_chat_id');

        if (empty($botToken) || empty($targetChatId)) {
            return false;
        }

        try {
            // Thay thế các ký tự xuống dòng thô (dạng text \n hoặc \r\n) thành xuống dòng thực tế để tin nhắn Telegram hiển thị chuẩn
            $message = str_replace(['\r\n', '\n', '\r'], ["\n", "\n", "\n"], $message);

            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            $data = [
                'chat_id'                  => $targetChatId,
                'text'                     => $message,
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => true,
            ];

            // Sử dụng Laravel HTTP Client để gửi request POST
            $response = \Illuminate\Support\Facades\Http::timeout(10)->post($url, $data);

            return $response->successful();
        } catch (\Exception $e) {
            // Ghi log lỗi hệ thống nếu việc kết nối Telegram bị lỗi
            \Illuminate\Support\Facades\Log::error("Lỗi gửi thông báo Telegram: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gửi thông báo Telegram bằng cách đưa vào hàng đợi (Queue) để gửi bất đồng bộ.
     * Quy tắc nghiệp vụ: Giúp tránh việc gọi API Telegram trực tiếp làm chậm luồng xử lý chính của người dùng.
     *
     * @param string $message Nội dung tin nhắn gửi đi
     * @param string|null $chatId Ghi đè Chat ID (nếu muốn gửi sang nhóm khác)
     * @return bool Trả về true nếu thêm vào hàng đợi thành công
     */
    public static function sendTelegramQueue(string $message, ?string $chatId = null): bool
    {
        // Kiểm tra xem tính năng gửi thông báo Telegram Bot có được bật hay không
        // Nếu bị tắt, không cần đưa vào hàng đợi để giảm tải cho database
        if (self::getVal('telegram_status', '1') !== '1') {
            return false;
        }

        $targetChatId = $chatId ?? self::getVal('telegram_chat_id');

        if (empty($targetChatId)) {
            return false;
        }

        try {
            \App\Models\TelegramQueue::enqueue($targetChatId, $message);
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Lỗi thêm thông báo Telegram vào hàng đợi: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gửi thông báo Telegram sử dụng template cấu hình từ Database.
     *
     * @param string $templateKey Khoá của template telegram trong settings (ví dụ: telegram_template_new_user)
     * @param array $data Mảng các biến thay thế trong template (ví dụ: ['name' => 'Nguyễn Văn A', 'email' => 'a@gmail.com'])
     * @param string|null $chatId Ghi đè Chat ID người nhận
     * @return bool Trả về true nếu gửi thành công, false nếu thất bại
     */
    public static function sendTelegramTemplate(string $templateKey, array $data = [], ?string $chatId = null): bool
    {
        // Kiểm tra xem tính năng gửi thông báo Telegram Bot có được bật hay không
        // Nếu admin tắt (telegram_status = 0), hệ thống sẽ ngưng gửi tin nhắn template để tiết kiệm tài nguyên
        if (self::getVal('telegram_status', '1') !== '1') {
            return false;
        }

        // Chuyển đổi tên key template thành key trạng thái và key chat_id riêng để quản lý chi tiết
        $statusKey = str_replace('telegram_template_', 'telegram_status_', $templateKey);
        $customChatIdKey = str_replace('telegram_template_', 'telegram_chat_id_', $templateKey);

        // 1. Kiểm tra trạng thái ON/OFF của loại thông báo này (mặc định là bật '1')
        $status = self::getVal($statusKey, '1');
        if ($status !== '1') {
            return false;
        }

        // 2. Kiểm tra xem Telegram Bot đã được cấu hình kết nối cơ bản hay chưa
        $botToken = self::getVal('telegram_bot_token');
        if (empty($botToken)) {
            return false; // Bỏ qua nếu chưa cấu hình Token để tránh lãng phí tài nguyên xử lý phía sau
        }

        $customChatId = self::getVal($customChatIdKey);
        $targetChatId = !empty($customChatId) ? $customChatId : ($chatId ?? self::getVal('telegram_chat_id'));
        if (empty($targetChatId)) {
            return false; // Bỏ qua nếu không có Chat ID hợp lệ nào được tìm thấy
        }

        // 3. Định nghĩa nội dung template mặc định của từng loại sử dụng xuống dòng thực tế thay vì ký tự \n thô
        $defaultTemplates = [
            'telegram_template_new_user' => "🔔 <b>[ĐĂNG KÝ TÀI KHOẢN MỚI]</b>
👤 Họ tên: {name}
📧 Email: {email}
📅 Thời gian: {time}",
            'telegram_template_withdrawal_created' => "💰 <b>[YÊU CẦU RÚT TIỀN MỚI]</b>
👤 Thành viên: {name} ({email})
💵 Số tiền: {amount}đ
💳 Phương thức: {payment_method}
🏦 Ngân hàng: {bank_name}
💳 Số tài khoản: {account_number}
👤 Tên tài khoản: {account_name}
📅 Thời gian: {time}",
            'telegram_template_withdrawal_approved' => "✅ <b>[DUYỆT YÊU CẦU RÚT TIỀN]</b>
👤 Thành viên: {name} ({email})
💵 Số tiền: {amount}đ
💳 Phương thức: {payment_method}
🏦 Ngân hàng: {bank_name}
💳 Số tài khoản: {account_number}
👤 Tên tài khoản: {account_name}
🟢 Trạng thái: THÀNH CÔNG
📅 Thời gian: {time}",
            'telegram_template_withdrawal_rejected' => "❌ <b>[TỪ CHỐI YÊU CẦU RÚT TIỀN]</b>
👤 Thành viên: {name} ({email})
💵 Số tiền: {amount}đ
🔴 Trạng thái: TỪ CHỐI
💬 Lý do: {reason}
📅 Thời gian: {time}",
            'telegram_template_cashback_created' => "🛍️ <b>[ĐƠN HÀNG CASHBACK CHỜ DUYỆT]</b>
🌐 Nền tảng: {platform}
👤 Thành viên: {name} ({email})
📦 Sản phẩm: {product_name}
💵 Giá trị đơn: {price}đ
💰 Hoàn tiền: {cashback_amount}đ
📅 Thời gian: {time}",
            'telegram_template_cashback_approved' => "🎉 <b>[DUYỆT ĐƠN HÀNG CASHBACK]</b>
🌐 Nền tảng: {platform}
👤 Thành viên: {name} ({email})
📦 Sản phẩm: {product_name}
💵 Giá trị đơn: {price}đ
💰 Hoàn tiền F0: {cashback_amount}đ
👥 Hoa hồng F1 ({f1_name}): {f1_commission}đ
👥 Hoa hồng F2 ({f2_name}): {f2_commission}đ
📅 Thời gian: {time}",
            'telegram_template_gift_created' => "🎁 <b>[YÊU CẦU ĐỔI QUÀ MỚI]</b>
👤 Thành viên: {name} ({email})
🛍️ Quà tặng: {gift_title}
💵 Giá trị: {gift_price}đ
📅 Thời gian: {time}",
            'telegram_template_shopee_cookie_expired' => "⚠️ <b>[CẢNH BÁO COOKIE SHOPEE LỖI]</b>
🌐 Hệ thống: {site_name}
👤 Tài khoản: <b>{account_name}</b> ({username})
🔴 Trạng thái: Hết hạn / Lỗi Cookie
💬 Chi tiết: {error_message}
📅 Thời gian: {time}
👉 Vui lòng đăng nhập trang quản trị để cập nhật lại Cookie Shopee.",
            'telegram_template_task_submitted' => "📝 <b>[YÊU CẦU XÁC NHẬN NHIỆM VỤ]</b>
👤 Thành viên: {name} ({email})
🎯 Nhiệm vụ: {task_title}
🏷️ Loại: {task_type}
💰 Tiền thưởng: {reward_amount}đ
💬 Ghi chú: {note}
📅 Thời gian: {time}
👉 Vào trang quản trị mục Nhiệm vụ → Tiến độ thành viên để duyệt."
        ];

        if (!isset($defaultTemplates[$templateKey])) {
            return false;
        }

        // 4. Lấy nội dung template từ database (nếu chưa có thì lấy mặc định)
        $message = self::getVal($templateKey, $defaultTemplates[$templateKey]);

        if (empty($message)) {
            return false;
        }

        // 5. Gộp thêm các biến hệ thống mặc định
        $data['time'] = date('d-m-Y H:i:s');

        // 6. Parse các biến trong nội dung tin nhắn
        foreach ($data as $key => $val) {
            $message = str_replace("{{$key}}", (string)$val, $message);
        }

        // 7. Thực hiện lưu vào hàng đợi gửi tin nhắn để Cron job quét gửi đi sau
        return self::sendTelegramQueue($message, $targetChatId);
    }

    /**
     * Sinh mã đơn hàng ngẫu nhiên tùy chỉnh dựa trên cấu hình hệ thống.
     * Logic: Đọc các tham số cấu hình gồm tiền tố (prefix), vị trí tiền tố, độ dài ký tự ngẫu nhiên, kiểu ký tự ngẫu nhiên,
     * sau đó tạo chuỗi ngẫu nhiên không trùng lặp trong database (cashback_clicks và cashback_histories).
     *
     * @return string
     */
    public static function generateOrderCode(string $platform = 'shopee'): string
    {
        $suffix = $platform === 'shopee' ? '' : '_' . $platform;
        // Tiền tố mặc định theo từng sàn thương mại điện tử
        $defaultPrefix = match ($platform) {
            'tiktok' => 'TTS',
            'lazada' => 'LZD',
            default => 'SHP',
        };
        // Đọc cấu hình từ Setting, nếu chưa có thì gán giá trị mặc định tương ứng
        $prefix = self::getVal('order_code_prefix' . $suffix, $defaultPrefix);
        $position = self::getVal('order_code_prefix_position' . $suffix, 'left');
        $length = (int) self::getVal('order_code_random_length' . $suffix, 10);
        $type = self::getVal('order_code_random_type' . $suffix, 'alphanumeric_upper');

        // Giới hạn độ dài ký tự ngẫu nhiên từ 4 đến 32 ký tự để đảm bảo an toàn và tính thẩm mỹ
        if ($length < 4 || $length > 32) {
            $length = 10;
        }

        do {
            // Lựa chọn bộ ký tự phù hợp theo cấu hình kiểu ngẫu nhiên của Admin
            switch ($type) {
                case 'numeric':
                    $characters = '0123456789';
                    break;
                case 'alpha_upper':
                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    break;
                case 'alpha':
                    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    break;
                case 'alphanumeric':
                    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    break;
                case 'alphanumeric_upper':
                default:
                    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    break;
            }

            $charactersLength = strlen($characters);
            $randomString = '';
            // Sinh chuỗi ngẫu nhiên sử dụng random_int bảo mật cao chống đoán trước
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[random_int(0, $charactersLength - 1)];
            }

            // Nối tiền tố theo vị trí cấu hình (trái hoặc phải)
            if ($position === 'right') {
                $code = $randomString . $prefix;
            } else {
                $code = $prefix . $randomString;
            }

            // Đảm bảo mã đơn hàng (hoặc mã giao dịch) không bị trùng lặp trong cả bảng clicks và histories
            $existsInClicks = \DB::table('cashback_clicks')->where('trans_id', $code)->exists();
            $existsInHistories = \DB::table('cashback_histories')->where('order_id', $code)->exists();
            $existsInHistoriesTrans = \DB::table('cashback_histories')->where('trans_id', $code)->exists();

            $isUnique = !$existsInClicks && !$existsInHistories && !$existsInHistoriesTrans;

        } while (!$isUnique);

        return $code;
    }

    /**
     * Sinh mã đơn rút tiền ngẫu nhiên tùy chỉnh dựa trên cấu hình hệ thống.
     * Logic: Đọc các tham số cấu hình gồm tiền tố (prefix), vị trí tiền tố, độ dài ký tự ngẫu nhiên, kiểu ký tự ngẫu nhiên,
     * sau đó tạo chuỗi ngẫu nhiên không trùng lặp trong database (withdrawals).
     *
     * @return string
     */
    public static function generateWithdrawCode(): string
    {
        // Đọc cấu hình từ Setting, nếu chưa có thì gán giá trị mặc định tương ứng
        $prefix = self::getVal('withdraw_code_prefix', 'HTS');
        $position = self::getVal('withdraw_code_prefix_position', 'left');
        $length = (int) self::getVal('withdraw_code_random_length', 6);
        $type = self::getVal('withdraw_code_random_type', 'alphanumeric_upper');

        // Giới hạn độ dài ký tự ngẫu nhiên từ 4 đến 32 ký tự để đảm bảo an toàn và tính thẩm mỹ
        if ($length < 4 || $length > 32) {
            $length = 6;
        }

        do {
            // Lựa chọn bộ ký tự phù hợp theo cấu hình kiểu ngẫu nhiên của Admin
            switch ($type) {
                case 'numeric':
                    $characters = '0123456789';
                    break;
                case 'alpha_upper':
                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    break;
                case 'alpha':
                    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    break;
                case 'alphanumeric':
                    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    break;
                case 'alphanumeric_upper':
                default:
                    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    break;
            }

            $charactersLength = strlen($characters);
            $randomString = '';
            // Sinh chuỗi ngẫu nhiên sử dụng random_int bảo mật cao chống đoán trước
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[random_int(0, $charactersLength - 1)];
            }

            // Nối tiền tố theo vị trí cấu hình (trái hoặc phải)
            if ($position === 'right') {
                $code = $randomString . $prefix;
            } else {
                $code = $prefix . $randomString;
            }

            // Đảm bảo mã đơn rút tiền không bị trùng lặp trong bảng withdrawals
            $isUnique = !\DB::table('withdrawals')->where('code', $code)->exists();

        } while (!$isUnique);

        return $code;
    }

    /**
     * Parse nội dung email template theo key và data.
     *
     * @param string $templateKey Khoá của template email
     * @param array $data Mảng các biến thay thế
     * @return array Mảng chứa ['subject', 'content']
     */
    public static function parseEmailContent(string $templateKey, array $data = []): array
    {
        $defaultTemplates = [
            'forgot_password' => [
                'subject' => 'Yêu Cầu Đặt Lại Mật Khẩu - Hoàn Tiền Shopee',
                'content' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #ff7a45 0%, #ee4d2d 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <h2 style="color: #ee4d2d; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Hoàn Tiền Shopee</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Đặt lại mật khẩu tài khoản của bạn</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 24px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản liên kết với email <strong style="color: #1f2937;">{email}</strong>. Vui lòng nhấn vào nút bên dưới để thiết lập mật khẩu mới (yêu cầu này có hiệu lực trong vòng 60 phút):</p>
            
            <div style="text-align: center; margin: 32px 0;">
                <a href="{reset_url}" style="background: linear-gradient(135deg, #ff7a45 0%, #ee4d2d 100%); color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 14px; display: inline-block; box-shadow: 0 4px 12px rgba(238, 77, 45, 0.25);">Đặt Lại Mật Khẩu</a>
            </div>
            
            <p style="margin: 0 0 16px 0; font-size: 13px; color: #9ca3af; line-height: 1.6; font-style: italic; border-left: 3px solid #e5e7eb; padding-left: 12px;">Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này. Tài khoản của bạn vẫn được bảo mật an toàn.</p>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>'
            ],
            'otp' => [
                'subject' => 'Mã Xác Thực Đăng Nhập - Hoàn Tiền Shopee',
                'content' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #ff7a45 0%, #ee4d2d 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <h2 style="color: #ee4d2d; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Hoàn Tiền Shopee</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Mã xác thực giao dịch bảo mật</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 24px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Dưới đây là mã xác thực OTP dùng để đăng nhập hoặc xác thực hoạt động tài khoản của bạn:</p>
            
            <div style="background-color: #fff7f5; border: 1.5px dashed #ee4d2d; border-radius: 12px; padding: 20px; text-align: center; margin: 24px 0;">
                <span style="font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #ee4d2d; font-family: \'Courier New\', Courier, monospace;">{otp}</span>
                <p style="margin: 8px 0 0 0; font-size: 12px; color: #ff7a45;">Mã OTP có hiệu lực trong vòng 10 phút</p>
            </div>
            
            <p style="margin: 0; font-size: 13px; color: #ff4d4d; line-height: 1.6; font-weight: 500;">⚠️ Lưu ý: Tuyệt đối KHÔNG chia sẻ mã OTP này cho bất kỳ ai, kể cả nhân viên hỗ trợ hệ thống.</p>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>'
            ],
            'verify_email' => [
                'subject' => 'Xác Minh Email Tài Khoản - Hoàn Tiền Shopee',
                'content' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #ff7a45 0%, #ee4d2d 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <h2 style="color: #ee4d2d; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Hoàn Tiền Shopee</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Xác minh địa chỉ email của bạn</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 24px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Cảm ơn bạn đã đăng ký tài khoản tại Hoàn Tiền Shopee. Để hoàn tất việc đăng ký và kích hoạt tài khoản, vui lòng sử dụng mã xác thực OTP dưới đây:</p>
            
            <div style="background-color: #fff7f5; border: 1.5px dashed #ee4d2d; border-radius: 12px; padding: 20px; text-align: center; margin: 24px 0;">
                <span style="font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #ee4d2d; font-family: \'Courier New\', Courier, monospace;">{otp}</span>
                <p style="margin: 8px 0 0 0; font-size: 12px; color: #ff7a45;">Mã xác thực có hiệu lực trong vòng 10 phút</p>
            </div>
            
            <p style="margin: 0 0 16px 0; font-size: 13px; color: #9ca3af; line-height: 1.6; font-style: italic; border-left: 3px solid #e5e7eb; padding-left: 12px;">Nếu bạn không thực hiện đăng ký tài khoản trên hệ thống của chúng tôi, vui lòng bỏ qua email này.</p>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>'
            ],
            'welcome' => [
                'subject' => 'Chào Mừng Bạn Đến Với Hoàn Tiền Shopee',
                'content' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #ff7a45 0%, #ee4d2d 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <h2 style="color: #ee4d2d; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Hoàn Tiền Shopee</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Chào mừng bạn tham gia cộng đồng của chúng tôi</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 16px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Chúc mừng bạn đã tạo tài khoản thành công tại <strong>Hoàn Tiền Shopee</strong> - Nền tảng cashback mua sắm uy tín hàng đầu Việt Nam.</p>
            <p style="margin: 0 0 24px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Từ bây giờ, bạn có thể bắt đầu mua sắm thông qua link rút gọn để nhận chiết khấu hoa hồng hấp dẫn từ Shopee, đồng thời tham gia xây dựng đội ngũ tiếp thị 2 tầng (F1 và F2) để gia tăng thu nhập thụ động không giới hạn.</p>
            
            <div style="text-align: center; margin: 32px 0;">
                <a href="{dashboard_url}" style="background: linear-gradient(135deg, #ff7a45 0%, #ee4d2d 100%); color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 14px; display: inline-block; box-shadow: 0 4px 12px rgba(238, 77, 45, 0.25);">Bắt Đầu Trải Nghiệm</a>
            </div>
            
            <div style="background-color: #f9fafb; border-radius: 12px; padding: 16px; border: 1px solid #f3f4f6;">
                <h4 style="margin: 0 0 8px 0; font-size: 13px; color: #1f2937; font-weight: bold;">💡 Mẹo nhỏ cho bạn:</h4>
                <p style="margin: 0; font-size: 12px; color: #6b7280; line-height: 1.5;">Đừng quên điểm danh chuyên cần mỗi ngày tại trang thành viên để nhận xu thưởng miễn phí và tích lũy chuỗi ngày để nhận phần thưởng lớn hơn nhé!</p>
            </div>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>'
            ],
            'withdrawal_created' => [
                'subject' => 'Yêu Cầu Rút Tiền Đang Chờ Duyệt - Hoàn Tiền Shopee',
                'content' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #ffb037 0%, #ff8c00 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <h2 style="color: #ff8c00; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Yêu Cầu Rút Tiền</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Hệ thống đang tiến hành xử lý yêu cầu của bạn</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 20px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Yêu cầu rút tiền của bạn đã được ghi nhận trên hệ thống và đang chờ phê duyệt. Số dư tương ứng đã được tạm giữ an toàn trong ví:</p>
            
            <div style="background-color: #f9fafb; border-radius: 12px; border: 1px solid #f3f4f6; padding: 20px; margin: 24px 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280;">Số tiền rút:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right; font-size: 15px;">{amount}đ</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280;">Phương thức nhận:</td>
                        <td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right; text-transform: uppercase;">{payment_method}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280;">Tên tài khoản:</td>
                        <td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right;">{account_name}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280;">Số tài khoản:</td>
                        <td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right; font-family: monospace;">{account_number}</td>
                    </tr>
                    {bank_row}
                </table>
            </div>
            
            <p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.6;">Giao dịch rút tiền thường được phê duyệt và giải ngân trong vòng 12 - 24 giờ làm việc. Bạn sẽ nhận được thông báo ngay khi giao dịch hoàn tất.</p>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>'
            ],
            'withdrawal_approved' => [
                'subject' => 'Yêu Cầu Rút Tiền Thành Công - Hoàn Tiền Shopee',
                'content' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #34d399 0%, #10b981 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <div style="width: 48px; height: 48px; background-color: #ecfdf5; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                <span style="font-size: 24px; color: #10b981;">✓</span>
            </div>
            <h2 style="color: #10b981; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Chuyển Tiền Thành Công</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Giao dịch rút tiền của bạn đã được giải ngân</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 20px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Quản trị viên đã phê duyệt thành công yêu cầu và thực hiện chuyển tiền vào tài khoản thụ hưởng của bạn:</p>
            
            <div style="background-color: #f0fdf4; border-radius: 12px; border: 1px solid #d1fae5; padding: 20px; margin: 24px 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr style="border-bottom: 1px solid #e6f4ea;">
                        <td style="padding: 10px 0; color: #047857;">Số tiền thực nhận:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #065f46; text-align: right; font-size: 16px;">{amount}đ</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e6f4ea;">
                        <td style="padding: 10px 0; color: #047857;">Phương thức nhận:</td>
                        <td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right; text-transform: uppercase;">{payment_method}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e6f4ea;">
                        <td style="padding: 10px 0; color: #047857;">Tên tài khoản:</td>
                        <td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right;">{account_name}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e6f4ea;">
                        <td style="padding: 10px 0; color: #047857;">Số tài khoản:</td>
                        <td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right; font-family: monospace;">{account_number}</td>
                    </tr>
                    {bank_row}
                </table>
            </div>
            
            <p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.6;">Cảm ơn bạn đã tin tưởng sử dụng dịch vụ tích lũy và mua sắm thông minh cùng Hoàn Tiền Shopee.</p>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>'
            ],
            'gift_approved' => [
                'subject' => 'Yêu cầu đổi quà tặng thành công - Hoàn Tiền Shopee',
                'content' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #34d399 0%, #10b981 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <div style="width: 48px; height: 48px; background-color: #ecfdf5; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                <span style="font-size: 24px; color: #10b981;">🎁</span>
            </div>
            <h2 style="color: #10b981; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Nhận Quà Thành Công</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Yêu cầu quy đổi quà tặng đã được hoàn tất</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 20px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Yêu cầu đổi quà tặng từ số dư tích lũy của bạn đã được quản trị viên phê duyệt thành công:</p>
            
            <div style="background-color: #f0fdf4; border-radius: 12px; border: 1px solid #d1fae5; padding: 20px; margin: 24px 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr style="border-bottom: 1px solid #e6f4ea;">
                        <td style="padding: 10px 0; color: #047857; width: 120px;">Tên quà tặng:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right;">{gift_title}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e6f4ea;">
                        <td style="padding: 10px 0; color: #047857;">Số tiền quy đổi:</td>
                        <td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right;">{gift_price}đ</td>
                    </tr>
                    {gift_data_row}
                    {notes_row}
                </table>
            </div>
            
            <p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.6;">Bạn có thể theo dõi chi tiết tình trạng vận chuyển hoặc thông tin bảo hành tại mục lịch sử đổi quà trong trang cá nhân.</p>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>'
            ],
            'gift_created' => [
                'subject' => 'Yêu cầu đổi quà tặng đang chờ duyệt - Hoàn Tiền Shopee',
                'content' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
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
</div>'
            ],
            'cashback_created' => [
                'subject' => 'Đơn Hàng Hoàn Tiền Được Ghi Nhận - Hoàn Tiền Shopee',
                'content' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #ff7a45 0%, #ee4d2d 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <h2 style="color: #ee4d2d; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Ghi Nhận Đơn Hàng</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Hệ thống đã nhận được thông tin đơn hàng của bạn</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 20px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Hệ thống đã ghi nhận thành công đơn hàng hoàn tiền mới của bạn từ Shopee. Chi tiết đơn hàng tạm tính như sau:</p>
            
            <div style="background-color: #f9fafb; border-radius: 12px; border: 1px solid #f3f4f6; padding: 20px; margin: 24px 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280; width: 120px;">Mã đơn hàng:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right; font-family: monospace;">{order_id}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280;">Nền tảng:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right;">{platform}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280;">Sản phẩm:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right; line-height: 1.4;">{product_name}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280;">Giá gốc sản phẩm:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right;">{price}đ</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280;">Tiền hoàn dự kiến:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #ee4d2d; text-align: right; font-size: 15px;">+{cashback_amount}đ</td>
                    </tr>
                </table>
            </div>
            
            <p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.6;">Đơn hàng hiện đang ở trạng thái <strong>Chờ duyệt</strong> (đối soát từ Shopee). Sau khi Shopee hoàn tất thanh toán và đơn hàng được chuyển sang trạng thái hợp lệ, tiền hoàn sẽ được cộng chính thức vào ví khả dụng của bạn.</p>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>'
            ],
            'cashback_approved' => [
                'subject' => 'Đơn Hàng Hoàn Tiền Được Phê Duyệt - Hoàn Tiền Shopee',
                'content' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #10b981 0%, #059669 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <div style="width: 48px; height: 48px; background-color: #ecfdf5; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; margin-left: auto; margin-right: auto;">
                <span style="font-size: 24px; color: #10b981;">🎉</span>
            </div>
            <h2 style="color: #059669; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Đơn Hàng Được Duyệt</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Chúc mừng! Tiền hoàn đã được cộng vào tài khoản của bạn</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 20px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Đơn hàng của bạn đã được đối soát thành công và phê duyệt hoàn tiền. Thông tin chi tiết như sau:</p>
            
            <div style="background-color: #f0fdf4; border-radius: 12px; border: 1px solid #dcfce7; padding: 20px; margin: 24px 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr style="border-bottom: 1px solid #dcfce7;">
                        <td style="padding: 10px 0; color: #15803d; width: 120px;">Mã đơn hàng:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right; font-family: monospace;">{order_id}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #dcfce7;">
                        <td style="padding: 10px 0; color: #15803d;">Nền tảng:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right;">{platform}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #dcfce7;">
                        <td style="padding: 10px 0; color: #15803d;">Sản phẩm:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right; line-height: 1.4;">{product_name}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #dcfce7;">
                        <td style="padding: 10px 0; color: #15803d;">Giá gốc sản phẩm:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right;">{price}đ</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #dcfce7;">
                        <td style="padding: 10px 0; color: #15803d;">Tiền hoàn nhận được:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #10b981; text-align: right; font-size: 15px;">+{cashback_amount}đ</td>
                    </tr>
                </table>
            </div>
            
            <p style="margin: 0 0 20px 0; font-size: 13px; color: #6b7280; line-height: 1.6;">Số tiền hoàn trên đã được cộng chính thức vào ví tích luỹ khả dụng của bạn. Bạn hiện tại đã có thể tạo yêu cầu rút tiền hoặc đổi quà tặng trên hệ thống.</p>
            
            <div style="text-align: center; margin: 24px 0;">
                <a href="{dashboard_url}" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 14px; display: inline-block; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">Kiểm tra ví của bạn</a>
            </div>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>'
            ],
        ];

        if (!isset($defaultTemplates[$templateKey])) {
            throw new \InvalidArgumentException("Email template key [{$templateKey}] is invalid.");
        }

        // Lấy tiêu đề và nội dung từ database (nếu chưa có thì lấy mặc định)
        $subject = self::getVal("email_subject_{$templateKey}", $defaultTemplates[$templateKey]['subject']);
        $content = self::getVal("email_content_{$templateKey}", $defaultTemplates[$templateKey]['content']);

        // Gộp thêm các biến hệ thống mặc định
        $data['year'] = date('Y');
        
        // Hỗ trợ build dòng ngân hàng nếu có bank_name (sử dụng màu sắc trung tính)
        if (isset($data['bank_name']) && !empty($data['bank_name']) && $data['bank_name'] !== 'Ví MoMo') {
            $data['bank_row'] = '<tr style="border-bottom: 1px solid #eaeaea;"><td style="padding: 10px 0; color: #6b7280;">Ngân hàng:</td><td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right;">' . $data['bank_name'] . '</td></tr>';
        } else {
            $data['bank_row'] = '';
        }

        // Hỗ trợ build dòng thông tin quà tặng (mã thẻ/voucher/giftcode) nếu có gift_data (sử dụng màu sắc trung tính)
        if (isset($data['gift_data']) && !empty($data['gift_data'])) {
            $data['gift_data_row'] = '<tr style="border-bottom: 1px solid #eaeaea;"><td style="padding: 10px 0; color: #6b7280;">Thông tin quà tặng:</td><td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right; font-family: monospace; white-space: pre-wrap;">' . nl2br(e($data['gift_data'])) . '</td></tr>';
        } else {
            $data['gift_data_row'] = '';
        }

        // Hỗ trợ build dòng ghi chú / thông tin vận chuyển nếu có notes (sử dụng màu sắc trung tính)
        if (isset($data['notes']) && !empty($data['notes'])) {
            $data['notes_row'] = '<tr style="border-bottom: 1px solid #eaeaea;"><td style="padding: 10px 0; color: #6b7280;">Ghi chú:</td><td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right; font-style: italic;">' . e($data['notes']) . '</td></tr>';
        } else {
            $data['notes_row'] = '';
        }

        // Parse các biến trong tiêu đề và nội dung email
        foreach ($data as $key => $val) {
            $placeholder = "{" . $key . "}";
            $encodedPlaceholderLower = '%7b' . strtolower($key) . '%7d';
            $encodedPlaceholderUpper = '%7B' . strtoupper($key) . '%7D';

            // 1. Thay thế trực tiếp trong văn bản thông thường
            $subject = str_replace($placeholder, (string)$val, $subject);
            $content = str_replace($placeholder, (string)$val, $content);
            $content = str_replace([$encodedPlaceholderLower, $encodedPlaceholderUpper], (string)$val, $content);

            // 2. Tự động phát hiện và dọn dẹp các thuộc tính href bị CKEditor/Trình duyệt tự động chèn tiền tố tương đối
            // (Ví dụ: href="https://domain.com/admin/logs/%7Bdashboard_url%7D" -> href="https://domain.com/dashboard")
            $pattern = '/href=["\'][^"\']*(?:' . preg_quote($placeholder, '/') . '|' . preg_quote($encodedPlaceholderLower, '/') . '|' . preg_quote($encodedPlaceholderUpper, '/') . ')[^"\']*["\']/i';
            $content = preg_replace_callback($pattern, function ($matches) use ($val) {
                return 'href="' . $val . '"';
            }, $content);
        }

        return ['subject' => $subject, 'content' => $content];
    }

    /**
     * Gửi email sử dụng template động cấu hình từ Database.
     *
     * @param string $to Địa chỉ email người nhận
     * @param string $templateKey Khoá của template email trong settings (ví dụ: forgot_password)
     * @param array $data Mảng các biến thay thế trong template (ví dụ: ['name' => 'Nguyễn Văn A', 'otp' => '123456'])
     * @return bool Trả về true nếu gửi thành công, false nếu thất bại
     */
    public static function sendEmail(?string $to, string $templateKey, array $data = []): bool
    {
        // Thành viên đăng ký bằng Số điện thoại có thể chưa cập nhật email, khi đó bỏ qua việc gửi thư
        if (empty(trim((string) $to))) {
            return false;
        }

        // Kiểm tra xem tính năng gửi Email (SMTP) có được bật hay không
        // Nếu admin tắt (smtp_status = 0), hệ thống sẽ ngưng gửi email để tránh lỗi kết nối hoặc tiết kiệm tài nguyên
        if (self::getVal('smtp_status', '1') !== '1') {
            return false;
        }

        // Kiểm tra trạng thái hoạt động của mẫu email cụ thể này (mặc định là bật '1')
        // Nếu admin chọn tắt (email_status_{templateKey} = 0), hệ thống sẽ từ chối gửi để tránh gửi spam hoặc theo nhu cầu vận hành
        if (self::getVal("email_status_{$templateKey}", '1') !== '1') {
            return false;
        }

        try {
            $parsed = self::parseEmailContent($templateKey, $data);
            
            // Sử dụng view Blade 'emails.dynamic'
            \Illuminate\Support\Facades\Mail::send('emails.dynamic', ['content' => $parsed['content']], function ($message) use ($to, $parsed) {
                $message->to($to);
                $message->subject($parsed['subject']);
            });
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Lỗi gửi Email Template [{$templateKey}] tới [{$to}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Đưa email vào hàng đợi gửi đi (Custom Database Queue) để gửi không đồng bộ.
     *
     * @param string $to Địa chỉ email người nhận
     * @param string $templateKey Khoá của template email trong settings (ví dụ: forgot_password)
     * @param array $data Mảng các biến thay thế trong template
     * @return bool Trả về true nếu đưa vào hàng đợi thành công, false nếu thất bại
     */
    public static function sendEmailQueue(?string $to, string $templateKey, array $data = []): bool
    {
        // Thành viên đăng ký bằng Số điện thoại có thể chưa cập nhật email, khi đó bỏ qua việc đưa vào hàng đợi
        if (empty(trim((string) $to))) {
            return false;
        }

        // Kiểm tra xem tính năng gửi Email (SMTP) có được bật hay không
        // Nếu admin tắt, hệ thống không cần đưa email vào hàng đợi để giảm dung lượng lưu trữ DB không cần thiết
        if (self::getVal('smtp_status', '1') !== '1') {
            return false;
        }

        // Kiểm tra trạng thái hoạt động của mẫu email cụ thể này (mặc định là bật '1')
        // Nếu admin chọn tắt (email_status_{templateKey} = 0), hệ thống sẽ từ chối đưa email vào hàng đợi
        if (self::getVal("email_status_{$templateKey}", '1') !== '1') {
            return false;
        }

        try {
            $parsed = self::parseEmailContent($templateKey, $data);
            
            \App\Models\EmailQueue::create([
                'to_email' => $to,
                'to_name' => $data['name'] ?? '',
                'subject' => $parsed['subject'],
                'body' => $parsed['content'],
                'status' => 'pending',
            ]);
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Lỗi thêm Email Template [{$templateKey}] vào hàng đợi tới [{$to}]: " . $e->getMessage());
            return false;
        }
    }
}
