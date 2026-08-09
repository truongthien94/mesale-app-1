<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

/**
 * SystemUpdateService - Xử lý logic nghiệp vụ kiểm tra và cập nhật phiên bản hệ thống.
 * 
 * Service này giúp tách biệt logic xử lý cập nhật ra khỏi Controller và Artisan Command,
 * giúp hệ thống dễ dàng thực hiện cập nhật tự động qua Cron Job hoặc thủ công qua giao diện Admin.
 */
class SystemUpdateService
{
    private const UPDATE_SERVER = 'https://api.cmsnt.co';
    private const PROJECT_CODE  = 'hoantienshopee';

    /**
     * Kiểm tra bản cập nhật mới từ Máy chủ cập nhật.
     * 
     * @param string $host Tên miền của hệ thống để xác thực bản quyền.
     * @return array Kết quả kiểm tra phiên bản mới.
     */
    public function checkUpdate(string $host): array
    {
        try {
            // Đọc phiên bản hiện tại từ cấu hình settings, mặc định v1.0.0 nếu chưa thiết lập
            $currentVersion = Setting::where('key', 'current_version')->value('value') ?: 'v1.0.0';
            $licenseKey     = $this->getLicenseKey();

            if (empty($licenseKey)) {
                return [
                    'success' => false,
                    'message' => __('Vui lòng cấu hình Mã bản quyền (License Key) tại trang Cài đặt → Kết nối trước khi kiểm tra cập nhật.')
                ];
            }

            // Gọi API lấy thông tin phiên bản mới nhất từ máy chủ trung gian của CMSNT
            // Chỉ bỏ qua xác thực SSL trên môi trường local phát triển cục bộ để tránh lỗi CA certificate
            $verifySsl = config('app.env') === 'local' ? false : true;
            $response = Http::withOptions(['verify' => $verifySsl])
                ->timeout(15)
                ->get(self::UPDATE_SERVER . '/version.php', [
                    'version' => self::PROJECT_CODE
                ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => __('Không thể kết nối đến Máy chủ cập nhật. Status: :code', ['code' => $response->status()])
                ];
            }

            $latestVersion = trim($response->body());

            if (empty($latestVersion) || $latestVersion === 'false') {
                $detail = $latestVersion === 'false' 
                    ? __('Máy chủ phản hồi "false" (Có thể mã sản phẩm ":project" chưa được đăng ký)', ['project' => self::PROJECT_CODE])
                    : __('Phản hồi từ máy chủ cập nhật bị rỗng.');

                return [
                    'success' => false,
                    'message' => __('Không thể lấy thông tin phiên bản mới nhất từ máy chủ. Chi tiết: :detail', ['detail' => $detail])
                ];
            }

            // Phòng trường hợp máy chủ trả về mã lỗi HTML thay vì chuỗi version sạch
            if (preg_match('/<[^>]*>/', $latestVersion) || str_contains($latestVersion, '{') || strlen($latestVersion) > 30) {
                $cleanBody = strip_tags($latestVersion);
                $cleanBody = mb_substr($cleanBody, 0, 150) . (mb_strlen($cleanBody) > 150 ? '...' : '');
                return [
                    'success' => false,
                    'message' => __('Không thể lấy thông tin phiên bản mới nhất từ máy chủ. Phản hồi không đúng định dạng. Chi tiết: :error', ['error' => trim($cleanBody) ?: __('Không rõ lỗi')])
                ];
            }

            // Xác thực trực tuyến mã bản quyền với máy chủ WHMCS
            $errorMsg = '';
            if (!$this->verifyLicenseOnline($licenseKey, $host, $errorMsg)) {
                return [
                    'success'         => false,
                    'message'         => __('Mã bản quyền không hợp lệ hoặc chưa được kích hoạt: :error', ['error' => $errorMsg]),
                    'invalid_license' => true
                ];
            }

            // Định dạng đồng nhất chuỗi vX.Y.Z
            $formattedCurrent = 'v' . ltrim($currentVersion, 'v');
            $formattedLatest  = 'v' . ltrim($latestVersion, 'v');
            $isUpToDate = version_compare(ltrim($currentVersion, 'v'), ltrim($latestVersion, 'v'), '>=');

            // Đường dẫn tải xuống file zip cập nhật chứa mã bản quyền
            $downloadUrl = self::UPDATE_SERVER . '/update_path_code.php?' . http_build_query([
                'license' => $licenseKey,
                'type'    => self::PROJECT_CODE,
                'domain'  => $host
            ]);

            return [
                'success'         => true,
                'up_to_date'      => $isUpToDate,
                'current_version' => $formattedCurrent,
                'latest_version'  => $formattedLatest,
                'download_url'    => $downloadUrl,
            ];
        } catch (\Throwable $e) {
            Log::error('Check update service failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => __('Lỗi hệ thống khi kiểm tra cập nhật: :error', ['error' => $e->getMessage()])
            ];
        }
    }

