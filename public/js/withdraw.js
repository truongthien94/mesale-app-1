/**
 * Trình điều khiển logic danh sách lịch sử rút tiền bằng AJAX.
 */
document.addEventListener('DOMContentLoaded', function () {
    const desktopContainer = document.getElementById('withdrawal-desktop-table-container');
    const mobileContainer = document.getElementById('withdrawal-mobile-cards-container');

    // Bắt sự kiện phân trang bằng Event Delegation trên các container
    if (desktopContainer) {
        desktopContainer.addEventListener('click', handlePaginationClick);
    }
    if (mobileContainer) {
        mobileContainer.addEventListener('click', handlePaginationClick);
    }

    function handlePaginationClick(e) {
        const link = e.target.closest('.ajax-pagination a, .pagination a');
        if (link) {
            e.preventDefault();
            e.stopPropagation();
            loadWithdrawals(link.href);
        }
    }

    // Bắt sự kiện cho form tìm kiếm Desktop (Tĩnh)
    const filterForm = document.getElementById('withdraw-filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            submitFilter(false);
        });

        const statusSelect = document.getElementById('withdraw-status-select');
        if (statusSelect) {
            statusSelect.addEventListener('change', () => submitFilter(false));
        }

        const resetBtn = document.getElementById('withdraw-reset-filter-btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                const searchInput = document.getElementById('withdraw-search-input');
                if (searchInput) searchInput.value = '';
                if (statusSelect) statusSelect.value = 'all';
                submitFilter(false);
            });
        }
    }

    // Bắt sự kiện cho form tìm kiếm Mobile (Tĩnh)
    const filterFormMobile = document.getElementById('withdraw-filter-form-mobile');
    if (filterFormMobile) {
        filterFormMobile.addEventListener('submit', (e) => {
            e.preventDefault();
            submitFilter(true);
        });

        const statusSelectMobile = document.getElementById('withdraw-status-select-mobile');
        if (statusSelectMobile) {
            statusSelectMobile.addEventListener('change', () => submitFilter(true));
        }

        const resetBtnMobile = document.getElementById('withdraw-reset-filter-btn-mobile');
        if (resetBtnMobile) {
            resetBtnMobile.addEventListener('click', () => {
                const searchInputMobile = document.getElementById('withdraw-search-input-mobile');
                if (searchInputMobile) searchInputMobile.value = '';
                if (statusSelectMobile) statusSelectMobile.value = 'all';
                submitFilter(true);
            });
        }
    }

    // Lắng nghe sự kiện popstate để xử lý khi nhấn nút Back/Forward của trình duyệt
    window.addEventListener('popstate', () => {
        syncFiltersFromURL();
        loadWithdrawals(window.location.href, false);
    });

    /**
     * Thu thập giá trị lọc và gửi request
     * @param {boolean} isMobile
     */
    function submitFilter(isMobile = false) {
        let searchVal = '';
        let statusVal = 'all';

        if (isMobile) {
            const searchInputMobile = document.getElementById('withdraw-search-input-mobile');
            const statusSelectMobile = document.getElementById('withdraw-status-select-mobile');
            searchVal = searchInputMobile ? searchInputMobile.value.trim() : '';
            statusVal = statusSelectMobile ? statusSelectMobile.value : 'all';
        } else {
            const searchInput = document.getElementById('withdraw-search-input');
            const statusSelect = document.getElementById('withdraw-status-select');
            searchVal = searchInput ? searchInput.value.trim() : '';
            statusVal = statusSelect ? statusSelect.value : 'all';
        }

        const params = new URLSearchParams();
        if (searchVal) {
            params.set('search', searchVal);
        }
        if (statusVal && statusVal !== 'all') {
            params.set('status', statusVal);
        }

        const actionUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        loadWithdrawals(actionUrl);
    }

    /**
     * Đồng bộ giá trị form từ URL
     */
    function syncFiltersFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchVal = urlParams.get('search') || '';
        const statusVal = urlParams.get('status') || 'all';
        const hasActiveFilters = searchVal !== '' || statusVal !== 'all';

        // Đồng bộ bản Desktop
        const searchInput = document.getElementById('withdraw-search-input');
        const statusSelect = document.getElementById('withdraw-status-select');
        const resetBtn = document.getElementById('withdraw-reset-filter-btn');

        if (searchInput) searchInput.value = searchVal;
        if (statusSelect) statusSelect.value = statusVal;
        if (resetBtn) {
            resetBtn.style.display = hasActiveFilters ? 'inline-flex' : 'none';
        }

        // Đồng bộ bản Mobile
        const searchInputMobile = document.getElementById('withdraw-search-input-mobile');
        const statusSelectMobile = document.getElementById('withdraw-status-select-mobile');
        const resetBtnMobile = document.getElementById('withdraw-reset-filter-btn-mobile');

        if (searchInputMobile) searchInputMobile.value = searchVal;
        if (statusSelectMobile) statusSelectMobile.value = statusVal;
        if (resetBtnMobile) {
            resetBtnMobile.style.display = hasActiveFilters ? 'inline-flex' : 'none';
        }
    }

    /**
     * Gửi yêu cầu AJAX để tải danh sách rút tiền
     * @param {string} url
     * @param {boolean} pushState
     */
    function loadWithdrawals(url, pushState = true) {
        const prevDesktopHTML = desktopContainer ? desktopContainer.innerHTML : '';
        const prevMobileHTML = mobileContainer ? mobileContainer.innerHTML : '';

        if (desktopContainer) {
            desktopContainer.classList.add('pointer-events-none');
            desktopContainer.innerHTML = getDesktopSkeletonHTML();
        }
        if (mobileContainer) {
            mobileContainer.classList.add('pointer-events-none');
            mobileContainer.innerHTML = getMobileSkeletonHTML();
        }

        axios.get(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            // Phân tách nội dung desktop và mobile từ response HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(response.data, 'text/html');

            const desktopSource = doc.getElementById('ajax-desktop-content');
            const mobileSource = doc.getElementById('ajax-mobile-content');

            if (desktopContainer) {
                desktopContainer.classList.remove('pointer-events-none');
                if (desktopSource) {
                    desktopContainer.innerHTML = desktopSource.innerHTML;
                }
            }
            if (mobileContainer) {
                mobileContainer.classList.remove('pointer-events-none');
                if (mobileSource) {
                    mobileContainer.innerHTML = mobileSource.innerHTML;
                }
            }

            // Vẽ lại Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Cập nhật thanh địa chỉ
            if (pushState) {
                window.history.pushState(null, '', url);
            }

            // Đồng bộ lại giá trị các form tĩnh
            syncFiltersFromURL();
        })
        .catch(error => {
            console.error('Lỗi tải lịch sử rút tiền:', error);
            if (desktopContainer) {
                desktopContainer.innerHTML = prevDesktopHTML;
                desktopContainer.classList.remove('pointer-events-none');
            }
            if (mobileContainer) {
                mobileContainer.innerHTML = prevMobileHTML;
                mobileContainer.classList.remove('pointer-events-none');
            }

            window.dispatchEvent(new CustomEvent('toast', { 
                detail: { text: 'Có lỗi xảy ra khi tải dữ liệu.', type: 'error' } 
            }));
        });
    }

    function getDesktopSkeletonHTML() {
        return `
            <div class="space-y-4 animate-pulse p-6">
                <div class="h-12 bg-gray-50 dark:bg-slate-800/50 rounded-xl mb-2"></div>
                <div class="h-12 bg-gray-50 dark:bg-slate-800/50 rounded-xl mb-2"></div>
                <div class="h-12 bg-gray-50 dark:bg-slate-800/50 rounded-xl"></div>
            </div>
        `;
    }

    function getMobileSkeletonHTML() {
        return `
            <div class="space-y-3 animate-pulse p-4">
                <div class="h-28 bg-gray-50 dark:bg-slate-800/50 rounded-2xl"></div>
                <div class="h-28 bg-gray-50 dark:bg-slate-800/50 rounded-2xl"></div>
            </div>
        `;
    }
});
