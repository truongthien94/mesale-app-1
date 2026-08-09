<?php

namespace App\Services;

use App\Models\CashbackHistory;
use App\Models\CashbackClick;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\ShortLink;
use App\Services\CashbackApprovalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service đồng bộ báo cáo đơn hàng hoàn tiền TikTok Shop từ RioHub API.
 * Mọi function, logic block đều có comment tiếng Việt giải thích rõ ràng.
 */
class TikTokSyncService
{
    /**
     * Đồng bộ báo cáo đơn hàng hoàn tiền TikTok Shop từ RioHub API.
     * Giải thích: Lọc dữ liệu từ RioHub API và đối chiếu với click logs để tự động duyệt/từ chối hoàn tiền,
     * đồng thời phân chia hoa hồng MLM 2 tầng (F1/F2).
     *
     * @param int $days Số ngày gần đây cần quét dữ liệu (nếu không có mốc lưu trữ)
     * @return array [success => bool, total => int, approved => int, rejected => int]
     */
    public function syncOrders(int $days = 30): array
    {
        // 0. Kiểm tra công tắc bật/tắt trước khi gọi bất kỳ request nào ra ngoài.
        // Nếu Admin đã tắt sàn TikTok Shop hoặc tắt kết nối API thì dừng ngay, tuyệt đối không gọi RioHub API.
        if (Setting::getVal('tiktok_status', '1') !== '1') {
            Log::info('TikTok Sync: Bỏ qua vì sàn TikTok Shop đang được tắt (tiktok_status = 0).');
            return [
                'success' => false,
                'skipped' => true,
                'total' => 0,
                'approved' => 0,
                'rejected' => 0,
                'details' => [],
                'error' => 'Sàn TikTok Shop đang được tắt trong cấu hình hệ thống.'
            ];
        }

        if (Setting::getVal('apitiktok_status', '1') !== '1') {
            Log::info('TikTok Sync: Bỏ qua vì kết nối API TikTok Shop đang được tắt (apitiktok_status = 0).');
            return [
                'success' => false,
                'skipped' => true,
                'total' => 0,
                'approved' => 0,
                'rejected' => 0,
                'details' => [],
                'error' => 'Kết nối API TikTok Shop đang được tắt trong cấu hình hệ thống.'
            ];
        }

        // 1. Đọc cấu hình kết nối API của RioHub từ settings và file môi trường .env
        $apiUrl = trim(Setting::getVal('apitiktok_url')) ?: 'https://riohub.vn/api/v1';
        $apiKey = Setting::getVal('apitiktok_key') ?: config('services.riohub.api_key');
        $creatorUsername = Setting::getVal('tiktok_creator_username');

        // Trả về lỗi nếu admin chưa cấu hình API Key hoặc Creator Username
        if (empty($apiKey)) {
            Log::error("TikTok Sync Error: apitiktok_key / RIOHUB_API_KEY is not configured");
            return [
                'success' => false,
                'total' => 0,
                'approved' => 0,
                'rejected' => 0,
                'details' => [],
                'error' => 'Hệ thống chưa được cấu hình API Key cho TikTok Shop.'
            ];
        }

        if (empty($creatorUsername)) {
            Log::error("TikTok Sync Error: tiktok_creator_username is not configured in settings");
            return [
                'success' => false,
                'total' => 0,
                'approved' => 0,
                'rejected' => 0,
                'details' => [],
                'error' => 'Hệ thống chưa được cấu hình Creator Username cho TikTok Shop.'
            ];
        }

        // Ràng buộc bảo mật (Anti-SSRF): Xác thực URL API TikTok không trỏ tới dải IP Private/Loopback nội bộ
        if (!\App\Helpers\SecurityHelper::validateSsfUrl($apiUrl)) {
            Log::error("TikTok Sync Error: apitiktok_url is blocked by SSRF protector: " . $apiUrl);
            return [
                'success' => false,
                'total' => 0,
                'approved' => 0,
                'rejected' => 0,
                'details' => [],
                'error' => 'Cấu hình URL API TikTok Shop không hợp lệ hoặc không an toàn.'
            ];
        }

        // 2. Tính toán khoảng thời gian đồng bộ (Luôn quét lùi lại 30 ngày qua để đối soát và phát hiện đơn hàng bị hủy/hoàn trả muộn)
        $now = time();
        $start = $now - ($days * 24 * 3600);

        $page = 1;
        $pageSize = 50;
        $hasMore = true;

        $successCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;
        $details = [];

        // Đọc cấu hình tỷ lệ hoàn tiền cho TikTok Shop
        $cashbackSystemRate = (float)Setting::getVal('tiktok_cashback_rate', 50);

        try {
            while ($hasMore) {
                Log::info("TikTok Sync API - Quét trang {$page}, start: {$start}, end: {$now}");
                
                // Gọi API lấy danh sách đơn hàng affiliate từ RioHub
                $response = Http::withoutVerifying()
                    ->timeout(15)
                    ->withHeaders([
                        'X-Riohub-Api-Key' => $apiKey,
                        'Accept' => 'application/json'
                    ])
                    ->get(rtrim($apiUrl, '/') . '/partner/tiktok/affiliate/orders', [
                        'creator_username' => $creatorUsername,
                        'update_time_start' => $start,
                        'update_time_end' => $now,
                        'page' => $page,
                        'page_size' => $pageSize
                    ]);

                if (!$response->successful()) {
                    throw new \Exception('Gọi RioHub API thất bại. HTTP Code: ' . $response->status() . ' - ' . $response->body());
                }

                $resData = $response->json();
                $orders = $resData['orders'] ?? [];

                if (empty($orders)) {
                    $hasMore = false;
                    break;
                }

                // Nhóm các đơn hàng TikTok Shop có cùng order_id từ API để tránh việc ghi đè dữ liệu sản phẩm của nhau
                // Giải thích: Một đơn hàng có thể chứa nhiều sản phẩm (được trả về dưới dạng các dòng khác nhau có cùng order_id).
                // Chúng ta nhóm và cộng dồn giá trị đơn hàng, hoa hồng cũng như ghép tên các sản phẩm thành một dòng duy nhất.
                // Để hỗ trợ tính năng bật/tắt hoa hồng thưởng, chúng ta cộng dồn tất cả các trường hoa hồng chi tiết (chuẩn, thưởng, quảng cáo shop).
                $groupedOrders = [];
                foreach ($orders as $order) {
                    $oId = $order['order_id'] ?? '';
                    if (empty($oId)) {
                        continue;
                    }
                    
                    if (!isset($groupedOrders[$oId])) {
                        $groupedOrders[$oId] = $order;
                        // Chuyển product_name thành mảng để gộp các sản phẩm
                        $groupedOrders[$oId]['product_names'] = [$order['product_name'] ?? 'Sản phẩm TikTok Shop'];
                        $groupedOrders[$oId]['total_price'] = (float)($order['price'] ?? $order['original_price'] ?? 0);
                        $groupedOrders[$oId]['total_commission'] = (float)($order['actual_commission'] ?? $order['est_commission'] ?? $order['commission_amount'] ?? 0);
                        
                        // Lưu vết các loại hoa hồng riêng biệt để tính toán loại trừ thưởng khi gộp đơn
                        $groupedOrders[$oId]['total_actual_commission'] = isset($order['actual_commission']) ? (float)$order['actual_commission'] : null;
                        $groupedOrders[$oId]['total_actual_bonus_commission'] = isset($order['actual_bonus_commission']) ? (float)$order['actual_bonus_commission'] : null;
                        
                        $groupedOrders[$oId]['total_est_standard_commission'] = (float)($order['est_standard_commission'] ?? 0);
                        $groupedOrders[$oId]['total_est_shop_ads_commission'] = (float)($order['est_shop_ads_commission'] ?? 0);
                        $groupedOrders[$oId]['total_est_bonus_commission'] = (float)($order['est_bonus_commission'] ?? 0);
                        $groupedOrders[$oId]['total_est_commission'] = (float)($order['est_commission'] ?? 0);
                        
                        $groupedOrders[$oId]['statuses'] = [(int)($order['status'] ?? $order['order_status'] ?? 1)];
                    } else {
                        $groupedOrders[$oId]['product_names'][] = $order['product_name'] ?? 'Sản phẩm TikTok Shop';
                        $groupedOrders[$oId]['total_price'] += (float)($order['price'] ?? $order['original_price'] ?? 0);
                        $groupedOrders[$oId]['total_commission'] += (float)($order['actual_commission'] ?? $order['est_commission'] ?? $order['commission_amount'] ?? 0);
                        
                        // Cộng dồn hoa hồng thực tế nếu có
                        if (isset($order['actual_commission'])) {
                            if ($groupedOrders[$oId]['total_actual_commission'] === null) {
                                $groupedOrders[$oId]['total_actual_commission'] = 0.0;
                            }
                            $groupedOrders[$oId]['total_actual_commission'] += (float)$order['actual_commission'];
                        }
                        
                        // Cộng dồn hoa hồng thưởng thực tế
                        if (isset($order['actual_bonus_commission'])) {
                            if ($groupedOrders[$oId]['total_actual_bonus_commission'] === null) {
                                $groupedOrders[$oId]['total_actual_bonus_commission'] = 0.0;
                            }
                            $groupedOrders[$oId]['total_actual_bonus_commission'] += (float)$order['actual_bonus_commission'];
                        }
                        
                        $groupedOrders[$oId]['total_est_standard_commission'] += (float)($order['est_standard_commission'] ?? 0);
                        $groupedOrders[$oId]['total_est_shop_ads_commission'] += (float)($order['est_shop_ads_commission'] ?? 0);
                        $groupedOrders[$oId]['total_est_bonus_commission'] += (float)($order['est_bonus_commission'] ?? 0);
                        $groupedOrders[$oId]['total_est_commission'] += (float)($order['est_commission'] ?? 0);
                        
                        $groupedOrders[$oId]['statuses'][] = (int)($order['status'] ?? $order['order_status'] ?? 1);
                        
                        // Cập nhật sub_id nếu bản ghi trước đó chưa có sub_id mà bản ghi này có
                        if (empty($groupedOrders[$oId]['sub_id']) && !empty($order['sub_id'])) {
                            $groupedOrders[$oId]['sub_id'] = $order['sub_id'];
                        }
                    }
                }
 
                // Xử lý chuẩn hóa dữ liệu sau khi nhóm
                foreach ($groupedOrders as $oId => &$gOrder) {
                    // Loại bỏ tên trùng lặp và ghép lại bằng dấu phẩy
                    $uniqueNames = array_unique($gOrder['product_names']);
                    $gOrder['product_name'] = implode(', ', $uniqueNames);
                    $gOrder['price'] = $gOrder['total_price'];
                    
                    // Ghi nhận hoa hồng thực tế đã cộng dồn
                    if ($gOrder['total_actual_commission'] !== null) {
                        $gOrder['actual_commission'] = $gOrder['total_actual_commission'];
                    } else {
                        unset($gOrder['actual_commission']);
                    }
                    
                    if ($gOrder['total_actual_bonus_commission'] !== null) {
                        $gOrder['actual_bonus_commission'] = $gOrder['total_actual_bonus_commission'];
                    } else {
                        unset($gOrder['actual_bonus_commission']);
                    }
                    
                    $gOrder['est_standard_commission'] = $gOrder['total_est_standard_commission'];
                    $gOrder['est_shop_ads_commission'] = $gOrder['total_est_shop_ads_commission'];
                    $gOrder['est_bonus_commission'] = $gOrder['total_est_bonus_commission'];
                    $gOrder['est_commission'] = $gOrder['total_est_commission'];
                    $gOrder['commission_amount'] = $gOrder['total_commission'];
 
                    // Quyết định trạng thái đơn hàng chung: 1 = pending, 2 = settled, 3 = cancelled/refunded
                    // Nếu có bất kỳ item nào pending (1) -> pending (1) để tiếp tục đối soát
                    // Nếu không có item nào pending, nhưng có item settled (2) -> settled (2) để duyệt hoàn tiền
                    // Nếu tất cả các item đều cancelled/refunded (3) -> cancelled (3)
                    $finalStatus = 2;
                    if (in_array(1, $gOrder['statuses'])) {
                        $finalStatus = 1;
                    } elseif (in_array(3, $gOrder['statuses']) && !in_array(2, $gOrder['statuses'])) {
                        $finalStatus = 3;
                    } elseif (in_array(2, $gOrder['statuses'])) {
                        $finalStatus = 2;
                    }
                    $gOrder['status'] = $finalStatus;
                }
                unset($gOrder);
 
                // Sắp xếp các đơn hàng theo thời gian tạo (create_time) tăng dần để đơn hàng mua trước luôn được xử lý trước
                uasort($groupedOrders, function ($a, $b) {
                    $timeA = (int)($a['create_time'] ?? 0);
                    $timeB = (int)($b['create_time'] ?? 0);
                    return $timeA <=> $timeB;
                });
 
                foreach ($groupedOrders as $order) {
                    $orderId = $order['order_id'] ?? '';
                    $orderStatus = (int)($order['status'] ?? $order['order_status'] ?? 1); // 1 = pending, 2 = settled, 3 = cancelled/refunded
                    $subId = $order['sub_id'] ?? ''; // Mã trans_id đối soát
                    $productName = $order['product_name'] ?? 'Sản phẩm TikTok Shop';
                    $productImage = $order['product_image'] ?? '';
                    
                    // API RioHub trả về 'price' cho giá trị đơn
                    $originalPrice = (float)($order['price'] ?? $order['original_price'] ?? 0);
                    
                    // Business Rule: Kiểm tra cấu hình xem hệ thống có cho phép cộng hoa hồng thưởng (bonus commission) khi hoàn tiền không.
                    // Nếu admin tắt hoa hồng thưởng, ta sẽ trừ phần hoa hồng thưởng ra khỏi tổng hoa hồng ròng (hoặc tính bằng tổng hoa hồng chuẩn + QC shop).
                    $applyBonusCommission = Setting::getVal('tiktok_apply_bonus_commission', '1') === '1';
                    
                    if ($applyBonusCommission) {
                        // Nếu bật: Lấy tổng hoa hồng ròng (gồm cả chuẩn + thưởng + quảng cáo shop)
                        $realCommission = (float)($order['actual_commission'] ?? $order['est_commission'] ?? $order['commission_amount'] ?? 0);
                    } else {
                        // Nếu tắt: Loại trừ hoa hồng thưởng ra khỏi tổng số tiền tính hoàn tiền.
                        // - Thực tế sau đối soát: actual_commission - actual_bonus_commission
                        // - Ước tính (chờ đối soát hoặc chưa về): est_standard_commission + est_shop_ads_commission (hoặc est_commission - est_bonus_commission)
                        if (isset($order['actual_commission'])) {
                            $actualComm = (float)$order['actual_commission'];
                            $actualBonus = (float)($order['actual_bonus_commission'] ?? 0);
                            $realCommission = max(0.0, $actualComm - $actualBonus);
                        } else {
                            $estStandard = (float)($order['est_standard_commission'] ?? 0);
                            $estShopAds = (float)($order['est_shop_ads_commission'] ?? 0);
                            if ($estStandard > 0 || $estShopAds > 0) {
                                $realCommission = $estStandard + $estShopAds;
                            } else {
                                $estComm = (float)($order['est_commission'] ?? $order['commission_amount'] ?? 0);
                                $estBonus = (float)($order['est_bonus_commission'] ?? 0);
                                $realCommission = max(0.0, $estComm - $estBonus);
                            }
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

                    // Khởi tạo trạng thái đối soát mặc định cho đơn hàng này
                    $statusSystem = 'ignored';
                    $messageSystem = 'Bỏ qua (Không tìm thấy lượt click hoặc mã đối soát giao dịch).';

                    // Tìm đơn hàng hoàn tiền đã có sẵn trong database dựa trên order_id thực tế từ TikTok
                    $cashback = CashbackHistory::where('order_id', $orderId)->first();

                    // Nếu chưa có đơn hàng, tìm theo trans_id (đối chiếu qua sub_id)
                    // Quy tắc nghiệp vụ: Chỉ được phép liên kết (map) với bản ghi chưa được gán mã đơn hàng thực tế khác
                    // (tức là order_id rỗng, null hoặc trùng khớp với trans_id để tránh ghi đè dữ liệu của đơn hàng khác)
                    if (!$cashback && !empty($subId)) {
                        $cashback = CashbackHistory::where('trans_id', $subId)
                            ->where(function ($query) use ($subId) {
                                $query->whereNull('order_id')
                                    ->orWhere('order_id', '')
                                    ->orWhere('order_id', $subId);
                            })
                            ->first();
                    }

                    // Nếu đã có bản ghi đơn hàng nhưng trạng thái không còn là chờ duyệt, bỏ qua hoặc thu hồi nếu đơn bị hủy
                    if ($cashback) {
                        if ($cashback->status !== 'pending') {
                            $statusSystem = 'already_processed';
                            $messageSystem = 'Đơn hàng này đã được xử lý từ trước (Trạng thái trên web: ' . ($cashback->status === 'approved' ? 'Đã duyệt' : ($cashback->status === 'rejected' ? 'Từ chối/Hủy' : $cashback->status)) . ').';

                            // TỰ ĐỘNG THU HỒI HOÀN TIỀN (AUTO-CLAWBACK TIKTOK SHOP)
                            // Nếu đơn hàng đã duyệt trước đó trên web, nhưng TikTok Shop API báo đơn bị hủy/hoàn trả (orderStatus = 3)
                            if ($cashback->status === 'approved' && $orderStatus === 3) {
                                $statusSystem = 'recalled';
                                $reasonRecall = 'Thu hồi hoàn tiền tự động: Đơn hàng bị hủy hoặc hoàn trả trên TikTok Shop sau khi hoàn thành.';
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
                        // Nếu chưa có đơn hàng, tiến hành tìm lượt click gốc từ bảng cashback_clicks
                        $click = null;
                        if (!empty($subId)) {
                            $click = CashbackClick::where('trans_id', $subId)->first();
                        }

                        // Nếu không tìm thấy trong cashback_clicks, tìm qua bảng short_links (chia sẻ link)
                        $shortLink = null;
                        if (!$click && !empty($subId)) {
                            $shortLink = ShortLink::where('destination_url', 'like', '%' . $subId . '%')->first();
                        }

                        // Nếu tìm thấy click / link gốc hợp lệ, tiến hành tạo mới đơn hàng hoàn tiền (chờ duyệt)
                        if ($click || $shortLink) {
                            $userId = $click ? $click->user_id : $shortLink->user_id;
                            $platform = $click ? ($click->platform ?? 'tiktok') : 'tiktok';
                            $transIdToUse = $subId;
                            $affiliateUrl = $click ? $click->affiliate_url : $shortLink->destination_url;

                            // Quy tắc lấy ảnh sản phẩm TikTok Shop: API danh sách đơn hàng RioHub không trả về product_image.
                            // Vì vậy, hệ thống sẽ ưu tiên truy vấn từ cache products cục bộ theo product_id thực tế của TikTok trước để đảm bảo ảnh khớp chính xác.
                            // Nếu cache chưa có ảnh (ví dụ sản phẩm mua kèm B chưa được dán link), hệ thống sẽ chủ động gọi API lấy thông tin sản phẩm và cập nhật cache.
                            // Nếu không có, ta mới đối soát qua lượt click gốc và chỉ lấy ảnh nếu tên khớp tương đối.
                            $productImageForDb = '';
                            $productId = $order['product_id'] ?? '';
                            if (!empty($productId)) {
                                $tiktokDbId = 'tiktok_' . $productId;
                                $cachedProduct = \App\Models\Product::where('shopee_id', $tiktokDbId)->first();
                                if ($cachedProduct && !empty($cachedProduct->image)) {
                                    $productImageForDb = $cachedProduct->image;
                                } else {
                                    // Gọi API đối tác lấy chi tiết sản phẩm và cập nhật cache database
                                    $productImageForDb = $this->fetchAndCacheProductInfo($productId, $apiUrl, $apiKey, $creatorUsername) ?: '';
                                }
                            }

                            // Nếu cache chưa có ảnh, và click gốc có chứa ảnh, ta so sánh độ trùng khớp tên sản phẩm để quyết định lấy ảnh hay không
                            if (empty($productImageForDb) && $click && !empty($click->product_image)) {
                                if ($this->isProductMatched($click->product_name, $productName)) {
                                    $productImageForDb = $click->product_image;
                                }
                            }

                            if ($userId) {
                                // Luôn luôn tạo mới đơn hàng hoàn tiền riêng biệt cho mỗi order_id từ API
                                $cashback = CashbackHistory::create([
                                    'user_id' => $userId,
                                    'platform' => $platform,
                                    'order_id' => $orderId,
                                    'trans_id' => $transIdToUse,
                                    'product_name' => Str::limit($productName, 500, '...'),
                                    'product_image' => Str::limit($productImageForDb ?: $productImage, 1000, ''),
                                    'original_price' => $originalPrice,
                                    'cashback_amount' => $cashbackAmount,
                                    'cashback_rate' => $cashbackRate,
                                    'commission_amount' => $realCommission,
                                    'affiliate_url' => $affiliateUrl,
                                    'status' => 'pending',
                                ]);

                                // Gửi thông báo Telegram khi ghi nhận đơn hàng cashback TikTok mới
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
                                            'platform' => $platform === 'shopee' ? Setting::getVal('shopee_platform_name', 'Shopee') : Setting::getVal('tiktok_platform_name', 'TikTok Shop'),
                                        ]);
                                    }
                                } catch (\Exception $tgEx) {
                                    Log::error('Lỗi gửi Telegram khi tạo đơn cashback TikTok thực tế: ' . $tgEx->getMessage());
                                }

                                // Gửi email thông báo cho thành viên biết đơn hàng đã được ghi nhận (hàng đợi bất đồng bộ)
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
                                            'platform' => $platform === 'shopee' ? Setting::getVal('shopee_platform_name', 'Shopee') : Setting::getVal('tiktok_platform_name', 'TikTok Shop'),
                                        ]);
                                    }
                                } catch (\Exception $mailEx) {
                                    Log::error('Lỗi gửi Email thông báo khi tạo đơn cashback TikTok thực tế: ' . $mailEx->getMessage());
                                }
                            }
                        }
                    }

