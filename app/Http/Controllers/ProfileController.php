<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Setting;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use PragmaRX\Google2FA\Google2FA;

class ProfileController extends Controller
{


    /**
     * Hiển thị trang cấu hình tài khoản và thông tin cá nhân.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Lấy danh sách các phiên hoạt động thực tế từ bảng sessions trong database
        $loginSessions = \DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) {
                $parsed = $this->parseUserAgent($session->user_agent);
                return (object) [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'device_os' => $parsed['device'],
                    'browser' => $parsed['browser'],
                    'icon' => $parsed['icon'],
                    'created_at' => \Carbon\Carbon::createFromTimestamp($session->last_activity),
                    'is_current_device' => $session->id === request()->session()->getId()
                ];
            });

        return view('dashboard.profile', compact('user', 'loginSessions'));
    }

    /**
     * Đăng xuất khỏi một phiên thiết bị cụ thể.
     */
    public function logoutSession(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Không cho phép tự xoá phiên hiện tại qua route này
        if ($id === $request->session()->getId()) {
            return back()->with('error', 'Không thể đăng xuất phiên hiện tại của bạn tại đây. Vui lòng sử dụng nút Đăng xuất trên menu.');
        }

        // Xoá session trong database
        $deleted = \DB::table('sessions')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted) {
            ActivityLog::log('Đã đăng xuất một phiên thiết bị từ xa', $user->id);
            return back()->with('success', 'Đã đăng xuất thiết bị thành công.');
        }

