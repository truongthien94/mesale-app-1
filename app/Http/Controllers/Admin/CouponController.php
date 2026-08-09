<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Controller quản trị Mã giảm giá: cho phép admin tự thêm/sửa/xóa mã giảm giá theo ý muốn,
 * thay vì phụ thuộc hoàn toàn vào dữ liệu đồng bộ tự động từ API.
 *
 * Mọi mã do admin thao tác (thêm/sửa) sẽ được gán source = 'manual' để lệnh đồng bộ
 * `coupons:sync` bỏ qua, tránh việc dữ liệu API ghi đè lên mã tự nhập.
 */
class CouponController extends Controller
{
    /**
     * Nguồn đánh dấu mã giảm giá do admin tự tạo/chỉnh sửa thủ công.
     */
    public const SOURCE_MANUAL = 'manual';

    /**
     * Danh sách các sàn được hỗ trợ khi nhập tay.
     */
    public const PLATFORMS = ['shopee', 'lazada', 'tiktok', 'other'];

    /**
     * Trang danh sách mã giảm giá kèm bộ lọc, tìm kiếm và thống kê nhanh.
     */
    public function index(Request $request)
    {
        // Số dòng hiển thị động (tối đa 500 dòng)
        $limit = $request->integer('limit', 20);
        if ($limit < 1 || $limit > 500) {
            $limit = 20;
        }

        $query = Coupon::query()->orderBy('id', 'desc');

        // Tìm kiếm theo mã, tiêu đề hoặc mô tả
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Lọc theo sàn
        if ($request->filled('platform') && $request->platform !== 'all') {
            $query->where('platform', $request->platform);
        }

        // Lọc theo trạng thái logic
        if ($request->filled('state')) {
            switch ($request->state) {
                case 'active':
                    $query->where(function ($q) {
                        $q->whereNull('expired_at')->orWhere('expired_at', '>=', now());
                    });
                    break;
                case 'expired':
                    $query->whereNotNull('expired_at')->where('expired_at', '<', now());
                    break;
                case 'manual':
                    $query->where('source', self::SOURCE_MANUAL);
                    break;
                case 'api':
                    $query->where(function ($q) {
                        $q->whereNull('source')->orWhere('source', '!=', self::SOURCE_MANUAL);
                    });
                    break;
            }
        }

        $coupons = $query->paginate($limit)->withQueryString();

        // Thống kê tổng quan cho các thẻ KPI
        $stats = [
            'total'   => Coupon::count(),
            'active'  => Coupon::where(function ($q) {
                            $q->whereNull('expired_at')->orWhere('expired_at', '>=', now());
                        })->count(),
            'manual'  => Coupon::where('source', self::SOURCE_MANUAL)->count(),
            'expired' => Coupon::whereNotNull('expired_at')->where('expired_at', '<', now())->count(),
        ];

        // Danh sách sàn hiện có để làm gợi ý bộ lọc
        $platforms = Coupon::select('platform')->distinct()->pluck('platform')->filter()->values()->toArray();

        return view('admin.coupons.index', compact('coupons', 'stats', 'platforms'));
    }

