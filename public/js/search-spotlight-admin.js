/**
 * Khởi tạo logic quản lý Spotlight Search cho giao diện Admin Panel
 * @param {Array} adminItems Danh sách các liên kết và chức năng được truyền từ PHP
 */
function searchSpotlight(adminItems) {
    return {
        isOpen: false,
        searchQuery: '',
        selectedIndex: 0,
        items: adminItems || [],

        // Lấy danh sách 10 chức năng đầu tiên làm gợi ý nhanh khi chưa gõ từ khóa
        get popularItems() {
            return this.items.slice(0, 10);
        },

        // Lọc danh sách menu và cài đặt dựa trên từ khóa tìm kiếm tiếng Việt không dấu
        get filteredItems() {
            if (this.searchQuery === '') return [];
            const queryClean = this.removeVietnameseTones(this.searchQuery);
            return this.items.filter(item => {
                const nameClean = this.removeVietnameseTones(item.name);
                const keywordsClean = this.removeVietnameseTones(item.keywords || '');
                const categoryClean = this.removeVietnameseTones(item.category);
                return nameClean.includes(queryClean) || keywordsClean.includes(queryClean) || categoryClean.includes(queryClean);
            });
        },

        // Khởi động lắng nghe sự kiện thay đổi từ khóa để reset con trỏ chọn
        init() {
            this.$watch('searchQuery', value => {
                this.selectedIndex = 0;
                // Sau khi render kết quả, re-init Lucide Icons để hiển thị đúng icon mới
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            });
        },

        // Mở khung Spotlight và tự động focus vào ô input
        openModal() {
            this.isOpen = true;
            this.searchQuery = '';
            this.selectedIndex = 0;
            setTimeout(() => {
                this.$refs.searchInput.focus();
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }, 100);
        },

        // Đóng khung Spotlight
        closeModal() {
            this.isOpen = false;
        },

        // Bật/tắt trạng thái khung Spotlight
        toggleModal() {
            if (this.isOpen) {
                this.closeModal();
            } else {
                this.openModal();
            }
        },

        // Di chuyển con trỏ xuống trong danh sách kết quả
        navigateDown() {
            const max = this.searchQuery === '' ? this.popularItems.length : this.filteredItems.length;
            if (max === 0) return;
            this.selectedIndex = (this.selectedIndex + 1) % max;
            this.scrollIntoView();
        },

        // Di chuyển con trỏ lên trong danh sách kết quả
        navigateUp() {
            const max = this.searchQuery === '' ? this.popularItems.length : this.filteredItems.length;
            if (max === 0) return;
            this.selectedIndex = (this.selectedIndex - 1 + max) % max;
            this.scrollIntoView();
        },

        // Chọn và truy cập liên kết của phần tử hiện tại đang được con trỏ nhắm tới
        selectCurrent() {
            const activeList = this.searchQuery === '' ? this.popularItems : this.filteredItems;
            if (activeList[this.selectedIndex]) {
                const targetUrl = activeList[this.selectedIndex].url;
                this.closeModal();
                
                // Thực hiện điều hướng trực tiếp đến URL chức năng hoặc tab cấu hình được chọn
                window.location.href = targetUrl;
            }
        },

        // Tự động cuộn phần tử được chọn vào vùng nhìn thấy của container
        scrollIntoView() {
            this.$nextTick(() => {
                const container = this.$refs.resultsContainer;
                const selectedEl = document.getElementById('search-item-' + this.selectedIndex);
                if (selectedEl && container) {
                    const containerTop = container.scrollTop;
                    const containerBottom = containerTop + container.clientHeight;
                    const elemTop = selectedEl.offsetTop;
                    const elemBottom = elemTop + selectedEl.clientHeight;

                    if (elemTop < containerTop) {
                        container.scrollTop = elemTop;
                    } else if (elemBottom > containerBottom) {
                        container.scrollTop = elemBottom - container.clientHeight;
                    }
                }
            });
        },

        // Hàm chuẩn hóa loại bỏ toàn bộ dấu tiếng Việt để so sánh chuỗi chính xác
        removeVietnameseTones(str) {
            if (!str) return '';
            str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
            str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
            str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
            str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
            str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
            str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
            str = str.replace(/đ/g, "d");
            str = str.replace(/À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Đ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ/g, "A");
            str = str.replace(/È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ/g, "E");
            str = str.replace(/Ì|Í|Ị|Ỉ|Ĩ/g, "I");
            str = str.replace(/Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ/g, "O");
            str = str.replace(/Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ/g, "U");
            str = str.replace(/Ỳ|Ý|Ỵ|Ỷ|Ỹ/g, "Y");
            // Loại bỏ các ký tự đặc biệt, chỉ giữ lại chữ cái, số và khoảng trắng
            str = str.replace(/[^A-Za-z0-9 ]/g, '');
            return str.toLowerCase().trim();
        }
    };
}

// Đăng ký bộ lắng nghe sự kiện phím tắt toàn cục (Ctrl + K / Cmd + K)
// Ngăn chặn mở trên các input, textarea hoặc văn bản đang soạn thảo để tránh Race Condition phím tắt
window.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        const activeEl = document.activeElement;
        if (activeEl && (
            activeEl.tagName === 'INPUT' || 
            activeEl.tagName === 'TEXTAREA' || 
            activeEl.isContentEditable || 
            activeEl.closest('.cke') // Loại trừ khi đang soạn thảo CKEditor
        )) {
            return; // Để cho phím tắt mặc định của editor hoạt động
        }
        e.preventDefault();
        window.dispatchEvent(new CustomEvent('open-search'));
    }
});
