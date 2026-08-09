{{--
    Component tạo nhanh nội dung bài viết blog bằng AI.
    Nút + modal cùng nằm trong 1 scope x-data để chia sẻ trạng thái.
--}}
<div x-data="postAiGenerator()" class="inline-flex">
    <button type="button" @click="open()"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-gradient-to-r from-violet-500 to-fuchsia-500 hover:from-violet-600 hover:to-fuchsia-600 rounded-lg transition-all shadow-sm shadow-violet-500/20">
        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
        {{ __('Tạo bằng AI') }}
    </button>

    {{-- Modal nhập yêu cầu tạo nội dung --}}
    <div x-show="show" x-cloak
         class="fixed inset-0 z-[80] flex items-center justify-center p-4"
         @keydown.escape.window="show = false">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="show = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col z-10"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            {{-- Lớp phủ loader khi AI đang xử lý: phủ toàn bộ modal với hiệu ứng xoay --}}
            <div x-show="loading" x-cloak
                 class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm rounded-2xl">
                <div class="w-10 h-10 border-[3px] border-violet-200 dark:border-violet-900 border-t-violet-500 rounded-full animate-spin"></div>
                <p class="text-xs font-semibold text-violet-600 dark:text-violet-400 animate-pulse">{{ __('AI đang soạn nội dung...') }}</p>
            </div>
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-slate-800 shrink-0">
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-slate-100 text-sm flex items-center gap-2">
                        <i data-lucide="sparkles" class="w-4 h-4 text-violet-500"></i>
                        {{ __('Tạo nội dung bài viết bằng AI') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ __('Mô tả chủ đề, AI sẽ soạn bài viết HTML hoàn chỉnh cho bạn') }}</p>
                </div>
                <button @click="show = false" type="button" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-xl transition-all">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-5 space-y-4">
                {{-- Thông báo lỗi --}}
                <div x-show="error" x-cloak class="p-3 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/30 rounded-xl flex gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 shrink-0 mt-0.5"></i>
                    <p class="text-xs text-red-600 dark:text-red-400 leading-relaxed" x-text="error"></p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        {{ __('Mô tả chủ đề / yêu cầu') }} <span class="text-red-500">*</span>
                    </label>
                    <textarea x-model="prompt" rows="4"
                              placeholder="{{ __('VD: Viết bài hướng dẫn cách săn mã giảm giá Shopee và nhận hoàn tiền tối đa cho người mới...') }}"
                              class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200"></textarea>
                    <p class="text-[10px] text-gray-400 mt-1">{{ __('Tiêu đề bài viết hiện tại (nếu có) sẽ được dùng làm ngữ cảnh cho AI.') }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        {{ __('Giọng điệu') }}
                    </label>
                    <select x-model="tone"
                            class="w-full px-3 py-2.5 text-xs border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                        <option value="than-thien">{{ __('Thân thiện, gần gũi') }}</option>
                        <option value="chuyen-nghiep">{{ __('Chuyên nghiệp, trang trọng') }}</option>
                        <option value="huong-dan">{{ __('Hướng dẫn chi tiết từng bước') }}</option>
                        <option value="review">{{ __('Đánh giá, review sản phẩm') }}</option>
                    </select>
                </div>

                <label class="flex items-center gap-2.5 p-3 border border-gray-200 dark:border-slate-700 rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-800 transition-all">
                    <input type="checkbox" x-model="autofill" class="text-violet-500 rounded focus:ring-violet-500">
                    <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">{{ __('Tự động điền cả Tóm tắt, Tiêu đề & SEO (nếu đang trống)') }}</span>
                </label>

                <div class="p-3 bg-violet-50 dark:bg-violet-950/20 rounded-xl border border-violet-100 dark:border-violet-900/30 flex gap-2">
                    <i data-lucide="info" class="w-3.5 h-3.5 text-violet-500 shrink-0 mt-0.5"></i>
                    <p class="text-[10px] text-violet-700 dark:text-violet-400 leading-relaxed">
                        {{ __('Nội dung hiện tại trong trình soạn thảo sẽ bị thay thế bằng kết quả AI tạo ra.') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-gray-200 dark:border-slate-800 shrink-0">
                <button @click="show = false" type="button"
                        class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-slate-400 bg-gray-100 dark:bg-slate-800 rounded-xl hover:bg-gray-200 dark:hover:bg-slate-700 transition-all">
                    {{ __('Huỷ') }}
                </button>
                <button @click="generate()" type="button" :disabled="loading"
                        class="inline-flex items-center gap-2 px-5 py-2 text-xs font-bold text-white bg-gradient-to-r from-violet-500 to-fuchsia-500 hover:from-violet-600 hover:to-fuchsia-600 rounded-xl transition-all shadow-sm shadow-violet-500/20 disabled:opacity-60 disabled:cursor-not-allowed">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }"></i>
                    <span x-text="loading ? '{{ __('Đang tạo...') }}' : '{{ __('Tạo nội dung') }}'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function postAiGenerator() {
        return {
            aiEnabled: {{ ($aiEnabled ?? false) ? 'true' : 'false' }},
            show: false,
            prompt: '',
            tone: 'than-thien',
            autofill: true,
            loading: false,
            error: '',

            open() {
                if (!this.aiEnabled) {
                    alert("{{ __('Dịch vụ AI hiện đang tắt. Vui lòng kích hoạt trong Cài đặt > AI.') }}");
                    return;
                }
                this.error = '';
                this.show = true;
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },

            // Điền giá trị vào input/textarea nếu đang trống (hoặc luôn điền khi force = true)
            fillField(selector, value, force) {
                if (!value) return;
                const el = document.querySelector(selector);
                if (el && (force || !el.value.trim())) {
                    el.value = value;
                    // Gửi sự kiện input để AlpineJS đồng bộ dữ liệu (ví dụ tự sinh slug từ tiêu đề)
                    el.dispatchEvent(new Event('input'));
                }
            },

            generate() {
                if (this.loading) return;
                if (!this.prompt.trim()) {
                    this.error = "{{ __('Vui lòng mô tả nội dung bài viết bạn muốn tạo.') }}";
                    return;
                }
                this.loading = true;
                this.error = '';
                const title = document.querySelector('input[name=title]')?.value || '';
                fetch('{{ route('admin.blog.posts.generate_ai') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ prompt: this.prompt, title: title, tone: this.tone })
                })
                .then(r => r.json().then(data => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok || !data.success) {
                        this.error = data.message || "{{ __('Không thể tạo nội dung. Vui lòng thử lại.') }}";
                        return;
                    }
                    // Đổ nội dung chính vào trình soạn thảo TinyMCE (hoặc textarea nếu chưa init)
                    if (data.content) {
                        const editor = window.tinymce ? tinymce.get('content-editor') : null;
                        if (editor) {
                            editor.setContent(data.content);
                        } else {
                            const ta = document.getElementById('content-editor');
                            if (ta) ta.value = data.content;
                        }
                    }
                    // Tự điền các trường còn lại nếu admin bật tùy chọn và trường đang trống
                    if (this.autofill) {
                        this.fillField('input[name=title]', data.title, false);
                        this.fillField('textarea[name=summary]', data.summary, false);
                        this.fillField('input[name=seo_title]', data.seo_title, false);
                        this.fillField('textarea[name=seo_description]', data.seo_description, false);
                        this.fillField('input[name=seo_keywords]', data.seo_keywords, false);
                    }
                    this.show = false;
                })
                .catch(() => {
                    this.error = "{{ __('Lỗi kết nối. Vui lòng thử lại.') }}";
                })
                .finally(() => { this.loading = false; });
            },
        };
    }
</script>
