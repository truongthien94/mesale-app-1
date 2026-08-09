<?php

namespace App\Services;

use App\Models\CashbackHistory;
use App\Models\CashbackClick;
use App\Models\User;
use App\Models\Setting;
use App\Models\ShortLink;
use App\Services\CashbackApprovalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service đồng bộ báo cáo đơn hàng hoàn tiền Lazada từ Lazada Affiliate API (Conversion Report).
 * Mọi function, logic block đều có comment tiếng Việt giải thích rõ ràng.
 *
 * Khác biệt so với TikTok/Shopee:
 *  - Đối soát theo dải ngày dateStart/dateEnd (YYYY-MM-DD) + phân trang page/limit.
 *  - Đối soát đơn qua subId1 (hoặc affiliateSubId) khớp với trans_id đã nối vào tracking link.
 *  - Trạng thái đơn là chuỗi (Pending / Fulfilled / Rejected / Returned...) nên map phòng thủ theo chuỗi.
 */
class LazadaSyncService
{
    /**
     * Đồng bộ báo cáo đơn hàng hoàn tiền Lazada từ Lazada Affiliate API.
     *
     * @param int $days Số ngày gần đây cần quét dữ liệu để đối soát và phát hiện đơn hủy/hoàn muộn
     * @return array [success => bool, total => int, approved => int, rejected => int, details => array]
     */
    public function syncOrders(int $days = 30): array
    {
        // 0. Kiểm tra công tắc bật/tắt trước khi gọi bất kỳ request nào ra ngoài.
        // Nếu Admin đã tắt sàn Lazada hoặc tắt kết nối API thì dừng ngay, tuyệt đối không gọi Lazada Affiliate API.
        if (Setting::getVal('lazada_status', '0') !== '1') {
            Log::info('Lazada Sync: Bỏ qua vì sàn Lazada đang được tắt (lazada_status = 0).');
            return array_merge(
                $this->errorResult('Sàn Lazada đang được tắt trong cấu hình hệ thống.'),
                ['skipped' => true]
            );
        }

        if (Setting::getVal('apilazada_status', '1') !== '1') {
            Log::info('Lazada Sync: Bỏ qua vì kết nối API Lazada đang được tắt (apilazada_status = 0).');
            return array_merge(
                $this->errorResult('Kết nối API Lazada đang được tắt trong cấu hình hệ thống.'),
                ['skipped' => true]
            );
        }

        // 1. Đọc cấu hình kết nối Lazada Affiliate API từ settings và file môi trường .env
        $apiUrl = trim(Setting::getVal('apilazada_url')) ?: config('services.lazada.api_url', 'https://api.lazada.vn/rest');
        $userToken = Setting::getVal('apilazada_user_token') ?: config('services.lazada.user_token');
        $appKey = Setting::getVal('apilazada_app_key') ?: config('services.lazada.app_key');
        $appSecret = Setting::getVal('apilazada_app_secret') ?: config('services.lazada.app_secret');
        $subIdParam = trim(Setting::getVal('lazada_subid_param')) ?: 'sub_id1';

        // Trả về lỗi nếu thiếu bất kỳ thông tin xác thực API nào (cần đủ cả 3 để hoạt động)
        if (empty($userToken) || empty($appKey) || empty($appSecret)) {
            Log::error("Lazada Sync Error: Thiếu cấu hình API Lazada (userToken/appKey/appSecret).");
            return $this->errorResult('Hệ thống chưa được cấu hình cho Lazada. Vui lòng liên hệ quản trị viên.');
        }

        // Ràng buộc bảo mật (Anti-SSRF): Xác thực URL API Lazada không trỏ tới dải IP Private/Loopback nội bộ
        if (!\App\Helpers\SecurityHelper::validateSsfUrl($apiUrl)) {
            Log::error("Lazada Sync Error: apilazada_url is blocked by SSRF protector: " . $apiUrl);
            return $this->errorResult('Cấu hình URL API Lazada không hợp lệ hoặc không an toàn.');
        }

        // 2. Tính toán khoảng thời gian đồng bộ và chia nhỏ theo từng tháng dương lịch
        // (Lazada Open API bắt buộc dateStart và dateEnd phải thuộc cùng 1 tháng: "only support fetch single month data")
        $now = time();
        $globalEndDate = date('Y-m-d', $now);
        $globalStartDate = date('Y-m-d', $now - ($days * 24 * 3600));

        $dateRanges = $this->getDateRangesPerMonth($globalStartDate, $globalEndDate);

        $limit = 100;
        $successCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;
        $details = [];

        // Đọc cấu hình tỷ lệ hoàn tiền cho Lazada
        $cashbackSystemRate = (float)Setting::getVal('lazada_cashback_rate', 50);
        // Nhãn tên nền tảng dùng cho thông báo Telegram/Email
        $platformLabel = Setting::getVal('lazada_platform_name', 'Lazada');

        try {
            foreach ($dateRanges as $range) {
                $dateStart = $range['dateStart'];
                $dateEnd = $range['dateEnd'];
                $page = 1;
                $hasMore = true;

                while ($hasMore) {
                    Log::info("Lazada Sync API - Quét trang {$page}, từ {$dateStart} đến {$dateEnd}");

                    // Dựng tham số request: ký chuẩn Lazada Open Platform nếu có app_key + app_secret,
                    // ngược lại fallback gửi userToken thô (trường hợp gọi qua trung gian).
                    $apiPath = '/marketing/conversion/report';
                    $businessParams = [
                        'dateStart' => $dateStart,
                        'dateEnd' => $dateEnd,
                        'page' => $page,
                        'limit' => $limit
                    ];
                if (\App\Helpers\LazadaApiSigner::canSign($appKey, $appSecret)) {
                    // userToken là tham số nghiệp vụ (theo tài liệu), KHÔNG phải OAuth access_token → đưa vào params để ký.
                    $queryParams = \App\Helpers\LazadaApiSigner::signedParams($apiPath, array_merge($businessParams, ['userToken' => $userToken]), $appKey, $appSecret, null);
                } else {
                    $queryParams = array_merge(['userToken' => $userToken], $businessParams);
                }

                // Gọi API lấy báo cáo chuyển đổi (conversion report) từ Lazada
                $response = Http::withoutVerifying()
                    ->timeout(20)
                    ->withHeaders([
                        'Accept' => 'application/json'
                    ])
                    ->get(rtrim($apiUrl, '/') . $apiPath, $queryParams);

                if (!$response->successful()) {
                    throw new \Exception('Gọi Lazada API thất bại. HTTP Code: ' . $response->status() . ' - ' . $response->body());
                }

                $resData = $response->json();

                // Lazada trả mã lỗi ở cấp cao nhất khi request sai (thiếu app_key, sai chữ ký, token hết hạn...)
                if (!empty($resData['code']) && $resData['code'] !== '0' && $resData['code'] !== 0) {
                    throw new \Exception('Lazada API báo lỗi: ' . ($resData['message'] ?? $resData['code']) . ' - ' . $response->body());
                }
                // Lazada Open Platform bọc dữ liệu trong result.data; dò tìm phòng thủ qua nhiều tầng tùy region.
                $orders = $resData['result']['data']['results']
                    ?? $resData['result']['data']['orders']
                    ?? $resData['result']['data']
                    ?? $resData['result']['results']
                    ?? $resData['data']['results']
                    ?? $resData['data']['orders']
                    ?? $resData['data']
                    ?? $resData['results']
                    ?? $resData['orders']
                    ?? [];

                if (empty($orders) || !is_array($orders)) {
                    $hasMore = false;
                    break;
                }

                // Gom nhóm các sub-order có cùng parent orderId để tránh ghi đè và cộng dồn giá trị đơn/hoa hồng
                $groupedOrders = [];
                foreach ($orders as $order) {
                    if (!is_array($order)) {
                        continue;
                    }
                    $oId = (string)($order['orderId'] ?? '');
                    if ($oId === '') {
                        continue;
                    }

                    // Thu thập toàn bộ mã đối soát tiềm năng và mốc thời gian đặt hàng của dòng báo cáo này
                    $subIdVals = $this->extractSubIds($order, $subIdParam);
                    $orderTimeVal = $this->extractOrderTime($order);

                    if (!isset($groupedOrders[$oId])) {
                        $groupedOrders[$oId] = $order;
                        $groupedOrders[$oId]['product_names'] = [$order['skuName'] ?? __('Sản phẩm Lazada')];
                        $groupedOrders[$oId]['total_order_amt'] = (float)($order['orderAmt'] ?? 0);
                        $groupedOrders[$oId]['total_payout'] = $this->extractPayout($order);
                        // Tách riêng hoa hồng cơ bản và hoa hồng thưởng để hỗ trợ cấu hình loại trừ bonus
                        $groupedOrders[$oId]['total_base_payout'] = (float)($order['basePayout'] ?? 0);
                        $groupedOrders[$oId]['total_bonus_payout'] = (float)($order['bonusPayout'] ?? 0);
                        $groupedOrders[$oId]['statuses'] = [$this->mapStatus($order)];
                        $groupedOrders[$oId]['sub_ids'] = $subIdVals;
                        $groupedOrders[$oId]['order_time'] = $orderTimeVal;
                        $groupedOrders[$oId]['has_returned'] = !empty($order['returnedTime']);
                    } else {
                        $groupedOrders[$oId]['product_names'][] = $order['skuName'] ?? __('Sản phẩm Lazada');
                        $groupedOrders[$oId]['total_order_amt'] += (float)($order['orderAmt'] ?? 0);
                        $groupedOrders[$oId]['total_payout'] += $this->extractPayout($order);
                        $groupedOrders[$oId]['total_base_payout'] += (float)($order['basePayout'] ?? 0);
                        $groupedOrders[$oId]['total_bonus_payout'] += (float)($order['bonusPayout'] ?? 0);
                        $groupedOrders[$oId]['statuses'][] = $this->mapStatus($order);
                        if (!empty($order['returnedTime'])) {
                            $groupedOrders[$oId]['has_returned'] = true;
                        }
                        // Gộp thêm các mã đối soát tiềm năng của sub-order vào danh sách chung (loại trùng)
                        if (!empty($subIdVals)) {
                            $groupedOrders[$oId]['sub_ids'] = array_values(array_unique(
                                array_merge($groupedOrders[$oId]['sub_ids'] ?? [], $subIdVals)
                            ));
                        }
                        // Luôn giữ mốc đặt hàng sớm nhất trong các sub-order của cùng một đơn cha
                        if ($orderTimeVal !== '') {
                            $currentTime = (string)($groupedOrders[$oId]['order_time'] ?? '');
                            if ($currentTime === '' || $this->toTimestamp($orderTimeVal) < $this->toTimestamp($currentTime)) {
                                $groupedOrders[$oId]['order_time'] = $orderTimeVal;
                            }
                        }
                    }
                }

                // Chuẩn hóa dữ liệu sau khi nhóm và quyết định trạng thái chung của parent order
                foreach ($groupedOrders as $oId => &$gOrder) {
                    $uniqueNames = array_unique($gOrder['product_names']);
                    $gOrder['product_name'] = implode(', ', $uniqueNames);
                    $gOrder['orderAmt'] = $gOrder['total_order_amt'];
                    $gOrder['payout'] = $gOrder['total_payout'];
                    $gOrder['base_payout'] = $gOrder['total_base_payout'];
                    $gOrder['bonus_payout'] = $gOrder['total_bonus_payout'];

                    // 1 = pending, 2 = settled/hợp lệ, 3 = cancelled/hoàn trả
                    // Nếu có bất kỳ sub-order nào pending -> pending; nếu có hoàn trả -> cancelled; còn lại nếu có settled -> settled
                    $finalStatus = 2;
                    if (in_array(1, $gOrder['statuses'], true)) {
                        $finalStatus = 1;
                    } elseif ($gOrder['has_returned'] || (in_array(3, $gOrder['statuses'], true) && !in_array(2, $gOrder['statuses'], true))) {
                        $finalStatus = 3;
                    } elseif (in_array(2, $gOrder['statuses'], true)) {
                        $finalStatus = 2;
                    }
                    $gOrder['status_code'] = $finalStatus;
                }
                unset($gOrder);

                // Sắp xếp theo thời gian ĐẶT HÀNG tăng dần (giống Shopee dùng purchase_time) để đơn mua trước
                // luôn được tạo bản ghi trước, đảm bảo quy tắc chặn "đơn mua sau" của cùng một lượt click chạy đúng.
                // Lưu ý: KHÔNG dùng conversionTime vì đó là mốc ghi nhận chuyển đổi/đối soát, có thể lệch thứ tự so với thời điểm mua.
                uasort($groupedOrders, function ($a, $b) {
                    $timeA = $this->toTimestamp((string)($a['order_time'] ?? ''));
                    $timeB = $this->toTimestamp((string)($b['order_time'] ?? ''));
                    return $timeA <=> $timeB;
                });

                foreach ($groupedOrders as $order) {
                    $orderId = (string)($order['orderId'] ?? '');
                    $orderStatus = (int)($order['status_code'] ?? 1); // 1 = pending, 2 = settled, 3 = cancelled/returned
                    // Danh sách mã đối soát tiềm năng của đơn (Lazada có thể trả sub-id ở nhiều trường khác nhau tùy region)
                    $possibleSubIds = $order['sub_ids'] ?? [];
                    $subId = $possibleSubIds[0] ?? '';
                    $matchedSubId = null; // Mã đối soát thực sự khớp được với dữ liệu trong CSDL
                    $productName = $order['product_name'] ?? __('Sản phẩm Lazada');
                    $originalPrice = (float)($order['orderAmt'] ?? 0);

                    // Business Rule: Kiểm tra cấu hình xem hệ thống có cho phép cộng hoa hồng thưởng (bonus commission) khi hoàn tiền không.
                    // Nếu admin tắt hoa hồng thưởng, ta sẽ chỉ tính hoa hồng cơ bản (basePayout), loại trừ bonusPayout.
                    $applyBonusCommission = Setting::getVal('lazada_apply_bonus_commission', '1') === '1';

                    if ($applyBonusCommission) {
                        // Nếu bật: Lấy tổng hoa hồng ròng (gồm cả cơ bản + thưởng) từ estPayout hoặc basePayout + bonusPayout
                        $realCommission = (float)($order['payout'] ?? 0);
                    } else {
                        // Nếu tắt: Chỉ lấy hoa hồng cơ bản, loại trừ hoa hồng thưởng
                        $basePayout = (float)($order['base_payout'] ?? $order['basePayout'] ?? 0);
                        if ($basePayout > 0) {
                            $realCommission = $basePayout;
                        } else {
                            // Fallback: Trừ bonusPayout khỏi tổng payout nếu không có basePayout riêng
                            $totalPayout = (float)($order['payout'] ?? 0);
                            $bonusPayout = (float)($order['bonus_payout'] ?? $order['bonusPayout'] ?? 0);
                            $realCommission = max(0.0, $totalPayout - $bonusPayout);
                        }
                    }

                    if (empty($orderId)) {
                        continue;
                    }

                    // Tính toán tiền hoàn lại cho người mua (F0) dựa trên cấu hình hệ thống
                    // Chuẩn hoá về số nguyên đồng vì đồng Việt Nam không có đơn vị nhỏ hơn 1 đồng
                    $cashbackAmount = \App\Helpers\MoneyHelper::round($realCommission * ($cashbackSystemRate / 100));
                    // Chặn trên giá trị tỷ lệ để không vượt ngưỡng lưu trữ của cột decimal(5,2)
                    $cashbackRate = \App\Helpers\CashbackHelper::clampRate($originalPrice > 0 ? ($cashbackAmount / $originalPrice) * 100 : 0);

                    $statusSystem = 'ignored';
                    $messageSystem = 'Bỏ qua (Không tìm thấy lượt click hoặc mã đối soát giao dịch).';

                    // Tìm đơn hàng hoàn tiền đã có sẵn theo order_id thực tế từ Lazada
                    $cashback = CashbackHistory::where('order_id', $orderId)->first();

                    // Nếu chưa có, quét lần lượt từng mã đối soát tiềm năng để tìm theo trans_id.
                    // Quy tắc nghiệp vụ: Chỉ được phép liên kết (map) với bản ghi chưa được gán mã đơn hàng thực tế khác
                    // (order_id rỗng, null hoặc đang bằng chính trans_id) để tránh đơn hàng này ghi đè dữ liệu của đơn hàng khác
                    // khi nhiều đơn cùng dùng chung một sub-id (khách click lấy link 1 lần rồi mua nhiều đơn).
                    if (!$cashback) {
                        foreach ($possibleSubIds as $possibleId) {
                            $cashback = CashbackHistory::where('trans_id', $possibleId)
                                ->where(function ($query) use ($possibleId) {
                                    $query->whereNull('order_id')
                                        ->orWhere('order_id', '')
                                        ->orWhere('order_id', $possibleId);
                                })
                                ->first();
                            if ($cashback) {
                                $matchedSubId = $possibleId;
                                break;
                            }
                        }
                    }

                    if ($cashback) {
                        if ($cashback->status !== 'pending') {
                            $statusSystem = 'already_processed';
                            $messageSystem = 'Đơn hàng này đã được xử lý từ trước (Trạng thái trên web: ' . ($cashback->status === 'approved' ? 'Đã duyệt' : ($cashback->status === 'rejected' ? 'Từ chối/Hủy' : $cashback->status)) . ').';

                            // TỰ ĐỘNG THU HỒI HOÀN TIỀN (AUTO-CLAWBACK LAZADA)
                            // Nếu đơn đã duyệt trước đó nhưng Lazada báo hủy/hoàn trả (orderStatus = 3)
                            if ($cashback->status === 'approved' && $orderStatus === 3) {
                                $statusSystem = 'recalled';
                                $reasonRecall = 'Thu hồi hoàn tiền tự động: Đơn hàng bị hủy hoặc hoàn trả trên Lazada sau khi hoàn thành.';
                                $messageSystem = 'Hệ thống đã tự động thu hồi tiền hoàn của thành viên và hoa hồng MLM liên quan.';

                                DB::transaction(function () use ($cashback, $reasonRecall, &$rejectedCount, &$successCount) {
                                    $success = CashbackApprovalService::clawback($cashback, $reasonRecall, [
                                        'source' => 'sync'
                                    ]);
                                    if ($success) {
                                        $rejectedCount++;
                                        $successCount++;
                                    }
                                });
                            }
                        }
                    } else {
                        // Chưa có đơn hàng, quét lần lượt từng mã đối soát tiềm năng để tìm lượt click gốc từ cashback_clicks
                        $click = null;
                        $matchedSubId = null;
                        foreach ($possibleSubIds as $possibleId) {
                            $click = CashbackClick::where('trans_id', $possibleId)->first();
                            if ($click) {
                                $matchedSubId = $possibleId;
                                break;
                            }
                        }

                        // Nếu không có trong cashback_clicks, tìm qua short_links (chia sẻ link cho người khác mua hộ)
                        $shortLink = null;
                        if (!$click) {
                            foreach ($possibleSubIds as $possibleId) {
                                $shortLink = ShortLink::where('destination_url', 'like', '%' . $possibleId . '%')->first();
                                if ($shortLink) {
                                    $matchedSubId = $possibleId;
                                    break;
                                }
                            }
                        }

                        if ($click || $shortLink) {
                            $userId = $click ? $click->user_id : $shortLink->user_id;
                            $platform = $click ? ($click->platform ?? 'lazada') : 'lazada';
                            $affiliateUrl = $click ? $click->affiliate_url : $shortLink->destination_url;

                            // Lazada conversion report không trả ảnh sản phẩm. Ưu tiên ảnh từ lượt click gốc nếu tên khớp tương đối.
                            $productImageForDb = '';
                            if ($click && !empty($click->product_image) && $this->isProductMatched($click->product_name, $productName)) {
                                $productImageForDb = $click->product_image;
                            }

                            if ($userId) {
                                $cashback = CashbackHistory::create([
                                    'user_id' => $userId,
                                    'platform' => $platform,
                                    'order_id' => $orderId,
                                    'trans_id' => $matchedSubId,
                                    'product_name' => Str::limit($productName, 500, '...'),
                                    'product_image' => Str::limit($productImageForDb, 1000, ''),
                                    'original_price' => $originalPrice,
                                    'cashback_amount' => $cashbackAmount,
                                    'cashback_rate' => $cashbackRate,
                                    'commission_amount' => $realCommission,
                                    'affiliate_url' => $affiliateUrl,
                                    'status' => 'pending',
                                ]);

                                // Gửi thông báo Telegram khi ghi nhận đơn cashback Lazada mới
                                try {
                                    $user = User::find($userId);
                                    if ($user) {
                                        Setting::sendTelegramTemplate('telegram_template_cashback_created', [
                                            'name' => $user->name,
                                            'email' => $user->email,
                                            'product_name' => $productName,
                                            'price' => number_format($originalPrice),
                                            'cashback_amount' => number_format($cashbackAmount),
                                            'commission' => number_format($realCommission),
                                            'profit' => number_format($realCommission - $cashbackAmount),
                                            'platform' => $platformLabel,
                                        ]);
                                    }
                                } catch (\Exception $tgEx) {
                                    Log::error('Lỗi gửi Telegram khi tạo đơn cashback Lazada: ' . $tgEx->getMessage());
                                }

                                // Gửi email thông báo cho thành viên (hàng đợi bất đồng bộ)
                                try {
                                    $user = $user ?? User::find($userId);
                                    if ($user && !empty($user->email)) {
                                        Setting::sendEmailQueue($user->email, 'cashback_created', [
                                            'name' => $user->name,
                                            'email' => $user->email,
                                            'order_id' => $orderId,
                                            'product_name' => $productName,
                                            'price' => number_format($originalPrice),
                                            'cashback_amount' => number_format($cashbackAmount),
                                            'platform' => $platformLabel,
                                        ]);
                                    }
                                } catch (\Exception $mailEx) {
                                    Log::error('Lỗi gửi Email thông báo khi tạo đơn cashback Lazada: ' . $mailEx->getMessage());
                                }
                            }
                        }
                    }

                    // Xử lý trạng thái đối soát nếu đơn đang pending
                    if ($cashback && $cashback->status === 'pending') {
                        if ($cashback->order_id !== $orderId) {
                            $cashback->order_id = $orderId;
                        }
                        $cashback->original_price = $originalPrice;
                        $cashback->commission_amount = $realCommission;
                        $cashback->cashback_amount = $cashbackAmount;
                        $cashback->cashback_rate = $cashbackRate;

                        // Bổ sung ảnh sản phẩm từ click gốc nếu DB đang rỗng và tên khớp tương đối
                        if (empty($cashback->product_image)) {
                            $clickToUpdate = CashbackClick::where('trans_id', $cashback->trans_id)->first();
                            if ($clickToUpdate && !empty($clickToUpdate->product_image) && $this->isProductMatched($clickToUpdate->product_name, $productName)) {
                                $cashback->product_image = Str::limit($clickToUpdate->product_image, 1000, '');
                            }
                        }

                        $cashback->save();

                        if ($orderStatus === 3) {
                            // Đơn bị hủy/hoàn trả trên Lazada
                            $statusSystem = 'rejected';
                            $messageSystem = 'Hệ thống tự động từ chối (Đơn bị hủy hoặc hoàn trả trên Lazada).';

                            DB::transaction(function () use ($cashback, $orderId, &$rejectedCount, &$successCount) {
                                $cashback = CashbackHistory::where('id', $cashback->id)->lockForUpdate()->firstOrFail();
                                if ($cashback->status !== 'pending') {
                                    return;
                                }
                                $cashback->order_id = $orderId;
                                $cashback->save();

                                CashbackApprovalService::reject($cashback, 'Đơn hàng bị hủy hoặc hoàn trả trên Lazada (Đồng bộ tự động).', [
                                    'source' => 'sync'
                                ]);

                                $rejectedCount++;
                                $successCount++;
                            });
                        } elseif ($orderStatus === 2) {
                            // Đơn hợp lệ và đã hoàn tất (settled)
                            $checkProductMatch = Setting::getVal('lazada_check_product_match', '1') === '1';
                            $autoCashbackFuture = Setting::getVal('lazada_auto_cashback_future_orders', '0') === '1';

                            $isMatched = true;
                            $rejectReason = '';

                            // 1. Kiểm tra đơn mua sau (tương lai) của cùng lượt click nếu tắt cấu hình hoàn tiền tương lai
                            if (!$autoCashbackFuture) {
                                $hasPriorOrder = CashbackHistory::where('trans_id', $cashback->trans_id)
                                    ->where('id', '<', $cashback->id)
                                    ->exists();
                                if ($hasPriorOrder) {
                                    $isMatched = false;
                                    $rejectReason = 'Không hỗ trợ hoàn tiền cho các đơn hàng mua sau (tương lai) của cùng lượt click.';
                                }
                            }

                            // 2. Kiểm tra khớp tên sản phẩm nếu cấu hình yêu cầu
                            if ($isMatched && $checkProductMatch) {
                                if (!$this->isProductMatched($cashback->product_name, $productName)) {
                                    $isMatched = false;
                                    $rejectReason = 'Khách hàng mua sai sản phẩm so với link lấy ban đầu.';
                                }
                            }

                            if (!$isMatched) {
                                $statusSystem = 'rejected';
                                $messageSystem = 'Hệ thống tự động từ chối: ' . $rejectReason;

                                DB::transaction(function () use ($cashback, $orderId, $rejectReason, &$rejectedCount, &$successCount) {
                                    $cashback = CashbackHistory::where('id', $cashback->id)->lockForUpdate()->firstOrFail();
                                    if ($cashback->status !== 'pending') {
                                        return;
                                    }
                                    $cashback->order_id = $orderId;
                                    $cashback->save();

                                    CashbackApprovalService::reject($cashback, $rejectReason ?: 'Khách hàng mua sản phẩm không đủ điều kiện hoàn tiền (Đồng bộ tự động Lazada API).', [
                                        'source' => 'sync'
                                    ]);

                                    $rejectedCount++;
                                    $successCount++;
                                });
                            } else {
                                // Đơn hợp lệ và trùng khớp -> Tự động duyệt hoàn tiền và MLM
                                $statusSystem = 'approved';
                                $messageSystem = 'Hệ thống tự động duyệt hoàn tiền thành công.';

                                DB::transaction(function () use ($cashback, $originalPrice, $realCommission, $orderId, &$approvedCount, &$successCount) {
                                    $cashback = CashbackHistory::where('id', $cashback->id)->lockForUpdate()->firstOrFail();
                                    if ($cashback->status !== 'pending') {
                                        return;
                                    }
                                    $cashback->order_id = $orderId;
                                    $cashback->save();

                                    CashbackApprovalService::approve($cashback, $originalPrice, $realCommission, [
                                        'source' => 'sync'
                                    ]);

                                    $approvedCount++;
                                    $successCount++;
                                });
                            }
                        } else {
                            $statusSystem = 'pending';
                            $messageSystem = 'Đơn hàng đang ở trạng thái Chờ đối soát từ Lazada, tiếp tục chờ duyệt.';
                        }
                    }

                    // Chuyển đổi trạng thái hiển thị thân thiện
                    $statusLazadaVi = __('Chờ đối soát');
                    if ($orderStatus === 2) {
                        $statusLazadaVi = __('Đơn hợp lệ');
                    } elseif ($orderStatus === 3) {
                        $statusLazadaVi = __('Đã hủy');
                    }

                    // Hiển thị thời gian đặt hàng thực tế trong bảng đối soát (chuẩn hoá cả dạng epoch lẫn dạng chuỗi ngày giờ)
                    $purchaseTimeRaw = (string)($order['order_time'] ?? '');
                    $purchaseTimestamp = $this->toTimestamp($purchaseTimeRaw);
                    $purchaseTimeDisplay = $purchaseTimestamp > 0
                        ? date('Y-m-d H:i:s', $purchaseTimestamp)
                        : ($purchaseTimeRaw !== '' ? $purchaseTimeRaw : date('Y-m-d H:i:s'));

                    $details[] = [
                        'order_sn' => $orderId,
                        'purchase_time' => $purchaseTimeDisplay,
                        'product_name' => $productName,
                        'actual_amount' => $originalPrice,
                        'commission' => $realCommission,
                        'cashback_amount' => $cashbackAmount,
                        'status_shopee' => $statusLazadaVi,
                        'status_system' => $statusSystem,
                        'message_system' => $messageSystem,
                        'utm_content' => $matchedSubId ?: ($subId ?: '-')
                    ];
                }

                // Nếu số lượng đơn trả về nhỏ hơn limit, không còn trang tiếp theo trong tháng này
                if (count($orders) < $limit) {
                    $hasMore = false;
                } else {
                    $page++;
                }
            }
            } // Kết thúc vòng lặp foreach ($dateRanges as $range)

            // Lưu thời gian đồng bộ thành công gần nhất
            Setting::setVal('lazada_last_sync_timestamp', $now);

            return [
                'success' => true,
                'total' => $successCount,
                'approved' => $approvedCount,
                'rejected' => $rejectedCount,
                'details' => $details
            ];

        } catch (\Exception $e) {
            Log::error("Đồng bộ Lazada orders lỗi: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'success' => false,
                'total' => $successCount,
                'approved' => $approvedCount,
                'rejected' => $rejectedCount,
                'details' => $details,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Thu thập TẤT CẢ các giá trị sub-id (mã đối soát trans_id) tiềm năng từ một dòng conversion report.
     * Tại sao phải lấy nhiều giá trị: giống Shopee (utm_content/utm_source/sub_id1), Lazada cũng có thể trả mã đối soát
     * ở các trường khác nhau tùy region và tùy loại link (subId1, affiliateSubId...). Nếu chỉ lấy đúng một trường,
     * hệ thống sẽ không tìm ra lượt click gốc và bỏ sót đơn hoàn tiền của thành viên.
     *
     * Thứ tự ưu tiên: tham số đã cấu hình -> affiliateSubId -> các subId còn lại.
     * Mỗi giá trị được lấy cả dạng nguyên gốc lẫn dạng đã cắt hậu tố sau dấu gạch ngang (giống Shopee),
     * vì một số kênh có thể nối thêm hậu tố vào sub-id khi điều hướng.
     *
     * @param array $order
     * @param string $subIdParam Tên tham số sub-id đã cấu hình (ví dụ sub_id1)
     * @return array Danh sách mã đối soát tiềm năng (đã loại trùng, giữ nguyên thứ tự ưu tiên)
     */
    private function extractSubIds(array $order, string $subIdParam): array
    {
        // Chuyển sub_id1 -> subId1 để khớp với tên field trong response report
        $fieldFromParam = preg_replace_callback('/_([a-z0-9])/i', function ($m) {
            return strtoupper($m[1]);
        }, $subIdParam);

        $candidates = [$fieldFromParam, 'affiliateSubId', 'subId1', 'subId2', 'subId3', 'subId4', 'subId5', 'subId6'];

        $values = [];
        foreach ($candidates as $field) {
            if (empty($order[$field])) {
                continue;
            }

            $raw = trim((string)$order[$field]);
            if ($raw === '') {
                continue;
            }

            // Giữ giá trị nguyên gốc trước (phòng trường hợp Admin cấu hình tiền tố mã đơn có chứa dấu gạch ngang)
            $values[] = $raw;

            // Bổ sung phần đầu trước dấu gạch ngang giống cách Shopee bóc tách utm_content/sub_id1
            $head = trim(explode('-', $raw)[0]);
            if ($head !== '') {
                $values[] = $head;
            }
        }

        return array_values(array_unique($values));
    }

    /**
     * Trích xuất mốc thời gian ĐẶT HÀNG của một dòng conversion report.
     * Ưu tiên các trường mô tả thời điểm khách đặt mua; chỉ dùng conversionTime khi không có trường nào khác,
     * vì conversionTime là mốc ghi nhận chuyển đổi nên có thể lệch thứ tự so với thời điểm mua thực tế.
     *
     * @param array $order
     * @return string
     */
    private function extractOrderTime(array $order): string
    {
        foreach (['orderTime', 'orderCreateTime', 'orderCreatedTime', 'purchaseTime', 'conversionTime'] as $field) {
            if (!empty($order[$field])) {
                return trim((string)$order[$field]);
            }
        }
        return '';
    }

    /**
     * Chuẩn hoá mốc thời gian của Lazada về timestamp (giây).
     * Lazada tùy region có thể trả về chuỗi ngày giờ (2026-08-04 10:20:30) hoặc epoch dạng giây/mili giây.
     *
     * @param string $value
     * @return int
     */
    private function toTimestamp(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        // Dạng epoch thuần số: tự nhận biết mili giây (13 chữ số) để quy đổi về giây
        if (ctype_digit($value)) {
            $number = (int)$value;
            return $number > 99999999999 ? (int)($number / 1000) : $number;
        }

        return strtotime($value) ?: 0;
    }

    /**
     * Trích xuất hoa hồng tuyệt đối (payout) từ một dòng conversion report.
     * Ưu tiên estPayout (base + bonus); fallback basePayout + bonusPayout.
     *
     * @param array $order
     * @return float
     */
    private function extractPayout(array $order): float
    {
        if (isset($order['estPayout']) && is_numeric($order['estPayout'])) {
            return (float)$order['estPayout'];
        }
        $base = (float)($order['basePayout'] ?? 0);
        $bonus = (float)($order['bonusPayout'] ?? 0);
        return $base + $bonus;
    }

    /**
     * Ánh xạ trạng thái chuỗi của Lazada về mã nội bộ: 1 = pending, 2 = settled/hợp lệ, 3 = cancelled/hoàn trả.
     * Dùng so khớp chuỗi phòng thủ vì các region Lazada có thể trả về nhãn khác nhau.
     *
     * @param array $order
     * @return int
     */
    private function mapStatus(array $order): int
    {
        // Có hoàn hàng -> coi như hủy
        if (!empty($order['returnedTime'])) {
            return 3;
        }

        $status = mb_strtolower(trim((string)($order['status'] ?? '')));
        $validity = mb_strtolower(trim((string)($order['validity'] ?? '')));

        // Các nhãn coi là hủy/không hợp lệ
        foreach (['reject', 'invalid', 'cancel', 'return', 'fail'] as $bad) {
            if (str_contains($status, $bad) || str_contains($validity, $bad)) {
                return 3;
            }
        }

        // Các nhãn coi là hợp lệ / đã hoàn tất
        foreach (['fulfill', 'deliver', 'settl', 'approv', 'valid', 'confirm', 'complete', 'paid'] as $good) {
            if (str_contains($status, $good) || str_contains($validity, $good)) {
                return 2;
            }
        }

        // Mặc định coi là đang chờ đối soát
        return 1;
    }

    /**
     * Kiểm tra tên sản phẩm thực mua có khớp/tương đồng với sản phẩm lấy link ban đầu không.
     */
    private function isProductMatched(string $originalName, string $actualName): bool
    {
        $originalName = mb_strtolower(trim($originalName));
        $actualName = mb_strtolower(trim($actualName));

        if (empty($originalName) || empty($actualName)) {
            return false;
        }

        if (str_contains($actualName, $originalName) || str_contains($originalName, $actualName)) {
            return true;
        }

        $origWords = array_filter(explode(' ', preg_replace('/[^\w\s]/u', ' ', $originalName)), function ($w) {
            return mb_strlen(trim($w)) >= 2;
        });

        if (empty($origWords)) {
            return false;
        }

        $matchedCount = 0;
        foreach ($origWords as $word) {
            if (str_contains($actualName, $word)) {
                $matchedCount++;
            }
        }

        $matchRate = $matchedCount / count($origWords);
        return $matchRate >= 0.40;
    }

    /**
     * Trả về mảng kết quả lỗi chuẩn hoá.
     *
     * @param string $message
     * @return array
     */
    private function errorResult(string $message): array
    {
        return [
            'success' => false,
            'total' => 0,
            'approved' => 0,
            'rejected' => 0,
            'details' => [],
            'error' => $message
        ];
    }

    /**
     * Chia khoảng ngày [startDate, endDate] thành các khoảng nhỏ,
     * đảm bảo mỗi khoảng chỉ nằm gói gọn trong cùng 1 tháng dương lịch
     * để đáp ứng quy định "only support fetch single month data" từ Lazada Open API.
     *
     * @param string $startDateStr Định dạng YYYY-MM-DD
     * @param string $endDateStr Định dạng YYYY-MM-DD
     * @return array Danh sách các khoảng ngày ['dateStart' => '...', 'dateEnd' => '...']
     */
    private function getDateRangesPerMonth(string $startDateStr, string $endDateStr): array
    {
        $ranges = [];
        $currentStart = \Carbon\Carbon::parse($startDateStr)->startOfDay();
        $targetEnd = \Carbon\Carbon::parse($endDateStr)->endOfDay();

        while ($currentStart->lte($targetEnd)) {
            // Lấy ngày cuối cùng của tháng hiện tại
            $endOfMonth = $currentStart->copy()->endOfMonth()->startOfDay();

            // Ngày kết thúc của đoạn là ngày nhỏ hơn giữa endOfMonth và targetEnd
            $currentEnd = $endOfMonth->lt($targetEnd) ? $endOfMonth : $targetEnd->copy()->startOfDay();

            $ranges[] = [
                'dateStart' => $currentStart->format('Y-m-d'),
                'dateEnd' => $currentEnd->format('Y-m-d')
            ];

            // Chuyển sang ngày đầu tiên của tháng tiếp theo
            $currentStart = $endOfMonth->copy()->addDay();
        }

        return $ranges;
    }
}
