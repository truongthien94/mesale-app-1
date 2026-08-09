<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * InstallerController - Trình cài đặt hệ thống qua trình duyệt.
 * 
 * Luồng wizard 5 bước:
 * 1. Kiểm tra yêu cầu môi trường (PHP version, extensions)
 * 2. Kiểm tra quyền ghi thư mục & file
 * 3. Cấu hình kết nối Database
 * 4. Tạo tài khoản Admin đầu tiên
 * 5. Hoàn tất cài đặt & tự xóa file lock
 */
class InstallerController extends Controller
{
    /**
     * Phiên bản PHP tối thiểu yêu cầu bởi Laravel 11.x
     */
    private const MIN_PHP_VERSION = '8.2';

    /**
     * Danh sách extension PHP bắt buộc để hệ thống hoạt động
     * Key: tên extension, Value: mô tả mục đích sử dụng
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
        'pcntl'      => 'Quản lý tiến trình chạy ngầm (Yêu cầu bật các hàm: pcntl_signal, pcntl_alarm, pcntl_fork, pcntl_async_signals)',
    ];

    /**
     * Danh sách extension khuyến nghị (không bắt buộc nhưng nên có)
     */
    private const RECOMMENDED_EXTENSIONS = [
        'redis'  => 'Cache & Session hiệu năng cao (Redis)',
        'zip'    => 'Nén/giải nén file (Cập nhật hệ thống)',
        'exif'   => 'Đọc metadata ảnh (xoay ảnh tự động)',
        'intl'   => 'Hỗ trợ quốc tế hóa (Ngôn ngữ, Tiền tệ)',
    ];

    /**
     * Danh sách thư mục/file cần quyền ghi để Laravel hoạt động.
     * 
     * Tại sao cần phân quyền ghi cho các thư mục này:
     * - storage/*: Lưu trữ logs hệ thống, sessions người dùng và cache framework.
     * - bootstrap/cache: Tăng tốc độ tải trang bằng cách lưu trữ cache route/config.
     * - public/uploads: Nơi lưu trữ hình ảnh tải lên từ admin và khách hàng.
     * - lang: Lưu trữ bản dịch tĩnh dạng JSON phục vụ cho tính năng đa ngôn ngữ.
     * - .env: Lưu thông tin cấu hình kết nối database, khóa bảo mật của hệ thống.
     */
    private const WRITABLE_PATHS = [
        'storage/app'           => 'Lưu trữ file ứng dụng',
        'storage/framework'     => 'Lưu trữ cache & session',
        'storage/logs'          => 'Ghi log hoạt động hệ thống',
        'bootstrap/cache'       => 'Cache cấu hình framework',
        'public/uploads'        => 'Lưu trữ ảnh upload từ Admin',
        'lang'                  => 'Lưu trữ bản dịch/ngôn ngữ hệ thống',
        '.env'                  => 'File cấu hình môi trường',
    ];

    // ============================================================
    // BƯỚC 1: KIỂM TRA YÊU CẦU MÔI TRƯỜNG
    // ============================================================

    /**
     * Hiển thị trang kiểm tra yêu cầu hệ thống (PHP version, extensions).
     * Đây là bước đầu tiên trong wizard cài đặt.
     */
    public function stepRequirements()
    {
        $phpVersion = PHP_VERSION;
        $phpVersionOk = version_compare($phpVersion, self::MIN_PHP_VERSION, '>=');

        // Kiểm tra từng extension bắt buộc để đảm bảo hệ thống có đủ thư viện hoạt động
        $requiredExtensions = [];
        $allRequiredPassed = true;
        foreach (self::REQUIRED_EXTENSIONS as $ext => $desc) {
            $loaded = extension_loaded($ext);

            // Business Rule: Laravel Task Scheduler cần extension pcntl hoạt động bình thường.
            // Nếu extension được bật nhưng một trong các hàm pcntl_* bị cấm (disable_functions) thì cron vẫn bị lỗi crash.
            // Do đó cần kiểm tra extension cùng với các hàm pcntl_signal, pcntl_alarm, pcntl_fork, pcntl_async_signals phải khả dụng.
            if ($ext === 'pcntl') {
                $loaded = $loaded
                    && function_exists('pcntl_signal')
                    && function_exists('pcntl_alarm')
                    && function_exists('pcntl_fork')
                    && function_exists('pcntl_async_signals');
            }

            $requiredExtensions[$ext] = [
                'description' => $desc,
                'loaded'      => $loaded,
            ];
            if (!$loaded) {
                $allRequiredPassed = false;
            }
        }

        // Kiểm tra extension khuyến nghị (không chặn cài đặt)
        $recommendedExtensions = [];
        foreach (self::RECOMMENDED_EXTENSIONS as $ext => $desc) {
            $recommendedExtensions[$ext] = [
                'description' => $desc,
                'loaded'      => extension_loaded($ext),
            ];
        }

        // Chỉ cho phép chuyển bước tiếp theo nếu PHP version OK và tất cả extension bắt buộc đã cài
        $canProceed = $phpVersionOk && $allRequiredPassed;

        return view('installer.step1-requirements', compact(
            'phpVersion',
            'phpVersionOk',
            'requiredExtensions',
            'recommendedExtensions',
            'canProceed'
        ));
    }