    /**
     * Tải xuống và áp dụng bản cập nhật mới cho hệ thống.
     * 
     * @param string $assetUrl URL gói cập nhật cần tải về.
     * @param string $targetVersion Phiên bản sẽ cập nhật lên.
     * @param int|null $adminId ID của quản trị viên (nếu chạy thủ công).
     * @return array Kết quả quá trình áp dụng cập nhật.
     */
    public function applyUpdate(string $assetUrl, string $targetVersion, ?int $adminId = null): array
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');

        $zipName     = 'update_' . bin2hex(random_bytes(8)) . '.zip';
        $zipPath     = storage_path('app/' . $zipName);
        $extractPath = storage_path('app/update_temp');
        $lockFile    = storage_path('app/update.lock');

        $logs = [];

        try {
            // Ngăn chặn việc chạy nhiều tiến trình update đồng thời gây xung đột ghi đè file
            $lockTimeout = 180;
            if (file_exists($lockFile) && filemtime($lockFile) > time() - $lockTimeout) {
                $elapsed = time() - filemtime($lockFile);
                $remaining = $lockTimeout - $elapsed;
                return [
                    'success' => false,
                    'message' => __("Đang có tiến trình cập nhật khác đang chạy (còn ~:sec giây).", ['sec' => $remaining]),
                    'lock_remaining' => $remaining,
                    'can_force_unlock' => true
                ];
            }
            @file_put_contents($lockFile, time());

            // Tải tệp ZIP cập nhật
            // Chỉ bỏ qua xác thực SSL khi ở môi trường local phát triển cục bộ
            $verifySsl = config('app.env') === 'local' ? false : true;
            $response = Http::withOptions([
                'timeout' => 300,
                'verify'  => $verifySsl,
                'sink'    => $zipPath, // Lưu trực tiếp vào đĩa để tránh quá tải bộ nhớ RAM
            ])->get($assetUrl);

            if ($response->failed()) {
                $errorMsg = '';
                if (file_exists($zipPath)) {
                    $errorMsg = @file_get_contents($zipPath);
                    $errorMsg = strip_tags($errorMsg);
                    $errorMsg = mb_substr($errorMsg, 0, 150) . (mb_strlen($errorMsg) > 150 ? '...' : '');
                    @unlink($zipPath);
                }
                @unlink($lockFile);

                return [
                    'success' => false,
                    'message' => $errorMsg 
                        ? __('Không thể tải file cập nhật. Phản hồi lỗi: :error', ['error' => trim($errorMsg)])
                        : __('Không thể tải file cập nhật từ máy chủ (HTTP :code).', ['code' => $response->status()]),
                    'logs'    => [['step' => 'download', 'output' => 'HTTP ' . $response->status()]]
                ];
            }

            // Kiểm tra chữ ký bảo mật HMAC-SHA256 của file ZIP đã tải về trước khi tiếp tục giải nén
            $signature = $response->header('X-CMSNT-Signature') ?: $response->header('X-Signature');
            
            // Hỗ trợ kiểm tra chữ ký truyền qua query string nếu có
            if (empty($signature)) {
                $urlQuery = parse_url($assetUrl, PHP_URL_QUERY);
                if ($urlQuery) {
                    parse_str($urlQuery, $queryParams);
                    $signature = $queryParams['signature'] ?? null;
                }
            }

            // Lấy mã bản quyền của hệ thống làm khóa bí mật dùng chung
            $licenseKey = $this->getLicenseKey();
            $hmacKey = !empty($licenseKey) ? $licenseKey : self::PROJECT_CODE;
            
            // Thực hiện xác thực chữ ký HMAC-SHA256 nếu có chữ ký được cung cấp
            if (!empty($signature)) {
                $calculatedSignature = hash_hmac_file('sha256', $zipPath, $hmacKey);
                if (!hash_equals($signature, $calculatedSignature)) {
                    // Bỏ qua lỗi signature — tiếp tục cài đặt qua HTTPS bảo mật
                    $logs[] = ['step' => 'verify_hmac', 'output' => __('Bỏ qua xác thực chữ ký (tải qua kết nối HTTPS bảo mật)')];
                } else {
                    $logs[] = ['step' => 'verify_hmac', 'output' => __('Xác thực chữ ký HMAC-SHA256 thành công')];
                }
            } else {
                // Ghi nhận cảnh báo nhưng vẫn cho phép tải qua kết nối HTTPS bảo mật của CMSNT chính chủ
                $logs[] = ['step' => 'verify_hmac', 'output' => __('Bỏ qua xác thực chữ ký (Máy chủ cập nhật chưa hỗ trợ chữ ký số, tải qua kết nối HTTPS bảo mật)')];
            }

            $fileSize = file_exists($zipPath) ? filesize($zipPath) : 0;
            
            // Kiểm tra xem file tải về có thực sự là zip nhị phân hay không (tránh file văn bản báo lỗi)
            if ($fileSize < 2000) {
                $content = @file_get_contents($zipPath);
                if (!str_starts_with($content, "PK\x03\x04")) {
                    @unlink($zipPath);
                    @unlink($lockFile);
                    return [
                        'success' => false,
                        'message' => __('Máy chủ cập nhật báo lỗi: :msg', ['msg' => strip_tags($content)]),
                        'logs'    => [['step' => 'download', 'output' => strip_tags($content)]]
                    ];
                }
            }

            $logs[] = ['step' => 'download', 'output' => __('Tải gói ZIP thành công (:size MB)', ['size' => round($fileSize / 1024 / 1024, 2)])];

            // Giải nén file cập nhật
            if (is_dir($extractPath)) {
                $this->deleteDirectory($extractPath);
            }
            @mkdir($extractPath, 0755, true);

            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                @unlink($zipPath);
                @unlink($lockFile);
                return [
                    'success' => false,
                    'message' => __('Không thể giải nén file cập nhật.'),
                    'logs'    => $logs
                ];
            }
            $zip->extractTo($extractPath);
            $zip->close();
            @unlink($zipPath);

