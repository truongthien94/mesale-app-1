<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Models\Setting;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class WithdrawalController extends Controller
{

    // Số đơn rút tiền pending tối đa mà một user được phép có cùng lúc
    const MAX_PENDING_WITHDRAWALS = 3;

    // Số lần nhập sai OTP tối đa trước khi mã OTP bị huỷ bỏ (chống brute-force)
    const MAX_OTP_ATTEMPTS = 5;

    /**
     * Hiển thị trang yêu cầu rút tiền và lịch sử rút tiền cho người dùng.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Kiểm tra trạng thái hoạt động của hệ thống rút tiền
        $enabled = Setting::getVal('withdrawal_enabled', '1') === '1';
        if (!$enabled) {
            return redirect()->route('dashboard')->with('error', 'Hệ thống rút tiền tạm thời bảo trì.');
        }

        // Đọc cấu hình rút tiền từ cơ sở dữ liệu: số tiền rút tối thiểu (đồng bộ với key min_withdraw trên trang cấu hình admin)
        // Chuẩn hoá về số nguyên đồng để ngưỡng hiển thị trên giao diện trùng khớp với ngưỡng lúc kiểm tra dữ liệu
        $minWithdraw = \App\Helpers\MoneyHelper::round(Setting::getVal('min_withdraw', 50000));

        // Đọc danh sách các ngân hàng được phép rút tiền từ cấu hình của admin
        $allowedBanksSetting = Setting::getVal('allowed_banks', 'Vietcombank,Techcombank,MB Bank,ACB,BIDV,Vietinbank,Agribank,TPBank,VPBank');
        $allowedBanks = array_map('trim', explode(',', $allowedBanksSetting));

        // Đọc danh sách các ví điện tử được phép rút tiền từ cấu hình của admin để hiển thị cho thành viên lựa chọn
        $allowedWalletsSetting = Setting::getVal('allowed_wallets', 'Momo,ZaloPay,ShopeePay');
        $allowedWallets = array_map('trim', explode(',', $allowedWalletsSetting));

        // Đọc nội dung văn bản quy định rút tiền của hệ thống
        $withdrawalRules = Setting::getVal('withdrawal_rules', "Vui lòng điền đúng thông tin số tài khoản và viết hoa tên chủ tài khoản không dấu. Hệ thống không chịu trách nhiệm nếu chuyển khoản sai thông tin do người dùng cung cấp.\nCác yêu cầu rút tiền được duyệt thủ công bởi Admin trong vòng 1-24h làm việc.\nTài khoản vi phạm, cố tình gian lận điểm danh hoặc tạo đơn Shopee ảo sẽ bị khoá vĩnh viễn và huỷ số dư ví.");

        // Đọc cấu hình phí rút tiền và cách tính phí
        $feeType = Setting::getVal('withdrawal_fee_type', 'percentage');
        $feeValue = (float)Setting::getVal('withdrawal_fee_value', 0);

        // Khởi tạo query lịch sử rút tiền của người dùng hiện tại
        $query = Withdrawal::where('user_id', $user->id);

        // Lọc theo từ khóa tìm kiếm (Tên tài khoản, Số tài khoản, Tên ngân hàng/ví)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('account_name', 'like', '%' . $search . '%')
                  ->orWhere('account_number', 'like', '%' . $search . '%')
                  ->orWhere('bank_name', 'like', '%' . $search . '%');
            });
        }

        // Lọc theo trạng thái rút tiền (pending, approved, rejected)
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Thực hiện phân trang danh sách kết quả rút tiền (10 dòng mỗi trang)
        $withdrawals = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Trả về giao diện danh sách lịch sử rút tiền một phần (partial HTML) khi có yêu cầu AJAX để tối ưu tốc độ tải trang
        if ($request->ajax()) {
            return view('dashboard.partials.withdrawal_list', compact('withdrawals'))->render();
        }

        // Đọc cấu hình sổ tài khoản nhận tiền và nạp danh sách tài khoản đã lưu của thành viên (nếu tính năng đang bật).
        // Đặt sau nhánh AJAX để tránh truy vấn thừa mỗi lần lọc/phân trang lịch sử rút tiền.
        $savedAccountsEnabled = Setting::getVal('withdraw_saved_accounts_enabled', '1') === '1';
        $savedAccounts = $savedAccountsEnabled
            ? $user->paymentAccounts()->orderByDesc('is_default')->orderByDesc('created_at')->get()
            : collect();

        return view('dashboard.withdraw', compact('user', 'withdrawals', 'minWithdraw', 'allowedBanks', 'allowedWallets', 'withdrawalRules', 'feeType', 'feeValue', 'savedAccountsEnabled', 'savedAccounts'));
    }

    /**
     * Trả về response lỗi thống nhất (hỗ trợ cả JSON lẫn redirect truyền thống).
     */
    private function errorResponse(Request $request, string $message, int $httpCode = 400)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], $httpCode);
        }
        return back()->with('error', $message)->withInput();
    }

    /**
     * Tiếp nhận và xử lý yêu cầu rút tiền mới từ người dùng.
     * [SECURITY] Đã vá 6 lỗ hổng bảo mật:
     *   1. Giới hạn max amount = balance hiện tại
     *   2. Rate Limiting chống spam tạo đơn rút
     *   3. Kiểm tra số đơn pending đang chờ
     *   4. Chống brute-force OTP (đếm số lần nhập sai)
     *   5. Hash OTP thay vì lưu plaintext
     *   6. Ẩn chi tiết Exception khỏi client
     */
    public function store(Request $request)
    {
        // Kiểm tra Cloudflare Turnstile Captcha nếu được bật
        if (\App\Models\Setting::getVal('turnstile_status', '0') === '1' && \App\Models\Setting::getVal('turnstile_on_withdraw', '0') === '1') {
            $token = $request->input('cf_turnstile_response');
            if (!\App\Services\TurnstileService::verify($token, $request->ip())) {
                return $this->errorResponse($request, 'Xác thực Captcha không hợp lệ. Vui lòng thử lại.');
            }
        }

        $user = Auth::user();

        // Kiểm tra xem hệ thống rút tiền có đang được mở không
        $enabled = Setting::getVal('withdrawal_enabled', '1') === '1';
        if (!$enabled) {
            return $this->errorResponse($request, 'Hệ thống rút tiền đang tạm bảo trì.');
        }

        // [FIX #2] Chống spam: Giới hạn tối đa 5 yêu cầu rút tiền mỗi ngày cho mỗi user
        $withdrawThrottleKey = 'withdraw-store:' . $user->id;
        if (RateLimiter::tooManyAttempts($withdrawThrottleKey, 5)) {
            $seconds = RateLimiter::availableIn($withdrawThrottleKey);
            return $this->errorResponse($request, "Bạn đã gửi quá nhiều yêu cầu rút tiền. Vui lòng thử lại sau " . ceil($seconds / 60) . " phút.", 429);
        }
        RateLimiter::hit($withdrawThrottleKey, 300); // Reset sau 5 phút

        // [FIX #3] Kiểm tra số đơn rút tiền pending chưa xử lý, ngăn user tạo quá nhiều đơn gây nhiễu
        $pendingCount = Withdrawal::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
        if ($pendingCount >= self::MAX_PENDING_WITHDRAWALS) {
            return $this->errorResponse($request, 'Bạn đang có ' . $pendingCount . ' yêu cầu rút tiền đang chờ xử lý. Vui lòng chờ admin duyệt trước khi tạo yêu cầu mới.');
        }

        // Lấy số tiền rút tối thiểu được cấu hình từ bảng cài đặt
        // Chuẩn hoá về số nguyên đồng vì số tiền rút sau đó cũng bị cắt phần lẻ, nếu ngưỡng tối thiểu còn
        // phần thập phân thì một yêu cầu vừa đủ điều kiện lúc validate lại rơi xuống dưới ngưỡng sau khi cắt
        $minWithdraw = \App\Helpers\MoneyHelper::round(Setting::getVal('min_withdraw', 50000));

        // [FIX #1] Lấy balance hiện tại để dùng làm max amount validation
        $currentBalance = (float)User::where('id', $user->id)->value('balance');

        // Lấy mảng danh sách ngân hàng hợp lệ để thực hiện validation ở backend
        $allowedBanksSetting = Setting::getVal('allowed_banks', 'Vietcombank,Techcombank,MB Bank,ACB,BIDV,Vietinbank,Agribank,TPBank,VPBank');
        $allowedBanks = array_map('trim', explode(',', $allowedBanksSetting));

        // Lấy mảng danh sách ví điện tử hợp lệ để thực hiện validation ở backend
        $allowedWalletsSetting = Setting::getVal('allowed_wallets', 'Momo,ZaloPay,ShopeePay');
        $allowedWallets = array_map('trim', explode(',', $allowedWalletsSetting));

        // Kiểm tra xem hình thức rút tiền qua Ngân hàng / Ví điện tử có bị tắt không
        $bankEnabled = Setting::getVal('withdraw_bank_enabled', '1') === '1';
        $walletEnabled = Setting::getVal('withdraw_wallet_enabled', '1') === '1';

        if ($request->input('payment_method') === 'bank' && !$bankEnabled) {
            return $this->errorResponse($request, 'Phương thức rút tiền qua Ngân hàng hiện tại đang bị tắt.');
        }

        if (in_array($request->input('payment_method'), ['wallet', 'momo']) && !$walletEnabled) {
            return $this->errorResponse($request, 'Phương thức rút tiền qua Ví điện tử hiện tại đang bị tắt.');
        }

        // Validate dữ liệu yêu cầu rút tiền của khách hàng
        // [FIX #1] Thêm rule max = balance hiện tại, ngăn user rút số tiền lớn hơn số dư
        $request->validate([
            'amount' => "required|numeric|min:{$minWithdraw}|max:{$currentBalance}",
            'payment_method' => 'required|in:bank,wallet,momo',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:100',
            // Ràng buộc bảo mật: Ngăn chặn người dùng sửa đổi HTML để gửi ngân hàng ngoài danh sách được hỗ trợ
            'bank_name' => [
                'required_if:payment_method,bank',
                'nullable',
                'string',
                'max:100',
                function ($attribute, $value, $fail) use ($allowedBanks, $request) {
                    if ($request->payment_method === 'bank' && !in_array($value, $allowedBanks)) {
                        $fail('Ngân hàng đã chọn không nằm trong danh sách hỗ trợ của hệ thống.');
                    }
                }
            ],
            // Ràng buộc bảo mật: Ngăn chặn người dùng gửi ví điện tử ngoài danh sách được hỗ trợ
            'wallet_name' => [
                'required_if:payment_method,wallet',
                'nullable',
                'string',
                'max:100',
                function ($attribute, $value, $fail) use ($allowedWallets, $request) {
                    if ($request->payment_method === 'wallet' && !in_array($value, $allowedWallets)) {
                        $fail('Ví điện tử đã chọn không nằm trong danh sách hỗ trợ của hệ thống.');
                    }
                }
            ],
        ], [
            'amount.required' => 'Vui lòng nhập số tiền cần rút.',
            'amount.numeric' => 'Số tiền rút phải là một con số.',
            'amount.min' => "Số tiền rút tối thiểu là " . \App\Helpers\CurrencyHelper::format($minWithdraw) . ".",
            'amount.max' => "Số tiền rút không được vượt quá số dư ví hiện tại (" . \App\Helpers\CurrencyHelper::format($currentBalance) . ").",
            'payment_method.required' => 'Vui lòng chọn phương thức nhận tiền.',
            'account_number.required' => 'Vui lòng nhập số tài khoản hoặc số điện thoại ví.',
            'account_name.required' => 'Vui lòng nhập tên chủ tài khoản viết hoa không dấu.',
            'bank_name.required_if' => 'Vui lòng chọn ngân hàng nhận tiền hợp lệ.',
            'wallet_name.required_if' => 'Vui lòng chọn ví điện tử nhận tiền hợp lệ.',
        ]);

        // Chuẩn hóa số tài khoản để kiểm tra trùng lặp bên trong transaction (tránh race condition TOCTOU)
        $uniqueAccount = Setting::getVal('withdraw_unique_account', '0') === '1';
        $cleanAccountNumber = $uniqueAccount ? preg_replace('/\s+/', '', $request->account_number) : null;

        // Chuẩn hoá số tiền rút về số nguyên đồng, làm tròn xuống để không bao giờ vượt quá số dư đã kiểm tra ở bước validate
        $amount = \App\Helpers\MoneyHelper::truncate($request->amount);

        // Đọc cấu hình phí rút tiền hiện thời của hệ thống
        $feeType = Setting::getVal('withdrawal_fee_type', 'percentage');
        $feeValue = (float)Setting::getVal('withdrawal_fee_value', 0);

        // Tính toán phí dựa trên loại phí cố định (fixed) hoặc phần trăm (percentage)
        // Chuẩn hoá về số nguyên đồng để khớp chính xác với số phí xem trước bằng Math.round() ở giao diện
        $fee = 0;
        if ($feeType === 'percentage') {
            $fee = \App\Helpers\MoneyHelper::round(($amount * $feeValue) / 100);
        } else {
            $fee = \App\Helpers\MoneyHelper::round($feeValue);
        }

        $realAmount = $amount - $fee;

        // Ràng buộc nghiệp vụ: Chặn việc rút tiền nếu số tiền rút nhỏ hơn hoặc bằng phí rút (thực nhận <= 0)
        if ($realAmount <= 0) {
            return $this->errorResponse($request, 'Số tiền rút phải lớn hơn phí rút tiền của hệ thống (' . number_format($fee) . 'đ).');
        }

        // Kiểm tra xác minh OTP rút tiền nếu hệ thống yêu cầu
        $otpRequired = Setting::getVal('withdraw_otp_required', '0') === '1';
        if ($otpRequired) {
            $request->validate([
                'otp_code' => 'required|string|size:6',
            ], [
                'otp_code.required' => 'Vui lòng nhập mã OTP để xác nhận giao dịch rút tiền.',
                'otp_code.size' => 'Mã OTP phải gồm đúng 6 chữ số.',
            ]);

            // [FIX #4] Chống brute-force OTP: Đếm số lần nhập sai, vượt ngưỡng thì huỷ mã OTP
            $otpAttemptKey = 'otp-verify-attempt:' . $user->id;
            if (RateLimiter::tooManyAttempts($otpAttemptKey, self::MAX_OTP_ATTEMPTS)) {
                // Huỷ mã OTP khi vượt quá số lần thử cho phép
                User::where('id', $user->id)->update([
                    'otp_code' => null,
                    'otp_expires_at' => null,
                ]);
                RateLimiter::clear($otpAttemptKey);
                return $this->errorResponse($request, 'Bạn đã nhập sai mã OTP quá ' . self::MAX_OTP_ATTEMPTS . ' lần. Mã OTP đã bị huỷ, vui lòng yêu cầu gửi lại mã mới.');
            }

            // Lấy thông tin user hiện tại để xác minh mã OTP
            $currentUser = User::find($user->id);

            // [FIX #5] So sánh OTP bằng Hash::check() thay vì so sánh plaintext
            $otpValid = $currentUser->otp_code
                && Hash::check($request->otp_code, $currentUser->otp_code)
                && $currentUser->otp_expires_at
                && !$currentUser->otp_expires_at->isPast();

            if (!$otpValid) {
                // Ghi nhận số lần nhập sai OTP (reset sau 10 phút đồng bộ với thời hạn OTP)
                RateLimiter::hit($otpAttemptKey, 600);
                $remainingAttempts = self::MAX_OTP_ATTEMPTS - RateLimiter::attempts($otpAttemptKey);
                return $this->errorResponse($request, 'Mã OTP không chính xác hoặc đã hết hiệu lực. Bạn còn ' . $remainingAttempts . ' lần thử.');
            }

            // Lưu ý: việc HỦY (tiêu thụ) mã OTP được dời vào bên trong transaction bên dưới — chỉ huỷ khi
            // mọi kiểm tra (số dư/đơn chờ/trùng STK) đã vượt qua. Nhờ vậy nếu giao dịch thất bại thì mã OTP
            // không bị huỷ oan và người dùng vẫn dùng lại được mã cũ (chống replay vẫn đảm bảo vì mã bị huỷ
            // ngay khi đơn rút được tạo thành công trong cùng một transaction).
        }

        // Sử dụng Database Transaction để thực hiện trừ tiền an toàn tuyệt đối
        try {
            return DB::transaction(function () use ($user, $amount, $fee, $realAmount, $request, $uniqueAccount, $cleanAccountNumber, $otpRequired) {
                // Khoá dòng dữ liệu User để kiểm tra số dư mới nhất (pessimistic locking) nhằm tránh race condition khi gửi request song song
                $userModel = User::where('id', $user->id)->lockForUpdate()->first();

                if ($userModel->balance < $amount) {
                    throw new \Exception('INSUFFICIENT_BALANCE');
                }

                // Kiểm tra lại số lượng yêu cầu rút tiền đang chờ duyệt (pending) trong Transaction sau khi đã khóa dòng User,
                // nhằm triệt tiêu hoàn toàn khả năng người dùng lách luật gửi nhiều request song song để tạo nhiều lệnh rút tiền vượt giới hạn cho phép.
                $pendingCount = Withdrawal::where('user_id', $userModel->id)
                    ->where('status', 'pending')
                    ->count();
                if ($pendingCount >= self::MAX_PENDING_WITHDRAWALS) {
                    throw new \Exception('MAX_PENDING_EXCEEDED');
                }

                // [FIX TOCTOU] Kiểm tra trùng số tài khoản bên trong transaction để ngăn race condition:
                // hai user gửi đồng thời cùng STK sẽ được serialize tại đây thay vì bỏ qua nhau ngoài transaction.
                if ($uniqueAccount && $cleanAccountNumber) {
                    $duplicateNumber = Withdrawal::where('user_id', '!=', $userModel->id)
                        ->where('status', '!=', 'rejected')
                        ->where(function($q) use ($cleanAccountNumber) {
                            $q->whereRaw("REPLACE(account_number, ' ', '') = ?", [$cleanAccountNumber]);
                        })
                        ->exists();
                    if ($duplicateNumber) {
                        throw new \Exception('DUPLICATE_ACCOUNT');
                    }
                }

                // Tiêu thụ mã OTP NGAY TRONG transaction sau khi mọi kiểm tra đã vượt qua:
                // nếu tx fail ở các bước trên thì OTP không bị huỷ; nếu tới đây thì đơn rút chắc chắn được tạo,
                // huỷ OTP để chống tấn công phát lại (Replay attack).
                if ($otpRequired) {
                    $userModel->otp_code = null;
                    $userModel->otp_expires_at = null;
                    RateLimiter::clear('otp-verify-attempt:' . $userModel->id);
                }

                $oldBalance = $userModel->balance;

                // Trừ tiền khỏi số dư khả dụng của User ngay lập tức để tránh double-spending
                $userModel->balance -= $amount;
                $userModel->save();

                // Ghi nhận biến động số dư (Giao dịch trừ tiền rút)
                \App\Models\BalanceLog::write(
                    $userModel,
                    $oldBalance,
                    -$amount,
                    $userModel->balance,
                    'withdraw_request',
                    "Yêu cầu rút tiền về " . ($request->payment_method === 'bank' ? $request->bank_name : ($request->payment_method === 'wallet' ? 'Ví ' . $request->wallet_name : 'Ví MoMo'))
                );

                // Sinh mã đơn rút tiền ngẫu nhiên theo cấu hình tùy chỉnh của Admin
                $withdrawalCode = Setting::generateWithdrawCode();

                // Tạo bản ghi rút tiền chờ duyệt kèm theo phí rút và thực nhận cố định lúc khởi tạo
                $withdrawal = Withdrawal::create([
                    'user_id' => $user->id,
                    'code' => $withdrawalCode,
                    'amount' => $amount,
                    'fee' => $fee,
                    'real_amount' => $realAmount,
                    'payment_method' => $request->payment_method,
                    'account_number' => $request->account_number,
                    'account_name' => strtoupper($request->account_name),
                    'bank_name' => $request->payment_method === 'bank' ? $request->bank_name : ($request->payment_method === 'wallet' ? $request->wallet_name : 'Ví MoMo'),
                    'status' => 'pending'
                ]);

                // Ghi nhận lịch sử hoạt động
                ActivityLog::log("Tạo yêu cầu rút " . number_format($amount) . "đ về " . ($request->payment_method === 'bank' ? $request->bank_name : ($request->payment_method === 'wallet' ? $request->wallet_name : 'Momo')), $user->id);

                // Gửi thông báo nội bộ
                \App\Models\Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Yêu cầu rút tiền đang chờ duyệt',
                    'content' => "Yêu cầu rút " . number_format($amount) . "đ của bạn đã được gửi lên hệ thống và đang chờ admin xử lý."
                ]);

                // Đưa email thông báo tạo yêu cầu rút tiền vào hàng đợi (Queue) để gửi bất đồng bộ nhằm tối ưu hiệu năng phản hồi giao diện
                try {
                    \App\Models\Setting::sendEmailQueue($userModel->email, 'withdrawal_created', [
                        'code' => $withdrawalCode,
                        'name' => $userModel->name,
                        'email' => $userModel->email,
                        'amount' => number_format($amount),
                        'payment_method' => $request->payment_method === 'bank' ? 'Chuyển khoản ngân hàng' : 'Ví điện tử',
                        'account_name' => strtoupper($request->account_name),
                        'account_number' => $request->account_number,
                        'bank_name' => $request->payment_method === 'bank' ? $request->bank_name : ($request->payment_method === 'wallet' ? $request->wallet_name : 'Ví MoMo')
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Lỗi thêm email tạo rút tiền vào hàng đợi: ' . $e->getMessage());
                }

                // Gửi thông báo Telegram khi tạo yêu cầu rút tiền mới
                try {
                    \App\Models\Setting::sendTelegramTemplate('telegram_template_withdrawal_created', [
                        'code' => $withdrawalCode,
                        'name' => $userModel->name,
                        'email' => $userModel->email,
                        'amount' => number_format($amount),
                        'payment_method' => $request->payment_method === 'bank' ? 'Chuyển khoản ngân hàng' : 'Ví điện tử',
                        'bank_name' => $request->payment_method === 'bank' ? $request->bank_name : ($request->payment_method === 'wallet' ? $request->wallet_name : 'Ví MoMo'),
                        'account_number' => $request->account_number,
                        'account_name' => strtoupper($request->account_name),
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Lỗi gửi Telegram tạo rút tiền: ' . $e->getMessage());
                }

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Tạo yêu cầu rút tiền thành công! Vui lòng chờ admin xét duyệt.'
                    ]);
                }

                return redirect()->route('withdraw')->with('success', 'Tạo yêu cầu rút tiền thành công! Vui lòng chờ admin xét duyệt.');
            });
        } catch (\Exception $e) {
            \Log::error("Withdrawal transaction failed: " . $e->getMessage());

            // [FIX #6] Ẩn chi tiết Exception khỏi client, chỉ trả message thân thiện
            $userMessage = 'Có lỗi hệ thống xảy ra khi xử lý giao dịch. Vui lòng thử lại sau.';

            // Chỉ hiển thị message cụ thể cho các lỗi nghiệp vụ đã biết trước
            if ($e->getMessage() === 'INSUFFICIENT_BALANCE') {
                $userMessage = 'Số dư tài khoản của bạn không đủ để thực hiện yêu cầu này.';
            } elseif ($e->getMessage() === 'MAX_PENDING_EXCEEDED') {
                $userMessage = 'Bạn đã đạt giới hạn số lượng yêu cầu rút tiền đang chờ xử lý. Vui lòng chờ admin duyệt trước khi tạo yêu cầu mới.';
            } elseif ($e->getMessage() === 'DUPLICATE_ACCOUNT') {
                $userMessage = 'Số tài khoản này đã được sử dụng bởi một tài khoản khác trong hệ thống.';
            }

            return $this->errorResponse($request, $userMessage);
        }
    }

    /**
     * Tạo mã OTP rút tiền và gửi về email của thành viên.
     * [SECURITY] OTP được hash bằng bcrypt trước khi lưu vào database.
     */
    public function sendOtp(Request $request)
    {
        $user = Auth::user();

        // Kiểm tra cấu hình OTP rút tiền có đang được kích hoạt hay không
        $otpRequired = Setting::getVal('withdraw_otp_required', '0') === '1';
        if (!$otpRequired) {
            return response()->json([
                'success' => false,
                'message' => 'Hệ thống rút tiền hiện tại không yêu cầu xác minh mã OTP.'
            ], 400);
        }

        // Chống spam: Giới hạn tối đa 3 lần yêu cầu gửi OTP mỗi phút
        $throttleKey = 'send-withdraw-otp:' . $user->id;
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'success' => false,
                'message' => "Bạn đã yêu cầu gửi mã quá nhanh. Vui lòng thử lại sau {$seconds} giây."
            ], 429);
        }
        RateLimiter::hit($throttleKey, 60);

        // Sinh mã OTP 6 số ngẫu nhiên có độ tin cậy bảo mật cao
        $otp = (string)random_int(100000, 999999);

        // [FIX #5] Hash mã OTP trước khi lưu vào database để bảo vệ khi database bị rò rỉ
        $userModel = User::find($user->id);
        $userModel->otp_code = Hash::make($otp);
        $userModel->otp_expires_at = now()->addMinutes(10);
        $userModel->save();

        // Xoá bộ đếm lần nhập sai OTP cũ khi tạo mã mới (reset lại cơ hội nhập)
        RateLimiter::clear('otp-verify-attempt:' . $user->id);

        // Tiến hành gửi email thông báo mã OTP qua mailer hệ thống (gửi mã plaintext cho user)
        try {
            Setting::sendEmail($user->email, 'otp', [
                'name' => $user->name,
                'email' => $user->email,
                'otp' => $otp
            ]);

            ActivityLog::log('Yêu cầu mã OTP xác nhận tạo lệnh rút tiền', $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Mã OTP rút tiền đã được gửi thành công đến email ' . $user->email . '.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Lỗi gửi OTP rút tiền qua Email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi gửi email OTP. Vui lòng kiểm tra lại cấu hình SMTP của hệ thống.'
            ], 500);
        }
    }
}
