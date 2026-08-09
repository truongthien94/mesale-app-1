@extends('layouts.admin')

@section('title', __('Nhật Ký Hệ Thống') . ' - ' . $siteName)

@section('content')
{{-- Truyền dữ liệu bản ghi log đã được parse sang Alpine để lọc/tìm kiếm tức thời phía client.
     Dùng cờ HEX để escape các ký tự </script>, dấu nháy... tránh phá vỡ thẻ script hoặc lỗ hổng XSS
     khi nội dung log chứa mã HTML/script. --}}
<script>
    window.SYSTEM_LOG_ENTRIES = @json($entries, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE);
</script>

<div class="space-y-6"
     x-data="systemLogsPanel()"
     x-init="$nextTick(() => window.lucide && lucide.createIcons())">

    <!-- Tiêu đề & Hành động -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Nhật Ký Hệ Thống') }}</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">
                {{ __('Phân tích trực tiếp tệp laravel.log: tách bản ghi theo cấp độ, lọc nhanh và xem stack trace để chẩn đoán lỗi') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <!-- Chọn số dòng đọc -->
            <form action="{{ route('admin.logs.system') }}" method="GET" class="shrink-0">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="list" class="w-4 h-4"></i>
                    </div>
                    <select name="lines" onchange="this.form.submit()"
                            class="appearance-none pl-9 pr-8 py-2.5 text-xs font-semibold border border-gray-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee cursor-pointer">
                        @foreach($allowedLines as $opt)
                            <option value="{{ $opt }}" {{ $lines == $opt ? 'selected' : '' }}>{{ __('Đọc :n dòng', ['n' => number_format($opt)]) }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
            </form>

            <!-- Tự động làm mới -->
            <button type="button" @click="toggleAuto()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2.5 text-xs font-semibold rounded-xl border transition-all shadow-sm"
                    :class="auto ? 'bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700' : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-200 border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700'">
                <span class="inline-flex" :class="auto && 'animate-pulse'"><i data-lucide="timer" class="w-4 h-4"></i></span>
                <span x-text="auto ? '{{ __('Đang tự làm mới') }}' : '{{ __('Tự làm mới') }}'"></span>
            </button>

            <!-- Tải xuống -->
            <a href="{{ route('admin.logs.system.download') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2.5 text-xs font-semibold text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-xl transition-all shadow-sm whitespace-nowrap">
                <i data-lucide="download" class="w-4 h-4"></i>
                {{ __('Tải file') }}
            </a>

            <!-- Tải lại -->
            <button onclick="window.location.reload()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2.5 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md whitespace-nowrap">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                {{ __('Tải lại') }}
            </button>

            <!-- Dọn dẹp -->
            <button type="button" @click="showClearModal = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md cursor-pointer whitespace-nowrap">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                {{ __('Dọn dẹp') }}
            </button>
            <form id="clear-logs-form" action="{{ route('admin.logs.system.clear') }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>
    </div>

    <!-- Alert thông báo -->
    @if(session('success'))
        <div class="p-4 text-sm text-green-800 dark:text-green-300 rounded-2xl bg-green-50 dark:bg-green-950/30 border border-green-100 dark:border-green-900/40 flex items-center gap-2" role="alert">
            <i data-lucide="check-circle" class="w-4 h-4 text-green-600 shrink-0"></i>
            <div><span class="font-semibold">{{ __('Thành công!') }}</span> {{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 text-sm text-red-800 dark:text-red-300 rounded-2xl bg-red-50 dark:bg-red-950/30 border border-red-100 dark:border-red-900/40 flex items-center gap-2" role="alert">
            <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 shrink-0"></i>
            <div><span class="font-semibold">{{ __('Lỗi!') }}</span> {{ session('error') }}</div>
        </div>
    @endif

    <!-- Thẻ thống kê tổng quan -->
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
        @php
            $cards = [
                ['key' => 'total',   'label' => __('Tổng bản ghi'), 'icon' => 'layers',         'val' => $stats['total'],   'cls' => 'text-gray-900 dark:text-white',   'iconCls' => 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-300'],
                ['key' => 'error',   'label' => __('Lỗi'),          'icon' => 'octagon-alert',  'val' => $stats['error'],   'cls' => 'text-red-600 dark:text-red-400',   'iconCls' => 'bg-red-50 dark:bg-red-950/40 text-red-500'],
                ['key' => 'warning', 'label' => __('Cảnh báo'),     'icon' => 'triangle-alert', 'val' => $stats['warning'], 'cls' => 'text-amber-600 dark:text-amber-400', 'iconCls' => 'bg-amber-50 dark:bg-amber-950/40 text-amber-500'],
                ['key' => 'info',    'label' => __('Thông tin'),    'icon' => 'info',           'val' => $stats['info'],    'cls' => 'text-sky-600 dark:text-sky-400',   'iconCls' => 'bg-sky-50 dark:bg-sky-950/40 text-sky-500'],
                ['key' => 'debug',   'label' => __('Debug'),        'icon' => 'bug',            'val' => $stats['debug'],   'cls' => 'text-slate-600 dark:text-slate-300', 'iconCls' => 'bg-slate-100 dark:bg-slate-700 text-slate-500'],
            ];
        @endphp
        @foreach($cards as $c)
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-4 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $c['iconCls'] }}">
                    <i data-lucide="{{ $c['icon'] }}" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold text-gray-400 dark:text-slate-400 truncate">{{ $c['label'] }}</p>
                    <p class="text-xl font-extrabold {{ $c['cls'] }}">{{ number_format($c['val']) }}</p>
                </div>
            </div>
        @endforeach

        <!-- Thẻ thông tin tệp -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-4 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 bg-shopee/10 text-shopee">
                <i data-lucide="hard-drive" class="w-5 h-5"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-gray-400 dark:text-slate-400 truncate">{{ __('Dung lượng file') }}</p>
                <p class="text-xl font-extrabold text-gray-900 dark:text-white">{{ $fileInfo['size'] }}</p>
                <p class="text-[10px] text-gray-400 dark:text-slate-500">{{ __('Tối đa') }} {{ $fileInfo['maxSize'] }}</p>
            </div>
        </div>
    </div>

    <!-- Thanh meta của tệp -->
    <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-[11px] text-gray-500 dark:text-slate-400 px-1">
        <span class="inline-flex items-center gap-1.5 font-mono">
            <i data-lucide="file-text" class="w-3.5 h-3.5"></i> {{ $fileInfo['path'] }}
        </span>
        <span class="inline-flex items-center gap-1.5">
            <i data-lucide="align-justify" class="w-3.5 h-3.5"></i> {{ __('Tổng :n dòng', ['n' => number_format($fileInfo['totalLines'])]) }}
        </span>
        @if($fileInfo['modified'])
            <span class="inline-flex items-center gap-1.5">
                <i data-lucide="clock" class="w-3.5 h-3.5"></i> {{ __('Cập nhật cuối') }}: {{ $fileInfo['modified']->format('d/m/Y H:i:s') }} ({{ $fileInfo['modified']->diffForHumans() }})
            </span>
        @endif
    </div>

    @if($notice)
        <!-- Trạng thái thông báo (Demo / không có file / lỗi đọc) -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl border border-gray-200 dark:border-slate-700 p-10 text-center shadow-sm">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-amber-50 dark:bg-amber-950/30 text-amber-500 flex items-center justify-center mb-4">
                <i data-lucide="shield-alert" class="w-7 h-7"></i>
            </div>
            <p class="text-sm text-gray-600 dark:text-slate-300 max-w-xl mx-auto leading-relaxed">{{ $notice }}</p>
        </div>
    @else
        <!-- Thanh công cụ: tìm kiếm + bộ lọc cấp độ + chế độ xem -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl border border-gray-200 dark:border-slate-700 shadow-sm p-4 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center gap-3">
                <!-- Ô tìm kiếm -->
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input type="text" x-model="search"
                           placeholder="{{ __('Tìm trong nội dung, cấp độ, stack trace...') }}"
                           class="block w-full pl-10 pr-10 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs bg-gray-50/60 dark:bg-slate-900/50 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all">
                    <button x-show="search" @click="search = ''" type="button"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600" x-cloak>
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Chuyển chế độ xem thẻ / raw -->
                <div class="inline-flex p-1 bg-gray-100 dark:bg-slate-900 rounded-xl shrink-0 self-start">
                    <button type="button" @click="view = 'cards'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all"
                            :class="view === 'cards' ? 'bg-white dark:bg-slate-700 text-shopee shadow-sm' : 'text-gray-500 dark:text-slate-400'">
                        <i data-lucide="layout-list" class="w-3.5 h-3.5"></i> {{ __('Bản ghi') }}
                    </button>
                    <button type="button" @click="view = 'raw'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all"
                            :class="view === 'raw' ? 'bg-white dark:bg-slate-700 text-shopee shadow-sm' : 'text-gray-500 dark:text-slate-400'">
                        <i data-lucide="terminal" class="w-3.5 h-3.5"></i> {{ __('Thô') }}
                    </button>
                </div>
            </div>

            <!-- Chip lọc theo cấp độ -->
            <div class="flex flex-wrap items-center gap-2">
                @php
                    $chips = [
                        ['k' => 'all',     'label' => __('Tất cả'),   'n' => $stats['total'],   'on' => 'bg-shopee text-white border-shopee',                                 'off' => 'bg-white dark:bg-slate-900 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-700'],
                        ['k' => 'error',   'label' => __('Lỗi'),      'n' => $stats['error'],   'on' => 'bg-red-600 text-white border-red-600',                               'off' => 'bg-white dark:bg-slate-900 text-red-600 dark:text-red-400 border-gray-200 dark:border-slate-700'],
                        ['k' => 'warning', 'label' => __('Cảnh báo'), 'n' => $stats['warning'], 'on' => 'bg-amber-500 text-white border-amber-500',                           'off' => 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 border-gray-200 dark:border-slate-700'],
                        ['k' => 'info',    'label' => __('Thông tin'),'n' => $stats['info'],    'on' => 'bg-sky-600 text-white border-sky-600',                               'off' => 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 border-gray-200 dark:border-slate-700'],
                        ['k' => 'debug',   'label' => __('Debug'),    'n' => $stats['debug'],   'on' => 'bg-slate-600 text-white border-slate-600',                           'off' => 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-gray-200 dark:border-slate-700'],
                        ['k' => 'other',   'label' => __('Khác'),     'n' => $stats['other'],   'on' => 'bg-violet-600 text-white border-violet-600',                         'off' => 'bg-white dark:bg-slate-900 text-violet-600 dark:text-violet-400 border-gray-200 dark:border-slate-700'],
                    ];
                @endphp
                @foreach($chips as $chip)
                    <button type="button" @click="level = '{{ $chip['k'] }}'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full border transition-all"
                            :class="level === '{{ $chip['k'] }}' ? '{{ $chip['on'] }}' : '{{ $chip['off'] }}'">
                        {{ $chip['label'] }}
                        <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold rounded-full"
                              :class="level === '{{ $chip['k'] }}' ? 'bg-white/25' : 'bg-gray-100 dark:bg-slate-700'">{{ number_format($chip['n']) }}</span>
                    </button>
                @endforeach

                <span class="ml-auto text-[11px] text-gray-400 dark:text-slate-500" x-show="view === 'cards'">
                    {{ __('Hiển thị') }} <span class="font-bold text-gray-600 dark:text-slate-300" x-text="visibleCount"></span> / {{ number_format($stats['total']) }}
                </span>
            </div>
        </div>

        <!-- CHẾ ĐỘ BẢN GHI -->
        <div x-show="view === 'cards'" class="space-y-2.5">
            <!-- Trạng thái rỗng khi lọc không khớp -->
            <div x-show="visibleCount === 0"
                 class="bg-white dark:bg-slate-800 rounded-3xl border border-gray-200 dark:border-slate-700 p-12 text-center shadow-sm">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-gray-100 dark:bg-slate-700 text-gray-400 flex items-center justify-center mb-4">
                    <i data-lucide="file-search" class="w-7 h-7"></i>
                </div>
                <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Không có bản ghi nào khớp với bộ lọc hiện tại.') }}</p>
            </div>

            <template x-for="(entry, idx) in entries" :key="idx">
                <div x-show="matches(entry)"
                     class="group bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden border-l-4 transition-all"
                     :class="meta(entry).bar">
                    <!-- Dòng tiêu đề bản ghi -->
                    <div class="flex items-start gap-3 p-3.5">
                        <!-- Badge cấp độ -->
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-lg shrink-0 mt-0.5"
                              :class="meta(entry).badge">
                            <i :data-lucide="meta(entry).icon" class="w-3.5 h-3.5"></i>
                            <span x-text="entry.level"></span>
                        </span>

                        <!-- Nội dung -->
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-gray-800 dark:text-slate-100 font-medium break-words leading-relaxed"
                               :class="!entry.open && 'line-clamp-2'"
                               x-text="entry.message"></p>

                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5 text-[10px] text-gray-400 dark:text-slate-500">
                                <span class="inline-flex items-center gap-1 font-mono">
                                    <i data-lucide="clock" class="w-3 h-3"></i><span x-text="entry.time"></span>
                                </span>
                                <span class="inline-flex items-center gap-1" x-show="entry.ago">
                                    <i data-lucide="history" class="w-3 h-3"></i><span x-text="entry.ago"></span>
                                </span>
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-gray-100 dark:bg-slate-700 font-mono font-semibold">
                                    <span x-text="entry.env"></span>
                                </span>
                            </div>
                        </div>

                        <!-- Hành động -->
                        <div class="flex items-center gap-1 shrink-0">
                            <button type="button" @click="copy(entry.raw, idx)"
                                    class="p-1.5 text-gray-400 hover:text-shopee hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                                    :title="copiedIdx === idx ? '{{ __('Đã chép!') }}' : '{{ __('Sao chép bản ghi') }}'">
                                <span class="inline-flex text-emerald-500" x-show="copiedIdx === idx" x-cloak><i data-lucide="check" class="w-3.5 h-3.5"></i></span>
                                <span class="inline-flex" x-show="copiedIdx !== idx"><i data-lucide="copy" class="w-3.5 h-3.5"></i></span>
                            </button>
                            <button type="button" @click="entry.open = !entry.open" x-show="entry.hasTrace"
                                    class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                                <span class="inline-flex transition-transform" :class="entry.open && 'rotate-180'"><i data-lucide="chevron-down" class="w-4 h-4"></i></span>
                            </button>
                        </div>
                    </div>

                    <!-- Chi tiết / Stack trace (thu gọn) -->
                    <div x-show="entry.open && entry.hasTrace" x-collapse x-cloak>
                        <div class="border-t border-gray-100 dark:border-slate-700 bg-slate-900 dark:bg-black">
                            <pre class="p-4 overflow-auto max-h-96 text-[11px] leading-relaxed font-mono text-slate-300 whitespace-pre-wrap break-all" x-text="entry.details"></pre>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- CHẾ ĐỘ THÔ (Terminal) -->
        <div x-show="view === 'raw'" x-cloak
             class="bg-slate-900 dark:bg-black rounded-3xl shadow-xl border border-slate-800 overflow-hidden flex flex-col">
            <div class="px-4 py-3 bg-slate-950 flex items-center gap-2 border-b border-slate-800">
                <div class="flex gap-1.5 shrink-0">
                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                    <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                </div>
                <div class="text-[10px] text-gray-400 font-mono font-bold mx-auto">laravel.log &mdash; Terminal Reader</div>
            </div>
            <div class="p-5 overflow-auto max-h-[640px]">
                <pre class="whitespace-pre-wrap font-mono break-all text-xs text-slate-300 leading-relaxed" x-text="rawText"></pre>
            </div>
        </div>
    @endif

    <!-- Modal xác nhận dọn dẹp log -->
    <div x-show="showClearModal"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showClearModal = false"></div>

        <div class="relative bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 dark:border-slate-800 transform transition-all space-y-4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

            <div class="flex items-start gap-4">
                <div class="p-3 bg-red-50 dark:bg-red-950/30 rounded-2xl border border-red-100 dark:border-red-900/30 text-red-600 dark:text-red-400 shrink-0">
                    <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                </div>
                <div class="space-y-1.5">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Dọn dẹp nhật ký hệ thống?') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed">
                        {{ __('Hành động này sẽ xóa sạch toàn bộ nội dung của tệp nhật ký lỗi hệ thống (laravel.log). Bạn có chắc chắn muốn tiếp tục? Điều này không thể hoàn tác.') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" @click="showClearModal = false"
                        class="px-4 py-2.5 text-xs font-semibold text-gray-750 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-xl transition-all border border-gray-200 dark:border-slate-850 cursor-pointer">
                    {{ __('Hủy bỏ') }}
                </button>
                <button type="button" @click="document.getElementById('clear-logs-form').submit()"
                        class="px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    {{ __('Xác nhận xóa') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function systemLogsPanel() {
        return {
            // Dữ liệu bản ghi đã parse từ server (mới nhất trước)
            entries: window.SYSTEM_LOG_ENTRIES || [],
            search: '',
            level: 'all',
            view: 'cards',
            auto: false,
            timer: null,
            copiedIdx: null,
            showClearModal: false,

            // Bảng màu & icon theo nhóm cấp độ nghiêm trọng
            levelMeta: {
                error:   { bar: 'border-l-red-500',    icon: 'octagon-alert',  badge: 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300' },
                warning: { bar: 'border-l-amber-500',  icon: 'triangle-alert', badge: 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' },
                info:    { bar: 'border-l-sky-500',    icon: 'info',           badge: 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300' },
                debug:   { bar: 'border-l-slate-400',  icon: 'bug',            badge: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200' },
                other:   { bar: 'border-l-violet-500', icon: 'circle-dot',     badge: 'bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300' },
            },

            meta(entry) {
                return this.levelMeta[entry.severity] || this.levelMeta.other;
            },

            // Logic lọc kết hợp: theo cấp độ + từ khóa tìm kiếm
            matches(entry) {
                if (this.level !== 'all' && entry.severity !== this.level) return false;
                const q = this.search.trim().toLowerCase();
                if (q === '') return true;
                return (entry.message + ' ' + entry.details + ' ' + entry.level + ' ' + entry.env)
                    .toLowerCase().includes(q);
            },

            get visibleCount() {
                return this.entries.filter(e => this.matches(e)).length;
            },

            get rawText() {
                return this.entries.map(e => e.raw).join('\n\n');
            },

            copy(text, idx) {
                navigator.clipboard.writeText(text).then(() => {
                    // Hiện dấu tích xác nhận trong 1.5s rồi trả lại icon sao chép
                    this.copiedIdx = idx;
                    setTimeout(() => { this.copiedIdx = null; }, 1500);
                });
            },

            toggleAuto() {
                this.auto = !this.auto;
                if (this.auto) {
                    // Tự động tải lại trang sau mỗi 15 giây để cập nhật log mới nhất
                    this.timer = setInterval(() => window.location.reload(), 15000);
                } else if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
            },
        };
    }
</script>
@endsection
