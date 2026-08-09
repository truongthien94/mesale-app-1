<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gift;
use App\Models\GiftRedemption;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GiftController extends Controller
{
    /**
     * Danh sách quà tặng và yêu cầu đổi quà.
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'gifts');
        $search = $request->get('search');

        $giftsQuery = Gift::orderBy('id', 'desc');
        if ($tab === 'gifts') {
            if (!empty($search)) {
                $giftsQuery->where('title', 'like', '%' . $search . '%');
            }
            if ($request->has('tag') && !empty($request->tag)) {
                $giftsQuery->where('tag', $request->tag);
            }
            if ($request->has('type') && !empty($request->type)) {
                $giftsQuery->where('type', $request->type);
            }
            if ($request->has('status') && $request->status !== '') {
                $giftsQuery->where('status', $request->status);
            }
        }
        
        // Cấu hình số dòng hiển thị động (limit), tối đa 500 dòng
        $limitGifts = $request->integer('limit', 15);
        if ($limitGifts < 1 || $limitGifts > 500) {
            $limitGifts = 15;
        }
        $gifts = $giftsQuery->paginate($limitGifts, ['*'], 'gifts_page')->withQueryString();
        
        $redemptionsQuery = GiftRedemption::with(['user', 'gift'])->orderBy('id', 'desc');
        if ($tab === 'redemptions') {
            if (!empty($search)) {
                $redemptionsQuery->where(function ($q) use ($search) {
                    $q->where('code', 'like', '%' . $search . '%')
                      ->orWhere('notes', 'like', '%' . $search . '%')
                      ->orWhere('gift_data', 'like', '%' . $search . '%')
                      ->orWhereHas('gift', function ($gq) use ($search) {
                          $gq->where('title', 'like', '%' . $search . '%');
                      });
                });
            }
            if ($request->has('user_search') && !empty($request->user_search)) {
                $userSearch = '%' . $request->user_search . '%';
                $redemptionsQuery->whereHas('user', function ($uq) use ($userSearch) {
                    $uq->where('name', 'like', $userSearch)
                      ->orWhere('email', 'like', $userSearch)
                      ->orWhere('phone', 'like', $userSearch);
                });
            }
            if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
                $redemptionsQuery->where('status', $request->status);
            }
        }

        // Cấu hình số dòng hiển thị động (limit), tối đa 500 dòng
        $limitRedemptions = $request->integer('limit', 15);
        if ($limitRedemptions < 1 || $limitRedemptions > 500) {
            $limitRedemptions = 15;
        }
        $redemptions = $redemptionsQuery->paginate($limitRedemptions, ['*'], 'redemptions_page')->withQueryString();

        // Lấy danh sách tag lọc, nếu chưa có thì lấy mặc định
        $tags = json_decode(Setting::getVal('gift_tags', '[]'), true);
        if (empty($tags)) {
            $tags = ["Thẻ cào", "Thẻ game", "Mã giảm giá", "Vật phẩm"];
        }

        // Thống kê số tiền đổi quà thành công (approved) theo các mốc thời gian để Admin dễ dàng kiểm soát quỹ quà tặng
        $todayGiftExchanged = GiftRedemption::where('status', 'approved')->whereDate('created_at', today())->sum('amount');
        $weekGiftExchanged = GiftRedemption::where('status', 'approved')->where('created_at', '>=', now()->startOfWeek())->sum('amount');
        $monthGiftExchanged = GiftRedemption::where('status', 'approved')->where('created_at', '>=', now()->startOfMonth())->sum('amount');
        $totalGiftExchanged = GiftRedemption::where('status', 'approved')->sum('amount');

        return view('admin.gifts.index', compact(
            'gifts',
            'redemptions',
            'tab',
            'tags',
            'todayGiftExchanged',
            'weekGiftExchanged',
            'monthGiftExchanged',
            'totalGiftExchanged'
        ));
    }

    /**
     * Tạo mới quà tặng.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'type' => 'required|string|in:voucher,phone_card,giftcode,physical',
            'status' => 'required|boolean',
            'image' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        // Chỉ ghi đúng các trường đã được kiểm duyệt để chống mass assignment các cột nhạy cảm
        $gift = Gift::create($validated);

        ActivityLog::log("Thêm quà tặng mới: {$gift->title}", auth()->id());

        return redirect()->route('admin.gifts.index', ['tab' => 'gifts'])->with('success', 'Thêm quà tặng mới thành công!');
    }

    /**
     * Cập nhật quà tặng.
     */
    public function update(Request $request, Gift $gift)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'type' => 'required|string|in:voucher,phone_card,giftcode,physical',
            'status' => 'required|boolean',
            'image' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        // Chỉ ghi đúng các trường đã được kiểm duyệt để chống mass assignment các cột nhạy cảm
        $gift->update($validated);

        ActivityLog::log("Cập nhật quà tặng: {$gift->title}", auth()->id());

        return redirect()->route('admin.gifts.index', ['tab' => 'gifts'])->with('success', 'Cập nhật quà tặng thành công!');
    }

    /**
     * Xóa quà tặng.
     */
    public function destroy(Gift $gift)
    {
        $title = $gift->title;
        
        // Kiểm tra xem có đơn đổi quà nào liên kết không
        if ($gift->redemptions()->count() > 0) {
            return redirect()->route('admin.gifts.index', ['tab' => 'gifts'])->with('error', 'Không thể xóa quà tặng này vì đã có thành viên quy đổi.');
        }

        $gift->delete();

        ActivityLog::log("Xóa quà tặng: {$title}", auth()->id());

        return redirect()->route('admin.gifts.index', ['tab' => 'gifts'])->with('success', 'Xóa quà tặng thành công!');
    }

    /**
     * Duyệt đơn đổi quà.
     */
    public function approve(Request $request, $id)
    {
        $redemption = GiftRedemption::findOrFail($id);

        if (!in_array($redemption->status, ['pending', 'approved'])) {
            return back()->with('error', 'Yêu cầu này không thể phê duyệt hoặc chỉnh sửa.');
        }

        $request->validate([
            'gift_data' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        $isUpdate = $redemption->status === 'approved';

        $redemption->update([
            'status' => 'approved',
            'gift_data' => $request->gift_data,
            'notes' => $request->notes,
            'processed_at' => $isUpdate ? $redemption->processed_at : now()
        ]);

        if ($isUpdate) {
            ActivityLog::log("Cập nhật thông tin bảo hành đơn đổi quà #{$redemption->code} cho thành viên: {$redemption->user->name}", auth()->id());
            $message = 'Cập nhật thông tin bảo hành thành công!';
        } else {
            // Gửi email thông báo duyệt đổi quà thành công
            try {
                $user = $redemption->user;
                if ($user && !empty($user->email)) {
                    Setting::sendEmail($user->email, 'gift_approved', [
                        'name' => $user->name,
                        'email' => $user->email,
                        'gift_title' => $redemption->gift->title ?? '',
                        'gift_price' => number_format($redemption->amount),
                        'gift_data' => $request->gift_data,
                        'notes' => $request->notes
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Lỗi gửi email duyệt đơn đổi quà: ' . $e->getMessage());
            }

            ActivityLog::log("Duyệt đơn đổi quà #{$redemption->code} cho thành viên: {$redemption->user->name}", auth()->id());
            $message = 'Duyệt đơn đổi quà thành công!';
        }

        return redirect()->route('admin.gifts.index', ['tab' => 'redemptions'])->with('success', $message);
    }

    /**
     * Từ chối đơn đổi quà và hoàn tiền về ví cho user.
     */
    public function reject(Request $request, $id)
    {
        $redemption = GiftRedemption::findOrFail($id);

        if ($redemption->status !== 'pending') {
            return back()->with('error', 'Yêu cầu này đã được xử lý trước đó.');
        }

        $request->validate([
            'notes' => 'required|string|max:1000'
        ], [
            'notes.required' => 'Vui lòng nhập lý do từ chối để thông báo cho thành viên.'
        ]);

        DB::transaction(function () use ($redemption, $request) {
            // Khóa dòng user để tránh race condition
            $user = User::where('id', $redemption->user_id)->lockForUpdate()->firstOrFail();
            
            // Hoàn lại tiền vào ví khả dụng
            $oldBalance = $user->balance;
            $newBalance = $oldBalance + $redemption->amount;
            // balance được gán tường minh (không mass-assign) vì cột này đã bị loại khỏi $fillable vì lý do bảo mật.
            $user->balance = $newBalance;
            $user->save();

            \App\Models\BalanceLog::create([
                'user_id' => $user->id,
                'amount_before' => $oldBalance,
                'amount_change' => $redemption->amount,
                'amount_after' => $newBalance,
                'type' => 'gift_refund',
                'description' => "Hoàn tiền từ chối đổi quà tặng #{$redemption->code} ({$redemption->gift->title})"
            ]);

            // Trả lại 1 lượt kho sản phẩm nếu cần thiết
            $gift = Gift::where('id', $redemption->gift_id)->lockForUpdate()->first();
            if ($gift) {
                $gift->increment('stock');
            }

            // Cập nhật trạng thái đổi quà thành rejected
            $redemption->update([
                'status' => 'rejected',
                'notes' => $request->notes,
                'processed_at' => now()
            ]);

            // Ghi nhật ký hệ thống
            ActivityLog::log("Từ chối đơn đổi quà #{$redemption->code} cho thành viên: {$user->name}. Lý do: {$request->notes}", auth()->id());
        });

        return redirect()->route('admin.gifts.index', ['tab' => 'redemptions'])->with('success', 'Đã từ chối đơn đổi quà và hoàn lại tiền thành công!');
    }

    /**
     * Xuất danh sách quà tặng ra định dạng file JSON hoặc CSV.
     * Hỗ trợ tải dữ liệu nhanh chóng để sao lưu hoặc chuyển đổi hệ thống.
     */
    public function export(Request $request)
    {
        // Chặn xuất danh sách quà tặng ở chế độ Demo để bảo vệ dữ liệu và tránh lạm dụng băng thông máy chủ
        if (config('app.demo')) {
            return redirect()->back()->with('error', __('Tính năng xuất danh sách quà tặng bị khóa ở chế độ Demo.'));
        }

        $format = $request->get('format', 'json');
        
        $idsStr = $request->get('ids');
        $giftsQuery = Gift::orderBy('id', 'desc');
        if (!empty($idsStr)) {
            $ids = array_filter(array_map('intval', explode(',', $idsStr)));
            if (!empty($ids)) {
                $giftsQuery->whereIn('id', $ids);
            }
        }
        $gifts = $giftsQuery->get();

        if ($format === 'csv') {
            $fileName = 'gifts_export_' . now()->format('YmdHis') . '.csv';
            
            $headers = [
                "Content-type"        => "text/csv; charset=UTF-8",
                "Content-Disposition" => "attachment; filename=$fileName",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            // Các cột tiêu đề CSV hiển thị cho người quản trị dễ đọc
            $columns = ['Tiêu đề', 'Hình ảnh', 'Mô tả', 'Giá quy đổi', 'Tồn kho', 'Phân loại', 'Tag lọc', 'Trạng thái'];

            $callback = function() use ($gifts, $columns) {
                $file = fopen('php://output', 'w');
                
                // Thêm ký tự BOM (Byte Order Mark) để Microsoft Excel hiển thị đúng font tiếng Việt có dấu
                fputs($file, "\xEF\xBB\xBF");
                
                fputcsv($file, $columns);

                foreach ($gifts as $gift) {
                    fputcsv($file, [
                        $gift->title,
                        $gift->image,
                        $gift->description,
                        $gift->price,
                        $gift->stock,
                        $gift->type,
                        $gift->tag,
                        $gift->status ? 1 : 0
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        // Định dạng mặc định là JSON tải xuống dạng file stream
        $fileName = 'gifts_export_' . now()->format('YmdHis') . '.json';
        $data = $gifts->map(function ($gift) {
            return [
                'title' => $gift->title,
                'image' => $gift->image,
                'description' => $gift->description,
                'price' => $gift->price,
                'stock' => $gift->stock,
                'type' => $gift->type,
                'tag' => $gift->tag,
                'status' => $gift->status ? 1 : 0
            ];
        });

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }, $fileName, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Nhập danh sách quà tặng từ tệp tin JSON hoặc CSV được tải lên.
     * Chạy toàn bộ tiến trình trong Database Transaction để đảm bảo tính nhất quán (nếu lỗi sẽ rollback toàn bộ).
     */
    public function import(Request $request)
    {
        // Chặn nhập danh sách quà tặng ở chế độ Demo để tránh ghi đè dữ liệu mẫu của hệ thống
        if (config('app.demo')) {
            return redirect()->back()->with('error', __('Tính năng nhập danh sách quà tặng bị khóa ở chế độ Demo.'));
        }

        $request->validate([
            'file' => 'required|file|mimes:json,csv,txt',
        ], [
            'file.required' => __('Vui lòng chọn tệp tin để nhập.'),
            'file.file' => __('Tệp tin không hợp lệ.'),
            'file.mimes' => __('Hệ thống chỉ hỗ trợ nhập từ tệp tin .json hoặc .csv.'),
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $importedCount = 0;

        try {
            if ($extension === 'json') {
                $content = file_get_contents($file->getRealPath());
                $data = json_decode($content, true);

                if (!is_array($data)) {
                    return back()->with('error', __('Định dạng tệp JSON không hợp lệ. Phải là một mảng danh sách quà tặng.'));
                }

                // Thực thi thêm mới quà tặng trong database transaction
                DB::transaction(function () use ($data, &$importedCount) {
                    foreach ($data as $item) {
                        if (empty($item['title'])) {
                            continue;
                        }

                        Gift::create([
                            'title' => $item['title'],
                            'image' => $item['image'] ?? null,
                            'description' => $item['description'] ?? null,
                            'price' => isset($item['price']) ? (float)$item['price'] : 0,
                            'stock' => isset($item['stock']) ? (int)$item['stock'] : 0,
                            'type' => isset($item['type']) && in_array($item['type'], ['voucher', 'phone_card', 'giftcode', 'physical']) ? $item['type'] : 'voucher',
                            'tag' => $item['tag'] ?? null,
                            'status' => isset($item['status']) ? (bool)$item['status'] : true
                        ]);
                        $importedCount++;
                    }
                });
            } else {
                // Xử lý tệp tin CSV
                $filePath = $file->getRealPath();
                $rows = [];
                $header = null;
                $separator = ',';

                if (($handle = fopen($filePath, 'r')) !== false) {
                    // Kiểm tra và bỏ qua ký tự BOM UTF-8 ở đầu file nếu có
                    $bom = fread($handle, 3);
                    if ($bom !== "\xEF\xBB\xBF") {
                        rewind($handle);
                    }

                    // Đọc dòng tiêu đề (Header)
                    $header = fgetcsv($handle, 1000, ',');
                    
                    // Hỗ trợ cả dấu phân tách là chấm phẩy (;) phổ biến của Excel khu vực Châu Âu/Việt Nam
                    if ($header && count($header) == 1 && strpos($header[0], ';') !== false) {
                        rewind($handle);
                        if ($bom === "\xEF\xBB\xBF") {
                            fread($handle, 3);
                        }
                        $header = fgetcsv($handle, 1000, ';');
                        $separator = ';';
                    }

                    // Đọc toàn bộ các dòng tiếp theo
                    while (($row = fgetcsv($handle, 1000, $separator)) !== false) {
                        $rows[] = $row;
                    }
                    fclose($handle);
                }

                if (empty($header)) {
                    return back()->with('error', __('Tệp tin CSV trống hoặc không thể đọc dòng tiêu đề.'));
                }

                // Xây dựng sơ đồ khớp cột (Header Mapping) để hỗ trợ cả tiếng Anh lẫn tiếng Việt không dấu
                $headerMap = [];
                foreach ($header as $key => $colName) {
                    $colNameClean = strtolower(trim($colName));
                    if (in_array($colNameClean, ['tiêu đề', 'tieu de', 'title'])) {
                        $headerMap['title'] = $key;
                    } elseif (in_array($colNameClean, ['hình ảnh', 'hinh anh', 'image', 'ảnh', 'anh'])) {
                        $headerMap['image'] = $key;
                    } elseif (in_array($colNameClean, ['mô tả', 'mo ta', 'description', 'detail'])) {
                        $headerMap['description'] = $key;
                    } elseif (in_array($colNameClean, ['giá quy đổi', 'gia quy doi', 'price', 'giá', 'gia'])) {
                        $headerMap['price'] = $key;
                    } elseif (in_array($colNameClean, ['tồn kho', 'ton kho', 'stock', 'số lượng', 'so luong'])) {
                        $headerMap['stock'] = $key;
                    } elseif (in_array($colNameClean, ['phân loại', 'phan loai', 'type', 'loại', 'loai'])) {
                        $headerMap['type'] = $key;
                    } elseif (in_array($colNameClean, ['tag lọc', 'tag loc', 'tag'])) {
                        $headerMap['tag'] = $key;
                    } elseif (in_array($colNameClean, ['trạng thái', 'trang thai', 'status'])) {
                        $headerMap['status'] = $key;
                    }
                }

                // Thực thi thêm mới trong Database Transaction bảo vệ toàn vẹn dữ liệu
                DB::transaction(function () use ($rows, $headerMap, &$importedCount) {
                    foreach ($rows as $row) {
                        if (empty($row) || count($row) === 0) {
                            continue;
                        }

                        // Lấy giá trị cột Tiêu đề
                        $title = '';
                        if (isset($headerMap['title']) && isset($row[$headerMap['title']])) {
                            $title = trim($row[$headerMap['title']]);
                        } else {
                            $title = isset($row[0]) ? trim($row[0]) : '';
                        }

                        // Bỏ qua nếu dòng không có tiêu đề
                        if (empty($title)) {
                            continue;
                        }

                        $image = isset($headerMap['image']) && isset($row[$headerMap['image']]) ? trim($row[$headerMap['image']]) : (isset($row[1]) ? trim($row[1]) : null);
                        $description = isset($headerMap['description']) && isset($row[$headerMap['description']]) ? trim($row[$headerMap['description']]) : (isset($row[2]) ? trim($row[2]) : null);
                        $price = isset($headerMap['price']) && isset($row[$headerMap['price']]) ? (float)trim($row[$headerMap['price']]) : (isset($row[3]) ? (float)trim($row[3]) : 0);
                        $stock = isset($headerMap['stock']) && isset($row[$headerMap['stock']]) ? (int)trim($row[$headerMap['stock']]) : (isset($row[4]) ? (int)trim($row[4]) : 0);
                        $type = isset($headerMap['type']) && isset($row[$headerMap['type']]) ? trim($row[$headerMap['type']]) : (isset($row[5]) ? trim($row[5]) : 'voucher');
                        $tag = isset($headerMap['tag']) && isset($row[$headerMap['tag']]) ? trim($row[$headerMap['tag']]) : (isset($row[6]) ? trim($row[6]) : null);
                        $statusVal = isset($headerMap['status']) && isset($row[$headerMap['status']]) ? trim($row[$headerMap['status']]) : (isset($row[7]) ? trim($row[7]) : '1');
                        
                        $status = ($statusVal === '1' || strtolower($statusVal) === 'true' || strtolower($statusVal) === 'hoạt động');

                        if (!in_array($type, ['voucher', 'phone_card', 'giftcode', 'physical'])) {
                            $type = 'voucher';
                        }

                        Gift::create([
                            'title' => $title,
                            'image' => $image ?: null,
                            'description' => $description ?: null,
                            'price' => $price,
                            'stock' => $stock,
                            'type' => $type,
                            'tag' => $tag ?: null,
                            'status' => $status
                        ]);
                        $importedCount++;
                    }
                });
            }

            // Ghi nhận nhật ký hệ thống
            ActivityLog::log("Nhập thành công {$importedCount} quà tặng từ tệp tin cấu hình", auth()->id());

            return redirect()->route('admin.gifts.index', ['tab' => 'gifts'])->with('success', __('Nhập thành công :count quà tặng từ file!', ['count' => $importedCount]));

        } catch (\Exception $e) {
            \Log::error('Lỗi nhập quà tặng: ' . $e->getMessage());
            return back()->with('error', __('Có lỗi xảy ra khi nhập tệp tin: :msg', ['msg' => $e->getMessage()]));
        }
    }

    /**
     * Cập nhật cấu hình quy đổi quà tặng hệ thống.
     */
    public function updateConfig(Request $request)
    {
        $request->validate([
            'gift_redemption_enabled' => 'required|boolean'
        ]);

        Setting::updateOrCreate(
            ['key' => 'gift_redemption_enabled'],
            ['value' => $request->gift_redemption_enabled]
        );

        // Xóa bộ nhớ cache
        \Illuminate\Support\Facades\Cache::forget('setting.gift_redemption_enabled');

        $statusText = $request->gift_redemption_enabled ? 'BẬT' : 'TẮT';
        ActivityLog::log("Cập nhật trạng thái quy đổi quà tặng: {$statusText}", auth()->id());

        return redirect()->route('admin.gifts.index')->with('success', "Đã {$statusText} chức năng quy đổi quà tặng thành công!");
    }

    /**
     * Cập nhật danh sách tag lọc quà tặng.
     */
    public function updateTags(Request $request)
    {
        $request->validate([
            'tags' => 'nullable|array',
            'tags.*' => 'required|string|max:100'
        ]);

        $tagsInput = $request->input('tags', []);
        
        // Lọc các tag trùng hoặc rỗng
        $tags = array_values(array_filter(array_unique(array_map('trim', $tagsInput))));

        Setting::updateOrCreate(
            ['key' => 'gift_tags'],
            ['value' => json_encode($tags, JSON_UNESCAPED_UNICODE)]
        );

        // Xóa bộ nhớ cache
        \Illuminate\Support\Facades\Cache::forget('setting.gift_tags');

        ActivityLog::log("Cập nhật danh sách tag lọc quà tặng", auth()->id());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Cập nhật danh sách tag thành công!'),
                'tags' => $tags
            ]);
        }

        return redirect()->route('admin.gifts.index', ['tab' => 'gifts'])->with('success', __('Cập nhật danh sách tag thành công!'));
    }

    /**
     * Xóa hàng loạt quà tặng được chọn.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'gift_ids' => 'required|string',
            'confirm_text' => 'required|string',
        ]);

        if (strtoupper($request->confirm_text) !== 'XÓA HÀNG LOẠT') {
            return response()->json(['status' => 'error', 'message' => __('Từ khóa xác nhận không chính xác.')]);
        }

        $giftIds = explode(',', $request->gift_ids);
        $giftIds = array_filter(array_map('intval', $giftIds));

        if (empty($giftIds)) {
            return response()->json(['status' => 'error', 'message' => __('Không có quà tặng nào hợp lệ để xóa.')]);
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($giftIds) {
                $gifts = Gift::whereIn('id', $giftIds)->lockForUpdate()->get();

                foreach ($gifts as $gift) {
                    $title = $gift->title;
                    $gift->delete();
                    ActivityLog::log(__("Xóa quà tặng khỏi hệ thống (Hành động hàng loạt): :title", ['title' => $title]), auth()->id());
                }
            });

            return response()->json([
                'status' => 'success',
                'message' => __('Đã xóa thành công :count quà tặng được chọn.', ['count' => count($giftIds)])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Lỗi hệ thống: :error', ['error' => $e->getMessage()])
            ]);
        }
    }

    /**
     * Cập nhật hàng loạt quà tặng được chọn.
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'gift_ids' => 'required|string',
            'fields' => 'required|array',
            'data' => 'required|array'
        ]);

        $giftIds = explode(',', $request->gift_ids);
        $giftIds = array_filter(array_map('intval', $giftIds));

        if (empty($giftIds)) {
            return response()->json(['status' => 'error', 'message' => __('Không có quà tặng nào hợp lệ để cập nhật.')]);
        }

        $fields = $request->input('fields');
        $data = $request->input('data');

        // Lọc ra các trường được chọn để cập nhật
        $updateData = [];
        $updatedFieldsNames = [];

        if (!empty($fields['type']) && isset($data['type'])) {
            $updateData['type'] = $data['type'];
            $updatedFieldsNames[] = __('Phân loại');
        }
        if (isset($fields['tag']) && $fields['tag'] && isset($data['tag'])) {
            $updateData['tag'] = $data['tag'];
            $updatedFieldsNames[] = __('Tag lọc');
        }
        if (!empty($fields['status']) && isset($data['status'])) {
            $updateData['status'] = intval($data['status']);
            $updatedFieldsNames[] = __('Trạng thái');
        }
        if (!empty($fields['stock']) && isset($data['stock'])) {
            $updateData['stock'] = intval($data['stock']);
            $updatedFieldsNames[] = __('Tồn kho');
        }

        if (empty($updateData)) {
            return response()->json(['status' => 'error', 'message' => __('Vui lòng chọn ít nhất một trường để cập nhật.')]);
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($giftIds, $updateData, $updatedFieldsNames) {
                $gifts = Gift::whereIn('id', $giftIds)->lockForUpdate()->get();

                foreach ($gifts as $gift) {
                    $gift->update($updateData);
                }

                $fieldsList = implode(', ', $updatedFieldsNames);
                ActivityLog::log(__("Cập nhật hàng loạt :count quà tặng (Các trường: :fields)", [
                    'count' => count($giftIds),
                    'fields' => $fieldsList
                ]), auth()->id());
            });

            return response()->json([
                'status' => 'success',
                'message' => __('Đã cập nhật thành công :count quà tặng được chọn.', ['count' => count($giftIds)])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Lỗi hệ thống: :error', ['error' => $e->getMessage()])
            ]);
        }
    }
}