    // ============================================================
    // BƯỚC 2: KIỂM TRA QUYỀN GHI THƯ MỤC
    // ============================================================

    /**
     * Hiển thị trang kiểm tra quyền ghi file/thư mục.
     * Laravel và hệ thống đa ngôn ngữ yêu cầu quyền ghi vào storage, bootstrap/cache, lang, .env.
     * 
     * Tại sao logic kiểm tra này cần xử lý thông minh:
     * - Nếu file/thư mục đã tồn tại, ta kiểm tra trực tiếp quyền ghi (is_writable).
     * - Nếu file/thư mục chưa tồn tại (như .env khi cài mới tinh), ta phải kiểm tra quyền ghi của thư mục cha (dirname)
     *   để đảm bảo PHP có quyền tạo mới tệp tin/thư mục đó. Nếu không xử lý, hàm is_writable sẽ trả về false
     *   và chặn người dùng cài đặt vô lý.
     */
    public function stepPermissions()
    {
        $permissions = [];
        $allPassed = true;

        foreach (self::WRITABLE_PATHS as $path => $desc) {
            $fullPath = base_path($path);
            $writable = false;

            if (file_exists($fullPath)) {
                // Tệp tin hoặc thư mục đã tồn tại, kiểm tra quyền ghi trực tiếp
                $writable = is_writable($fullPath);
            } else {
                // Tệp tin hoặc thư mục chưa tồn tại (ví dụ: file .env chưa được tạo)
                // Kiểm tra quyền ghi của thư mục cha trực tiếp để đảm bảo hệ thống có thể tự tạo mới
                $parentDir = dirname($fullPath);
                $writable = file_exists($parentDir) && is_writable($parentDir);
            }

            $permissions[$path] = [
                'description' => $desc,
                'writable'    => $writable,
                'full_path'   => $fullPath,
            ];

            if (!$writable) {
                $allPassed = false;
            }
        }

        return view('installer.step2-permissions', compact('permissions', 'allPassed'));
    }

    // ============================================================
    // BƯỚC 3: CẤU HÌNH CƠ SỞ DỮ LIỆU
    // ============================================================

    /**
     * Hiển thị form nhập thông tin kết nối Database.
     */
    public function stepDatabase()
    {
        return view('installer.step3-database');
    }

