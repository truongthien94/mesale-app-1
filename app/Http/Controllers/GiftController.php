<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\GiftRedemption;
use App\Models\ActivityLog;
use App\Models\BalanceLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GiftController extends Controller
{
    /**
     * Hiển thị danh sách quà tặng và lịch sử đổi quà của thành viên.
     */
    public function index(Request $request)
    {
        // Kiểm tra xem chức năng quy đổi quà tặng có bật không
        if (\App\Models\Setting::getVal('gift_redemption_enabled', '0') !== '1') {
            return redirect()->route('dashboard')->with('error', __('Chức năng quy đổi quà tặng hiện tại đang tạm khóa để bảo trì.'));
        }

        $user = Auth::user();
        
        // Khởi tạo query quà tặng đang hoạt động
        $query = Gift::where('status', true);

        // Lọc theo từ khóa tìm kiếm
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', '%' . $search . '%');
        }

        // Lọc theo Tag
        if ($request->filled('tag') && $request->input('tag') !== 'all') {
            $query->where('tag', $request->input('tag'));
        }

        // Lọc theo phân loại (Type)
        if ($request->filled('type') && $request->input('type') !== 'all') {
            $query->where('type', $request->input('type'));
        }

        // Sắp xếp
        $sort = $request->input('sort', 'newest');
        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } else {
            $query->orderBy('id', 'desc'); // mặc định mới nhất
        }

        // Phân trang danh sách quà tặng (12 phần quà mỗi trang)
        $gifts = $query->paginate(12)->withQueryString();

        // Lấy tất cả tags duy nhất của các quà tặng đang hoạt động phục vụ hiển thị bộ lọc
        $availableTags = Gift::where('status', true)
            ->whereNotNull('tag')
            ->where('tag', '<>', '')
            ->pluck('tag')
            ->unique()
            ->values();

        // Lấy lịch sử đổi quà của user này (Sử dụng param 'redemptions_page' để tránh trùng lặp)
        $redemptions = GiftRedemption::with('gift')
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'redemptions_page')
            ->withQueryString();

        // Nếu là request AJAX, trả về partial view danh sách quà tặng để nâng cao UX
        if ($request->ajax()) {
            return view('dashboard.gifts.partials.gift_list', compact('gifts', 'availableTags'))->render();
        }

        return view('dashboard.gifts.index', compact('gifts', 'redemptions', 'availableTags'));
    }

    /**
     * AJAX endpoint riêng để load / tìm kiếm lịch sử đổi quà của thành viên.
     * Chỉ trả về partial HTML bảng, không reload toàn trang.
     */
    public function redemptions(Request $request)
    {
        // Bắt buộc phải là AJAX request để bảo vệ endpoint này
        if (!$request->ajax()) {
            abort(403);
        }

        $user = Auth::user();

        // Khởi tạo query lịch sử đổi quà của user hiện tại
        $query = GiftRedemption::with('gift')
            ->where('user_id', $user->id);

        // Lọc theo từ khóa tìm kiếm (theo tên quà hoặc mã đơn)
        if ($request->filled('redemption_search')) {
            $search = $request->input('redemption_search');
            $query->where(function ($q) use ($search) {
                // Tìm theo mã đơn
                $q->where('code', 'like', '%' . $search . '%')
                  // Hoặc tìm theo tên quà tặng qua relationship
                  ->orWhereHas('gift', function ($gq) use ($search) {
                      $gq->where('title', 'like', '%' . $search . '%');
                  });
            });
        }

        // Lọc theo trạng thái nếu có
        if ($request->filled('redemption_status') && $request->input('redemption_status') !== 'all') {
            $query->where('status', $request->input('redemption_status'));
        }

        // Sắp xếp mới nhất lên trên
        $redemptions = $query->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Trả về partial HTML bảng lịch sử để AJAX cập nhật container
        return view('dashboard.gifts.partials.redemption_list', compact('redemptions'))->render();
    }

    /**
     * Thực hiện quy đổi quà tặng.
     */
    public function store(Request $request)
    {
        // Kiểm tra xem chức năng quy đổi quà tặng có bật không
        if (\App\Models\Setting::getVal('gift_redemption_enabled', '0') !== '1') {
            return redirect()->back()->with('error', __('Chức năng quy đổi quà tặng hiện đang tạm khóa.'));
        }

        $gift = Gift::findOrFail($request->gift_id);

        $request->validate([
            'gift_id' => 'required|exists:gifts,id',
            'fullname' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'address' => $gift->type === 'physical' ? 'required|string|max:500' : 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000'
        ], [
            'fullname.required' => 'Vui lòng nhập họ và tên người nhận.',
            'phone.required' => 'Vui lòng nhập số điện thoại người nhận.',
            'email.required' => 'Vui lòng nhập email nhận thông tin.',
            'email.email' => 'Định dạng email không hợp lệ.',
            'address.required' => 'Vui lòng nhập địa chỉ giao nhận hàng đối với sản phẩm vật lý.'
        ]);

        $user = Auth::user();
        $giftId = $request->gift_id;

        try {
            DB::transaction(function () use ($user, $giftId, $request) {
                // Khóa dòng quà tặng để chống mua quá số lượng stock
                $gift = Gift::where('id', $giftId)->lockForUpdate()->firstOrFail();

                if (!$gift->status) {
                    throw new \Exception('Món quà này hiện không còn hoạt động trên hệ thống.');
                }

                if ($gift->stock <= 0) {
                    throw new \Exception('Món quà này hiện đã hết hàng trong kho.');
                }

                // Khóa dòng user để chống mua âm tài khoản
                $dbUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

                if ($dbUser->balance < $gift->price) {
                    throw new \Exception('Số dư ví khả dụng của bạn không đủ để quy đổi phần quà này.');
                }

                // Trừ tiền của user
                $oldBalance = $dbUser->balance;
                $newBalance = $oldBalance - $gift->price;
                // balance được gán tường minh (không mass-assign) vì cột này đã bị loại khỏi $fillable vì lý do bảo mật.
                $dbUser->balance = $newBalance;
                $dbUser->save();

                // Trừ stock quà tặng
                $gift->decrement('stock');

                // Gom thông tin vận chuyển/nhận hàng thành mảng
                $shippingInfo = [
                    'fullname' => $request->fullname,
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'address' => $request->address,
                    'notes' => $request->notes,
                ];

                // Sinh mã giao dịch random không trùng lặp
                $code = 'GFT' . strtoupper(\Illuminate\Support\Str::random(8));
                while (GiftRedemption::where('code', $code)->exists()) {
                    $code = 'GFT' . strtoupper(\Illuminate\Support\Str::random(8));
                }

                // Tạo yêu cầu đổi quà
                $redemption = GiftRedemption::create([
                    'code' => $code,
                    'user_id' => $dbUser->id,
                    'gift_id' => $gift->id,
                    'amount' => $gift->price,
                    'shipping_info' => $shippingInfo,
                    'status' => 'pending'
                ]);

                // Ghi nhận biến động số dư ví
                BalanceLog::create([
                    'user_id' => $dbUser->id,
                    'amount_before' => $oldBalance,
                    'amount_change' => -$gift->price,
                    'amount_after' => $newBalance,
                    'type' => 'gift_exchange',
                    'description' => "Đổi quà tặng #{$redemption->code} ({$gift->title})"
                ]);

                // Ghi nhận nhật ký hoạt động
                ActivityLog::log("Đổi quà tặng thành công: {$gift->title}. Số tiền trừ: " . number_format($gift->price) . "đ", $dbUser->id);
            });

            // Gửi email thông báo tạo yêu cầu đổi quà tặng thành công qua hàng đợi
            \App\Models\Setting::sendEmailQueue($request->email, 'gift_created', [
                'name' => $request->fullname,
                'email' => $request->email,
                'gift_title' => $gift->title,
                'gift_price' => number_format($gift->price),
            ]);

            // Gửi thông báo tức thời về Telegram Bot của Admin để duyệt/xử lý đơn hàng nhanh chóng
            \App\Models\Setting::sendTelegramTemplate('telegram_template_gift_created', [
                'name' => $request->fullname,
                'email' => $request->email,
                'gift_title' => $gift->title,
                'gift_price' => number_format($gift->price),
            ]);

            return redirect()->route('gifts.index')->with('success', 'Gửi yêu cầu đổi quà thành công! Vui lòng chờ admin duyệt và gửi dữ liệu.');

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
}
