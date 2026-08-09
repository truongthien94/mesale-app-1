<?php

namespace Database\Seeders;

use App\Models\Gift;
use Illuminate\Database\Seeder;

class GiftSeeder extends Seeder
{
    /**
     * Chạy dữ liệu seeding quà tặng mẫu.
     */
    public function run(): void
    {
        // Để trống danh sách quà tặng theo yêu cầu cập nhật mới
        $gifts = [
            // [
            //     'title' => 'Thẻ cào Viettel 50,000đ',
            //     'description' => 'Mã thẻ cào điện thoại mạng Viettel mệnh giá 50,000đ. Hệ thống sẽ tự động gửi mã thẻ và số seri sau khi được duyệt.',
            //     'price' => 50000,
            //     'stock' => 10,
            //     'type' => 'phone_card',
            //     'tag' => 'Thẻ cào',
            //     'image' => 'https://res.cloudinary.com/cmsnt/image/upload/v1700000000/viettel_card.png',
            //     'status' => true,
            // ],
            // [
            //     'title' => 'Thẻ game Garena 100,000đ',
            //     'description' => 'Thẻ nạp game Garena dùng để quy đổi quân huy, sò, kim cương mệnh giá 100,000đ.',
            //     'price' => 100000,
            //     'stock' => 5,
            //     'type' => 'giftcode',
            //     'tag' => 'Thẻ game',
            //     'image' => 'https://res.cloudinary.com/cmsnt/image/upload/v1700000000/garena_card.png',
            //     'status' => true,
            // ],
            // [
            //     'title' => 'Voucher giảm giá Shopee 50k',
            //     'description' => 'Voucher giảm giá 50,000đ áp dụng cho mọi đơn hàng Shopee khi thanh toán qua link affiliate của hệ thống.',
            //     'price' => 45000,
            //     'stock' => 20,
            //     'type' => 'voucher',
            //     'tag' => 'Mã giảm giá',
            //     'image' => 'https://res.cloudinary.com/cmsnt/image/upload/v1700000000/shopee_voucher.png',
            //     'status' => true,
            // ],
            // [
            //     'title' => 'Bình giữ nhiệt Shopee Inox 304 cao cấp',
            //     'description' => 'Bình giữ nhiệt chất liệu Inox 304 cao cấp dung tích 500ml màu cam Shopee cực đẹp. Quà tặng vật lý sẽ được giao tận nhà trong 2-4 ngày làm việc.',
            //     'price' => 120000,
            //     'stock' => 3,
            //     'type' => 'physical',
            //     'tag' => 'Vật phẩm',
            //     'image' => 'https://res.cloudinary.com/cmsnt/image/upload/v1700000000/shopee_flask.png',
            //     'status' => true,
            // ],
        ];

        foreach ($gifts as $gift) {
            Gift::updateOrCreate(['title' => $gift['title']], $gift);
        }
    }
}
