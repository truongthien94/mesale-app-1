<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Chuyển dữ liệu giao diện homepage hiện có (các key hp_* trong bảng settings)
 * sang bảng page_blocks để trình dựng trang mới hoạt động mà KHÔNG làm thay đổi
 * hiển thị trang chủ. Idempotent: chỉ seed khi page_blocks (home) đang rỗng.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Chỉ seed một lần để tránh trùng lặp khi chạy lại migration trên môi trường khách hàng
        if (DB::table('page_blocks')->where('page', 'home')->exists()) {
            return;
        }

        $s = DB::table('settings')->pluck('value', 'key')->toArray();
        $get = fn($key, $default = '') => $s[$key] ?? $default;

        // Các nhóm key thuộc từng loại block hệ thống
        $pick = function (array $keys) use ($s) {
            $out = [];
            foreach ($keys as $k) {
                if (array_key_exists($k, $s)) {
                    $out[$k] = $s[$k];
                }
            }
            return $out;
        };

        $now = now();
        $rows = [];
        $order = 0;

        // ---------- HERO (luôn đứng đầu) ----------
        $rows[] = [
            'page' => 'home',
            'type' => 'hero',
            'name' => 'Hero Section',
            'settings' => json_encode($pick([
                'hp_hero_layout', 'hp_hero_badge', 'hp_hero_title_1', 'hp_hero_title_2', 'hp_hero_title_3',
                'hp_hero_description', 'hp_hero_placeholder', 'hp_hero_btn_text',
                'hp_hero_right_type', 'hp_hero_right_video_url', 'hp_hero_right_video_poster',
                'hp_show_demo_modal', 'hp_demo_modal_type', 'hp_demo_modal_custom_content',
                'hp_show_notice_modal', 'hp_notice_modal_content',
                'hp_stats_title', 'hp_stats_clicks_label', 'hp_stats_users_label', 'hp_stats_paid_label',
                'hp_stats_clicks_value', 'hp_stats_users_value', 'hp_stats_paid_value',
            ]), JSON_UNESCAPED_UNICODE),
            'sort_order' => $order++,
            'enabled' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // ---------- Các section thân trang theo thứ tự đã lưu ----------
        $sectionOrder = json_decode($get('hp_sections_order', '["hp_show_timeline","hp_show_steps","hp_show_features","hp_show_coupons","hp_show_blog"]'), true) ?: [];
        $customMap = collect(json_decode($get('hp_custom_sections', '[]'), true) ?: [])->keyBy('id')->toArray();

        $systemBlocks = [
            'hp_show_timeline' => ['type' => 'timeline', 'name' => 'Quy trình hoàn tiền', 'keys' => [
                'hp_timeline_badge', 'hp_timeline_title', 'hp_timeline_subtitle',
                'hp_timeline_step_1_badge', 'hp_timeline_step_1_title', 'hp_timeline_step_1_tag', 'hp_timeline_step_1_desc',
                'hp_timeline_step_2_badge', 'hp_timeline_step_2_title', 'hp_timeline_step_2_tag', 'hp_timeline_step_2_desc',
                'hp_timeline_step_3_badge', 'hp_timeline_step_3_title', 'hp_timeline_step_3_tag', 'hp_timeline_step_3_desc',
            ]],
            'hp_show_steps' => ['type' => 'steps', 'name' => 'Hướng dẫn 3 bước', 'keys' => [
                'hp_steps_title', 'hp_steps_subtitle',
                'hp_step_1_title', 'hp_step_1_icon', 'hp_step_1_desc',
                'hp_step_2_title', 'hp_step_2_icon', 'hp_step_2_desc',
                'hp_step_3_title', 'hp_step_3_icon', 'hp_step_3_desc',
            ]],
            'hp_show_features' => ['type' => 'features', 'name' => 'Điểm nổi bật', 'keys' => [
                'hp_features_title', 'hp_features_subtitle',
                'hp_feature_1_title', 'hp_feature_1_icon', 'hp_feature_1_color', 'hp_feature_1_desc',
                'hp_feature_2_title', 'hp_feature_2_icon', 'hp_feature_2_color', 'hp_feature_2_desc',
                'hp_feature_3_title', 'hp_feature_3_icon', 'hp_feature_3_color', 'hp_feature_3_desc',
                'hp_feature_4_title', 'hp_feature_4_icon', 'hp_feature_4_color', 'hp_feature_4_desc',
            ]],
            'hp_show_coupons' => ['type' => 'coupons', 'name' => 'Mã giảm giá Shopee', 'keys' => []],
            'hp_show_blog' => ['type' => 'blog', 'name' => 'Tin tức & Blog', 'keys' => []],
        ];

        foreach ($sectionOrder as $key) {
            if (isset($systemBlocks[$key])) {
                $def = $systemBlocks[$key];
                $rows[] = [
                    'page' => 'home',
                    'type' => $def['type'],
                    'name' => $def['name'],
                    'settings' => json_encode($pick($def['keys']), JSON_UNESCAPED_UNICODE),
                    'sort_order' => $order++,
                    'enabled' => ($get($key, '1') === '1'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            } elseif (isset($customMap[$key])) {
                // Section HTML/TEXT tùy chỉnh do Admin tạo trước đây
                $cs = $customMap[$key];
                $rows[] = [
                    'page' => 'home',
                    'type' => 'html',
                    'name' => $cs['title'] ?? 'Section tùy chỉnh',
                    'settings' => json_encode([
                        'title' => $cs['title'] ?? '',
                        'content' => $cs['content'] ?? '',
                    ], JSON_UNESCAPED_UNICODE),
                    'sort_order' => $order++,
                    'enabled' => !empty($cs['enabled']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('page_blocks')->insert($rows);
    }

    public function down(): void
    {
        // Không xoá dữ liệu để tránh mất nội dung; page_blocks sẽ bị xoá cùng bảng nếu rollback migration tạo bảng.
    }
};
