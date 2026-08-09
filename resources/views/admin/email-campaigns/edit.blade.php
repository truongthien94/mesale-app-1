@extends('layouts.admin')

@section('title', __('Chỉnh Sửa Chiến Dịch Email') . ' - ' . $siteName)

@section('styles')
{{-- TinyMCE: trình soạn thảo trực quan (WYSIWYG) cho nội dung email --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    // Mở cửa sổ elFinder để chọn ảnh từ thư viện media của hệ thống
    function openElfinderPopup(inputId) {
        var width = 900, height = 600;
        var left = (screen.width - width) / 2;
        var top = (screen.height - height) / 2;
        var url = '{{ url("elfinder/popup") }}/' + inputId;
        window.open(url, 'elfinderPicker', 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes,status=no');
    }
    // Callback toàn cục được elFinder gọi sau khi người dùng chọn ảnh xong
    window.processSelectedFile = function(fileUrl, inputId) {
        if (inputId === 'tinymce_image' && window.tinymceFilePickerCallback) {
            window.tinymceFilePickerCallback(fileUrl, { alt: 'Ảnh email' });
            window.tinymceFilePickerCallback = null;
        }
    };
</script>
@endsection

@section('content')
<div class="space-y-6" x-data="campaignForm()">

    {{-- Tiêu đề --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.email_campaigns.show', $emailCampaign) }}"
           class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-xl transition-all">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ __('Chỉnh Sửa Chiến Dịch Email') }}</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ $emailCampaign->name }}</p>
        </div>
    </div>

    <form action="{{ route('admin.email_campaigns.update', $emailCampaign) }}" method="POST">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- CỘT TRÁI: NỘI DUNG --}}
            <div class="lg:col-span-8 space-y-5">

                <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 space-y-4">
                    <h3 class="font-bold text-sm text-gray-800 dark:text-slate-100 flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-slate-800">
                        <i data-lucide="info" class="w-4 h-4 text-shopee"></i>
                        {{ __('Thông tin chiến dịch') }}
                    </h3>

                    <div>
                        <label for="name" class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            {{ __('Tên chiến dịch') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name', $emailCampaign->name) }}" required
                               class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                        @error('name') <p class="text-[10px] text-red-500 font-semibold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="subject" class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            {{ __('Tiêu đề email (Subject)') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="subject" name="subject" value="{{ old('subject', $emailCampaign->subject) }}"
                               x-model="subject" required
                               class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                        @error('subject') <p class="text-[10px] text-red-500 font-semibold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">{{ __('Tên người gửi') }}</label>
                            <input type="text" name="from_name" value="{{ old('from_name', $emailCampaign->from_name) }}"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">{{ __('Email người gửi') }}</label>
                            <input type="email" name="from_email" value="{{ old('from_email', $emailCampaign->from_email) }}"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                        </div>
                    </div>
                </div>

                {{-- Soạn nội dung --}}
                <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-slate-800">
                        <h3 class="font-bold text-sm text-gray-800 dark:text-slate-100 flex items-center gap-2">
                            <i data-lucide="code-2" class="w-4 h-4 text-shopee"></i>
                            {{ __('Nội dung email (HTML)') }}
                        </h3>
                        <button type="button" @click="previewEmail()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-100 transition-all">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            {{ __('Xem trước') }}
                        </button>
                    </div>

                    <div class="p-3 bg-amber-50 dark:bg-amber-950/20 rounded-xl border border-amber-100 dark:border-amber-900/30">
                        <p class="text-xs font-bold text-amber-700 dark:text-amber-400 mb-2 flex items-center gap-1.5">
                            <i data-lucide="variable" class="w-3.5 h-3.5"></i>
                            {{ __('Biến có thể dùng:') }}
                        </p>
                        @php $varBtnClass = 'inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-mono font-bold text-amber-800 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/30 rounded border border-amber-200 dark:border-amber-800/50 hover:bg-amber-200 dark:hover:bg-amber-800/40 transition-all cursor-pointer'; @endphp
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" @click="insertVariable('@{{name}}')" class="{{ $varBtnClass }}" title="{{ __('Tên thành viên') }}">@{{name}}</button>
                            <button type="button" @click="insertVariable('@{{email}}')" class="{{ $varBtnClass }}" title="{{ __('Email thành viên') }}">@{{email}}</button>
                            <button type="button" @click="insertVariable('@{{balance}}')" class="{{ $varBtnClass }}" title="{{ __('Số dư ví') }}">@{{balance}}</button>
                            <button type="button" @click="insertVariable('@{{referral_code}}')" class="{{ $varBtnClass }}" title="{{ __('Mã giới thiệu') }}">@{{referral_code}}</button>
                            <button type="button" @click="insertVariable('@{{site_name}}')" class="{{ $varBtnClass }}" title="{{ __('Tên website') }}">@{{site_name}}</button>
                        </div>
                    </div>

                    {{-- TinyMCE thay thế textarea này thành trình soạn thảo trực quan; vẫn giữ name="body" để gửi form --}}
                    <textarea id="body" name="body" rows="18" class="w-full">{{ old('body', $emailCampaign->body) }}</textarea>
                    @error('body') <p class="text-[10px] text-red-500 font-semibold mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- CỘT PHẢI: SETTINGS --}}
            <div class="lg:col-span-4 space-y-5">

                {{-- Target audience --}}
                <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 space-y-4">
                    <h3 class="font-bold text-sm text-gray-800 dark:text-slate-100 flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-slate-800">
                        <i data-lucide="users" class="w-4 h-4 text-shopee"></i>
                        {{ __('Đối tượng nhắm mục tiêu') }}
                    </h3>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">{{ __('Nhóm người nhận') }} <span class="text-red-500">*</span></label>
                        <select name="target_audience" x-model="audience" @change="fetchRecipientCount()"
                                class="w-full px-3 py-2.5 text-xs border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                            @foreach($audiences as $val => $label)
                                <option value="{{ $val }}" {{ old('target_audience', $emailCampaign->target_audience) === $val ? 'selected' : '' }}>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-3 p-3 bg-gray-50 dark:bg-slate-950/30 rounded-xl border border-gray-100 dark:border-slate-800">
                        <p class="text-[10px] font-bold text-gray-500 dark:text-slate-500 uppercase tracking-wider">{{ __('Lọc bổ sung (tuỳ chọn)') }}</p>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-slate-400 mb-1">{{ __('Số dư tối thiểu (đ)') }}</label>
                            <input type="number" name="min_balance" x-ref="minBalance"
                                   value="{{ old('min_balance', $emailCampaign->target_filter['min_balance'] ?? '') }}"
                                   min="0" @change="fetchRecipientCount()" placeholder="0"
                                   class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 dark:text-slate-200">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-slate-400 mb-1">{{ __('Đăng ký sau ngày') }}</label>
                            <input type="date" name="registered_after" x-ref="registeredAfter"
                                   value="{{ old('registered_after', $emailCampaign->target_filter['registered_after'] ?? '') }}"
                                   @change="fetchRecipientCount()"
                                   class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 dark:text-slate-200">
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-shopee/5 dark:bg-shopee/10 rounded-xl border border-shopee/10 dark:border-shopee/20">
                        <div class="flex items-center gap-2">
                            <i data-lucide="users-2" class="w-4 h-4 text-shopee"></i>
                            <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">{{ __('Ước tính người nhận') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span x-show="loadingCount" class="text-xs text-gray-400 animate-pulse">{{ __('Đang đếm...') }}</span>
                            <span x-show="!loadingCount" class="text-lg font-black text-shopee" x-text="recipientCount.toLocaleString()"></span>
                        </div>
                    </div>
                </div>

                {{-- Lên lịch --}}
                <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 space-y-4">
                    <h3 class="font-bold text-sm text-gray-800 dark:text-slate-100 flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-slate-800">
                        <i data-lucide="calendar-clock" class="w-4 h-4 text-shopee"></i>
                        {{ __('Thời gian gửi') }}
                    </h3>

                    <div class="space-y-2">
                        <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer transition-all"
                               :class="scheduleType === 'now' ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800'">
                            <input type="radio" name="schedule_type" value="now" x-model="scheduleType" class="text-shopee focus:ring-shopee">
                            <div>
                                <p class="text-xs font-bold text-gray-800 dark:text-slate-200">{{ __('Lưu nháp (gửi thủ công)') }}</p>
                                <p class="text-[10px] text-gray-500 dark:text-slate-400">{{ __('Chiến dịch ở trạng thái Nháp') }}</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer transition-all"
                               :class="scheduleType === 'scheduled' ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800'">
                            <input type="radio" name="schedule_type" value="scheduled" x-model="scheduleType" class="text-shopee focus:ring-shopee">
                            <div>
                                <p class="text-xs font-bold text-gray-800 dark:text-slate-200">{{ __('Đánh dấu đã lên lịch') }}</p>
                                <p class="text-[10px] text-gray-500 dark:text-slate-400">{{ __('Chuyển sang trạng thái Scheduled') }}</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="space-y-2">
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 px-5 py-3 text-sm font-bold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shadow-shopee/20">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        {{ __('Lưu thay đổi') }}
                    </button>
                    <a href="{{ route('admin.email_campaigns.show', $emailCampaign) }}"
                       class="w-full flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-gray-600 dark:text-slate-400 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">
                        {{ __('Huỷ') }}
                    </a>
                </div>
            </div>
        </div>
    </form>

    {{-- Teleport modal xem trước email ra body để backdrop hiển thị full màn hình --}}
    <template x-teleport="body">
        <div x-show="showPreview" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" @keydown.escape.window="showPreview = false">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showPreview = false"></div>
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col z-10">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-slate-800 shrink-0">
                    <h3 class="font-bold text-gray-900 dark:text-slate-100 text-sm flex items-center gap-2">
                        <i data-lucide="eye" class="w-4 h-4 text-shopee"></i>
                        {{ __('Xem trước email') }}
                    </h3>
                    <button @click="showPreview = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-xl transition-all">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-5">
                    <div class="mb-3 p-3 bg-gray-50 dark:bg-slate-800 rounded-xl">
                        <p class="text-xs text-gray-500 dark:text-slate-400"><span class="font-bold">Subject:</span> <span x-text="previewSubject"></span></p>
                    </div>
                    <div class="border border-gray-200 dark:border-slate-700 rounded-xl overflow-hidden">
                        <iframe id="preview-iframe" class="w-full min-h-[400px]" frameborder="0" srcdoc=""></iframe>
                    </div>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection

@section('scripts')
<script>
function campaignForm() {
    return {
        audience: '{{ old('target_audience', $emailCampaign->target_audience) }}',
        scheduleType: '{{ old('schedule_type', $emailCampaign->status === 'scheduled' ? 'scheduled' : 'now') }}',
        recipientCount: 0,
        loadingCount: false,
        subject: @json(old('subject', $emailCampaign->subject)),
        body: @json(old('body', $emailCampaign->body)),
        showPreview: false,
        previewSubject: '',
        editor: null,

        init() {
            this.fetchRecipientCount();
            this.initEditor();
        },

        // Khởi tạo trình soạn thảo TinyMCE và đồng bộ nội dung 2 chiều với biến body của Alpine
        initEditor() {
            const self = this;
            const isDark = document.documentElement.classList.contains('dark');
            tinymce.init({
                selector: '#body',
                height: 480,
                menubar: false,
                language: 'vi',
                branding: false,
                promotion: false,
                plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount',
                toolbar: 'undo redo | blocks fontsize | bold italic forecolor backcolor | alignleft aligncenter alignright | bullist numlist outdent indent | link image table | removeformat code preview fullscreen',
                content_style: 'body { font-family: Arial, sans-serif; font-size: 14px }',
                skin: isDark ? 'oxide-dark' : 'oxide',
                content_css: isDark ? 'dark' : 'default',
                // Giữ nguyên toàn bộ HTML/inline-style cho email, không để TinyMCE lọc bỏ
                valid_elements: '*[*]',
                verify_html: false,
                file_picker_callback: function (callback, value, meta) {
                    if (meta.filetype === 'image') {
                        window.tinymceFilePickerCallback = callback;
                        openElfinderPopup('tinymce_image');
                    }
                },
                setup: function (editor) {
                    self.editor = editor;
                    editor.on('init', function () {
                        if (self.body) editor.setContent(self.body);
                    });
                    // Mỗi khi nội dung thay đổi, đồng bộ ngược về biến body của Alpine
                    editor.on('change keyup undo redo SetContent', function () {
                        self.body = editor.getContent();
                    });
                }
            });
        },

        fetchRecipientCount() {
            this.loadingCount = true;
            const params = new URLSearchParams({
                target_audience: this.audience,
                min_balance: this.$refs.minBalance?.value || '',
                registered_after: this.$refs.registeredAfter?.value || '',
            });
            fetch('{{ route('admin.email_campaigns.count_recipients') }}?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => { this.recipientCount = data.count; this.loadingCount = false; })
            .catch(() => { this.loadingCount = false; });
        },

        insertVariable(variable) {
            // Chèn biến vào vị trí con trỏ trong trình soạn thảo TinyMCE
            if (this.editor) {
                this.editor.insertContent(variable);
                this.body = this.editor.getContent();
                this.editor.focus();
            }
        },

        previewEmail() {
            // Đồng bộ nội dung mới nhất từ TinyMCE trước khi xem trước
            if (this.editor) this.body = this.editor.getContent();
            if (!this.body.trim()) {
                alert("{{ __('Vui lòng nhập nội dung email trước khi xem trước.') }}");
                return;
            }
            fetch('{{ route('admin.email_campaigns.preview') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ subject: this.subject, body: this.body })
            })
            .then(r => {
                if (!r.ok) throw new Error('Preview failed: ' + r.status);
                return r.json();
            })
            .then(data => {
                this.previewSubject = data.subject ?? '';
                this.showPreview = true;
                this.$nextTick(() => {
                    const iframe = document.getElementById('preview-iframe');
                    if (iframe) iframe.srcdoc = data.body ?? '';
                });
            })
            .catch(err => {
                console.error(err);
                alert("{{ __('Không thể tải xem trước. Vui lòng thử lại.') }}");
            });
        },
    }
}
</script>
@endsection
