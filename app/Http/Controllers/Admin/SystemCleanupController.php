<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class SystemCleanupController extends Controller
{
    /**
     * Đường dẫn thư mục lưu trữ các bản sao lưu database cục bộ.
     */
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
    }

    /**
     * Hiển thị trang quản trị dọn dẹp và sao lưu hệ thống.
     */
    public function index()
    {
        // Tạo thư mục backup nếu chưa tồn tại
        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }

        // Lấy danh sách các tệp sao lưu hiện có
        $backups = [];
        if (File::exists($this->backupDir)) {
            $files = File::files($this->backupDir);
            foreach ($files as $file) {
                if ($file->getExtension() === 'sql') {
                    $backups[] = [
                        'filename' => $file->getFilename(),
                        'size'     => round($file->getSize() / 1024, 2), // KB
                        'created_at'=> date('Y-m-d H:i:s', $file->getMTime()),
                    ];
                }
            }
            // Sắp xếp các bản sao lưu mới nhất lên đầu
            usort($backups, function ($a, $b) {
                return strcmp($b['created_at'], $a['created_at']);
            });
        }

        // Thu thập thông tin kích thước dữ liệu hiện tại để hiển thị trực quan
        $dbName = config('database.connections.' . config('database.default') . '.database');
        $dbSize = 0;
        try {
            $sizeResult = DB::select("
                SELECT SUM(data_length + index_length) as total_size
                FROM information_schema.tables 
                WHERE table_schema = ?
            ", [$dbName]);
            $dbSize = round(($sizeResult[0]->total_size ?? 0) / 1024 / 1024, 2); // MB
        } catch (\Exception $e) {}

        // Tính dung lượng log file
        $logSize = 0;
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            $logSize = round(File::size($logPath) / 1024 / 1024, 2); // MB
        }

        // Thống kê số lượng bản ghi logs/queues có thể dọn dẹp, bổ sung đếm số lượng cho rút tiền, đổi quà tặng, biến động số dư và các nhật ký khác
        $counts = [
            'activity_logs'        => DB::table('activity_logs')->count(),
            'api_logs'             => DB::table('api_logs')->count(),
            'email_queues'         => DB::table('email_queues')->count(),
            'telegram_queues'      => DB::table('telegram_queues')->count(),
            'cashback_clicks'      => DB::table('cashback_clicks')->count(),
            'cashback_histories'   => DB::table('cashback_histories')->count(),
            'withdrawals'          => DB::table('withdrawals')->count(),
            'gift_redemptions'     => DB::table('gift_redemptions')->count(),
            'gift_code_redemptions'=> DB::table('gift_code_redemptions')->count(),
            'balance_logs'         => DB::table('balance_logs')->count(),
            'daily_checkins'       => DB::table('daily_checkins')->count(),
            'referral_commissions' => DB::table('referral_commissions')->count(),
            'short_links'          => DB::table('short_links')->count(),
            'notifications'        => DB::table('notifications')->count(),
            'saved_products'       => DB::table('saved_products')->count(),
        ];

        return view('admin.system-cleanup', compact('backups', 'dbSize', 'logSize', 'counts'));
    }

    /**
     * Thực hiện các tác vụ dọn dẹp hệ thống được chọn.
     */
    public function cleanup(Request $request)
    {
        // Chặn chỉnh sửa nếu đang ở chế độ Demo
        if (config('app.demo')) {
            return back()->with('error', __('Hệ thống đang ở chế độ Demo, không thể thực hiện dọn dẹp dữ liệu!'));
        }

        $request->validate([
            'actions' => 'required|array',
        ]);

        $actions = $request->input('actions');
        $cleaned = [];

        try {
            // 1. Dọn dẹp Cache hệ thống
            if (in_array('cache', $actions)) {
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');
                $cleaned[] = __('Bộ nhớ đệm (Cache)');
            }

            // 2. Dọn dẹp tệp tin Logs hệ thống (laravel.log)
            if (in_array('logs', $actions)) {
                $logPath = storage_path('logs/laravel.log');
                if (File::exists($logPath)) {
                    File::put($logPath, ''); // Làm rỗng file log thay vì xóa hẳn để tránh lỗi ghi quyền
                }
                $cleaned[] = __('Tệp nhật ký hệ thống (laravel.log)');
            }

            // 3. Dọn dẹp Nhật ký hoạt động (Activity Logs)
            if (in_array('activity_logs', $actions)) {
                $days = intval($request->input('keep_recent_activity_logs', 0));
                if ($days > 0) {
                    DB::table('activity_logs')->where('created_at', '<', now()->subDays($days))->delete();
                    $cleaned[] = __('Nhật ký hoạt động (chừa lại :days ngày gần nhất)', ['days' => $days]);
                } else {
                    DB::table('activity_logs')->delete();
                    $cleaned[] = __('Toàn bộ Nhật ký hoạt động');
                }
            }

            // 3b. Dọn dẹp Nhật ký gọi API (Api Logs)
            if (in_array('api_logs', $actions)) {
                $days = intval($request->input('keep_recent_api_logs', 0));
                if ($days > 0) {
                    DB::table('api_logs')->where('created_at', '<', now()->subDays($days))->delete();
                    $cleaned[] = __('Nhật ký gọi API (chừa lại :days ngày gần nhất)', ['days' => $days]);
                } else {
                    DB::table('api_logs')->delete();
                    $cleaned[] = __('Toàn bộ Nhật ký gọi API');
                }
            }

            // 4. Dọn dẹp hàng đợi Email (đã gửi thành công hoặc thất bại)
            if (in_array('email_queues', $actions)) {
                DB::table('email_queues')->delete();
                $cleaned[] = __('Hàng đợi gửi Email');
            }

            // 5. Dọn dẹp hàng đợi Telegram
            if (in_array('telegram_queues', $actions)) {
                DB::table('telegram_queues')->delete();
                $cleaned[] = __('Hàng đợi Telegram');
            }

            // 6. Dọn dẹp Lịch sử click hoàn tiền (giúp giảm tải DB khi chạy lâu năm)
            if (in_array('cashback_clicks', $actions)) {
                $days = intval($request->input('keep_recent_clicks', 0));
                if ($days > 0) {
                    DB::table('cashback_clicks')->where('created_at', '<', now()->subDays($days))->delete();
                    $cleaned[] = __('Nhật ký click hoàn tiền (chừa lại :days ngày gần nhất)', ['days' => $days]);
                } else {
                    DB::table('cashback_clicks')->delete();
                    $cleaned[] = __('Toàn bộ nhật ký click hoàn tiền');
                }
            }

            // 7. Dọn dẹp lịch sử đơn hàng hoàn tiền cũ (cashback_histories)
            if (in_array('cashback_histories', $actions)) {
                $days = intval($request->input('keep_orders', 0));
                if ($days > 0) {
                    $deletedCount = DB::table('cashback_histories')
                        ->where('created_at', '<', now()->subDays($days))
                        ->delete();
                    $cleaned[] = __('Lịch sử đơn hàng hoàn tiền cũ hơn :period (đã xóa :count đơn)', [
                        'period' => $this->getPeriodLabel($days),
                        'count' => $deletedCount
                    ]);
                } else {
                    $deletedCount = DB::table('cashback_histories')->delete();
                    $cleaned[] = __('Toàn bộ lịch sử đơn hàng hoàn tiền (đã xóa :count đơn)', ['count' => $deletedCount]);
                }
            }

            // 8. Dọn dẹp Lịch sử rút tiền (withdrawals) để giải phóng các giao dịch rút tiền cũ
            if (in_array('withdrawals', $actions)) {
                $days = intval($request->input('keep_recent_withdrawals', 0));
                if ($days > 0) {
                    DB::table('withdrawals')->where('created_at', '<', now()->subDays($days))->delete();
                    $cleaned[] = __('Lịch sử rút tiền (chừa lại :days ngày gần nhất)', ['days' => $days]);
                } else {
                    DB::table('withdrawals')->delete();
                    $cleaned[] = __('Toàn bộ lịch sử rút tiền');
                }
            }

            // 9. Dọn dẹp Lịch sử đổi quà tặng (gift_redemptions) từ shop đổi quà
            if (in_array('gift_redemptions', $actions)) {
                $days = intval($request->input('keep_recent_gift_redemptions', 0));
                if ($days > 0) {
                    DB::table('gift_redemptions')->where('created_at', '<', now()->subDays($days))->delete();
                    $cleaned[] = __('Lịch sử đổi quà tặng (chừa lại :days ngày gần nhất)', ['days' => $days]);
                } else {
                    DB::table('gift_redemptions')->delete();
                    $cleaned[] = __('Toàn bộ lịch sử đổi quà tặng');
                }
            }

            // 10. Dọn dẹp Lịch sử đổi mã Giftcode (gift_code_redemptions) từ nhập code
            if (in_array('gift_code_redemptions', $actions)) {
                $days = intval($request->input('keep_recent_gift_code_redemptions', 0));
                if ($days > 0) {
                    DB::table('gift_code_redemptions')->where('created_at', '<', now()->subDays($days))->delete();
                    $cleaned[] = __('Lịch sử đổi mã Giftcode (chừa lại :days ngày gần nhất)', ['days' => $days]);
                } else {
                    DB::table('gift_code_redemptions')->delete();
                    $cleaned[] = __('Toàn bộ lịch sử đổi mã Giftcode');
                }
            }

            // 11. Dọn dẹp Biến động số dư (balance_logs) để giải phóng dữ liệu dòng tiền cũ
            if (in_array('balance_logs', $actions)) {
                $days = intval($request->input('keep_recent_balance_logs', 0));
                if ($days > 0) {
                    DB::table('balance_logs')->where('created_at', '<', now()->subDays($days))->delete();
                    $cleaned[] = __('Biến động số dư (chừa lại :days ngày gần nhất)', ['days' => $days]);
                } else {
                    DB::table('balance_logs')->delete();
                    $cleaned[] = __('Toàn bộ biến động số dư');
                }
            }

            // 12. Dọn dẹp Nhật ký điểm danh (daily_checkins)
            if (in_array('daily_checkins', $actions)) {
                $days = intval($request->input('keep_recent_daily_checkins', 0));
                if ($days > 0) {
                    DB::table('daily_checkins')->where('created_at', '<', now()->subDays($days))->delete();
                    $cleaned[] = __('Nhật ký điểm danh (chừa lại :days ngày gần nhất)', ['days' => $days]);
                } else {
                    DB::table('daily_checkins')->delete();
                    $cleaned[] = __('Toàn bộ nhật ký điểm danh');
                }
            }

            // 13. Dọn dẹp Hoa hồng giới thiệu Affiliate (referral_commissions)
            if (in_array('referral_commissions', $actions)) {
                $days = intval($request->input('keep_recent_referral_commissions', 0));
                if ($days > 0) {
                    DB::table('referral_commissions')->where('created_at', '<', now()->subDays($days))->delete();
                    $cleaned[] = __('Hoa hồng giới thiệu Affiliate (chừa lại :days ngày gần nhất)', ['days' => $days]);
                } else {
                    DB::table('referral_commissions')->delete();
                    $cleaned[] = __('Toàn bộ hoa hồng giới thiệu Affiliate');
                }
            }

            // 14. Dọn dẹp Nhật ký Short Link (short_links)
            if (in_array('short_links', $actions)) {
                $days = intval($request->input('keep_recent_short_links', 0));
                if ($days > 0) {
                    DB::table('short_links')->where('created_at', '<', now()->subDays($days))->delete();
                    $cleaned[] = __('Nhật ký Short Link (chừa lại :days ngày gần nhất)', ['days' => $days]);
                } else {
                    DB::table('short_links')->delete();
                    $cleaned[] = __('Toàn bộ nhật ký Short Link');
                }
            }

            // 15. Dọn dẹp Nhật ký thông báo (notifications)
            if (in_array('notifications', $actions)) {
                $days = intval($request->input('keep_recent_notifications', 0));
                if ($days > 0) {
                    DB::table('notifications')->where('created_at', '<', now()->subDays($days))->delete();
                    $cleaned[] = __('Nhật ký thông báo (chừa lại :days ngày gần nhất)', ['days' => $days]);
                } else {
                    DB::table('notifications')->delete();
                    $cleaned[] = __('Toàn bộ nhật ký thông báo');
                }
            }

            // 16. Dọn dẹp Sản phẩm đã lưu (saved_products)
            if (in_array('saved_products', $actions)) {
                $days = intval($request->input('keep_recent_saved_products', 0));
                if ($days > 0) {
                    DB::table('saved_products')->where('created_at', '<', now()->subDays($days))->delete();
                    $cleaned[] = __('Danh sách sản phẩm đã lưu (chừa lại :days ngày gần nhất)', ['days' => $days]);
                } else {
                    DB::table('saved_products')->delete();
                    $cleaned[] = __('Toàn bộ sản phẩm đã lưu');
                }
            }

            // Ghi nhật ký hành động của Admin
            $actionStr = implode(', ', $cleaned);
            ActivityLog::log("Đã thực hiện dọn dẹp hệ thống các mục: {$actionStr}");

            return back()->with('success', __('Đã dọn dẹp thành công các mục: :items', ['items' => $actionStr]));
        } catch (\Exception $e) {
            \Log::error('Lỗi dọn dẹp hệ thống: ' . $e->getMessage());
            return back()->with('error', __('Đã xảy ra lỗi khi dọn dẹp: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Tạo bản sao lưu Database (SQL dump thuần PHP tương thích mọi môi trường).
     */
    public function backupStore()
    {
        // Chặn nếu đang ở chế độ Demo
        if (config('app.demo')) {
            return back()->with('error', __('Hệ thống đang ở chế độ Demo, không thể tạo bản sao lưu!'));
        }

        try {
            // Đọc tên cơ sở dữ liệu hiện tại
            $dbName = config('database.connections.' . config('database.default') . '.database');
            
            // Lấy tất cả các bảng
            $tables = [];
            $result = DB::select('SHOW TABLES');
            $keyName = 'Tables_in_' . $dbName;
            
            foreach ($result as $row) {
                $tables[] = $row->$keyName;
            }

            // Xây dựng nội dung file SQL
            $sql = "-- Hoan Tien Shopee Database Backup\n";
            $sql .= "-- Tên Database: " . $dbName . "\n";
            $sql .= "-- Ngày tạo: " . now()->toDateTimeString() . "\n";
            $sql .= "-- ------------------------------------------------------\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                // Tạo cấu trúc bảng
                $createTable = DB::select("SHOW CREATE TABLE `{$table}`");
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $createTable[0]->{'Create Table'} . ";\n\n";

                // Lấy dữ liệu của bảng
                $rows = DB::table($table)->get();
                foreach ($rows as $row) {
                    $rowArray = (array)$row;
                    $keys = array_map(function ($key) {
                        return "`{$key}`";
                    }, array_keys($rowArray));
                    
                    $values = array_map(function ($val) {
                        if ($val === null) {
                            return 'NULL';
                        }
                        return DB::getPdo()->quote($val);
                    }, array_values($rowArray));

                    $sql .= "INSERT INTO `{$table}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            // Tạo thư mục backup nếu chưa tồn tại
            if (!File::exists($this->backupDir)) {
                File::makeDirectory($this->backupDir, 0755, true);
            }

            // Ghi file SQL cục bộ
            $filename = 'backup_' . date('Y_m_d_His') . '_' . substr(md5(uniqid()), 0, 8) . '.sql';
            File::put($this->backupDir . '/' . $filename, $sql);

            // Ghi nhật ký
            ActivityLog::log("Tạo bản sao lưu cơ sở dữ liệu cục bộ: {$filename}");

            return back()->with('success', __('Đã tạo bản sao lưu cơ sở dữ liệu thành công: :file', ['file' => $filename]));
        } catch (\Exception $e) {
            \Log::error('Lỗi khi sao lưu database: ' . $e->getMessage());
            return back()->with('error', __('Không thể sao lưu cơ sở dữ liệu: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Tải bản sao lưu SQL về máy tính.
     * Giải thích logic bảo mật: Ngăn chặn triệt để tấn công Path Traversal bằng cách lấy đường dẫn tuyệt đối thực tế (realpath),
     * kiểm tra xem đường dẫn đó có bắt đầu bằng thư mục backup hợp lệ không, và bắt buộc tệp tin tải xuống phải có đuôi mở rộng .sql.
     */
    public function backupDownload($filename)
    {
        // Chặn nếu đang ở chế độ Demo
        if (config('app.demo')) {
            return redirect()->route('admin.dashboard')->with('error', __('Chức năng tải bản sao lưu không khả dụng ở chế độ Demo!'));
        }

        // Tạo thư mục backup nếu chưa tồn tại nhằm đảm bảo hàm realpath không bị lỗi
        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }

        // Lấy tên tệp tin an toàn (bỏ qua mọi ký tự đường dẫn tương đối hoặc mã hóa)
        $safe = basename($filename);
        $realPath = realpath($this->backupDir . '/' . $safe);
        $realBase = realpath($this->backupDir);

        // Ràng buộc bảo mật (Anti-Path Traversal):
        // 1. Phải lấy được đường dẫn tuyệt đối (realpath) thành công.
        // 2. Đường dẫn thực tế phải nằm bên trong thư mục backups được cấu hình (phòng tránh chèn đường dẫn tương đối như ..).
        // 3. Tên tệp tin bắt buộc phải kết thúc bằng đuôi mở rộng .sql nhằm tránh tải các file hệ thống nhạy cảm khác như .env.
        if (!$realPath || !str_starts_with($realPath, $realBase . DIRECTORY_SEPARATOR) || !str_ends_with($safe, '.sql')) {
            abort(404, __('Tệp tin không tồn tại hoặc đường dẫn không hợp lệ.'));
        }

        return Response::download($realPath, $safe);
    }

    /**
     * Xóa bản sao lưu cơ sở dữ liệu.
     * Giải thích logic bảo mật: Ngăn chặn việc xóa tệp tin hệ thống ngoài thư mục backup bằng cách xác thực realpath tương tự download.
     */
    public function backupDestroy($filename)
    {
        // Chặn nếu đang ở chế độ Demo
        if (config('app.demo')) {
            return back()->with('error', __('Hệ thống đang ở chế độ Demo, không thể xóa bản sao lưu!'));
        }

        // Tạo thư mục backup nếu chưa tồn tại
        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }

        // Lấy tên tệp tin an toàn
        $safe = basename($filename);
        $realPath = realpath($this->backupDir . '/' . $safe);
        $realBase = realpath($this->backupDir);

        // Ràng buộc bảo mật (Anti-Path Traversal):
        // Chỉ cho phép thao tác trên các file nằm hoàn toàn trong thư mục backups và có đuôi .sql
        if (!$realPath || !str_starts_with($realPath, $realBase . DIRECTORY_SEPARATOR) || !str_ends_with($safe, '.sql')) {
            return back()->with('error', __('Tệp tin không tồn tại hoặc đường dẫn không hợp lệ.'));
        }

        File::delete($realPath);

        // Ghi nhật ký hoạt động
        ActivityLog::log("Xóa bản sao lưu cơ sở dữ liệu: {$safe}");

        return back()->with('success', __('Đã xóa bản sao lưu thành công!'));
    }

    /**
     * Lấy nhãn hiển thị khoảng thời gian dọn dẹp.
     */
    protected function getPeriodLabel($days): string
    {
        $days = intval($days);
        if ($days === 365) {
            return __('1 năm');
        }
        return __(':days ngày', ['days' => $days]);
    }
}
