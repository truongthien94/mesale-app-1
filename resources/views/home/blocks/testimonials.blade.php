{{-- Block Đánh giá thành viên (Testimonials) — social proof, hỗ trợ kiểu lưới hoặc trượt ngang --}}
@php
    use App\Helpers\LinkHelper;

    $reviews = collect($s['items'] ?? [])
        ->filter(fn($it) => !empty(trim($it['content'] ?? '')) || !empty(trim($it['name'] ?? '')))
        ->values();
    $reviewLayout = $s['layout'] ?? 'grid';
    $reviewHeadingId = 'reviews-' . substr(md5(($s['title'] ?? '') . $reviews->count()), 0, 8);
@endphp
@if($reviews->count() > 0)
<section @if(!empty($s['title'])) aria-labelledby="{{ $reviewHeadingId }}" @endif class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 pt-16 md:pt-24 border-t border-gray-100/60 dark:border-slate-800/40">
    <div class="bg-gradient-to-br from-white/90 to-amber-50/45 dark:from-slate-900/60 dark:to-slate-900/20 p-8 md:p-12 rounded-[32px] border border-amber-100/50 dark:border-slate-800/60 shadow-sm">
        {{-- Tiêu đề khu vực --}}
        <div class="text-center max-w-2xl mx-auto mb-10 md:mb-12">
            @if(!empty($s['badge']))
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400">
                <i data-lucide="heart" class="w-3.5 h-3.5"></i>
                {{ $s['badge'] }}
            </span>
            @endif
            @if(!empty($s['title']))
            <h2 id="{{ $reviewHeadingId }}" class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white mt-3 leading-tight">{{ $s['title'] }}</h2>
            @endif
            @if(!empty($s['subtitle']))
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-2">{{ $s['subtitle'] }}</p>
            @endif
        </div>

    @php
        // Bảng màu luân phiên cho avatar chữ cái đầu (khi không có ảnh)
        $avatarColors = ['bg-orange-500', 'bg-green-500', 'bg-blue-500', 'bg-purple-500', 'bg-pink-500', 'bg-amber-500'];
    @endphp

    <div class="{{ $reviewLayout === 'slider'
        ? 'flex gap-5 overflow-x-auto snap-x snap-mandatory pb-4 -mx-4 px-4 sm:mx-0 sm:px-0 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden'
        : 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6' }}">
        @foreach($reviews as $index => $item)
        @php
            $rating = (int) ($item['rating'] ?? 5);
            $rating = max(1, min(5, $rating ?: 5));
            $initial = mb_strtoupper(mb_substr(trim($item['name'] ?? 'U'), 0, 1, 'UTF-8'), 'UTF-8');
            $avatarColor = $avatarColors[$index % count($avatarColors)];
        @endphp
        <figure class="{{ $reviewLayout === 'slider' ? 'snap-start shrink-0 w-[300px] sm:w-[340px]' : '' }} bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-md dark:shadow-none border border-gray-150 dark:border-slate-800/80 flex flex-col hover:shadow-lg transition-all duration-300">
            {{-- Số sao đánh giá (aria-label để trình đọc màn hình đọc được điểm số) --}}
            <div class="flex items-center gap-0.5 mb-3" role="img" aria-label="{{ __(':rating trên 5 sao', ['rating' => $rating]) }}">
                @for($star = 1; $star <= 5; $star++)
                <i data-lucide="star" class="w-4 h-4 {{ $star <= $rating ? 'text-amber-400 fill-amber-400' : 'text-gray-200 dark:text-slate-700' }}" aria-hidden="true"></i>
                @endfor
            </div>

            {{-- Nội dung đánh giá --}}
            <blockquote class="text-sm text-gray-600 dark:text-slate-300 leading-relaxed flex-grow">
                “{{ trim($item['content'] ?? '') }}”
            </blockquote>

            {{-- Thông tin người đánh giá --}}
            <figcaption class="flex items-center gap-3 mt-5 pt-4 border-t border-gray-100 dark:border-slate-800">
                @php $avatarUrl = \App\Helpers\LinkHelper::safe($item['avatar'] ?? ''); @endphp
                @if($avatarUrl !== '')
                <img src="{{ $avatarUrl }}" alt="{{ __('Ảnh đại diện của :name', ['name' => trim($item['name'] ?? '') ?: __('thành viên')]) }}" width="44" height="44" class="w-11 h-11 rounded-full object-cover shrink-0" loading="lazy" decoding="async">
                @else
                <div class="w-11 h-11 rounded-full {{ $avatarColor }} text-white font-bold flex items-center justify-center shrink-0">{{ $initial }}</div>
                @endif
                <div class="min-w-0">
                    <span class="text-sm font-bold text-gray-900 dark:text-white block truncate">{{ $item['name'] ?? '' }}</span>
                    @if(!empty($item['role']))
                    <span class="text-xs text-gray-400 dark:text-slate-500 block truncate">{{ $item['role'] }}</span>
                    @endif
                </div>
            </figcaption>
        </figure>
        @endforeach
        </div>
    </div>
</section>
@endif