                    // 6. Xử lý trạng thái đối soát nếu đơn đang ở trạng thái pending (chờ duyệt)
                    if ($cashback && $cashback->status === 'pending') {
                        // Cập nhật lại thông tin đơn hàng từ API đối soát
                        if ($cashback->order_id !== $orderId) {
                            $cashback->order_id = $orderId;
                        }
                        $cashback->original_price = $originalPrice;
                        $cashback->commission_amount = $realCommission;
                        $cashback->cashback_amount = $cashbackAmount;
                        $cashback->cashback_rate = $cashbackRate;

                        // Tự động bổ sung hình ảnh sản phẩm nếu hình ảnh cũ trong DB đang bị rỗng/lỗi
                        if (empty($cashback->product_image)) {
                            $productImageForUpdate = '';
                            $productId = $order['product_id'] ?? '';
                            if (!empty($productId)) {
                                $tiktokDbId = 'tiktok_' . $productId;
                                $cachedProduct = \App\Models\Product::where('shopee_id', $tiktokDbId)->first();
                                if ($cachedProduct && !empty($cachedProduct->image)) {
                                    $productImageForUpdate = $cachedProduct->image;
                                } else {
                                    // Gọi API đối tác lấy chi tiết sản phẩm và cập nhật cache database
                                    $productImageForUpdate = $this->fetchAndCacheProductInfo($productId, $apiUrl, $apiKey, $creatorUsername) ?: '';
                                }
                            }

                            // Nếu cache chưa có ảnh, và click gốc có chứa ảnh, ta so sánh độ tương quan tên sản phẩm để quyết định lấy ảnh hay không
                            $clickToUpdate = CashbackClick::where('trans_id', $cashback->trans_id)->first();
                            if (empty($productImageForUpdate) && $clickToUpdate && !empty($clickToUpdate->product_image)) {
                                $clickProdName = strtolower(trim($clickToUpdate->product_name));
                                $orderProdName = strtolower(trim($productName));
                                similar_text($clickProdName, $orderProdName, $percent);
                                
                                // Nếu tên sản phẩm tương đồng trên 30% hoặc chứa nhau, ta sử dụng ảnh từ lượt click gốc
                                if ($percent > 30 || str_contains($orderProdName, $clickProdName) || str_contains($clickProdName, $orderProdName)) {
                                    $productImageForUpdate = $clickToUpdate->product_image;
                                }
                            }

                            if (!empty($productImageForUpdate)) {
                                $cashback->product_image = Str::limit($productImageForUpdate, 1000, '');
                            }
                        }

                        $cashback->save();

                        // Trạng thái đơn từ RioHub: 1 = pending, 2 = settled, 3 = cancelled/refunded
                        if ($orderStatus === 3) {
                            // Trường hợp đơn bị hủy trên TikTok Shop
                            $statusSystem = 'rejected';
                            $messageSystem = 'Hệ thống tự động từ chối (Đơn bị hủy trên TikTok Shop).';

                            DB::transaction(function () use ($cashback, $orderId, &$rejectedCount, &$successCount) {
                                $cashback = CashbackHistory::where('id', $cashback->id)->lockForUpdate()->firstOrFail();
                                if ($cashback->status !== 'pending') {
                                    return;
                                }
                                $cashback->order_id = $orderId;
                                $cashback->save();

                                CashbackApprovalService::reject($cashback, 'Đơn hàng bị hủy trên TikTok Shop (Đồng bộ tự động).', [
                                    'source' => 'sync'
                                ]);

                                $rejectedCount++;
                                $successCount++;
                            });
                        } elseif ($orderStatus === 2) {
                            // Trường hợp đơn hàng hợp lệ và đã hoàn thành (settled)
                            $checkProductMatch = Setting::getVal('tiktok_check_product_match', '1') === '1';
                            $autoCashbackFuture = Setting::getVal('tiktok_auto_cashback_future_orders', '0') === '1';

                            $isMatched = true;
                            $rejectReason = '';

                            // 1. Kiểm tra xem có phải là đơn hàng mua sau (tương lai) và có bị tắt cấu hình hoàn tiền tương lai không
                            if (!$autoCashbackFuture) {
                                // Nếu tắt hoàn tiền tương lai, kiểm tra xem đã có đơn hàng nào được tạo trước đó cho click này chưa
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
                                // Nếu không đủ điều kiện duyệt (sai sản phẩm hoặc là đơn mua sau và tắt hoàn tiền tương lai)
                                $statusSystem = 'rejected';
                                $messageSystem = 'Hệ thống tự động từ chối: ' . $rejectReason;

                                DB::transaction(function () use ($cashback, $orderId, $rejectReason, &$rejectedCount, &$successCount) {
                                    $cashback = CashbackHistory::where('id', $cashback->id)->lockForUpdate()->firstOrFail();
                                    if ($cashback->status !== 'pending') {
                                        return;
                                    }
                                    $cashback->order_id = $orderId;
                                    $cashback->save();

                                    CashbackApprovalService::reject($cashback, $rejectReason ?: 'Khách hàng mua sản phẩm không đủ điều kiện hoàn tiền (Đồng bộ tự động TikTok Shop API).', [
                                        'source' => 'sync'
                                    ]);

                                    $rejectedCount++;
                                    $successCount++;
                                });
                            } else {
                                // Đơn hàng hợp lệ và trùng khớp tên sản phẩm -> Tự động duyệt hoàn tiền và MLM
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
                            // Trạng thái chờ đối soát hoặc chưa hoàn thành
                            $statusSystem = 'pending';
                            $messageSystem = 'Đơn hàng đang ở trạng thái Chờ đối soát từ TikTok Shop, tiếp tục chờ duyệt.';
                        }
                    }

                    // Chuyển đổi trạng thái hiển thị thân thiện
                    $statusTikTokVi = __('Chờ đối soát');
                    if ($orderStatus === 2) {
                        $statusTikTokVi = __('Đơn hợp lệ');
                    } elseif ($orderStatus === 3) {
                        $statusTikTokVi = __('Đã hủy');
                    }

                    $details[] = [
                        'order_sn' => $orderId,
                        'purchase_time' => date('Y-m-d H:i:s', $order['create_time'] ?? time()),
                        'product_name' => $productName,
                        'actual_amount' => $originalPrice,
                        'commission' => $realCommission,
                        'cashback_amount' => $cashbackAmount,
                        'status_shopee' => $statusTikTokVi,
                        'status_system' => $statusSystem,
                        'message_system' => $messageSystem,
                        'utm_content' => $subId ?: '-'
                    ];
                }

