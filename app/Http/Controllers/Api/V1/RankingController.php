<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\MoneyHelper;
use App\Models\CashbackHistory;
use App\Models\DailyCheckin;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API Bảng xếp hạng (leaderboard): top đơn hàng, tiền hoàn, điểm danh, giới thiệu, số dư.
 * Che một phần tên hiển thị để bảo vệ quyền riêng tư thành viên.
 */
class RankingController extends ApiController
{
    /**
     * GET /api/v1/openapi/ranking
     */
    public function index(Request $request): JsonResponse
    {
        if (Setting::getVal('ranking_status', '1') !== '1') {
            return $this->fail(__('Tính năng bảng xếp hạng hiện đang tạm khóa.'), 403, 'RANKING_DISABLED');
        }

        $limit = (int) Setting::getVal('ranking_limit', 10);
        if ($limit <= 0) {
            $limit = 10;
        }

        $data = [];

        // A. Top đơn hàng (số đơn hoàn tiền đã duyệt nhiều nhất)
        if (Setting::getVal('ranking_top_orders_status', '1') === '1') {
            $rows = CashbackHistory::select('user_id', DB::raw('COUNT(*) as total_orders'))
                ->where('status', 'approved')
                ->groupBy('user_id')->orderByDesc('total_orders')->limit($limit)->get();
            $users = User::whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');
            $data['top_orders'] = $rows->filter(fn ($r) => $users->has($r->user_id))
                ->map(fn ($r) => [
                    'name' => $this->maskName($users[$r->user_id]->name),
                    'avatar' => $users[$r->user_id]->avatar,
                    'value' => (int) $r->total_orders,
                ])->values();
        }

        // B. Top tiền hoàn (tổng tiền hoàn cao nhất)
        if (Setting::getVal('ranking_top_cashback_status', '1') === '1') {
            $data['top_cashback'] = User::where('role', 'user')->where('status', 'active')
                ->where('total_cashback', '>', 0)
                ->orderByDesc('total_cashback')->limit($limit)->get()
                ->map(fn (User $u) => [
                    'name' => $this->maskName($u->name),
                    'avatar' => $u->avatar,
                    'value' => (int) MoneyHelper::round($u->total_cashback),
                ])->values();
        }

        // C. Top điểm danh (chuỗi streak dài nhất)
        if (Setting::getVal('ranking_top_checkin_status', '1') === '1') {
            $rows = DailyCheckin::select('user_id', DB::raw('MAX(streak_days) as max_streak'))
                ->groupBy('user_id')->orderByDesc('max_streak')->limit($limit)->get();
            $users = User::whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');
            $data['top_checkin'] = $rows->filter(fn ($r) => $users->has($r->user_id))
                ->map(fn ($r) => [
                    'name' => $this->maskName($users[$r->user_id]->name),
                    'avatar' => $users[$r->user_id]->avatar,
                    'value' => (int) $r->max_streak,
                ])->values();
        }

        // D. Top giới thiệu (số F1 đăng ký nhiều nhất)
        if (Setting::getVal('ranking_top_referral_status', '1') === '1') {
            $rows = User::select('referred_by as user_id', DB::raw('COUNT(*) as total_referrals'))
                ->whereNotNull('referred_by')
                ->groupBy('referred_by')->orderByDesc('total_referrals')->limit($limit)->get();
            $users = User::whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');
            $data['top_referral'] = $rows->filter(fn ($r) => $users->has($r->user_id))
                ->map(fn ($r) => [
                    'name' => $this->maskName($users[$r->user_id]->name),
                    'avatar' => $users[$r->user_id]->avatar,
                    'value' => (int) $r->total_referrals,
                ])->values();
        }

        // E. Top số dư khả dụng
        if (Setting::getVal('ranking_top_balance_status', '1') === '1') {
            $data['top_balance'] = User::where('role', 'user')->where('status', 'active')
                ->where('balance', '>', 0)
                ->orderByDesc('balance')->limit($limit)->get()
                ->map(fn (User $u) => [
                    'name' => $this->maskName($u->name),
                    'avatar' => $u->avatar,
                    'value' => (int) MoneyHelper::round($u->balance),
                ])->values();
        }

        return $this->ok($data);
    }

    /**
     * Che một phần tên hiển thị (giữ ký tự đầu và cuối) để bảo vệ quyền riêng tư.
     */
    private function maskName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '***';
        }
        $len = mb_strlen($name);
        if ($len <= 2) {
            return mb_substr($name, 0, 1).'*';
        }

        return mb_substr($name, 0, 1).str_repeat('*', $len - 2).mb_substr($name, -1);
    }
}
