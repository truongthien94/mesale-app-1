<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Models\Setting;

/**
 * Controller xử lý hiển thị danh sách mã giảm giá ở frontend cho khách hàng.
 */
class CouponController extends Controller
{
    /**
     * Hiển thị danh sách mã giảm giá đa sàn.
     * Giải thích: 
     * - Kiểm tra cấu hình ON/OFF của chức năng từ Admin. Nếu tắt, trả về trang 404 để bảo mật và ngăn truy cập.
     * - Lấy danh sách mã giảm giá đang hoạt động (chưa hết hạn hoặc expired_at là null).
     * - Hỗ trợ lọc theo sàn (platform) và tìm kiếm từ khoá.
     */
    public function index(Request $request)
    {
        // 1. Kiểm tra trạng thái ON/OFF của chức năng
        $status = Setting::getVal('coupon_status', '1');
        if ($status !== '1') {
            abort(404, __('Chức năng mã giảm giá hiện tại đang tạm khóa.'));
        }

        // 2. Xây dựng truy vấn lấy mã giảm giá chưa hết hạn của sàn Shopee
        $query = Coupon::query()
            ->where('platform', 'shopee')
            ->where(function ($q) {
                $q->whereNull('expired_at')
                  ->orWhere('expired_at', '>=', now());
            });

        // Lọc theo sàn (Platform)
        if ($request->has('platform') && $request->platform !== 'all') {
            $query->where('platform', $request->platform);
        }

        // Lọc theo danh mục (Category)
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Lọc theo từ khóa tìm kiếm (Code hoặc Title)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sắp xếp mã giảm giá mới nhất và phân trang
        $coupons = $query->orderBy('created_at', 'desc')->paginate(12);

        // Lấy danh sách các sàn hiện có để làm bộ lọc ngoài frontend
        $platforms = Coupon::select('platform')
            ->distinct()
            ->pluck('platform')
            ->toArray();

        // Lấy danh sách các danh mục độc nhất của sàn Shopee để làm thanh trượt lọc danh mục
        $categories = Coupon::where('platform', 'shopee')
            ->where(function ($q) {
                $q->whereNull('expired_at')
                  ->orWhere('expired_at', '>=', now());
            })
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->toArray();

        return view('coupons.index', compact('coupons', 'platforms', 'categories'));
    }
}
