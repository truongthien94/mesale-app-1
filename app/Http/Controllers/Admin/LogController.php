<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\EmailQueue;
use App\Models\TelegramQueue;
use App\Models\DailyCheckin;
use App\Models\CashbackClick;
use App\Models\ShortLink;
use App\Models\ReferralCommission;
use App\Models\Notification;
use App\Models\SavedProduct;
use App\Models\ApiLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LogController extends Controller
{
    /**
     * Xem nhật ký hoạt động của toàn bộ thành viên và admin.
     */
    public function activityLogs(Request $request)
    {
        $query = ActivityLog::with('user');

        // Tìm kiếm tách biệt theo thông tin thành viên (tên, email)
        if ($request->has('user_search') && !empty($request->user_search)) {
            $search = '%' . $request->user_search . '%';
            $query->whereHas('user', function($qu) use ($search) {
                $qu->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        // Tìm kiếm theo hành động chi tiết
        if ($request->has('activity') && !empty($request->activity)) {
            $query->where('activity', 'like', '%' . $request->activity . '%');
        }

        // Tìm kiếm theo địa chỉ IP thực hiện
        if ($request->has('ip_address') && !empty($request->ip_address)) {
            $query->where('ip_address', 'like', '%' . $request->ip_address . '%');
        }

        // Cấu hình số dòng hiển thị động (limit), tối đa 500 dòng
        $limit = $request->integer('limit', 15);
        if ($limit < 1 || $limit > 500) {
            $limit = 15;
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate($limit)->withQueryString();

        return view('admin.logs.activity', compact('logs'));
    }

    /**
     * Xem nhật ký lỗi hệ thống (Laravel Storage Logs).
     *
     * Nâng cấp: thay vì đổ raw text, ở đây ta bóc tách (parse) tệp laravel.log thành
     * các bản ghi có cấu trúc (thời gian, cấp độ, nội dung, stack trace) kèm thống kê
     * theo cấp độ để quản trị viên chẩn đoán/sửa lỗi nhanh hơn.
     */
    public function systemLogs(Request $request)
    {
        // Tự động kiểm tra và giới hạn kích thước file log không vượt quá 10MB
        self::limitLogFileSize();

        $logPath = storage_path('logs/laravel.log');

        // Số dòng cuối cần đọc (giới hạn để tránh quá tải bộ nhớ với file log lớn)
        $allowedLines = [200, 500, 1000, 2000, 5000];
        $lines = $request->integer('lines', 500);
        if (!in_array($lines, $allowedLines, true)) {
            $lines = 500;
        }

        // Thông tin tổng quan về tệp log
        $fileInfo = [
            'exists'     => File::exists($logPath),
            'size'       => File::exists($logPath) ? self::formatBytes(filesize($logPath)) : '0 B',
            'sizeRaw'    => File::exists($logPath) ? filesize($logPath) : 0,
            'maxSize'    => '10 MB',
            'modified'   => File::exists($logPath) ? \Illuminate\Support\Carbon::createFromTimestamp(filemtime($logPath)) : null,
            'totalLines' => 0,
            'path'       => 'storage/logs/laravel.log',
        ];

        // Ẩn nội dung log hệ thống ở chế độ Demo để bảo vệ thông tin cấu hình nhạy cảm khỏi rò rỉ
        if (config('app.demo')) {
            $entries = [];
            $stats = ['total' => 0, 'error' => 0, 'warning' => 0, 'info' => 0, 'debug' => 0, 'other' => 0];
            $notice = __('Hệ thống đang chạy ở chế độ Demo thử nghiệm. Nội dung nhật ký lỗi hệ thống laravel.log tạm thời bị ẩn để bảo vệ bảo mật.');
            return view('admin.logs.system', compact('entries', 'stats', 'fileInfo', 'lines', 'allowedLines', 'notice'));
        }

        $entries = [];
        $notice = null;

        if (File::exists($logPath)) {
            try {
                $rawLines = $this->tailLines($logPath, $lines, $fileInfo['totalLines']);
                $entries = $this->parseLogEntries($rawLines);
                // Đảo ngược để bản ghi mới nhất hiển thị trên cùng
                $entries = array_reverse($entries);
            } catch (\Throwable $e) {
                $notice = __('Không thể đọc tệp nhật ký: :msg', ['msg' => $e->getMessage()]);
            }
        } else {
            $notice = __('Chưa có tệp nhật ký hệ thống nào được tạo hoặc tệp đang trống.');
        }

        // Thống kê theo cấp độ nghiêm trọng để hiển thị thẻ tổng quan
        $stats = ['total' => count($entries), 'error' => 0, 'warning' => 0, 'info' => 0, 'debug' => 0, 'other' => 0];
        foreach ($entries as $en) {
            $stats[$en['severity']]++;
        }

        return view('admin.logs.system', compact('entries', 'stats', 'fileInfo', 'lines', 'allowedLines', 'notice'));
    }

    /**
     * Đọc N dòng cuối cùng của một tệp mà không tải toàn bộ tệp vào RAM.
     * Đồng thời ghi nhận tổng số dòng của tệp vào biến tham chiếu $totalLines.
     */
    private function tailLines(string $path, int $n, int &$totalLines = 0): array
    {
        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $startLine = max(0, $totalLines - $n);
        $file->seek($startLine);

        $lines = [];
        while (!$file->eof()) {
            $lines[] = $file->current();
            $file->next();
        }

        return $lines;
    }

    /**
     * Bóc tách các dòng log Laravel thô thành các bản ghi có cấu trúc.
     * Một bản ghi bắt đầu bằng dòng tiêu đề "[thời gian] env.LEVEL: nội dung",
     * các dòng phía sau (context JSON, stack trace) được gom vào phần chi tiết.
     */
    private function parseLogEntries(array $lines): array
    {
        $entries = [];
        $current = null;
        // Ví dụ khớp: [2026-06-17 20:05:12] local.INFO: message ...
        $headerRegex = '/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:?\d{2})?)\]\s+([a-zA-Z0-9_.-]+)\.([A-Z]+):\s?(.*)$/u';

        foreach ($lines as $line) {
            $clean = rtrim($line, "\r\n");

            if (preg_match($headerRegex, $clean, $m)) {
                if ($current) {
                    $entries[] = $this->finalizeEntry($current);
                }
                $current = [
                    'timeRaw' => $m[1],
                    'env'     => $m[2],
                    'level'   => strtoupper($m[3]),
                    'message' => $m[4],
                    'extra'   => [],
                ];
            } elseif ($current) {
                // Dòng tiếp nối (stack trace / context) của bản ghi hiện tại
                $current['extra'][] = $clean;
            }
            // Bỏ qua các dòng "mồ côi" trước tiêu đề đầu tiên (bản ghi bị cắt do đọc tail)
        }

        if ($current) {
            $entries[] = $this->finalizeEntry($current);
        }

        return $entries;
    }

    /**
     * Hoàn thiện một bản ghi log: chuẩn hóa thời gian, tách chi tiết/stack trace,
     * gán nhóm mức độ nghiêm trọng để tô màu ở giao diện.
     */
    private function finalizeEntry(array $e): array
    {
        $details = trim(implode("\n", $e['extra']));
        $message = trim($e['message']);

        try {
            $time = \Illuminate\Support\Carbon::parse($e['timeRaw']);
        } catch (\Throwable $ex) {
            $time = null;
        }

        $raw = '[' . $e['timeRaw'] . '] ' . $e['env'] . '.' . $e['level'] . ': ' . $e['message'];
        if ($details !== '') {
            $raw .= "\n" . $details;
        }

        return [
            'level'    => $e['level'],
            'severity' => $this->severityOf($e['level']),
            'env'      => $e['env'],
            'time'     => $time ? $time->format('d/m/Y H:i:s') : $e['timeRaw'],
            'ago'      => $time ? $time->diffForHumans() : '',
            'message'  => $message,
            'details'  => $details,
            'hasTrace' => $details !== '',
            'raw'      => $raw,
            'open'     => false,
        ];
    }

    /**
     * Quy về nhóm mức độ nghiêm trọng phục vụ tô màu/bộ lọc trên giao diện.
     */
    private function severityOf(string $level): string
    {
        return match ($level) {
            'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'error',
            'WARNING' => 'warning',
            'NOTICE', 'INFO' => 'info',
            'DEBUG' => 'debug',
            default => 'other',
        };
    }

    /**
     * Định dạng dung lượng byte sang đơn vị dễ đọc (KB, MB...).
     */
    private static function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        return round($bytes / (1024 ** $power), $precision) . ' ' . $units[$power];
    }

    /**
     * Tải xuống toàn bộ tệp nhật ký lỗi hệ thống (laravel.log) để phân tích ngoại tuyến.
     */
    public function downloadSystemLogs(Request $request)
    {
        // Chặn tải xuống ở chế độ Demo để bảo vệ thông tin nhạy cảm
        if (config('app.demo')) {
            return back()->with('error', __('Chức năng tải xuống bị vô hiệu hóa ở chế độ Demo.'));
        }

        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            return back()->with('error', __('Không tìm thấy tệp nhật ký hệ thống để tải xuống.'));
        }

        // Ghi nhận nhật ký hoạt động của Admin để phục vụ giám sát bảo mật
        ActivityLog::log('Tải xuống tệp nhật ký lỗi hệ thống laravel.log', auth()->id());

        return response()->download($logPath, 'laravel-' . now()->format('Ymd_His') . '.log');
    }

    /**
     * Dọn dẹp thủ công toàn bộ tệp nhật ký lỗi hệ thống (laravel.log).
     */
    public function clearSystemLogs(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');
        
        try {
            if (File::exists($logPath)) {
                // Ghi đè file log thành một dòng thông báo trống
                File::put($logPath, '[' . now()->toDateTimeString() . '] local.INFO: Nhật ký lỗi hệ thống đã được dọn dẹp thủ công bởi quản trị viên.' . PHP_EOL);
            }
            
            // Ghi nhận nhật ký hoạt động
            ActivityLog::log("Dọn dẹp nhật ký lỗi hệ thống laravel.log", auth()->id());
            
            return back()->with('success', __('Đã dọn dẹp nhật ký lỗi hệ thống thành công!'));
        } catch (\Exception $e) {
            return back()->with('error', __('Lỗi khi dọn dẹp logs: :msg', ['msg' => $e->getMessage()]));
        }
    }

    /**
     * Giới hạn kích thước file log hệ thống không vượt quá 10MB.
     * Nếu vượt quá 10MB, hệ thống sẽ tự động cắt bỏ phần log cũ nhất và chỉ giữ lại 2MB log mới nhất ở đuôi.
     */
    public static function limitLogFileSize()
    {
        $logPath = storage_path('logs/laravel.log');
        $maxSize = 10 * 1024 * 1024; // 10MB
        $keepSize = 2 * 1024 * 1024;  // Giữ lại 2MB log mới nhất ở đuôi

        if (file_exists($logPath) && filesize($logPath) > $maxSize) {
            $fp = @fopen($logPath, 'r+');
            if ($fp) {
                // Đưa con trỏ đến vị trí cách cuối file $keepSize bytes
                if (@fseek($fp, -$keepSize, SEEK_END) === 0) {
                    // Đọc phần nội dung log mới nhất
                    $data = @fread($fp, $keepSize);
                    if ($data !== false) {
                        // Tìm vị trí xuống dòng đầu tiên để bỏ đi dòng bị cắt dở
                        $firstNewline = strpos($data, "\n");
                        if ($firstNewline !== false) {
                            $data = substr($data, $firstNewline + 1);
                        }
                        
                        // Đưa con trỏ về đầu file, ghi đè phần dữ liệu mới nhất
                        @rewind($fp);
                        @fwrite($fp, $data);
                        // Cắt ngắn file tại vị trí ghi xong để giải phóng dung lượng thừa phía sau
                        @ftruncate($fp, ftell($fp));
                        
                        // Ghi thêm một dòng thông báo hệ thống đã tự động dọn dẹp log cũ
                        @fwrite($fp, '[' . now()->toDateTimeString() . '] local.INFO: Hệ thống đã tự động dọn dẹp các dòng log cũ nhất do kích thước vượt quá 10MB.' . PHP_EOL);
                    }
                }
                @fclose($fp);
            }
        }
    }

    /**
     * Xem danh sách hàng đợi gửi email.
     * Hỗ trợ tìm kiếm, lọc theo trạng thái và phân trang.
     */
    public function emailQueueLogs(Request $request)
    {
        // Tải kèm thông tin user (nếu email thuộc về một tài khoản trên hệ thống) để hiển thị nút chỉnh sửa nhanh
        $query = EmailQueue::with('user');

        // Lọc theo trạng thái gửi thư
        if ($request->has('status') && in_array($request->status, ['pending', 'sending', 'sent', 'failed'])) {
            $query->where('status', $request->status);
        }

        // Tìm kiếm theo email nhận hoặc tên người nhận
        if ($request->has('email_search') && !empty($request->email_search)) {
            $search = '%' . $request->email_search . '%';
            $query->where(function($q) use ($search) {
                $q->where('to_email', 'like', $search)
                  ->orWhere('to_name', 'like', $search);
            });
        }

        // Tìm kiếm theo tiêu đề email
        if ($request->has('subject') && !empty($request->subject)) {
            $query->where('subject', 'like', '%' . $request->subject . '%');
        }

        // Cấu hình số dòng hiển thị động (limit), tối đa 500 dòng
        $limit = $request->integer('limit', 15);
        if ($limit < 1 || $limit > 500) {
            $limit = 15;
        }

        $queues = $query->orderBy('created_at', 'desc')->paginate($limit)->withQueryString();

        return view('admin.logs.email_queue', compact('queues'));
    }

    /**
     * Thử gửi lại email bị lỗi (đưa trạng thái về pending và reset số lần thử).
     */
    public function retryEmail($id)
    {
        $email = EmailQueue::findOrFail($id);
        
        $email->update([
            'status' => 'pending',
            'attempts' => 0,
            'error_message' => null,
        ]);

        return back()->with('success', 'Đã đặt lại trạng thái chờ gửi cho email này.');
    }

    /**
     * Xóa email khỏi hàng đợi.
     */
    public function deleteEmail($id)
    {
        $email = EmailQueue::findOrFail($id);
        $email->delete();

        return back()->with('success', 'Đã xóa email khỏi hàng đợi thành công.');
    }

    /**
     * Thao tác nhanh: đặt lại trạng thái chờ gửi cho nhiều email cùng lúc.
     * Đưa toàn bộ email được chọn về trạng thái pending, reset số lần thử và xóa thông báo lỗi.
     */
    public function bulkRetryEmail(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));

        if (empty($ids)) {
            return back()->with('error', __('Vui lòng chọn ít nhất một email để gửi lại.'));
        }

        $count = EmailQueue::whereIn('id', $ids)->update([
            'status' => 'pending',
            'attempts' => 0,
            'error_message' => null,
            'sent_at' => null,
        ]);

        return back()->with('success', __('Đã đặt lại trạng thái chờ gửi cho :count email.', ['count' => $count]));
    }

    /**
     * Thao tác nhanh: xóa nhiều email khỏi hàng đợi cùng lúc.
     */
    public function bulkDeleteEmail(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));

        if (empty($ids)) {
            return back()->with('error', __('Vui lòng chọn ít nhất một email để xóa.'));
        }

        $count = EmailQueue::whereIn('id', $ids)->delete();

        return back()->with('success', __('Đã xóa :count email khỏi hàng đợi.', ['count' => $count]));
    }

    /**
     * Xem nhật ký điểm danh chuyên cần của toàn bộ thành viên.
     * Hỗ trợ tìm kiếm theo tên thành viên, email hoặc lọc.
     */
    public function checkinLogs(Request $request)
    {
        $query = DailyCheckin::with('user');

        // Tìm kiếm theo tên hoặc email thành viên điểm danh
        if ($request->has('user_search') && !empty($request->user_search)) {
            $search = '%' . $request->user_search . '%';
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('phone', 'like', $search);
            });
        }

        // Cấu hình số dòng hiển thị động (limit), tối đa 500 dòng
        $limit = $request->integer('limit', 15);
        if ($limit < 1 || $limit > 500) {
            $limit = 15;
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate($limit)->withQueryString();

        return view('admin.logs.checkin', compact('logs'));
    }

    /**
     * Xem nhật ký hoa hồng Affiliate của toàn bộ thành viên (MLM F1, F2).
     * Hỗ trợ tìm kiếm theo thông tin người giới thiệu, người mua hoặc mã đơn hàng.
     */
    public function referralCommissions(Request $request)
    {
        $query = ReferralCommission::with(['referrer', 'referred', 'cashbackHistory']);

        // Tìm kiếm theo thông tin người nhận hoa hồng (referrer - F1/F2)
        if ($request->has('referrer_search') && !empty($request->referrer_search)) {
            $search = '%' . $request->referrer_search . '%';
            $query->whereHas('referrer', function($qr) use ($search) {
                $qr->where('name', 'like', $search)
                   ->orWhere('email', 'like', $search)
                   ->orWhere('phone', 'like', $search);
            });
        }

        // Tìm kiếm theo thông tin người mua trực tiếp (referred - F0)
        if ($request->has('referred_search') && !empty($request->referred_search)) {
            $search = '%' . $request->referred_search . '%';
            $query->whereHas('referred', function($qrf) use ($search) {
                $qrf->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search);
            });
        }

        // Tìm kiếm theo mã đơn hàng Shopee
        if ($request->has('order_id') && !empty($request->order_id)) {
            $search = '%' . $request->order_id . '%';
            $query->whereHas('cashbackHistory', function($qch) use ($search) {
                $qch->where('order_id', 'like', $search)
                    ->orWhere('order_code', 'like', $search);
            });
        }

        // Cấu hình số dòng hiển thị động (limit), tối đa 500 dòng
        $limit = $request->integer('limit', 15);
        if ($limit < 1 || $limit > 500) {
            $limit = 15;
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate($limit)->withQueryString();

        return view('admin.logs.referral_commissions', compact('logs'));
    }

    /**
     * Xem danh sách hàng đợi gửi tin nhắn Telegram.
     * Hỗ trợ tìm kiếm, lọc theo trạng thái và phân trang.
     */
    public function telegramQueueLogs(Request $request)
    {
        $query = TelegramQueue::query();

        // Lọc theo trạng thái gửi tin nhắn
        if ($request->has('status') && in_array($request->status, ['pending', 'sending', 'sent', 'failed'])) {
            $query->where('status', $request->status);
        }

        // Tìm kiếm theo Chat ID Telegram
        if ($request->has('chat_id') && !empty($request->chat_id)) {
            $query->where('chat_id', 'like', '%' . $request->chat_id . '%');
        }

        // Tìm kiếm theo nội dung tin nhắn
        if ($request->has('message') && !empty($request->message)) {
            $query->where('message', 'like', '%' . $request->message . '%');
        }

        // Cấu hình số dòng hiển thị động (limit), tối đa 500 dòng
        $limit = $request->integer('limit', 15);
        if ($limit < 1 || $limit > 500) {
            $limit = 15;
        }

        $queues = $query->orderBy('created_at', 'desc')->paginate($limit)->withQueryString();

        return view('admin.logs.telegram_queue', compact('queues'));
    }

    /**
     * Thử gửi lại tin nhắn Telegram bị lỗi.
     */
    public function retryTelegram($id)
    {
        $telegram = TelegramQueue::findOrFail($id);
        
        $telegram->update([
            'status' => 'pending',
            'attempts' => 0,
            'error_message' => null,
        ]);

        return back()->with('success', 'Đã đặt lại trạng thái chờ gửi cho tin nhắn Telegram này.');
    }

    /**
     * Xóa tin nhắn khỏi hàng đợi Telegram.
     */
    public function deleteTelegram($id)
    {
        $telegram = TelegramQueue::findOrFail($id);
        $telegram->delete();

        return back()->with('success', 'Đã xóa tin nhắn khỏi hàng đợi Telegram thành công.');
    }

    /**
     * Xem nhật ký lượt click hoàn tiền (Cashback Clicks).
     * Quy tắc nghiệp vụ: Giúp admin theo dõi người dùng nào đã click vào link Shopee và mã giao dịch trans_id tương ứng để đối soát.
     */
    public function cashbackClicks(Request $request)
    {
        $query = CashbackClick::with(['user', 'cashbackHistory']);

        // Tìm kiếm theo mã giao dịch đối soát (trans_id)
        if ($request->has('trans_id') && !empty($request->trans_id)) {
            $query->where('trans_id', 'like', '%' . $request->trans_id . '%');
        }

        // Tìm kiếm theo thông tin thành viên (tên, email)
        if ($request->has('user_search') && !empty($request->user_search)) {
            $search = '%' . $request->user_search . '%';
            $query->whereHas('user', function($qu) use ($search) {
                $qu->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        // Tìm kiếm theo tên sản phẩm Shopee
        if ($request->has('product_name') && !empty($request->product_name)) {
            $query->where('product_name', 'like', '%' . $request->product_name . '%');
        }

        // Cấu hình số dòng hiển thị động (limit), tối đa 500 dòng
        $limit = $request->integer('limit', 15);
        if ($limit < 1 || $limit > 500) {
            $limit = 15;
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate($limit)->withQueryString();

        return view('admin.logs.cashback_clicks', compact('logs'));
    }

    /**
     * Xem nhật ký liên kết rút gọn (Short Links).
     * Logic: Hiển thị danh sách các link rút gọn được người dùng tạo ra, số click và URL đích đối soát.
     */
    public function shortLinks(Request $request)
    {
        $query = ShortLink::with('user');

        // Tìm kiếm theo mã code rút gọn
        if ($request->has('code') && !empty($request->code)) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }

        // Tìm kiếm theo thông tin thành viên tạo link (tên, email)
        if ($request->has('user_search') && !empty($request->user_search)) {
            $search = '%' . $request->user_search . '%';
            $query->whereHas('user', function($qu) use ($search) {
                $qu->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        // Tìm kiếm theo URL đích
        if ($request->has('destination_url') && !empty($request->destination_url)) {
            $query->where('destination_url', 'like', '%' . $request->destination_url . '%');
        }

        // Cấu hình số dòng hiển thị động (limit), tối đa 500 dòng
        $limit = $request->integer('limit', 15);
        if ($limit < 1 || $limit > 500) {
            $limit = 15;
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate($limit)->withQueryString();

        return view('admin.logs.short_links', compact('logs'));
    }

    /**
     * Xem danh sách nhật ký thông báo gửi cho người dùng.
     * Quy tắc nghiệp vụ: Giúp Admin giám sát các thông báo (hệ thống/cá nhân) được gửi cho user, 
     * theo dõi trạng thái đã đọc hay chưa của user để đánh giá mức độ tương tác.
     */
    public function notificationLogs(Request $request)
    {
        $query = Notification::with('user');

        // Lọc theo thông tin người nhận (tên, email)
        if ($request->has('user_search') && !empty($request->user_search)) {
            $search = '%' . $request->user_search . '%';
            $query->whereHas('user', function($qu) use ($search) {
                $qu->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        // Lọc theo tiêu đề hoặc nội dung thông báo
        if ($request->has('search') && !empty($request->search)) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('content', 'like', $search);
            });
        }

        // Lọc theo trạng thái đã đọc hay chưa (is_read)
        if ($request->has('is_read') && $request->is_read !== '') {
            $query->where('is_read', $request->boolean('is_read'));
        }

        // Lọc theo loại thông báo (personal/general)
        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        // Cấu hình số dòng hiển thị động (limit), tối đa 500 dòng
        $limit = $request->integer('limit', 15);
        if ($limit < 1 || $limit > 500) {
            $limit = 15;
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate($limit)->withQueryString();

        return view('admin.logs.notifications', compact('logs'));
    }

    /**
     * Xóa thông báo khỏi hệ thống.
     * Quy tắc nghiệp vụ: Cho phép Admin dọn dẹp các thông báo không cần thiết hoặc nhầm lẫn để tránh làm phiền user.
     */
    public function deleteNotification($id)
    {
        // Sử dụng database transaction để đảm bảo tính toàn vẹn dữ liệu
        $notification = Notification::findOrFail($id);
        
        $title = $notification->title;
        $userName = $notification->user ? $notification->user->name : 'N/A';
        
        $notification->delete();

        // Ghi lại nhật ký hoạt động của Admin để phục vụ giám sát bảo mật
        ActivityLog::log("Xóa thông báo gửi cho user {$userName} (Tiêu đề: {$title})", auth()->id());

        return back()->with('success', __('Đã xóa thông báo thành công!'));
    }

    /**
     * Xem nhật ký danh sách các sản phẩm đã lưu của toàn bộ thành viên.
     * Quy tắc nghiệp vụ: Giúp Admin theo dõi sản phẩm nào đang được người dùng quan tâm
     * và lưu lại nhiều nhất, từ đó hỗ trợ nghiên cứu hành vi mua sắm và tối ưu chiến dịch affiliate.
     */
    public function savedProducts(Request $request)
    {
        $query = SavedProduct::with('user');

        // Tìm kiếm theo thông tin thành viên (tên, email, số điện thoại)
        if ($request->has('user_search') && !empty($request->user_search)) {
            $search = '%' . $request->user_search . '%';
            $query->whereHas('user', function($qu) use ($search) {
                $qu->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('phone', 'like', $search);
            });
        }

        // Tìm kiếm theo tên sản phẩm đã lưu
        if ($request->has('product_name') && !empty($request->product_name)) {
            $query->where('name', 'like', '%' . $request->product_name . '%');
        }

        // Cấu hình số dòng hiển thị động (limit), tối đa 500 dòng để tránh quá tải RAM
        $limit = $request->integer('limit', 15);
        if ($limit < 1 || $limit > 500) {
            $limit = 15;
        }

        $products = $query->orderBy('created_at', 'desc')->paginate($limit)->withQueryString();

        return view('admin.logs.saved_products', compact('products'));
    }

    /**
     * Xóa một bản ghi sản phẩm đã lưu khỏi danh sách.
     * Quy tắc nghiệp vụ: Cho phép Admin xóa các sản phẩm lỗi, sản phẩm vi phạm chính sách
     * hoặc sản phẩm đã hết hạn mà người dùng đã lưu để tối ưu hóa dữ liệu trong database.
     */
    public function deleteSavedProduct($id)
    {
        $product = SavedProduct::findOrFail($id);
        
        $productName = $product->name;
        $userName = $product->user ? $product->user->name : 'N/A';
        $userId = $product->user_id;

        $product->delete();

        // Ghi lại nhật ký hoạt động của Admin để phục vụ giám sát bảo mật
        ActivityLog::log("Xóa sản phẩm đã lưu của user {$userName} (Tên sản phẩm: {$productName})", auth()->id());
        
        // Ghi thêm log hoạt động cho user để user biết sản phẩm đã bị quản trị viên xóa khỏi danh sách
        ActivityLog::log("Sản phẩm đã lưu '{$productName}' đã bị quản trị viên gỡ bỏ", $userId);

        return back()->with('success', __('Đã xóa sản phẩm đã lưu thành công!'));
    }

    /**
     * API thống kê lượt click hoàn tiền (cashback_clicks) theo thời gian.
     * Giải thích nghiệp vụ:
     * - Trả về dữ liệu JSON gồm: số lượt click theo ngày/tháng, top sản phẩm được tìm kiếm nhiều nhất và tổng số lượt click.
     * - Giúp admin phân tích xu hướng mua sắm của thành viên để có chiến lược tiếp thị phù hợp.
     * - Hỗ trợ các mốc thời gian: tuần, tháng, năm.
     */
    public function cashbackClicksStats(Request $request)
    {
        // Nhận tham số period, mặc định là week
        $period = $request->input('period', 'week');
        if (!in_array($period, ['week', 'month', 'year'])) {
            $period = 'week';
        }

        // Xác định khoảng thời gian và format gom nhóm ngày/tháng trong MySQL
        if ($period === 'week') {
            $startDate = \Carbon\Carbon::now()->subDays(6)->startOfDay();
            $groupFormat = '%Y-%m-%d';
            $labelFormat = 'd/m';
        } elseif ($period === 'month') {
            $startDate = \Carbon\Carbon::now()->subDays(29)->startOfDay();
            $groupFormat = '%Y-%m-%d';
            $labelFormat = 'd/m';
        } else {
            $startDate = \Carbon\Carbon::now()->subMonths(11)->startOfMonth();
            $groupFormat = '%Y-%m';
            $labelFormat = null;
        }

        // Truy vấn số lượng click theo ngày/tháng
        $clickData = CashbackClick::where('created_at', '>=', $startDate)
            ->select(
                \Illuminate\Support\Facades\DB::raw("DATE_FORMAT(created_at, '{$groupFormat}') as date_key"),
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as click_count')
            )
            ->groupBy('date_key')
            ->pluck('click_count', 'date_key')
            ->toArray();

        // Tạo mảng labels và datasets cho Chart.js
        $labels = [];
        $clicks = [];

        if ($period === 'year') {
            // Duyệt 12 tháng gần nhất
            for ($i = 11; $i >= 0; $i--) {
                $date = \Carbon\Carbon::now()->subMonths($i);
                $key = $date->format('Y-m');
                $labels[] = __('Tháng') . ' ' . $date->format('m/Y');
                $clicks[] = (int)($clickData[$key] ?? 0);
            }
        } else {
            // Duyệt từng ngày
            $totalDays = $period === 'week' ? 6 : 29;
            for ($i = $totalDays; $i >= 0; $i--) {
                $date = \Carbon\Carbon::now()->subDays($i);
                $key = $date->format('Y-m-d');
                $labels[] = $date->format($labelFormat);
                $clicks[] = (int)($clickData[$key] ?? 0);
            }
        }

        // Tính tổng lượt click trong thời gian này
        $totalClicks = array_sum($clicks);

        // Lấy số người dùng khác nhau đã lấy link trong thời gian này
        $totalUsers = CashbackClick::where('created_at', '>=', $startDate)
            ->distinct('user_id')
            ->count('user_id');

        // Lấy top 5 sản phẩm được lấy link nhiều nhất trong khoảng thời gian này
        $topProducts = CashbackClick::where('created_at', '>=', $startDate)
            ->select('product_name', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_clicks'))
            ->groupBy('product_name')
            ->orderByDesc('total_clicks')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'labels' => $labels,
            'clicks' => $clicks,
            'totals' => [
                'clicks' => $totalClicks,
                'users' => $totalUsers,
            ],
            'top_products' => $topProducts,
            'period' => $period,
        ]);
    }

    /**
     * Xem nhật ký gọi API của toàn bộ hệ thống.
     *
     * Business rule: Cho phép Admin giám sát hoạt động sử dụng API
     * của thành viên — biết ai gọi endpoint nào, lúc nào, dữ liệu gì,
     * và kết quả trả về ra sao (status code). Hỗ trợ nhiều bộ lọc đa chiều.
     */
    public function apiLogs(Request $request)
    {
        $query = ApiLog::with('user');

        // Lọc theo thông tin thành viên (tên, email)
        if ($request->filled('user_search')) {
            $search = '%' . $request->user_search . '%';
            $query->whereHas('user', function ($qu) use ($search) {
                $qu->where('name', 'like', $search)
                   ->orWhere('email', 'like', $search);
            });
        }

        // Lọc theo đường dẫn endpoint API
        if ($request->filled('endpoint')) {
            $query->where('endpoint', 'like', '%' . $request->endpoint . '%');
        }

        // Lọc theo HTTP method (GET, POST, PUT, DELETE)
        if ($request->filled('method_filter')) {
            $query->where('method', strtoupper($request->method_filter));
        }

        // Lọc theo nhóm API (openapi / bot)
        if ($request->filled('api_group')) {
            $query->where('api_group', $request->api_group);
        }

        // Lọc theo HTTP status code
        if ($request->filled('status_code')) {
            $query->where('status_code', $request->integer('status_code'));
        }

        // Lọc theo địa chỉ IP nguồn
        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%' . $request->ip_address . '%');
        }

        // Cấu hình số dòng hiển thị động (limit), tối đa 500 dòng
        $limit = $request->integer('limit', 15);
        if ($limit < 1 || $limit > 500) {
            $limit = 15;
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate($limit)->withQueryString();

        return view('admin.logs.api', compact('logs'));
    }

    /**
     * Dọn dẹp thủ công toàn bộ nhật ký gọi API (api_logs) trong cơ sở dữ liệu.
     *
     * Business rule: Giúp Admin làm sạch bộ nhớ cơ sở dữ liệu khi số lượng nhật ký gọi API quá lớn.
     */
    public function clearApiLogs(Request $request)
    {
        try {
            // Xóa toàn bộ bản ghi nhật ký gọi API
            ApiLog::query()->delete();

            // Ghi nhận nhật ký hoạt động của Admin
            ActivityLog::log("Dọn dẹp nhật ký gọi API", auth()->id());

            return back()->with('success', __('Đã dọn dẹp toàn bộ nhật ký gọi API thành công!'));
        } catch (\Exception $e) {
            return back()->with('error', __('Lỗi khi dọn dẹp nhật ký API: :msg', ['msg' => $e->getMessage()]));
        }
    }

    /**
     * Dọn dẹp toàn bộ Nhật ký hoạt động (activity_logs).
     */
    public function clearActivityLogs(Request $request)
    {
        try {
            ActivityLog::query()->delete();
            ActivityLog::log("Dọn dẹp toàn bộ nhật ký hoạt động", auth()->id());
            return back()->with('success', __('Đã dọn dẹp toàn bộ nhật ký hoạt động thành công!'));
        } catch (\Exception $e) {
            return back()->with('error', __('Lỗi khi dọn dẹp nhật ký hoạt động: :msg', ['msg' => $e->getMessage()]));
        }
    }

    /**
     * Dọn dẹp toàn bộ Hàng đợi Email (email_queues).
     */
    public function clearEmailQueue(Request $request)
    {
        try {
            EmailQueue::query()->delete();
            ActivityLog::log("Dọn dẹp toàn bộ hàng đợi email", auth()->id());
            return back()->with('success', __('Đã dọn dẹp toàn bộ hàng đợi email thành công!'));
        } catch (\Exception $e) {
            return back()->with('error', __('Lỗi khi dọn dẹp hàng đợi email: :msg', ['msg' => $e->getMessage()]));
        }
    }

    /**
     * Dọn dẹp toàn bộ Hàng đợi Telegram (telegram_queues).
     */
    public function clearTelegramQueue(Request $request)
    {
        try {
            TelegramQueue::query()->delete();
            ActivityLog::log("Dọn dẹp toàn bộ hàng đợi Telegram", auth()->id());
            return back()->with('success', __('Đã dọn dẹp toàn bộ hàng đợi Telegram thành công!'));
        } catch (\Exception $e) {
            return back()->with('error', __('Lỗi khi dọn dẹp hàng đợi Telegram: :msg', ['msg' => $e->getMessage()]));
        }
    }

    /**
     * Dọn dẹp toàn bộ Nhật ký điểm danh (daily_checkins).
     */
    public function clearCheckinLogs(Request $request)
    {
        try {
            DailyCheckin::query()->delete();
            ActivityLog::log("Dọn dẹp toàn bộ nhật ký điểm danh", auth()->id());
            return back()->with('success', __('Đã dọn dẹp toàn bộ nhật ký điểm danh thành công!'));
        } catch (\Exception $e) {
            return back()->with('error', __('Lỗi khi dọn dẹp nhật ký điểm danh: :msg', ['msg' => $e->getMessage()]));
        }
    }

    /**
     * Dọn dẹp toàn bộ Hoa hồng giới thiệu Affiliate (referral_commissions).
     */
    public function clearReferralCommissions(Request $request)
    {
        try {
            ReferralCommission::query()->delete();
            ActivityLog::log("Dọn dẹp toàn bộ nhật ký hoa hồng giới thiệu", auth()->id());
            return back()->with('success', __('Đã dọn dẹp toàn bộ nhật ký hoa hồng giới thiệu thành công!'));
        } catch (\Exception $e) {
            return back()->with('error', __('Lỗi khi dọn dẹp nhật ký hoa hồng giới thiệu: :msg', ['msg' => $e->getMessage()]));
        }
    }

    /**
     * Dọn dẹp toàn bộ Nhật ký click hoàn tiền (cashback_clicks).
     */
    public function clearCashbackClicks(Request $request)
    {
        try {
            CashbackClick::query()->delete();
            ActivityLog::log("Dọn dẹp toàn bộ nhật ký click hoàn tiền", auth()->id());
            return back()->with('success', __('Đã dọn dẹp toàn bộ nhật ký click hoàn tiền thành công!'));
        } catch (\Exception $e) {
            return back()->with('error', __('Lỗi khi dọn dẹp nhật ký click hoàn tiền: :msg', ['msg' => $e->getMessage()]));
        }
    }

    /**
     * Dọn dẹp toàn bộ Nhật ký liên kết rút gọn Short Link (short_links).
     */
    public function clearShortLinks(Request $request)
    {
        try {
            ShortLink::query()->delete();
            ActivityLog::log("Dọn dẹp toàn bộ nhật ký liên kết rút gọn Short Link", auth()->id());
            return back()->with('success', __('Đã dọn dẹp toàn bộ nhật ký Short Link thành công!'));
        } catch (\Exception $e) {
            return back()->with('error', __('Lỗi khi dọn dẹp nhật ký Short Link: :msg', ['msg' => $e->getMessage()]));
        }
    }

    /**
     * Dọn dẹp toàn bộ Nhật ký thông báo (notifications).
     */
    public function clearNotificationLogs(Request $request)
    {
        try {
            Notification::query()->delete();
            ActivityLog::log("Dọn dẹp toàn bộ nhật ký thông báo", auth()->id());
            return back()->with('success', __('Đã dọn dẹp toàn bộ nhật ký thông báo thành công!'));
        } catch (\Exception $e) {
            return back()->with('error', __('Lỗi khi dọn dẹp nhật ký thông báo: :msg', ['msg' => $e->getMessage()]));
        }
    }

    /**
     * Dọn dẹp toàn bộ Sản phẩm đã lưu của User (saved_products).
     */
    public function clearSavedProducts(Request $request)
    {
        try {
            SavedProduct::query()->delete();
            ActivityLog::log("Dọn dẹp toàn bộ sản phẩm đã lưu", auth()->id());
            return back()->with('success', __('Đã dọn dẹp toàn bộ sản phẩm đã lưu thành công!'));
        } catch (\Exception $e) {
            return back()->with('error', __('Lỗi khi dọn dẹp sản phẩm đã lưu: :msg', ['msg' => $e->getMessage()]));
        }
    }
}
