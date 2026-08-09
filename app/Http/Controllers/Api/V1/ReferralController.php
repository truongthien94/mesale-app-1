<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\MoneyHelper;
use App\Models\ReferralCommission;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Tiếp thị liên kết (Referral MLM 2 tầng).
 * Trả về link giới thiệu, thống kê hoa hồng, danh sách F1/F2 và lịch sử hoa hồng.
 */
class ReferralController extends ApiController
{
    /**
     * GET /api/v1/openapi/referrals
     * Query: level (1|2), status (pending|approved), page, per_page (danh sách hoa hồng)
     */
    public function index(Request $request): JsonResponse
    {
        if (Setting::getVal('referral_enabled', '1') !== '1') {
            return $this->fail(__('Tính năng tiếp thị liên kết hiện đang tạm khóa.'), 403, 'REFERRAL_DISABLED');
        }

        $user = $this->apiUser($request);
        $f2Enabled = Setting::getVal('referral_f2_enabled', '1') === '1';

        // Tổng hoa hồng đã duyệt phát sinh từ từng thành viên cấp dưới
        $memberEarnings = ReferralCommission::where('referrer_id', $user->id)
            ->where('status', 'approved')
            ->groupBy('referred_id')
            ->selectRaw('referred_id, SUM(amount) as total')
            ->pluck('total', 'referred_id');

        // Danh sách F1 (giới thiệu trực tiếp)
        $f1Users = User::where('referred_by', $user->id)
            ->orderByDesc('created_at')
            ->get();
        $f1Ids = $f1Users->pluck('id')->toArray();

        // Danh sách F2 (giới thiệu qua F1)
        $f2Users = collect();
        if ($f2Enabled && ! empty($f1Ids)) {
            $f2Users = User::whereIn('referred_by', $f1Ids)
                ->orderByDesc('created_at')
                ->get();
        }

        $mapMember = fn (User $m) => [
            'id' => $m->id,
            'name' => $m->name,
            'email' => $this->maskEmail($m->email),
            'avatar' => $m->avatar,
            'joined_at' => optional($m->created_at)->toIso8601String(),
            'total_commission' => (int) MoneyHelper::round($memberEarnings[$m->id] ?? 0),
        ];

        // Thống kê hoa hồng tổng hợp
        $totalCommission = (int) MoneyHelper::round(ReferralCommission::where('referrer_id', $user->id)->where('status', 'approved')->sum('amount'));
        $pendingCommission = (int) MoneyHelper::round(ReferralCommission::where('referrer_id', $user->id)->where('status', 'pending')->sum('amount'));

        // Lịch sử hoa hồng (phân trang, hỗ trợ lọc level & status)
        $perPage = max(1, min((int) $request->query('per_page', 15), 50));
        $commissions = ReferralCommission::with(['referred', 'cashbackHistory'])
            ->where('referrer_id', $user->id)
            ->when($request->filled('level'), fn ($q) => $q->where('level', $request->query('level')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $commissionItems = $commissions->getCollection()->map(fn (ReferralCommission $c) => [
            'id' => $c->id,
            'amount' => (int) MoneyHelper::round($c->amount),
            'level' => (int) $c->level,
            'status' => $c->status,
            'from_member' => $c->referred ? [
                'id' => $c->referred->id,
                'name' => $c->referred->name,
                'email' => $this->maskEmail($c->referred->email),
            ] : null,
            'order_id' => $c->cashbackHistory->order_id ?? null,
            'product_name' => $c->cashbackHistory->product_name ?? null,
            'created_at' => optional($c->created_at)->toIso8601String(),
        ]);

        return $this->ok([
            'referral_code' => $user->referral_code,
            'referral_link' => route('home', ['ref' => $user->referral_code]),
            'rates' => [
                'f1_rate' => (float) Setting::getVal('referral_f1_rate', 5),
                'f2_rate' => (float) Setting::getVal('referral_f2_rate', 2),
                'f2_enabled' => $f2Enabled,
            ],
            'stats' => [
                'f1_count' => $f1Users->count(),
                'f2_count' => $f2Users->count(),
                'total_commission' => $totalCommission,
                'pending_commission' => $pendingCommission,
                'total_referral_earned' => (int) MoneyHelper::round($user->total_referral_earned),
            ],
            'f1_members' => $f1Users->map($mapMember)->values(),
            'f2_members' => $f2Users->map($mapMember)->values(),
            'commissions' => [
                'items' => $commissionItems,
                'pagination' => [
                    'current_page' => $commissions->currentPage(),
                    'per_page' => $commissions->perPage(),
                    'total' => $commissions->total(),
                    'last_page' => $commissions->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Che một phần email của thành viên cấp dưới để bảo vệ quyền riêng tư.
     */
    private function maskEmail(?string $email): ?string
    {
        if (empty($email) || ! str_contains($email, '@')) {
            return $email;
        }
        [$name, $domain] = explode('@', $email, 2);
        $visible = mb_substr($name, 0, 2);

        return $visible.str_repeat('*', max(1, mb_strlen($name) - 2)).'@'.$domain;
    }
}
