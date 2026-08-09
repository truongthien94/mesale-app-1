<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\MoneyHelper;
use App\Models\ActivityLog;
use App\Models\BalanceLog;
use App\Models\Gift;
use App\Models\GiftRedemption;
use App\Models\Setting;
use App\Models\User;
use App\Services\FinancialIdempotencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * API Đổi quà tặng: danh sách quà, lịch sử đổi quà và thực hiện quy đổi.
 */
class GiftController extends ApiController
{
    private function ensureFeatureEnabled(): ?JsonResponse
    {
        if (Setting::getVal('gift_redemption_enabled', '0') !== '1') {
            return $this->fail(__('Chức năng quy đổi quà tặng hiện đang tạm khóa để bảo trì.'), 403, 'GIFT_DISABLED');
        }

        return null;
    }

    /**
     * GET /api/v1/openapi/gifts
     * Query: search, tag, type, sort (newest|price_asc|price_desc), page, per_page
     */
    public function index(Request $request): JsonResponse
    {
        if ($resp = $this->ensureFeatureEnabled()) {
            return $resp;
        }

        $query = Gift::where('status', true);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where('title', 'like', "%{$search}%");
        }
        if (($tag = $request->query('tag')) && $tag !== 'all') {
            $query->where('tag', $tag);
        }
        if (($type = $request->query('type')) && $type !== 'all') {
            $query->where('type', $type);
        }

        $sort = $request->query('sort', 'newest');
        if ($sort === 'price_asc') {
            $query->orderBy('price');
        } elseif ($sort === 'price_desc') {
            $query->orderByDesc('price');
        } else {
            $query->orderByDesc('id');
        }

        $perPage = max(1, min((int) $request->query('per_page', 12), 50));
        $gifts = $query->paginate($perPage);

        $availableTags = Gift::where('status', true)
            ->whereNotNull('tag')->where('tag', '<>', '')
            ->pluck('tag')->unique()->values();

