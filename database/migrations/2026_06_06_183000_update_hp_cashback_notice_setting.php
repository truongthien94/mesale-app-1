<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Nội dung lưu ý mặc định hiển thị ở kết quả tìm kiếm
        $defaultValue = "Tiền hoàn hiển thị là tạm tính. Số tiền thực nhận sẽ được ghi nhận theo giá trị đơn hàng sau khi trừ voucher và mã giảm giá.\n\nNếu mua nhiều sản phẩm trong cùng một đơn, tiền hoàn sẽ được nhân lên theo số lượng sản phẩm đủ điều kiện. Một số ngành hàng Shopee có thể giới hạn tối đa 50K/đơn, nên với đơn lớn bạn có thể tách đơn hoặc nhắn hỗ trợ để được tư vấn.";
        
        $setting = Setting::where('key', 'hp_cashback_notice')->first();
        
        // Chỉ tự động nhập nếu cấu hình chưa tồn tại hoặc đang bị rỗng
        if (!$setting || empty(trim($setting->value))) {
            Setting::setVal('hp_cashback_notice', $defaultValue, 'Nội dung lưu ý hiển thị ở phần kết quả tìm kiếm sản phẩm trang chủ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không thực hiện gì khi rollback để bảo toàn dữ liệu cấu hình
    }
};
