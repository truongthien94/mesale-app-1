/**
 * Trình điều khiển logic mạng lưới giới thiệu (Affiliate MLM) bằng AlpineJS.
 * Nhận chuỗi thông báo đa ngôn ngữ được định nghĩa sẵn từ Blade view.
 * 
 * @param {Object} config - Cấu hình truyền từ backend.
 * @param {string} config.copySuccessMsg - Thông báo hiển thị khi sao chép thành công.
 * @param {string} config.loadErrorMsg - Thông báo hiển thị khi tải dữ liệu thất bại.
 */
function referralHandler(config) {
    return {
        copied: false,
        loading: false,
        // Trạng thái bộ lọc lịch sử hoa hồng (rỗng = tất cả); khởi tạo theo query hiện tại để đồng bộ khi tải lại trang
        filterLevel: config.initLevel || '',
        filterStatus: config.initStatus || '',

        /**
         * Khởi tạo các sự kiện lắng nghe click phân trang và popstate
         */
        init() {
            // Expose thực thể hiện tại ra window để các phần khác có thể tương tác
            window._referralHandler = this;

            // Sử dụng Event Delegation để bắt các click phân trang
            const container = document.getElementById('referral-commission-list-container');
            if (container) {
                container.addEventListener('click', (e) => {
                    const link = e.target.closest('.ajax-pagination a, .pagination a');
                    if (link) {
                        e.preventDefault();
                        e.stopPropagation();
                        this.loadCommissions(link.href);
                    }
                });
            }

            // Lắng nghe sự kiện popstate để xử lý khi người dùng nhấn nút Back/Forward của trình duyệt
            window.addEventListener('popstate', () => {
                this.loadCommissions(window.location.href, false);
            });
        },

        /**
         * Thực hiện sao chép đường dẫn giới thiệu vào bộ nhớ tạm.
         * Có phương án dự phòng (fallback) cho trình duyệt cũ / môi trường không bảo mật,
         * và phát Toast lỗi nếu thất bại thay vì im lặng không phản hồi.
         */
        copyLink() {
            const linkInput = this.$refs.refLink;
            const text = linkInput.value;
            linkInput.select();

            // Trình duyệt cũ hoặc môi trường không bảo mật (không có Clipboard API)
            if (!navigator.clipboard) {
                try {
                    document.execCommand('copy');
                    this.triggerCopySuccess();
                } catch (err) {
                    console.error('Lỗi khi sao chép liên kết:', err);
                    this.notifyCopyError();
                }
                return;
            }

            // Clipboard API hiện đại
            navigator.clipboard.writeText(text)
                .then(() => this.triggerCopySuccess())
                .catch(err => {
                    console.error('Lỗi khi sao chép liên kết:', err);
                    this.notifyCopyError();
                });
        },

        /**
         * Cập nhật giao diện và phát Toast khi sao chép thành công
         */
        triggerCopySuccess() {
            this.copied = true;
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { text: config.copySuccessMsg, type: 'success' }
            }));
            // Reset trạng thái nút sao chép sau 2 giây
            setTimeout(() => {
                this.copied = false;
            }, 2000);
        },

        /**
         * Phát Toast báo lỗi khi không sao chép được link
         */
        notifyCopyError() {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { text: config.copyErrorMsg || config.loadErrorMsg || 'Không thể sao chép liên kết.', type: 'error' }
            }));
        },

        /**
         * Tải ảnh QR Code của link giới thiệu về máy (fetch Blob client-side để tránh CORS/nghẽn mạng DDEV)
         * @param {string} text - Nội dung mã hoá vào QR (đường dẫn giới thiệu)
         */
        downloadQrCode(text) {
            const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=' + encodeURIComponent(text);
            fetch(qrUrl)
                .then(response => {
                    if (!response.ok) throw new Error('Không thể tải tệp hình ảnh từ QRServer');
                    return response.blob();
                })
                .then(blob => {
                    const blobUrl = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = blobUrl;
                    link.download = 'qr-gioi-thieu.png';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(blobUrl);
                })
                .catch(error => {
                    console.error('Lỗi khi tải mã QR ở client-side:', error);
                    // Fallback: mở ảnh trực tiếp ở tab mới nếu lỗi mạng/CORS
                    window.open(qrUrl, '_blank');
                });
        },

        /**
         * Áp dụng bộ lọc cấp (F1/F2) và trạng thái vào lịch sử hoa hồng,
         * dựng lại URL và tải dữ liệu qua AJAX (giữ trải nghiệm không tải lại trang)
         */
        applyFilters() {
            const params = new URLSearchParams();
            if (this.filterLevel) params.set('level', this.filterLevel);
            if (this.filterStatus) params.set('status', this.filterStatus);
            const query = params.toString();
            const url = (config.baseUrl || window.location.pathname) + (query ? '?' + query : '');
            this.loadCommissions(url);
        },

        /**
         * Gửi yêu cầu AJAX để tải danh sách hoa hồng
         * @param {string} url - Đường dẫn cần tải dữ liệu
         * @param {boolean} pushState - Có đẩy URL mới lên thanh địa chỉ không (mặc định: true)
         */
        loadCommissions(url, pushState = true) {
            this.loading = true;
            const container = document.getElementById('referral-commission-list-container');
            const previousHTML = container ? container.innerHTML : '';

            if (container) {
                container.classList.add('pointer-events-none');
                container.innerHTML = this.getSkeletonHTML();
            }

            axios.get(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => {
                this.loading = false;
                if (container) {
                    container.innerHTML = response.data;
                    container.classList.remove('pointer-events-none');
                }

                // Vẽ lại Lucide icons cho dữ liệu mới
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }

                // Cập nhật thanh địa chỉ trình duyệt
                if (pushState) {
                    window.history.pushState(null, '', url);
                }
            })
            .catch(error => {
                this.loading = false;
                console.error('Lỗi tải hoa hồng tiếp thị:', error);
                
                if (container) {
                    container.innerHTML = previousHTML;
                    container.classList.remove('pointer-events-none');
                }

                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { text: config.loadErrorMsg || 'Có lỗi xảy ra khi tải dữ liệu.', type: 'error' } 
                }));
            });
        },

        /**
         * Sinh chuỗi HTML Skeleton loader để hiển thị trong lúc chờ phản hồi AJAX
         * @returns {string} HTML Skeleton
         */
        getSkeletonHTML() {
            return `
                <div class="space-y-4 animate-pulse">
                    <div class="hidden md:block">
                        <div class="h-8 bg-gray-100 dark:bg-slate-800 rounded-xl mb-4"></div>
                        <div class="h-12 bg-gray-50 dark:bg-slate-800/50 rounded-xl mb-2"></div>
                        <div class="h-12 bg-gray-50 dark:bg-slate-800/50 rounded-xl mb-2"></div>
                        <div class="h-12 bg-gray-50 dark:bg-slate-800/50 rounded-xl"></div>
                    </div>
                    <div class="block md:hidden space-y-3">
                        <div class="h-28 bg-gray-50 dark:bg-slate-800/50 rounded-2xl"></div>
                        <div class="h-28 bg-gray-50 dark:bg-slate-800/50 rounded-2xl"></div>
                    </div>
                </div>
            `;
        }
    }
}
