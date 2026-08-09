<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Dịch vụ dựng dữ liệu có cấu trúc (JSON-LD Schema.org) cho trang chủ.
 *
 * Vì sao cần tập trung tại đây thay vì để mỗi block tự in schema riêng:
 * - Google chỉ chấp nhận DUY NHẤT một khối FAQPage trên mỗi URL. Trước đây block FAQ tự in
 *   schema của mình, nên khi Admin thêm 2 block FAQ trở lên ở /admin/appearance thì trang chủ
 *   sẽ có 2 thẻ FAQPage trùng lặp và bị Google Search Console báo lỗi, mất rich snippet.
 * - Gom toàn bộ thực thể (Organization, WebSite, WebPage, FAQPage, HowTo, BreadcrumbList) vào
 *   một mảng "@graph" duy nhất giúp các thực thể liên kết với nhau qua "@id", đây là cách khai
 *   báo được Google khuyến nghị và cho kết quả hiểu ngữ nghĩa tốt nhất.
 */
class HomeSchemaService
{
    /**
     * Dựng toàn bộ đồ thị dữ liệu có cấu trúc cho trang chủ.
     *
     * @param Collection $blocks Danh sách block của trình dựng trang (đã sắp thứ tự)
     * @param string $siteName Tên website
     * @param string $siteDescription Mô tả website
     * @param string|null $siteLogo Đường dẫn logo website
     * @return string Chuỗi JSON đã mã hoá an toàn để nhúng vào thẻ <script type="application/ld+json">
     */
    public function build(Collection $blocks, string $siteName, string $siteDescription, ?string $siteLogo = null): string
    {
        $home = url('/');
        $orgId  = $home . '#organization';
        $siteId = $home . '#website';
        $pageId = $home . '#webpage';

        // Chỉ lấy các block đang bật — block đã tắt không hiển thị nên không được khai báo schema
        $activeBlocks = $blocks->filter(fn($b) => !empty($b->enabled))->values();

        $graph = [
            $this->organization($orgId, $home, $siteName, $siteLogo),
            $this->website($siteId, $orgId, $home, $siteName, $siteDescription),
            $this->webPage($pageId, $siteId, $orgId, $home, $siteName, $siteDescription),
            $this->breadcrumb($home, $siteName),
        ];

        // FAQPage: gom câu hỏi của TẤT CẢ block FAQ đang bật thành một thực thể duy nhất
        if ($faq = $this->faqPage($activeBlocks, $pageId)) {
            $graph[] = $faq;
        }

        // HowTo: mô tả quy trình nhận hoàn tiền lấy từ block "Hướng dẫn 3 bước"
        if ($howTo = $this->howTo($activeBlocks, $siteName)) {
            $graph[] = $howTo;
        }

        return json_encode(
            ['@' . 'context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_PRETTY_PRINT
        );
    }

    /**
     * Thực thể Organization: thông tin doanh nghiệp, logo, kênh mạng xã hội và kênh hỗ trợ.
     */
    protected function organization(string $orgId, string $home, string $siteName, ?string $siteLogo): array
    {
        $org = [
            '@type' => 'Organization',
            '@id'   => $orgId,
            'name'  => $siteName,
            'url'   => $home,
            'logo'  => [
                '@type' => 'ImageObject',
                'url'   => !empty($siteLogo) ? $siteLogo : asset('assets/images/logo.png'),
            ],
        ];

        // sameAs: danh sách kênh mạng xã hội chính thức giúp Google xác thực thương hiệu
        $socials = array_values(array_filter([
            Setting::getVal('facebook_link'),
            Setting::getVal('youtube_link'),
            Setting::getVal('tiktok_link'),
            Setting::getVal('telegram_link'),
            Setting::getVal('zalo_link'),
        ], fn($link) => !empty($link) && preg_match('#^https?://#i', (string) $link)));

        if (!empty($socials)) {
            $org['sameAs'] = $socials;
        }

        // contactPoint: số hotline hỗ trợ khách hàng (nếu Admin đã cấu hình)
        $hotline = trim((string) Setting::getVal('support_hotline'));
        if ($hotline !== '') {
            $org['contactPoint'] = [
                '@type'             => 'ContactPoint',
                'contactType'       => 'customer support',
                'telephone'         => $hotline,
                'areaServed'        => 'VN',
                'availableLanguage' => ['Vietnamese'],
            ];
        }

        return $org;
    }

    /**
     * Thực thể WebSite: định danh website và ngôn ngữ chính.
     */
    protected function website(string $siteId, string $orgId, string $home, string $siteName, string $siteDescription): array
    {
        return [
            '@type'       => 'WebSite',
            '@id'         => $siteId,
            'url'         => $home,
            'name'        => $siteName,
            'description' => $siteDescription,
            'publisher'   => ['@id' => $orgId],
            'inLanguage'  => str_replace('_', '-', app()->getLocale() === 'vi' ? 'vi-VN' : app()->getLocale()),
        ];
    }

    /**
     * Thực thể WebPage: mô tả chính trang chủ và liên kết ngược về WebSite/Organization.
     */
    protected function webPage(string $pageId, string $siteId, string $orgId, string $home, string $siteName, string $siteDescription): array
    {
        return [
            '@type'       => 'WebPage',
            '@id'         => $pageId,
            'url'         => $home,
            'name'        => $siteName,
            'description' => $siteDescription,
            'isPartOf'    => ['@id' => $siteId],
            'about'       => ['@id' => $orgId],
            'inLanguage'  => app()->getLocale() === 'vi' ? 'vi-VN' : app()->getLocale(),
        ];
    }

    /**
     * Thực thể BreadcrumbList: đường dẫn phân cấp (trang chủ là cấp 1).
     */
    protected function breadcrumb(string $home, string $siteName): array
    {
        return [
            '@type'           => 'BreadcrumbList',
            '@id'             => $home . '#breadcrumb',
            'itemListElement' => [[
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => __('Trang chủ'),
                'item'     => $home,
            ]],
        ];
    }

    /**
     * Thực thể FAQPage gom từ mọi block FAQ đang bật và hai modal hướng dẫn trong Hero.
     * Chỉ nhận câu hỏi có đủ cả nội dung hỏi lẫn đáp (Google từ chối câu hỏi thiếu câu trả lời)
     * và loại bỏ câu hỏi trùng lặp giữa các block.
     *
     * @return array|null null nếu không có câu hỏi hợp lệ nào
     */
    protected function faqPage(Collection $blocks, string $pageId): ?array
    {
        $questions = [];
        $seen = [];

        $cleanText = static function ($value): string {
            $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return trim((string) preg_replace('/\s+/u', ' ', $text));
        };

        $addQuestion = static function ($question, $answer) use (&$questions, &$seen, $cleanText): void {
            $question = $cleanText($question);
            $answer = $cleanText($answer);

            if ($question === '' || $answer === '') {
                return;
            }

            $fingerprint = mb_strtolower($question, 'UTF-8');
            if (isset($seen[$fingerprint])) {
                return;
            }
            $seen[$fingerprint] = true;

            $questions[] = [
                '@type'          => 'Question',
                'name'           => $question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
            ];
        };

        foreach ($blocks->where('type', 'faq') as $block) {
            foreach ((array) ($block->settings['items'] ?? []) as $item) {
                $addQuestion($item['question'] ?? '', $item['answer'] ?? '');
            }
        }

        // Hai modal trong Hero là nội dung FAQ hiển thị khi người dùng bấm mở.
        if ($hero = $blocks->firstWhere('type', 'hero')) {
            $settings = (array) ($hero->settings ?? []);

            if (($settings['hp_show_demo_modal'] ?? '1') === '1') {
                $addQuestion(
                    __('Bạn chưa biết cách lấy link?'),
                    __('Mở sản phẩm trên Shopee, nhấn Chia sẻ và sao chép liên kết. Quay lại Mê Sale, dán liên kết vào ô lấy link hoàn tiền rồi kiểm tra để tiếp tục mua hàng.')
                );
            }

            if (($settings['hp_show_notice_modal'] ?? '1') === '1') {
                $addQuestion(
                    __('Cần lưu ý gì khi sử dụng?'),
                    $settings['hp_notice_modal_content'] ?? ''
                );
            }
        }

        if (empty($questions)) {
            return null;
        }

        return [
            '@type'      => 'FAQPage',
            '@id'        => url('/') . '#faq',
            'isPartOf'   => ['@id' => $pageId],
            'mainEntity' => $questions,
        ];
    }

    /**
     * Thực thể HowTo dựng từ block "Hướng dẫn 3 bước" — mô tả quy trình nhận hoàn tiền.
     * Giúp công cụ tìm kiếm hiểu trang chủ có hướng dẫn thao tác cụ thể theo từng bước.
     *
     * @return array|null null nếu block không bật hoặc không có bước nào có tiêu đề
     */
    protected function howTo(Collection $blocks, string $siteName): ?array
    {
        $block = $blocks->firstWhere('type', 'steps');
        if (!$block) {
            return null;
        }

        $settings = (array) ($block->settings ?? []);
        $steps = [];

        for ($i = 1; $i <= 3; $i++) {
            $title = trim(strip_tags((string) ($settings["hp_step_{$i}_title"] ?? '')));
            if ($title === '') {
                continue;
            }

            $step = [
                '@type'    => 'HowToStep',
                'position' => count($steps) + 1,
                'name'     => $title,
                'url'      => url('/') . '#buoc-' . $i,
            ];

            $desc = trim(strip_tags((string) ($settings["hp_step_{$i}_desc"] ?? '')));
            if ($desc !== '') {
                $step['text'] = $desc;
            }

            $steps[] = $step;
        }

        if (count($steps) < 2) {
            return null;
        }

        $howTo = [
            '@type' => 'HowTo',
            '@id'   => url('/') . '#howto',
            'name'  => trim(strip_tags((string) ($settings['hp_steps_title'] ?? ''))) ?: __('Cách nhận hoàn tiền tại :site', ['site' => $siteName]),
            'step'  => $steps,
        ];

        $subtitle = trim(strip_tags((string) ($settings['hp_steps_subtitle'] ?? '')));
        if ($subtitle !== '') {
            $howTo['description'] = $subtitle;
        }

        return $howTo;
    }
}
