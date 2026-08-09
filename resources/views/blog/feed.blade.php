<?= '<?xml version="1.0" encoding="UTF-8"?>' ?>
<rss version="2.0"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:wfw="http://wellformedweb.org/CommentAPI/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:atom="http://www.w3.org/2005/Atom"
    xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
    xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
    xmlns:georss="http://www.georss.org/georss"
    xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#"
>
    <channel>
        <title>{{ \App\Models\Setting::getVal('site_name', config('app.name')) }} - {{ __('Blog Tin Tức & Khuyến Mãi') }}</title>
        <atom:link href="{{ url('/blog/feed') }}" rel="self" type="application/rss+xml" />
        <link>{{ url('/blog') }}</link>
        <description>{{ __('Cập nhật tin tức khuyến mãi Shopee, kinh nghiệm mua sắm hoàn tiền thông minh') }}</description>
        <lastBuildDate>{{ $lastBuildDate->toRssString() }}</lastBuildDate>
        <language>vi-VN</language>
        <sy:updatePeriod>hourly</sy:updatePeriod>
        <sy:updateFrequency>1</sy:updateFrequency>
        <generator>Laravel Blog CMS</generator>

        @foreach($posts as $post)
            <item>
                <title><![CDATA[{{ $post->title }}]]></title>
                <link>{{ route('blog.show', $post->slug) }}</link>
                <pubDate>{{ $post->published_at ? $post->published_at->toRssString() : $post->created_at->toRssString() }}</pubDate>
                <dc:creator><![CDATA[{{ $post->author->name ?? 'Admin' }}]]></dc:creator>
                <category><![CDATA[{{ $post->category->name ?? 'Tin tức chung' }}]]></category>
                <guid isPermaLink="false">{{ route('blog.show', $post->slug) }}</guid>
                <description><![CDATA[{{ $post->summary }}]]></description>
                <content:encoded><![CDATA[{!! $post->content !!}]]></content:encoded>
                @if($post->thumbnail)
                    <enclosure url="{{ asset($post->thumbnail) }}" length="12345" type="image/jpeg" />
                @endif
            </item>
        @endforeach
    </channel>
</rss>
