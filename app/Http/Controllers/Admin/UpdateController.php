<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ActivityLog;
use App\Services\SystemUpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * UpdateController - Điều khiển các hành động cập nhật hệ thống thủ công qua giao diện Admin.
 * 
 * Sử dụng SystemUpdateService để xử lý logic cập nhật, giảm thiểu sự trùng lặp
 * và đảm bảo tính đồng bộ với cron job cập nhật tự động.
 */
class UpdateController extends Controller
{
    /**
     * @var SystemUpdateService
     */
    protected $updateService;

    /**
     * Khởi tạo UpdateController với SystemUpdateService.
     * 
     * @param SystemUpdateService $updateService
     */
    public function __construct(SystemUpdateService $updateService)
    {
        $this->updateService = $updateService;
    }

    /**
     * Hiển thị giao diện trang cập nhật hệ thống.
     */
    public function index()
    {
        $autoUpdate = Setting::getVal('auto_update', '1');
        return view('admin.update.index', compact('autoUpdate'));
    }

    /**
     * Bật/tắt chế độ tự động cập nhật hệ thống qua AJAX.
     */
    public function toggleAutoUpdate(Request $request)
    {
        try {
            $status = $request->input('status') ? '1' : '0';
            
            Setting::setVal('auto_update', $status);

            $message = $status === '1' 
                ? __('Đã bật tự động cập nhật hệ thống thành công.') 
                : __('Đã tắt tự động cập nhật hệ thống.');

            ActivityLog::log($message, auth()->id());

            return response()->json([
                'success' => true,
                'status'  => $status,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            Log::error('Toggle auto update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Có lỗi xảy ra khi cập nhật cấu hình.')
            ], 500);
        }
    }

    /**
     * Kiểm tra bản cập nhật mới từ Máy chủ cập nhật.
     */
    public function check(Request $request)
    {
        // Chống spam kiểm tra cập nhật (Rate limit: tối đa 1 lần mỗi 60 giây cho mỗi tài khoản admin)
        $cacheKey = 'last_update_check_time_' . auth()->id();
        $lastCheck = cache()->get($cacheKey);
        if ($lastCheck && (time() - $lastCheck) < 60) {
            $secondsLeft = 60 - (time() - $lastCheck);
            return response()->json([
                'status' => 'error',
                'message' => __('Vui lòng đợi :seconds giây trước khi thực hiện kiểm tra cập nhật tiếp theo.', ['seconds' => $secondsLeft])
            ], 429);
        }
        cache()->put($cacheKey, time(), 60);

        try {
            $host = $request->getHost();
            $result = $this->updateService->checkUpdate($host);

            if (!$result['success']) {
                $statusCode = isset($result['invalid_license']) ? 400 : 500;
                return response()->json([
                    'status'          => 'error',
                    'message'         => $result['message'],
                    'invalid_license' => $result['invalid_license'] ?? false
                ], $statusCode);
            }

            $formattedCurrent = $result['current_version'];
            $formattedLatest  = $result['latest_version'];
            $isUpToDate       = $result['up_to_date'];
            $downloadUrl      = $result['download_url'];

            // Xây dựng cấu trúc danh sách release trả về cho giao diện
            $releases = [];
            $releases[] = [
                'version'   => $formattedLatest,
                'name'      => __('Phiên bản mới nhất (:version)', ['version' => $formattedLatest]),
                'message'   => __('Bản phát hành chính thức từ hệ thống CMSNT. Vui lòng bấm Cập nhật ngay để hệ thống tự động tải và cài đặt.'),
                'date'      => now()->toDateString(),
                'asset_url' => $downloadUrl,
                'current'   => $formattedLatest === $formattedCurrent,
            ];

            if ($isUpToDate) {
                $releases[0]['current'] = true;
            }

            return response()->json([
                'status'          => 'success',
                'up_to_date'      => $isUpToDate,
                'current_version' => $formattedCurrent,
                'latest_version'  => $formattedLatest,
                'releases'        => $releases,
            ]);
        } catch (\Throwable $e) {
            Log::error('Update check failed: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => __('Lỗi kiểm tra cập nhật: :error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * Dummy API chi tiết commit (giữ lại để tương thích giao diện frontend cũ)
     */
    public function commitDetail(Request $request)
    {
        return response()->json([
            'status'  => 'success',
            'files'   => [],
            'stats'   => ['additions' => 0, 'deletions' => 0]
        ]);
    }

    /**
     * Tiến hành tải xuống và áp dụng bản cập nhật.
     */
    public function apply(Request $request)
    {
        $adminId = auth()->id();
        $assetUrl = $request->input('asset_url');
        $targetVersion = $request->input('target_version');

        if (!$assetUrl) {
            return response()->json([
                'status' => 'error',
                'message' => __('Không tìm thấy đường dẫn tải gói cập nhật.'),
                'logs'    => []
            ], 400);
        }

        // Bảo mật: Chỉ cho phép link tải từ Máy chủ cập nhật chính thức của CMSNT
        if (!str_starts_with($assetUrl, 'https://api.cmsnt.co')) {
            return response()->json([
                'status' => 'error',
                'message' => __('URL tải xuống không hợp lệ. Chỉ chấp nhận tải từ máy chủ cập nhật chính thức.'),
                'logs'    => []
            ], 400);
        }

        $result = $this->updateService->applyUpdate($assetUrl, $targetVersion, $adminId);

        if (!$result['success']) {
            $status = isset($result['lock_remaining']) ? 409 : 500;
            return response()->json([
                'status'           => 'error',
                'message'          => $result['message'],
                'lock_remaining'   => $result['lock_remaining'] ?? null,
                'can_force_unlock' => $result['can_force_unlock'] ?? false,
                'logs'             => $result['logs'] ?? []
            ], $status);
        }

        return response()->json([
            'status'      => 'success',
            'message'     => $result['message'],
            'new_version' => $result['new_version'],
            'logs'        => $result['logs'],
        ]);
    }

    /**
     * Mở khóa bằng tay khi tiến trình cập nhật trước bị kẹt.
     */
    public function forceUnlock()
    {
        $this->updateService->forceUnlock(auth()->id());

        return response()->json([
            'status'  => 'success',
            'message' => __('Đã mở khóa và làm sạch thư mục tạm thành công. Bạn có thể tiến hành cập nhật lại.'),
        ]);
    }

    /**
     * Lưu mã bản quyền (license_key) của khách hàng.
     */
    public function saveLicense(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string|max:255',
        ]);

        $licenseKey = trim($request->input('license_key'));

        Setting::updateOrCreate(
            ['key' => 'license_key'],
            ['value' => $licenseKey]
        );

        // Xóa cache xác thực bản quyền session để kiểm tra online ngay lập tức
        session()->forget('__license_cache');

        ActivityLog::log("Cập nhật mã bản quyền hệ thống trực tiếp từ trang cập nhật", auth()->id());

        return response()->json([
            'status'  => 'success',
            'message' => __('Đã kích hoạt mã bản quyền thành công!'),
        ]);
    }
}
