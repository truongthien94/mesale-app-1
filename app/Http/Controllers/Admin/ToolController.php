<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashbackHistory;
use App\Services\ReferralCommissionService;
use App\Services\CashbackApprovalService;
use App\Models\Setting;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\BalanceLog;
use App\Models\Notification;
use App\Models\ShopeeAccount;
use App\Models\CashbackClick;
use App\Models\ShortLink;
use App\Services\ShopeeSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class ToolController extends Controller
{
    /**
     * Hiển thị trang quản lý Công cụ trong Admin Panel.
     * Kiểm tra phân quyền truy cập thông qua middleware 'permission:manage_tools'.
     */
    public function index()
    {
        $shopeeAccounts = ShopeeAccount::orderBy('created_at', 'desc')->get();
        return view('admin.tools.index', compact('shopeeAccounts'));
    }

    /**
     * Xử lý file báo cáo hoa hồng Shopee (.csv) được upload lên để đối soát.
     * Cập nhật trạng thái đơn hàng:
     * - Trạng thái đặt hàng chứa "Hủy" hoặc "Cancel" -> Từ chối đơn hoàn tiền.
     * - Trạng thái đặt hàng chứa "Hoàn thành", "Thanh toán", "Đối soát" hoặc "Completed" -> Duyệt đơn hoàn tiền & Chia hoa hồng MLM.
     */
    public function importCommission(Request $request)
    {
        // Chặn nhập file đối soát hoa hồng ở chế độ Demo để bảo vệ dữ liệu tài chính của hệ thống
        if (config('app.demo')) {
            return back()->with('error', __('Tính năng đối soát hoa hồng Shopee bị khóa ở chế độ Demo.'));
        }

        // 1. Xác thực file tải lên
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Giới hạn 10MB
        ], [
            'csv_file.required' => 'Vui lòng chọn file báo cáo đối soát (.csv) cần tải lên.',
            'csv_file.mimes' => 'Định dạng file không hợp lệ. Chỉ hỗ trợ file .csv hoặc .txt.',
            'csv_file.max' => 'Dung lượng file vượt quá giới hạn cho phép (tối đa 10MB).'
        ]);

        try {
            $file = $request->file('csv_file');
            $path = $file->getRealPath();
            $handle = fopen($path, 'r');

            if (!$handle) {
                return back()->with('error', 'Không thể mở và đọc dữ liệu từ tệp tin đã chọn.');
            }

            // 2. Đọc dòng tiêu đề (Header)
            $header = fgetcsv($handle, 10000, ",");
            if (!$header) {
                fclose($handle);
                return back()->with('error', 'Tệp tin trống hoặc không có dòng tiêu đề hợp lệ.');
            }

            // Loại bỏ ký tự BOM (Byte Order Mark) nếu có ở đầu tiêu đề UTF-8
            $header[0] = preg_replace('/^[\xFF\xFE\xEF\xBB\xBF]+/', '', $header[0]);

            // Chuyển mảng tiêu đề thành dạng key => index để tra cứu dễ dàng
            $columnsMap = array_flip($header);

            // Xác định vị trí các cột quan trọng trong file CSV của Shopee
            $orderIdCol = $columnsMap['ID đơn hàng'] ?? null;
            $statusCol = $columnsMap['Trạng thái đặt hàng'] ?? null;
            $commissionCol = $columnsMap['Tổng hoa hồng đơn hàng(₫)'] ?? $columnsMap['Tổng hoa hồng sản phẩm(₫)'] ?? null;
            $priceCol = $columnsMap['Giá trị đơn hàng (₫)'] ?? $columnsMap['Giá(₫)'] ?? null; // Cột chứa giá gốc sản phẩm
            $subId1Col = $columnsMap['Sub_id1'] ?? $columnsMap['sub_id1'] ?? $columnsMap['sub_id'] ?? null; // Cột chứa mã giao dịch nội bộ
            $utmSourceCol = $columnsMap['utm_source'] ?? $columnsMap['Utm_source'] ?? $columnsMap['Sub_id2'] ?? $columnsMap['sub_id2'] ?? null; // Cột chứa UTM Source
            $productNameCol = $columnsMap['Tên sản phẩm'] ?? $columnsMap['Tên mặt hàng'] ?? $columnsMap['Sản phẩm'] ?? $columnsMap['Product Name'] ?? $columnsMap['Item Name'] ?? null;
            $shopNameCol = $columnsMap['Tên Shop'] ?? $columnsMap['Tên shop'] ?? $columnsMap['Shop Name'] ?? $columnsMap['shop_name'] ?? null; // Cột chứa tên shop bán hàng

            if ($orderIdCol === null || $statusCol === null) {
                fclose($handle);
                return back()->with('error', 'Cấu trúc file CSV không hợp lệ. Không tìm thấy các cột bắt buộc: "ID đơn hàng" và "Trạng thái đặt hàng".');
            }

            $successCount = 0;
            $approvedCount = 0;
            $rejectedCount = 0;
            $ignoredCount = 0;
            $details = []; // Mảng lưu trữ kết quả xử lý chi tiết từng đơn hàng

            // Lấy tỷ lệ chia cashback hệ thống để tính toán lại tiền hoàn (mặc định 70%)
            $cashbackSystemRate = (float)Setting::getVal('shopee_cashback_rate', 50);
            $checkinUtmSource = strtolower(trim(\App\Models\Setting::getVal('checkin_redirect_utm_source', 'diemdanh')));


            // 3. Đọc từng dòng dữ liệu đối soát
            while (($row = fgetcsv($handle, 10000, ",")) !== false) {
                $orderId = trim($row[$orderIdCol] ?? '');
                $statusText = trim($row[$statusCol] ?? '');
                $subId1 = $subId1Col !== null ? trim($row[$subId1Col] ?? '') : '';
                $utmSource = $utmSourceCol !== null ? trim($row[$utmSourceCol] ?? '') : '';
                $actualProductName = $productNameCol !== null ? trim($row[$productNameCol] ?? '') : '';
                $shopName = $shopNameCol !== null ? trim($row[$shopNameCol] ?? '') : '';
                
                if (empty($orderId)) {
                    continue;
                }

                // Đọc hoa hồng thực tế từ báo cáo nếu có cột tương ứng
                $realCommission = 0;
                if ($commissionCol !== null && isset($row[$commissionCol])) {
                    // Loại bỏ các ký tự không phải số (ví dụ dấu phân tách hàng nghìn)
                    $cleanValue = preg_replace('/[^\d.]/', '', $row[$commissionCol]);
                    $realCommission = (float)$cleanValue;
                }

                // Đọc giá trị đơn hàng thực tế (giá gốc sản phẩm) từ báo cáo nếu có cột tương ứng
                $realPrice = 0;
                if ($priceCol !== null && isset($row[$priceCol])) {
                    // Loại bỏ các ký tự không phải số
                    $cleanPrice = preg_replace('/[^\d.]/', '', $row[$priceCol]);
                    $realPrice = (float)$cleanPrice;
                }

                // Quy tắc nghiệp vụ: Nếu sub_id1 hoặc utm_source bắt đầu bằng giá trị checkin_redirect_utm_source, lưu doanh thu điểm danh vào bảng checkin_revenues.
                // Điều này giúp tách biệt các đơn hàng dùng làm quỹ điểm danh, không tạo đơn hoàn tiền cho thành viên.
                $isCheckinOrder = (
                    str_starts_with(strtolower(trim($subId1)), $checkinUtmSource) || 
                    str_starts_with(strtolower(trim($utmSource)), $checkinUtmSource)
                );
                if ($isCheckinOrder) {
                    $statusRevenue = 'pending';
                    $statusLower = mb_strtolower($statusText);
                    if (
                        mb_strpos($statusLower, 'hoàn thành') !== false || 
                        mb_strpos($statusLower, 'thanh toán') !== false || 
                        mb_strpos($statusLower, 'đối soát') !== false || 
                        mb_strpos($statusLower, 'completed') !== false
                    ) {
                        $statusRevenue = 'approved';
                    } elseif (mb_strpos($statusLower, 'hủy') !== false || mb_strpos($statusLower, 'cancel') !== false) {
                        $statusRevenue = 'rejected';
                    }

                    \App\Models\CheckinRevenue::updateOrCreate(
                        ['order_id' => $orderId],
                        [
                            'product_name' => Str::limit($actualProductName, 500, '...'),
                            'original_price' => $realPrice,
                            'commission_amount' => $realCommission,
                            'status' => $statusRevenue,
                            'shop_name' => Str::limit($shopName, 250, ''),
                            'fraud_reason' => null,
                        ]
                    );

                    $details[] = [
                        'order_id' => $orderId,
                        'product_name' => $actualProductName,
                        'status' => $statusRevenue,
                        'amount' => 0,
                        'message' => 'Ghi nhận doanh thu điểm danh vào bảng checkin_revenues (Trạng thái: ' . $statusRevenue . ').'
                    ];

                    $successCount++;
                    if ($statusRevenue === 'approved') {
                        $approvedCount++;
                    } elseif ($statusRevenue === 'rejected') {
                        $rejectedCount++;
                    }
                    continue;
                }

                // Quy tắc nghiệp vụ: Nếu sub_id1 hoặc utm_source bắt đầu bằng giá trị coupon_utm_source, lưu doanh thu mã giảm giá vào bảng coupon_revenues.
                // Điều này giúp tách biệt các đơn hàng dùng làm quỹ mã giảm giá, không tạo đơn hoàn tiền cho thành viên.
                $couponUtmSource = strtolower(trim(\App\Models\Setting::getVal('coupon_utm_source', 'magiamgia')));
                $isCouponOrder = (
                    str_starts_with(strtolower(trim($subId1)), $couponUtmSource) || 
                    str_starts_with(strtolower(trim($utmSource)), $couponUtmSource)
                );
                if ($isCouponOrder) {
                    $statusRevenue = 'pending';
                    $statusLower = mb_strtolower($statusText);
                    if (
                        mb_strpos($statusLower, 'hoàn thành') !== false || 
                        mb_strpos($statusLower, 'thanh toán') !== false || 
                        mb_strpos($statusLower, 'đối soát') !== false || 
                        mb_strpos($statusLower, 'completed') !== false
                    ) {
                        $statusRevenue = 'approved';
                    } elseif (mb_strpos($statusLower, 'hủy') !== false || mb_strpos($statusLower, 'cancel') !== false) {
                        $statusRevenue = 'rejected';
                    }

                    \App\Models\CouponRevenue::updateOrCreate(
                        ['order_id' => $orderId],
                        [
                            'product_name' => Str::limit($actualProductName, 500, '...'),
                            'original_price' => $realPrice,
                            'commission_amount' => $realCommission,
                            'status' => $statusRevenue,
                            'shop_name' => Str::limit($shopName, 250, ''),
                            'fraud_reason' => null,
                        ]
                    );

                    $details[] = [
                        'order_id' => $orderId,
                        'product_name' => $actualProductName,
                        'status' => $statusRevenue,
                        'amount' => 0,
                        'message' => 'Ghi nhận doanh thu mã giảm giá vào bảng coupon_revenues (Trạng thái: ' . $statusRevenue . ').'
                    ];

                    $successCount++;
                    if ($statusRevenue === 'approved') {
                        $approvedCount++;
                    } elseif ($statusRevenue === 'rejected') {
                        $rejectedCount++;
                    }
                    continue;
                }

                // Đầu tiên: Tìm bản ghi đơn hoàn tiền khớp với ID đơn hàng thật của Shopee (order_id = orderId)
                $cashback = CashbackHistory::where('order_id', $orderId)->first();

                // Thứ hai: Nếu không tìm thấy theo mã đơn hàng Shopee thật, thử tìm theo mã giao dịch nội bộ trong sub_id1 hoặc utm_source
                // Tại sao: Thiết bị người dùng (đặc biệt là iOS) có thể đẩy mã giao dịch (trans_id) vào utm_source thay vì sub_id1.
                // Ta kiểm tra cả cột order_id (đề phòng đơn lưu trans_id làm order_id tạm thời) và cột trans_id của đơn hàng.
                if (!$cashback) {
                    $possibleTransIds = array_unique(array_filter([$subId1, $utmSource]));
                    foreach ($possibleTransIds as $possibleId) {
                        $lowerId = strtolower(trim($possibleId));
                        if (str_starts_with($lowerId, $checkinUtmSource) || str_starts_with($lowerId, $couponUtmSource)) {
                            continue;
                        }
                        
                        // Thử tìm theo order_id
                        $cashback = CashbackHistory::where('order_id', $possibleId)->first();
                        if (!$cashback) {
                            // Thử tìm theo trans_id
                            $cashback = CashbackHistory::where('trans_id', $possibleId)->first();
                        }
                        
                        if ($cashback) {
                            break;
                        }
                    }
                }

                if (!$cashback) {
                    $possibleTransIds = array_unique(array_filter([$subId1, $utmSource]));
                    
                    // Quy tắc nghiệp vụ: Tìm lượt click gốc từ bảng cashback_clicks bằng cách quét qua danh sách trans_id tiềm năng
                    $click = null;
                    $matchedTransId = null;
                    foreach ($possibleTransIds as $possibleId) {
                        $lowerId = strtolower(trim($possibleId));
                        if (str_starts_with($lowerId, $checkinUtmSource) || str_starts_with($lowerId, $couponUtmSource)) {
                            continue;
                        }
                        $click = CashbackClick::where('trans_id', $possibleId)->first();
                        if ($click) {
                            $matchedTransId = $possibleId;
                            break;
                        }
                    }

                    // Quy tắc nghiệp vụ bổ sung: Nếu không tìm thấy click trong bảng cashback_clicks,
                    // ta tiếp tục tìm kiếm trong bảng short_links (liên kết rút gọn) khớp theo mã rút gọn hoặc link đích.
                    $shortLink = null;
                    if (!$click) {
                        foreach ($possibleTransIds as $possibleId) {
                            $lowerId = strtolower(trim($possibleId));
                            if (str_starts_with($lowerId, $checkinUtmSource) || str_starts_with($lowerId, $couponUtmSource)) {
                                continue;
                            }
                            $shortLink = ShortLink::where('destination_url', 'like', '%' . $possibleId . '%')->first();
                            if ($shortLink) {
                                $matchedTransId = $possibleId;
                                break;
                            }
                        }
                    }

                    // Nếu tìm thấy click hoặc link rút gọn hợp lệ khớp với mã đối soát thì tự động tạo đơn hoàn tiền chờ duyệt
                    if ($click || $shortLink) {
                        $userId = $click ? $click->user_id : $shortLink->user_id;
                        $platform = $click ? ($click->platform ?? 'shopee') : 'shopee';
                        $transIdToUse = $matchedTransId;
                        $productImage = $click ? $click->product_image : '';
                        $affiliateUrl = $click ? $click->affiliate_url : $shortLink->destination_url;

                        if ($userId) {
                            // Tính toán số tiền cashback thực tế theo cấu hình hệ thống: lấy hoa hồng thực tế nhân tỷ lệ hoàn trả
                            // Chuẩn hoá về số nguyên đồng vì đồng Việt Nam không có đơn vị nhỏ hơn 1 đồng
                            $cashbackAmount = \App\Helpers\MoneyHelper::round($realCommission * ($cashbackSystemRate / 100));
                            // Chặn trên giá trị tỷ lệ để không vượt ngưỡng lưu trữ của cột decimal(5,2)
                            $cashbackRate = \App\Helpers\CashbackHelper::clampRate($realPrice > 0 ? ($cashbackAmount / $realPrice * 100) : 0);

                            // Tạo bản ghi đơn hàng mới ở trạng thái chờ duyệt (pending), nâng giới hạn ảnh sản phẩm lên 1000 ký tự
                            $cashback = CashbackHistory::create([
                                'user_id' => $userId,
                                'platform' => $platform,
                                'order_id' => $orderId,
                                'trans_id' => $transIdToUse,
                                'product_name' => Str::limit($actualProductName ?: 'Sản phẩm Shopee', 500, '...'),
                                'product_image' => Str::limit($productImage, 1000, ''),
                                'original_price' => $realPrice,
                                'cashback_amount' => \App\Helpers\MoneyHelper::round($cashbackAmount),
                                'cashback_rate' => round($cashbackRate, 2),
                                'commission_amount' => $realCommission,
                                'affiliate_url' => $affiliateUrl,
                                'status' => 'pending',
                                'shop_name' => Str::limit($shopName, 250, ''),
                                'fraud_reason' => null,
                            ]);

                            // Gửi thông báo Telegram khi có đơn hàng cashback mới thực tế được ghi nhận
                            try {
                                $user = User::find($userId);
                                if ($user) {
                                    \App\Models\Setting::sendTelegramTemplate('telegram_template_cashback_created', [
                                        'name' => $user->name,
                                        'email' => $user->email,
                                        'product_name' => $actualProductName ?: 'Sản phẩm Shopee',
                                        'price' => number_format($realPrice),
                                        'cashback_amount' => number_format($cashbackAmount),
                                        'commission' => number_format($realCommission),
                                        'profit' => number_format($realCommission - $cashbackAmount),
                                        'platform' => $platform === 'tiktok' ? 'TikTok Shop' : 'Shopee',
                                    ]);
                                }
                            } catch (\Exception $e) {
                                Log::error('Lỗi gửi Telegram khi import CSV tạo đơn: ' . $e->getMessage());
                            }
                        }
                    }
                }

                if (!$cashback) {
                    $ignoredCount++;
                    $details[] = [
                        'order_id' => $orderId,
                        'product_name' => 'Không xác định',
                        'status' => 'ignored',
                        'amount' => 0,
                        'message' => 'Không tìm thấy mã đơn hàng Shopee hoặc mã giao dịch nội bộ trong CSDL.'
                    ];
                    continue;
                }

                // Chỉ xử lý các đơn hàng đang ở trạng thái 'pending' để tránh ghi đè hoặc tính trùng tiền
                if ($cashback->status !== 'pending') {
                    $ignoredCount++;
                    $details[] = [
                        'order_id' => $orderId,
                        'product_name' => $cashback->product_name,
                        'status' => 'ignored',
                        'amount' => $cashback->cashback_amount,
                        'message' => 'Bỏ qua (Đơn hàng này đã được xử lý từ trước).'
                    ];
                    continue;
                }

                // Cập nhật tên shop từ file CSV (nếu có - cắt ngắn tránh lỗi VARCHAR)
                if (!empty($shopName)) {
                    $cashback->shop_name = Str::limit($shopName, 250, '');
                    $cashback->save();
                }

                // Chuyển chữ thường để so sánh không phân biệt hoa thường
                $statusLower = mb_strtolower($statusText);

                // TRƯỜNG HỢP 1: ĐƠN HÀNG BỊ HỦY TRÊN SHOPEE
                if (mb_strpos($statusLower, 'hủy') !== false || mb_strpos($statusLower, 'cancel') !== false) {
                    
                    DB::transaction(function () use ($cashback, $orderId) {
                        $cashback = CashbackHistory::where('id', $cashback->id)->lockForUpdate()->firstOrFail();
                        $cashback->order_id = $orderId; // Cập nhật lại sang mã đơn hàng Shopee thật
                        $cashback->save();
                        
                        CashbackApprovalService::reject($cashback, 'Đơn hàng bị hủy trên Shopee (Cập nhật tự động từ báo cáo đối soát CSV).', [
                            'source' => 'csv'
                        ]);
                    });

                    $details[] = [
                        'order_id' => $orderId,
                        'product_name' => $cashback->product_name,
                        'status' => 'rejected',
                        'amount' => $cashback->cashback_amount,
                        'message' => 'Hủy đơn hoàn tiền thành công (Đơn bị hủy trên Shopee).'
                    ];

                    $rejectedCount++;
                    $successCount++;

                // TRƯỜNG HỢP 2: ĐƠN HÀNG HOÀN THÀNH/ĐÃ THANH TOÁN
                } elseif (
                    mb_strpos($statusLower, 'hoàn thành') !== false || 
                    mb_strpos($statusLower, 'thanh toán') !== false || 
                    mb_strpos($statusLower, 'đối soát') !== false || 
                    mb_strpos($statusLower, 'completed') !== false
                ) {
                    // So sánh sản phẩm thực tế trong CSV có khớp với sản phẩm lúc lấy link không
                    $isMatched = true; // Mặc định là true nếu file CSV không có cột tên sản phẩm để tránh lỗi đối soát
                    if (!empty($actualProductName) && !empty($cashback->product_name)) {
                        $isMatched = $this->isProductMatched($cashback->product_name, $actualProductName);
                    }

                    if (!$isMatched) {
                        DB::transaction(function () use ($cashback, $orderId) {
                            $cashback = CashbackHistory::where('id', $cashback->id)->lockForUpdate()->firstOrFail();
                            $cashback->order_id = $orderId;
                            $cashback->save();
                            
                            CashbackApprovalService::reject($cashback, 'Khách hàng mua sai sản phẩm so với link lấy ban đầu (Đối soát tự động từ báo cáo CSV).', [
                                'source' => 'csv'
                            ]);
                        });

                        $details[] = [
                            'order_id' => $orderId,
                            'product_name' => $cashback->product_name,
                            'status' => 'rejected',
                            'amount' => $cashback->cashback_amount,
                            'message' => 'Hệ thống tự động từ chối hoàn tiền do khách hàng mua sản phẩm khác so với link đã lấy.'
                        ];

                        $rejectedCount++;
                        $successCount++;
                    } else {

                        DB::transaction(function () use ($cashback, $realCommission, $realPrice, $orderId, &$approvedCount, &$successCount) {
                            // Khóa bản ghi để tránh tranh chấp dữ liệu song song
                            $cashback = CashbackHistory::where('id', $cashback->id)->lockForUpdate()->firstOrFail();

                            if ($cashback->status !== 'pending') {
                                return;
                            }

                            // Cập nhật lại mã đơn hàng Shopee thật trong CSDL
                            $cashback->order_id = $orderId;
                            $cashback->save();

                            // Gọi service trung tâm để phê duyệt
                            CashbackApprovalService::approve($cashback, $realPrice, $realCommission, [
                                'source' => 'csv'
                            ]);

                            $approvedCount++;
                            $successCount++;
                        });

                        // Lấy thông tin cập nhật mới nhất từ DB
                        $updatedCashback = CashbackHistory::find($cashback->id);
                        $details[] = [
                            'order_id' => $orderId,
                            'product_name' => $cashback->product_name,
                            'status' => 'approved',
                            'amount' => $updatedCashback ? $updatedCashback->cashback_amount : $cashback->cashback_amount,
                            'message' => 'Phê duyệt hoàn tiền và phân chia MLM thành công.'
                        ];
                    }
                } else {
                    $ignoredCount++;
                    $details[] = [
                        'order_id' => $orderId,
                        'product_name' => $cashback->product_name,
                        'status' => 'ignored',
                        'amount' => $cashback->cashback_amount,
                        'message' => "Bỏ qua (Trạng thái đặt hàng '$statusText' không thuộc diện đối soát tự động)."
                    ];
                }
            }

            fclose($handle);

            // Ghi nhật ký hoạt động chung của admin
            ActivityLog::log("Thực hiện tải file CSV đối soát đơn hàng hoàn tiền. Kết quả: Xử lý thành công $successCount đơn (Duyệt: $approvedCount đơn, Từ chối: $rejectedCount đơn), Bỏ qua $ignoredCount đơn.", auth()->id());

            // Lưu kết quả đối soát chi tiết vào Session
            $importResult = [
                'summary' => [
                    'total' => $successCount + $ignoredCount,
                    'success' => $successCount,
                    'approved' => $approvedCount,
                    'rejected' => $rejectedCount,
                    'ignored' => $ignoredCount
                ],
                'details' => $details
            ];

            return back()
                ->with('success', "Đối soát hoàn tất! Đã xử lý $successCount đơn hàng (Phê duyệt: $approvedCount, Hủy: $rejectedCount).")
                ->with('import_result', $importResult);

        } catch (\Exception $e) {
            Log::error("Import commission CSV failed: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return back()->with('error', 'Đã xảy ra lỗi hệ thống khi xử lý đối soát: ' . $e->getMessage());
        }
    }

    /**
     * Thêm tài khoản Shopee Affiliate bằng cookie JSON (tải lên file hoặc dán chuỗi JSON).
     * Giải thích: Đọc dữ liệu cookie từ file .json tải lên hoặc chuỗi JSON được dán trực tiếp, gọi thử API Shopee để kiểm tra kết nối.
     */
    public function storeShopeeAccount(Request $request)
    {
        // Chặn thêm tài khoản Shopee Affiliate ở chế độ Demo để tránh làm thay đổi cấu hình kết nối của hệ thống
        if (config('app.demo')) {
            return back()->with('error', __('Tính năng quản lý tài khoản Shopee Affiliate bị khóa ở chế độ Demo.'));
        }

        // 1. Kiểm tra xem người dùng chọn tải lên file hay dán trực tiếp
        if ($request->hasFile('cookie_file')) {
            $request->validate([
                'name' => 'required|string|max:100',
                'cookie_file' => 'required|file|mimes:json,txt|max:2048',
            ], [
                'name.required' => 'Vui lòng nhập tên gợi nhớ cho tài khoản.',
                'cookie_file.required' => 'Vui lòng chọn tệp tin JSON cookie.',
                'cookie_file.mimes' => 'Tệp tin tải lên phải có định dạng .json hoặc .txt.',
                'cookie_file.max' => 'Dung lượng tệp cookie vượt quá giới hạn cho phép (tối đa 2MB).'
            ]);

            $cookieJson = file_get_contents($request->file('cookie_file')->getRealPath());
        } else {
            $request->validate([
                'name' => 'required|string|max:100',
                'cookie_json' => 'required|string',
            ], [
                'name.required' => 'Vui lòng nhập tên gợi nhớ cho tài khoản.',
                'cookie_json.required' => 'Vui lòng dán cookie JSON hoặc tải lên file JSON cookie.',
            ]);

            $cookieJson = trim($request->cookie_json);
        }

        // 2. Kiểm tra tính hợp lệ của định dạng JSON
        $decoded = json_decode($cookieJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->with('error', 'Định dạng JSON không hợp lệ. Vui lòng kiểm tra lại cấu trúc file cookie.')->withInput();
        }

        $syncService = new ShopeeSyncService();
        $validation = $syncService->validateCookie($cookieJson);

        if (!$validation['success']) {
            return back()->with('error', 'Không thể kết nối đến Shopee với cookie này: ' . $validation['error'])->withInput();
        }

        // 3. Lưu tài khoản mới vào cơ sở dữ liệu
        $account = ShopeeAccount::create([
            'name' => $request->name,
            'cookie' => $decoded,
            'username' => $validation['username'],
            'status' => 'active',
            'error_message' => null,
            'last_sync_at' => null,
        ]);

        ActivityLog::log("Thêm tài khoản Shopee Affiliate tự động: {$account->name} (Username: {$account->username})", auth()->id());

        return back()->with('success', "Thêm tài khoản '{$account->name}' thành công! Trạng thái: Hoạt động.");
    }

    /**
     * Xóa tài khoản Shopee Affiliate khỏi hệ thống.
     */
    public function destroyShopeeAccount($id)
    {
        // Chặn xóa tài khoản Shopee Affiliate ở chế độ Demo để bảo vệ cấu hình kết nối mẫu của hệ thống
        if (config('app.demo')) {
            return back()->with('error', __('Tính năng quản lý tài khoản Shopee Affiliate bị khóa ở chế độ Demo.'));
        }

        $account = ShopeeAccount::findOrFail($id);
        $name = $account->name;
        
        $account->delete();

        ActivityLog::log("Xóa tài khoản Shopee Affiliate tự động: {$name}", auth()->id());

        return back()->with('success', "Đã xóa tài khoản '{$name}' thành công.");
    }

    /**
     * Đồng bộ thủ công báo cáo đơn hàng của tài khoản Shopee cụ thể.
     * Giải thích: Cho phép gọi thông qua AJAX từ giao diện Modal ở Admin Panel để hiển thị chi tiết các đơn hàng đối soát.
     */
    public function syncShopeeAccount(Request $request, $id)
    {
        // Chặn đồng bộ tài khoản Shopee Affiliate thủ công ở chế độ Demo để tránh thay đổi trạng thái đơn hàng và số dư ví
        if (config('app.demo')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Tính năng đồng bộ tài khoản Shopee Affiliate bị khóa ở chế độ Demo.')
                ], 403);
            }
            return back()->with('error', __('Tính năng đồng bộ tài khoản Shopee Affiliate bị khóa ở chế độ Demo.'));
        }

        $account = ShopeeAccount::findOrFail($id);

        $syncService = new ShopeeSyncService();
        $result = $syncService->syncAccount($account, 30); // Đồng bộ các đơn trong 30 ngày gần đây

        if ($result['success']) {
            ActivityLog::log("Thực hiện đồng bộ thủ công Shopee Account ID {$account->id}. Kết quả: Duyệt {$result['approved']} đơn, từ chối {$result['rejected']} đơn.", auth()->id());

            // Nếu gọi bằng AJAX/JSON, trả về dữ liệu chi tiết đối soát để hiển thị trên Modal
            if ($request->ajax() || $request->wantsJson()) {
                $details = $result['details'] ?? [];
                $ignoredCount = 0;
                $alreadyProcessedCount = 0;

                foreach ($details as $detail) {
                    if (($detail['status_system'] ?? '') === 'ignored') {
                        $ignoredCount++;
                    } elseif (($detail['status_system'] ?? '') === 'already_processed') {
                        $alreadyProcessedCount++;
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => "Đồng bộ tài khoản '{$account->name}' hoàn tất!",
                    'summary' => [
                        'total_scanned' => count($details),
                        'approved' => $result['approved'],
                        'rejected' => $result['rejected'],
                        'already_processed' => $alreadyProcessedCount,
                        'ignored' => $ignoredCount,
                    ],
                    'details' => $details
                ]);
            }

            return back()->with('success', "Đồng bộ tài khoản '{$account->name}' hoàn tất! Kết quả: Phê duyệt {$result['approved']} đơn, Từ chối {$result['rejected']} đơn.");
        } else {
            // Nếu gặp lỗi và gọi bằng AJAX/JSON, trả về lỗi chi tiết
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Đồng bộ tài khoản '{$account->name}' thất bại!",
                    'error' => $account->error_message ?? $result['error'] ?? 'Lỗi kết nối API Shopee.'
                ], 400);
            }

            return back()->with('error', "Đồng bộ tài khoản '{$account->name}' thất bại! Lý do: " . ($account->error_message ?? 'Lỗi kết nối API Shopee.'));
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
