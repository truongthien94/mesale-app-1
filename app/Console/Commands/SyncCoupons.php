<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Setting;
use App\Models\Coupon;
use Illuminate\Support\Facades\Log;

class SyncCoupons extends Command
{
    /**
     * Tên và cú pháp của lệnh Artisan console.
     *
     * @var string
     */
    protected $signature = 'coupons:sync {--force : Bỏ qua kiểm tra công tắc tự động đồng bộ (dùng cho nút Đồng bộ ngay thủ công)}';

    /**
     * Mô tả chi tiết về lệnh Artisan.
     *
     * @var string
     */
    protected $description = 'Đồng bộ danh sách mã giảm giá từ API đối tác đa sàn';

    /**
     * Thực hiện logic xử lý của command.
     * Giải thích: Gọi API lấy danh sách mã giảm giá phân trang, lặp qua từng trang để lấy dữ liệu 
     * và thực hiện updateOrCreate vào bảng coupons theo cặp khoá [platform, code] để tránh trùng lặp.
     */
    public function handle()
    {
        // 1. Kiểm tra trạng thái hoạt động của chức năng
        $status = Setting::getVal('coupon_status', '1');
        if ($status !== '1') {
            $this->info('Chức năng mã giảm giá đang TẮT.');
            return 0;
        }

        // 1.1. Kiểm tra công tắc tự động đồng bộ (tách biệt với hiển thị).
        // Khi TẮT, lịch chạy tự động sẽ bỏ qua để admin chỉ dùng mã tự nhập tay.
        // Nút "Đồng bộ ngay" thủ công truyền cờ --force nên vẫn chạy được bình thường.
        $autoSync = Setting::getVal('coupon_auto_sync', '1');
        if ($autoSync !== '1' && !$this->option('force')) {
            $this->info('Tự động đồng bộ mã giảm giá đang TẮT (bỏ qua lịch chạy tự động).');
            return 0;
        }

        // 2. Lấy cấu hình kết nối API
        $apiUrl = Setting::getVal('coupon_api_url');
        $apiKey = Setting::getVal('coupon_api_key');

        if (empty($apiUrl)) {
            $this->error('Đường dẫn API đồng bộ chưa được cấu hình.');
            return 1;
        }

        $this->info('Bắt đầu đồng bộ mã giảm giá từ API: ' . $apiUrl);

        $page = 1;
        $maxPages = 50; // Giới hạn tối đa 50 trang để ngăn chặn vòng lặp vô tận nếu API lỗi
        $totalSynced = 0;

        do {
            $this->info("Đang lấy dữ liệu trang {$page}...");

            try {
                // Gọi request GET đến API đối tác kèm header API Key
                $response = Http::withHeaders([
                    'X-API-KEY' => $apiKey,
                    'Accept' => 'application/json',
                ])->timeout(30)->get($apiUrl, [
                    'page' => $page
                ]);

                if (!$response->successful()) {
                    $this->error("Lỗi kết nối API tại trang {$page}: HTTP " . $response->status());
                    Log::error("SyncCoupons: Lỗi API trang {$page}: HTTP " . $response->status() . " - " . $response->body());
                    break;
                }

                $result = $response->json();
                $items = [];
                $lastPage = 1;

                // Chuẩn hoá dữ liệu trả về theo Laravel paginator hoặc dạng mảng phẳng
                if (isset($result['data']) && is_array($result['data'])) {
                    $items = $result['data'];
                    $lastPage = $result['last_page'] ?? 1;
                } elseif (is_array($result)) {
                    $items = $result;
                    $lastPage = 1;
                }

                if (empty($items)) {
                    $this->info('Không còn dữ liệu mã giảm giá nào ở trang này.');
                    break;
                }

                foreach ($items as $item) {
                    // Nếu code rỗng, tự động sinh mã code giả lập dạng BANNER_{id} để lưu vào DB không bị trùng lặp unique key
                    $code = $item['code'] ?? '';
                    if (empty($code)) {
                        if (isset($item['id'])) {
                            $code = 'BANNER_' . $item['id'];
                        } else {
                            $code = 'BANNER_' . substr(md5(($item['title'] ?? '') . ($item['redirect_link'] ?? '')), 0, 10);
                        }
                    }

                    // Tự động gán platform mặc định là 'shopee' hoặc lấy từ dữ liệu API
                    $platform = $item['platform'] ?? 'shopee';

                    // Bỏ qua nếu đã tồn tại một mã trùng do admin tự nhập/chỉnh sửa (source = 'manual'),
                    // để dữ liệu API không ghi đè lên các mã giảm giá tùy chỉnh của admin.
                    $existing = Coupon::where('platform', $platform)->where('code', $code)->first();
                    if ($existing && $existing->source === 'manual') {
                        $this->line("Bỏ qua mã thủ công (không ghi đè): {$code}");
                        continue;
                    }

                    // Cập nhật hoặc tạo mới bản ghi mã giảm giá
                    Coupon::updateOrCreate(
                        [
                            'platform' => $platform,
                            'code' => $code
                        ],
                        [
                            'title' => $item['title'] ?? '',
                            'description' => $item['description'] ?? '',
                            'category' => $item['category'] ?? null,
                            'min_spend' => isset($item['min_spend']) ? (int)$item['min_spend'] : 0,
                            'discount_amount' => isset($item['discount_amount']) ? (int)$item['discount_amount'] : 0,
                            'discount_percentage' => isset($item['discount_percentage']) ? (int)$item['discount_percentage'] : 0,
                            'clicks' => isset($item['clicks']) ? (int)$item['clicks'] : 0,
                            'expired_at' => !empty($item['expired_at']) ? date('Y-m-d H:i:s', strtotime($item['expired_at'])) : null,
                            'redirect_link' => $item['redirect_link'] ?? null,
                            'source' => $item['source'] ?? null,
                            'image_url' => $item['image_url'] ?? null,
                        ]
                    );

                    $totalSynced++;
                }

                $this->info("Đã đồng bộ thành công " . count($items) . " mã giảm giá của trang {$page}.");

                // Nếu đã đi đến trang cuối cùng thì dừng lại
                if ($page >= $lastPage) {
                    $this->info('Đã hoàn thành đồng bộ toàn bộ các trang.');
                    break;
                }

                $page++;

            } catch (\Exception $e) {
                $this->error('Gặp lỗi ngoại lệ khi đang đồng bộ: ' . $e->getMessage());
                Log::error('SyncCoupons: Lỗi ngoại lệ: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                break;
            }

        } while ($page <= $maxPages);

        // Ghi lại thời điểm đồng bộ thành công để hiển thị ở quản trị
        Setting::setVal('cron_last_run_coupons_sync', now()->toDateTimeString());

        $this->info("Đồng bộ hoàn tất! Tổng cộng đã xử lý: {$totalSynced} mã giảm giá.");
        return 0;
    }
}
