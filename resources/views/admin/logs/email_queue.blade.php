@extends('layouts.admin')

@section('title', __('Hàng Đợi Email') . ' - ' . $siteName)

@php
    $hasFilter = request('status') || request('email_search') || request('subject') || (request('limit') && request('limit') != 15);
@endphp

@section('content')
<div class="space-y-6" x-data="{
    selectedEmail: null,
    showPreviewModal: false,
    previewSubject: '',
    previewBody: '',
    activeTab: 'preview',
    showFilter: @json($hasFilter),
    selected: [],
    allIds: @json($queues->pluck('id')->values()),
    showBulkDeleteModal: false,
    showBulkRetryModal: false,
    showClearModal: false,
    get allChecked() { return this.allIds.length !== 0 && this.selected.length === this.allIds.length; },
    toggleAll(checked) { this.selected = checked ? this.allIds.map(id => String(id)) : []; }
}">
    <!-- Tiêu đề -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Hàng Đợi Email') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Theo dõi, quản lý và kiểm tra trạng thái các email gửi đi từ hệ thống thông báo') }}</p>
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
            
            <!-- Nút dọn dẹp tất cả hàng đợi Email -->
            <button type="button" 
                    @click="showClearModal = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md cursor-pointer whitespace-nowrap">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>{{ __('Dọn dẹp tất cả') }}</span>
            </button>

            <span class="text-xs text-gray-400 bg-gray-100 px-3 py-2 border border-gray-200 rounded-xl font-medium">
                {{ __('Batch size: 10 email/cron run') }}
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
        <form action="{{ route('admin.logs.email_queue') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
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
                <select name="status" class="block w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="">{{ __('Tất cả trạng thái') }}</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Chờ gửi') }}</option>
                    <option value="sending" {{ request('status') === 'sending' ? 'selected' : '' }}>{{ __('Đang gửi') }}</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>{{ __('Đã gửi') }}</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>{{ __('Gửi lỗi') }}</option>
                </select>
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="email_search" 
                       value="{{ request('email_search') }}"
                       placeholder="{{ __('Người nhận (Tên, email)...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="subject" 
                       value="{{ request('subject') }}"
                       placeholder="{{ __('Tiêu đề email...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-grow px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shrink-0">
                    {{ __('Lọc') }}
                </button>
                @if($hasFilter)
                    <a href="{{ route('admin.logs.email_queue') }}" class="flex-grow inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all shadow-sm shrink-0 gap-1">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        <span>{{ __('Bỏ lọc') }}</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Form dùng chung cho các thao tác nhanh (bulk): mỗi checkbox dòng gửi lên mảng ids[] qua x-model="selected".
         Hai nút gửi lại / xóa dùng thuộc tính formaction để trỏ tới route tương ứng. --}}
    <form id="bulkEmailForm" method="POST">
        @csrf
    </form>

    <!-- Thanh thao tác nhanh (hiện khi có bản ghi được chọn) -->
    <div x-show="selected.length !== 0"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-shopee/5 border border-shopee/20 rounded-3xl px-4 py-3"
         style="display: none;">
        <div class="flex items-center gap-2 text-xs font-semibold text-shopee">
            <i data-lucide="check-check" class="w-4 h-4"></i>
            <span>{{ __('Đã chọn') }} <span x-text="selected.length"></span> {{ __('email') }}</span>
            <button type="button" @click="selected = []" class="ml-1 text-gray-400 hover:text-gray-600 underline decoration-dotted">{{ __('Bỏ chọn') }}</button>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="showBulkRetryModal = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-orange-500 hover:bg-orange-600 rounded-xl transition-all shadow-sm">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                <span>{{ __('Gửi lại các email đã chọn') }}</span>
            </button>
            <button type="button" @click="showBulkDeleteModal = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl transition-all shadow-sm">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                <span>{{ __('Xóa các email đã chọn') }}</span>
            </button>
        </div>
    </div>

    <!-- Bảng dữ liệu -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="border-b border-gray-200 text-xs font-bold text-gray-400 bg-gray-50/50">
                        <th class="p-4 w-10 text-center">
                            <input type="checkbox"
                                   @change="toggleAll($event.target.checked)"
                                   :checked="allChecked"
                                   class="w-4 h-4 rounded border-gray-300 text-shopee focus:ring-shopee/30 cursor-pointer align-middle"
                                   title="{{ __('Chọn tất cả') }}">
                        </th>
                        <th class="p-4 w-12 text-center">ID</th>
                        <th class="p-4">{{ __('Người nhận') }}</th>
                        <th class="p-4">{{ __('Tiêu đề email') }}</th>
                        <th class="p-4 text-center">{{ __('Trạng thái') }}</th>
                        <th class="p-4 text-center">{{ __('Số lần thử') }}</th>
                        <th class="p-4 text-center">{{ __('Thời gian') }}</th>
                        <th class="p-4 text-center w-24">{{ __('Hành động') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse($queues as $queue)
                        <tr :class="selected.includes('{{ $queue->id }}') ? 'bg-shopee/5' : ''">
                            <td class="p-4 text-center">
                                <input type="checkbox"
                                       form="bulkEmailForm"
                                       name="ids[]"
                                       value="{{ $queue->id }}"
                                       x-model="selected"
                                       class="w-4 h-4 rounded border-gray-300 text-shopee focus:ring-shopee/30 cursor-pointer align-middle">
                            </td>
                            <td class="p-4 font-bold text-gray-400 text-center">{{ $queue->id }}</td>
                            <td class="p-4">
                                {{-- Hiển thị thông tin người nhận email. Nếu địa chỉ email khớp với tài khoản trong hệ thống, cung cấp liên kết chỉnh sửa hồ sơ (admin.users.edit) để admin dễ dàng xử lý. --}}
                                @if($queue->user)
                                    <div class="flex items-center gap-2">
                                        <div>
                                            <p class="font-bold text-gray-950">{{ $queue->to_name ?: $queue->user->name }}</p>
                                            <p class="text-[10px] text-gray-400 font-mono">{{ $queue->to_email }}</p>
                                        </div>
                                        <a href="{{ route('admin.users.edit', $queue->user->id) }}" class="p-1 text-blue-600 hover:bg-blue-50 rounded-lg inline-flex items-center justify-center transition-colors shrink-0" title="{{ __('Chỉnh sửa thành viên') }}">
                                            <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </div>
                                @else
                                    <p class="font-bold text-gray-950">{{ $queue->to_name ?: __('N/A') }}</p>
                                    <p class="text-[10px] text-gray-400 font-mono">{{ $queue->to_email }}</p>
                                @endif
                            </td>
                            <td class="p-4 font-semibold text-gray-700 max-w-xs truncate" title="{{ $queue->subject }}">
                                {{ $queue->subject }}
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
                                    {{-- Sử dụng input và textarea ẩn để lưu trữ tiêu đề và nội dung email. 
                                         Cách này giúp tránh hoàn toàn lỗi vỡ cú pháp HTML/JS khi nội dung email chứa dấu nháy đơn, nháy kép, backtick hoặc ký tự xuống dòng. --}}
                                    <input type="hidden" id="email-subject-{{ $queue->id }}" value="{{ $queue->subject }}">
                                    <textarea id="email-body-{{ $queue->id }}" class="hidden">{{ $queue->body }}</textarea>

                                    <!-- Nút Xem trước email -->
                                    <button @click="
                                        previewSubject = document.getElementById('email-subject-{{ $queue->id }}').value;
                                        previewBody = document.getElementById('email-body-{{ $queue->id }}').value;
                                        activeTab = 'preview';
                                        showPreviewModal = true;
                                    " class="p-1.5 text-gray-400 hover:text-shopee rounded-lg hover:bg-gray-50 transition-all" title="{{ __('Xem trước nội dung email') }}">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>

                                    <!-- Nút Thử lại (nếu trạng thái là failed) -->
                                    @if($queue->status === 'failed')
                                        <form action="{{ route('admin.logs.email_queue.retry', $queue->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-orange-500 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-all" title="{{ __('Gửi lại email này') }}">
                                                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Nút Xóa -->
                                    <form action="{{ route('admin.logs.email_queue.delete', $queue->id) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa email này khỏi hàng đợi?')">
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
                                <td colspan="8" class="px-4 py-2 text-[10px] text-red-600 font-mono border-t border-red-50">
                                    <span class="font-bold">{{ __('Chi tiết lỗi:') }}</span> {{ $queue->error_message }}
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-400">{{ __('Hàng đợi email hiện tại trống.') }}</td>
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

    <!-- Modal Xem Trước Email (AlpineJS) -->
    <template x-teleport="body">
        <div x-show="showPreviewModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/40 backdrop-blur-sm"
             x-transition
             x-cloak>
            <div class="bg-white rounded-3xl shadow-xl border border-gray-200 w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden"
                 @click.away="showPreviewModal = false">
                <!-- Tiêu đề Modal -->
                <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-950 text-sm">{{ __('Xem trước Nội dung Email') }}</h3>
                        <p class="text-[10px] text-gray-500 mt-0.5 truncate max-w-md font-medium" x-text="previewSubject"></p>
                    </div>
                    <button @click="showPreviewModal = false" class="p-1 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-500 transition-all">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                {{-- Thanh điều hướng tab để chuyển đổi giữa chế độ xem trực quan (Giao diện) và xem mã nguồn thô (Textarea) --}}
                <div class="px-4 bg-gray-50 border-b border-gray-150 flex gap-4">
                    <button @click="activeTab = 'preview'" 
                            :class="activeTab === 'preview' ? 'border-shopee text-shopee font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="py-2.5 text-xs border-b-2 transition-all focus:outline-none">
                        {{ __('Giao diện') }}
                    </button>
                    <button @click="activeTab = 'source'" 
                            :class="activeTab === 'source' ? 'border-shopee text-shopee font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="py-2.5 text-xs border-b-2 transition-all focus:outline-none">
                        {{ __('Mã nguồn (HTML)') }}
                    </button>
                </div>
                
                <!-- Nội dung hiển thị chi tiết email -->
                <div class="flex-1 overflow-y-auto p-4 bg-gray-50/50">
                    {{-- Chế độ xem trực quan HTML --}}
                    {{-- Sử dụng iframe với thuộc tính sandbox (chặn hoàn toàn thực thi script) để hiển thị giao diện HTML email một cách an toàn nhất, phòng tránh lỗ hổng Stored XSS --}}
                    <iframe x-show="activeTab === 'preview'" 
                            :srcdoc="previewBody"
                            sandbox="allow-popups"
                            class="w-full min-h-[400px] bg-white rounded-2xl border border-gray-150 p-4 text-xs"
                    ></iframe>

                    {{-- Chế độ xem mã nguồn thô sử dụng Textarea cho phép dễ dàng copy và kiểm tra nội dung thẻ --}}
                    <div x-show="activeTab === 'source'" class="h-full min-h-[300px] flex flex-col">
                        <textarea readonly 
                                  x-text="previewBody"
                                  class="w-full flex-1 min-h-[300px] p-3 text-xs font-mono bg-gray-900 text-gray-100 rounded-2xl border border-gray-800 focus:outline-none focus:ring-0 resize-y"
                                  @click="$el.select()"></textarea>
                        <p class="text-[10px] text-gray-400 mt-1.5 flex items-center gap-1">
                            <i data-lucide="info" class="w-3.5 h-3.5 text-gray-400"></i>
                            {{ __('Nhấp vào ô văn bản trên để tự động bôi đen toàn bộ mã nguồn.') }}
                        </p>
                    </div>
                </div>

                <!-- Footer của Modal -->
                <div class="p-3 border-t border-gray-100 bg-gray-50/50 flex justify-end">
                    <button @click="showPreviewModal = false" class="px-4 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">
                        {{ __('Đóng') }}
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal xác nhận gửi lại hàng loạt (AlpineJS) -->
    <template x-teleport="body">
        <div x-show="showBulkRetryModal"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             x-transition
             x-cloak>
            <div class="bg-white rounded-3xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden"
                 @click.away="showBulkRetryModal = false">
                <div class="p-5 text-center">
                    <div class="mx-auto flex items-center justify-center w-12 h-12 rounded-full bg-orange-50 text-orange-500 mb-3">
                        <i data-lucide="refresh-cw" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-gray-950 text-base">{{ __('Gửi lại các email đã chọn?') }}</h3>
                    <p class="text-xs text-gray-500 mt-1.5">
                        {{ __('Hệ thống sẽ đưa') }} <span class="font-bold text-orange-600" x-text="selected.length"></span> {{ __('email về trạng thái chờ gửi và gửi lại ở lần chạy cron kế tiếp.') }}
                    </p>
                </div>
                <div class="p-4 border-t border-gray-100 bg-gray-50/50 flex justify-center gap-2">
                    <button type="button" @click="showBulkRetryModal = false"
                            class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">
                        {{ __('Hủy bỏ') }}
                    </button>
                    <button type="submit" form="bulkEmailForm" formaction="{{ route('admin.logs.email_queue.bulk_retry') }}"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-orange-500 hover:bg-orange-600 rounded-xl transition-all shadow-sm">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span>{{ __('Xác nhận gửi lại') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal xác nhận xóa hàng loạt (AlpineJS) -->
    <template x-teleport="body">
        <div x-show="showBulkDeleteModal"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             x-transition
             x-cloak>
            <div class="bg-white rounded-3xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden"
                 @click.away="showBulkDeleteModal = false">
                <div class="p-5 text-center">
                    <div class="mx-auto flex items-center justify-center w-12 h-12 rounded-full bg-red-50 text-red-500 mb-3">
                        <i data-lucide="trash-2" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-gray-950 text-base">{{ __('Xóa các email đã chọn?') }}</h3>
                    <p class="text-xs text-gray-500 mt-1.5">
                        {{ __('Bạn sắp xóa') }} <span class="font-bold text-red-600" x-text="selected.length"></span> {{ __('email khỏi hàng đợi. Hành động này không thể hoàn tác.') }}
                    </p>
                </div>
                <div class="p-4 border-t border-gray-100 bg-gray-50/50 flex justify-center gap-2">
                    <button type="button" @click="showBulkDeleteModal = false"
                            class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">
                        {{ __('Hủy bỏ') }}
                    </button>
                    <button type="submit" form="bulkEmailForm" formaction="{{ route('admin.logs.email_queue.bulk_delete') }}"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl transition-all shadow-sm">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        <span>{{ __('Xác nhận xóa') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal xác nhận dọn dẹp tất cả hàng đợi Email -->
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
                        <h3 class="text-lg font-bold text-gray-900">{{ __('Xác Nhận Dọn Dẹp Hàng Đợi Email') }}</h3>
                        <p class="text-xs text-gray-500">{{ __('Hành động này không thể hoàn tác!') }}</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                    {{ __('Bạn có chắc chắn muốn xóa toàn bộ danh sách hàng đợi email hiện có? Việc này sẽ làm rỗng danh sách các email đang chờ hoặc đã gửi.') }}
                </p>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="showClearModal = false" class="px-4 py-2.5 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all">
                        {{ __('Hủy bỏ') }}
                    </button>
                    <form action="{{ route('admin.logs.email_queue.clear') }}" method="POST" class="inline">
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