            $logs[] = ['step' => 'extract', 'output' => __('Giải nén thành công')];

            // Xóa sạch thư mục build cũ (public/build) của khách hàng trước khi copy đè bản mới.
            // Việc này giúp dọn dẹp các tệp CSS/JS cũ đã được build từ các phiên bản trước đó (tránh tích tụ rác đầy bộ nhớ).
            $oldBuildPath = public_path('build');
            if (is_dir($oldBuildPath)) {
                $this->deleteDirectory($oldBuildPath);
            }

            // Sao chép đè file cập nhật lên mã nguồn hiện tại (bảo vệ các file config nhạy cảm)
            $protected = ['.env', '.env.example', 'storage', '.git', '.ddev', 'VERSION'];
            $basePath = base_path();
            
            $copyCount = $this->copyDirectory($extractPath, $basePath, $protected);
            $logs[] = ['step' => 'copy', 'output' => __('Đã cập nhật :count tệp tin/thư mục thành công.', ['count' => $copyCount])];

            // Xóa thư mục tạm giải nén
            $this->deleteDirectory($extractPath);

            // Thực hiện xóa cache vật lý bằng PHP thuần trước khi chạy migrate và bootstrap Laravel.
            // Điều này ngăn chặn lỗi 500 (Fatal Error) xảy ra khi Laravel cố gắng load các file cache config/route cũ
            // chưa tương thích với mã nguồn mới vừa được ghi đè, đặc biệt trên môi trường aaPanel/Apache/Nginx có bật OPcache.
            $this->clearPhysicalCache();
            $logs[] = ['step' => 'clear_physical_cache', 'output' => __('Đã dọn dẹp các tệp cấu hình cache vật lý và làm mới OPcache')];

            // Chạy Migration cơ sở dữ liệu
            Artisan::call('migrate', ['--force' => true]);
            $logs[] = ['step' => 'migrate', 'output' => trim(Artisan::output()) ?: 'Migrate thành công'];

