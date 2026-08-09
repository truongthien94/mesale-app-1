@extends('layouts.admin')

@section('title', 'Quản lý Bình Luận - Trang Quản Trị')

@section('content')
<div class="space-y-6" x-data="{ 
    replyModalOpen: false, 
    commentId: null, 
    commentAuthor: '', 
    commentContent: '',
    openReplyModal(comment) {
        this.commentId = comment.id;
        this.commentAuthor = comment.author_name;
        this.commentContent = comment.content;
        this.replyModalOpen = true;
        this.$refs.replyForm.action = '/' + window.adminPrefix + '/blog/comments/' + comment.id + '/reply';
    }
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Quản lý Bình Luận Blog</h1>
            <p class="text-sm text-gray-500">Kiểm duyệt và phản hồi các bình luận từ độc giả trên các bài viết.</p>
        </div>
    </div>

    <!-- Filter Status tab style -->
    <div class="flex items-center gap-2 border-b border-gray-200 dark:border-slate-800 pb-px">
        <a href="{{ route('admin.blog.comments.index') }}" class="px-4 py-2.5 text-sm font-semibold border-b-2 transition-all {{ request('status') === null ? 'border-shopee text-shopee' : 'border-transparent text-gray-550 hover:text-gray-900' }}">
            Tất cả bình luận
        </a>
        <a href="{{ route('admin.blog.comments.index', ['status' => 'pending']) }}" class="px-4 py-2.5 text-sm font-semibold border-b-2 transition-all {{ request('status') === 'pending' ? 'border-shopee text-shopee' : 'border-transparent text-gray-550 hover:text-gray-900' }}">
            Chờ duyệt
        </a>
        <a href="{{ route('admin.blog.comments.index', ['status' => 'approved']) }}" class="px-4 py-2.5 text-sm font-semibold border-b-2 transition-all {{ request('status') === 'approved' ? 'border-shopee text-shopee' : 'border-transparent text-gray-550 hover:text-gray-900' }}">
            Đã duyệt
        </a>
    </div>

    <!-- Data Table -->
    <div class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-55 dark:bg-slate-950 border-b border-gray-100 dark:border-slate-855 text-xs font-bold text-gray-600 dark:text-slate-300 uppercase tracking-wider">
                        <th class="px-6 py-4 min-w-[180px]">Độc giả</th>
                        <th class="px-6 py-4 min-w-[200px]">Nội dung bình luận</th>
                        <th class="px-6 py-4 min-w-[150px]">Bài viết</th>
                        <th class="px-6 py-4">Trạng thái</th>
                        <th class="px-6 py-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                                  @forelse($comments as $comment)
                    {{-- 
                        Sử dụng AlpineJS (x-data) quản lý trạng thái của từng bình luận cục bộ 
                        để đồng bộ trạng thái hiển thị ngay lập tức khi Admin click duyệt nhanh.
                    --}}
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20" x-data="{ status: '{{ $comment->status }}' }">
                        <td class="px-6 py-4 min-w-[180px] whitespace-normal">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ substr($comment->author_name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <span class="font-semibold text-gray-900 dark:text-white block">{{ $comment->author_name }}</span>
                                    <span class="text-xs text-gray-400 block mt-0.5">{{ $comment->author_email }}</span>
                                    <span class="text-[10px] text-gray-450 block mt-0.5">IP: {{ $comment->ip_address }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 max-w-sm min-w-[200px] whitespace-normal">
                            <p class="text-gray-700 dark:text-slate-300 line-clamp-3 leading-relaxed">
                                {{ $comment->content }}
                            </p>
                            <span class="text-[10px] text-gray-400 block mt-1">Đăng lúc: {{ $comment->created_at->format('H:i d/m/Y') }}</span>
                        </td>
                        <td class="px-6 py-4 max-w-xs min-w-[150px] whitespace-normal">
                            @if($comment->post)
                            <a href="{{ route('blog.show', $comment->post->slug) }}" target="_blank" class="text-xs font-semibold text-gray-600 hover:text-shopee dark:text-slate-350 dark:hover:text-shopee-light hover:underline line-clamp-2">
                                {{ $comment->post->title }}
                            </a>
                            @else
                            <span class="text-xs text-red-500">Bài viết đã bị xóa</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            {{-- 
                                Badge trạng thái hiển thị Đã duyệt / Chờ duyệt.
                                Cho phép click trực tiếp vào Badge để chuyển đổi nhanh trạng thái thông qua AJAX.
                            --}}
                            <button @click="
                                axios.post('/' + window.adminPrefix + '/blog/comments/{{ $comment->id }}/toggle-status')
                                    .then(res => {
                                        if (res.data.success) {
                                            status = res.data.status;
                                            window.dispatchEvent(new CustomEvent('toast', { detail: { text: res.data.message, type: 'success' } }));
                                        }
                                    });
                            "
                            class="px-2.5 py-0.5 text-xs font-bold rounded-full border transition-all cursor-pointer inline-block status-badge"
                            :class="status === 'approved' ? 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100/50' : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100/50'">
                                <span x-text="status === 'approved' ? 'Đã duyệt' : 'Chờ duyệt'"></span>
                            </button>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($comment->status === 'pending')
                                {{-- 
                                    Nút Duyệt nhanh bình luận bằng màu xanh lá (chỉ hiển thị khi bình luận đang ở trạng thái 'pending' - Chờ duyệt).
                                    Click vào nút này sẽ gửi AJAX duyệt bình luận, đồng thời ẩn nút đi và cập nhật badge trạng thái.
                                --}}
                                <button type="button"
                                        x-show="status === 'pending'"
                                        @click="
                                            axios.post('/' + window.adminPrefix + '/blog/comments/{{ $comment->id }}/toggle-status')
                                                .then(res => {
                                                    if (res.data.success) {
                                                        status = res.data.status;
                                                        window.dispatchEvent(new CustomEvent('toast', { detail: { text: res.data.message, type: 'success' } }));
                                                    }
                                                });
                                        "
                                        class="p-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 hover:text-emerald-700 rounded-lg border border-emerald-200 transition-all flex items-center justify-center"
                                        title="Duyệt bình luận này">
                                    <i data-lucide="check" class="w-4 h-4 pointer-events-none"></i>
                                </button>
                                @endif

                                <button type="button" 
                                        @click="openReplyModal({
                                            id: {{ $comment->id }},
                                            author_name: '{{ addslashes($comment->author_name) }}',
                                            content: '{{ addslashes($comment->content) }}'
                                        })"
                                        class="p-1.5 bg-gray-50 hover:bg-shopee/10 text-gray-600 hover:text-shopee rounded-lg border border-gray-150 transition-all" 
                                        title="Phản hồi bình luận">
                                    <i data-lucide="reply" class="w-4 h-4 pointer-events-none"></i>
                                </button>
                                <form action="{{ route('admin.blog.comments.destroy', $comment->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bình luận này và phản hồi của nó?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 bg-gray-50 hover:bg-red-50 text-gray-600 hover:text-red-500 rounded-lg border border-gray-150 transition-all" title="Xóa bình luận">
                                        <i data-lucide="trash-2" class="w-4 h-4 pointer-events-none"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            <i data-lucide="message-square" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                            Chưa có bình luận nào được gửi lên.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($comments->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-850">
            {{ $comments->links() }}
        </div>
        @endif
    </div>

    <!-- AlpineJS Reply Modal Popup -->
    <template x-teleport="body">
        <div x-show="replyModalOpen" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             x-cloak>
            <div @click.away="replyModalOpen = false" 
                 class="w-full max-w-lg bg-white dark:bg-slate-900 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-slate-800 p-6 space-y-4">
                
                <div class="flex items-center justify-between pb-3 border-b border-gray-50 dark:border-slate-850">
                    <h3 class="font-bold text-gray-900 dark:text-white text-base">Phản Hồi Bình Luận</h3>
                    <button @click="replyModalOpen = false" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
    
                <div class="p-3 bg-gray-55/40 dark:bg-slate-800/40 border border-gray-100/50 dark:border-slate-850/50 rounded-xl text-xs text-gray-600 dark:text-slate-400 space-y-1">
                    <p>Bình luận của <strong x-text="commentAuthor"></strong>:</p>
                    <p class="italic" x-text="commentContent"></p>
                </div>
    
                <form x-ref="replyForm" action="" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase mb-2">Nội dung phản hồi *</label>
                        <textarea name="content" required rows="4" placeholder="Nhập nội dung trả lời từ Admin..." class="w-full px-4 py-3 text-sm rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="replyModalOpen = false" class="px-4 py-2 text-xs font-bold text-gray-500 bg-gray-50 hover:bg-gray-100 rounded-xl border border-gray-200 dark:border-slate-850 transition-all">Hủy</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-shopee hover:bg-shopee-dark rounded-xl shadow-md transition-all">Gửi phản hồi</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection
