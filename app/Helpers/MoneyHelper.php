<?php

namespace App\Helpers;

/**
 * Lớp tiện ích chuẩn hoá số tiền của hệ thống về đơn vị đồng Việt Nam (VND).
 *
 * Bối cảnh nghiệp vụ: Đồng Việt Nam không có đơn vị nhỏ hơn 1 đồng, trong khi hầu hết
 * số tiền của hệ thống đều sinh ra từ phép nhân tỷ lệ phần trăm (tiền hoàn = hoa hồng × tỷ lệ hoàn,
 * hoa hồng F1/F2 = tiền hoàn × tỷ lệ giới thiệu, phí rút = số tiền rút × tỷ lệ phí). Các phép nhân này
 * luôn tạo ra phần thập phân và được lưu thẳng vào các cột decimal(15,2) của cơ sở dữ liệu.
 *
 * Hệ quả nếu không chuẩn hoá:
 *  - Số dư ví tích luỹ phần lẻ vô nghĩa (ví dụ 346.523,13đ) trong khi giao diện lại làm tròn khi hiển thị,
 *    dẫn tới việc con số người dùng nhìn thấy không khớp với con số thật trong cơ sở dữ liệu.
 *  - Mỗi màn hình lại làm tròn theo một kiểu khác nhau (number_format làm tròn lên, CurrencyHelper cắt xuống)
 *    khiến việc đối soát giữa các báo cáo bị lệch.
 *
 * Vì vậy mọi phép tính tiền tệ mới đều phải đi qua lớp tiện ích này trước khi ghi vào cơ sở dữ liệu.
 */
class MoneyHelper
{
    /**
     * Ngưỡng chuẩn hoá sai số dấu phẩy động trước khi cắt phần thập phân.
     *
     * Giải thích: Kiểu float của PHP không biểu diễn chính xác được mọi số thập phân, nên một phép tính
     * đúng ra phải bằng 1000 có thể cho ra 999.9999999999999. Nếu cắt thẳng phần lẻ sẽ ra 999 (sai 1 đồng).
     * Do đó luôn làm tròn về 4 chữ số thập phân để triệt tiêu sai số này trước khi xử lý tiếp.
     */
    private const FLOAT_PRECISION = 4;

    /**
     * Làm tròn số tiền về số nguyên đồng gần nhất (làm tròn nửa lên).
     *
     * Đây là hàm mặc định dùng cho toàn bộ phép tính tiền tệ mới của hệ thống: tiền hoàn cho người mua,
     * hoa hồng giới thiệu F1/F2, phí rút tiền, tiền thưởng điểm danh... Cách làm tròn này khớp chính xác
     * với hàm Math.round() phía JavaScript đang dùng để hiển thị số tiền dự kiến cho người dùng,
     * nhờ đó số tiền xem trước ở giao diện luôn bằng đúng số tiền hệ thống ghi nhận.
     *
     * @param float|int|string|null $amount Số tiền thô vừa tính được
     * @return float Số tiền đã chuẩn hoá về số nguyên đồng
     */
    public static function round($amount): float
    {
        if (!is_numeric($amount) || !is_finite((float) $amount)) {
            return 0.0;
        }

        return (float) round(round((float) $amount, self::FLOAT_PRECISION), 0);
    }

    /**
     * Làm tròn số tiền về số nguyên đồng theo hướng tiến về 0 (cắt bỏ phần lẻ).
     *
     * Dùng cho các trường hợp bắt buộc không được phép làm số tiền phình to hơn giá trị gốc, điển hình là
     * khi làm sạch phần lẻ tồn đọng của số dư ví: nếu làm tròn lên, hệ thống sẽ tự sinh thêm tiền vào ví
     * người dùng mà không có nguồn đối ứng. Với số tiền âm (nghiệp vụ thu hồi hoa hồng) hàm tiến về 0
     * nên cũng không trừ nhiều hơn giá trị gốc.
     *
     * @param float|int|string|null $amount Số tiền thô cần cắt phần lẻ
     * @return float Số tiền đã chuẩn hoá về số nguyên đồng
     */
    public static function truncate($amount): float
    {
        if (!is_numeric($amount) || !is_finite((float) $amount)) {
            return 0.0;
        }

        $normalized = round((float) $amount, self::FLOAT_PRECISION);

        return $normalized < 0 ? (float) ceil($normalized) : (float) floor($normalized);
    }
}
