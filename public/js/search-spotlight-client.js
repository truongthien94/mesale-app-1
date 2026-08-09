/**
 * Khởi tạo logic quản lý Spotlight Search Client cho giao diện thành viên và trang khách
 * @param {Array} clientItems Danh sách các liên kết chức năng được truyền từ PHP
 */
function searchSpotlightClient(clientItems) {
    return {
        isOpen: false, // Trạng thái mở/đóng modal tìm kiếm nhanh
        searchQuery: '', // Từ khóa tìm kiếm do người dùng nhập vào
        selectedIndex: 0, // Chỉ mục (index) của mục đang được chọn/hover để điều hướng bằng phím
        items: clientItems || [], // Lưu trữ danh sách các mục để thực hiện lọc tìm kiếm nhanh

        // Lấy 10 mục đầu tiên làm gợi ý truy cập nhanh khi người dùng chưa nhập từ khóa
        get popularItems() {
            return this.items.slice(0, 10);
        },

        // Bộ lọc tìm kiếm nhanh theo từ khóa không dấu để tối ưu trải nghiệm người dùng
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

        // Theo dõi thay đổi từ khóa để đưa chỉ mục lựa chọn về 0 và vẽ lại icon Lucide
        init() {
            this.$watch('searchQuery', value => {
                this.selectedIndex = 0;
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            });
        },

        // Mở hộp tìm kiếm nhanh, reset các giá trị và tự động focus vào ô nhập liệu
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

        // Đóng hộp tìm kiếm nhanh
        closeModal() {
            this.isOpen = false;
        },

        // Bật/Tắt hộp tìm kiếm nhanh
        toggleModal() {
            if (this.isOpen) {
                this.closeModal();
            } else {
                this.openModal();
            }
        },

        // Di chuyển lựa chọn xuống dưới bằng phím mũi tên Down
        navigateDown() {
            const max = this.searchQuery === '' ? this.popularItems.length : this.filteredItems.length;
            if (max === 0) return;
            this.selectedIndex = (this.selectedIndex + 1) % max;
            this.scrollIntoView();
        },

        // Di chuyển lựa chọn lên trên bằng phím mũi tên Up
        navigateUp() {
            const max = this.searchQuery === '' ? this.popularItems.length : this.filteredItems.length;
            if (max === 0) return;
            this.selectedIndex = (this.selectedIndex - 1 + max) % max;
            this.scrollIntoView();
        },

        // Truy cập liên kết đang được chọn khi nhấn phím Enter
        selectCurrent() {
            const activeList = this.searchQuery === '' ? this.popularItems : this.filteredItems;
            if (activeList[this.selectedIndex]) {
                const targetUrl = activeList[this.selectedIndex].url;
                this.closeModal();
                window.location.href = targetUrl;
            }
        },

        // Tự động cuộn khung hiển thị kết quả tương ứng với mục đang chọn bằng bàn phím
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

        // Hàm chuẩn hóa tiếng Việt không dấu để tối ưu hóa quá trình tìm kiếm nhanh
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
            str = str.replace(/[^A-Za-z0-9 ]/g, '');
            return str.toLowerCase().trim();
        }
    };
}

// Đăng ký sự kiện phím tắt toàn cục Ctrl + K hoặc Cmd + K cho người dùng truy cập nhanh Spotlight Search
window.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        const activeEl = document.activeElement;
        // Tránh kích hoạt Spotlight Search khi người dùng đang nhập văn bản trong ô input, textarea khác
        if (activeEl && (
            activeEl.tagName === 'INPUT' || 
            activeEl.tagName === 'TEXTAREA' || 
            activeEl.isContentEditable
        )) {
            return;
        }
        e.preventDefault();
        window.dispatchEvent(new CustomEvent('open-search'));
    }
});
