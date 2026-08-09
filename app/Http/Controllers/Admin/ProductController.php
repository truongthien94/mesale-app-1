<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Danh sách các mốc thời gian được phép chọn khi dọn dẹp sản phẩm.
     * Giải thích nghiệp vụ:
     * - Bảng `products` bản chất chỉ là bộ nhớ đệm (cache) thông tin sản phẩm lấy về từ API các sàn
     *   thương mại điện tử. Dữ liệu càng để lâu thì giá bán và tỷ lệ hoa hồng càng sai lệch so với
     *   thực tế, đồng thời làm phình dung lượng cơ sở dữ liệu, nên cần được dọn dẹp định kỳ.
     * - Mảng này được dùng chung cho cả việc đếm số lượng hiển thị trong modal và việc thực thi xóa,
     *   nhờ đó con số quản trị viên nhìn thấy trước khi xác nhận luôn khớp với con số thực tế bị xóa.
     * - Mốc `all` có `days = null`, nghĩa là xóa sạch toàn bộ bảng sản phẩm mà không xét thời gian.
     */
    private function cleanupPeriods(): array
    {
        return [
            '1_day'    => ['days' => 1,    'label' => 'Cũ hơn 1 ngày'],
            '7_days'   => ['days' => 7,    'label' => 'Cũ hơn 7 ngày'],
            '30_days'  => ['days' => 30,   'label' => 'Cũ hơn 30 ngày'],
            '90_days'  => ['days' => 90,   'label' => 'Cũ hơn 90 ngày'],
            '180_days' => ['days' => 180,  'label' => 'Cũ hơn 6 tháng'],
            '365_days' => ['days' => 365,  'label' => 'Cũ hơn 1 năm'],
            'all'      => ['days' => null, 'label' => 'Toàn bộ sản phẩm'],
        ];
    }

    /**
     * Hiển thị danh sách sản phẩm shopee đã được lưu trong hệ thống.
     * Hỗ trợ tìm kiếm theo tên hoặc Shopee ID và phân trang.
     * Bổ sung tính năng thống kê số lượt click lấy link sản phẩm hôm nay, tuần này, tháng này.
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // Tìm kiếm theo tên sản phẩm
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Tìm kiếm theo Shopee ID
        if ($request->has('shopee_id') && !empty($request->shopee_id)) {
            $query->where('shopee_id', 'like', '%' . $request->shopee_id . '%');
        }

        // Tìm kiếm theo URL sản phẩm (affiliate_url)
        if ($request->has('url') && !empty($request->url)) {
            $query->where('affiliate_url', 'like', '%' . $request->url . '%');
        }

        // Cấu hình số dòng hiển thị động (limit), tối đa 500 dòng
        $limit = $request->integer('limit', 15);
        if ($limit < 1 || $limit > 500) {
            $limit = 15;
        }

        // Sắp xếp theo thời gian tạo: sản phẩm mới nhất luôn hiển thị trước
        // Dùng thêm cột id giảm dần để đảm bảo thứ tự ổn định khi nhiều sản phẩm
        // được tạo trong cùng một thời điểm (cùng giá trị created_at)
        $products = $query->orderBy('created_at', 'desc')
                          ->orderBy('id', 'desc')
                          ->paginate($limit)
                          ->withQueryString();

        /*
         * Business Rule: Thống kê số lượt lấy link sản phẩm (click hoàn tiền)
         * giúp quản trị viên nắm bắt nhanh hiệu quả tiếp thị liên kết và nhu cầu của thành viên
         * theo các mốc thời gian: hôm nay, tuần này và tháng này.
         */
        $todayClicks = \App\Models\CashbackClick::whereDate('created_at', \Carbon\Carbon::today())->count();
        $thisWeekClicks = \App\Models\CashbackClick::where('created_at', '>=', \Carbon\Carbon::now()->startOfWeek())->count();
        $thisMonthClicks = \App\Models\CashbackClick::where('created_at', '>=', \Carbon\Carbon::now()->startOfMonth())->count();

        /*
         * Business Rule: Đếm trước số lượng sản phẩm sẽ bị xóa ở từng mốc thời gian
         * để modal "Dọn dẹp" hiển thị con số trực quan ngay cạnh mỗi lựa chọn, giúp quản trị viên
         * cân nhắc chính xác trước khi xác nhận một thao tác xóa không thể hoàn tác.
         * Tối ưu hiệu năng: gộp toàn bộ các mốc vào MỘT truy vấn duy nhất bằng SUM(CASE WHEN...)
         * thay vì chạy nhiều câu lệnh COUNT riêng lẻ cho từng mốc thời gian.
         */
        $cleanupPeriods = $this->cleanupPeriods();
        $countExpressions = [];
        $countBindings = [];

        foreach ($cleanupPeriods as $key => $config) {
            // Mốc "toàn bộ sản phẩm" không có điều kiện thời gian nên được xử lý riêng bên dưới
            if ($config['days'] === null) {
                continue;
            }

            $countExpressions[] = "SUM(CASE WHEN created_at < ? THEN 1 ELSE 0 END) as period_{$key}";
            $countBindings[] = \Carbon\Carbon::now()->subDays($config['days'])->toDateTimeString();
        }

        // Luôn đặt COUNT(*) ở cuối câu lệnh vì biểu thức này không sử dụng tham số binding,
        // bảo đảm thứ tự các dấu ? luôn khớp tuyệt đối với thứ tự mảng $countBindings
        $countExpressions[] = 'COUNT(*) as period_all';

        $countRow = Product::selectRaw(implode(', ', $countExpressions), $countBindings)->first();

        // Chuyển kết quả truy vấn thành mảng số nguyên theo từng khóa mốc thời gian
        // (hàm SUM trả về NULL khi bảng rỗng nên cần ép kiểu về 0)
        $cleanupCounts = [];
        foreach ($cleanupPeriods as $key => $config) {
            $cleanupCounts[$key] = (int) ($countRow->{'period_' . $key} ?? 0);
        }

        return view('admin.products.index', compact(
            'products',
            'todayClicks',
            'thisWeekClicks',
            'thisMonthClicks',
            'cleanupPeriods',
            'cleanupCounts'
        ));
    }

    /**
     * API thống kê lượt lấy link sản phẩm (cashback_clicks) theo thời gian.
     * Giải thích nghiệp vụ:
     * - Trả về dữ liệu JSON gồm: số lượt click theo ngày/tháng, top sản phẩm được tìm kiếm nhiều nhất và tổng số lượt click.
     * - Giúp admin phân tích xu hướng mua sắm của thành viên để có chiến lược tiếp thị phù hợp.
     * - Hỗ trợ các mốc thời gian: tuần, tháng, năm.
     */
    public function productStats(Request $request)
    {
        // Nhận tham số period, mặc định là week
        $period = $request->input('period', 'week');
        if (!in_array($period, ['week', 'month', 'year'])) {
            $period = 'week';
        }

        // Xác định khoảng thời gian và format gom nhóm ngày/tháng trong MySQL
        if ($period === 'week') {
            $startDate = \Carbon\Carbon::now()->subDays(6)->startOfDay();
            $groupFormat = '%Y-%m-%d';
            $labelFormat = 'd/m';
        } elseif ($period === 'month') {
            $startDate = \Carbon\Carbon::now()->subDays(29)->startOfDay();
            $groupFormat = '%Y-%m-%d';
            $labelFormat = 'd/m';
        } else {
            $startDate = \Carbon\Carbon::now()->subMonths(11)->startOfMonth();
            $groupFormat = '%Y-%m';
            $labelFormat = null;
        }

        // Truy vấn số lượng click theo ngày/tháng
        $clickData = \App\Models\CashbackClick::where('created_at', '>=', $startDate)
            ->select(
                \Illuminate\Support\Facades\DB::raw("DATE_FORMAT(created_at, '{$groupFormat}') as date_key"),
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as click_count')
            )
            ->groupBy('date_key')
            ->pluck('click_count', 'date_key')
            ->toArray();

        // Tạo mảng labels và datasets cho Chart.js
        $labels = [];
        $clicks = [];

        if ($period === 'year') {
            // Duyệt 12 tháng gần nhất
            for ($i = 11; $i >= 0; $i--) {
                $date = \Carbon\Carbon::now()->subMonths($i);
                $key = $date->format('Y-m');
                $labels[] = __('Tháng') . ' ' . $date->format('m/Y');
                $clicks[] = (int)($clickData[$key] ?? 0);
            }
        } else {
            // Duyệt từng ngày
            $totalDays = $period === 'week' ? 6 : 29;
            for ($i = $totalDays; $i >= 0; $i--) {
                $date = \Carbon\Carbon::now()->subDays($i);
                $key = $date->format('Y-m-d');
                $labels[] = $date->format($labelFormat);
                $clicks[] = (int)($clickData[$key] ?? 0);
            }
        }

        // Tính tổng lượt click trong thời gian này
        $totalClicks = array_sum($clicks);

        // Lấy số người dùng khác nhau đã lấy link trong thời gian này
        $totalUsers = \App\Models\CashbackClick::where('created_at', '>=', $startDate)
            ->distinct('user_id')
            ->count('user_id');

        // Lấy top 5 sản phẩm được lấy link nhiều nhất trong khoảng thời gian này
        $topProducts = \App\Models\CashbackClick::where('created_at', '>=', $startDate)
            ->select('product_name', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_clicks'))
            ->groupBy('product_name')
            ->orderByDesc('total_clicks')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'labels' => $labels,
            'clicks' => $clicks,
            'totals' => [
                'clicks' => $totalClicks,
                'users' => $totalUsers,
            ],
            'top_products' => $topProducts,
            'period' => $period,
        ]);
    }

    /**
     * Xóa sản phẩm khỏi cơ sở dữ liệu.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $productName = $product->name;
        
        $product->delete();

        // Ghi lại nhật ký hoạt động của admin
        ActivityLog::log("Xóa sản phẩm khỏi hệ thống: {$productName}", auth()->id());

        return back()->with('success', __('Đã xóa sản phẩm thành công!'));
    }

    /**
     * Dọn dẹp hàng loạt sản phẩm cache theo mốc thời gian lưu trữ được chọn trong modal "Dọn dẹp".
     * Quy tắc nghiệp vụ:
     * - Mốc thời gian bắt buộc phải nằm trong danh sách whitelist tại hàm cleanupPeriods() để
     *   chặn hoàn toàn khả năng giả mạo tham số nhằm xóa dữ liệu ngoài ý muốn.
     * - Bắt buộc quản trị viên phải tích chọn ô xác nhận (`confirm`) mới được phép thực thi,
     *   vì đây là thao tác xóa dữ liệu diện rộng và không thể hoàn tác.
     * - Điều kiện lọc dựa trên cột `created_at` (Thời gian lưu) đúng như cột đang hiển thị trên bảng
     *   danh sách, giúp quản trị viên đối chiếu trực quan giữa giao diện và dữ liệu thực tế bị xóa.
     */
    public function cleanup(Request $request)
    {
        $cleanupPeriods = $this->cleanupPeriods();

        $request->validate([
            'period'  => ['required', 'string', Rule::in(array_keys($cleanupPeriods))],
            'confirm' => ['required', 'accepted'],
        ], [
            'period.required'  => __('Vui lòng chọn mốc thời gian cần dọn dẹp.'),
            'period.in'        => __('Mốc thời gian dọn dẹp không hợp lệ.'),
            'confirm.required' => __('Bạn phải tích chọn xác nhận trước khi dọn dẹp dữ liệu.'),
            'confirm.accepted' => __('Bạn phải tích chọn xác nhận trước khi dọn dẹp dữ liệu.'),
        ]);

        $period = $request->input('period');
        $days   = $cleanupPeriods[$period]['days'];
        $label  = $cleanupPeriods[$period]['label'];

        try {
            $query = Product::query();

            // Mốc 'all' xóa sạch toàn bộ bảng, các mốc còn lại chỉ xóa bản ghi cũ hơn ngưỡng thời gian
            if ($days !== null) {
                $query->where('created_at', '<', \Carbon\Carbon::now()->subDays($days));
            }

            $deleted = (int) $query->delete();

            // Ghi nhật ký hoạt động phục vụ giám sát bảo mật (thao tác xóa dữ liệu trên diện rộng)
            ActivityLog::log("Dọn dẹp sản phẩm theo mốc thời gian ({$label}): đã xóa {$deleted} bản ghi", auth()->id());

            return response()->json([
                'status'  => 'success',
                'deleted' => $deleted,
                'message' => $deleted > 0
                    ? __('Đã dọn dẹp thành công :count sản phẩm (:label).', ['count' => number_format($deleted), 'label' => __($label)])
                    : __('Không có sản phẩm nào thuộc mốc thời gian này để dọn dẹp.'),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Cleanup products failed: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => __('Có lỗi xảy ra trong quá trình dọn dẹp sản phẩm: :msg', ['msg' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * Xóa nhanh hàng loạt các sản phẩm được quản trị viên tích chọn trực tiếp trên bảng danh sách.
     * Quy tắc nghiệp vụ:
     * - Danh sách ID nhận từ client được chuẩn hóa lại hoàn toàn ở phía server (ép kiểu số nguyên,
     *   loại bỏ giá trị rỗng và trùng lặp) để chống các payload bất thường hoặc bị giả mạo.
     * - Bắt buộc tích chọn ô xác nhận (`confirm`) mới được phép thực thi thao tác xóa.
     * - Bảng `products` là bảng cache độc lập, không có bản ghi nào ở bảng khác tham chiếu tới nên
     *   việc xóa không làm ảnh hưởng đến dữ liệu đơn hoàn tiền hay lịch sử click đã lưu vết.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids'     => ['required', 'string'],
            'confirm' => ['required', 'accepted'],
        ], [
            'ids.required'     => __('Không có sản phẩm nào được chọn để xóa.'),
            'confirm.required' => __('Bạn phải tích chọn xác nhận trước khi xóa hàng loạt.'),
            'confirm.accepted' => __('Bạn phải tích chọn xác nhận trước khi xóa hàng loạt.'),
        ]);

        // Chuẩn hóa chuỗi ID: tách theo dấu phẩy, ép kiểu số nguyên, loại bỏ giá trị rỗng và trùng lặp
        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $request->input('ids'))))));

        if (empty($ids)) {
            return response()->json([
                'status'  => 'error',
                'message' => __('Không có sản phẩm nào được chọn để xóa.'),
            ], 422);
        }

        // Chặn payload bất thường gửi quá nhiều ID cùng lúc gây quá tải truy vấn cơ sở dữ liệu
        if (count($ids) > 1000) {
            return response()->json([
                'status'  => 'error',
                'message' => __('Chỉ được phép xóa tối đa 1000 sản phẩm trong một lần thao tác.'),
            ], 422);
        }

        try {
            $selectedCount = count($ids);
            $deleted = (int) Product::whereIn('id', $ids)->delete();

            // Ghi nhật ký hoạt động phục vụ giám sát bảo mật thao tác xóa hàng loạt của quản trị viên
            ActivityLog::log("Xóa nhanh hàng loạt sản phẩm khỏi hệ thống: đã xóa {$deleted}/{$selectedCount} sản phẩm được chọn", auth()->id());

            return response()->json([
                'status'  => 'success',
                'deleted' => $deleted,
                'message' => $deleted > 0
                    ? __('Đã xóa thành công :count sản phẩm được chọn.', ['count' => number_format($deleted)])
                    : __('Các sản phẩm đã chọn không còn tồn tại trong hệ thống.'),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Bulk destroy products failed: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => __('Có lỗi xảy ra trong quá trình xóa hàng loạt sản phẩm: :msg', ['msg' => $e->getMessage()]),
            ], 500);
        }
    }
}
