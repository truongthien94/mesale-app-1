<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftCode;
use App\Models\GiftCodeRedemption;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Controller quản trị hệ thống Giftcode: thêm/sửa/xóa mã, cấu hình điều kiện và theo dõi lượt đổi.
 */
class GiftCodeController extends Controller
{
    /**
     * Trang quản lý Giftcode gồm 2 tab: danh sách mã và lịch sử đổi mã.
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'codes');
        $search = $request->get('search');

        // Cấu hình số dòng hiển thị động (limit), tối đa 500 dòng
        $limit = $request->integer('limit', 15);
        if ($limit < 1 || $limit > 500) {
            $limit = 15;
        }

        // ----- TAB 1: Danh sách mã Giftcode -----
        $codesQuery = GiftCode::withCount('redemptions')->orderBy('id', 'desc');
        if ($tab === 'codes') {
            if (!empty($search)) {
                $codesQuery->where(function ($q) use ($search) {
                    $q->where('code', 'like', '%' . $search . '%')
                      ->orWhere('title', 'like', '%' . $search . '%');
                });
            }
            // Lọc theo trạng thái logic (active/paused/expired/sold_out)
            if ($request->filled('state')) {
                $this->applyStateFilter($codesQuery, $request->state);
            }
        }
        $codes = $codesQuery->paginate($limit, ['*'], 'codes_page')->withQueryString();

        // ----- TAB 2: Lịch sử đổi mã -----
        $redemptionsQuery = GiftCodeRedemption::with(['user', 'giftCode'])->orderBy('id', 'desc');
        if ($tab === 'redemptions') {
            if (!empty($search)) {
                $redemptionsQuery->where(function ($q) use ($search) {
                    $q->where('code', 'like', '%' . $search . '%')
                      ->orWhereHas('user', function ($uq) use ($search) {
                          $uq->where('name', 'like', '%' . $search . '%')
                             ->orWhere('email', 'like', '%' . $search . '%')
                             ->orWhere('phone', 'like', '%' . $search . '%');
                      });
                });
            }
        }
        $redemptions = $redemptionsQuery->paginate($limit, ['*'], 'redemptions_page')->withQueryString();

        // Thống kê tổng quan hiển thị các thẻ KPI
        $stats = [
            'total_codes'       => GiftCode::count(),
            'active_codes'      => GiftCode::where('status', true)
                                            ->where(function ($q) {
                                                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                                            })->count(),
            'total_redemptions' => GiftCodeRedemption::count(),
            'total_distributed' => (float) GiftCodeRedemption::sum('amount'),
        ];

        return view('admin.giftcodes.index', compact('codes', 'redemptions', 'tab', 'stats'));
    }

    /**
     * Áp dụng bộ lọc trạng thái logic cho danh sách mã.
     */
    private function applyStateFilter($query, string $state): void
    {
        switch ($state) {
            case 'active':
                $query->where('status', true)
                      ->where(function ($q) {
                          $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                      })
                      ->where(function ($q) {
                          $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                      })
                      ->where(function ($q) {
                          $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses');
                      });
                break;
            case 'paused':
                $query->where('status', false);
                break;
            case 'expired':
                $query->whereNotNull('expires_at')->where('expires_at', '<', now());
                break;
            case 'sold_out':
                $query->whereNotNull('max_uses')->whereColumn('used_count', '>=', 'max_uses');
                break;
        }
    }

