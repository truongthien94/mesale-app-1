{{-- Ảnh thu nhỏ sản phẩm dùng chung. Khi thiếu ảnh hoặc ảnh lỗi sẽ hiện icon placeholder trung tính.
     Biến: $order (bắt buộc), $size (class kích thước), $iconSize (class icon), $rounded (class bo góc) --}}
@php
    $thumbSize = $size ?? 'w-10 h-10';
    $thumbIcon = $iconSize ?? 'w-4 h-4';
    $thumbRound = $rounded ?? 'rounded-lg';
    $thumbBase = 'border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800';
@endphp
<div class="relative {{ $thumbSize }} shrink-0">
    @if($order->product_image)
        <img src="{{ $order->product_image }}"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
             alt="{{ __('Ảnh sản phẩm') }}"
             class="{{ $thumbSize }} object-cover {{ $thumbRound }} {{ $thumbBase }}">
        <div style="display:none" class="absolute inset-0 {{ $thumbSize }} items-center justify-center {{ $thumbRound }} {{ $thumbBase }} text-gray-300 dark:text-slate-600">
            <i data-lucide="image" class="{{ $thumbIcon }}"></i>
        </div>
    @else
        <div class="flex {{ $thumbSize }} items-center justify-center {{ $thumbRound }} {{ $thumbBase }} text-gray-300 dark:text-slate-600">
            <i data-lucide="image" class="{{ $thumbIcon }}"></i>
        </div>
    @endif
</div>
