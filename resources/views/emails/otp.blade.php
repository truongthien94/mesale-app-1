<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Mã Xác Thực OTP</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f5; margin: 0; padding: 20px; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-top: 4px solid #f97316;">
        <h2 style="color: #f97316; margin-top: 0; text-align: center;">Mã Xác Thực OTP</h2>
        <!-- Gửi lời chào cá nhân hóa cho người dùng -->
        <p>Xin chào <strong>{{ $user->name }}</strong>,</p>
        <p>Hệ thống <strong>Hoàn Tiền Shopee</strong> đã nhận được yêu cầu xác thực bảo mật từ tài khoản của bạn.</p>
        <p>Vui lòng sử dụng mã OTP dưới đây để hoàn tất quá trình xác thực (Mã này có hiệu lực trong vòng 10 phút):</p>
        
        <!-- Vùng hiển thị mã OTP nổi bật, dễ sao chép -->
        <div style="text-align: center; margin: 30px 0; background-color: #fef3c7; border: 1px dashed #f59e0b; padding: 15px; border-radius: 6px;">
            <span style="font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #d97706;">{{ $otp }}</span>
        </div>
        
        <p style="color: #ef4444; font-size: 13px; font-weight: bold;">Lưu ý: Tuyệt đối không chia sẻ mã này cho bất kỳ ai để bảo vệ tài khoản của bạn.</p>
        <p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email hoặc đổi mật khẩu ngay lập tức.</p>
        <hr style="border: 0; border-top: 1px solid #e4e4e7; margin: 30px 0;">
        <p style="font-size: 12px; color: #71717a; text-align: center;">Đây là email tự động từ hệ thống Hoàn Tiền Shopee. Vui lòng không trả lời thư này.</p>
    </div>
</body>
</html>
