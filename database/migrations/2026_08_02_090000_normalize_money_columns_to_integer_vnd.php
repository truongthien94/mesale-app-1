<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Làm sạch toàn bộ phần thập phân tồn đọng của các cột tiền tệ trong cơ sở dữ liệu.
 *
 * Bối cảnh: Trước đây các công thức tính tiền hoàn, hoa hồng giới thiệu và phí rút tiền đều nhân với tỷ lệ
 * phần trăm rồi lưu thẳng kết quả vào các cột decimal(15,2), khiến số dư ví và toàn bộ lịch sử giao dịch
 * tích luỹ phần lẻ vô nghĩa (ví dụ 346.523,13đ) trong khi đồng Việt Nam không có đơn vị nhỏ hơn 1 đồng.
 * Kể từ nay mọi phép tính tiền tệ đã đi qua App\Helpers\MoneyHelper nên dữ liệu mới luôn là số nguyên,
 * migration này chịu trách nhiệm chuẩn hoá nốt phần dữ liệu lịch sử.
 *
 * Nguyên tắc làm tròn được áp dụng:
 *  - Nhóm số dư ví và các cột tổng tích luỹ của thành viên: CẮT BỎ phần lẻ (làm tròn về phía 0).
 *    Lý do: nếu làm tròn lên, hệ thống sẽ tự sinh thêm tiền vào ví thành viên mà không có nguồn đối ứng.
 *    Ngoài ra giao diện đang hiển thị số dư qua CurrencyHelper::format() vốn cũng cắt phần lẻ, nên cách này
 *    giữ nguyên đúng con số mà thành viên đang nhìn thấy, không gây thắc mắc sau khi cập nhật.
 *  - Nhóm bảng lịch sử và giá trị đơn lẻ: LÀM TRÒN về số nguyên gần nhất, khớp với cách number_format()
 *    đang hiển thị các con số này ở giao diện quản trị.
 *  - Riêng bảng balance_logs: hai cột số dư trước/sau được cắt phần lẻ giống số dư ví, sau đó cột biến động
 *    được tính lại bằng hiệu của hai cột này để đảm bảo luôn thoả mãn: số dư trước + biến động = số dư sau.
 */
