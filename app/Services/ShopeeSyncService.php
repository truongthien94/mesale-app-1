<?php

namespace App\Services;

use App\Models\ShopeeAccount;
use App\Models\CashbackHistory;
use App\Models\CashbackClick;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\BalanceLog;
use App\Models\Setting;
use App\Models\ShortLink;
use App\Services\CashbackApprovalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShopeeSyncService
{
    /**
     * Chuyển đổi mảng cookie JSON (hoặc chuỗi JSON) thành chuỗi Header Cookie HTTP.
     * Giải thích: Shopee API yêu cầu gửi Cookie dưới dạng một chuỗi phân tách bởi dấu chấm phẩy (;).
     * 
     * @param mixed $cookieData
     * @return string
     */
    public function buildCookieString($cookieData): string
    {
        if (is_string($cookieData)) {
            $cookieData = json_decode($cookieData, true);
        }

        if (!is_array($cookieData) || !isset($cookieData['cookies']) || !is_array($cookieData['cookies'])) {
            return '';
        }

        $cookies = [];
        foreach ($cookieData['cookies'] as $cookie) {
            if (isset($cookie['name']) && isset($cookie['value'])) {
                $cookies[] = $cookie['name'] . '=' . $cookie['value'];
            }
        }

        return implode('; ', $cookies);
    }

    /**
     * Trích xuất giá trị cookie cụ thể từ cấu trúc cookie JSON.
     * Giải thích: Dùng để lấy các biến định danh như SPC_U (User ID Shopee) nhằm mục đích lưu thông tin hiển thị.
     * 
     * @param mixed $cookieData
     * @param string $name
     * @return string|null
     */
    public function getCookieValue($cookieData, string $name): ?string
    {
        if (is_string($cookieData)) {
            $cookieData = json_decode($cookieData, true);
        }

        if (!is_array($cookieData) || !isset($cookieData['cookies']) || !is_array($cookieData['cookies'])) {
            return null;
        }

        foreach ($cookieData['cookies'] as $cookie) {
            if (isset($cookie['name']) && $cookie['name'] === $name) {
                return $cookie['value'] ?? null;
            }
        }

        return null;
    }

    /**
     * Kiểm tra tính hợp lệ của Cookie bằng cách gọi thử API báo cáo của Shopee.
     * Giải thích: Tránh trường hợp người dùng nhập cookie hết hạn hoặc sai cấu trúc.
     * 
     * @param string $cookieJson
     * @return array [success => bool, username => string|null, error => string|null]
     */
    public function validateCookie(string $cookieJson): array
    {
        $cookieString = $this->buildCookieString($cookieJson);
        if (empty($cookieString)) {
            return [
                'success' => false,
                'username' => null,
                'error' => 'Định dạng JSON Cookie không đúng cấu trúc J2TEAM Cookies.'
            ];
        }

        // Lấy SPC_U làm username dự phòng
        $spcU = $this->getCookieValue($cookieJson, 'SPC_U') ?: 'shopee_user';

        try {
            // Gọi API báo cáo của Shopee với page_size = 1 để test kết nối nhanh
            $response = Http::withHeaders([
                'Cookie' => $cookieString,
                'Affiliate-Program-Type' => '1',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json, text/plain, */*',
            ])->timeout(10)->get('https://affiliate.shopee.vn/api/v3/report/list', [
                'page_size' => 1,
                'page_num' => 1,
                'purchase_time_s' => time() - 86400,
                'purchase_time_e' => time(),
                'version' => 1
            ]);

            if ($response->successful()) {
                $resData = $response->json();
                if (isset($resData['code']) && $resData['code'] === 0) {
                    // Cố gắng tìm tên hiển thị tài khoản từ dữ liệu đơn hàng nếu có
                    $affName = null;
                    if (isset($resData['data']['list']) && is_array($resData['data']['list']) && count($resData['data']['list']) > 0) {
                        $affName = $resData['data']['list'][0]['affiliate_name'] ?? null;
                    }

                    return [
                        'success' => true,
                        'username' => $affName ?: $spcU,
                        'error' => null
                    ];
                }

                return [
                    'success' => false,
                    'username' => null,
                    'error' => 'Shopee API trả về lỗi: ' . ($resData['msg'] ?? 'Không rõ lý do')
                ];
            }

            return [
                'success' => false,
                'username' => null,
                'error' => 'Không thể kết nối đến API Shopee. Mã lỗi: ' . $response->status()
            ];
        } catch (\Exception $e) {
            Log::error("validateCookie error: " . $e->getMessage());
            return [
                'success' => false,
                'username' => null,
                'error' => 'Lỗi kết nối: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Đồng bộ báo cáo đơn hàng cho một tài khoản Shopee cụ thể.
     * Giải thích: Lọc dữ liệu từ Shopee API và đối chiếu với đơn hàng 'pending' trên hệ thống, tự động duyệt/từ chối.
     * 
     * @param ShopeeAccount $account
     * @param int $days Số ngày gần đây cần quét dữ liệu
     * @return array [success => bool, total => int, approved => int, rejected => int]
     */
    public function syncAccount(ShopeeAccount $account, int $days = 30): array
    {
        // Kiểm tra công tắc bật/tắt trước khi gọi bất kỳ request nào ra ngoài.
        // Nếu Admin đã tắt sàn Shopee hoặc tắt kết nối API thì dừng ngay, tuyệt đối không gọi Shopee API.
        if (Setting::getVal('shopee_status', '1') !== '1') {
            Log::info('Shopee Sync: Bỏ qua vì sàn Shopee đang được tắt (shopee_status = 0).');
            return [
                'success' => false,
                'skipped' => true,
                'total' => 0,
                'approved' => 0,
                'rejected' => 0,
                'details' => [],
                'error' => 'Sàn Shopee đang được tắt trong cấu hình hệ thống.'
            ];
        }

        if (Setting::getVal('apishopee_status', '1') !== '1') {
            Log::info('Shopee Sync: Bỏ qua vì kết nối API Shopee đang được tắt (apishopee_status = 0).');
            return [
                'success' => false,
                'skipped' => true,
                'total' => 0,
                'approved' => 0,
                'rejected' => 0,
                'details' => [],
                'error' => 'Kết nối API Shopee đang được tắt trong cấu hình hệ thống.'
            ];
        }

        // Lưu trữ trạng thái tài khoản trước khi đồng bộ để kiểm tra sự thay đổi trạng thái lỗi cookie
        $oldStatus = $account->status;

        $cookieString = $this->buildCookieString($account->cookie);
        if (empty($cookieString)) {
            $account->update([
                'status' => 'expired',
                'error_message' => 'Cookie không đúng định dạng.'
            ]);

            // Tránh việc spam thông báo Telegram liên tục mỗi chu kỳ cron job chạy.
            // Chỉ gửi thông báo khi tài khoản đang hoạt động bình thường bỗng dưng bị lỗi/hết hạn.
            if ($oldStatus !== 'expired') {
                try {
                    Setting::sendTelegramTemplate('telegram_template_shopee_cookie_expired', [
                        'site_name' => Setting::getVal('site_name', 'Hoàn Tiền Shopee'),
                        'account_name' => $account->name,
                        'username' => $account->username,
                        'error_message' => 'Cấu trúc Cookie không đúng định dạng.'
                    ]);
                } catch (\Exception $tgEx) {
                    Log::error("Lỗi gửi Telegram báo lỗi cấu trúc cookie: " . $tgEx->getMessage());
                }
            }

            return [
                'success' => false,
                'total' => 0,
                'approved' => 0,
                'rejected' => 0,
                'details' => []
            ];
        }

        $now = time();
        $start = $now - ($days * 24 * 3600);

        $page = 1;
        $pageSize = 50;
        $hasMore = true;

        $successCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;
        $details = []; // Mảng lưu trữ chi tiết các đơn hàng quét được từ Shopee API

        // Lấy tỷ lệ chia cashback hệ thống để tính toán lại tiền hoàn (mặc định 70%)
        $cashbackSystemRate = (float)Setting::getVal('shopee_cashback_rate', 50);

        try {
            while ($hasMore) {
                $response = Http::withHeaders([
                    'Cookie' => $cookieString,
                    'Affiliate-Program-Type' => '1',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json, text/plain, */*',
                ])->timeout(15)->get('https://affiliate.shopee.vn/api/v3/report/list', [
                    'page_size' => $pageSize,
                    'page_num' => $page,
                    'purchase_time_s' => $start,
                    'purchase_time_e' => $now,
                    'version' => 1
                ]);

                if (!$response->successful()) {
                    throw new \Exception('Gọi Shopee API thất bại. HTTP Code: ' . $response->status());
                }

                $resData = $response->json();
                if (!isset($resData['code']) || $resData['code'] !== 0) {
                    throw new \Exception('Shopee API lỗi: ' . ($resData['msg'] ?? 'Không rõ lý do'));
                }

                $list = $resData['data']['list'] ?? [];
                if (empty($list)) {
                    $hasMore = false;
                    break;
                }

                // Sắp xếp các giao dịch theo thời gian mua (purchase_time) tăng dần để giao dịch mua trước luôn được xử lý trước
                uasort($list, function ($a, $b) {
                    $timeA = (int)($a['purchase_time'] ?? 0);
                    $timeB = (int)($b['purchase_time'] ?? 0);
                    return $timeA <=> $timeB;
                });

                foreach ($list as $item) {
                    $checkoutId = $item['checkout_id'] ?? '';
                    $conversionStatus = (int)($item['conversion_status'] ?? 0); // Trạng thái chuyển đổi từ API Shopee (3 = Đã hủy, 2 = Đơn hợp lệ)
                    $utmContent = explode('-', (string)($item['utm_content'] ?? ''))[0]; // Mã giao dịch đối soát của hệ thống web
                    $utmSource = explode('-', (string)($item['utm_source'] ?? ''))[0]; // Thu thập utm_source từ Shopee API
                    $subId1 = explode('-', (string)($item['sub_id1'] ?? $item['subid1'] ?? ''))[0]; // Thu thập sub_id1 từ Shopee API
                    $checkinUtmSource = strtolower(trim(Setting::getVal('checkin_redirect_utm_source', 'diemdanh')));

                    if (empty($checkoutId)) {
                        continue;
                    }

                    $subOrders = $item['orders'] ?? [];
                    foreach ($subOrders as $subOrder) {
                        $orderSn = $subOrder['order_sn'] ?? '';
                        $orderStatus = $subOrder['order_status'] ?? ''; // COMPLETED, CANCELLED, ...

                        if (empty($orderSn)) {
                            continue;
                        }

                        // Lấy thông tin chi tiết các sản phẩm trong đơn hàng
                        $productNames = [];
                        $shopNames = [];
                        $fraudReasons = [];
                        // Lấy tổng hoa hồng đã áp trần của Shopee ở cấp độ checkout (nằm ngoài items và orders)
                        $realCommission = (float)($item['affiliate_net_commission'] ?? 0);
                        $realPrice = 0;
                        $isFraud = false;
                        if (isset($subOrder['items']) && is_array($subOrder['items'])) {
                            foreach ($subOrder['items'] as $orderItem) {
                                $productNames[] = $orderItem['item_name'] ?? 'Sản phẩm Shopee';
                                $realPrice += ($orderItem['actual_amount'] ?? 0);
                                if (isset($orderItem['is_fraud']) && $orderItem['is_fraud'] == 1) {
                                    $isFraud = true;
                                }
                                if (!empty($orderItem['shop_name'])) {
                                    $shopNames[] = $orderItem['shop_name'];
                                }
                                if (!empty($orderItem['fraud_reason'])) {
                                    $fraudReasons[] = $orderItem['fraud_reason'];
                                }
                            }
                        }

                        $productName = count($productNames) > 0 ? implode(', ', $productNames) : 'Không có tên sản phẩm';
                        $shopName = count($shopNames) > 0 ? implode(', ', array_unique($shopNames)) : null;
                        $fraudReason = count($fraudReasons) > 0 ? implode('; ', array_unique($fraudReasons)) : null;
                        // Chia cho 100000 vì Shopee nhân 10^5 cho tiền tệ trong API
                        $realCommission = (float)($realCommission / 100000);
                        $realPrice = (float)($realPrice / 100000);

                        // Tính toán cashback thực tế dự kiến dựa trên hoa hồng thực nhận từ Shopee để hiển thị đối soát
                        // Chuẩn hoá về số nguyên đồng vì đồng Việt Nam không có đơn vị nhỏ hơn 1 đồng
                        $cashbackAmount = \App\Helpers\MoneyHelper::round($realCommission * ($cashbackSystemRate / 100));

                        // Quy tắc nghiệp vụ: Nếu utm_source, utm_content hoặc sub_id1 bắt đầu bằng giá trị cấu hình của checkin_redirect_utm_source,
                        // đây là đơn hàng ghi nhận doanh thu và lợi nhuận từ chức năng Điểm danh (không phải hoàn tiền cho thành viên).
                        // Chúng tôi sẽ lưu thông tin đơn này vào bảng riêng là checkin_revenues và tiếp tục xử lý các đơn hàng khác.
                        $isCheckinOrder = (
                            str_starts_with(strtolower(trim($utmSource)), $checkinUtmSource) || 
                            str_starts_with(strtolower(trim($utmContent)), $checkinUtmSource) || 
                            str_starts_with(strtolower(trim($subId1)), $checkinUtmSource)
                        );
                        if ($isCheckinOrder) {
                            $statusRevenue = 'pending';
                            if ($conversionStatus === 2) {
                                $statusRevenue = 'approved';
                            } elseif ($conversionStatus === 3 || $isFraud) {
                                $statusRevenue = 'rejected';
                            }

                            \App\Models\CheckinRevenue::updateOrCreate(
                                ['order_id' => $orderSn],
                                [
                                    'product_name' => Str::limit($productName, 500, '...'),
                                    'original_price' => $realPrice,
                                    'commission_amount' => $realCommission,
                                    'status' => $statusRevenue,
                                    'shop_name' => Str::limit($shopName, 250, ''),
                                    'fraud_reason' => Str::limit($fraudReason, 250, ''),
                                ]
                            );

                            $statusSystem = $statusRevenue;
                            $messageSystem = 'Đã ghi nhận doanh thu điểm danh vào bảng checkin_revenues (Trạng thái: ' . $statusRevenue . ').';

                            $details[] = [
                                'order_sn' => $orderSn,
                                'purchase_time' => date('Y-m-d H:i:s', $item['purchase_time'] ?? time()),
                                'product_name' => $productName,
                                'actual_amount' => $realPrice,
                                'commission' => $realCommission,
                                'cashback_amount' => 0,
                                'status_shopee' => $conversionStatus === 2 ? __('Đơn hợp lệ') : ($conversionStatus === 3 ? __('Đã hủy') : __('Chờ đối soát')),
                                'status_system' => $statusSystem,
                                'message_system' => $messageSystem,
                                'utm_content' => $utmContent ?: $subId1 ?: '-'
                            ];

                            $successCount++;
                            continue;
                        }

                        // Quy tắc nghiệp vụ: Nếu utm_source, utm_content hoặc sub_id1 bắt đầu bằng giá trị cấu hình của coupon_utm_source,
                        // đây là đơn hàng ghi nhận doanh thu và lợi nhuận từ chức năng Mã giảm giá.
                        // Chúng tôi sẽ lưu thông tin đơn này vào bảng riêng là coupon_revenues và tiếp tục xử lý các đơn hàng khác.
                        $couponUtmSource = strtolower(trim(Setting::getVal('coupon_utm_source', 'magiamgia')));
                        $isCouponOrder = (
                            str_starts_with(strtolower(trim($utmSource)), $couponUtmSource) || 
                            str_starts_with(strtolower(trim($utmContent)), $couponUtmSource) || 
                            str_starts_with(strtolower(trim($subId1)), $couponUtmSource)
                        );
                        if ($isCouponOrder) {
                            $statusRevenue = 'pending';
                            if ($conversionStatus === 2) {
                                $statusRevenue = 'approved';
                            } elseif ($conversionStatus === 3 || $isFraud) {
                                $statusRevenue = 'rejected';
                            }

                            \App\Models\CouponRevenue::updateOrCreate(
                                ['order_id' => $orderSn],
                                [
                                    'product_name' => Str::limit($productName, 500, '...'),
                                    'original_price' => $realPrice,
                                    'commission_amount' => $realCommission,
                                    'status' => $statusRevenue,
                                    'shop_name' => Str::limit($shopName, 250, ''),
                                    'fraud_reason' => Str::limit($fraudReason, 250, ''),
                                ]
                            );

                            $statusSystem = $statusRevenue;
                            $messageSystem = 'Đã ghi nhận doanh thu mã giảm giá vào bảng coupon_revenues (Trạng thái: ' . $statusRevenue . ').';

                            $details[] = [
                                'order_sn' => $orderSn,
                                'purchase_time' => date('Y-m-d H:i:s', $item['purchase_time'] ?? time()),
                                'product_name' => $productName,
                                'actual_amount' => $realPrice,
                                'commission' => $realCommission,
                                'cashback_amount' => 0,
                                'status_shopee' => $conversionStatus === 2 ? __('Đơn hợp lệ') : ($conversionStatus === 3 ? __('Đã hủy') : __('Chờ đối soát')),
                                'status_system' => $statusSystem,
                                'message_system' => $messageSystem,
                                'utm_content' => $utmContent ?: $subId1 ?: '-'
                            ];

                            $successCount++;
                            continue;
                        }

                        // Khởi tạo trạng thái đối soát hệ thống mặc định cho đơn hàng này
                        $statusSystem = 'ignored';
                        $messageSystem = 'Bỏ qua (Không tìm thấy mã đơn hàng Shopee hoặc mã giao dịch nội bộ trong CSDL).';

                        // Thu thập tất cả các trường có khả năng chứa mã đối soát giao dịch (trans_id) từ API Shopee.
                        // Tại sao: Shopee có thể trả về tham số sub_id (trans_id) ở các cột khác nhau (utm_source, utm_content, sub_id1)
                        // tùy thuộc vào nền tảng thiết bị (ví dụ: trên iOS/Shopee App thường bị map sang utm_source hoặc sub_id1).
                        $possibleTransIds = array_unique(array_filter([$utmContent, $utmSource, $subId1]));
                        // Loại bỏ các giá trị bắt đầu bằng nguồn doanh thu hệ thống (điểm danh, mã giảm giá)
                        $possibleTransIds = array_filter($possibleTransIds, function($val) use ($checkinUtmSource, $couponUtmSource) {
                            $lowerVal = strtolower(trim($val));
                            return !str_starts_with($lowerVal, $checkinUtmSource) && !str_starts_with($lowerVal, $couponUtmSource);
                        });

                        // Quy tắc nghiệp vụ: Tìm kiếm bản ghi CashbackHistory dựa trên mã đơn hàng Shopee (order_sn) thực tế
                        $cashback = CashbackHistory::where('order_id', $orderSn)->first();

                        // Nếu chưa có đơn hàng theo order_sn, thử tìm kiếm CashbackHistory theo mã giao dịch trans_id từ các trường trả về
                        // Quy tắc nghiệp vụ: Chỉ được phép liên kết (map) với bản ghi chưa được gán mã đơn hàng thực tế khác
                        // (tức là order_id rỗng, null hoặc trùng khớp với trans_id để tránh ghi đè dữ liệu của đơn hàng khác)
                        if (!$cashback) {
                            foreach ($possibleTransIds as $possibleId) {
                                $cashback = CashbackHistory::where('trans_id', $possibleId)
                                    ->where(function ($query) {
                                        $query->whereNull('order_id')
                                            ->orWhere('order_id', '');
                                    })
                                    ->first();
                                if ($cashback) {
                                    $matchedTransId = $possibleId;
                                    break;
                                }
                            }
                        }

                        if ($cashback) {
                            if ($cashback->status !== 'pending') {
                                $statusSystem = 'already_processed';
                                $messageSystem = 'Đơn hàng này đã được xử lý từ trước (Trạng thái trên web: ' . ($cashback->status === 'approved' ? 'Đã duyệt' : ($cashback->status === 'rejected' ? 'Từ chối/Hủy' : $cashback->status)) . ').';

                                // TỰ ĐỘNG THU HỒI HOÀN TIỀN (AUTO-CLAWBACK)
                                // Nếu đơn hàng đã duyệt trước đó trên web, nhưng Shopee API báo đơn bị hủy (conversion_status = 3) hoặc bị đánh giá gian lận (is_fraud = true)
                                if ($cashback->status === 'approved' && ($conversionStatus === 3 || $isFraud)) {
                                    $statusSystem = 'recalled';
                                    $reasonRecall = $isFraud 
                                        ? 'Thu hồi hoàn tiền tự động: Đơn hàng bị Shopee đánh giá gian lận (Fraud/Spam) sau khi hoàn thành.'
                                        : 'Thu hồi hoàn tiền tự động: Đơn hàng bị hủy/hoàn trả trên sàn Shopee sau khi hoàn thành.';

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
                            // Quy tắc nghiệp vụ: Nếu hoàn toàn chưa có đơn hàng CashbackHistory trong hệ thống,
                            // tiến hành tìm lượt click gốc từ bảng cashback_clicks bằng cách quét qua danh sách trans_id tiềm năng.
                            $click = null;
                            $matchedTransId = null;
                            foreach ($possibleTransIds as $possibleId) {
                                $click = CashbackClick::where('trans_id', $possibleId)->first();
                                if ($click) {
                                    $matchedTransId = $possibleId;
                                    break;
                                }
                            }

                            // Quy tắc nghiệp vụ bổ sung: Nếu không tìm thấy click trong bảng cashback_clicks,
                            // ta tiếp tục tìm kiếm trong bảng short_links (liên kết rút gọn).
                            // Lý do: Người dùng có thể tạo link rút gọn rồi mang đi chia sẻ cho người khác mua hộ trực tiếp, 
                            // khi đó hệ thống chỉ lưu log click chung chứ không ghi nhận vào cashback_clicks (vốn chỉ tạo khi user tự phân tích link trên web).
                            // Hoặc logs trong cashback_clicks đã bị dọn dẹp theo thời gian nhưng bảng short_links vẫn lưu vết đích đến chứa sub_id/trans_id.
                            $shortLink = null;
                            if (!$click) {
                                foreach ($possibleTransIds as $possibleId) {
                                    $shortLink = ShortLink::where('destination_url', 'like', '%' . $possibleId . '%')->first();
                                    if ($shortLink) {
                                        $matchedTransId = $possibleId;
                                        break;
                                    }
                                }
                            }

                            // Nếu tìm thấy lượt click hoặc link rút gọn hợp lệ khớp với mã đối soát
                            if ($click || $shortLink) {
                                $userId = $click ? $click->user_id : $shortLink->user_id;
                                $platform = $click ? ($click->platform ?? 'shopee') : 'shopee';
                                $transIdToUse = $matchedTransId;
                                
                                $productImage = '';
                                if ($click && !empty($click->product_image)) {
                                    if ($this->isProductMatched($click->product_name, $productName)) {
                                        $productImage = $click->product_image;
                                    }
                                }

                                $affiliateUrl = $click ? $click->affiliate_url : $shortLink->destination_url;

                                // Chỉ tiến hành tạo đơn hàng chờ duyệt nếu xác định được thành viên sở hữu hợp lệ
                                if ($userId) {
                                    // Chặn trên giá trị tỷ lệ để không vượt ngưỡng lưu trữ của cột decimal(5,2)
                                    $cashbackRate = \App\Helpers\CashbackHelper::clampRate($realPrice > 0 ? ($cashbackAmount / $realPrice) * 100 : 0);

                                    // Tạo bản ghi đơn hàng mới ở trạng thái chờ duyệt (pending)
                                    // Tăng giới hạn lưu trữ ảnh sản phẩm lên 1000 ký tự để không bị cắt cụt đường dẫn CDN
                                        $cashback = CashbackHistory::create([
                                            'user_id' => $userId,
                                            'platform' => $platform,
                                            'order_id' => $orderSn,
                                            'trans_id' => $transIdToUse,
                                            'product_name' => Str::limit($productName, 500, '...'),
                                            'product_image' => Str::limit(isset($subOrder['items'][0]['img_code']) ? 'https://cf.shopee.vn/file/' . $subOrder['items'][0]['img_code'] : $productImage, 1000, ''),
                                            'original_price' => $realPrice,
                                            'cashback_amount' => \App\Helpers\MoneyHelper::round($cashbackAmount),
                                            'cashback_rate' => round($cashbackRate, 2),
                                            'commission_amount' => $realCommission,
                                            'affiliate_url' => $affiliateUrl,
                                            'status' => 'pending',
                                            'shop_name' => Str::limit($shopName, 250, ''),
                                            'fraud_reason' => Str::limit($fraudReason, 250, ''),
                                        ]);

                                        // Gửi thông báo Telegram khi có đơn hàng cashback mới thực tế được ghi nhận
                                        try {
                                            $user = User::find($userId);
                                            if ($user) {
                                                \App\Models\Setting::sendTelegramTemplate('telegram_template_cashback_created', [
                                                    'name' => $user->name,
                                                    'email' => $user->email,
                                                    'product_name' => $productName,
                                                    'price' => number_format($realPrice),
                                                    'cashback_amount' => number_format($cashbackAmount),
                                                    'commission' => number_format($realCommission),
                                                    'profit' => number_format($realCommission - $cashbackAmount),
                                                    'platform' => $platform === 'tiktok' ? Setting::getVal('tiktok_platform_name', 'TikTok Shop') : Setting::getVal('shopee_platform_name', 'Shopee'),
                                                ]);
                                            }
                                        } catch (\Exception $e) {
                                            Log::error('Lỗi gửi Telegram khi tạo đơn cashback thực tế: ' . $e->getMessage());
                                        }

                                        // Gửi email thông báo cho thành viên biết đơn hàng đã được ghi nhận (sử dụng hàng đợi)
                                        try {
                                            $user = $user ?? User::find($userId);
                                            if ($user && !empty($user->email)) {
                                                \App\Models\Setting::sendEmailQueue($user->email, 'cashback_created', [
                                                    'name' => $user->name,
                                                    'email' => $user->email,
                                                    'order_id' => $orderSn,
                                                    'product_name' => $productName,
                                                    'price' => number_format($realPrice),
                                                    'cashback_amount' => number_format($cashbackAmount),
                                                    'platform' => $platform === 'tiktok' ? Setting::getVal('tiktok_platform_name', 'TikTok Shop') : Setting::getVal('shopee_platform_name', 'Shopee'),
                                                ]);
                                            }
                                        } catch (\Exception $e) {
                                            Log::error('Lỗi gửi Email thông báo khi tạo đơn cashback thực tế: ' . $e->getMessage());
                                        }
                                    }
                                }
                            }

                        if ($cashback && $cashback->status === 'pending') {
                            // Cập nhật thông tin shop_name và lý do gian lận từ Shopee nếu có
                            if (!empty($shopName)) {
                                $cashback->shop_name = Str::limit($shopName, 250, '');
                            }
                            if (!empty($fraudReason)) {
                                $cashback->fraud_reason = Str::limit($fraudReason, 250, '');
                            }
                            
                            // Nếu mã đơn hàng hiện tại đang là trans_id hoặc khác orderSn thực tế, cập nhật lại sang orderSn
                            if ($cashback->order_id !== $orderSn) {
                                $cashback->order_id = $orderSn;
                            }
                            
                            // Cập nhật lại hoa hồng và tiền hoàn thực tế của đơn hàng từ API Shopee
                            $cashback->original_price = $realPrice;
                            $cashback->commission_amount = $realCommission;
                            $cashback->cashback_amount = $cashbackAmount;
                            // Chặn trên giá trị tỷ lệ để không vượt ngưỡng lưu trữ của cột decimal(5,2)
                            $cashback->cashback_rate = \App\Helpers\CashbackHelper::clampRate($realPrice > 0 ? ($cashbackAmount / $realPrice) * 100 : 0);
                            
                            $cashback->save();

                                // TRƯỜNG HỢP 1: ĐƠN HÀNG BỊ HỦY / KHÔNG HỢP LỆ TRÊN SHOPEE (conversion_status = 3)
                                if ($conversionStatus === 3) {
                                    $statusSystem = 'rejected';
                                    $messageSystem = 'Hệ thống đã tự động từ chối hoàn tiền (Đơn hàng bị hủy trên Shopee).';

                                    DB::transaction(function () use ($cashback, $orderSn, &$rejectedCount, &$successCount) {
                                        // Khóa dòng để chống race condition
                                        $cashback = CashbackHistory::where('id', $cashback->id)->lockForUpdate()->firstOrFail();
                                        if ($cashback->status !== 'pending') {
                                            return;
                                        }

                                        $cashback->order_id = $orderSn;
                                        $cashback->save();

                                        CashbackApprovalService::reject($cashback, 'Đơn hàng bị hủy trên Shopee (Đồng bộ tự động).', [
                                            'source' => 'sync'
                                        ]);

                                        $rejectedCount++;
                                        $successCount++;
                                    });
                                }
                                // TRƯỜNG HỢP 2: ĐƠN HÀNG PHÁT SINH GIAN LẬN (FRAUD)
                                elseif ($isFraud) {
                                    $statusSystem = 'rejected';
                                    $messageSystem = 'Hệ thống đã tự động từ chối hoàn tiền (Shopee đánh giá đơn hàng gian lận/Fraud).';

                                    DB::transaction(function () use ($cashback, $orderSn, &$rejectedCount, &$successCount) {
                                        $cashback = CashbackHistory::where('id', $cashback->id)->lockForUpdate()->firstOrFail();
                                        if ($cashback->status !== 'pending') {
                                            return;
                                        }

                                        $cashback->order_id = $orderSn;
                                        $cashback->save();

                                        CashbackApprovalService::reject($cashback, 'Đơn hàng bị Shopee đánh giá gian lận (Fraud/Spam) nên không có hoa hồng.', [
                                            'source' => 'sync'
                                        ]);

                                        $rejectedCount++;
                                        $successCount++;
                                    });
                                }
                                // TRƯỜNG HỢP 3: ĐƠN HÀNG HOÀN THÀNH / HỢP LỆ (conversion_status = 2)
                                elseif ($conversionStatus === 2) {
                                    // Kiểm tra cấu hình hệ thống
                                    $checkProductMatch = Setting::getVal('shopee_check_product_match', '1') === '1';
                                    $autoCashbackFuture = Setting::getVal('shopee_auto_cashback_future_orders', '0') === '1';

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

                                    // 2. Kiểm tra tính tương thích/trùng khớp của sản phẩm thực tế mua với sản phẩm lấy link
                                    if ($isMatched && $checkProductMatch && isset($subOrder['items']) && is_array($subOrder['items'])) {
                                        $hasMatchedItem = false;
                                        foreach ($subOrder['items'] as $orderItem) {
                                            $actualItemName = $orderItem['item_name'] ?? '';
                                            if ($this->isProductMatched($cashback->product_name, $actualItemName)) {
                                                $hasMatchedItem = true;
                                                break;
                                            }
                                        }
                                        if (!$hasMatchedItem) {
                                            $isMatched = false;
                                            $rejectReason = 'Khách hàng mua sản phẩm khác so với link đã lấy.';
                                        }
                                    }

                                    if (!$isMatched) {
                                        $statusSystem = 'rejected';
                                        $messageSystem = 'Hệ thống tự động từ chối: ' . $rejectReason;

                                        DB::transaction(function () use ($cashback, $orderSn, $rejectReason, &$rejectedCount, &$successCount) {
                                            $cashback = CashbackHistory::where('id', $cashback->id)->lockForUpdate()->firstOrFail();
                                            if ($cashback->status !== 'pending') {
                                                return;
                                            }

                                            $cashback->order_id = $orderSn;
                                            $cashback->save();

                                            CashbackApprovalService::reject($cashback, $rejectReason ?: 'Khách hàng mua sản phẩm không đủ điều kiện hoàn tiền (Đồng bộ tự động Shopee API).', [
                                                'source' => 'sync'
                                            ]);

                                            $rejectedCount++;
                                            $successCount++;
                                        });
                                    } else {
                                        $statusSystem = 'approved';
                                        $messageSystem = 'Hệ thống đã tự động duyệt hoàn tiền thành công.';

                                        DB::transaction(function () use ($cashback, $realCommission, $realPrice, $orderSn, &$approvedCount, &$successCount) {
                                            $cashback = CashbackHistory::where('id', $cashback->id)->lockForUpdate()->firstOrFail();
                                            if ($cashback->status !== 'pending') {
                                                return;
                                            }

                                            $cashback->order_id = $orderSn;
                                            $cashback->save();

                                            CashbackApprovalService::approve($cashback, $realPrice, $realCommission, [
                                                'source' => 'sync'
                                            ]);

                                            $approvedCount++;
                                            $successCount++;
                                        });
                                    }
                                } else {
                                    $statusSystem = 'pending';
                                    $messageSystem = 'Đơn hàng đang ở trạng thái khác trên Shopee (conversion_status: ' . $conversionStatus . '), tiếp tục chờ đối soát.';
                                }
                            }

                        // Dịch trạng thái Shopee từ API sang tiếng Việt thân thiện để hiển thị trên giao diện quản trị dựa trên conversion_status
                        $statusShopeeVi = __('Chờ đối soát');
                        if ($conversionStatus === 2) {
                            $statusShopeeVi = __('Đơn hợp lệ');
                        } elseif ($conversionStatus === 3) {
                            $statusShopeeVi = __('Đã hủy');
                        }

                        // Lưu trữ chi tiết đơn hàng đối soát (thêm cột cashback_amount để hiển thị tiền hoàn khách)
                        $details[] = [
                            'order_sn' => $orderSn,
                            'purchase_time' => date('Y-m-d H:i:s', $item['purchase_time'] ?? time()),
                            'product_name' => $productName,
                            'actual_amount' => $realPrice,
                            'commission' => $realCommission,
                            'cashback_amount' => $cashbackAmount,
                            'status_shopee' => $statusShopeeVi,
                            'status_system' => $statusSystem,
                            'message_system' => $messageSystem,
                            'utm_content' => $utmContent ?: '-' // Hiển thị trực tiếp utm_content (sub_id mã đối soát) từ API Shopee
                        ];
                    }
                }

                // Kiểm tra phân trang để lấy tiếp dữ liệu
                $totalCount = $resData['data']['total_count'] ?? 0;
                if ($page * $pageSize >= $totalCount) {
                    $hasMore = false;
                } else {
                    $page++;
                }
            }

            // Đồng bộ thành công, cập nhật trạng thái tài khoản
            $account->update([
                'status' => 'active',
                'error_message' => null,
                'last_sync_at' => now(),
            ]);

            // Sắp xếp danh sách đơn hàng quét được theo thời gian mua mới nhất lên trước để admin dễ dàng theo dõi trên giao diện
            usort($details, function ($a, $b) {
                return strcmp($b['purchase_time'], $a['purchase_time']);
            });

            return [
                'success' => true,
                'total' => $successCount,
                'approved' => $approvedCount,
                'rejected' => $rejectedCount,
                'details' => $details
            ];

        } catch (\Exception $e) {
            Log::error("Đồng bộ Shopee Account ID {$account->id} lỗi: " . $e->getMessage());
            $account->update([
                'status' => 'expired',
                'error_message' => 'Lỗi đồng bộ: ' . $e->getMessage()
            ]);

            // Gửi thông báo tự động về Telegram khi xảy ra lỗi kết nối/cookie bị hết hạn.
            // Chỉ thông báo một lần khi chuyển từ trạng thái hoạt động bình thường sang trạng thái lỗi.
            if ($oldStatus !== 'expired') {
                try {
                    Setting::sendTelegramTemplate('telegram_template_shopee_cookie_expired', [
                        'site_name' => Setting::getVal('site_name', 'Hoàn Tiền Shopee'),
                        'account_name' => $account->name,
                        'username' => $account->username,
                        'error_message' => 'Lỗi đồng bộ: ' . $e->getMessage()
                    ]);
                } catch (\Exception $tgEx) {
                    Log::error("Lỗi gửi Telegram báo lỗi đồng bộ cookie: " . $tgEx->getMessage());
                }
            }

            // Sắp xếp danh sách đơn hàng quét được theo thời gian mua mới nhất lên trước ngay cả khi xảy ra lỗi nửa chừng
            usort($details, function ($a, $b) {
                return strcmp($b['purchase_time'], $a['purchase_time']);
            });

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
     * Kiểm tra xem tên sản phẩm thực tế mua có khớp/tương đồng với sản phẩm lấy link ban đầu không.
     * Quy tắc: 
     * 1. Chữ thường, bỏ dấu và ký tự đặc biệt, trim khoảng trắng.
     * 2. Nếu chứa tên nhau -> Khớp ngay lập tức.
     * 3. Tách từ khóa trong tên gốc, nếu trùng khớp >= 40% số từ khóa -> Khớp (đáp ứng viết tắt/mô tả Shopee thay đổi).
     */
    private function isProductMatched(string $originalName, string $actualName): bool
    {
        $originalName = mb_strtolower(trim($originalName));
        $actualName = mb_strtolower(trim($actualName));

        if (empty($originalName) || empty($actualName)) {
            return false;
        }

        // 1. Trường hợp trùng khớp hoàn toàn hoặc chứa tên nhau
        if (str_contains($actualName, $originalName) || str_contains($originalName, $actualName)) {
            return true;
        }

        // 2. Tách từ khóa của sản phẩm gốc (chỉ lấy từ >= 2 ký tự)
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

        // Tính tỷ lệ trùng khớp từ khóa
        $matchRate = $matchedCount / count($origWords);

        // Ngưỡng an toàn: trùng >= 40% từ khóa thì coi như khớp
        return $matchRate >= 0.40;
    }
}
