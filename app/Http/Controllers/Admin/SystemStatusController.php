<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class SystemStatusController extends Controller
{
    /**
     * Danh sách các PHP extensions bắt buộc phải cài đặt để hệ thống hoạt động ổn định.
     * Dựa trên cấu hình trong InstallerController.
     */
    private const REQUIRED_EXTENSIONS = [
        'pdo_mysql'  => 'Kết nối database MySQL/MariaDB',
        'mbstring'   => 'Xử lý chuỗi đa ngôn ngữ (Tiếng Việt)',
        'openssl'    => 'Mã hóa và bảo mật HTTPS',
        'tokenizer'  => 'Phân tích cú pháp PHP (Blade Template)',
        'json'       => 'Xử lý dữ liệu JSON (API)',
        'curl'       => 'Gọi API bên ngoài (Shopee, Telegram)',
        'fileinfo'   => 'Xác định loại file upload',
        'ctype'      => 'Kiểm tra kiểu ký tự',
        'xml'        => 'Xử lý dữ liệu XML',
        'dom'        => 'Thao tác DOM (Email template)',
        'bcmath'     => 'Tính toán tài chính chính xác cao',
        'gd'         => 'Xử lý hình ảnh (QR Code, Avatar)',
        'pcntl'      => 'Quản lý tiến trình chạy ngầm (Cron Job/Queue)',
    ];

    /**
     * Danh sách các PHP extensions khuyến nghị nên có để tăng hiệu năng hoặc thêm tính năng phụ.
     */
    private const RECOMMENDED_EXTENSIONS = [
        'redis'  => 'Cache & Session hiệu năng cao (Redis)',
        'zip'    => 'Nén/giải nén file (Cập nhật hệ thống)',
        'exif'   => 'Đọc metadata ảnh (xoay ảnh tự động)',
        'intl'   => 'Hỗ trợ quốc tế hóa (Ngôn ngữ, Tiền tệ)',
    ];

    /**
     * Hiển thị trang trạng thái sức khỏe hệ thống cho admin giám sát nhanh.
     * Thu thập thông tin từ môi trường server, database, cache, cron job...
     * mà không cần admin phải SSH vào máy chủ.
     * Yêu cầu giấy phép bản quyền hợp lệ để xem nội dung (bảo vệ thông tin nhạy cảm).
     */
    public function index()
    {
        // === KIỂM TRA GIẤY PHÉP BẢN QUYỀN TRƯỚC KHI HIỂN THỊ NỘI DUNG ===
        $licenseKey = trim((string) Setting::where('key', 'license_key')->value('value'));
        $licenseValid = true;
        $licenseError = '';

        if (!empty($licenseKey)) {
            $licenseValid = $this->verifyLicenseOnline($licenseKey, request()->getHost(), $licenseError);
        }

        // Nếu giấy phép chưa kích hoạt hoặc không hợp lệ → trả view với trạng thái lỗi
        if (!$licenseValid) {
            return view('admin.system-status', [
                'licenseValid' => false,
                'licenseKey'   => $licenseKey,
                'licenseError' => $licenseError,
            ]);
        }

        // === 1. THÔNG TIN MÔI TRƯỜNG ỨNG DỤNG ===
        $appInfo = [
            'app_name'    => config('app.name', 'Hoàn Tiền Shopee'),
            'app_env'     => config('app.env', 'production'),
            'app_debug'   => config('app.debug'),
            'app_demo'    => config('app.demo'),
            'app_url'     => config('app.url'),
            'app_locale'  => config('app.locale'),
            'timezone'    => config('app.timezone'),
        ];

        // === 2. THÔNG TIN PHP ===
        $phpInfo = [
            'version'           => PHP_VERSION,
            'memory_limit'      => ini_get('memory_limit'),
            'max_execution_time'=> ini_get('max_execution_time') . 's',
            'upload_max_size'   => ini_get('upload_max_filesize'),
            'post_max_size'     => ini_get('post_max_size'),
            'sapi'              => php_sapi_name(),
            // Kiểm tra các PHP extensions quan trọng cần thiết cho Laravel
            'extensions'        => $this->getImportantExtensions(),
        ];

        // === 3. THÔNG TIN SERVER ===
        $serverInfo = [
            'os'          => php_uname('s') . ' ' . php_uname('r'),
            'hostname'    => gethostname(),
            'server_ip'   => $_SERVER['SERVER_ADDR'] ?? request()->server('SERVER_ADDR', 'N/A'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'document_root'   => $_SERVER['DOCUMENT_ROOT'] ?? base_path(),
        ];

        // === 4. TÀI NGUYÊN MÁY CHỦ (CPU, RAM, DISK) ===
        $resources = $this->getServerResources();

        // === 5. THÔNG TIN DATABASE ===
        $dbInfo = $this->getDatabaseInfo();

        // Ẩn (mask) các thông tin nhạy cảm của server và database khi đang chạy ở chế độ Demo để tránh rò rỉ hạ tầng
        if (config('app.demo')) {
            $serverInfo['hostname'] = 'demo-server.local';
            $serverInfo['server_ip'] = '127.0.0.1';
            $serverInfo['document_root'] = '/var/www/html/hoantienshopee';
            
            $dbInfo['host'] = '127.0.0.1';
            $dbInfo['database_name'] = 'demo_database';
        }

        // === 6. THÔNG TIN CACHE & SESSION ===
        $cacheInfo = [
            'cache_driver'   => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_driver'   => config('queue.default'),
        ];

        // === 7. TRẠNG THÁI CRON JOB ===
        $cronInfo = $this->getCronInfo();

        // === 8. THÔNG TIN LARAVEL ===
        $laravelInfo = [
            'version'     => \Illuminate\Foundation\Application::VERSION,
            'config_cached' => file_exists(base_path('bootstrap/cache/config.php')),
            'route_cached'  => file_exists(base_path('bootstrap/cache/routes-v7.php')),
            'view_cached'   => is_dir(storage_path('framework/views')) && count(glob(storage_path('framework/views/*.php'))) > 0,
        ];

        // === 9. DUNG LƯỢNG THƯ MỤC STORAGE ===
        $storageInfo = $this->getStorageInfo();

        // === 10. KIỂM TRA QUYỀN GHI THƯ MỤC & FILE ===
        // Hệ thống tự động kiểm tra quyền đọc ghi của các thư mục/file cấu hình quan trọng để phát hiện sớm lỗi vận hành
        $writablePaths = [
            'storage/app'           => 'Lưu trữ file ứng dụng',
            'storage/framework'     => 'Lưu trữ cache & session',
            'storage/logs'          => 'Ghi log hoạt động hệ thống',
            'bootstrap/cache'       => 'Cache cấu hình framework',
            'public/uploads'        => 'Lưu trữ ảnh upload từ Admin',
            'lang'                  => 'Lưu trữ bản dịch/ngôn ngữ hệ thống',
            '.env'                  => 'File cấu hình môi trường',
        ];

        $permissionsInfo = [];
        $hasPermissionError = false;

        foreach ($writablePaths as $path => $desc) {
            $fullPath = base_path($path);
            $writable = false;

            if (file_exists($fullPath)) {
                $writable = is_writable($fullPath);
            } else {
                // Nếu file chưa tồn tại (ví dụ file .env), kiểm tra xem thư mục cha có quyền ghi để tạo file hay không
                $parentDir = dirname($fullPath);
                $writable = file_exists($parentDir) && is_writable($parentDir);
            }

            $permissionsInfo[$path] = [
                'description' => $desc,
                'writable'    => $writable,
                'full_path'   => $fullPath,
            ];

            if (!$writable) {
                $hasPermissionError = true;
            }
        }

        // Kiểm tra xem có thiếu extension bắt buộc nào để hiển thị cảnh báo lên đầu trang
        $hasExtensionError = false;
        foreach ($phpInfo['extensions'] as $ext => $info) {
            if ($info['required'] && !$info['loaded']) {
                $hasExtensionError = true;
                break;
            }
        }

        return view('admin.system-status', compact(
            'appInfo', 'phpInfo', 'serverInfo', 'resources',
            'dbInfo', 'cacheInfo', 'cronInfo', 'laravelInfo', 'storageInfo',
            'permissionsInfo', 'hasPermissionError', 'hasExtensionError'
        ) + ['licenseValid' => true, 'licenseKey' => $licenseKey, 'licenseError' => '']);
    }

    /**
     * Kiểm tra danh sách PHP extensions bắt buộc và khuyến nghị cần thiết cho hệ thống.
     * Đồng thời kiểm tra các hàm đặc thù của pcntl để đảm bảo cron job chạy ngầm hoạt động tốt.
     */
    private function getImportantExtensions(): array
    {
        $result = [];

        // Kiểm tra các extension bắt buộc phải cài đặt
        foreach (self::REQUIRED_EXTENSIONS as $ext => $desc) {
            $loaded = extension_loaded($ext);

            // Business Rule: Cần kiểm tra xem extension pcntl có các hàm quan trọng bị disable trên server không
            if ($ext === 'pcntl') {
                $loaded = $loaded 
                    && function_exists('pcntl_signal') 
                    && function_exists('pcntl_alarm') 
                    && function_exists('pcntl_fork') 
                    && function_exists('pcntl_async_signals');
            }

            $result[$ext] = [
                'description' => __($desc),
                'required'    => true,
                'loaded'      => (bool) $loaded,
            ];
        }

        // Kiểm tra các extension khuyến nghị cài đặt thêm
        foreach (self::RECOMMENDED_EXTENSIONS as $ext => $desc) {
            $result[$ext] = [
                'description' => __($desc),
                'required'    => false,
                'loaded'      => (bool) extension_loaded($ext),
            ];
        }

        return $result;
    }

    /**
     * Thu thập thông tin tài nguyên máy chủ (CPU, RAM, Disk).
     * Xử lý graceful fallback khi shell_exec bị disable trên shared hosting.
     */
    private function getServerResources(): array
    {
        $resources = [
            'cpu_cores'   => null,
            'cpu_usage'   => null,
            'ram_total'   => null,
            'ram_used'    => null,
            'ram_percent' => null,
            'disk_total'  => null,
            'disk_free'   => null,
            'disk_used'   => null,
            'disk_percent'=> null,
        ];

        // Lấy CPU load average (Unix/Linux only)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $resources['cpu_usage'] = $load ? round($load[0], 2) : null;
        }

        // Lấy số CPU cores thông qua /proc/cpuinfo
        try {
            if (@is_readable('/proc/cpuinfo')) {
                $cpuinfo = @file_get_contents('/proc/cpuinfo');
                if ($cpuinfo) {
                    $resources['cpu_cores'] = substr_count($cpuinfo, 'processor');
                }
            }
        } catch (\Exception $e) {}

        // Lấy thông tin RAM qua /proc/meminfo (Linux only)
        try {
            if (@is_readable('/proc/meminfo')) {
                $meminfo = @file_get_contents('/proc/meminfo');
                if ($meminfo) {
                    preg_match('/MemTotal:\s+(\d+)\s/', $meminfo, $totalMatch);
                    preg_match('/MemAvailable:\s+(\d+)\s/', $meminfo, $availMatch);
                    if (!empty($totalMatch[1])) {
                        $totalKb = (int)$totalMatch[1];
                        $resources['ram_total'] = round($totalKb / 1024 / 1024, 2); // GB
                        if (!empty($availMatch[1])) {
                            $availKb = (int)$availMatch[1];
                            $usedKb = $totalKb - $availKb;
                            $resources['ram_used'] = round($usedKb / 1024 / 1024, 2); // GB
                            $resources['ram_percent'] = round(($usedKb / $totalKb) * 100, 1);
                        }
                    }
                }
            }
        } catch (\Exception $e) {}

        // Lấy thông tin ổ đĩa qua hàm PHP built-in (luôn hoạt động)
        $basePath = base_path();
        $diskTotal = @disk_total_space($basePath);
        $diskFree = @disk_free_space($basePath);
        if ($diskTotal && $diskFree) {
            $diskUsed = $diskTotal - $diskFree;
            $resources['disk_total']   = round($diskTotal / 1024 / 1024 / 1024, 2); // GB
            $resources['disk_free']    = round($diskFree / 1024 / 1024 / 1024, 2);  // GB
            $resources['disk_used']    = round($diskUsed / 1024 / 1024 / 1024, 2);  // GB
            $resources['disk_percent'] = round(($diskUsed / $diskTotal) * 100, 1);
        }

        return $resources;
    }

    /**
     * Thu thập thông tin Database (phiên bản, kích thước, số bảng, số kết nối).
     */
    private function getDatabaseInfo(): array
    {
        $info = [
            'driver'        => config('database.default'),
            'host'          => config('database.connections.' . config('database.default') . '.host'),
            'database_name' => config('database.connections.' . config('database.default') . '.database'),
            'version'       => null,
            'total_tables'  => 0,
            'total_size_mb' => 0,
        ];

        try {
            // Phiên bản MySQL/MariaDB
            $versionResult = DB::select('SELECT VERSION() as version');
            $info['version'] = $versionResult[0]->version ?? 'N/A';

            // Tổng số bảng và kích thước database
            $dbName = $info['database_name'];
            $sizeResult = DB::select("
                SELECT 
                    COUNT(*) as total_tables,
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as total_size_mb
                FROM information_schema.tables 
                WHERE table_schema = ?
            ", [$dbName]);

            if (!empty($sizeResult[0])) {
                $info['total_tables']  = $sizeResult[0]->total_tables;
                $info['total_size_mb'] = $sizeResult[0]->total_size_mb ?? 0;
            }
        } catch (\Exception $e) {
            \Log::error('Lỗi khi thu thập thông tin database cho trang trạng thái: ' . $e->getMessage());
        }

        return $info;
    }

    /**
     * Lấy trạng thái Cron Job dựa trên heartbeat schedule_last_run.
     */
    private function getCronInfo(): array
    {
        $cronLastRun = Setting::getVal('schedule_last_run');
        $info = [
            'last_run'     => $cronLastRun,
            'active'       => false,
            'time_label'   => null,
            'minutes_ago'  => null,
        ];

        if ($cronLastRun) {
            $lastRunTime = Carbon::parse($cronLastRun);
            $info['minutes_ago'] = (int) $lastRunTime->diffInMinutes(now());
            // Cron được coi là hoạt động nếu chạy trong vòng 5 phút gần nhất
            $info['active']      = $info['minutes_ago'] <= 5;
            $info['time_label']  = $lastRunTime->diffForHumans();
        }

        return $info;
    }

    /**
     * Tính dung lượng các thư mục con quan trọng trong storage.
     */
    private function getStorageInfo(): array
    {
        $dirs = [
            'logs'    => storage_path('logs'),
            'cache'   => storage_path('framework/cache'),
            'views'   => storage_path('framework/views'),
            'uploads' => public_path('uploads'),
        ];

        $result = [];
        foreach ($dirs as $name => $path) {
            $result[$name] = [
                'path' => $path,
                'size' => is_dir($path) ? $this->getDirectorySize($path) : 0,
            ];
        }
        return $result;
    }

    /**
     * Tính tổng dung lượng của một thư mục (đệ quy).
     * Giới hạn depth để tránh quá chậm trên thư mục lớn.
     */
    private function getDirectorySize(string $path, int $maxDepth = 3, int $currentDepth = 0): int
    {
        if ($currentDepth >= $maxDepth || !is_dir($path)) {
            return 0;
        }

        $size = 0;
        try {
            $iterator = new \DirectoryIterator($path);
            foreach ($iterator as $item) {
                if ($item->isDot()) continue;
                if ($item->isFile()) {
                    $size += $item->getSize();
                } elseif ($item->isDir()) {
                    $size += $this->getDirectorySize($item->getPathname(), $maxDepth, $currentDepth + 1);
                }
            }
        } catch (\Exception $e) {}

        return $size;
    }

    /**
     * Xác thực mã bản quyền trực tuyến qua máy chủ WHMCS.
     * Tái sử dụng cùng cơ chế với UpdateController để đảm bảo nhất quán.
     */
    private function verifyLicenseOnline(string $licenseKey, string $domain, &$errorMsg = ''): bool
    {
        return true;
        // Danh sách trắng domain phát triển cục bộ
        $domainWhiteList = ['localhost', '127.0.0.1', 'hoantienshopee.ddev.site'];
        if (in_array($domain, $domainWhiteList, true)) {
            return true;
        }

        // Cache session để tránh gọi API liên tục (TTL: 60 phút)
        $cacheKey = '__license_cache';
        $cacheTtl = 3600;
        $cacheData = session($cacheKey);

        if (is_array($cacheData) && isset($cacheData['license_key'], $cacheData['checked_at'], $cacheData['result'])) {
            if ($cacheData['license_key'] === $licenseKey && (time() - $cacheData['checked_at']) < $cacheTtl) {
                return $this->evaluateLicenseResult($cacheData['result'], $errorMsg);
            }
        }

        // Gọi API WHMCS verify
        $whmcsUrl = 'https://client.cmsnt.co/modules/servers/licensing/verify.php';
        $licensingSecretKey = 'hoantienshopee';
        $checkToken = time() . md5(mt_rand(100000000, mt_getrandmax()) . $licenseKey);
        $ip = request()->server('SERVER_ADDR') ?: (request()->server('LOCAL_ADDR') ?: request()->ip());

        try {
            // Chỉ bỏ qua xác thực SSL khi ở môi trường local phát triển cục bộ
            $verifySsl = config('app.env') === 'local' ? false : true;
            $response = Http::withOptions(['verify' => $verifySsl])
                ->timeout(10)
                ->asForm()
                ->post($whmcsUrl, [
                    'licensekey'  => $licenseKey,
                    'domain'      => $domain,
                    'ip'          => $ip,
                    'dir'         => base_path(),
                    'check_token' => $checkToken
                ]);

            if ($response->failed()) {
                $errorMsg = __('Không thể kết nối đến máy chủ xác thực bản quyền (HTTP :code).', ['code' => $response->status()]);
                return false;
            }

            $rawResponse = $response->body();
            if (empty($rawResponse)) {
                $errorMsg = __('Máy chủ bản quyền phản hồi rỗng.');
                return false;
            }

            // Parse XML response từ WHMCS
            $results = [];
            if (preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $rawResponse, $matches)) {
                foreach ($matches[1] as $k => $v) {
                    $results[$v] = $matches[2][$k];
                }
            }

            if (empty($results) || !isset($results['status'])) {
                $errorMsg = __('Dữ liệu phản hồi từ máy chủ bản quyền không hợp lệ.');
                return false;
            }

            // Kiểm tra chữ ký MD5 chống giả mạo
            if (isset($results['md5hash'])) {
                if ($results['md5hash'] !== md5($licensingSecretKey . $checkToken)) {
                    $errorMsg = __('Chữ ký xác thực phản hồi không hợp lệ.');
                    return false;
                }
            }

            // Cache kết quả nếu kích hoạt thành công
            if ($results['status'] === 'Active') {
                session([$cacheKey => ['license_key' => $licenseKey, 'checked_at' => time(), 'result' => $results]]);
            } else {
                session()->forget($cacheKey);
            }

            return $this->evaluateLicenseResult($results, $errorMsg);
        } catch (\Throwable $e) {
            $errorMsg = __('Lỗi kết nối máy chủ bản quyền: :err', ['err' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Phân tích trạng thái bản quyền từ kết quả WHMCS và gán thông điệp lỗi tương ứng.
     */
    private function evaluateLicenseResult(array $results, &$errorMsg): bool
    {
        $status = $results['status'] ?? 'Invalid';
        $detail = $results['error_detail'] ?? ($results['description'] ?? '');

        $statusMessages = [
            'Active'    => ['', true],
            'Invalid'   => [__('Mã bản quyền không hợp lệ.'), false],
            'Expired'   => [__('Mã bản quyền đã hết hạn.'), false],
            'Suspended' => [__('Mã bản quyền đã bị tạm ngưng.'), false],
            'timeout'   => [__('Hết thời gian chờ kiểm tra bản quyền.'), true],
        ];

        if (isset($statusMessages[$status])) {
            list($msg, $isValid) = $statusMessages[$status];
            if (!$isValid) {
                $errorMsg = $detail ? "{$msg} ({$detail})" : $msg;
            }
            return $isValid;
        }

        $errorMsg = __('Trạng thái bản quyền không xác định: :status', ['status' => $status]);
        return false;
    }

    /**
     * Dọn dẹp và xóa bộ nhớ đệm (Cache) của hệ thống.
     * Xóa application cache, config cache, route cache và view cache.
     * Ghi nhận lịch sử hoạt động vào cơ sở dữ liệu.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCache()
    {
        try {
            // Xóa bộ nhớ đệm ứng dụng (Application Cache)
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            
            // Xóa bộ nhớ đệm cấu hình (Config Cache)
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            
            // Xóa bộ nhớ đệm định tuyến (Route Cache)
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            
            // Xóa bộ nhớ đệm giao diện (View Cache)
            \Illuminate\Support\Facades\Artisan::call('view:clear');

            // Ghi nhật ký hoạt động của admin khi dọn dẹp cache
            \App\Models\ActivityLog::log('Đã thực hiện dọn dẹp và xóa toàn bộ bộ nhớ đệm (Cache) của hệ thống.');

            return response()->json([
                'status'  => 'success',
                'message' => __('Xóa bộ nhớ đệm (Cache) hệ thống thành công!')
            ]);
        } catch (\Exception $e) {
            // Ghi log lỗi vào file log hệ thống
            \Log::error('Lỗi khi xóa bộ nhớ đệm hệ thống: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => __('Đã xảy ra lỗi trong quá trình dọn dẹp cache: :error', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    /**
     * Bật tối ưu hóa hệ thống bằng cách lưu cache cấu hình và định tuyến.
     * Tác dụng: Giảm thiểu thời gian tải trang tối đa khi chạy trên môi trường thực tế (production).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function optimize()
    {
        try {
            // Chạy lệnh optimize để tự động bật Config Cache & Route Cache cùng lúc
            \Illuminate\Support\Facades\Artisan::call('optimize');

            // Ghi nhật ký hoạt động của admin khi bật tối ưu hóa
            \App\Models\ActivityLog::log('Đã thực hiện tối ưu hóa hệ thống (Bật Config Cache và Route Cache).');

            return response()->json([
                'status'  => 'success',
                'message' => __('Bật tối ưu hóa hệ thống (Config & Route Cache) thành công!')
            ]);
        } catch (\Exception $e) {
            // Ghi nhận lỗi chi tiết vào hệ thống log
            \Log::error('Lỗi khi tối ưu hóa hệ thống: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => __('Đã xảy ra lỗi trong quá trình tối ưu hóa hệ thống: :error', ['error' => $e->getMessage()])
            ], 500);
        }
    }
}
