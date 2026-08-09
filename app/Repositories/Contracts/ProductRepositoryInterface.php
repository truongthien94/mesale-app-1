<?php

namespace App\Repositories\Contracts;

use App\Models\Product;

interface ProductRepositoryInterface
{
    /**
     * Tìm sản phẩm trong hệ thống dựa trên Shopee ID hoặc URL.
     *
     * @param string $shopeeId
     * @return Product|null
     */
    public function findByShopeeId(string $shopeeId): ?Product;

    /**
     * Tạo mới hoặc cập nhật thông tin sản phẩm đã tra cứu.
     *
     * Quy tắc nghiệp vụ: Chỉ lưu cache những sản phẩm có đầy đủ thông tin (tên, giá bán, link affiliate).
     * Nếu dữ liệu bị thiếu (ví dụ không lấy được giá bán), hàm trả về null và KHÔNG ghi vào cache.
     *
     * @param array $data Dữ liệu sản phẩm nhận về từ API
     * @return Product|null Bản ghi cache đã lưu, hoặc null nếu dữ liệu thiếu thông tin nên bị bỏ qua
     */
    public function createOrUpdate(array $data): ?Product;

    /**
     * Tăng số lượt tìm kiếm/tra cứu sản phẩm đó.
     *
     * @param Product $product
     * @return void
     */
    public function incrementSearchCount(Product $product): void;
}
