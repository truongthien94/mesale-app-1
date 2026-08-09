/**
 * Trình điều khiển logic hộp thư thông báo sử dụng AlpineJS.
 * Hỗ trợ chuyển tab Ajax, tự động render Lucide icon sau khi tải dữ liệu mới.
 * 
 * @param {Object} config - Cấu hình truyền từ backend.
 * @param {string} config.initialTab - Tab khởi tạo hiện tại ('general' hoặc 'personal').
 * @param {string} config.initialFilter - Bộ lọc khởi tạo ('all' hoặc 'unread').
 * @param {string} config.baseUrl - URL gốc của trang thông báo (không kèm query string).
 * @param {string} config.readAllRoute - URL API đánh dấu tất cả đã đọc (POST).
 * @param {string} config.loadErrorMsg - Thông báo lỗi khi không tải được danh sách.
 * @param {string} config.generalErrorMsg - Thông báo lỗi chung khi có sự cố hệ thống.
 */
function notificationHandler(config) {
    return {
        currentTab: config.initialTab,
        currentFilter: config.initialFilter || 'all',

        // Trạng thái modal chi tiết thông báo
        openModal: false,
        selectedItem: null,

        /**
         * Mở modal hiển thị nội dung đầy đủ của một thông báo.
         * Tự động đánh dấu đã đọc nếu thông báo còn ở trạng thái chưa đọc.
         * @param {Object} item - Dữ liệu thông báo (id, title, content, created_at, created_human, is_read)
         */
        openDetail(item) {
            this.selectedItem = item;
            this.openModal = true;

            // Đánh dấu đã đọc ngầm (không tải lại danh sách để tránh đóng modal)
            if (item && !item.is_read) {
                this.markRead(item.id);
            }
        },

        /**
         * Đánh dấu một thông báo đã đọc mà KHÔNG tải lại danh sách.
         * Dùng khi mở modal: chỉ cập nhật giao diện thẻ tương ứng tại chỗ.
         * @param {number} id - ID của thông báo
         */
        markRead(id) {
            axios.post('/dashboard/notifications/' + id + '/read')
            .then(response => {
                if (response.data.status === 'success') {
                    // Cập nhật trạng thái trong dữ liệu đang mở
                    if (this.selectedItem && this.selectedItem.id === id) {
                        this.selectedItem.is_read = true;
                    }
                    // Gỡ huy hiệu "Mới" trên thẻ tương ứng và giảm bộ đếm tab
                    const card = document.querySelector('[data-noti-id="' + id + '"]');
                    if (card) {
                        card.querySelectorAll('.unread-badge').forEach(el => el.remove());
                        card.setAttribute('data-noti-read', '1');
                    }
                }
            })
            .catch(() => {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { text: config.generalErrorMsg, type: 'error' }
                }));
            });
        },

        /**
         * Tạo URL từ tab và bộ lọc hiện tại.
         * @returns {string} URL kèm query string tab & filter
         */
        buildUrl() {
            const params = new URLSearchParams();
            params.set('tab', this.currentTab);
            if (this.currentFilter && this.currentFilter !== 'all') {
                params.set('filter', this.currentFilter);
            }
            return config.baseUrl + '?' + params.toString();
        },

        /**
         * Khởi tạo các sự kiện lắng nghe click phân trang và popstate
         */
        init() {
            // Sử dụng Event Delegation để bắt sự kiện click phân trang bên trong container Ajax
            const container = document.getElementById('notification-list-container');
            if (container) {
                container.addEventListener('click', (e) => {
                    const link = e.target.closest('.ajax-pagination a, .pagination a');
                    if (link) {
                        e.preventDefault();
                        e.stopPropagation(); // Ngăn sự kiện click phân trang nổi bọt lên document
                        this.loadNotifications(link.href);
                    }
                });
            }

            // Lắng nghe sự kiện popstate khi nhấn Back/Forward trên trình duyệt để khôi phục trạng thái
            window.addEventListener('popstate', () => {
                const urlParams = new URLSearchParams(window.location.search);
                this.currentTab = urlParams.get('tab') || 'general';
                this.currentFilter = urlParams.get('filter') || 'all';
                this.loadNotifications(window.location.href);
            });
        },

        /**
         * Chuyển đổi tab hiển thị (giữ nguyên bộ lọc đang chọn)
         * @param {string} tab - Tên tab chuyển đổi
         */
        switchTab(tab) {
            if (this.currentTab === tab) return;
            this.currentTab = tab;
            this.loadNotifications(this.buildUrl());
        },

        /**
         * Đổi bộ lọc trạng thái đọc (giữ nguyên tab đang chọn)
         * @param {string} filter - 'all' hoặc 'unread'
         */
        setFilter(filter) {
            if (this.currentFilter === filter) return;
            this.currentFilter = filter;
            this.loadNotifications(this.buildUrl());
        },

        /**
         * Gửi request tải danh sách thông báo qua Ajax
         * @param {string} url - URL API / Trang tải thông báo
         */
        loadNotifications(url) {
            const container = document.getElementById('notification-list-container');
            if (container) {
                container.classList.add('pointer-events-none', 'opacity-50');
            }

            axios.get(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => {
                if (container) {
                    container.innerHTML = response.data;
                    container.classList.remove('pointer-events-none', 'opacity-50');
                }
                // Vẽ lại Lucide icons cho nội dung mới tải về
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
                // Đẩy URL mới lên thanh địa chỉ mà không tải lại trang
                window.history.pushState(null, '', url);
            })
            .catch(error => {
                if (container) {
                    container.classList.remove('pointer-events-none', 'opacity-50');
                }
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { text: config.loadErrorMsg, type: 'error' } 
                }));
            });
        },

        /**
         * Đánh dấu tất cả thông báo đã đọc
         */
        markAllAsRead() {
            axios.post(config.readAllRoute)
            .then(response => {
                if (response.data.status === 'success') {
                    // Hiển thị Toast thông báo thành công
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { text: response.data.message, type: 'success' } 
                    }));

                    // Xóa các badge chưa đọc trên giao diện
                    document.querySelectorAll('.unread-badge').forEach(el => el.remove());
                    
                    // Tải lại dữ liệu trang hiện tại qua Ajax
                    this.loadNotifications(window.location.href);
                }
            })
            .catch(error => {
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { text: config.generalErrorMsg, type: 'error' } 
                }));
            });
        },

        /**
         * Đánh dấu một thông báo cụ thể là đã đọc
         * @param {number} id - ID của thông báo
         */
        markSingleAsRead(id) {
            axios.post('/dashboard/notifications/' + id + '/read')
            .then(response => {
                if (response.data.status === 'success') {
                    // Hiển thị Toast thông báo thành công
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { text: response.data.message, type: 'success' } 
                    }));
                    
                    // Tải lại dữ liệu trang hiện tại qua Ajax
                    this.loadNotifications(window.location.href);
                }
            })
            .catch(error => {
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { text: config.generalErrorMsg, type: 'error' } 
                }));
            });
        }
    }
}
