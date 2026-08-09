@php
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
@endphp
<div x-data="{ active: {{ $code->status ? 'true' : 'false' }}, busy: false,
        toggle() {
            if (this.busy) return; this.busy = true;
            fetch('{{ route('admin.giftcodes.toggle_status', $code) }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(d => { this.busy = false; this.active = d.active; window.dispatchEvent(new CustomEvent('toast', { detail: { text: d.message, type: 'success' } })); })
                .catch(() => this.busy = false);
        } }"
    class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 p-4 shadow-sm">
    <div class="flex items-start justify-between gap-2 mb-3">
        <div class="flex items-start gap-2 min-w-0">
            <input type="checkbox" value="{{ $code->id }}" x-model.number="selected" class="mt-1 rounded border-gray-300 text-shopee focus:ring-shopee/30 w-4 h-4 shrink-0">
            <div class="min-w-0">
                <span class="font-mono font-bold text-shopee text-sm tracking-wide block">{{ $code->code }}</span>
                @if($code->title)<span class="text-[11px] text-gray-400">{{ $code->title }}</span>@endif
            </div>
        </div>
        <button type="button" @click="toggle()" :class="active ? 'bg-green-500' : 'bg-gray-300 dark:bg-slate-700'" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors shrink-0">
            <span :class="active ? 'translate-x-5' : 'translate-x-0'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
        </button>
    </div>
    <div class="grid grid-cols-2 gap-2 text-xs mb-3">
        <div class="bg-gray-50/60 dark:bg-slate-800/40 rounded-xl p-2.5">
            <span class="block text-[10px] text-gray-400 uppercase tracking-wide">{{ __('Phần thưởng') }}</span>
            <span class="font-extrabold text-gray-900 dark:text-white">{{ $code->reward_label }}</span>
        </div>
        <div class="bg-gray-50/60 dark:bg-slate-800/40 rounded-xl p-2.5">
            <span class="block text-[10px] text-gray-400 uppercase tracking-wide">{{ __('Lượt dùng') }}</span>
            <span class="font-bold text-gray-700 dark:text-slate-300">{{ number_format($code->used_count) }} / {{ $code->max_uses !== null ? number_format($code->max_uses) : '∞' }}</span>
        </div>
    </div>
    <div class="flex items-center justify-between gap-2 pt-3 border-t border-gray-100 dark:border-slate-800">
        <span class="text-[11px] text-gray-400">
            {{ $code->expires_at ? __('HH: ') . $code->expires_at->format('d/m/Y') : __('Không hết hạn') }}
        </span>
        <div class="flex items-center gap-1.5">
            <button @click="openEdit({{ \Illuminate\Support\Js::from($editData) }})" class="p-1.5 text-gray-400 hover:text-shopee hover:bg-shopee/10 rounded-lg transition-all"><i data-lucide="pencil" class="w-4 h-4"></i></button>
            <button @click="openDelete('{{ $code->code }}', '{{ route('admin.giftcodes.destroy', $code) }}')" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-all"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
        </div>
    </div>
</div>