        return back()->with('error', 'Không tìm thấy phiên thiết bị hoặc phiên đã hết hạn.');
    }

    /**
     * Phân tích chuỗi User Agent thô để nhận dạng Hệ điều hành (OS), Trình duyệt và Icon.
     * Hỗ trợ hiển thị giao diện đẹp, trực quan hơn cho người dùng.
     */
    private function parseUserAgent($userAgent)
    {
        if (empty($userAgent)) {
            return [
                'device' => __('Thiết bị không xác định'),
                'browser' => __('Trình duyệt không xác định'),
                'icon' => 'monitor'
            ];
        }

        // 1. Nhận diện Hệ điều hành (OS) và Icon đại diện tương ứng
        $os = 'OS không xác định';
        $icon = 'monitor';
        
        if (preg_match('/iphone/i', $userAgent)) {
            $os = 'iPhone (iOS)';
            $icon = 'smartphone';
        } elseif (preg_match('/ipad/i', $userAgent)) {
            $os = 'iPad (iPadOS)';
            $icon = 'tablet';
        } elseif (preg_match('/android/i', $userAgent)) {
            $os = 'Android';
            $icon = 'smartphone';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $os = 'macOS';
            $icon = 'laptop';
        } elseif (preg_match('/windows|win32/i', $userAgent)) {
            $os = 'Windows';
            $icon = 'monitor';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $os = 'Linux';
            $icon = 'monitor';
        }

        // 2. Nhận diện Trình duyệt
        $browser = 'Trình duyệt không xác định';
        if (preg_match('/chrome/i', $userAgent) && !preg_match('/edge|edg/i', $userAgent) && !preg_match('/opr/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/edge|edg/i', $userAgent)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/opr/i', $userAgent)) {
            $browser = 'Opera';
        } elseif (preg_match('/fb_iab|fbav/i', $userAgent)) {
            $browser = 'Facebook App';
        }

        return [
            'device' => $os,
            'browser' => $browser,
            'icon' => $icon
        ];
    }

    /**
     * Cập nhật thông tin cá nhân cơ bản (Tên, Số điện thoại).
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Chuẩn hoá số điện thoại về dạng thống nhất trước khi kiểm tra tính duy nhất
        if ($request->filled('phone')) {
            $request->merge(['phone' => User::normalizePhone($request->phone)]);
        }

        // Thành viên đăng ký bằng Số điện thoại được phép bổ sung email để bảo mật tài khoản.
        // Khi tài khoản đã có email thì email là cố định, mọi giá trị gửi lên đều bị bỏ qua.
        $canAddEmail = empty($user->email);

        $rules = [
            'name' => 'required|string|max:255',
            'phone' => ['nullable', 'string', 'regex:/^0\d{8,10}$/'],
        ];

        // Chỉ ràng buộc SĐT là duy nhất khi hệ thống cho phép đăng nhập bằng Số điện thoại,
        // tránh làm kẹt việc cập nhật hồ sơ của các website đang có dữ liệu SĐT trùng lặp từ trước
        if (Setting::getVal('register_identifier_phone', '0') === '1') {
            $rules['phone'][] = Rule::unique('users', 'phone')->ignore($user->id);
        }

        if ($canAddEmail) {
            $request->merge(['email' => strtolower(trim((string) $request->email))]);
            $rules['email'] = ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)];
        }

        $request->validate($rules, [
            'name.required' => __('Vui lòng nhập họ và tên của bạn.'),
            'phone.regex' => __('Số điện thoại không đúng định dạng (bắt đầu bằng số 0 và có từ 9 đến 11 chữ số).'),
            'phone.unique' => __('Số điện thoại này đã được sử dụng trên hệ thống.'),
            'email.email' => __('Địa chỉ email không đúng định dạng.'),
            'email.unique' => __('Địa chỉ email này đã được sử dụng trên hệ thống.'),
        ]);

        $userModel = User::find($user->id);
        $userModel->name = $request->name;
        // Số điện thoại để trống phải lưu là NULL (không phải chuỗi rỗng) vì cột này có ràng buộc UNIQUE
        $userModel->phone = $request->filled('phone') ? $request->phone : null;

        // Ghi nhận email vừa được thành viên bổ sung (chỉ áp dụng cho tài khoản chưa từng có email)
        $emailJustAdded = false;
        if ($canAddEmail && $request->filled('email')) {
            $userModel->email = $request->email;
            $emailJustAdded = true;
        }

        $userModel->save();

        if ($emailJustAdded) {
            ActivityLog::log(__('Bổ sung địa chỉ email cho tài khoản: :email', ['email' => $userModel->email]), $user->id);

            // Gửi thư chào mừng để thành viên xác nhận email vừa bổ sung là chính xác và hoạt động tốt
            try {
                \App\Models\Setting::sendEmailQueue($userModel->email, 'welcome', [
                    'name' => $userModel->name,
                    'email' => $userModel->email,
                    'dashboard_url' => route('dashboard'),
                ]);
            } catch (\Exception $e) {
                \Log::error('Lỗi gửi email chào mừng khi thành viên bổ sung email: ' . $e->getMessage());
            }

            return back()->with('success', __('Cập nhật thông tin cá nhân thành công! Địa chỉ email của bạn đã được lưu và không thể thay đổi về sau.'));
        }

        ActivityLog::log('Cập nhật thông tin cá nhân', $user->id);

        return back()->with('success', 'Cập nhật thông tin cá nhân thành công!');
    }

    /**
     * Xử lý yêu cầu đổi mật khẩu.
     */
    public function changePassword(Request $request)
    {
        $user = Auth::user();

        // Validate mật khẩu mới
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu mới phải từ 8 ký tự trở lên.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.'
        ]);

        // 1. Kiểm tra mật khẩu hiện tại
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác.']);
        }

        // 2. Lưu mật khẩu mới trực tiếp vào thực thể User đang đăng nhập (Auth::user())
        // Việc lưu trực tiếp này giúp đồng bộ mật khẩu mới vào Auth Guard đang được cache trong phiên làm việc hiện tại,
        // tránh lỗi lệch mật khẩu (Password Mismatch) khi thực thi đăng xuất các thiết bị khác ngay sau đó.
        $user->password = Hash::make($request->password);
        $user->setRememberToken(Str::random(60));
        $user->save();

        // Laravel tự động đăng xuất tất cả các session khác bằng cách kiểm tra mật khẩu mới của thực thể đã đồng bộ
        Auth::logoutOtherDevices($request->password);

        ActivityLog::log('Thay đổi mật khẩu tài khoản và đăng xuất các thiết bị khác', $user->id);

        return back()->with('success', 'Đổi mật khẩu thành công!');
    }

    /**
     * Chuẩn bị thông tin khởi tạo Google Authenticator 2FA.
     * Trả về Secret Key tạm thời và QR Code để quét.
     */
    public function setup2FA(Request $request)
    {
        $user = Auth::user();

        // Nếu đã bật 2FA rồi thì không cho phép khởi tạo lại bí mật mới tránh làm mất đồng bộ
        if ($user->google2fa_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản của bạn đã kích hoạt bảo mật 2FA trước đó.'
            ], 400);
        }

        $google2fa = new Google2FA();

        // Lấy secret key tạm thời trong session để đảm bảo nếu user reload modal vẫn dùng chung 1 key
        $secretKey = session('google2fa_secret_temp');
        if (!$secretKey) {
            $secretKey = $google2fa->generateSecretKey();
            session(['google2fa_secret_temp' => $secretKey]);
        }

        // Tạo URI định dạng chuẩn Google Authenticator để ứng dụng di động nhận dạng đúng tên app và email
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            'HoanTienShopee',
            $user->email,
            $secretKey
        );

        // Gọi API sinh ảnh QR Code nhanh gọn, không cần cài thư viện đồ hoạ PHP GD cồng kềnh
        $qrCodeImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrCodeUrl);

        return response()->json([
            'success' => true,
            'secret_key' => $secretKey,
            'qr_code_url' => $qrCodeImageUrl
        ]);
    }

    /**
     * Xác minh mã OTP từ app Google Authenticator và chính thức kích hoạt 2FA.
     */
    public function enable2FA(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|string|size:6'
        ], [
            'otp_code.required' => 'Vui lòng nhập mã OTP xác thực.',
            'otp_code.size' => 'Mã OTP Google Authenticator phải gồm đúng 6 chữ số.'
        ]);

        $user = Auth::user();
        $secretKey = session('google2fa_secret_temp');

        if (!$secretKey) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phiên làm việc thiết lập 2FA đã hết hạn. Vui lòng nhấn nút kích hoạt lại.'
                ], 400);
            }
            return back()->with('error', 'Phiên làm việc thiết lập 2FA đã hết hạn. Vui lòng nhấn nút kích hoạt lại.');
        }

        $google2fa = new Google2FA();
        
        try {
            // Sử dụng khối try-catch để bắt các Exception hệ thống (ví dụ: máy chủ thiếu extension GMP hoặc BCMath cần cho việc tính toán mã TOTP số lớn)
            // Tránh việc ném ra lỗi Server Error 500 chung chung làm gián đoạn trải nghiệm người dùng
            $isValid = $google2fa->verifyKey($secretKey, $request->otp_code);
        } catch (\Exception $e) {
            // Ghi chi tiết lỗi vào log hệ thống cho quản trị viên, KHÔNG trả chi tiết Exception về client
            \Log::error('Lỗi xác thực Google 2FA: ' . $e->getMessage());

            $errorMessage = __('Lỗi hệ thống khi xác thực 2FA. Vui lòng thử lại sau hoặc liên hệ quản trị viên.');
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            return back()->with('error', $errorMessage);
        }

        if (!$isValid) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã OTP Google Authenticator không chính xác. Vui lòng quét lại hoặc kiểm tra giờ trên điện thoại.'
                ], 400);
            }
            return back()->with('error', 'Mã OTP Google Authenticator không chính xác. Vui lòng quét lại hoặc kiểm tra giờ trên điện thoại.');
        }

        // Mã hóa Secret Key trước khi lưu vào DB bằng Crypt để tránh lộ key nếu DB bị tấn công
        $userModel = User::find($user->id);
        $userModel->google2fa_secret = \Illuminate\Support\Facades\Crypt::encryptString($secretKey);
        $userModel->google2fa_enabled = true;
        $userModel->save();

        // Xoá secret key tạm thời trong session sau khi đã bật thành công
        session()->forget('google2fa_secret_temp');

        // Ghi lại nhật ký hệ thống nhằm mục đích kiểm tra bảo mật
        ActivityLog::log('Kích hoạt bảo mật 2FA Google Authenticator', $user->id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kích hoạt bảo mật 2FA thành công!'
            ]);
        }
        return back()->with('success', 'Kích hoạt bảo mật 2FA thành công!');
    }

    /**
     * Huỷ kích hoạt bảo mật Google Authenticator.
     * Yêu cầu nhập đúng mật khẩu tài khoản và mã OTP để ngăn chặn việc huỷ trái phép.
     */
    public function disable2FA(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'otp_code' => 'required|string|size:6'
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu tài khoản để xác nhận.',
            'otp_code.required' => 'Vui lòng nhập mã OTP để xác nhận huỷ.',
            'otp_code.size' => 'Mã OTP Google Authenticator phải gồm đúng 6 chữ số.'
        ]);

        $user = Auth::user();

        // 1. Xác minh mật khẩu tài khoản hiện tại trước để tăng tính phòng vệ
        if (!Hash::check($request->password, $user->password)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mật khẩu tài khoản hiện tại không chính xác.'
                ], 400);
            }
            return back()->with('error', 'Mật khẩu tài khoản hiện tại không chính xác.');
        }

        // 2. Xác minh mã OTP Google Authenticator bằng cách giải mã secret key từ DB
        $google2fa = new Google2FA();
        try {
            $decryptedSecret = \Illuminate\Support\Facades\Crypt::decryptString($user->google2fa_secret);
            // Sử dụng verifyKey trong khối try-catch để ngăn lỗi 500 do môi trường thiếu extension
            $isValid = $google2fa->verifyKey($decryptedSecret, $request->otp_code);
        } catch (\Exception $e) {
            // Ghi chi tiết lỗi vào log hệ thống cho quản trị viên, KHÔNG trả chi tiết Exception về client
            \Log::error('Lỗi huỷ kích hoạt Google 2FA: ' . $e->getMessage());

            $errorMessage = __('Lỗi hệ thống khi huỷ kích hoạt 2FA. Vui lòng thử lại sau hoặc liên hệ quản trị viên.');
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            return back()->with('error', $errorMessage);
        }

        if (!$isValid) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã OTP Google Authenticator không chính xác.'
                ], 400);
            }
            return back()->with('error', 'Mã OTP Google Authenticator không chính xác.');
        }

        // 3. Tắt 2FA và xóa khoá bí mật khỏi database để dọn dẹp bộ nhớ
        $userModel = User::find($user->id);
        $userModel->google2fa_secret = null;
        $userModel->google2fa_enabled = false;
        $userModel->save();

        ActivityLog::log('Huỷ kích hoạt bảo mật 2FA Google Authenticator', $user->id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã huỷ kích hoạt bảo mật 2FA thành công.'
            ]);
        }
        return back()->with('success', 'Đã huỷ kích hoạt bảo mật 2FA thành công.');
    }

    /**
     * Tạo mã OTP và gửi về email của người dùng để chuẩn bị kích hoạt hoặc huỷ kích hoạt.
     */
    public function sendEmailOTP(Request $request)
    {
        $user = Auth::user();

        // Tài khoản đăng ký bằng Số điện thoại chưa có email nên không thể dùng lớp bảo mật OTP qua Email
        if (empty($user->email)) {
            return response()->json([
                'success' => false,
                'message' => __('Tài khoản của bạn chưa có địa chỉ email. Vui lòng bổ sung email trong phần Thông tin cá nhân trước khi bật bảo mật OTP qua Email.')
            ], 422);
        }

        // Chống spam gửi OTP: Giới hạn tối đa 1 lần/phút (chờ 60 giây) để đồng bộ với frontend và bảo vệ tài nguyên hệ thống
        $throttleKey = 'send-email-otp:' . $user->id;
        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'success' => false,
                'message' => "Bạn đã gửi mã OTP gần đây. Vui lòng chờ {$seconds} giây để yêu cầu gửi lại."
            ], 429);
        }
        RateLimiter::hit($throttleKey, 60);

        // Sinh mã OTP 6 chữ số ngẫu nhiên (CSPRNG đảm bảo entropy cao cho mục đích bảo mật)
        $otp = random_int(100000, 999999);

        // Lưu mã OTP đã hash và cài đặt hạn dùng 10 phút để tránh rò rỉ hoặc brute force
        $userModel = User::find($user->id);
        $userModel->otp_code = Hash::make($otp);
        $userModel->otp_expires_at = now()->addMinutes(10);
        $userModel->save();

        // Gửi email thông báo mã OTP chứa thiết kế chuyên nghiệp của website
        try {
            \App\Models\Setting::sendEmail($user->email, 'otp', [
                'name' => $user->name,
                'email' => $user->email,
                'otp' => $otp
            ]);

            ActivityLog::log('Yêu cầu gửi mã OTP xác thực về email', $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Mã OTP đã được gửi thành công đến hòm thư ' . $user->email . '. Vui lòng kiểm tra hộp thư đến hoặc hộp thư rác.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Lỗi gửi OTP Email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi gửi email OTP. Vui lòng kiểm tra cấu hình SMTP của hệ thống.'
            ], 500);
        }
    }

    /**
     * Xác minh mã OTP gửi qua email và chính thức kích hoạt bảo mật Email OTP.
     */
    public function enableEmailOTP(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|string|size:6'
        ], [
            'otp_code.required' => 'Vui lòng nhập mã OTP được gửi về email.',
            'otp_code.size' => 'Mã OTP phải gồm đúng 6 chữ số.'
        ]);

        $user = Auth::user();

        // Kiểm tra xem OTP trong database có trùng khớp (so sánh qua Hash::check) và còn hạn không
        if (!$user->otp_code || !Hash::check($request->otp_code, $user->otp_code) || !$user->otp_expires_at || $user->otp_expires_at->isPast()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã OTP không chính xác hoặc đã hết hiệu lực. Vui lòng bấm nhận lại mã.'
                ], 400);
            }
            return back()->with('error', 'Mã OTP không chính xác hoặc đã hết hiệu lực. Vui lòng bấm nhận lại mã.');
        }

        // Cập nhật trạng thái bật OTP và xoá mã tạm thời để tránh sử dụng lại (Replay attack)
        $userModel = User::find($user->id);
        $userModel->email_otp_enabled = true;
        $userModel->otp_code = null;
        $userModel->otp_expires_at = null;
        $userModel->save();

        ActivityLog::log('Kích hoạt bảo mật xác thực OTP qua Email', $user->id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kích hoạt bảo mật xác thực OTP qua Email thành công!'
            ]);
        }
        return back()->with('success', 'Kích hoạt bảo mật xác thực OTP qua Email thành công!');
    }

    /**
     * Huỷ kích hoạt bảo mật Email OTP.
     * Yêu cầu nhập đúng mật khẩu tài khoản và mã OTP gửi về email để xác nhận.
     */
    public function disableEmailOTP(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'otp_code' => 'required|string|size:6'
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu tài khoản để xác nhận.',
            'otp_code.required' => 'Vui lòng nhập mã OTP để xác nhận huỷ.',
            'otp_code.size' => 'Mã OTP phải gồm đúng 6 chữ số.'
        ]);

        $user = Auth::user();

        // 1. Xác minh mật khẩu tài khoản hiện tại trước để tăng tính phòng vệ
        if (!Hash::check($request->password, $user->password)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mật khẩu tài khoản hiện tại không chính xác.'
                ], 400);
            }
            return back()->with('error', 'Mật khẩu tài khoản hiện tại không chính xác.');
        }

        // 2. Kiểm tra xem OTP trong database có trùng khớp (so sánh qua Hash::check) và còn hạn hay không
        if (!$user->otp_code || !Hash::check($request->otp_code, $user->otp_code) || !$user->otp_expires_at || $user->otp_expires_at->isPast()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã OTP không chính xác hoặc đã hết hiệu lực.'
                ], 400);
            }
            return back()->with('error', 'Mã OTP không chính xác hoặc đã hết hiệu lực.');
        }

        // 3. Huỷ kích hoạt bảo mật Email OTP và dọn dẹp các trường OTP
        $userModel = User::find($user->id);
        $userModel->email_otp_enabled = false;
        $userModel->otp_code = null;
        $userModel->otp_expires_at = null;
        $userModel->save();

        ActivityLog::log('Huỷ kích hoạt bảo mật xác thực OTP qua Email', $user->id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã huỷ kích hoạt bảo mật OTP qua Email thành công.'
            ]);
        }
        return back()->with('success', 'Đã huỷ kích hoạt bảo mật OTP qua Email thành công.');
    }

    /**
     * Hủy liên kết tài khoản thành viên với Bot Telegram hoặc Bot Zalo.
     * 
     * Business Rule:
     * - Chặn thao tác khi hệ thống chạy ở chế độ Demo để bảo vệ tài khoản mẫu.
     * - Xóa thông tin chat ID tương ứng (bot_telegram_chat_id / bot_zalo_chat_id) về null trong CSDL.
     * - Ghi lại nhật ký hoạt động (ActivityLog) để quản trị viên dễ dàng giám sát bảo mật.
     * - Hỗ trợ phản hồi linh hoạt cho cả Form submit thường lẫn yêu cầu AJAX.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function unlinkBot(Request $request)
    {
        // 1. Chặn thao tác ở chế độ Demo
        if (config('app.demo')) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Chức năng này bị vô hiệu hoá trong chế độ Demo.')
                ], 403);
            }
            return back()->with('error', __('Chức năng này bị vô hiệu hoá trong chế độ Demo.'));
        }

        $type = $request->input('type');
        $user = auth()->user();

        // 2. Xử lý gỡ liên kết Zalo Bot
        if ($type === 'zalo') {
            User::where('id', $user->id)->update(['bot_zalo_chat_id' => null]);
            ActivityLog::log('Hủy liên kết Zalo Bot', $user->id);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Đã hủy liên kết Zalo Bot thành công.')
                ]);
            }
            return back()->with('success', __('Đã hủy liên kết Zalo Bot thành công.'));
        }

        // 3. Xử lý gỡ liên kết Telegram Bot
        if ($type === 'telegram') {
            User::where('id', $user->id)->update(['bot_telegram_chat_id' => null]);
            ActivityLog::log('Hủy liên kết Telegram Bot', $user->id);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Đã hủy liên kết Telegram Bot thành công.')
                ]);
            }
            return back()->with('success', __('Đã hủy liên kết Telegram Bot thành công.'));
        }

        // 4. Báo lỗi nếu loại bot không hợp lệ
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => __('Loại Bot không hợp lệ.')
            ], 400);
        }
        return back()->with('error', __('Loại Bot không hợp lệ.'));
    }

    /**
     * Tự xóa tài khoản của thành viên đang đăng nhập.
     * Chỉ hoạt động khi quản trị viên bật tùy chọn 'allow_self_delete_account'.
     * Yêu cầu nhập đúng mật khẩu để xác nhận, chống Race Condition bằng DB Transaction + Lock.
     */
    public function deleteAccount(Request $request)
    {
        // 1. Chặn thao tác khi đang chạy ở chế độ Demo
        if (config('app.demo')) {
            return back()->with('error', __('Chức năng này bị vô hiệu hoá trong chế độ Demo.'));
        }

        // 2. Chỉ cho phép khi quản trị viên đã bật tùy chọn tự xóa tài khoản
        if (Setting::getVal('allow_self_delete_account', '0') !== '1') {
            return back()->with('error', __('Tính năng tự xóa tài khoản hiện đang bị tắt.'));
        }

        $user = Auth::user();

        // 3. Xác thực mật khẩu để xác nhận chủ tài khoản
        $request->validate([
            'password' => 'required|string',
        ], [
            'password.required' => __('Vui lòng nhập mật khẩu để xác nhận xóa tài khoản.'),
        ]);

        if (!Hash::check($request->password, $user->password)) {
            return back()->with('error', __('Mật khẩu không chính xác.'));
        }

        try {
            $userEmail = $user->email;
            $userBalance = (float) $user->balance;

            DB::transaction(function () use ($user) {
                // Khóa bản ghi user để tránh tranh chấp dữ liệu trong quá trình xóa
                $userModel = User::where('id', $user->id)->lockForUpdate()->first();
                if (!$userModel) {
                    throw new \Exception(__('Tài khoản không tồn tại.'));
                }

                $email = $userModel->email;

                // Chủ động xóa các dữ liệu liên quan mà DB không tự động cascade sạch sẽ
                DB::table('activity_logs')->where('user_id', $userModel->id)->delete();
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                DB::table('sessions')->where('user_id', $userModel->id)->delete();

                // Xóa bản ghi người dùng (DB tự động cascade các bảng liên quan)
                $userModel->delete();
            });

            // Ghi nhật ký hệ thống lưu vết số dư của thành viên trước khi xóa (truyền null cho user_id để log không bị dọn dẹp)
            ActivityLog::log(__('Thành viên tự xóa tài khoản trên Web: :email (Số dư còn lại trước khi xóa: :balance)', [
                'email' => $userEmail,
                'balance' => \App\Helpers\CurrencyHelper::format($userBalance),
            ]), null);

            // 5. Đăng xuất khỏi phiên hiện tại sau khi đã xóa tài khoản
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('home')->with('success', __('Tài khoản của bạn đã được xóa vĩnh viễn.'));
        } catch (\Exception $e) {
            logger()->error('Lỗi khi thành viên tự xóa tài khoản: ' . $e->getMessage());
            return back()->with('error', __('Có lỗi xảy ra trong quá trình xóa tài khoản. Vui lòng thử lại sau.'));
        }
    }

    /**
     * Tạo mới mã API Key cá nhân của người dùng.
     */
    public function regenerateApiKey(Request $request)
    {
        if (config('app.demo')) {
            return back()->with('error', __('Chức năng này bị vô hiệu hoá trong chế độ Demo.'));
        }

        $user = Auth::user();
        $userModel = User::find($user->id);
        $newToken = $userModel->regenerateApiToken();

        ActivityLog::log('Đã tạo mới lại Khóa API Key cá nhân', $user->id);

        return back()->with('success', __('Đã tạo mới Khóa API Key cá nhân thành công!'));
    }
}
