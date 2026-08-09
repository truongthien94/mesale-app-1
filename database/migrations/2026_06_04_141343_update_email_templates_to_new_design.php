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
        $templates = [
            'email_content_forgot_password' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
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
</div>',
            'email_content_otp' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
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
</div>',
            'email_content_welcome' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
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
</div>',
            'email_content_withdrawal_created' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
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
</div>',
            'email_content_withdrawal_approved' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
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
</div>',
            'email_content_gift_approved' => '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
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
</div>',
        ];

        foreach ($templates as $key => $content) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $content]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không cần reverse vì chỉ cập nhật dữ liệu mẫu
    }
};
