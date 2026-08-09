<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\Log;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Tìm sản phẩm theo Shopee ID trong bảng products.
     *
     * @param string $shopeeId
     * @return Product|null
     */
    public function findByShopeeId(string $shopeeId): ?Product
    {
        // Tra cứu sản phẩm trong cơ sở dữ liệu dựa trên mã Shopee ID duy nhất
        return Product::where('shopee_id', $shopeeId)->first();
    }

    /**
     * Kiểm tra dữ liệu sản phẩm lấy về từ sàn có đầy đủ thông tin để lưu cache hay không.
     *
     * Quy tắc nghiệp vụ: Các lượt lấy link bị thiếu thông tin quan trọng (thường gặp nhất là không lấy
     * được giá bán do API lỗi hoặc phải fallback sang cào HTML) sẽ KHÔNG được ghi vào bảng cache products.
     * Việc này tránh cho hệ thống "đóng băng" một bản ghi rác: những lượt tra cứu sau sẽ đọc lại đúng
     * bản ghi thiếu giá đó thay vì gọi lại API để lấy dữ liệu đầy đủ.
     *
     * @param array $data Dữ liệu sản phẩm chuẩn bị ghi vào cache
     * @return bool True nếu dữ liệu đầy đủ và được phép lưu cache
     */
    public static function hasCompleteInfo(array $data): bool
    {
        // Bắt buộc phải có tên sản phẩm
        if (trim((string)($data['name'] ?? '')) === '') {
            return false;
        }

        // Bắt buộc phải có giá bán hợp lệ (lớn hơn 0)
        if ((float)($data['price'] ?? 0) <= 0) {
            return false;
        }

        // Bắt buộc phải có link affiliate, nếu không bản ghi cache sẽ vô dụng ở các lượt tra cứu sau
        if (trim((string)($data['affiliate_url'] ?? '')) === '') {
            return false;
        }

        return true;
    }

    /**
     * Tạo hoặc cập nhật thông tin sản phẩm trong bảng products.
     *
     * @param array $data Dữ liệu sản phẩm nhận về từ API
     * @return Product|null Trả về null khi dữ liệu thiếu thông tin nên bị bỏ qua không lưu cache
     */
    public function createOrUpdate(array $data): ?Product
    {
        // Bỏ qua hoàn toàn (không tạo mới và cũng không ghi đè bản ghi cũ đang có dữ liệu tốt)
        // đối với các sản phẩm lấy link bị thiếu thông tin như giá bán
        if (!self::hasCompleteInfo($data)) {
            Log::warning('Bỏ qua lưu cache sản phẩm do thiếu thông tin (giá bán/tên/link affiliate): ' . ($data['shopee_id'] ?? 'N/A'));
            return null;
        }

        // Sử dụng updateOrCreate để tránh trùng lặp dữ liệu và đồng bộ thông tin mới nhất
        return Product::updateOrCreate(
            ['shopee_id' => $data['shopee_id']],
            [
                'name' => $data['name'],
                'image' => $data['image'],
                'price' => $data['price'],
                'commission_amount' => $data['commission_amount'],
                'shopee_commission' => $data['shopee_commission'] ?? 0,
                'cashback_amount' => $data['cashback_amount'],
                'cashback_rate' => $data['cashback_rate'],
                'affiliate_url' => $data['affiliate_url'],
            ]
        );
    }

    /**
     * Tăng số lượt tra cứu của sản phẩm đó.
     *
     * @param Product $product
     * @return void
     */
    public function incrementSearchCount(Product $product): void
    {
        // Tăng trường search_count lên 1 đơn vị
        $product->increment('search_count');
    }
}
