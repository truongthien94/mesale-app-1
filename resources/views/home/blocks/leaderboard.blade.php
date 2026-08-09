{{-- Block Bảng xếp hạng hoàn tiền — nhiều tab BXH (dữ liệu thật, đồng bộ trang /ranking) --}}
@php
    // Giới hạn số dòng mỗi bảng (1..10)
    $lbLimit = (int) ($s['limit'] ?? 5);
    $lbLimit = max(1, min(10, $lbLimit ?: 5));

    // Đọc cấu hình che tên thành viên (đồng bộ trang Bảng xếp hạng)
    $lbNameMode = \App\Models\Setting::getVal('ranking_name_mode', 'mask');
    $lbStart    = (int) \App\Models\Setting::getVal('ranking_name_visible_start', 2);
    $lbEnd      = (int) \App\Models\Setting::getVal('ranking_name_visible_end', 2);
    $lbMaskChar = \App\Models\Setting::getVal('ranking_name_mask_char', '*');
    $lbMaskLen  = (int) \App\Models\Setting::getVal('ranking_name_mask_length', 3);

    $lbMask = function ($name) use ($lbNameMode, $lbStart, $lbEnd, $lbMaskChar, $lbMaskLen) {
        if (empty($name)) return __('Thành viên ẩn danh');
        if ($lbNameMode === 'full') return $name;
        $maskChar = $lbMaskChar !== '' ? mb_substr($lbMaskChar, 0, 1, 'UTF-8') : '*';
        $start = max(0, $lbStart);
        $end = max(0, $lbEnd);
        $maskLen = max(1, $lbMaskLen);
        $length = mb_strlen($name, 'UTF-8');
        if ($length <= $start + $end) {
            return mb_substr($name, 0, 1, 'UTF-8') . str_repeat($maskChar, $maskLen);
        }
        $head = $start > 0 ? mb_substr($name, 0, $start, 'UTF-8') : '';
        $tail = $end > 0 ? mb_substr($name, -$end, $end, 'UTF-8') : '';
        return $head . str_repeat($maskChar, $maskLen) . $tail;
    };

    // Hàm gắn thông tin User vào các bản ghi gộp nhóm (tránh N+1)
    $lbAttachUsers = function ($rows) {
        $users = \App\Models\User::whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');
        return $rows->map(function ($it) use ($users) {
            $it->user = $users->get($it->user_id);
            return $it;
        })->filter(fn ($it) => $it->user !== null)->values();
    };

    // Tôn trọng cấu hình Bảng xếp hạng toàn cục (Cài đặt > Bảng xếp hạng):
    // một tab chỉ hiển thị khi tính năng BXH đang bật + tab đó bật toàn cục + admin bật trong block.
    $lbMasterOn = \App\Models\Setting::getVal('ranking_status', '1') === '1';
    $lbShow = fn ($blockKey, $globalKey) => $lbMasterOn
        && \App\Models\Setting::getVal($globalKey, '1') === '1'
        && (($s[$blockKey] ?? '0') === '1');

    // Xây dựng danh sách tab được bật (chỉ giữ tab CÓ dữ liệu để trang chủ không hiển thị bảng trống)
    $lbTabs = [];

    // A. Top Đơn Hàng
    if ($lbShow('show_orders', 'ranking_top_orders_status')) {
        $rows = $lbAttachUsers(
            \App\Models\CashbackHistory::select('user_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as metric'))
                ->where('status', 'approved')->groupBy('user_id')
                ->orderBy('metric', 'desc')->limit($lbLimit)->get()
        )->map(fn ($it) => (object) [
            'user_id' => $it->user_id, 'name' => $it->user->name, 'joined' => $it->user->created_at,
            'value' => number_format($it->metric),
        ]);
        if ($rows->count()) $lbTabs[] = ['key' => 'orders', 'label' => __('Top Đơn Hàng'), 'icon' => 'trophy', 'unit' => __('ĐƠN HÀNG'), 'rows' => $rows];
    }

    // B. Top Tiền Hoàn
    if ($lbShow('show_cashback', 'ranking_top_cashback_status')) {
        $rows = \App\Models\User::where('role', 'user')->where('status', 'active')->where('total_cashback', '>', 0)
            ->orderBy('total_cashback', 'desc')->limit($lbLimit)->get()
            ->map(fn ($u) => (object) [
                'user_id' => $u->id, 'name' => $u->name, 'joined' => $u->created_at,
                'value' => \App\Helpers\CurrencyHelper::format($u->total_cashback),
            ]);
        if ($rows->count()) $lbTabs[] = ['key' => 'cashback', 'label' => __('Top Tiền Hoàn'), 'icon' => 'wallet', 'unit' => __('TIỀN HOÀN'), 'rows' => $rows];
    }

    // C. Top Điểm Danh
    if ($lbShow('show_checkin', 'ranking_top_checkin_status')) {
        $rows = $lbAttachUsers(
            \App\Models\DailyCheckin::select('user_id', \Illuminate\Support\Facades\DB::raw('MAX(streak_days) as metric'))
                ->groupBy('user_id')->orderBy('metric', 'desc')->limit($lbLimit)->get()
        )->map(fn ($it) => (object) [
            'user_id' => $it->user_id, 'name' => $it->user->name, 'joined' => $it->user->created_at,
            'value' => number_format($it->metric),
        ]);
        if ($rows->count()) $lbTabs[] = ['key' => 'checkin', 'label' => __('Top Điểm Danh'), 'icon' => 'flame', 'unit' => __('CHUỖI'), 'rows' => $rows];
    }

    // D. Top Giới Thiệu
    if ($lbShow('show_referral', 'ranking_top_referral_status')) {
        $rows = $lbAttachUsers(
            \App\Models\User::select('referred_by as user_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as metric'))
                ->whereNotNull('referred_by')->groupBy('referred_by')
                ->orderBy('metric', 'desc')->limit($lbLimit)->get()
        )->map(fn ($it) => (object) [
            'user_id' => $it->user_id, 'name' => $it->user->name, 'joined' => $it->user->created_at,
            'value' => number_format($it->metric),
        ]);
        if ($rows->count()) $lbTabs[] = ['key' => 'referrals', 'label' => __('Top Giới Thiệu'), 'icon' => 'users', 'unit' => __('BẠN BÈ'), 'rows' => $rows];
    }

    // E. Top Số Dư
    if ($lbShow('show_balance', 'ranking_top_balance_status')) {
        $rows = \App\Models\User::where('role', 'user')->where('status', 'active')->where('balance', '>', 0)
            ->orderBy('balance', 'desc')->limit($lbLimit)->get()
            ->map(fn ($u) => (object) [
                'user_id' => $u->id, 'name' => $u->name, 'joined' => $u->created_at,
                'value' => \App\Helpers\CurrencyHelper::format($u->balance),
            ]);
        if ($rows->count()) $lbTabs[] = ['key' => 'balance', 'label' => __('Top Số Dư'), 'icon' => 'piggy-bank', 'unit' => __('SỐ DƯ'), 'rows' => $rows];
    }

    $lbDefault = $lbTabs[0]['key'] ?? '';
    $lbRankingOn = $lbMasterOn;
    $lbMyId = auth()->check() ? auth()->id() : null;
