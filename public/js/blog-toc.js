/**
 * Tự động theo dõi cuộn màn hình và highlight đề mục đang đọc trong phần Mục lục (TOC).
 * Sử dụng IntersectionObserver để giám sát các thẻ H2 và H3 trong bài viết.
 */
document.addEventListener('DOMContentLoaded', () => {
    const headings = Array.from(document.querySelectorAll('.blog-content h2, .blog-content h3'));
    const tocLinks = document.querySelectorAll('.toc-link');
    
    if (headings.length > 0 && tocLinks.length > 0) {
        // Bản đồ lưu trạng thái hiển thị của các thẻ h2/h3 trong viewport
        const visibleHeadings = new Map();

        const observerOptions = {
            root: null,
            rootMargin: '-100px 0px -40% 0px', // Vùng quét nằm ở phần nửa trên của màn hình
            threshold: 0
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                visibleHeadings.set(entry.target, entry.isIntersecting);
            });

            // Tìm tiêu đề đầu tiên đang xuất hiện trong tầm mắt của người đọc
            let activeHeading = null;
            for (const heading of headings) {
                if (visibleHeadings.get(heading)) {
                    activeHeading = heading;
                    break;
                }
            }

            // Nếu không có tiêu đề nào trực tiếp nằm trong tầm mắt (ở giữa các mục dài)
            // Chọn tiêu đề gần nhất nằm ở phía trên vị trí cuộn hiện tại của trang
            if (!activeHeading) {
                const scrollPosition = window.scrollY + 120;
                for (let i = headings.length - 1; i >= 0; i--) {
                    if (headings[i].offsetTop <= scrollPosition) {
                        activeHeading = headings[i];
                        break;
                    }
                }
            }

            // Cập nhật trạng thái active cho các liên kết tương ứng trên thanh mục lục (TOC)
            if (activeHeading) {
                const activeId = activeHeading.getAttribute('id');
                tocLinks.forEach(link => {
                    const href = link.getAttribute('href');
                    const dot = link.querySelector('.toc-dot');
                    const isSubLink = link.classList.contains('pl-8');

                    if (href === `#${activeId}`) {
                        // Kích hoạt trạng thái Active (màu cam Shopee, chữ đậm, phóng to dot)
                        link.classList.add('text-shopee', 'font-semibold');
                        if (isSubLink) {
                            link.classList.remove('text-gray-400', 'dark:text-slate-500');
                        } else {
                            link.classList.remove('text-gray-555', 'dark:text-slate-400');
                        }

                        if (dot) {
                            dot.classList.add('bg-shopee', 'scale-125', 'ring-4', 'ring-shopee/10');
                            dot.classList.remove('bg-gray-300', 'dark:bg-slate-700');
                        }
                    } else {
                        // Trả về trạng thái Inactive mặc định
                        link.classList.remove('text-shopee', 'font-semibold');
                        if (isSubLink) {
                            link.classList.add('text-gray-400', 'dark:text-slate-500');
                        } else {
                            link.classList.add('text-gray-555', 'dark:text-slate-400');
                        }

                        if (dot) {
                            dot.classList.remove('bg-shopee', 'scale-125', 'ring-4', 'ring-shopee/10');
                            dot.classList.add('bg-gray-300', 'dark:bg-slate-700');
                        }
                    }
                });
            }
        }, observerOptions);

        // Bắt đầu theo dõi tất cả các thẻ tiêu đề
        headings.forEach(heading => observer.observe(heading));
    }
});
