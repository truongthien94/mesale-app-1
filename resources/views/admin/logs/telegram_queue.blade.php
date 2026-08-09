@extends('layouts.admin')

@section('title', __('Hàng Đợi Telegram') . ' - ' . $siteName)

@php
    $hasFilter = request('status') || request('chat_id') || request('message') || (request('limit') && request('limit') != 15);
@endphp

@section('content')
<div class="space-y-6" x-data="{ 
    selectedTelegram: null,
    showPreviewModal: false,
    previewChatId: '',
    previewBody: '',
    showFilter: @json($hasFilter),
    showClearModal: false
}">
    <!-- Tiêu đề -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Hàng Đợi Telegram') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Theo dõi, quản lý và kiểm tra trạng thái các thông báo gửi đi qua Telegram Bot') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- Nút bật/tắt bộ lọc -->
            <button @click="showFilter = !showFilter" 
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold rounded-xl border border-gray-200 transition-all shadow-sm"
                    :class="showFilter ? 'bg-shopee text-white border-shopee hover:bg-shopee-dark' : 'bg-white hover:bg-gray-50 text-gray-700'">
                <i data-lucide="filter" class="w-4 h-4"></i>
                <span>{{ __('Bộ lọc') }}</span>
                @if($hasFilter)
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                @endif
            </button>

            <!-- Nút dọn dẹp tất cả hàng đợi Telegram -->
            <button type="button" 
                    @click="showClearModal = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md cursor-pointer whitespace-nowrap">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>{{ __('Dọn dẹp tất cả') }}</span>
            </button>

            <span class="text-xs text-gray-400 bg-gray-100 px-3 py-2 border border-gray-200 rounded-xl font-medium">
                {{ __('Hàng đợi bất đồng bộ') }}
            </span>
        </div>
    </div>

    <!-- Bộ lọc tìm kiếm -->
    <div x-show="showFilter" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         class="bg-white p-4 rounded-3xl shadow-sm border border-gray-200"
         style="display: {{ $hasFilter ? 'block' : 'none' }}">
        <form action="{{ route('admin.logs.telegram_queue') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <select name="limit" class="block w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="15" {{ request('limit') == 15 ? 'selected' : '' }}>{{ __('Hiển thị 15 dòng') }}</option>
                    <option value="30" {{ request('limit') == 30 ? 'selected' : '' }}>{{ __('Hiển thị 30 dòng') }}</option>
                    <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>{{ __('Hiển thị 50 dòng') }}</option>
                    <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>{{ __('Hiển thị 100 dòng') }}</option>
                    <option value="200" {{ request('limit') == 200 ? 'selected' : '' }}>{{ __('Hiển thị 200 dòng') }}</option>
                    <option value="500" {{ request('limit') == 500 ? 'selected' : '' }}>{{ __('Hiển thị 500 dòng') }}</option>
                </select>
            </div>

            <div>
                <select name="status" class="block w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
                    <option value="">{{ __('Tất cả trạng thái') }}</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Chờ gửi') }}</option>
                    <option value="sending" {{ request('status') === 'sending' ? 'selected' : '' }}>{{ __('Đang gửi') }}</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>{{ __('Đã gửi') }}</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>{{ __('Gửi lỗi') }}</option>
                </select>
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="hash" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="chat_id" 
                       value="{{ request('chat_id') }}"
                       placeholder="{{ __('Chat ID...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="message-square" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="message" 
                       value="{{ request('message') }}"
                       placeholder="{{ __('Nội dung tin nhắn...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-grow px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shrink-0">
                    {{ __('Lọc') }}
                </button>
                @if($hasFilter)
                    <a href="{{ route('admin.logs.telegram_queue') }}" class="flex-grow inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all shadow-sm shrink-0 gap-1">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        <span>{{ __('Bỏ lọc') }}</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Bảng dữ liệu -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="border-b border-gray-200 text-xs font-bold text-gray-400 bg-gray-50/50">
                        <th class="p-4 w-12 text-center">ID</th>
                        <th class="p-4">{{ __('Chat ID') }}</th>
                        <th class="p-4">{{ __('Nội dung tin nhắn') }}</th>
                        <th class="p-4 text-center">{{ __('Trạng thái') }}</th>
                        <th class="p-4 text-center">{{ __('Số lần thử') }}</th>
                        <th class="p-4 text-center">{{ __('Thời gian') }}</th>
                        <th class="p-4 text-center w-24">{{ __('Hành động') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse($queues as $queue)
                        <tr>
                            <td class="p-4 font-bold text-gray-400 text-center">{{ $queue->id }}</td>
                            <td class="p-4">
                                <span class="font-mono bg-gray-100 px-2 py-1 rounded text-gray-700 font-semibold">{{ $queue->chat_id }}</span>
                                {{-- Trích xuất địa chỉ email từ nội dung thông báo Telegram bằng Regex. Nếu khớp với một tài khoản thành viên trong hệ thống, hiển thị nhãn và nút chỉnh sửa nhanh phục vụ quản trị --}}
                                @php
                                    $email = null;
                                    if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $queue->message, $matches)) {
                                        $email = $matches[0];
                                    }
                                    $matchedUser = $email ? \App\Models\User::where('email', $email)->first() : null;
                                @endphp
                                @if($matchedUser)
                                    <div class="mt-2 flex items-center gap-1 text-[10px] text-blue-600 bg-blue-50/50 px-2 py-1 rounded-lg border border-blue-100/50 w-fit">
                                        <i data-lucide="user" class="w-3 h-3"></i>
                                        <span class="font-medium truncate max-w-[100px]">{{ $matchedUser->name }}</span>
                                        <a href="{{ route('admin.users.edit', $matchedUser->id) }}" class="p-0.5 hover:bg-blue-100 rounded text-blue-700 transition-colors" title="{{ __('Chỉnh sửa thành viên') }}">
                                            <i data-lucide="edit" class="w-3 h-3"></i>
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td class="p-4 font-medium text-gray-700 max-w-sm truncate" title="{{ strip_tags($queue->message) }}">
                                {{ Str::limit(strip_tags($queue->message), 150) }}
                            </td>
                            <td class="p-4 text-center">
                                @if($queue->status === 'sent')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        {{ __('Đã gửi') }}
                                    </span>
                                @elseif($queue->status === 'sending')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                        {{ __('Đang gửi') }}
                                    </span>
                                @elseif($queue->status === 'failed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-200" title="{{ $queue->error_message }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        {{ __('Lỗi gửi') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-yellow-50 text-yellow-700 border border-yellow-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
                                        {{ __('Chờ gửi') }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-4 text-center font-bold text-gray-700">
                                {{ $queue->attempts }}/3
                            </td>
                            <td class="p-4 text-center text-gray-400 text-[10px]">
                                <div title="{{ __('Ngày tạo') }}">
                                    <span class="font-bold text-gray-500">Tạo:</span> {{ $queue->created_at->format('d/m H:i') }}
                                </div>
                                @if($queue->sent_at)
                                    <div class="mt-0.5" title="{{ __('Ngày gửi thành công') }}">
                                        <span class="font-bold text-green-600">Gửi:</span> {{ $queue->sent_at->format('d/m H:i') }}
                                    </div>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    {{-- Sử dụng input và textarea ẩn để lưu trữ thông tin chat_id và nội dung tin nhắn.
                                         Giải pháp này giúp ngăn ngừa hoàn toàn lỗ hổng XSS/vỡ cú pháp JS khi render trực tiếp biến PHP vào chuỗi Javascript. --}}
                                    <input type="hidden" id="telegram-chatid-{{ $queue->id }}" value="{{ $queue->chat_id }}">
                                    <textarea id="telegram-message-{{ $queue->id }}" class="hidden">{{ $queue->message }}</textarea>

                                    <!-- Nút Xem trước tin nhắn -->
                                    <button @click="
                                        previewChatId = document.getElementById('telegram-chatid-{{ $queue->id }}').value;
                                        previewBody = document.getElementById('telegram-message-{{ $queue->id }}').value;
                                        showPreviewModal = true;
                                    " class="p-1.5 text-gray-400 hover:text-shopee rounded-lg hover:bg-gray-50 transition-all" title="{{ __('Xem trước nội dung tin nhắn') }}">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>

                                    <!-- Nút Thử lại (nếu trạng thái là failed) -->
                                    @if($queue->status === 'failed')
                                        <form action="{{ route('admin.logs.telegram_queue.retry', $queue->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-orange-500 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-all" title="{{ __('Gửi lại tin nhắn này') }}">
                                                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Nút Xóa -->
                                    <form action="{{ route('admin.logs.telegram_queue.delete', $queue->id) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tin nhắn này khỏi hàng đợi Telegram?')">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-red-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="{{ __('Xóa khỏi hàng đợi') }}">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <!-- Dòng hiển thị lỗi chi tiết nếu gửi thất bại -->
                        @if($queue->status === 'failed' && $queue->error_message)
                            <tr class="bg-red-50/20">
                                <td colspan="7" class="px-4 py-2 text-[10px] text-red-600 font-mono border-t border-red-50">
                                    <span class="font-bold">{{ __('Chi tiết lỗi:') }}</span> {{ $queue->error_message }}
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-gray-400">{{ __('Hàng đợi Telegram hiện tại trống.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        @if($queues->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $queues->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Xem Trước Tin Nhắn (AlpineJS) -->
    <template x-teleport="body">
        <div x-show="showPreviewModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/40 backdrop-blur-sm"
             x-transition
             x-cloak>
            <div class="bg-white rounded-3xl shadow-xl border border-gray-200 w-full max-w-lg max-h-[80vh] flex flex-col overflow-hidden"
                 @click.away="showPreviewModal = false">
                <!-- Header -->
                <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-950 text-sm">{{ __('Xem trước Nội dung Telegram') }}</h3>
                        <p class="text-[10px] text-gray-500 mt-0.5">Chat ID: <span class="font-mono" x-text="previewChatId"></span></p>
                    </div>
                    <button @click="showPreviewModal = false" class="p-1 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-500 transition-all">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                
                <!-- Body Preview -->
                <div class="flex-1 overflow-y-auto p-4 bg-gray-50/50">
                    {{-- Sử dụng x-text thay cho x-html để triệt tiêu hoàn toàn nguy cơ Stored XSS khi hiển thị tin nhắn chứa mã độc từ phía người dùng --}}
                    <div class="bg-[#eef2f6] rounded-2xl border border-gray-150 p-4 min-h-[150px] font-sans text-xs whitespace-pre-wrap text-gray-800" x-text="previewBody">
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-3 border-t border-gray-100 bg-gray-50/50 flex justify-end">
                    <button @click="showPreviewModal = false" class="px-4 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">
                        {{ __('Đóng') }}
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal xác nhận dọn dẹp tất cả hàng đợi Telegram -->
    <div x-show="showClearModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showClearModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6 border border-gray-100 relative z-10">
                <div class="flex items-center gap-4 text-red-600 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center shrink-0">
                        <i data-lucide="triangle-alert" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ __('Xác Nhận Dọn Dẹp Hàng Đợi Telegram') }}</h3>
                        <p class="text-xs text-gray-500">{{ __('Hành động này không thể hoàn tác!') }}</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                    {{ __('Bạn có chắc chắn muốn xóa toàn bộ tin nhắn trong hàng đợi Telegram? Việc này sẽ xóa sạch danh sách tin nhắn chưa gửi hoặc đã gửi.') }}
                </p>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="showClearModal = false" class="px-4 py-2.5 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all">
                        {{ __('Hủy bỏ') }}
                    </button>
                    <form action="{{ route('admin.logs.telegram_queue.clear') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md">
                            {{ __('Xác nhận dọn dẹp') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