        return $this->ok([
            'items' => $gifts->getCollection()->map(fn (Gift $g) => [
                'id' => $g->id,
                'title' => $g->title,
                'image' => $g->image,
                'description' => $g->description,
                'price' => (int) MoneyHelper::round($g->price),
                'stock' => (int) $g->stock,
                'type' => $g->type,
                'tag' => $g->tag,
            ]),
            'available_tags' => $availableTags,
            'pagination' => [
                'current_page' => $gifts->currentPage(),
                'per_page' => $gifts->perPage(),
                'total' => $gifts->total(),
                'last_page' => $gifts->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/openapi/gifts/redemptions
     * Query: search, status, page, per_page — lịch sử đổi quà của thành viên.
     */
    public function redemptions(Request $request): JsonResponse
    {
        if ($resp = $this->ensureFeatureEnabled()) {
            return $resp;
        }

        $user = $this->apiUser($request);
        $query = GiftRedemption::with('gift')->where('user_id', $user->id);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('gift', fn ($gq) => $gq->where('title', 'like', "%{$search}%"));
            });
        }
        if (($status = $request->query('status')) && $status !== 'all') {
            $query->where('status', $status);
        }

        $perPage = max(1, min((int) $request->query('per_page', 10), 50));
        $redemptions = $query->orderByDesc('id')->paginate($perPage);

        return $this->ok([
            'items' => $redemptions->getCollection()->map(fn (GiftRedemption $r) => [
                'id' => $r->id,
                'code' => $r->code,
                'gift_title' => $r->gift->title ?? null,
                'gift_image' => $r->gift->image ?? null,
                'amount' => (int) MoneyHelper::round($r->amount),
                'status' => $r->status,
                'created_at' => optional($r->created_at)->toIso8601String(),
                'processed_at' => optional($r->processed_at)->toIso8601String(),
            ]),
            'pagination' => [
                'current_page' => $redemptions->currentPage(),
                'per_page' => $redemptions->perPage(),
                'total' => $redemptions->total(),
                'last_page' => $redemptions->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/openapi/gifts/redeem
     * Body: gift_id, fullname, phone, email, address (bắt buộc với quà vật lý), notes
     */
    public function redeem(Request $request, FinancialIdempotencyService $idempotency): JsonResponse
    {
        if ($resp = $this->ensureFeatureEnabled()) {
            return $resp;
        }

        $user = $this->apiUser($request);

        $gift = Gift::find($request->input('gift_id'));
        if (! $gift) {
            return $this->fail(__('Món quà không tồn tại.'), 404, 'GIFT_NOT_FOUND');
        }

        try {
            $validated = $request->validate([
                'gift_id' => 'required|exists:gifts,id',
                'fullname' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'required|email|max:255',
                'address' => $gift->type === 'physical' ? 'required|string|max:500' : 'nullable|string|max:500',
                'notes' => 'nullable|string|max:1000',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        $redemptionInput = [
            'gift_id' => (int) $validated['gift_id'],
            'fullname' => trim($validated['fullname']),
            'phone' => trim($validated['phone']),
            'email' => strtolower(trim($validated['email'])),
            'address' => isset($validated['address']) && trim($validated['address']) !== ''
                ? trim($validated['address'])
                : null,
            'notes' => isset($validated['notes']) && trim($validated['notes']) !== ''
                ? trim($validated['notes'])
                : null,
        ];

        try {
            $result = $idempotency->execute(
                (int) $user->id,
                'gift.redeem',
                (string) $request->attributes->get('idempotency_key'),
                $redemptionInput,
                function () use ($user, $redemptionInput): array {
                    // Khóa dòng quà tặng chống vượt kho
                    $gift = Gift::where('id', $redemptionInput['gift_id'])->lockForUpdate()->firstOrFail();

                    if (! $gift->status) {
                        throw new \RuntimeException('GIFT_INACTIVE');
                    }
                    if ($gift->stock <= 0) {
                        throw new \RuntimeException('GIFT_OUT_OF_STOCK');
                    }

                    // Khóa dòng user chống trừ âm ví
                    $dbUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
                    if ($dbUser->balance < $gift->price) {
                        throw new \RuntimeException('INSUFFICIENT_BALANCE');
                    }

                    $oldBalance = $dbUser->balance;
                    $newBalance = $oldBalance - $gift->price;
                    // balance được gán tường minh (không mass-assign) vì cột này đã bị loại khỏi $fillable vì lý do bảo mật.
                    $dbUser->balance = $newBalance;
                    $dbUser->save();
                    $gift->decrement('stock');

                    $code = 'GFT'.strtoupper(Str::random(8));
                    while (GiftRedemption::where('code', $code)->exists()) {
                        $code = 'GFT'.strtoupper(Str::random(8));
                    }

                    $redemption = GiftRedemption::create([
                        'code' => $code,
                        'user_id' => $dbUser->id,
                        'gift_id' => $gift->id,
                        'amount' => $gift->price,
                        'shipping_info' => [
                            'fullname' => $redemptionInput['fullname'],
                            'phone' => $redemptionInput['phone'],
                            'email' => $redemptionInput['email'],
                            'address' => $redemptionInput['address'],
                            'notes' => $redemptionInput['notes'],
                        ],
                        'status' => 'pending',
                    ]);

                    BalanceLog::create([
                        'user_id' => $dbUser->id,
                        'amount_before' => $oldBalance,
                        'amount_change' => -$gift->price,
                        'amount_after' => $newBalance,
                        'type' => 'gift_exchange',
                        'description' => __('Đổi quà tặng :code (:title)', ['code' => '#'.$redemption->code, 'title' => $gift->title]),
                    ]);

                    ActivityLog::log(__('Đổi quà tặng :title (qua Open API), trừ :amount', [
                        'title' => $gift->title,
                        'amount' => number_format($gift->price, 0, ',', '.').'đ',
                    ]), $dbUser->id);

                    return [
                        'status' => 200,
                        'data' => [
                            'code' => $redemption->code,
                            'amount' => (int) MoneyHelper::round($redemption->amount),
                            'status' => $redemption->status,
                        ],
                    ];
                }
            );

            if ($result['outcome'] === FinancialIdempotencyService::OUTCOME_CONFLICT) {
                return $this->fail(__('Idempotency-Key đã được sử dụng với dữ liệu khác.'), 409, 'IDEMPOTENCY_KEY_REUSED');
            }
            if ($result['outcome'] === FinancialIdempotencyService::OUTCOME_IN_PROGRESS) {
                return $this->fail(__('Yêu cầu cùng Idempotency-Key đang được xử lý.'), 409, 'IDEMPOTENCY_REQUEST_IN_PROGRESS');
            }

            if ($result['outcome'] === FinancialIdempotencyService::OUTCOME_COMPLETED) {
                // External notifications are intentionally skipped on replay.
                try {
                    Setting::sendEmailQueue($redemptionInput['email'], 'gift_created', [
                        'name' => $redemptionInput['fullname'],
                        'email' => $redemptionInput['email'],
                        'gift_title' => $gift->title,
                        'gift_price' => number_format($gift->price),
                    ]);
                } catch (\Throwable $e) {
                    \Log::warning('Gift redemption email notification failed.', ['exception' => $e::class]);
                }

                try {
                    Setting::sendTelegramTemplate('telegram_template_gift_created', [
                        'name' => $redemptionInput['fullname'],
                        'email' => $redemptionInput['email'],
                        'gift_title' => $gift->title,
                        'gift_price' => number_format($gift->price),
                    ]);
                } catch (\Throwable $e) {
                    \Log::warning('Gift redemption Telegram notification failed.', ['exception' => $e::class]);
                }
            }

            return $this->ok(
                $result['data'],
                __('Gửi yêu cầu đổi quà thành công! Vui lòng chờ admin duyệt và gửi dữ liệu.'),
                $result['status']
            );
        } catch (\RuntimeException $e) {
            $failures = [
                'GIFT_INACTIVE' => __('Món quà này hiện không còn hoạt động trên hệ thống.'),
                'GIFT_OUT_OF_STOCK' => __('Món quà này hiện đã hết hàng trong kho.'),
                'INSUFFICIENT_BALANCE' => __('Số dư ví khả dụng của bạn không đủ để quy đổi phần quà này.'),
            ];

            if (isset($failures[$e->getMessage()])) {
                return $this->fail($failures[$e->getMessage()], 400, 'REDEEM_FAILED');
            }

            \Log::error('Gift redemption transaction failed.', ['exception' => $e::class]);

            return $this->fail(__('Có lỗi hệ thống xảy ra khi đổi quà. Vui lòng thử lại sau.'), 500, 'REDEEM_ERROR');
        } catch (\Throwable $e) {
            \Log::error('Gift redemption transaction failed.', ['exception' => $e::class]);

            return $this->fail(__('Có lỗi xảy ra khi đổi quà, vui lòng thử lại sau.'), 500, 'REDEEM_ERROR');
        }
    }
}
