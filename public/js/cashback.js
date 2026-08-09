/**
 * Trình điều khiển logic danh sách lịch sử hoàn tiền bằng Ajax.
 * Sử dụng mô hình hướng trạng thái (State-driven) đồng bộ với Gifts.
 * 
 * @param {Object} config - Cấu hình từ backend.
 * @param {string} config.loadErrorMsg - Thông báo lỗi khi không tải được dữ liệu.
 */
function cashbackHistoryHandler(config) {
    return {
        loading: false,
        hasActiveFilters: false,
        searchExpanded: false,
        openModal: false,
        selectedItem: null,
        activeChip: new URLSearchParams(window.location.search).get('status') || '',

        /**
         * Khởi tạo các sự kiện lắng nghe click phân trang và popstate
         */
        init() {
            setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);

            // Expose thực thể hiện tại ra window để các thành phần phân trang có thể truy cập
            window._cashbackHistoryHandler = this;

            // Kiểm tra trạng thái bộ lọc ban đầu dựa vào URL
            this.syncActiveFiltersState();

            // Tự mở form tìm kiếm nâng cao nếu người dùng đang có sẵn từ khoá hoặc bộ lọc thời gian/nền tảng
            const initialParams = new URLSearchParams(window.location.search);
            if (initialParams.get('search') || initialParams.get('platform') || initialParams.get('start_date') || initialParams.get('end_date')) {
                this.searchExpanded = true;
            }

            // Sử dụng Event Delegation để bắt các click phân trang
            const container = document.getElementById('cashback-list-container');
            if (container) {
                container.addEventListener('click', (e) => {
                    const link = e.target.closest('.ajax-pagination a, .pagination a');
                    if (link) {
                        e.preventDefault();
                        e.stopPropagation();
                        this.loadCashback(link.href);
                    }
                });
            }

            // Lắng nghe sự kiện popstate để xử lý tiến lùi trang
            window.addEventListener('popstate', () => {
                this.loadCashback(window.location.href, false);
            });
        },

        /**
         * Lọc dữ liệu dựa trên giá trị hiện tại của các ô nhập liệu
         */
        submitFilter() {
            const searchInput = document.getElementById('cashback-search-input');
            const statusSelect = document.getElementById('cashback-status-select');
            const platformSelect = document.getElementById('cashback-platform-select');
            const startDateInput = document.getElementById('cashback-start-date');
            const endDateInput = document.getElementById('cashback-end-date');

            const searchVal = searchInput ? searchInput.value.trim() : '';
            const statusVal = statusSelect ? statusSelect.value : '';
            const platformVal = platformSelect ? platformSelect.value : '';
            const startVal = startDateInput ? startDateInput.value : '';
            const endVal = endDateInput ? endDateInput.value : '';

            this.hasActiveFilters = searchVal !== '' || statusVal !== '' || platformVal !== '' || startVal !== '' || endVal !== '';
            this.activeChip = statusVal;

            const params = new URLSearchParams();
            if (searchVal) {
                params.set('search', searchVal);
            }
            if (statusVal) {
                params.set('status', statusVal);
            }
            if (platformVal) {
                params.set('platform', platformVal);
            }
            if (startVal) {
                params.set('start_date', startVal);
            }
            if (endVal) {
                params.set('end_date', endVal);
            }

            const actionUrl = window.location.pathname;
            const fullUrl = `${actionUrl}?${params.toString()}`;
            this.loadCashback(fullUrl);
        },

        /**
         * Đặt lại toàn bộ các ô lọc về giá trị trống và tải lại danh sách sạch
         */
        resetFilters() {
            const searchInput = document.getElementById('cashback-search-input');
            const statusSelect = document.getElementById('cashback-status-select');
            const platformSelect = document.getElementById('cashback-platform-select');
            const startDateInput = document.getElementById('cashback-start-date');
            const endDateInput = document.getElementById('cashback-end-date');

            if (searchInput) searchInput.value = '';
            if (statusSelect) statusSelect.value = '';
            if (platformSelect) platformSelect.value = '';
            if (startDateInput) startDateInput.value = '';
            if (endDateInput) endDateInput.value = '';

            this.hasActiveFilters = false;
            this.activeChip = '';

            const cleanUrl = window.location.pathname;
            this.loadCashback(cleanUrl);
        },

        /**
         * Cập nhật trạng thái hasActiveFilters dựa trên các ô nhập liệu hiện tại
         */
        syncActiveFiltersState() {
            const searchInput = document.getElementById('cashback-search-input');
            const statusSelect = document.getElementById('cashback-status-select');
            const platformSelect = document.getElementById('cashback-platform-select');
            const startDateInput = document.getElementById('cashback-start-date');
            const endDateInput = document.getElementById('cashback-end-date');

            const searchVal = searchInput ? searchInput.value.trim() : '';
            const statusVal = statusSelect ? statusSelect.value : '';
            const platformVal = platformSelect ? platformSelect.value : '';
            const startVal = startDateInput ? startDateInput.value : '';
            const endVal = endDateInput ? endDateInput.value : '';

            this.hasActiveFilters = searchVal !== '' || statusVal !== '' || platformVal !== '' || startVal !== '' || endVal !== '';
        },

        /**
         * Thực hiện gửi request AJAX để tải lịch sử hoàn tiền
         * @param {string} url - URL cần tải
         * @param {boolean} pushState - Có đẩy URL lên thanh địa chỉ trình duyệt không (mặc định: true)
         */
        loadCashback(url, pushState = true) {
            this.loading = true;
            const container = document.getElementById('cashback-list-container');
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
                console.error('Lỗi khi tải AJAX cashback history:', error);
                
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
            const searchInput = document.getElementById('cashback-search-input');
            const statusSelect = document.getElementById('cashback-status-select');
            const platformSelect = document.getElementById('cashback-platform-select');
            const startDateInput = document.getElementById('cashback-start-date');
            const endDateInput = document.getElementById('cashback-end-date');

            try {
                const urlObj = new URL(url, window.location.origin);
                const searchVal = urlObj.searchParams.get('search') || '';
                const statusVal = urlObj.searchParams.get('status') || '';
                const platformVal = urlObj.searchParams.get('platform') || '';
                const startVal = urlObj.searchParams.get('start_date') || '';
                const endVal = urlObj.searchParams.get('end_date') || '';

                if (searchInput) searchInput.value = searchVal;
                if (statusSelect) statusSelect.value = statusVal;
                if (platformSelect) platformSelect.value = platformVal;
                if (startDateInput) startDateInput.value = startVal;
                if (endDateInput) endDateInput.value = endVal;
                this.activeChip = statusVal;
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
                                    <tr class="border-b border-gray-100 dark:border-slate-850 text-xs font-bold text-gray-400 bg-gray-50/50 dark:bg-slate-800/30">
                                        <th class="p-4">Thông tin sản phẩm</th>
                                        <th class="p-4 text-right">Giá trị / Tiền hoàn</th>
                                        <th class="p-4 text-center">Trạng thái & Ngày tạo</th>
                                        <th class="p-4 text-center">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-slate-800/60 text-xs">
                                    ${Array(10).fill(0).map(() => `
                                        <tr class="transition-colors">
                                            <td class="p-4">
                                                <div class="flex items-center gap-3.5">
                                                    <div class="w-12 h-12 bg-gray-100 dark:bg-slate-800 rounded-xl shrink-0"></div>
                                                    <div class="min-w-0 flex-1 space-y-2">
                                                        <div class="h-3 bg-gray-200 dark:bg-slate-700 rounded w-24"></div>
                                                        <div class="h-3.5 bg-gray-100 dark:bg-slate-800 rounded w-3/4"></div>
                                                        <div class="h-3 bg-gray-100/50 dark:bg-slate-800/50 rounded w-20"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-4 text-right">
                                                <div class="space-y-2 inline-block text-right">
                                                    <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded w-24"></div>
                                                    <div class="h-3 bg-gray-100 dark:bg-slate-800 rounded w-16 float-right"></div>
                                                </div>
                                            </td>
                                            <td class="p-4 text-center">
                                                <div class="flex flex-col items-center gap-2">
                                                    <div class="h-5 bg-gray-200 dark:bg-slate-700 rounded-full w-20"></div>
                                                    <div class="h-3 bg-gray-100 dark:bg-slate-800 rounded w-16"></div>
                                                </div>
                                            </td>
                                            <td class="p-4 text-center">
                                                <div class="h-8 bg-gray-200 dark:bg-slate-700 rounded-xl w-16 mx-auto"></div>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile Skeleton -->
                    <div class="block md:hidden space-y-2.5">
                        ${Array(5).fill(0).map(() => `
                            <div class="relative bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800/80 shadow-sm overflow-hidden">
                                <div class="absolute left-0 top-0 bottom-0 w-[3px] bg-gray-200 dark:bg-slate-700"></div>
                                <div class="flex items-center justify-between pl-4 pr-3.5 pt-3 pb-0 gap-2">
                                    <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded-full w-20"></div>
                                    <div class="h-2.5 bg-gray-100 dark:bg-slate-800 rounded w-14"></div>
                                </div>
                                <div class="flex items-center gap-3 pl-4 pr-3.5 py-2.5">
                                    <div class="w-[52px] h-[52px] bg-gray-100 dark:bg-slate-800 rounded-xl shrink-0"></div>
                                    <div class="flex-1 space-y-2">
                                        <div class="h-3 bg-gray-200 dark:bg-slate-700 rounded w-full"></div>
                                        <div class="h-3 bg-gray-150 dark:bg-slate-800 rounded w-2/3"></div>
                                    </div>
                                    <div class="text-right space-y-1.5 shrink-0">
                                        <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded w-16"></div>
                                        <div class="h-2.5 bg-gray-100 dark:bg-slate-800 rounded w-10"></div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pl-4 pr-3.5 pb-3">
                                    <div class="h-3 bg-gray-200 dark:bg-slate-700 rounded-full w-16"></div>
                                    <div class="h-[15px] w-[15px] bg-gray-100 dark:bg-slate-800 rounded"></div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        },

        // Xác định tên API hiển thị động theo platform khi đồng bộ tự động
        getPlatformApiName(platform) {
            if (!platform || platform === 'shopee') return 'Shopee API';
            if (platform === 'tiktok') return 'TikTok API';
            if (platform === 'lazada') return 'Lazada API';
            return platform.toUpperCase() + ' API';
        },

        formatCurrency(value) {
            if (!value) return '0đ';
            if (typeof value === 'string' && (value.includes('đ') || value.includes('₫') || value.includes('$'))) {
                return value;
            }
            const num = parseFloat(value.toString().replace(/[^0-9.-]+/g,""));
            if (isNaN(num)) return value;
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(num);
        },



        // Dựng danh sách các mốc thời gian thay đổi trạng thái của đơn hàng để hiển thị timeline
        buildTimeline() {
            const cb = this.selectedItem;
            if (!cb || !cb.created_at) return [];

            const events = [];
            const trans = config.translations || {};

            // Trường hợp link vừa tạo nhưng sàn chưa ghi nhận đơn: chỉ có mốc tạo link và mốc đang chờ đối soát
            if (cb.status === 'unrecorded') {
                events.push({
                    event: 'created',
                    at: cb.created_at,
                    title: trans.link_created_title || 'Bạn đã tạo link mua hàng',
                    icon: 'link',
                    iconColor: 'text-blue-500',
                    titleColor: 'text-gray-800 dark:text-slate-200',
                    ring: 'border-blue-200 dark:border-blue-900/60',
                    source: '',
                });

                events.push({
                    event: 'waiting',
                    at: '',
                    title: trans.waiting_platform_title || 'Đang chờ sàn ghi nhận đơn',
                    icon: 'clock',
                    iconColor: 'text-gray-400',
                    titleColor: 'text-gray-500 dark:text-slate-400',
                    ring: 'border-gray-200 dark:border-slate-700',
                    source: trans.waiting_platform_note || '',
                });

                return events;
            }

            // Mốc đầu tiên: đơn hàng được ghi nhận vào hệ thống
            events.push({
                event: 'created',
                at: cb.created_at,
                title: trans.order_recorded || 'Đơn hàng được ghi nhận',
                icon: 'plus-circle',
                iconColor: 'text-blue-500',
                titleColor: 'text-gray-800 dark:text-slate-200',
                ring: 'border-blue-200 dark:border-blue-900/60',
                source: '',
            });

            // Bản đồ cấu hình hiển thị cho từng loại sự kiện đổi trạng thái
            const map = {
                approved: {
                    title: trans.approved_title || 'Đơn được duyệt hoàn tiền',
                    icon: 'check-circle', iconColor: 'text-green-500',
                    titleColor: 'text-green-600 dark:text-green-400',
                    ring: 'border-green-200 dark:border-green-900/60',
                },
                rejected: {
                    title: trans.rejected_title || 'Đơn bị từ chối',
                    icon: 'x-circle', iconColor: 'text-red-500',
                    titleColor: 'text-red-600 dark:text-red-400',
                    ring: 'border-red-200 dark:border-red-900/60',
                },
                clawback: {
                    title: trans.clawback_title || 'Thu hồi tiền hoàn (đơn bị huỷ)',
                    icon: 'rotate-ccw', iconColor: 'text-orange-500',
                    titleColor: 'text-orange-600 dark:text-orange-400',
                    ring: 'border-orange-200 dark:border-orange-900/60',
                },
            };

            const timeline = Array.isArray(cb.status_timeline) ? cb.status_timeline : [];

            if (timeline.length > 0) {
                // Dữ liệu timeline đầy đủ: hiển thị từng sự kiện đã ghi nhận
                timeline.forEach(t => {
                    const cfg = map[t.event];
                    if (!cfg) return;
                    events.push({
                        ...cfg,
                        at: this.formatDate(t.at),
                        source: '',
                        reason: t.reason || '',
                        amount: t.amount ? ((trans.amount_label || 'Số tiền:') + ' ' + this.formatCurrency(t.amount)) : '',
                    });
                });
            } else {
                // Dự phòng cho đơn cũ chưa lưu status_timeline: suy ra từ trạng thái hiện tại
                if (cb.status === 'approved' && cb.approved_at) {
                    events.push({ ...map.approved, at: cb.approved_at, source: '', reason: '', amount: '' });
                } else if (cb.status === 'rejected') {
                    events.push({
                        ...map.rejected,
                        at: cb.approved_at || cb.updated_at || cb.created_at,
                        source: '',
                        reason: cb.rejected_reason || '',
                        amount: '',
                    });
                }
            }

            return events;
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            if (typeof dateString === 'string' && dateString.includes('/') && dateString.includes(':')) {
                return dateString;
            }
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('vi-VN', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (e) {
                return dateString;
            }
        }
    };
}