    /**
     * Xác thực và chuẩn hóa dữ liệu form dùng chung cho thêm và sửa.
     *
     * @param Coupon|null $coupon Bản ghi đang sửa (null nếu thêm mới) để bỏ qua chính nó khi check unique.
     */
    private function validateData(Request $request, ?Coupon $coupon = null): array
    {
        // Chuẩn hóa mã: bỏ khoảng trắng thừa (giữ nguyên hoa/thường vì mã Shopee phân biệt hoa thường).
        // Chuyển chuỗi rỗng về null để quy tắc 'nullable' bỏ qua các kiểm tra tiếp theo (cho phép tạo banner không mã).
        $normalizedCode = trim(str_replace(' ', '', (string) $request->input('code')));
        $request->merge([
            'code' => $normalizedCode === '' ? null : $normalizedCode,
            'platform' => $request->input('platform', 'shopee'),
        ]);

        $validated = $request->validate([
            'platform' => ['required', Rule::in(self::PLATFORMS)],
            'code' => [
                // Chỉ cho phép chữ, số, gạch dưới, gạch ngang và dấu chấm.
                // Bảo mật: chặn các ký tự ' " < > ( ) ; ... nhằm ngăn stored XSS khi mã được nhúng
                // vào ngữ cảnh JavaScript ở trang mã giảm giá công khai.
                'nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_.\-]+$/',
                Rule::unique('coupons', 'code')
                    ->where(fn ($q) => $q->where('platform', $request->input('platform', 'shopee')))
                    ->ignore($coupon?->id),
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'category' => 'nullable|string|max:255',
            // Giới hạn tối đa 2 tỷ để không vượt phạm vi cột INT (tối đa ~2,147,483,647) gây lỗi ghi DB.
            'min_spend' => 'nullable|integer|min:0|max:2000000000',
            'discount_amount' => 'nullable|integer|min:0|max:2000000000',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'expired_at' => 'nullable|date',
            // Bảo mật: chỉ cho phép scheme http/https, chặn javascript:, data:...
            'redirect_link' => 'nullable|url:http,https|max:2000',
            'image_url' => 'nullable|url:http,https|max:2000',
        ], [
            'title.required' => __('Vui lòng nhập tiêu đề mã giảm giá.'),
            'min_spend.max' => __('Giá trị đơn tối thiểu quá lớn (tối đa 2 tỷ).'),
            'discount_amount.max' => __('Số tiền giảm quá lớn (tối đa 2 tỷ).'),
            'code.regex' => __('Mã giảm giá chỉ được chứa chữ, số, dấu gạch ngang, gạch dưới hoặc dấu chấm.'),
            'code.unique' => __('Mã giảm giá này đã tồn tại trên cùng một sàn.'),
            'redirect_link.url' => __('Đường dẫn chuyển hướng không hợp lệ (chỉ chấp nhận http/https).'),
            'image_url.url' => __('Đường dẫn ảnh không hợp lệ (chỉ chấp nhận http/https).'),
        ]);

        // Nếu admin không nhập mã, tự sinh mã dạng BANNER_ để frontend hiển thị như một banner ưu đãi (không có mã copy).
        // Khi đang sửa một banner cũ, giữ nguyên mã cũ để tránh đổi mã mỗi lần lưu.
        $code = $validated['code'] ?? '';
        if ($code === '') {
            if ($coupon && str_starts_with($coupon->code, 'BANNER_')) {
                $code = $coupon->code;
            } else {
                $code = 'BANNER_' . substr(md5(($validated['title'] ?? '') . ($validated['redirect_link'] ?? '') . microtime()), 0, 10);
            }
        }

        return [
            'platform' => $validated['platform'],
            'code' => $code,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? null,
            'min_spend' => (int) ($validated['min_spend'] ?? 0),
            'discount_amount' => (int) ($validated['discount_amount'] ?? 0),
            'discount_percentage' => (int) ($validated['discount_percentage'] ?? 0),
            'expired_at' => !empty($validated['expired_at']) ? date('Y-m-d H:i:s', strtotime($validated['expired_at'])) : null,
            'redirect_link' => $validated['redirect_link'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            // Đánh dấu là mã thủ công để không bị đồng bộ API ghi đè
            'source' => self::SOURCE_MANUAL,
        ];
    }

    /**
     * Tạo mới một mã giảm giá thủ công.
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $coupon = Coupon::create($data);

        ActivityLog::log("Thêm mã giảm giá thủ công: {$coupon->code} ({$coupon->title})", auth()->id());

        return redirect()->route('admin.coupons.index')
            ->with('success', __('Thêm mã giảm giá thành công!'));
    }

    /**
     * Cập nhật một mã giảm giá.
     */
    public function update(Request $request, Coupon $coupon)
    {
        $data = $this->validateData($request, $coupon);

        $coupon->update($data);

        ActivityLog::log("Cập nhật mã giảm giá: {$coupon->code} ({$coupon->title})", auth()->id());

        return redirect()->route('admin.coupons.index')
            ->with('success', __('Cập nhật mã giảm giá thành công!'));
    }

    /**
     * Xóa một mã giảm giá.
     */
    public function destroy(Coupon $coupon)
    {
        $code = $coupon->code;
        $coupon->delete();

        ActivityLog::log("Xóa mã giảm giá: {$code}", auth()->id());

        return redirect()->route('admin.coupons.index')
            ->with('success', __('Xóa mã giảm giá thành công!'));
    }

    /**
     * Xóa hàng loạt các mã được chọn.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|string',
            'confirm_text' => 'required|string',
        ]);

        if (mb_strtoupper(trim($request->confirm_text)) !== 'XÓA HÀNG LOẠT') {
            return response()->json(['status' => 'error', 'message' => __('Từ khóa xác nhận không chính xác.')]);
        }

        $ids = array_filter(array_map('intval', explode(',', $request->ids)));
        if (empty($ids)) {
            return response()->json(['status' => 'error', 'message' => __('Không có mã nào hợp lệ để xóa.')]);
        }

        DB::transaction(function () use ($ids) {
            $coupons = Coupon::whereIn('id', $ids)->lockForUpdate()->get();
            foreach ($coupons as $coupon) {
                $codeText = $coupon->code;
                $coupon->delete();
                ActivityLog::log(__("Xóa mã giảm giá (Hành động hàng loạt): :code", ['code' => $codeText]), auth()->id());
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => __('Đã xóa thành công :count mã giảm giá được chọn.', ['count' => count($ids)]),
        ]);
    }
}
