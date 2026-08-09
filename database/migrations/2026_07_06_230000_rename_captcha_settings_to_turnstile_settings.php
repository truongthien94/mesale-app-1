<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Chạy migration để đổi tên các khóa cấu hình từ captcha_* sang turnstile_*.
     * Giải thích: Chuyển đổi các khóa cấu hình cũ sang định dạng turnstile_* mới để khớp với logic kiểm tra của hệ thống,
     * tránh việc sếp Thành cấu hình xong nhưng ngoài client không nhận diện được do lệch tên key.
     */
    public function up(): void
    {
        $mapping = [
            'captcha_status'      => 'turnstile_status',
            'captcha_on_login'    => 'turnstile_on_login',
            'captcha_on_register' => 'turnstile_on_register',
            'captcha_on_forgot'   => 'turnstile_on_forgot_password',
            'captcha_on_withdraw' => 'turnstile_on_withdraw',
            'captcha_site_key'    => 'turnstile_site_key',
            'captcha_secret_key'  => 'turnstile_secret_key',
        ];

        foreach ($mapping as $old => $new) {
            // Kiểm tra xem khóa cũ có tồn tại hay không
            $exists = DB::table('settings')->where('key', $old)->first();
            if ($exists) {
                // Kiểm tra xem khóa mới đã tồn tại chưa để tránh lỗi trùng lặp khóa
                $newExists = DB::table('settings')->where('key', $new)->exists();
                if (!$newExists) {
                    DB::table('settings')->where('key', $old)->update(['key' => $new]);
                } else {
                    // Nếu khóa mới đã tồn tại rồi thì xóa bản ghi cấu hình cũ thừa đi
                    DB::table('settings')->where('key', $old)->delete();
                }
            }
        }
    }

    /**
     * Hoàn tác migration đổi tên.
     */
    public function down(): void
    {
        $mapping = [
            'turnstile_status'             => 'captcha_status',
            'turnstile_on_login'           => 'captcha_on_login',
            'turnstile_on_register'        => 'captcha_on_register',
            'turnstile_on_forgot_password' => 'captcha_on_forgot',
            'turnstile_on_withdraw'        => 'captcha_on_withdraw',
            'turnstile_site_key'           => 'captcha_site_key',
            'turnstile_secret_key'         => 'captcha_secret_key',
        ];

        foreach ($mapping as $old => $new) {
            $exists = DB::table('settings')->where('key', $old)->first();
            if ($exists) {
                $newExists = DB::table('settings')->where('key', $new)->exists();
                if (!$newExists) {
                    DB::table('settings')->where('key', $old)->update(['key' => $new]);
                } else {
                    DB::table('settings')->where('key', $old)->delete();
                }
            }
        }
    }
};