    /**
     * Xác thực và chuẩn hóa dữ liệu đầu vào của mã Giftcode dùng chung cho thêm và sửa.
     *
     * @param GiftCode|null $giftCode Bản ghi đang sửa (null nếu thêm mới) để bỏ qua chính nó khi check unique.
     */
    private function validateData(Request $request, ?GiftCode $giftCode = null): array
    {
        // Chuẩn hóa mã: bỏ khoảng trắng và chuyển in hoa trước khi kiểm tra trùng
        $request->merge([
            'code' => strtoupper(trim(str_replace(' ', '', (string) $request->input('code')))),
        ]);

        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9_\-]+$/',
                Rule::unique('gift_codes', 'code')->ignore($giftCode?->id),
            ],
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'reward_type' => 'required|in:fixed,random',
            'reward_amount' => 'required_if:reward_type,fixed|nullable|numeric|min:0|max:1000000000',
            'reward_min' => 'required_if:reward_type,random|nullable|numeric|min:0|max:1000000000',
            'reward_max' => 'required_if:reward_type,random|nullable|numeric|min:0|max:1000000000|gte:reward_min',
            'max_uses' => 'nullable|integer|min:1|max:100000000',
            'per_user_limit' => 'required|integer|min:1|max:100000',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'require_verified_email' => 'required|boolean',
            'min_total_cashback' => 'nullable|numeric|min:0|max:1000000000',
            'min_account_age_days' => 'nullable|integer|min:0|max:36500',
            'new_user_within_days' => 'nullable|integer|min:1|max:36500',
            'status' => 'required|boolean',
        ], [
            'code.required' => __('Vui lòng nhập mã Giftcode.'),
            'code.regex' => __('Mã chỉ được chứa chữ in hoa, số, dấu gạch ngang hoặc gạch dưới.'),
            'code.unique' => __('Mã Giftcode này đã tồn tại trong hệ thống.'),
            'reward_amount.required_if' => __('Vui lòng nhập số tiền thưởng cố định.'),
            'reward_min.required_if' => __('Vui lòng nhập số tiền tối thiểu.'),
            'reward_max.required_if' => __('Vui lòng nhập số tiền tối đa.'),
            'reward_max.gte' => __('Số tiền tối đa phải lớn hơn hoặc bằng số tiền tối thiểu.'),
            'expires_at.after_or_equal' => __('Thời gian hết hạn phải sau thời gian bắt đầu.'),
        ]);

        // Gán giá trị mặc định an toàn theo loại phần thưởng đã chọn
        return [
            'code' => $validated['code'],
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'reward_type' => $validated['reward_type'],
            'reward_amount' => $validated['reward_type'] === 'fixed' ? ($validated['reward_amount'] ?? 0) : 0,
            'reward_min' => $validated['reward_type'] === 'random' ? ($validated['reward_min'] ?? 0) : 0,
            'reward_max' => $validated['reward_type'] === 'random' ? ($validated['reward_max'] ?? 0) : 0,
            'max_uses' => $validated['max_uses'] ?? null,
            'per_user_limit' => $validated['per_user_limit'],
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'require_verified_email' => (bool) $validated['require_verified_email'],
            'min_total_cashback' => $validated['min_total_cashback'] ?? 0,
            'min_account_age_days' => $validated['min_account_age_days'] ?? 0,
            'new_user_within_days' => $validated['new_user_within_days'] ?? null,
            'status' => (bool) $validated['status'],
        ];
    }

    /**
     * Tạo mới mã Giftcode.
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $giftCode = GiftCode::create($data);

        ActivityLog::log("Tạo Giftcode mới: {$giftCode->code} (Thưởng: {$giftCode->reward_label})", auth()->id());

        return redirect()->route('admin.giftcodes.index', ['tab' => 'codes'])
            ->with('success', __('Thêm Giftcode mới thành công!'));
    }

    /**
     * Cập nhật mã Giftcode.
     */
    public function update(Request $request, GiftCode $giftCode)
    {
        $data = $this->validateData($request, $giftCode);

        $giftCode->update($data);

        ActivityLog::log("Cập nhật Giftcode: {$giftCode->code}", auth()->id());

        return redirect()->route('admin.giftcodes.index', ['tab' => 'codes'])
            ->with('success', __('Cập nhật Giftcode thành công!'));
    }

    /**
     * Xóa mã Giftcode (kèm toàn bộ lịch sử đổi qua ràng buộc cascade).
     */
    public function destroy(GiftCode $giftCode)
    {
        $code = $giftCode->code;
        $giftCode->delete();

        ActivityLog::log("Xóa Giftcode: {$code}", auth()->id());

        return redirect()->route('admin.giftcodes.index', ['tab' => 'codes'])
            ->with('success', __('Xóa Giftcode thành công!'));
    }

    /**
     * Bật/Tắt nhanh trạng thái hoạt động của một mã (AJAX).
     */
    public function toggleStatus(GiftCode $giftCode)
    {
        $giftCode->update(['status' => !$giftCode->status]);

        $statusText = $giftCode->status ? __('Bật') : __('Tạm dừng');
        ActivityLog::log("Đổi trạng thái Giftcode {$giftCode->code}: {$statusText}", auth()->id());

        return response()->json([
            'status' => 'success',
            'active' => $giftCode->status,
            'message' => __('Đã :action mã :code.', ['action' => mb_strtolower($statusText), 'code' => $giftCode->code]),
        ]);
    }

    /**
     * Sinh nhanh một mã ngẫu nhiên chưa trùng (AJAX) phục vụ nút "Tạo mã tự động" ở giao diện.
     */
    public function generate()
    {
        return response()->json([
            'code' => GiftCode::generateUniqueCode(),
        ]);
    }

    /**
     * Xóa hàng loạt các mã được chọn.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|string',
            'confirm_text' => 'required|string',
        ]);

        if (mb_strtoupper(trim($request->confirm_text)) !== 'XÓA HÀNG LOẠT') {
            return response()->json(['status' => 'error', 'message' => __('Từ khóa xác nhận không chính xác.')]);
        }

        $ids = array_filter(array_map('intval', explode(',', $request->ids)));
        if (empty($ids)) {
            return response()->json(['status' => 'error', 'message' => __('Không có mã nào hợp lệ để xóa.')]);
        }

        DB::transaction(function () use ($ids) {
            $codes = GiftCode::whereIn('id', $ids)->lockForUpdate()->get();
            foreach ($codes as $code) {
                $codeText = $code->code;
                $code->delete();
                ActivityLog::log(__("Xóa Giftcode khỏi hệ thống (Hành động hàng loạt): :code", ['code' => $codeText]), auth()->id());
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => __('Đã xóa thành công :count Giftcode được chọn.', ['count' => count($ids)]),
        ]);
    }

    /**
     * Cập nhật cấu hình chung của chức năng Giftcode.
     */
    public function updateConfig(Request $request)
    {
        $request->validate([
            'gift_code_enabled' => 'required|boolean',
            'gift_code_intro' => 'nullable|string|max:1000',
        ]);

        Setting::setVal('gift_code_enabled', $request->gift_code_enabled);
        Setting::setVal('gift_code_intro', $request->input('gift_code_intro', ''));

        // Xóa cache cấu hình để áp dụng ngay
        Cache::forget('setting.gift_code_enabled');
        Cache::forget('setting.gift_code_intro');

        $statusText = $request->gift_code_enabled ? 'BẬT' : 'TẮT';
        ActivityLog::log("Cập nhật cấu hình chức năng Giftcode: {$statusText}", auth()->id());

        return redirect()->route('admin.giftcodes.index')
            ->with('success', __('Đã cập nhật cấu hình Giftcode thành công!'));
    }
}
