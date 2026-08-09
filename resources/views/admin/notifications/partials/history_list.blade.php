<!-- Danh sách thông báo đã gửi dưới dạng Card Premium, hỗ trợ responsive và Dark Mode -->
<div class="space-y-4">
    @forelse($notifications as $noti)
        <!-- Mỗi thông báo được bọc trong một Card, phân biệt màu sắc nhẹ theo loại thông báo -->
        <div class="p-5 rounded-2xl border transition-all duration-200 hover:shadow-md dark:hover:shadow-lg dark:hover:shadow-slate-950/30 bg-white dark:bg-slate-900 hover:border-gray-300/80 dark:hover:border-slate-700
                    {{ $noti->type === 'general' ? 'border-orange-100 dark:border-orange-950/20 bg-orange-50/5 dark:bg-orange-950/5' : 'border-gray-200 dark:border-slate-800' }}">
            
            <!-- Phần tiêu đề và thông tin chung -->
            <div class="flex justify-between items-start gap-4 mb-2">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h4 class="font-bold text-gray-900 dark:text-slate-100 text-sm leading-snug">{{ $noti->title }}</h4>
                        
                        <!-- Badge phân loại loại tin nhắn (Hệ thống hoặc Cá nhân) -->
                        @if($noti->type === 'general')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-100 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/30">
                                {{ __('Chung (Hệ thống)') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-600 border border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30">
                                {{ __('Cá nhân (1 User)') }}
                            </span>
                        @endif
                    </div>
                    <!-- Định dạng thời gian tạo bản ghi -->
                    <span class="text-[10px] text-gray-400 dark:text-slate-500 font-mono block">{{ $noti->created_at->format('d/m/Y H:i:s') }}</span>
                </div>
                
                <!-- Badge hiển thị trạng thái xem thông báo của thành viên -->
                <span class="inline-flex items-center gap-1 text-[10px] font-semibold shrink-0">
                    @if($noti->is_read)
                        <span class="flex items-center gap-1 text-green-600 bg-green-50 px-2 py-0.5 rounded-full border border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30">
                            <i data-lucide="eye" class="w-3 h-3"></i> {{ __('Đã xem') }}
                        </span>
                    @else
                        <span class="flex items-center gap-1 text-gray-500 bg-gray-50 px-2 py-0.5 rounded-full border border-gray-100 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700">
                            <i data-lucide="eye-off" class="w-3 h-3"></i> {{ __('Chưa xem') }}
                        </span>
                    @endif
                </span>
            </div>

            <!-- Nội dung chi tiết của thông báo -->
            <p class="text-xs text-gray-600 dark:text-slate-300 leading-relaxed bg-gray-50/50 dark:bg-slate-950/30 p-3 rounded-xl border border-gray-100/50 dark:border-slate-850 mb-3">{{ $noti->content }}</p>
            
            <!-- Thông tin người nhận của thông báo -->
            <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-slate-400 border-t border-gray-100/70 dark:border-slate-800 pt-2.5">
                <span class="flex items-center gap-1">
                    <i data-lucide="user" class="w-3.5 h-3.5 text-gray-400 dark:text-slate-500"></i>
                    {{ __('Người nhận:') }}
                    <strong class="text-gray-700 dark:text-slate-300 font-bold ml-0.5">
                        {{ $noti->user ? $noti->user->email : __('Hệ thống') }}
                    </strong>
                    @if($noti->user)
                        <span class="text-gray-400 dark:text-slate-500">({{ $noti->user->name }})</span>
                    @endif
                </span>
            </div>
        </div>
    @empty
        <!-- Trường hợp không có dữ liệu phù hợp -->
        <div class="text-center py-12 bg-gray-50/50 dark:bg-slate-950/10 rounded-2xl border border-dashed border-gray-200 dark:border-slate-800">
            <i data-lucide="mail-warning" class="w-10 h-10 text-gray-300 dark:text-slate-700 mx-auto mb-2"></i>
            <p class="text-xs text-gray-400 dark:text-slate-500 font-medium">{{ __('Không tìm thấy thông báo nào phù hợp với bộ lọc.') }}</p>
        </div>
    @endforelse
</div>

<!-- Phân trang dữ liệu Laravel, tự động giữ query parameters nhờ withQueryString() -->
@if($notifications->hasPages())
    <div class="pt-4 border-t border-gray-100 dark:border-slate-800">
        {{ $notifications->links() }}
    </div>
@endif