                // Nếu số lượng đơn hàng trả về nhỏ hơn page size, đồng nghĩa với việc không còn trang tiếp theo
                if (count($orders) < $pageSize) {
                    $hasMore = false;
                } else {
                    $page++;
                }
            }

            // Lưu thời gian đồng bộ thành công gần nhất vào CSDL
            Setting::setVal('tiktok_last_sync_timestamp', $now);

            return [
                'success' => true,
                'total' => $successCount,
                'approved' => $approvedCount,
                'rejected' => $rejectedCount,
                'details' => $details
            ];

        } catch (\Exception $e) {
            Log::error("Đồng bộ TikTok Shop orders lỗi: " . $e->getMessage() . "\n" . $e->getTraceAsString());
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
     * Lấy thông tin sản phẩm từ RioHub API và lưu vào cache database cục bộ.
     * Giải thích: Khi đồng bộ đơn hàng TikTok Shop, nếu sản phẩm chưa từng được người dùng dán link trước đó (ví dụ mua kèm - sản phẩm B),
     * hệ thống sẽ chủ động gọi API chi tiết sản phẩm của RioHub để lấy hình ảnh và tên sản phẩm chính xác, lưu vào bảng products.
     *
     * @param string $productId ID sản phẩm TikTok Shop
     * @param string $apiUrl URL API của RioHub
     * @param string $apiKey API Key kết nối RioHub
     * @param string $creatorUsername Creator Username đăng ký TikTok Shop
     * @return string|null Trả về link ảnh sản phẩm nếu thành công, ngược lại trả về null
     */
    private function fetchAndCacheProductInfo(string $productId, string $apiUrl, string $apiKey, string $creatorUsername): ?string
    {
        try {
            // Ràng buộc bảo mật (Anti-SSRF): Xác thực URL API TikTok không trỏ tới dải IP Private/Loopback nội bộ
            if (!\App\Helpers\SecurityHelper::validateSsfUrl($apiUrl)) {
                Log::error("TikTok Sync - fetchAndCacheProductInfo blocked due to SSRF URL: " . $apiUrl);
                return null;
            }

            Log::info("TikTok Shop Sync - Tiến hành lấy thông tin sản phẩm chưa có cache từ API: " . $productId);
            
            // Gọi API lấy thông tin chi tiết sản phẩm TikTok Shop từ RioHub
            $response = Http::withoutVerifying()
                ->timeout(12)
                ->withHeaders([
                    'X-Riohub-Api-Key' => $apiKey,
                    'Accept' => 'application/json'
                ])
                ->get(rtrim($apiUrl, '/') . '/partner/tiktok/affiliate/products', [
                    'creator_username' => $creatorUsername,
                    'product_id' => $productId
                ]);

            if ($response->successful()) {
                $productJson = $response->json();
                $productsList = $productJson['products'] ?? [];

                if (!empty($productsList)) {
                    $rawProduct = $productsList[0];
                    $name = $rawProduct['title'] ?? 'Sản phẩm TikTok Shop';
                    $image = $rawProduct['main_image_url'] ?? null;
                    
                    // Xử lý tính toán giá bán gốc
                    $originalPrice = (float)($rawProduct['original_price']['minimum_amount'] ?? 0);
                    if ($originalPrice <= 0) {
                        $originalPrice = (float)($rawProduct['sales_price']['minimum_amount'] ?? 0);
                    }

                    // Xử lý lấy thông tin hoa hồng của sản phẩm
                    $amountStr = $rawProduct['commission']['amount'] ?? '0';
                    $commissionAmount = 0.0;
                    if (str_contains($amountStr, '-')) {
                        $parts = explode('-', $amountStr);
                        $min = (float)trim($parts[0]);
                        $max = (float)trim($parts[1]);
                        $commissionAmount = ($min + $max) / 2;
                    } else {
                        $commissionAmount = (float)$amountStr;
                    }

                    $tiktokDbId = 'tiktok_' . $productId;

                    // Tiến hành cập nhật hoặc tạo mới bản ghi cache sản phẩm
                    \App\Models\Product::updateOrCreate(
                        ['shopee_id' => $tiktokDbId],
                        [
                            'name' => Str::limit($name, 250, '...'),
                            'image' => $image,
                            // Chuẩn hoá về số nguyên đồng, riêng với TikTok Shop thì cột shopee_commission lưu số tiền hoa hồng
                            'price' => \App\Helpers\MoneyHelper::round($originalPrice),
                            'commission_amount' => \App\Helpers\MoneyHelper::round($commissionAmount),
                            'shopee_commission' => \App\Helpers\MoneyHelper::round($commissionAmount),
                            'cashback_amount' => 0.0,
                            'cashback_rate' => 0.0,
                            'affiliate_url' => ''
                        ]
                    );

                    Log::info("TikTok Shop Sync - Đã cache thông tin sản phẩm: {$productId} thành công.");
                    return $image;
                }
            } else {
                Log::warning("TikTok Shop Sync - Gọi API chi tiết sản phẩm {$productId} thất bại. HTTP Code: " . $response->status());
            }
        } catch (\Exception $e) {
            Log::error("TikTok Shop Sync - Gặp lỗi khi fetch thông tin sản phẩm {$productId}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Kiểm tra xem tên sản phẩm thực tế mua có khớp/tương đồng với sản phẩm lấy link ban đầu không.
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

        $origWords = array_filter(explode(' ', preg_replace('/[^\w\s]/u', ' ', $originalName)), function($w) {
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
}
