@php
    // Dữ liệu nạp vào form chỉnh sửa (đã định dạng sẵn cho input datetime-local)
    $editData = [
        'id' => $code->id,
        'code' => $code->code,
        'title' => $code->title,
        'description' => $code->description,
        'reward_type' => $code->reward_type,
        'reward_amount' => (int) $code->reward_amount,
        'reward_min' => (int) $code->reward_min,
        'reward_max' => (int) $code->reward_max,
        'max_uses' => $code->max_uses,
        'per_user_limit' => $code->per_user_limit,
        'starts_at' => $code->starts_at ? $code->starts_at->format('Y-m-d\TH:i') : '',
        'expires_at' => $code->expires_at ? $code->expires_at->format('Y-m-d\TH:i') : '',
        'require_verified_email' => $code->require_verified_email ? 1 : 0,
        'min_total_cashback' => (int) $code->min_total_cashback,
        'min_account_age_days' => (int) $code->min_account_age_days,
        'new_user_within_days' => $code->new_user_within_days,
        'status' => $code->status ? 1 : 0,
    ];
    $isExpired = $code->isExpired();
    $isSoldOut = $code->isSoldOut();
@endphp
<tr x-data="{ active: {{ $code->status ? 'true' : 'false' }}, busy: false,
        toggle() {
            if (this.busy) return; this.busy = true;
            fetch('{{ route('admin.giftcodes.toggle_status', $code) }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(d => { this.busy = false; this.active = d.active; window.dispatchEvent(new CustomEvent('toast', { detail: { text: d.message, type: 'success' } })); })
                .catch(() => this.busy = false);
        } }"
    class="hover:bg-gray-50/50 dark:hover:bg-slate-800/30 transition-colors">
    <td class="px-4 py-3">
        <input type="checkbox" value="{{ $code->id }}" x-model.number="selected" class="rounded border-gray-300 text-shopee focus:ring-shopee/30 w-4 h-4">
    </td>
    <td class="px-4 py-3">
        <div class="flex items-center gap-2">
            <span class="font-mono font-bold text-shopee text-sm tracking-wide">{{ $code->code }}</span>
            @if($isExpired)
                <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-md bg-red-50 text-red-600 dark:bg-red-950/30 dark:text-red-400">{{ __('Hết hạn') }}</span>
            @elseif($isSoldOut)
                <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-md bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400">{{ __('Hết lượt') }}</span>
            @endif
        </div>
        @if($code->title)<div class="text-[11px] text-gray-400 mt-0.5">{{ $code->title }}</div>@endif
    </td>
    <td class="px-4 py-3">
        <span class="font-extrabold text-gray-900 dark:text-white">{{ $code->reward_label }}</span>
        @if($code->reward_type === 'random')
            <span class="block text-[10px] text-gray-400">{{ __('Ngẫu nhiên') }}</span>
        @endif
    </td>
    <td class="px-4 py-3">
        <span class="font-bold text-gray-700 dark:text-slate-300">{{ number_format($code->used_count) }}</span>
        <span class="text-gray-400">/ {{ $code->max_uses !== null ? number_format($code->max_uses) : '∞' }}</span>
        <span class="block text-[10px] text-gray-400">{{ __(':n lượt/người', ['n' => $code->per_user_limit]) }}</span>
    </td>
    <td class="px-4 py-3 text-gray-500 dark:text-slate-400">
        @if($code->expires_at)
            {{ $code->expires_at->format('H:i d/m/Y') }}
        @else
            <span class="text-gray-400">{{ __('Không hết hạn') }}</span>
        @endif
    </td>
    <td class="px-4 py-3 text-center">
        <button type="button" @click="toggle()" :class="active ? 'bg-green-500' : 'bg-gray-300 dark:bg-slate-700'" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
            <span :class="active ? 'translate-x-5' : 'translate-x-0'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
        </button>
    </td>
    <td class="px-4 py-3">
        <div class="flex items-center justify-end gap-1.5">
            <button @click="openEdit({{ \Illuminate\Support\Js::from($editData) }})" class="p-1.5 text-gray-400 hover:text-shopee hover:bg-shopee/10 rounded-lg transition-all" title="{{ __('Sửa') }}">
                <i data-lucide="pencil" class="w-4 h-4"></i>
            </button>
            <button @click="openDelete('{{ $code->code }}', '{{ route('admin.giftcodes.destroy', $code) }}')" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-all" title="{{ __('Xóa') }}">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
        </div>
    </td>
</tr>