return new class extends Migration
{
    /**
     * Danh sách các cột được LÀM TRÒN về số nguyên gần nhất, gom theo tên bảng.
     */
    private const ROUND_COLUMNS = [
        'cashback_histories'    => ['original_price', 'cashback_amount', 'commission_amount'],
        'cashback_clicks'       => ['original_price', 'cashback_amount', 'commission_amount'],
        'referral_commissions'  => ['amount'],
        'daily_checkins'        => ['coins_earned'],
        // CẢNH BÁO: tuyệt đối không đưa cột products.shopee_commission vào danh sách này. Cột đó đang mang
        // hai ý nghĩa khác nhau tuỳ theo sàn thương mại điện tử: với Shopee và TikTok Shop nó lưu SỐ TIỀN
        // hoa hồng, nhưng với Lazada thì LazadaCashbackService lại dùng nó để lưu TỶ LỆ hoa hồng theo phần trăm.
        // Làm tròn cột này sẽ phá hỏng tỷ lệ hoa hồng của các sản phẩm Lazada (ví dụ 4,5% bị đẩy thành 5%).
        'products'              => ['price', 'commission_amount', 'cashback_amount'],
        'saved_products'        => ['price', 'cashback_amount'],
        'checkin_revenues'      => ['original_price', 'commission_amount'],
        'coupon_revenues'       => ['original_price', 'commission_amount'],
        'gifts'                 => ['price'],
        'gift_redemptions'      => ['amount'],
        'gift_codes'            => ['reward_amount', 'reward_min', 'reward_max', 'min_total_cashback'],
        'gift_code_redemptions' => ['amount'],
        'tasks'                 => ['reward_amount', 'min_order_amount'],
    ];

    /**
     * Danh sách các cột bị CẮT BỎ phần lẻ (làm tròn về phía 0), gom theo tên bảng.
     */
    private const TRUNCATE_COLUMNS = [
        'users' => ['balance', 'total_cashback', 'total_withdrawn', 'total_referral_earned'],
    ];

    public function up(): void
    {
        // SQLite does not expose MySQL's FLOOR/CEILING functions by default.
        if (DB::getDriverName() === 'sqlite') {
            $pdo = DB::connection()->getPdo();
            $pdo->sqliteCreateFunction('FLOOR', static fn ($value): float => floor((float) $value));
            $pdo->sqliteCreateFunction('CEILING', static fn ($value): float => ceil((float) $value));
        }
        // 1. Cắt bỏ phần lẻ của số dư ví và các cột tổng tích luỹ của thành viên
        foreach (self::TRUNCATE_COLUMNS as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    continue;
                }

                // Dùng CEILING cho giá trị âm để phép làm tròn luôn tiến về 0, không làm số tiền phình to hơn giá trị gốc.
                // Viết bằng CASE WHEN thay cho hàm IF() của MySQL để câu lệnh chạy được trên cả SQLite của môi trường kiểm thử.
                DB::statement(
                    "UPDATE `{$table}` SET `{$column}` = CASE WHEN `{$column}` < 0 THEN CEILING(`{$column}`) ELSE FLOOR(`{$column}`) END WHERE `{$column}` <> FLOOR(`{$column}`)"
                );
            }
        }

        // 2. Làm tròn các cột tiền của những bảng lịch sử và bảng dữ liệu tham chiếu
        foreach (self::ROUND_COLUMNS as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::statement("UPDATE `{$table}` SET `{$column}` = ROUND(`{$column}`, 0) WHERE `{$column}` <> ROUND(`{$column}`, 0)");
            }
        }

        // 3. Chuẩn hoá bảng yêu cầu rút tiền, đảm bảo ràng buộc: thực nhận = số tiền rút - phí
        //
        // Ba cột được cập nhật trong CÙNG một câu lệnh, với điều kiện lọc chỉ đúng những phiếu rút thật sự
        // đang chứa phần thập phân. Lý do bắt buộc phải gộp và lọc chặt như vậy: nếu tính lại real_amount
        // bằng một câu lệnh riêng với điều kiện "real_amount <> amount - fee" thì những phiếu rút cũ phát sinh
        // trước khi hệ thống bổ sung hai cột fee và real_amount (các phiếu này đang mang giá trị mặc định 0)
        // cũng sẽ bị ghi đè, tức là migration tự ý sửa dữ liệu lịch sử nằm ngoài phạm vi làm sạch phần lẻ.
        //
        // Vế phải của real_amount viết lại tường minh bằng ROUND thay vì tham chiếu hai cột vừa gán, bởi MySQL
        // gán các cột theo thứ tự từ trái sang phải (đọc được giá trị mới) còn SQLite thì đọc giá trị cũ.
        // Cách viết này cho ra kết quả giống nhau trên cả hai hệ quản trị cơ sở dữ liệu.
        if (Schema::hasTable('withdrawals') && Schema::hasColumn('withdrawals', 'fee') && Schema::hasColumn('withdrawals', 'real_amount')) {
            DB::statement(
                "UPDATE `withdrawals` SET
                    `amount`      = ROUND(`amount`, 0),
                    `fee`         = ROUND(`fee`, 0),
                    `real_amount` = ROUND(`amount`, 0) - ROUND(`fee`, 0)
                 WHERE `amount` <> ROUND(`amount`, 0)
                    OR `fee` <> ROUND(`fee`, 0)
                    OR `real_amount` <> ROUND(`real_amount`, 0)"
            );
        } elseif (Schema::hasTable('withdrawals')) {
            // Trường hợp cơ sở dữ liệu chưa có hai cột phí thì chỉ cần làm tròn số tiền rút
            DB::statement("UPDATE `withdrawals` SET `amount` = ROUND(`amount`, 0) WHERE `amount` <> ROUND(`amount`, 0)");
        }

        // 4. Chuẩn hoá nhật ký biến động số dư, giữ vững ràng buộc: số dư trước + biến động = số dư sau
        //
        // Cũng gộp vào một câu lệnh với điều kiện lọc chỉ các dòng đang chứa phần thập phân, để migration
        // không âm thầm ghi đè những dòng nhật ký vốn đã là số nguyên nhưng lệch công thức vì lý do khác.
        // Nếu tồn tại các dòng lệch như vậy thì đó là dấu hiệu của một lỗi riêng cần được điều tra,
        // không phải việc mà migration làm sạch phần lẻ này được phép che đi.
        if (Schema::hasTable('balance_logs')) {
            DB::statement(
                "UPDATE `balance_logs` SET
                    `amount_before` = CASE WHEN `amount_before` < 0 THEN CEILING(`amount_before`) ELSE FLOOR(`amount_before`) END,
                    `amount_after`  = CASE WHEN `amount_after` < 0 THEN CEILING(`amount_after`) ELSE FLOOR(`amount_after`) END,
                    `amount_change` = (CASE WHEN `amount_after` < 0 THEN CEILING(`amount_after`) ELSE FLOOR(`amount_after`) END)
                                    - (CASE WHEN `amount_before` < 0 THEN CEILING(`amount_before`) ELSE FLOOR(`amount_before`) END)
                 WHERE `amount_before` <> FLOOR(`amount_before`)
                    OR `amount_after` <> FLOOR(`amount_after`)
                    OR `amount_change` <> FLOOR(`amount_change`)"
            );
        }
    }

    /**
     * Không thể khôi phục lại phần thập phân đã bị cắt bỏ vì giá trị gốc không được lưu ở đâu khác.
     * Việc rollback vì vậy chỉ là thao tác rỗng, dữ liệu vẫn giữ nguyên ở dạng số nguyên đồng.
     */
    public function down(): void
    {
        // Không có hành động khôi phục
    }
};