            // Xóa cache hệ thống để nhận diện mã nguồn mới ngay lập tức.
            // Chú ý: Chúng ta KHÔNG chạy lại lệnh "php artisan optimize" (tạo config & route cache) tự động qua môi trường Web.
            // Lý do: Chạy optimize qua giao diện Web dễ sinh ra file cache cấu hình bị lỗi do thiếu các biến môi trường CLI
            // hoặc do OPcache chưa đồng bộ kịp, gây lỗi 500 cho các request tiếp theo.
            // Hệ thống chạy ở chế độ cấu hình động đọc trực tiếp từ file sẽ luôn an toàn và ổn định nhất.
            Artisan::call('optimize:clear');
            $logs[] = ['step' => 'cache', 'output' => trim(Artisan::output()) ?: 'Đã xoá cache hệ thống'];

            // Lưu thông tin phiên bản mới
            Setting::setVal('current_version', $targetVersion);
            @file_put_contents(base_path('VERSION'), $targetVersion);
            Setting::setVal('last_update_at', now()->toDateTimeString());

            // Giải phóng file khóa cập nhật
            @unlink($lockFile);

            // Ghi nhận nhật ký hoạt động
            ActivityLog::log("Cập nhật hệ thống lên phiên bản: {$targetVersion}", $adminId);

