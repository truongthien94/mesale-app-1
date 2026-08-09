<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cookie;
use App\Models\Setting;

class CaptureUtmSource
{
    /**
     * Handle an incoming request.
     * Xử lý request đi vào, tự động nhận diện nguồn truy cập (Facebook, Zalo, Google, Direct, hoặc UTM cụ thể)
     * và lưu nguồn này vào cookie để ghi nhận khi đăng ký tài khoản.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ghi nhận mã giới thiệu từ tham số 'ref' trên URL ở bất kỳ trang nào (trang chủ, blog,...)
        if ($request->has('ref')) {
            $refCode = $request->query('ref');
            if (is_string($refCode)) {
                // Kiểm tra xem cookie mã giới thiệu hiện tại có khác với mã mới không (để tránh tăng click trùng lặp liên tục khi tải lại trang)
                $oldRefCode = $request->cookie('referred_by_code');
                if ($oldRefCode !== $refCode) {
                    try {
                        // Tìm kiếm người giới thiệu theo mã giới thiệu
                        $referrerUser = \App\Models\User::where('referral_code', $refCode)->first();
                        if ($referrerUser) {
                            // Tăng số lượt click vào link giới thiệu lên 1 đơn vị
                            $referrerUser->increment('referral_clicks');
                        }
                    } catch (\Exception $e) {
                        // Bỏ qua lỗi truy vấn database nếu có
                    }
                }

                $cookieDays = 30;
                try {
                    // Lấy thời gian lưu trữ cookie từ cài đặt hệ thống
                    $cookieDays = (int)Setting::getVal('referral_cookie_days', 30);
                } catch (\Exception $e) {
                    // Bỏ qua lỗi nếu bảng cài đặt chưa tải kịp
                }
                // Thiết lập cookie lưu mã giới thiệu người dùng
                Cookie::queue('referred_by_code', $refCode, $cookieDays * 24 * 60);
            }
        }

        // Chỉ ghi nhận nguồn khi chưa tồn tại cookie utm_source trong máy của người dùng
        if (!$request->hasCookie('utm_source')) {
            $utmSource = null;

            // 1. Ưu tiên số 1: Lấy trực tiếp từ tham số URL ?utm_source=...
            if ($request->has('utm_source')) {
                $rawSource = $request->query('utm_source');
                if (is_string($rawSource)) {
                    // BẢO MẬT: Lọc sạch thẻ HTML và chỉ giữ lại các ký tự an toàn: chữ, số, khoảng trắng, gạch dưới, gạch ngang, dấu chấm.
                    $cleanSource = preg_replace('/[^a-zA-Z0-9\s\.\-_]/', '', strip_tags($rawSource));
                    // Giới hạn chiều dài chuỗi tối đa 100 ký tự để tránh lỗi tràn bộ nhớ DB hoặc phình to HTTP Header Cookie
                    $utmSource = mb_substr(trim($cleanSource), 0, 100);
                }
            } 
            // 2. Ưu tiên số 2: Phân tích nguồn giới thiệu tự nhiên qua HTTP Referer
            else {
                $referer = $request->headers->get('referer');
                
                if (!empty($referer)) {
                    $refererHost = strtolower(parse_url($referer, PHP_URL_HOST) ?? '');
                    $currentHost = strtolower($request->getHost());

                    // Loại trừ trường hợp người dùng chuyển hướng nội bộ giữa các trang trong website
                    if ($refererHost !== $currentHost && !str_ends_with($refererHost, '.' . $currentHost)) {
                        if (str_contains($refererHost, 'facebook.com') || str_contains($refererHost, 'fb.com') || str_contains($refererHost, 'fb.me')) {
                            $utmSource = 'Facebook';
                        } elseif (str_contains($refererHost, 'zalo')) {
                            $utmSource = 'Zalo';
                        } elseif (str_contains($refererHost, 'google')) {
                            $utmSource = 'Google';
                        } elseif (str_contains($refererHost, 'tiktok.com')) {
                            $utmSource = 'TikTok';
                        } elseif (str_contains($refererHost, 'youtube.com') || str_contains($refererHost, 'youtu.be')) {
                            $utmSource = 'YouTube';
                        } elseif (str_contains($refererHost, 'twitter.com') || str_contains($refererHost, 't.co') || str_contains($refererHost, 'x.com')) {
                            $utmSource = 'Twitter';
                        } else {
                            // BẢO MẬT: Lọc sạch host của Referer chỉ giữ lại các ký tự hợp lệ cho tên miền: chữ thường, số, dấu chấm, gạch ngang, gạch dưới.
                            // Kẻ tấn công giả mạo script payload trong Referer Header sẽ bị lọc sạch hoàn toàn.
                            $cleanHost = preg_replace('/[^a-z0-9\.\-_]/', '', $refererHost);
                            $utmSource = mb_substr($cleanHost, 0, 100);
                        }
                    }
                } 
                // 3. Ưu tiên số 3: Nếu không có referrer (người dùng gõ trực tiếp domain hoặc từ bookmark)
                else {
                    $utmSource = 'Direct';
                }
            }

            // Nếu đã xác định được nguồn truy cập hợp lệ, tiến hành lưu vào cookie
            if (!empty($utmSource)) {
                // Lấy cấu hình số ngày lưu cookie từ database (mặc định 30 ngày)
                $cookieDays = 30;
                try {
                    $cookieDays = (int)Setting::getVal('referral_cookie_days', 30);
                } catch (\Exception $e) {
                    // Bỏ qua nếu có lỗi phát sinh để tránh làm gián đoạn luồng request
                }

                // Lưu nguồn vào cookie (thời gian tính bằng phút)
                Cookie::queue('utm_source', $utmSource, $cookieDays * 24 * 60);
            }
        }

        return $next($request);
    }
}
