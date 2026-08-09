<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service xác thực Cloudflare Turnstile Captcha bảo mật hệ thống.
 */
class TurnstileService
{
    /**
     * Xác thực token Cloudflare Turnstile gửi từ client.
     *
     * @param string|null $responseToken Token captcha nhận từ client (cf-turnstile-response)
     * @param string|null $ip Địa chỉ IP của client gửi yêu cầu
     * @return bool Trả về true nếu xác thực thành công hoặc tính năng bị tắt, ngược lại trả về false.
     */
    public static function verify(?string $responseToken, ?string $ip = null): bool
    {
        // Lấy cấu hình trạng thái hoạt động của captcha từ database
        $status = Setting::getVal('turnstile_status', '0');
        $secretKey = Setting::getVal('turnstile_secret_key');

        // Nếu Turnstile tắt hoặc chưa được cấu hình secret key, mặc định cho qua (tránh lỗi vận hành)
        if ($status !== '1' || empty($secretKey)) {
            return true;
        }

        // Nếu bật Turnstile nhưng không nhận được token captcha, xác thực coi như thất bại
        if (empty($responseToken)) {
            return false;
        }

        try {
            // Gửi yêu cầu xác thực tới API Cloudflare Turnstile qua POST request
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret'   => $secretKey,
                'response' => $responseToken,
                'remoteip' => $ip,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Trả về true nếu Cloudflare xác nhận thành công
                if (!empty($data['success']) && $data['success'] === true) {
                    return true;
                }

                // Ghi log cảnh báo nếu Turnstile trả về lỗi xác thực
                Log::warning('Xác thực Turnstile thất bại', [
                    'response' => $data,
                    'ip' => $ip
                ]);
            } else {
                // Ghi log lỗi kết nối đến Cloudflare
                Log::error('Gọi API Cloudflare Turnstile không thành công', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            // Log lỗi ngoại lệ phát sinh trong quá trình call API
            Log::error('Lỗi ngoại lệ khi xác thực Turnstile: ' . $e->getMessage());
        }

        return false;
    }
}
