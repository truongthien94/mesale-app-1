<div class="space-y-3">
    @forelse($notifications as $noti)
        @php
            $isUnread = ! $noti->is_read;
            $notiData = [
                'id'            => $noti->id,
                'title'         => $noti->title,
                'content'       => $noti->content,
                'type'          => $tab,
                'is_read'       => $noti->is_read,
                'created_at'    => $noti->created_at->format('d/m/Y H:i'),
                'created_human' => $noti->created_at->diffForHumans(),
            ];
        @endphp
        <div data-noti-id="{{ $noti->id }}"
             @click="openDetail({{ \Illuminate\Support\Js::from($notiData) }})"
             class="group relative overflow-hidden flex items-start gap-4 p-4 sm:p-5 rounded-2xl border transition-all duration-200 cursor-pointer active:scale-[0.99] active:opacity-95 select-none
                    {{ $isUnread
                        ? 'bg-white dark:bg-slate-900 border-shopee/20 dark:border-shopee/30 shadow-sm hover:shadow-md'
                        : 'bg-gray-50/60 dark:bg-slate-900/40 border-gray-100 dark:border-slate-800/60 hover:bg-white dark:hover:bg-slate-900' }}">

            {{-- Thanh nhấn màu cho thông báo chưa đọc --}}
            @if($isUnread)
                <span class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-shopee to-shopee-light rounded-r"></span>
            @endif

            {{-- Biểu tượng --}}
            <div class="shrink-0 mt-0.5 w-11 h-11 flex items-center justify-center rounded-2xl transition-colors
                        {{ $isUnread
                            ? 'bg-gradient-to-tr from-shopee/15 to-shopee-light/15 text-shopee dark:from-shopee/25 dark:to-shopee-light/25 dark:text-shopee-light'
                            : 'bg-gray-100 dark:bg-slate-800 text-gray-400 dark:text-slate-500' }}">
                <i data-lucide="{{ $tab === 'general' ? 'megaphone' : 'bell' }}" class="w-5 h-5"></i>
            </div>

            {{-- Nội dung --}}
            <div class="flex-grow min-w-0 space-y-1.5">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 flex-wrap min-w-0">
                        <h3 class="text-sm font-bold truncate {{ $isUnread ? 'text-gray-900 dark:text-white' : 'text-gray-600 dark:text-slate-300' }}">
                            {{ $noti->title }}
                        </h3>
                        @if($isUnread)
                            <span class="unread-badge inline-flex items-center gap-1 px-1.5 py-0.5 text-[9px] font-bold rounded-md bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-900/30">
                                <span class="w-1 h-1 rounded-full bg-red-500 animate-pulse"></span>
                                {{ __('Mới') }}
                            </span>
                        @endif
                    </div>

                    @if($isUnread)
                        <button type="button"
                                @click.stop="markSingleAsRead({{ $noti->id }})"
                                class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold text-shopee bg-shopee/5 hover:bg-shopee/10 border border-shopee/20 transition-all cursor-pointer sm:opacity-0 sm:group-hover:opacity-100"
                                title="{{ __('Đánh dấu đã đọc') }}">
                            <i data-lucide="check" class="w-3 h-3"></i>
                            <span class="hidden sm:inline">{{ __('Đã đọc') }}</span>
                        </button>
                    @endif
                </div>

                <p class="text-xs leading-relaxed line-clamp-2 break-words {{ $isUnread ? 'text-gray-600 dark:text-slate-300' : 'text-gray-500 dark:text-slate-400' }}">
                    {{ $noti->content }}
                </p>

                <div class="flex items-center justify-between gap-2 pt-1">
                    <div class="flex items-center gap-1.5 text-[10px] text-gray-400 dark:text-slate-500">
                        <i data-lucide="clock" class="w-3 h-3"></i>
                        <span>{{ $noti->created_at->format('d/m/Y H:i') }}</span>
                        <span class="text-gray-300 dark:text-slate-600">•</span>
                        <span>{{ $noti->created_at->diffForHumans() }}</span>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-shopee/70 dark:text-shopee-light/70 shrink-0 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                        {{ __('Xem chi tiết') }}
                        <i data-lucide="chevron-right" class="w-3 h-3"></i>
                    </span>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 py-16 px-6 text-center">
            <div class="w-20 h-20 mx-auto mb-4 flex items-center justify-center rounded-full bg-gray-50 dark:bg-slate-800/60 text-gray-300 dark:text-slate-600">
                <i data-lucide="{{ ($filter ?? 'all') === 'unread' ? 'check-check' : 'mail-check' }}" class="w-10 h-10"></i>
            </div>
            @if(($filter ?? 'all') === 'unread')
                <h3 class="text-sm font-bold text-gray-700 dark:text-slate-200">{{ __('Bạn đã đọc hết thông báo') }}</h3>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">{{ __('Không còn thông báo nào chưa đọc trong mục này.') }}</p>
            @else
                <h3 class="text-sm font-bold text-gray-700 dark:text-slate-200">{{ __('Chưa có thông báo nào') }}</h3>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">{{ __('Hộp thư của bạn hiện đang trống. Các thông báo mới sẽ xuất hiện tại đây.') }}</p>
            @endif
        </div>
    @endforelse
</div>

<!-- Phân trang -->
@if($notifications->hasPages())
    <div class="mt-5 ajax-pagination">
        {{ $notifications->links() }}
    </div>
@endif
