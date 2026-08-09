<?php

namespace App\Helpers;

/**
 * Lớp tiện ích dùng chung cho các nghiệp vụ tính toán hoàn tiền của tất cả các sàn thương mại điện tử.
 */
class CashbackHelper
{
    /**
     * Giới hạn tối đa mà cột cashback_rate của bảng cashback_histories và products có thể lưu trữ.
     *
     * Giải thích: Hai cột này đều có kiểu dữ liệu decimal(5,2) nên giá trị lớn nhất là 999.99.
     * Cơ sở dữ liệu đang chạy ở chế độ STRICT_TRANS_TABLES, do đó nếu ghi vào một giá trị vượt ngưỡng
     * thì MySQL sẽ báo lỗi và làm gián đoạn toàn bộ tiến trình đồng bộ đơn hàng.
     */
    public const MAX_RATE = 999.99;

    /**
     * Chuẩn hoá tỷ lệ hoàn tiền (%) về khoảng giá trị an toàn trước khi ghi vào cơ sở dữ liệu.
     *
     * Nghiệp vụ: Tỷ lệ này được tính bằng công thức (số tiền hoàn / giá bán) × 100 nên phụ thuộc vào
     * dữ liệu do API của sàn trả về. Trong một số trường hợp bất thường (API trả về hoa hồng lớn hơn
     * cả giá bán, hoặc giá bán bị thiếu) kết quả có thể vượt ngưỡng lưu trữ cho phép. Ngoài ra, kể từ khi
     * hệ thống cho phép cấu hình tỷ lệ hoàn tiền vượt mốc 100% để chạy chiến dịch bù lỗ, khoảng an toàn
     * của phép tính này bị thu hẹp lại, nên bắt buộc phải chặn trên trước khi ghi dữ liệu.
     *
     * @param float|int|null $rate Tỷ lệ hoàn tiền thô vừa tính được (đơn vị %)
     * @return float Tỷ lệ đã được chuẩn hoá trong khoảng từ 0 đến 999.99, làm tròn 2 chữ số thập phân
     */
    public static function clampRate($rate): float
    {
        // Dữ liệu không hợp lệ (null, chuỗi rỗng, NaN, vô cực) đều quy về 0 để tránh ghi sai vào cơ sở dữ liệu
        if (!is_numeric($rate) || !is_finite((float) $rate)) {
            return 0.0;
        }

        return round(max(0.0, min((float) $rate, self::MAX_RATE)), 2);
    }
}