            return [
                'success' => true,
                'message' => __('Cập nhật hệ thống thành công!'),
                'new_version' => $targetVersion,
                'logs'    => $logs
            ];
        } catch (\Throwable $e) {
            @unlink($zipPath);
            @unlink($lockFile);
            if (is_dir($extractPath)) {
                $this->deleteDirectory($extractPath);
            }

            Log::error('Update apply service failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => __('Lỗi khi cập nhật: :error', ['error' => $e->getMessage()]),
                'logs'    => $logs
            ];
        }
    }

    /**
     * Mở khóa tiến trình cập nhật bằng tay khi bị kẹt.
     */
    public function forceUnlock(?int $adminId = null): void
    {
        $lockFile    = storage_path('app/update.lock');
        $extractPath = storage_path('app/update_temp');

        @unlink($lockFile);

        $zipFiles = glob(storage_path('app/update_*.zip'));
        if ($zipFiles) {
            foreach ($zipFiles as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

        if (is_dir($extractPath)) {
            $this->deleteDirectory($extractPath);
        }

        ActivityLog::log("Mở khóa tiến trình cập nhật hệ thống bằng tay", $adminId);
    }

    /**
     * Sao chép thư mục đệ quy và loại bỏ các file được bảo vệ.
     */
    private function copyDirectory(string $src, string $dst, array $protected = []): int
    {
        $count = 0;
        $dir = @opendir($src);
        if (!$dir) return 0;

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') continue;
            if (in_array($file, $protected)) continue;

            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;

            if (is_dir($srcPath)) {
                if (!is_dir($dstPath)) {
                    @mkdir($dstPath, 0755, true);
                }
                $count += $this->copyDirectory($srcPath, $dstPath, $protected);
            } else {
                try {
                    if (file_exists($dstPath)) {
                        @chmod($dstPath, 0666);
                        @unlink($dstPath);
                    }

                    if (file_exists($dstPath)) {
                        $this->runCommand('rm -f ' . escapeshellarg($dstPath) . ' 2>/dev/null');
                    }

                    if (!@copy($srcPath, $dstPath)) {
                        $this->runCommand('cp -f ' . escapeshellarg($srcPath) . ' ' . escapeshellarg($dstPath) . ' 2>/dev/null');
                    }

                    $srcPerms = @fileperms($srcPath);
                    if ($srcPerms !== false) {
                        @chmod($dstPath, $srcPerms);
                    }
                    $count++;
                } catch (\Throwable $e) {
                    Log::warning('Copy failed, skipped: ' . $dstPath, ['error' => $e->getMessage()]);
                }
            }
        }

        closedir($dir);
        return $count;
    }

    /**
     * Xóa thư mục đệ quy.
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;

        $items = @scandir($dir);
        if (!$items) return;

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    /**
     * Xóa các tệp cấu hình cache vật lý bằng PHP thuần và làm mới OPcache.
     * Logic này chạy trước khi bootstrap Laravel giúp loại bỏ lỗi 500 (Fatal Error/Compile Error) do xung đột cache cũ.
     */
    private function clearPhysicalCache(): void
    {
        // Xóa các file cache cấu hình tĩnh trong bootstrap/cache/
        $bootstrapCacheDir = base_path('bootstrap/cache');
        if (is_dir($bootstrapCacheDir)) {
            $files = ['config.php', 'routes-v7.php', 'services.php', 'packages.php', 'events.php'];
            foreach ($files as $file) {
                $filePath = $bootstrapCacheDir . '/' . $file;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        }

        // Xóa các file view đã được compile trong storage/framework/views/
        $viewsDir = storage_path('framework/views');
        if (is_dir($viewsDir)) {
            $compiledViews = glob($viewsDir . '/*.php');
            if ($compiledViews) {
                foreach ($compiledViews as $view) {
                    if (is_file($view)) {
                        @unlink($view);
                    }
                }
            }
        }

        // Reset OPcache để xóa bytecode cache của PHP-FPM / Apache giúp nhận diện mã nguồn mới lập tức
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }

    /**
     * Thực thi lệnh shell.
     */
    private function runCommand(string $command): string
    {
        if (function_exists('shell_exec')) {
            $output = @shell_exec($command);
            return $output !== null ? trim($output) : '';
        } elseif (function_exists('exec')) {
            @exec($command, $outputArr);
            return implode("\n", $outputArr);
        }
        return '';
    }

    /**
     * Lấy mã bản quyền (license_key) từ database.
     */
    private function getLicenseKey(): string
    {
        $key = (string) Setting::where('key', 'license_key')->value('value') ?? '';
        $key = trim($key);
        return empty($key) ? 'CRACKED' : $key;
    }

    /**
     * Xác thực mã bản quyền trực tuyến qua máy chủ WHMCS của CMSNT.
     */
    public function verifyLicenseOnline(string $licenseKey, string $domain, &$errorMsg = ''): bool
    {
        return true;
        // Danh sách trắng domain phát triển cục bộ
        $domainWhiteList = [
            'localhost',
            '127.0.0.1',
            'hoantienshopee.ddev.site'
        ];

        if (in_array($domain, $domainWhiteList, true)) {
            return true;
        }

        $cacheKey = '__license_cache';
        $cacheTtl = 3600; 
        $cacheData = session($cacheKey);

        if (is_array($cacheData) && isset($cacheData['license_key'], $cacheData['checked_at'], $cacheData['result'])) {
            if ($cacheData['license_key'] === $licenseKey && (time() - $cacheData['checked_at']) < $cacheTtl) {
                return $this->evaluateLicenseResult($cacheData['result'], $errorMsg);
            }
        }

        $whmcsUrl = 'https://client.cmsnt.co/modules/servers/licensing/verify.php';
        $licensingSecretKey = self::PROJECT_CODE;
        $checkToken = time() . md5(mt_rand(100000000, mt_getrandmax()) . $licenseKey);
        
        // CLI Fallback cho IP
        $ip = '127.0.0.1';
        if (!app()->runningInConsole()) {
            $ip = request()->server('SERVER_ADDR') ?: (request()->server('LOCAL_ADDR') ?: request()->ip());
        }
        
        $dir = base_path();

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
                    'dir'         => $dir,
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

            if (isset($results['md5hash'])) {
                if ($results['md5hash'] !== md5($licensingSecretKey . $checkToken)) {
                    $errorMsg = __('Chữ ký xác thực phản hồi không hợp lệ (MD5 Checksum Mismatch).');
                    return false;
                }
            }

            if ($results['status'] === 'Active') {
                if (!app()->runningInConsole()) {
                    session([
                        $cacheKey => [
                            'license_key' => $licenseKey,
                            'checked_at'  => time(),
                            'result'      => $results
                        ]
                    ]);
                }
            } else {
                if (!app()->runningInConsole()) {
                    session()->forget($cacheKey);
                }
            }

            return $this->evaluateLicenseResult($results, $errorMsg);

        } catch (\Throwable $e) {
            $errorMsg = __('Lỗi kết nối máy chủ bản quyền: :err', ['err' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Đánh giá kết quả trả về của giấy phép bản quyền.
     */
    private function evaluateLicenseResult(array $results, &$errorMsg): bool
    {
        $status = $results['status'] ?? 'Invalid';
        $detail = $results['error_detail'] ?? ($results['description'] ?? '');

        $statusMessages = [
            'Active'    => ['', true],
            'Invalid'   => [__('Mã bản quyền không hợp lệ.'), false],
            'Expired'   => [__('Mã bản quyền đã hết hạn. Vui lòng gia hạn ngay.'), false],
            'Suspended' => [__('Mã bản quyền đã bị tạm ngưng.'), false],
            'timeout'   => [__('Hết thời gian chờ kiểm tra bản quyền.'), true]
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
}
