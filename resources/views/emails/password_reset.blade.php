<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt Lại Mật Khẩu</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f5; margin: 0; padding: 20px; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-top: 4px solid #f97316;">
        <h2 style="color: #f97316; margin-top: 0; text-align: center;">Đặt Lại Mật Khẩu</h2>
        <p>Xin chào <strong>{{ $user->name }}</strong>,</p>
        <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu từ tài khoản của bạn trên hệ thống <strong>Hoàn Tiền Shopee</strong>.</p>
        <p>Vui lòng nhấn vào nút bên dưới để tiến hành thiết lập mật khẩu mới (Liên kết này có hiệu lực trong vòng 60 phút):</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $resetUrl }}" style="background-color: #f97316; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Đặt Lại Mật Khẩu</a>
        </div>
        <p>Nếu bạn không gửi yêu cầu này, vui lòng bỏ qua email này hoặc liên hệ với chúng tôi để được hỗ trợ.</p>
        <hr style="border: 0; border-top: 1px solid #e4e4e7; margin: 30px 0;">
        <p style="font-size: 12px; color: #71717a; text-align: center;">Đây là email tự động từ hệ thống Hoàn Tiền Shopee. Vui lòng không trả lời thư này.</p>
    </div>
</body>
</html>
