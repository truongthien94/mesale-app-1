<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    /**
     * Trần tối đa (đơn vị %) cho phép cấu hình các tỷ lệ hoàn tiền của từng sàn thương mại điện tử.
     *
     * Giải thích: Cho phép vượt mốc 100% để phục vụ các chiến dịch khuyến mãi bù lỗ (trả cho khách
     * nhiều hơn phần hoa hồng sàn trả về), đồng thời vẫn giữ một mức chặn hợp lý nhằm tránh trường hợp
     * quản trị viên gõ nhầm thừa số 0 gây thất thoát tài chính.
     *
     * Khi thay đổi hằng số này phải sửa đồng bộ thuộc tính max của các thẻ input tương ứng tại
     * resources/views/admin/settings/partials/{shopee,tiktok,lazada}.blade.php
     */
    public const MAX_CASHBACK_RATE = 500;

    /**
     * Hiển thị danh sách cấu hình hệ thống.
     * Giải thích logic: Chỉ render tab được chọn qua tham số query 'tab' để tối ưu hiệu suất và tốc độ load trang,
     * tránh việc khởi tạo hàng loạt CKEditor và render dữ liệu của 12 tab cùng một lúc gây lag trình duyệt.
     */
    public function index(Request $request)
    {
        // Kiểm tra khóa bảo mật cron, nếu trống thì tự động sinh khóa ngẫu nhiên và lưu vào cơ sở dữ liệu
        $cronSecretKey = Setting::getVal('cron_secret_key');
        if (empty($cronSecretKey)) {
            $cronSecretKey = bin2hex(random_bytes(16)); // Tạo chuỗi ngẫu nhiên 32 ký tự bảo mật
            Setting::setVal('cron_secret_key', $cronSecretKey, 'Khóa bảo mật dùng để thực thi các tác vụ Cron qua Webhook URL');
        }

        $settings = Setting::pluck('value', 'key')->toArray();
        // Cập nhật lại giá trị mới sinh vào mảng cài đặt để hiển thị ngoài giao diện
        $settings['cron_secret_key'] = $cronSecretKey;

        // Ẩn (mask) các cấu hình nhạy cảm khi đang chạy ở chế độ Demo để bảo vệ thông tin
        if (config('app.demo')) {
            $sensitiveKeys = [
                'smtp_password',
                'google_client_secret',
                'telegram_bot_token',
                'telegram_chat_id',
                'apishopee_key',
                'apitiktok_key',
                'apilazada_user_token',
                'apilazada_app_secret',
                'apilazada_product_key',
                'turnstile_secret_key',
                'proxycheck_api_key',
                'shopee_cookie',
                'tiktok_cookie',
                'license_key',
                'ai_openai_key',
                'ai_deepseek_key',
                'ai_claude_key',
                'ai_gemini_key',
                'openapi_key'
            ];
            foreach ($sensitiveKeys as $key) {
                if (isset($settings[$key]) && !empty($settings[$key])) {
                    $settings[$key] = '****************';
                }
            }
        }
        
        // Xác định tab đang được kích hoạt từ query string, mặc định hiển thị tab cấu hình chung 'general'
        $activeTab = $request->query('tab', 'general');
        
        // Danh sách các tab hợp lệ nhằm bảo vệ an toàn cho hệ thống khỏi tấn công chèn file động (đã hiển thị lại tab shortcuts để admin cấu hình phím tắt iOS Shortcuts)
        $validTabs = ['general', 'shopee', 'tiktok', 'lazada', 'coupons', 'shortcuts', 'mlm', 'ranking', 'reward', 'withdraw', 'shortlink', 'connections', 'security', 'email_templates', 'telegram_templates', 'cronjobs', 'other', 'open_api'];
        if (!in_array($activeTab, $validTabs)) {
            $activeTab = 'general';
        }
        
        // Trần tối đa của các ô nhập tỷ lệ hoàn tiền, đẩy ra view để thuộc tính max của thẻ input
        // luôn khớp với ràng buộc kiểm tra phía server, tránh tình trạng hai bên lệch nhau
        $maxCashbackRate = self::MAX_CASHBACK_RATE;

        return view('admin.settings.index', compact('settings', 'activeTab', 'maxCashbackRate'));
    }

    /**
     * Cập nhật các giá trị cấu hình hệ thống.
     */
    public function update(Request $request)
    {
        // Xác thực các file ảnh tải lên để tránh tải lên file độc hại (RCE)
        $request->validate([
            'logo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'logo_dark_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'favicon_file' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,ico|max:5120',
            'og_image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ], [
            'logo_file.image' => __('Logo tải lên phải là định dạng hình ảnh.'),
            'logo_file.mimes' => __('Logo chỉ hỗ trợ định dạng jpeg, png, jpg, gif, svg, webp.'),
            'logo_file.max' => __('Logo dung lượng tối đa 5MB.'),
            'logo_dark_file.image' => __('Logo tối tải lên phải là định dạng hình ảnh.'),
            'logo_dark_file.mimes' => __('Logo tối chỉ hỗ trợ định dạng jpeg, png, jpg, gif, svg, webp.'),
            'logo_dark_file.max' => __('Logo tối dung lượng tối đa 5MB.'),
            'favicon_file.mimes' => __('Favicon chỉ hỗ trợ định dạng jpeg, png, jpg, gif, svg, webp, ico.'),
            'favicon_file.max' => __('Favicon dung lượng tối đa 5MB.'),
            'og_image_file.image' => __('Ảnh OG Share tải lên phải là định dạng hình ảnh.'),
            'og_image_file.mimes' => __('Ảnh OG Share chỉ hỗ trợ định dạng jpeg, png, jpg, gif, svg, webp.'),
            'og_image_file.max' => __('Ảnh OG Share dung lượng tối đa 5MB.'),
        ]);

        // Ràng buộc khoảng giá trị hợp lệ cho các tỷ lệ hoàn tiền của từng sàn (đơn vị %).
        // Giải thích: Trước đây các ô này chỉ bị chặn bằng thuộc tính max của thẻ input phía trình duyệt,
        // nên vẫn có thể gửi thẳng request (Postman, sửa DOM) để lưu giá trị âm hoặc vượt trần cho phép.
        // Bổ sung kiểm tra phía server để chặn triệt để, đồng bộ đúng với trần đang áp dụng ở giao diện.
        // Lưu ý: dùng quy tắc 'sometimes' vì mỗi lần lưu chỉ có các trường của tab đang mở được gửi lên.
        $maxCashbackRate = self::MAX_CASHBACK_RATE;
        $rateLabels = [
            'shopee_cashback_rate'      => __('Tỷ lệ hoàn trả khách (Shopee)'),
            'shopee_fake_cashback_rate' => __('Tỷ lệ hoàn ảo (Shopee)'),
            'tiktok_cashback_rate'      => __('Tỷ lệ hoàn trả khách (TikTok Shop)'),
            'tiktok_fake_cashback_rate' => __('Tỷ lệ hoàn ảo (TikTok Shop)'),
            'lazada_cashback_rate'      => __('Tỷ lệ hoàn trả khách (Lazada)'),
            'lazada_fake_cashback_rate' => __('Tỷ lệ hoàn ảo (Lazada)'),
        ];

        $rateRules = [];
        $rateMessages = [];
        foreach ($rateLabels as $rateKey => $rateLabel) {
            $rateRules[$rateKey] = 'sometimes|nullable|numeric|min:0|max:' . $maxCashbackRate;
            $rateMessages[$rateKey . '.numeric'] = __(':field phải là một con số hợp lệ.', ['field' => $rateLabel]);
            $rateMessages[$rateKey . '.min'] = __(':field không được là số âm.', ['field' => $rateLabel]);
            $rateMessages[$rateKey . '.max'] = __(':field tối đa là :max%.', ['field' => $rateLabel, 'max' => $maxCashbackRate]);
        }

        $request->validate($rateRules, $rateMessages);

        // 1. Xử lý tải các file ảnh lên trực tiếp (nếu có)
        $fileKeys = [
            'logo_file' => 'site_logo',
            'logo_dark_file' => 'site_logo_dark',
            'favicon_file' => 'site_favicon',
            'og_image_file' => 'site_og_image'
        ];

        $uploadedUrls = [];

        foreach ($fileKeys as $fileInput => $settingKey) {
            // Kiểm tra xem file upload có hợp lệ không
            if ($request->hasFile($fileInput) && $request->file($fileInput)->isValid()) {
                $file = $request->file($fileInput);
                
                // Lấy URL ảnh cũ từ cấu hình database để xoá tệp cũ trên đĩa
                $oldUrl = Setting::getVal($settingKey);
                if (!empty($oldUrl)) {
                    $oldPath = parse_url($oldUrl, PHP_URL_PATH);
                    // Chỉ thực hiện xoá nếu file nằm trong thư mục uploads/settings của hệ thống
                    if ($oldPath && str_contains($oldPath, 'uploads/settings/')) {
                        $oldFilePath = public_path(ltrim($oldPath, '/'));
                        if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                            @unlink($oldFilePath); // Xoá file cũ để tối ưu dung lượng đĩa
                        }
                    }
                }

                // Lấy extension an toàn từ file
                $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
                // Chặn cứng các extension nguy hiểm
                if (in_array($extension, ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phar', 'htaccess', 'js'])) {
                    $extension = 'png';
                }

                // Đặt tên file duy nhất theo thời gian để tránh trùng lặp
                $fileName = time() . '_' . $fileInput . '.' . $extension;
                
                // Di chuyển file vào thư mục lưu trữ public/uploads/settings
                $file->move(public_path('uploads/settings'), $fileName);
                
                // Sinh đường dẫn URL đầy đủ của file vừa tải lên
                $fileUrl = asset('uploads/settings/' . $fileName);
                
                // Lưu vết URL vừa upload để loại trừ ở bước text input
                $uploadedUrls[$settingKey] = $fileUrl;
                
                // Cập nhật hoặc tạo mới bản ghi cấu hình trong database
                Setting::updateOrCreate(
                    ['key' => $settingKey],
                    ['value' => $fileUrl]
                );
                
                // Xoá bộ nhớ cache cũ để hệ thống cập nhật ảnh mới ngay lập tức
                Cache::forget("setting.{$settingKey}");
            }
        }

        // 2. Nhận dữ liệu text key => value thông thường từ request (trừ token csrf, active_tab, các file input và các text input tương ứng với file đã upload)
        $excludeKeys = array_merge(
            ['_token', 'active_tab'],
            array_keys($fileKeys),
            array_keys($uploadedUrls)
        );

        // Ràng buộc bảo mật (Stored XSS): Chỉ Super Admin (role_id = null) hoặc tài khoản có quyền 'manage_custom_code' mới được chỉnh sửa các mã nhúng tùy biến JS/CSS
        if (!auth()->user()->hasPermission('manage_custom_code')) {
            $excludeKeys = array_merge($excludeKeys, ['custom_css', 'custom_js_header', 'custom_js_body']);
        }

        // Nếu hệ thống đang chạy ở chế độ Demo (APP_DEMO = true), loại bỏ license_key
        // và các trường cấu hình Shopee, TikTok Shop nhạy cảm để tránh việc ghi đè hoặc lộ thông số kết nối API.
        if (config('app.demo')) {
            $excludeKeys = array_merge($excludeKeys, [
                'license_key',
                
                // Các key cấu hình Shopee
                'shopee_status', 'shopee_app_id', 'shopee_cashback_rate', 'shopee_fake_cashback_rate', 'shopee_tax_deduct_rate',
                'shopee_show_current_price',
                'apishopee_status', 'apishopee_url', 'apishopee_key',
                'cache_clean_estimated_hours', 'cache_clean_normal_days',
                'shopee_check_product_match', 'shopee_auto_cashback_future_orders',
                'hp_cashback_notice', 'order_code_prefix', 'order_code_prefix_position',
                'order_code_random_length', 'order_code_random_type',
                'shopee_link_converter',
                
                // Các key cấu hình TikTok Shop
                'tiktok_status', 'tiktok_cashback_rate', 'tiktok_fake_cashback_rate', 'tiktok_tax_deduct_rate',
                'tiktok_show_current_price',
                'apitiktok_status', 'apitiktok_url', 'tiktok_creator_username', 'apitiktok_key',
                'cache_clean_estimated_hours_tiktok', 'cache_clean_normal_days_tiktok',
                'tiktok_check_product_match', 'tiktok_auto_cashback_future_orders', 'hp_cashback_notice_tiktok',
                'order_code_prefix_tiktok', 'order_code_prefix_position_tiktok',
                'order_code_random_length_tiktok', 'order_code_random_type_tiktok',
                'tiktok_apply_bonus_commission',

                // Các key cấu hình Lazada
                'lazada_status', 'lazada_cashback_rate', 'lazada_fake_cashback_rate',
                'lazada_show_current_price', 'lazada_subid_param',
                'apilazada_status', 'apilazada_url', 'apilazada_user_token',
                'apilazada_app_key', 'apilazada_app_secret',
                'apilazada_product_status', 'apilazada_product_url', 'apilazada_product_key',
                'cache_clean_estimated_hours_lazada', 'cache_clean_normal_days_lazada',
                'lazada_check_product_match', 'lazada_auto_cashback_future_orders', 'lazada_apply_bonus_commission', 'hp_cashback_notice_lazada',
                'order_code_prefix_lazada', 'order_code_prefix_position_lazada',
                'order_code_random_length_lazada', 'order_code_random_type_lazada',

                // Các key cấu hình AI nhạy cảm
                'ai_openai_key', 'ai_deepseek_key', 'ai_claude_key', 'ai_gemini_key',
                
                // Các key cấu hình Open API nhạy cảm
                'openapi_key'
            ]);
        }

        $settingsData = $request->except($excludeKeys);

        // Xử lý lưu cấu hình Model AI tùy chỉnh của các nhà cung cấp (OpenAI, DeepSeek, Claude, Gemini)
        // Nếu người dùng chọn option 'custom', ta sẽ ghi nhận giá trị nhập ở ô input tùy chỉnh tương ứng.
        
        // 1. Đối với OpenAI
        if ($request->has('ai_openai_model')) {
            if ($request->input('ai_openai_model') === 'custom') {
                $settingsData['ai_openai_model'] = $request->input('ai_openai_model_custom', '');
            } else {
                $settingsData['ai_openai_model'] = $request->input('ai_openai_model');
            }
        }
        unset($settingsData['ai_openai_model_custom']);

        // 2. Đối với DeepSeek
        if ($request->has('ai_deepseek_model')) {
            if ($request->input('ai_deepseek_model') === 'custom') {
                $settingsData['ai_deepseek_model'] = $request->input('ai_deepseek_model_custom', '');
            } else {
                $settingsData['ai_deepseek_model'] = $request->input('ai_deepseek_model');
            }
        }
        unset($settingsData['ai_deepseek_model_custom']);

        // 3. Đối với Claude (Anthropic)
        if ($request->has('ai_claude_model')) {
            if ($request->input('ai_claude_model') === 'custom') {
                $settingsData['ai_claude_model'] = $request->input('ai_claude_model_custom', '');
            } else {
                $settingsData['ai_claude_model'] = $request->input('ai_claude_model');
            }
        }
        unset($settingsData['ai_claude_model_custom']);

        // 4. Đối với Google Gemini
        if ($request->has('ai_gemini_model')) {
            if ($request->input('ai_gemini_model') === 'custom') {
                $settingsData['ai_gemini_model'] = $request->input('ai_gemini_model_custom', '');
            } else {
                $settingsData['ai_gemini_model'] = $request->input('ai_gemini_model');
            }
        }
        unset($settingsData['ai_gemini_model_custom']);

        // Ràng buộc nghiệp vụ: Luôn phải còn ít nhất một phương thức định danh tài khoản khi đăng ký (Email hoặc Số điện thoại).
        // Nếu admin lỡ tắt cả hai, hệ thống tự bật lại đăng ký bằng Email để không khoá toàn bộ chức năng đăng ký thành viên mới.
        if (isset($settingsData['register_identifier_email'], $settingsData['register_identifier_phone'])) {
            if ($settingsData['register_identifier_email'] !== '1' && $settingsData['register_identifier_phone'] !== '1') {
                $settingsData['register_identifier_email'] = '1';
            }
        }

        // Kiểm tra thay đổi tiền tố Admin và validate
        $hasAdminPrefixChanged = false;
        if (isset($settingsData['admin_prefix'])) {
            $oldPrefix = Setting::getVal('admin_prefix', 'admin');
            $newPrefix = trim($settingsData['admin_prefix']);
            if (empty($newPrefix) || !preg_match('/^[a-zA-Z0-9_-]+$/', $newPrefix)) {
                $settingsData['admin_prefix'] = $oldPrefix;
            } else {
                $settingsData['admin_prefix'] = $newPrefix;
                if ($oldPrefix !== $newPrefix) {
                    $hasAdminPrefixChanged = true;
                }
            }
        }

        foreach ($settingsData as $key => $value) {
            // Ràng buộc bảo mật (Anti-SSRF): Xác thực các địa chỉ API Shopee & TikTok Shop không được trỏ về mạng nội bộ hoặc IP Private
            if ($key === 'apishopee_url' && !empty($value)) {
                if (!\App\Helpers\SecurityHelper::validateSsfUrl($value)) {
                    return back()->with('error', __('Đường dẫn API Shopee không hợp lệ hoặc chứa nguy cơ bảo mật SSRF!'))->withInput();
                }
            }

            if ($key === 'apitiktok_url' && !empty($value)) {
                if (!\App\Helpers\SecurityHelper::validateSsfUrl($value)) {
                    return back()->with('error', __('Đường dẫn API TikTok Shop không hợp lệ hoặc chứa nguy cơ bảo mật SSRF!'))->withInput();
                }
            }

            if ($key === 'apilazada_url' && !empty($value)) {
                if (!\App\Helpers\SecurityHelper::validateSsfUrl($value)) {
                    return back()->with('error', __('Đường dẫn API Lazada không hợp lệ hoặc chứa nguy cơ bảo mật SSRF!'))->withInput();
                }
            }

            if ($key === 'apilazada_product_url' && !empty($value)) {
                if (!\App\Helpers\SecurityHelper::validateSsfUrl($value)) {
                    return back()->with('error', __('Đường dẫn API lấy thông tin sản phẩm Lazada không hợp lệ hoặc chứa nguy cơ bảo mật SSRF!'))->withInput();
                }
            }

            // Validate JSON milestone trước khi lưu để đảm bảo dữ liệu hợp lệ
            // Key phải là số nguyên dương (ngày), value phải là số không âm (tiền thưởng)
            if ($key === 'checkin_streak_milestones' && !empty($value)) {
                $decoded = json_decode($value, true);
                if (!is_array($decoded)) {
                    // JSON không hợp lệ, giữ nguyên giá trị cũ và bỏ qua
                    continue;
                }
                // Lọc bỏ các entry có key hoặc value không hợp lệ
                $sanitized = [];
                foreach ($decoded as $day => $coins) {
                    if (is_numeric($day) && (int)$day > 0 && is_numeric($coins) && (float)$coins >= 0) {
                        $sanitized[(int)$day] = (float)$coins;
                    }
                }
                $value = json_encode($sanitized);
            }

            // Cập nhật hoặc tạo mới các tham số cấu hình text
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
            
            // Xoá cache của tham số tương ứng
            Cache::forget("setting.{$key}");
        }

        // Nếu đổi tiền tố đường dẫn quản trị, thực hiện clear route cache của Laravel để nhận diện đường dẫn mới ngay lập tức
        if ($hasAdminPrefixChanged) {
            try {
                \Illuminate\Support\Facades\Artisan::call('route:clear');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Lỗi xóa cache route khi đổi admin_prefix: " . $e->getMessage());
            }
        }

        // Ghi lại lịch sử hoạt động của Admin
        ActivityLog::log("Cập nhật cấu hình hệ thống", auth()->id());

        // Lấy tab đang hoạt động từ request gửi lên (mặc định là 'general' nếu không có) để chuyển hướng chính xác về tab cũ
        $activeTab = $request->input('active_tab', 'general');

        // Chuyển hướng trở về trang cấu hình kèm theo tham số query 'tab' giúp tối ưu render ở server-side và mượt mà ở client-side
        return redirect()->to(route('admin.settings.index', ['tab' => $activeTab]))->with('success', 'Cập nhật cấu hình hệ thống thành công!');
    }

    /**
     * Gửi email kiểm thử SMTP kết nối động theo tham số trên form.
     */
    public function testSmtp(Request $request)
    {
        $request->validate([
            'smtp_host'         => 'required|string',
            'smtp_port'         => 'required|numeric',
            'smtp_username'     => 'required|string',
            'smtp_password'     => 'required|string',
            'smtp_encryption'   => 'required|string',
            'smtp_from_name'    => 'required|string',
            'smtp_from_address' => 'nullable|email',
            'test_email'        => 'required|email',
        ]);

        // Cấu hình động Mailer tạm thời
        $config = [
            'transport'  => 'smtp',
            'host'       => $request->smtp_host,
            'port'       => $request->smtp_port,
            'encryption' => $request->smtp_encryption === 'none' ? null : $request->smtp_encryption,
            'username'   => $request->smtp_username,
            'password'   => $request->smtp_password,
            'timeout'    => 15,
        ];

        try {
            $smtpFromAddress = $request->smtp_from_address;
            if (empty($smtpFromAddress)) {
                $smtpFromAddress = filter_var($request->smtp_username, FILTER_VALIDATE_EMAIL) ? $request->smtp_username : config('mail.from.address');
            }

            config(['mail.mailers.smtp_test' => $config]);
            config(['mail.default' => 'smtp_test']);
            config(['mail.from.address' => $smtpFromAddress]);
            config(['mail.from.name' => $request->smtp_from_name]);

            \Illuminate\Support\Facades\Mail::raw('Chúc mừng! Đây là email tự động gửi từ hệ thống Hoàn Tiền Shopee để kiểm tra cấu hình SMTP kết nối thành công!', function ($message) use ($request) {
                $message->to($request->test_email)
                        ->subject('Kiểm tra kết nối gửi Email SMTP thành công!');
            });

            // Ghi log hoạt động
            ActivityLog::log("Kiểm thử gửi email SMTP tới: {$request->test_email}", auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Gửi email thử nghiệm thành công! Vui lòng kiểm tra hộp thư ' . $request->test_email . ' của bạn.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi SMTP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gửi email kiểm thử mẫu template cụ thể bằng dữ liệu giả lập.
     * Chức năng này giúp Admin kiểm tra giao diện và định dạng email trực quan trên hòm thư thực tế
     * mà không cần phải thực hiện các hành động trigger như đặt lại mật khẩu hoặc rút tiền thật.
     */
    public function testEmail(Request $request)
    {
        // Kiểm tra dữ liệu đầu vào bao gồm key template, tiêu đề, nội dung đang soạn thảo và địa chỉ email nhận
        $request->validate([
            'template_key' => 'required|string',
            'subject'      => 'required|string',
            'content'      => 'required|string',
            'test_email'   => 'required|email',
        ]);

        $subject = $request->input('subject');
        $content = $request->input('content');
        $to = $request->input('test_email');

        // Khai báo bộ dữ liệu giả lập tương ứng với các biến động trong email template
        // Việc giả lập này giúp nội dung email gửi đi hiển thị đầy đủ thông tin mẫu thay vì các thẻ trống {}
        $demoData = [
            'name'           => 'Nguyễn Văn A',
            'email'          => 'nguyenvana@gmail.com',
            'reset_url'      => 'https://hoantienshopee.ddev.site/password/reset/token123456',
            'otp'            => '123456',
            'dashboard_url'  => 'https://hoantienshopee.ddev.site/dashboard',
            'amount'         => '150,000',
            'payment_method' => 'Chuyển khoản ngân hàng',
            'account_name'   => 'NGUYEN VAN A',
            'account_number' => '19036789123456',
            'bank_row'       => '<tr><td style="padding: 8px 0; color: #666666;">Ngân hàng nhận:</td><td style="padding: 8px 0; text-align: right; font-weight: 500;">Techcombank (Chi nhánh Hà Nội)</td></tr>',
            'gift_title'     => 'Thẻ cào Viettel 50k',
            'gift_price'     => '50,000',
            'gift_data'      => 'Code: 1234567890 | Seri: 0987654321',
            'gift_data_row'  => '<tr><td style="padding: 8px 0; color: #666666;">Thông tin quà tặng:</td><td style="padding: 8px 0; text-align: right; font-weight: 500; font-family: monospace;">Code: 1234567890 | Seri: 0987654321</td></tr>',
            'notes'          => 'Đã duyệt qua hệ thống tự động.',
            'notes_row'      => '<tr><td style="padding: 8px 0; color: #666666;">Ghi chú:</td><td style="padding: 8px 0; text-align: right; font-style: italic;">Đã duyệt qua hệ thống tự động.</td></tr>',
            'year'           => date('Y'),
        ];

        // Thay thế toàn bộ các thẻ biến động bằng dữ liệu giả lập tương ứng
        foreach ($demoData as $key => $val) {
            $subject = str_replace("{{$key}}", (string)$val, $subject);
            $content = str_replace("{{$key}}", (string)$val, $content);
        }

        try {
            // Thực hiện gửi email HTML qua view template động
            \Illuminate\Support\Facades\Mail::send('emails.dynamic', ['content' => $content], function ($message) use ($to, $subject) {
                $message->to($to);
                $message->subject($subject);
            });

            // Ghi nhật ký hoạt động của quản trị viên để phục vụ giám sát bảo mật
            ActivityLog::log("Gửi thử email template [{$request->template_key}] tới: {$to}", auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Gửi email thử nghiệm mẫu thành công! Vui lòng kiểm tra hộp thư ' . $to
            ]);
        } catch (\Exception $e) {
            // Trả về lỗi chi tiết nếu quá trình kết nối hoặc gửi email SMTP thất bại
            return response()->json([
                'success' => false,
                'message' => 'Lỗi gửi email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gửi thử tin nhắn Telegram để kiểm tra cấu hình kết nối.
     */
    public function testTelegram(Request $request)
    {
        $request->validate([
            'bot_token' => 'required|string',
            'chat_id'   => 'required|string',
            'message'   => 'required|string',
        ]);

        $botToken = $request->input('bot_token');
        $chatId = $request->input('chat_id');
        $message = $request->input('message');

        try {
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            $data = [
                'chat_id'                  => $chatId,
                'text'                     => $message,
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => true,
            ];

            $response = \Illuminate\Support\Facades\Http::timeout(10)->post($url, $data);

            if ($response->successful()) {
                // Ghi nhật ký hoạt động của quản trị viên để phục vụ giám sát bảo mật
                ActivityLog::log("Gửi thử tin nhắn Telegram tới Chat ID: {$chatId}", auth()->id());

                return response()->json([
                    'success' => true,
                    'message' => 'Gửi tin nhắn thử nghiệm Telegram thành công! Vui lòng kiểm tra ứng dụng Telegram.'
                ]);
            } else {
                $errorResponse = $response->json();
                $description = $errorResponse['description'] ?? 'Lỗi không xác định từ API Telegram';
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi từ Telegram: ' . $description
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi kết nối Telegram: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gửi thử yêu cầu ngắn tới AI API để kiểm tra cấu hình kết nối.
     * Giải thích: Chức năng này giúp quản trị viên xác thực API Key và Model của các bên cung cấp AI 
     * (OpenAI, DeepSeek, Claude, Gemini) có hoạt động chính xác hay không mà không cần lưu lại cấu hình.
     */
    public function testAI(Request $request)
    {
        // Chặn gọi API thực tế nếu đang chạy ở chế độ demo để tránh lộ/lạm dụng tài nguyên
        if (config('app.demo', false) || env('APP_DEMO', false)) {
            return response()->json([
                'success' => false,
                'message' => __('Tính năng kiểm tra kết nối API bị vô hiệu hóa trong phiên bản thử nghiệm (Demo).')
            ]);
        }

        $request->validate([
            'provider' => 'required|string|in:openai,deepseek,claude,gemini',
            'api_key'  => 'required|string',
            'model'    => 'nullable|string',
        ]);

        $provider = $request->input('provider');
        $apiKey = $request->input('api_key');
        $model = $request->input('model');

        try {
            $aiService = app(\App\Services\AIService::class);
            
            // Sử dụng một prompt cực ngắn để kiểm thử và giảm tối đa token tiêu hao
            $messages = [
                ['role' => 'user', 'content' => 'Say "OK"']
            ];

            $response = $aiService->chat($messages, [
                'provider' => $provider,
                'model' => $model,
                'api_key' => $apiKey,
            ]);

            // Nếu phản hồi trả về chứa chuỗi thông báo lỗi được định nghĩa trong AIService
            if (str_contains($response, 'Lỗi khi kết nối với AI API') || str_contains($response, 'failed')) {
                return response()->json([
                    'success' => false,
                    'message' => $response
                ]);
            }

            // Ghi nhận nhật ký bảo mật hành động của quản trị viên
            ActivityLog::log("Kiểm tra kết nối AI API thành công [Provider: {$provider}, Model: {$model}]", auth()->id());

            return response()->json([
                'success' => true,
                'message' => __('Kết nối API AI thành công! Phản hồi từ AI: ":response"', ['response' => $response])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Lỗi kết nối AI: :error', ['error' => $e->getMessage()])
            ]);
        }
    }

    /**
     * API AJAX: Tạo nhanh nội dung HTML cho popup thông báo trang chủ bằng AI.
     * Trả về JSON { success: bool, content?: string, message?: string }.
     */
    public function generatePopupAi(Request $request)
    {
        // Chặn gọi API thực tế khi đang ở chế độ demo để tránh lạm dụng tài nguyên
        if (config('app.demo', false)) {
            return response()->json([
                'success' => false,
                'message' => __('Tính năng tạo nội dung bằng AI bị vô hiệu hóa trong phiên bản thử nghiệm (Demo).'),
            ], 422);
        }

        // Kiểm tra dịch vụ AI đã được bật trong cài đặt hệ thống chưa
        if (Setting::getVal('ai_status', '0') !== '1') {
            return response()->json([
                'success' => false,
                'message' => __('Dịch vụ AI hiện đang tắt. Vui lòng kích hoạt trong tab Kết nối > AI.'),
            ], 422);
        }

        $validated = $request->validate([
            'prompt' => 'required|string|max:2000',
            'tone'   => 'nullable|string|max:50',
        ], [
            'prompt.required' => __('Vui lòng mô tả nội dung thông báo bạn muốn tạo.'),
        ]);

        $siteName   = Setting::getVal('site_name', 'Hoàn Tiền Shopee');
        $themeColor = Setting::getVal('theme_color', '#ee4d2d');
        $tone       = $validated['tone'] ?? 'than-thien';

        $toneMap = [
            'than-thien'    => 'thân thiện, gần gũi',
            'chuyen-nghiep' => 'chuyên nghiệp, trang trọng',
            'khan-cap'      => 'khẩn cấp, thúc đẩy hành động ngay',
            'hao-hung'      => 'hào hứng, nhiệt huyết, nhiều cảm xúc',
        ];
        $toneText = $toneMap[$tone] ?? 'thân thiện, gần gũi';

        // System prompt: yêu cầu AI trả về đúng định dạng JSON để dễ phân tích
        $systemPrompt = <<<SYS
Bạn là chuyên gia thiết kế nội dung cho nền tảng hoàn tiền mua sắm Shopee tên là "{$siteName}".
Nhiệm vụ: viết nội dung HTML cho một POPUP THÔNG BÁO hiển thị ở trang chủ bằng TIẾNG VIỆT theo yêu cầu của người dùng (thông báo khuyến mãi, sự kiện, bảo trì, hướng dẫn...).

QUY TẮC BẮT BUỘC:
- Chỉ trả về DUY NHẤT một object JSON hợp lệ, KHÔNG kèm giải thích, KHÔNG bọc trong dấu ```.
- Cấu trúc JSON: {"content": "mã HTML của nội dung popup"}.
- Trường "content" là HTML dùng inline CSS (style="..."), gọn gàng, vừa khít trong một hộp popup hẹp (khoảng 400-500px), KHÔNG kèm thẻ <html>, <head>, <body>.
- Dùng màu thương hiệu chủ đạo là {$themeColor} cho tiêu đề, nút bấm và điểm nhấn.
- Có thể có tiêu đề nổi bật, đoạn mô tả ngắn gọn và nút kêu gọi hành động (CTA) nếu phù hợp.
- KHÔNG dùng <script>, KHÔNG dùng JavaScript.
- Giọng điệu: {$toneText}.
SYS;

        try {
            $aiService = app(\App\Services\AIService::class);
            $response = $aiService->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => 'Yêu cầu nội dung popup: ' . $validated['prompt']],
            ], [
                'max_tokens'  => 3000,
                'temperature' => 0.8,
            ]);

            // AIService trả về chuỗi lỗi đã chuẩn hóa thay vì ném exception khi gặp sự cố
            if (str_contains($response, 'Lỗi khi kết nối với AI API') || str_contains($response, 'Dịch vụ AI hiện đang')) {
                return response()->json(['success' => false, 'message' => $response], 422);
            }

            // Loại bỏ rào ``` nếu AI lỡ bọc kết quả trong code fence
            $clean = trim($response);
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
            $clean = preg_replace('/\s*```$/', '', $clean);

            // Cắt lấy đoạn JSON nằm giữa dấu { đầu tiên và } cuối cùng
            $start = strpos($clean, '{');
            $end   = strrpos($clean, '}');
            $content = null;

            if ($start !== false && $end !== false && $end > $start) {
                $json = substr($clean, $start, $end - $start + 1);
                $parsed = json_decode($json, true);
                if (is_array($parsed)) {
                    $content = $parsed['content'] ?? null;
                }
            }

            // Nếu không phân tích được JSON, coi toàn bộ phản hồi là nội dung HTML
            if (empty($content)) {
                $content = $clean;
            }

            ActivityLog::log(__('Tạo nội dung popup thông báo bằng AI'), auth()->id());

            return response()->json([
                'success' => true,
                'content' => $content,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Lỗi khi tạo nội dung AI: :error', ['error' => $e->getMessage()]),
            ], 422);
        }
    }

    /**
     * API AJAX: Tạo nhanh nội dung mẫu email giao dịch (subject + HTML) bằng AI.
     * Trả về JSON { success: bool, subject?: string, content?: string, message?: string }.
     */
    public function generateEmailTemplateAi(Request $request)
    {
        // Chặn gọi API thực tế khi đang ở chế độ demo để tránh lạm dụng tài nguyên
        if (config('app.demo', false)) {
            return response()->json([
                'success' => false,
                'message' => __('Tính năng tạo nội dung bằng AI bị vô hiệu hóa trong phiên bản thử nghiệm (Demo).'),
            ], 422);
        }

        // Kiểm tra dịch vụ AI đã được bật trong cài đặt hệ thống chưa
        if (Setting::getVal('ai_status', '0') !== '1') {
            return response()->json([
                'success' => false,
                'message' => __('Dịch vụ AI hiện đang tắt. Vui lòng kích hoạt trong tab Kết nối > AI.'),
            ], 422);
        }

        // Bản đồ các mẫu email: mô tả mục đích + danh sách biến động được phép dùng
        $templates = [
            'forgot_password'     => ['Đặt lại mật khẩu tài khoản', ['{name}', '{email}', '{reset_url}', '{year}']],
            'otp'                 => ['Gửi mã xác thực đăng nhập (OTP)', ['{name}', '{email}', '{otp}', '{year}']],
            'verify_email'        => ['Xác minh địa chỉ email tài khoản', ['{name}', '{email}', '{otp}', '{year}']],
            'welcome'             => ['Chào mừng thành viên mới đăng ký', ['{name}', '{email}', '{dashboard_url}', '{year}']],
            'withdrawal_created'  => ['Thông báo đã tạo lệnh rút tiền, đang chờ duyệt', ['{name}', '{email}', '{amount}', '{payment_method}', '{account_name}', '{account_number}', '{bank_row}', '{year}']],
            'withdrawal_approved' => ['Thông báo rút tiền đã được duyệt thành công', ['{name}', '{email}', '{amount}', '{payment_method}', '{account_name}', '{account_number}', '{bank_row}', '{year}']],
            'gift_approved'       => ['Thông báo duyệt đơn đổi quà thành công, kèm thông tin quà', ['{name}', '{email}', '{gift_title}', '{gift_price}', '{gift_data}', '{notes}', '{year}']],
            'gift_created'        => ['Thông báo đã tạo đơn đổi quà, đang xử lý', ['{name}', '{email}', '{gift_title}', '{gift_price}', '{year}']],
            'cashback_created'    => ['Thông báo ghi nhận đơn hàng hoàn tiền mới', ['{name}', '{email}', '{order_id}', '{platform}', '{product_name}', '{price}', '{cashback_amount}', '{year}']],
            'cashback_approved'   => ['Thông báo đơn hoàn tiền đã được duyệt, cộng tiền vào ví', ['{name}', '{email}', '{order_id}', '{platform}', '{product_name}', '{price}', '{cashback_amount}', '{dashboard_url}', '{year}']],
        ];

        $validated = $request->validate([
            'prompt'       => 'nullable|string|max:2000',
            'key'          => 'required|string|in:' . implode(',', array_keys($templates)),
            'tone'         => 'nullable|string|max:50',
            'with_subject' => 'nullable|boolean',
        ]);

        $siteName    = Setting::getVal('site_name', 'Hoàn Tiền Shopee');
        $themeColor  = Setting::getVal('theme_color', '#ee4d2d');
        $tone        = $validated['tone'] ?? 'chuyen-nghiep';
        $withSubject = $request->boolean('with_subject');
        [$purpose, $variables] = $templates[$validated['key']];
        $varList = implode(', ', $variables);

        $toneMap = [
            'than-thien'    => 'thân thiện, gần gũi',
            'chuyen-nghiep' => 'chuyên nghiệp, trang trọng',
            'hao-hung'      => 'hào hứng, nhiều cảm xúc',
        ];
        $toneText = $toneMap[$tone] ?? 'chuyên nghiệp, trang trọng';

        $extra = !empty($validated['prompt'])
            ? "Yêu cầu bổ sung từ người dùng: {$validated['prompt']}"
            : 'Không có yêu cầu bổ sung, hãy soạn nội dung chuẩn mực cho mục đích trên.';

        // System prompt: yêu cầu AI trả về đúng định dạng JSON để dễ phân tích
        $systemPrompt = <<<SYS
Bạn là chuyên gia thiết kế email giao dịch (transactional email) cho nền tảng hoàn tiền mua sắm Shopee tên là "{$siteName}".
Nhiệm vụ: soạn một MẪU EMAIL bằng TIẾNG VIỆT cho mục đích: "{$purpose}".

QUY TẮC BẮT BUỘC:
- Chỉ trả về DUY NHẤT một object JSON hợp lệ, KHÔNG kèm giải thích, KHÔNG bọc trong dấu ```.
- Cấu trúc JSON: {"subject": "tiêu đề email", "content": "mã HTML đầy đủ của email"}.
- Trường "content" là HTML hoàn chỉnh dùng inline CSS (style="..."), responsive, rộng tối đa 560px, căn giữa, bo góc, có header và footer.
- Dùng màu thương hiệu chủ đạo là {$themeColor} cho tiêu đề, nút bấm và điểm nhấn.
- BẮT BUỘC dùng các biến động sau ở vị trí phù hợp, GIỮ NGUYÊN VĂN dạng một cặp ngoặc nhọn (KHÔNG đổi tên, KHÔNG thêm khoảng trắng): {$varList}.
- Dùng {year} cho năm ở footer. KHÔNG dùng thẻ <script>, KHÔNG dùng JavaScript.
- Giọng điệu: {$toneText}.
SYS;

        try {
            $aiService = app(\App\Services\AIService::class);
            $response = $aiService->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $extra],
            ], [
                'max_tokens'  => 4000,
                'temperature' => 0.75,
            ]);

            // AIService trả về chuỗi lỗi đã chuẩn hóa thay vì ném exception khi gặp sự cố
            if (str_contains($response, 'Lỗi khi kết nối với AI API') || str_contains($response, 'Dịch vụ AI hiện đang')) {
                return response()->json(['success' => false, 'message' => $response], 422);
            }

            // Loại bỏ rào ``` nếu AI lỡ bọc kết quả trong code fence
            $clean = trim($response);
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
            $clean = preg_replace('/\s*```$/', '', $clean);

            // Cắt lấy đoạn JSON nằm giữa dấu { đầu tiên và } cuối cùng
            $start = strpos($clean, '{');
            $end   = strrpos($clean, '}');
            $subject = null;
            $content = null;

            if ($start !== false && $end !== false && $end > $start) {
                $json = substr($clean, $start, $end - $start + 1);
                $parsed = json_decode($json, true);
                if (is_array($parsed)) {
                    $subject = $parsed['subject'] ?? null;
                    $content = $parsed['content'] ?? null;
                }
            }

            // Nếu không phân tích được JSON, coi toàn bộ phản hồi là nội dung HTML
            if (empty($content)) {
                $content = $clean;
            }

            ActivityLog::log("Tạo mẫu email bằng AI [Template: {$validated['key']}]", auth()->id());

            return response()->json([
                'success' => true,
                'subject' => $withSubject ? $subject : null,
                'content' => $content,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Lỗi khi tạo nội dung AI: :error', ['error' => $e->getMessage()]),
            ], 422);
        }
    }

    /**
     * Ép buộc chạy ngay một tác vụ tự động (Cron Job) cụ thể từ phía Quản trị.
     * Giải thích: Chức năng này giúp quản trị viên kích hoạt ngay lập tức các tác vụ đồng bộ Shopee, 
     * gửi email/telegram đang chờ trong hàng đợi, hoặc sinh lại file sitemap SEO mà không cần chờ 
     * hệ thống cron job chạy tự động theo thời gian định kỳ.
     */
    public function runCron(Request $request)
    {
        $request->validate([
            'task' => 'required|string',
        ]);

        $task = $request->input('task');

        try {
            switch ($task) {
                case 'schedule:heartbeat':
                    // Thủ công cập nhật thời gian chạy Scheduler gần nhất
                    Setting::setVal('schedule_last_run', now()->toDateTimeString());
                    $message = __('Tác vụ ghi nhận trạng thái Scheduler đã được thực thi thành công.');
                    break;

                case 'process-email-queue':
                    // Kích hoạt gửi email hàng loạt từ hàng đợi
                    $cronController = app(\App\Http\Controllers\CronController::class);
                    // Ghép key bảo mật cron job vào query parameters của request hiện tại thay vì merge vào post data để CronController có thể lấy qua $request->query('key')
                    $secretKey = Setting::getVal('cron_secret_key');
                    $request->query->set('key', $secretKey);
                    
                    $response = $cronController->processEmailQueue($request);
                    $data = json_decode($response->getContent(), true);
                    $message = $data['message'] ?? __('Tác vụ gửi email hàng loạt đã được thực thi.');
                    break;

                case 'process-telegram-queue':
                    // Kích hoạt gửi tin nhắn Telegram hàng loạt từ hàng đợi
                    $cronController = app(\App\Http\Controllers\CronController::class);
                    // Ghép key bảo mật cron job vào query parameters của request để bảo vệ truy cập và khớp với $request->query('key')
                    $secretKey = Setting::getVal('cron_secret_key');
                    $request->query->set('key', $secretKey);
                    
                    $response = $cronController->processTelegramQueue($request);
                    $data = json_decode($response->getContent(), true);
                    $message = $data['message'] ?? __('Tác vụ gửi tin nhắn Telegram hàng loạt đã được thực thi.');
                    break;

                case 'system-maintenance':
                    // Kích hoạt bảo trì hệ thống (dọn log, xóa cache sản phẩm quá hạn)
                    $cronController = app(\App\Http\Controllers\CronController::class);
                    // Ghép key bảo mật cron job vào query parameters để vượt qua kiểm tra $request->query('key')
                    $secretKey = Setting::getVal('cron_secret_key');
                    $request->query->set('key', $secretKey);
                    
                    $response = $cronController->systemMaintenance($request);
                    $data = json_decode($response->getContent(), true);
                    $message = $data['message'] ?? __('Tác vụ bảo trì hệ thống đã được thực thi.');
                    if (isset($data['actions']) && is_array($data['actions'])) {
                        $message .= ' ' . __('Chi tiết:') . ' ' . implode(' | ', $data['actions']);
                    }
                    break;

                case 'sitemap:generate':
                    // Gọi lệnh sinh sitemap.xml phục vụ tối ưu tìm kiếm (SEO)
                    \Illuminate\Support\Facades\Artisan::call('sitemap:generate');
                    $message = __('Tác vụ sinh tệp sitemap.xml phục vụ SEO đã được thực thi thành công.');
                    break;

                case 'shopee:sync-commissions':
                    // Gọi lệnh đồng bộ hoa hồng đơn hàng Shopee Affiliate của các tài khoản
                    \Illuminate\Support\Facades\Artisan::call('shopee:sync-commissions');
                    $message = __('Tác vụ đồng bộ hoa hồng đơn hàng từ Shopee Affiliate đã được thực thi thành công.');
                    break;

                case 'tiktok:sync-commissions':
                    // Gọi lệnh đồng bộ hoa hồng đơn hàng TikTok Shop của các tài khoản
                    \Illuminate\Support\Facades\Artisan::call('tiktok:sync-commissions');
                    $message = __('Tác vụ đồng bộ hoa hồng đơn hàng từ TikTok Shop đã được thực thi thành công.');
                    break;

                case 'lazada:sync-commissions':
                    // Gọi lệnh đồng bộ hoa hồng đơn hàng Lazada của các tài khoản
                    \Illuminate\Support\Facades\Artisan::call('lazada:sync-commissions');
                    $message = __('Tác vụ đồng bộ hoa hồng đơn hàng từ Lazada đã được thực thi thành công.');
                    break;

                case 'coupons:sync':
                    // Gọi lệnh đồng bộ danh sách mã giảm giá đa sàn.
                    // Truyền --force để nút "Đồng bộ ngay" thủ công vẫn chạy dù công tắc tự động đồng bộ đang TẮT.
                    \Illuminate\Support\Facades\Artisan::call('coupons:sync', ['--force' => true]);
                    $message = __('Tác vụ đồng bộ danh sách mã giảm giá đa sàn đã được thực thi thành công.');
                    break;

                case 'system:auto-update':
                    // Gọi lệnh tự động kiểm tra và cập nhật phiên bản mới hệ thống
                    \Illuminate\Support\Facades\Artisan::call('system:auto-update');
                    $message = __('Tác vụ tự động kiểm tra và cập nhật phiên bản mới hệ thống đã được thực thi thành công.');
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => __('Tác vụ không hợp lệ.')
                    ], 400);
            }

            // Ghi nhận hành động chạy tay tác vụ của Admin vào nhật ký hoạt động
            ActivityLog::log("Kích hoạt chạy thủ công tác vụ: {$task}", auth()->id());

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Kích hoạt tác vụ cron [{$task}] thất bại: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Lỗi khi thực thi tác vụ: :error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * Khôi phục mẫu email mặc định bằng cách xóa setting tùy chỉnh khỏi DB.
     * Khi setting bị xóa, Blade fallback (??) sẽ tự động hiển thị nội dung gốc hardcoded.
     */
    public function resetEmailDefault(Request $request, string $key)
    {
        // Whitelist các template key hợp lệ để tránh xóa nhầm setting khác
        $allowedKeys = [
            'forgot_password', 'otp', 'verify_email', 'welcome',
            'withdrawal_created', 'withdrawal_approved',
            'gift_approved', 'gift_created',
            'cashback_created', 'cashback_approved'
        ];

        if (!in_array($key, $allowedKeys)) {
            return response()->json(['message' => __('Template không hợp lệ.')], 400);
        }

        // Xóa cả tiêu đề và nội dung email template khỏi DB
        Setting::where('key', 'email_subject_' . $key)->delete();
        Setting::where('key', 'email_content_' . $key)->delete();

        // Xóa cache tương ứng
        Cache::forget('setting.email_subject_' . $key);
        Cache::forget('setting.email_content_' . $key);

        // Ghi log hoạt động
        ActivityLog::log("Khôi phục mẫu email mặc định: {$key}", auth()->id());

        return response()->json(['message' => __('Đã khôi phục mẫu email mặc định thành công! Trang sẽ được tải lại.')]);
    }

}

