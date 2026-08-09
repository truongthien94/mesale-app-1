@php
    /**
     * Component Toast hiển thị mặc định sử dụng Alpine.js
     * Thiết kế theo phong cách Glassmorphism hiện đại, hỗ trợ tự động nhận diện Dark Mode và responsive tốt.
     */
    $toastPosition = \App\Models\Setting::getVal('toast_position', 'top-right');
    $toastDuration = (int)\App\Models\Setting::getVal('toast_duration', '4000');

    // Ánh xạ cấu hình vị trí sang class Tailwind CSS tương ứng
    $positionClasses = match($toastPosition) {
        'top-center'    => 'top-6 left-1/2 -translate-x-1/2 items-center',
        'top-right'     => 'top-6 right-4 items-end',
        'top-left'      => 'top-6 left-4 items-start',
        'bottom-right'  => 'bottom-6 right-4 items-end',
        'bottom-left'   => 'bottom-6 left-4 items-start',
        'bottom-center' => 'bottom-6 left-1/2 -translate-x-1/2 items-center',
        default         => 'top-6 right-4 items-end',
    };
@endphp

{{-- Khối Toast bọc ngoài lắng nghe sự kiện window 'toast' để tạo thông báo mới --}}
<div x-data="{
    messages: [],
    // Xóa một thông báo dựa trên id (timestamp)
    remove(id) {
        this.messages = this.messages.filter(m => m.id !== id);
    },
    // Thêm thông báo mới và đặt bộ hẹn giờ tự động đóng
    add(text, type = 'success') {
        const id = Date.now();
        this.messages.push({ id, text, type });
        setTimeout(() => this.remove(id), {{ $toastDuration }});
    }
}"
    @toast.window="add($event.detail.text, $event.detail.type)"
    class="fixed z-[9999] flex flex-col gap-3 w-full max-w-[90%] sm:max-w-md pointer-events-none {{ $positionClasses }}">

    {{-- Lặp qua danh sách tin nhắn để hiển thị giao diện chi tiết --}}
    <template x-for="msg in messages" :key="msg.id">
        <div x-transition:enter="transition ease-out duration-300 transform -translate-y-4 opacity-0"
            x-transition:enter-end="transform translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-200 transform -translate-y-2 opacity-0"
            class="px-4 py-3 rounded-2xl shadow-[0_10px_30px_rgba(0,0,0,0.08)] dark:shadow-[0_10px_30px_rgba(0,0,0,0.5)] border text-xs sm:text-sm font-semibold pointer-events-auto flex items-center justify-between gap-3 backdrop-blur-md transition-all duration-300 w-full"
            :class="{
                'bg-green-500/10 dark:bg-green-500/20 text-green-600 dark:text-green-400 border-green-200/50 dark:border-green-500/30': msg.type === 'success',
                'bg-red-500/10 dark:bg-red-500/20 text-red-600 dark:text-red-400 border-red-200/50 dark:border-red-500/30': msg.type === 'error',
                'bg-yellow-500/10 dark:bg-yellow-500/20 text-yellow-600 dark:text-yellow-400 border-yellow-200/50 dark:border-yellow-500/30': msg.type === 'warning',
                'bg-blue-500/10 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 border-blue-200/50 dark:border-blue-500/30': msg.type === 'info'
            }">
            <div class="flex items-center gap-3 flex-1">
                {{-- Icon tương ứng với từng trạng thái thông báo --}}
                <div class="p-1.5 rounded-full shrink-0" :class="{
                    'bg-green-500/20 text-green-500': msg.type === 'success',
                    'bg-red-500/20 text-red-500': msg.type === 'error',
                    'bg-yellow-500/20 text-yellow-500': msg.type === 'warning',
                    'bg-blue-500/20 text-blue-500': msg.type === 'info'
                }">
                    <template x-if="msg.type === 'success'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    </template>
                    <template x-if="msg.type === 'error'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </template>
                    <template x-if="msg.type === 'warning'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </template>
                    <template x-if="msg.type === 'info'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </template>
                </div>
                {{-- Nội dung thông báo --}}
                <span x-text="msg.text" class="flex-1 leading-tight pr-1 break-words text-gray-800 dark:text-slate-200"></span>
            </div>
            {{-- Nút tắt nhanh --}}
            <button @click="remove(msg.id)" class="text-gray-400 hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-200 shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </template>
</div>