@endphp

@if(!empty($lbTabs))
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 pt-16 md:pt-24 border-t border-gray-100/60 dark:border-slate-800/40"
     x-data="{ tab: '{{ $lbDefault }}' }">
    <div class="bg-gradient-to-br from-amber-50/60 to-transparent dark:from-slate-900/40 dark:to-transparent p-6 md:p-10 rounded-[32px] border border-amber-100/60 dark:border-slate-800/60">
        {{-- Tiêu đề khu vực --}}
        <div class="text-center max-w-2xl mx-auto mb-8">
            @if(!empty($s['badge']))
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400">
                <i data-lucide="trophy" class="w-3.5 h-3.5"></i>
                {{ $s['badge'] }}
            </span>
            @endif
            @if(!empty($s['title']))
            <h2 class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white mt-3 leading-tight">{{ $s['title'] }}</h2>
            @endif
            @if(!empty($s['subtitle']))
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-2">{{ $s['subtitle'] }}</p>
            @endif
        </div>

        {{-- Thanh chọn tab (chỉ hiện khi có nhiều hơn 1 bảng) --}}
        @if(count($lbTabs) > 1)
        <div class="overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden mb-6">
            <div class="flex items-center justify-center gap-1.5 p-1 bg-white dark:bg-slate-900 rounded-2xl w-max mx-auto border border-gray-150/80 dark:border-slate-800/80 shadow-sm">
                @foreach($lbTabs as $t)
                <button type="button" @click="tab = '{{ $t['key'] }}'"
                    :class="tab === '{{ $t['key'] }}' ? 'bg-shopee text-white shadow-md shadow-orange-500/20' : 'text-gray-600 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-slate-800/50'"
                    class="flex items-center justify-center gap-2 px-4 sm:px-5 py-2 rounded-xl text-xs font-bold transition-all duration-200 shrink-0">
                    <i data-lucide="{{ $t['icon'] }}" class="w-4 h-4"></i>
                    {{ $t['label'] }}
                </button>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Nội dung từng bảng --}}
        <div class="max-w-2xl mx-auto">
            @foreach($lbTabs as $t)
            <div x-show="tab === '{{ $t['key'] }}'" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
                 class="space-y-2.5">
                @foreach($t['rows'] as $index => $row)
                @php $rank = $index + 1; $isMe = $lbMyId && $row->user_id === $lbMyId; @endphp

                @if($rank === 1)
                {{-- HẠNG 1: nổi bật --}}
                <div class="flex items-center justify-between gap-3 p-4 rounded-2xl relative overflow-hidden border-2 border-amber-400/40 dark:border-amber-500/30 bg-gradient-to-r from-amber-500/5 via-amber-500/10 to-transparent dark:from-amber-950/20 dark:to-transparent shadow-md shadow-amber-500/5">
                    <div class="absolute top-0 left-0 bg-amber-500 text-[8px] text-white font-black px-2 py-0.5 rounded-br-lg rounded-tl-xl uppercase tracking-wider">#1</div>
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-full bg-white dark:bg-slate-800 flex items-center justify-center shadow border border-amber-200 shrink-0">
                            <i data-lucide="crown" class="w-5 h-5 text-amber-500 fill-amber-400"></i>
                        </div>
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-amber-950 bg-amber-300 ring-2 ring-white dark:ring-slate-900 shrink-0">
                            {{ mb_strtoupper(mb_substr($row->name ?? 'U', 0, 1, 'UTF-8'), 'UTF-8') }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-black text-gray-950 dark:text-white text-sm md:text-base block truncate leading-none">{{ $lbMask($row->name) }}</span>
                                @if($isMe)<span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400 uppercase tracking-wide">YOU</span>@endif
                            </div>
                            @if($row->joined)
                            <span class="text-[10px] text-gray-400 dark:text-slate-500 font-semibold block mt-1">{{ __('Tham gia: :date', ['date' => $row->joined->format('d/m/Y')]) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-[9px] text-gray-400 dark:text-slate-500 font-extrabold uppercase block tracking-wider mb-1">{{ $t['unit'] }}</span>
                        <span class="text-xl md:text-2xl font-black text-shopee block leading-none">{{ $row->value }}</span>
                    </div>
                </div>
                @else
                {{-- HẠNG 2 trở đi --}}
                <div class="flex items-center justify-between gap-3 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-gray-100/80 dark:border-slate-800/80 shadow-sm hover:shadow hover:border-gray-200 dark:hover:border-slate-700 transition-all duration-200">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-full bg-gray-50 border border-gray-100 dark:bg-slate-800 dark:border-slate-700 flex items-center justify-center font-bold text-xs text-gray-500 dark:text-slate-400 shrink-0">{{ $rank }}</div>
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-sm shrink-0 border
                            {{ $rank === 2 ? 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900/50' :
                               ($rank === 3 ? 'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-950/30 dark:text-indigo-400 dark:border-indigo-900/50' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 border-gray-200 dark:border-slate-700') }}">
                            {{ mb_strtoupper(mb_substr($row->name ?? 'U', 0, 1, 'UTF-8'), 'UTF-8') }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-800 dark:text-slate-200 text-sm block truncate leading-none">{{ $lbMask($row->name) }}</span>
                                @if($isMe)<span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400 uppercase tracking-wide">YOU</span>@endif
                            </div>
                            @if($row->joined)
                            <span class="text-[10px] text-gray-400 dark:text-slate-500 font-semibold block mt-1">{{ __('Tham gia: :date', ['date' => $row->joined->format('d/m/Y')]) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-[9px] text-gray-400 dark:text-slate-500 font-extrabold uppercase block tracking-wider mb-1">{{ $t['unit'] }}</span>
                        <span class="text-base md:text-lg font-black text-gray-800 dark:text-slate-200 block leading-none">{{ $row->value }}</span>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
            @endforeach
        </div>

        {{-- Nút xem bảng xếp hạng đầy đủ --}}
        @if(!empty(trim($s['link_text'] ?? '')) && $lbRankingOn)
        <div class="text-center mt-8">
            <a href="{{ route('ranking.index') }}" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-2xl text-xs font-bold text-shopee bg-white dark:bg-slate-900 border border-shopee/30 hover:bg-shopee/5 transition-all">
                {{ $s['link_text'] }}
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
        @endif
    </div>
</div>
@endif