    /**
     * Xử lý POST: Kiểm tra kết nối DB, ghi file .env, chạy migration & seeder.
     * 
     * Luồng xử lý:
     * 1. Validate input từ form
     * 2. Thử kết nối database bằng PDO để kiểm tra credential
     * 3. Ghi thông tin kết nối vào file .env
     * 4. Xóa cache config cũ để Laravel đọc .env mới
     * 5. Chạy artisan migrate để tạo cấu trúc bảng
     * 6. Chạy artisan db:seed để khởi tạo dữ liệu mặc định
     */
    public function stepDatabaseProcess(Request $request)
    {
        $request->validate([
            'db_host'     => 'required|string',
            'db_port'     => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
            'license_key' => 'required|string|max:255',
        ]);

        $host       = $request->input('db_host');
        $port       = $request->input('db_port');
        $database   = $request->input('db_database');
        $username   = $request->input('db_username');
        $password   = $request->input('db_password', '');
        $licenseKey = trim($request->input('license_key'));

        // Xác thực mã bản quyền trực tuyến với máy chủ WHMCS của CMSNT trước khi cho phép thiết lập hệ thống
        $errorMsg = '';
        $updateService = app(\App\Services\SystemUpdateService::class);
        if (!$updateService->verifyLicenseOnline($licenseKey, $request->getHost(), $errorMsg)) {
            return back()->withErrors([
                'license_key' => 'Giấy phép kích hoạt không hợp lệ: ' . $errorMsg
            ])->withInput();
        }

        // Kiểm tra kết nối database bằng PDO thuần trước khi ghi .env
        // Tránh trường hợp ghi sai thông tin khiến Laravel không thể boot
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database}";
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
            ]);

            // Kiểm tra database đã có bảng nào chưa (cảnh báo ghi đè)
            $stmt = $pdo->query("SHOW TABLES");
            $existingTables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $pdo = null; // Đóng kết nối PDO
        } catch (\PDOException $e) {
            // Trả về lỗi kết nối cho user biết để sửa thông tin
            return back()->withErrors([
                'db_connection' => 'Không thể kết nối database: ' . $e->getMessage()
            ])->withInput();
        }

        // Nếu database đã có bảng và không chọn force, cảnh báo người dùng
        if (!empty($existingTables) && !$request->boolean('force_overwrite')) {
            return back()->withErrors([
                'db_not_empty' => 'Database đã chứa ' . count($existingTables) . ' bảng dữ liệu. Chọn "Ghi đè dữ liệu cũ" nếu bạn muốn xóa và cài đặt lại từ đầu.'
            ])->withInput()->with('show_force_option', true);
        }

        // Ghi thông tin database vào file .env
        $this->updateEnvFile([
            'DB_CONNECTION' => 'mariadb',
            'DB_HOST'       => $host,
            'DB_PORT'       => $port,
            'DB_DATABASE'   => $database,
            'DB_USERNAME'   => $username,
            'DB_PASSWORD'   => $password,
            'APP_URL'       => url('/'),
        ]);

        // Xóa toàn bộ cache để Laravel đọc .env mới
        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
        } catch (\Exception $e) {
            // Bỏ qua lỗi clear cache vì chưa có database cũ
        }

        // Thiết lập lại kết nối DB runtime với thông tin mới
        // Vì config:clear chỉ xóa cache file, cần override config runtime
        config([
            'database.default' => 'mariadb',
            'database.connections.mariadb.host'     => $host,
            'database.connections.mariadb.port'      => $port,
            'database.connections.mariadb.database'  => $database,
            'database.connections.mariadb.username'  => $username,
            'database.connections.mariadb.password'  => $password,
        ]);

        // Purge kết nối cũ và reconnect với config mới
        DB::purge('mariadb');
        DB::reconnect('mariadb');

        // Chạy migration: tạo toàn bộ cấu trúc bảng từ đầu
        // Dùng --force vì đang ở môi trường production
        try {
            $forceFresh = !empty($existingTables) && $request->boolean('force_overwrite');
            if ($forceFresh) {
                // Nếu force overwrite: xóa toàn bộ bảng rồi migrate lại
                Artisan::call('migrate:fresh', ['--force' => true]);
            } else {
                // Database trống: chạy migration bình thường
                Artisan::call('migrate', ['--force' => true]);
            }

            // Khởi tạo dữ liệu hệ thống sạch qua SystemInitSeeder (không kèm user test)
            Artisan::call('db:seed', [
                '--class' => 'SystemInitSeeder',
                '--force' => true
            ]);

            // Lưu mã bản quyền vào bảng settings để dùng cho kiểm tra cập nhật online sau này
            DB::table('settings')->updateOrInsert(
                ['key' => 'license_key'],
                ['value' => $licenseKey, 'description' => 'Mã giấy phép kích hoạt hệ thống']
            );
        } catch (\Exception $e) {
            return back()->withErrors([
                'migration' => 'Lỗi khi tạo cấu trúc database: ' . $e->getMessage()
            ])->withInput();
        }

        // Tạo APP_KEY mới nếu chưa có (bảo mật encryption)
        if (empty(config('app.key')) || config('app.key') === 'base64:') {
            Artisan::call('key:generate', ['--force' => true]);
        }

        // Đánh dấu bước 3 đã hoàn thành trong session
        session(['installer_db_done' => true]);

        return redirect()->route('installer.step4');
    }

    /**
     * API test kết nối database qua AJAX.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testDatabaseConnection(Request $request)
    {
        $request->validate([
            'db_host'     => 'required|string',
            'db_port'     => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        $host     = $request->input('db_host');
        $port     = $request->input('db_port');
        $database = $request->input('db_database');
        $username = $request->input('db_username');
        $password = $request->input('db_password', '');

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database}";
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
            ]);
            $pdo = null;

            return response()->json([
                'success' => true,
                'message' => __('Kết nối database thành công!')
            ]);
        } catch (\PDOException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Kết nối thất bại: :error', ['error' => $e->getMessage()])
            ], 400);
        }
    }

    // ============================================================
    // BƯỚC 4: TẠO TÀI KHOẢN ADMIN
    // ============================================================

    /**
     * Hiển thị form tạo tài khoản Admin đầu tiên.
     */
    public function stepAdmin()
    {
        return view('installer.step4-admin');
    }

    /**
     * Xử lý POST: Tạo tài khoản Admin trong database.
     * Tài khoản admin này sẽ có quyền cao nhất (Super Admin).
     */
    public function stepAdminProcess(Request $request)
    {
        $request->validate([
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|max:255',
            'admin_password' => 'required|string|min:6|confirmed',
            'admin_phone'    => 'nullable|string|max:20',
            'site_name'      => 'required|string|max:255',
        ]);

        try {
            // Cập nhật tên website trong Settings
            DB::table('settings')->updateOrInsert(
                ['key' => 'site_name'],
                ['value' => $request->input('site_name'), 'description' => 'Tên website chính thức']
            );

            // Lưu mật khẩu admin tạm thời vào Settings để hiển thị rõ ở bước cuối (xóa sau khi hoàn tất)
            DB::table('settings')->updateOrInsert(
                ['key' => 'temp_admin_password'],
                ['value' => $request->input('admin_password'), 'description' => 'Mật khẩu admin tạm thời']
            );

            // Cập nhật APP_NAME trong .env
            $this->updateEnvFile([
                'APP_NAME' => '"' . $request->input('site_name') . '"',
            ]);

            // Kiểm tra xem đã có admin với email mặc định từ seeder không
            // Nếu có thì cập nhật, nếu không thì tạo mới
            $existingAdmin = DB::table('users')
                ->where('role', 'admin')
                ->first();

            $adminData = [
                'name'              => $request->input('admin_name'),
                'email'             => $request->input('admin_email'),
                'phone'             => $request->input('admin_phone', ''),
                'password'          => Hash::make($request->input('admin_password')),
                'role'              => 'admin',
                'status'            => 'active',
                'referral_code'     => strtoupper(Str::random(8)),
                'email_verified_at' => now(),
            ];

            if ($existingAdmin) {
                // Cập nhật admin từ seeder thành thông tin thực tế của người cài đặt
                DB::table('users')
                    ->where('id', $existingAdmin->id)
                    ->update($adminData);
            } else {
                DB::table('users')->insert(array_merge($adminData, [
                    'balance'         => 0,
                    'total_cashback'  => 0,
                    'total_withdrawn' => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]));
            }

            // Đánh dấu bước 4 hoàn thành và lưu thông tin đăng nhập tạm thời
            session([
                'installer_admin_done' => true,
                'installer_admin_email' => $request->input('admin_email'),
                'installer_admin_password' => $request->input('admin_password'),
            ]);

            return redirect()->route('installer.step5');
        } catch (\Exception $e) {
            return back()->withErrors([
                'admin_create' => 'Lỗi tạo tài khoản Admin: ' . $e->getMessage()
            ])->withInput();
        }
    }

    // ============================================================
    // BƯỚC 5: HOÀN TẤT CÀI ĐẶT
    // ============================================================

    /**
     * Hiển thị trang hoàn tất cài đặt.
     * Cho phép người dùng xác nhận và khóa installer.
     */
    public function stepFinish()
    {
        $admin = DB::table('users')
            ->where('role', 'admin')
            ->orderBy('id', 'asc')
            ->first();

        $adminEmail = $admin ? $admin->email : session('installer_admin_email');

        // Lấy mật khẩu tạm thời từ Settings để đảm bảo lúc nào cũng hiển thị rõ mật khẩu (không sợ mất session)
        $tempPasswordSetting = DB::table('settings')->where('key', 'temp_admin_password')->first();
        $adminPassword = $tempPasswordSetting ? $tempPasswordSetting->value : session('installer_admin_password', '******** (Mật khẩu sếp đã thiết lập)');

        return view('installer.step5-finish', compact('adminEmail', 'adminPassword'));
    }

    /**
     * Xử lý POST: Đánh dấu cài đặt hoàn tất.
     * 
     * Tạo file installed.lock trong storage để ngăn truy cập installer lần sau.
     * Thiết lập các cờ production-ready trong .env.
     */
    public function stepFinishProcess()
    {
        try {
            // Tạo file lock để đánh dấu đã cài đặt thành công
            // Middleware InstallerCheck sẽ kiểm tra file này
            $lockContent = json_encode([
                'installed_at'   => now()->toIso8601String(),
                'installed_by'   => request()->ip(),
                'app_version'    => config('app.name', 'HoanTienShopee'),
                'php_version'    => PHP_VERSION,
                'laravel_version' => app()->version(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            File::put(storage_path('installed.lock'), $lockContent);

            // Thiết lập production-ready flags trong .env
            $this->updateEnvFile([
                'APP_ENV'   => 'production',
                'APP_DEBUG' => 'false',
            ]);

            // Xóa cache và optimize cho production
            try {
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');
                Artisan::call('optimize');
            } catch (\Exception $e) {
                // Không chặn luồng nếu optimize thất bại
            }

            // Xóa mật khẩu admin tạm thời khỏi settings để đảm bảo an toàn tuyệt đối
            DB::table('settings')->where('key', 'temp_admin_password')->delete();

            // Xóa session installer
            session()->forget([
                'installer_db_done',
                'installer_admin_done',
                'installer_admin_email',
                'installer_admin_password'
            ]);

            return redirect('/')->with('success', 'Cài đặt hệ thống hoàn tất! Bạn có thể đăng nhập bằng tài khoản Admin vừa tạo.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'finish' => 'Lỗi hoàn tất cài đặt: ' . $e->getMessage()
            ]);
        }
    }

    // ============================================================
    // HÀM TIỆN ÍCH NỘI BỘ
    // ============================================================

    /**
     * Cập nhật giá trị trong file .env mà không làm mất các cấu hình khác.
     * 
     * Logic: Đọc nội dung .env -> tìm key cần sửa -> thay giá trị -> ghi lại.
     * Nếu key chưa tồn tại thì thêm dòng mới ở cuối file.
     * 
     * @param array $values Mảng key => value cần cập nhật
     */
    private function updateEnvFile(array $values): void
    {
        $envPath = base_path('.env');

        // Nếu file .env chưa tồn tại, sao chép từ .env.example
        if (!File::exists($envPath)) {
            $examplePath = base_path('.env.example');
            if (File::exists($examplePath)) {
                File::copy($examplePath, $envPath);
            } else {
                // Tạo file .env trống nếu không có .env.example
                File::put($envPath, '');
            }
        }

        $envContent = File::get($envPath);

        foreach ($values as $key => $value) {
            // Pattern tìm dòng chứa key (hỗ trợ cả giá trị có dấu ngoặc kép)
            $pattern = "/^{$key}=.*/m";

            if (preg_match($pattern, $envContent)) {
                // Key đã tồn tại -> cập nhật giá trị
                $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
            } else {
                // Key chưa tồn tại -> thêm dòng mới ở cuối
                $envContent .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $envContent);
    }

    /**
     * API endpoint kiểm tra trạng thái cài đặt.
     * Trả về JSON cho Ajax request từ frontend.
     */
    public function checkStatus()
    {
        return response()->json([
            'installed' => File::exists(storage_path('installed.lock')),
        ]);
    }
}
