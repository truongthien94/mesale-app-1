<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashbackClick extends Model
{
    use HasFactory;

    /**
     * Tên bảng tương ứng trong cơ sở dữ liệu.
     *
     * @var string
     */
    protected $table = 'cashback_clicks';

    /**
     * Các trường được phép gán dữ liệu hàng loạt (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'platform',
        'trans_id',
        'product_name',
        'product_image',
        'original_price',
        'cashback_amount',
        'cashback_rate',
        'commission_amount',
        'affiliate_url',
    ];

    /**
     * Định nghĩa mối quan hệ với model User.
     * Một lượt click hoàn tiền thuộc về một người dùng cụ thể.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Định nghĩa mối quan hệ với model CashbackHistory.
     * Một lượt click hoàn tiền có thể liên kết với một đơn hàng hoàn tiền thực tế thông qua cột trans_id.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cashbackHistory()
    {
        return $this->hasOne(CashbackHistory::class, 'trans_id', 'trans_id');
    }

    /**
     * Lấy liên kết rút gọn (ShortLink) tương ứng từ affiliate_url của lượt click.
     * Dùng để hiển thị số lượt click thực tế của link ở màn hình lịch sử hoàn tiền.
     *
     * @return \App\Models\ShortLink|null
     */
    public function getShortLink()
    {
        // Nếu không có link affiliate thì trả về null
        if (!$this->affiliate_url) {
            return null;
        }

        // Trích xuất mã rút gọn (code) nằm ở cuối đường dẫn url
        $code = basename($this->affiliate_url);

        // Truy vấn bảng short_links để lấy thông tin click tracking
        return ShortLink::where('code', $code)->first();
    }
}
