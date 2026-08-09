/**
 * Trình điều khiển logic điểm danh hằng ngày sử dụng AlpineJS.
 * Nhận cấu hình động từ Blade view để xử lý request qua Axios.
 * 
 * @param {Object} config - Cấu hình truyền từ backend Laravel.
 * @param {boolean} config.hasCheckedIn - Trạng thái đã điểm danh hôm nay chưa.
 * @param {number} config.streak - Chuỗi số ngày điểm danh liên tiếp hiện tại.
 * @param {string} config.checkinRoute - URL API gửi yêu cầu điểm danh (POST).
 */
function checkinHandler(config) {
    return {
        loading: false,
        hasCheckedIn: config.hasCheckedIn,
        streak: config.streak,
        deviceAllowed: config.deviceAllowed !== false,
        orderAllowed: config.orderAllowed !== false,
        emailAllowed: config.emailAllowed !== false,
        accountAgeAllowed: config.accountAgeAllowed !== false,

        // --- Các trường dữ liệu điều khiển modal mới ---
        // Trạng thái hiển thị modal thành công
        showSuccessModal: false,
        // Số xu/tiền thưởng hiển thị trên modal
        modalEarnedCoins: 0,
        // Số ngày chu kỳ hiện tại hiển thị trên modal
        modalStreakDays: 0,

        /**
         * Thực hiện gửi yêu cầu điểm danh lên máy chủ
         */
        performCheckin() {
            // Nếu đã điểm danh rồi thì không làm gì thêm để tránh trùng lặp
            if (this.hasCheckedIn) return;

            // Nếu thiết bị không hợp lệ thì báo lỗi và dừng lại
            if (!this.deviceAllowed) {
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { text: 'Thiết bị của bạn không được phép thực hiện điểm danh!', type: 'error' } 
                }));
                return;
            }

            // Nếu chưa đủ số đơn hàng trong tháng thì báo lỗi và dừng lại
            if (!this.orderAllowed) {
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { text: 'Bạn chưa phát sinh đủ số lượng đơn hàng hoàn tiền tối thiểu trong tháng này để được điểm danh!', type: 'error' } 
                }));
                return;
            }

            // Nếu chưa xác minh email thì báo lỗi và dừng lại
            if (!this.emailAllowed) {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { text: 'Tài khoản của bạn chưa xác minh email. Vui lòng xác minh email để điểm danh!', type: 'error' }
                }));
                return;
            }

            // Nếu tài khoản chưa đủ số ngày đăng ký tối thiểu thì báo lỗi và dừng lại
            if (!this.accountAgeAllowed) {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { text: 'Tài khoản của bạn chưa đủ số ngày đăng ký tối thiểu để được điểm danh!', type: 'error' }
                }));
                return;
            }

            // Kiểm tra cấu hình và tự động mở liên kết Shopee ngay khi click (để tránh bị bộ chặn popup của trình duyệt chặn khi chạy trong callback async)
            if (config.redirectEnabled && config.redirectUrl) {
                // Sử dụng regex kiểm tra User Agent để phát hiện thiết bị di động
                const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                const device = config.redirectDevice || 'both';
                let shouldRedirect = false;

                if (device === 'both') {
                    shouldRedirect = true;
                } else if (device === 'mobile' && isMobile) {
                    shouldRedirect = true;
                } else if (device === 'desktop' && !isMobile) {
                    shouldRedirect = true;
                }

                if (shouldRedirect) {
                    // Mở liên kết Shopee cấu hình ở tab mới (hoặc mở thẳng app Shopee trên mobile)
                    window.open(config.redirectUrl, '_blank');
                }
            }

            // Bắt đầu trạng thái tải dữ liệu
            this.loading = true;

            axios.post(config.checkinRoute)
                .then(response => {
                    if (response.data.status === 'success') {
                        // Cập nhật trạng thái giao diện ngay lập tức
                        this.hasCheckedIn = true;
                        this.streak = response.data.data.streak_days;

                        // Cập nhật thông tin và kích hoạt hiển thị modal thành công
                        this.modalEarnedCoins = response.data.data.coins_earned;
                        this.modalStreakDays = response.data.data.streak_days;
                        this.showSuccessModal = true;
                        
                        // Kích hoạt hệ thống Toast hiển thị thông báo thành công
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { 
                                text: `Điểm danh thành công! Nhận +${new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(response.data.data.coins_earned)}đ`, 
                                type: 'success' 
                            } 
                        }));
                    }
                })
                .catch(error => {
                    // Lấy thông báo lỗi trả về từ server hoặc dùng thông báo mặc định
                    const msg = error.response && error.response.data.message 
                        ? error.response.data.message 
                        : 'Không thể điểm danh. Vui lòng thử lại sau.';
                    
                    // Kích hoạt hệ thống Toast thông báo lỗi
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { text: msg, type: 'error' } 
                    }));
                })
                .finally(() => {
                    // Kết thúc trạng thái tải dữ liệu
                    this.loading = false;
                });
        },

        /**
         * Đóng modal thành công và tải lại trang để làm mới số dư ví cũng như bảng xếp hạng
         */
        closeSuccessModal() {
            this.showSuccessModal = false;
            window.location.reload();
        }
    }
}
