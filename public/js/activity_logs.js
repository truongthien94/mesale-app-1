/**
 * Trình điều khiển logic danh sách nhật ký hoạt động bằng Ajax.
 * Sử dụng mô hình hướng trạng thái (State-driven) đồng bộ với Balance Logs.
 * 
 * @param {Object} config - Cấu hình từ backend.
 * @param {string} config.loadErrorMsg - Thông báo lỗi khi không tải được dữ liệu.
 */
function activityLogsHandler(config) {
    return {
        // Trạng thái hiển thị spinner tải dữ liệu
        loading: false,
        // Trạng thái có bộ lọc đang hoạt động (dùng để hiển thị/ẩn nút Xoá bộ lọc)
        hasActiveFilters: false,

        /**
         * Khởi tạo các sự kiện lắng nghe click phân trang và popstate
         */
        init() {
            setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);

            // Expose thực thể hiện tại ra window để các thành phần phân trang có thể truy cập
            window._activityLogsHandler = this;

            // Kiểm tra trạng thái bộ lọc ban đầu dựa vào URL
            this.syncActiveFiltersState();

            // Sử dụng Event Delegation để bắt các click phân trang
            const container = document.getElementById('activity-logs-list-container');
            if (container) {
                container.addEventListener('click', (e) => {
                    const link = e.target.closest('.ajax-pagination a, .pagination a');
                    if (link) {
                        e.preventDefault();
                        e.stopPropagation();
                        this.loadLogs(link.href);
                    }
                });
            }

            // Lắng nghe sự kiện popstate để xử lý tiến lùi trang
            window.addEventListener('popstate', () => {
                this.loadLogs(window.location.href, false);
            });
        },

        /**
         * Lọc dữ liệu dựa trên giá trị hiện tại của các ô nhập liệu
         */
        submitFilter() {
            const searchInput = document.getElementById('activity-logs-search-input');
            const searchVal = searchInput ? searchInput.value.trim() : '';

            // Cập nhật trạng thái hiển thị nút Đặt lại bộ lọc
            this.hasActiveFilters = searchVal !== '';

            const params = new URLSearchParams();
            if (searchVal) {
                params.set('search', searchVal);
            }

            const actionUrl = window.location.pathname;
            const fullUrl = `${actionUrl}?${params.toString()}`;
            this.loadLogs(fullUrl);
        },

        /**
         * Đặt lại toàn bộ các ô lọc về giá trị trống và tải lại danh sách sạch
         */
        resetFilters() {
            const searchInput = document.getElementById('activity-logs-search-input');
            if (searchInput) searchInput.value = '';

            this.hasActiveFilters = false;

            const cleanUrl = window.location.pathname;
            this.loadLogs(cleanUrl);
        },

        /**
         * Cập nhật trạng thái hasActiveFilters dựa trên các ô nhập liệu hiện tại
         */
        syncActiveFiltersState() {
            const searchInput = document.getElementById('activity-logs-search-input');
            const searchVal = searchInput ? searchInput.value.trim() : '';

            this.hasActiveFilters = searchVal !== '';
        },

        /**
         * Thực hiện gửi request AJAX để tải nhật ký hoạt động
         * @param {string} url - URL cần tải
         * @param {boolean} pushState - Có đẩy URL lên thanh địa chỉ trình duyệt không (mặc định: true)
         */
        loadLogs(url, pushState = true) {
            this.loading = true;
            const container = document.getElementById('activity-logs-list-container');
            const previousHTML = container ? container.innerHTML : '';

            if (container) {
                container.classList.add('pointer-events-none');
                container.innerHTML = this.getSkeletonHTML();
            }

            axios.get(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => {
                if (container) {
                    container.innerHTML = response.data;
                    container.classList.remove('pointer-events-none');
                }
                
                // Đồng bộ lại các trường nhập liệu trên form và nút Đặt lại dựa theo URL mới nhận
                this.syncFormFields(url);
                this.syncActiveFiltersState();

                // Vẽ lại Lucide icons cho dữ liệu mới render
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }

                // Cập nhật URL trên thanh địa chỉ trình duyệt
                if (pushState) {
                    window.history.pushState(null, '', url);
                }
            })
            .catch(error => {
                console.error('Lỗi khi tải AJAX activity logs:', error);
                
                // Trở lại giao diện cũ nếu request bị lỗi
                if (container) {
                    container.innerHTML = previousHTML;
                    container.classList.remove('pointer-events-none');
                }

                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { text: config.loadErrorMsg || 'Có lỗi xảy ra khi tải dữ liệu.', type: 'error' } 
                }));
            })
            .finally(() => {
                this.loading = false;
            });
        },

        /**
         * Đồng bộ ngược giá trị các ô input khi URL thay đổi (nhấn Back/Forward)
         * @param {string} url - URL hiện tại
         */
        syncFormFields(url) {
            const searchInput = document.getElementById('activity-logs-search-input');
            try {
                const urlObj = new URL(url, window.location.origin);
                const searchVal = urlObj.searchParams.get('search') || '';

                if (searchInput) searchInput.value = searchVal;
            } catch (e) {
                console.error('Lỗi đồng bộ form fields:', e);
            }
        },

        /**
         * Trả về chuỗi HTML skeleton placeholder cho cả màn hình Desktop và Mobile (10 dòng)
         * @returns {string}
         */
        getSkeletonHTML() {
            return `
                <div class="space-y-4 animate-pulse">
                    <!-- Desktop Skeleton -->
                    <div class="hidden md:block bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800/80 overflow-hidden shadow-md">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-100 dark:border-slate-800 text-xs font-bold text-gray-400 bg-gray-50/50 dark:bg-slate-900/50">
                                        <th class="p-4">Nội dung hoạt động</th>
                                        <th class="p-4 text-center">Địa chỉ IP</th>
                                        <th class="p-4 text-center">Thời gian phát sinh</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs">
                                    ${Array(10).fill(0).map(() => `
                                        <tr class="transition-colors">
                                            <td class="p-4">
                                                <div class="h-3.5 bg-gray-200 dark:bg-slate-700 rounded w-3/4"></div>
                                            </td>
                                            <td class="p-4 text-center">
                                                <div class="h-3.5 bg-gray-100 dark:bg-slate-850 rounded w-20 mx-auto"></div>
                                            </td>
                                            <td class="p-4 text-center">
                                                <div class="h-3 bg-gray-100 dark:bg-slate-800 rounded w-24 mx-auto"></div>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile Skeleton -->
                    <div class="block md:hidden space-y-4">
                        ${Array(5).fill(0).map(() => `
                            <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-gray-100/80 dark:border-slate-800/80 space-y-3 shadow-sm">
                                <div class="flex justify-between items-center pb-2 border-b border-gray-100/50 dark:border-slate-800/50">
                                    <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded w-16"></div>
                                    <div class="h-3 bg-gray-100 dark:bg-slate-800 rounded w-20"></div>
                                </div>
                                <div class="h-3.5 bg-gray-200 dark:bg-slate-700 rounded w-5/6"></div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
    };
}
