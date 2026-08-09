@php
    $hpSettings = $s;
    $statsHeadingId = 'stats-' . substr(md5($hpSettings['hp_stats_title'] ?? 'stats'), 0, 8);
@endphp
{{-- Block Thống kê hệ thống (độc lập, hiển thị dạng dải 3 cột) --}}
<section @if(!empty($hpSettings['hp_stats_title'])) aria-labelledby="{{ $statsHeadingId }}" @endif class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 pt-16 md:pt-24 border-t border-gray-100/60 dark:border-slate-800/40">
    <div class="bg-gradient-to-br from-white/90 to-orange-50/45 dark:from-slate-900/60 dark:to-slate-900/20 p-6 md:p-10 rounded-[32px] border border-orange-100/50 dark:border-slate-800/60 shadow-sm">
        @if(!empty($hpSettings['hp_stats_title']))
        {{-- Dùng thẻ h2 (thay cho h4 trước đây) để không nhảy cấp tiêu đề so với h1 của khu vực Hero --}}
        <h2 id="{{ $statsHeadingId }}" class="text-center text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider flex items-center justify-center gap-1.5 mb-8">
            <i data-lucide="activity" class="w-3.5 h-3.5 text-shopee"></i>
            {{ $hpSettings['hp_stats_title'] }}
        </h2>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
            {{-- Lượt nhấp hoàn tiền --}}
            <div class="flex items-center gap-3 p-4 bg-white/70 dark:bg-slate-900/50 rounded-2xl border border-gray-100 dark:border-slate-800/80 hover:border-orange-100 dark:hover:border-slate-700 transition-all">
                <div class="w-11 h-11 rounded-xl bg-orange-50 dark:bg-orange-950/30 flex items-center justify-center text-shopee shrink-0">
                    <i data-lucide="mouse-pointer" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-slate-400 block">{{ $hpSettings['hp_stats_clicks_label'] ?? '' ?: __('Lượt nhấp hoàn tiền') }}</span>
                    <span class="text-lg font-extrabold text-gray-900 dark:text-white block">{{ number_format($totalClicks) }}</span>
                </div>
            </div>

            {{-- Thành viên hoạt động --}}
            <div class="flex items-center gap-3 p-4 bg-white/70 dark:bg-slate-900/50 rounded-2xl border border-gray-100 dark:border-slate-800/80 hover:border-orange-100 dark:hover:border-slate-700 transition-all">
                <div class="w-11 h-11 rounded-xl bg-green-50 dark:bg-green-950/30 flex items-center justify-center text-green-600 dark:text-green-400 shrink-0">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-slate-400 block">{{ $hpSettings['hp_stats_users_label'] ?? '' ?: __('Thành viên hoạt động') }}</span>
                    <span class="text-lg font-extrabold text-gray-900 dark:text-white block">{{ number_format($totalUsers) }}</span>
                </div>
            </div>

            {{-- Tổng tiền đã hoàn trả --}}
            <div class="flex items-center gap-3 p-4 bg-white/70 dark:bg-slate-900/50 rounded-2xl border border-gray-100 dark:border-slate-800/80 hover:border-orange-100 dark:hover:border-slate-700 transition-all">
                <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-950/30 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                    <i data-lucide="banknote" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-slate-400 block">{{ $hpSettings['hp_stats_paid_label'] ?? '' ?: __('Tổng hoa hồng đã chi trả') }}</span>
                    <span class="text-lg font-extrabold text-shopee block">{{ \App\Helpers\CurrencyHelper::format($totalCashbackPaid) }}</span>
                </div>
            </div>
        </div>
    </div>
</section>
