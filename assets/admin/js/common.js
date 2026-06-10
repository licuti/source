/**
 * ============================================================
 *  COMMON ADMIN SCRIPTS
 *  Chứa các hàm tái sử dụng cho toàn hệ thống Admin
 * ============================================================
 */

document.addEventListener("DOMContentLoaded", function() {

    // ==========================================
    // 1. AJAX TOGGLE STATUS (Siêu linh hoạt)
    // ==========================================
    const toggleStatusInputs = document.querySelectorAll('.ajax-toggle-status');
    toggleStatusInputs.forEach(input => {
        input.addEventListener('change', function() {
            const url = this.getAttribute('data-url');
            const id = this.getAttribute('data-id');
            const field = this.getAttribute('data-field') || 'hien_thi'; // Mặc định là hien_thi nếu không truyền
            const value = this.checked ? 1 : 0;
            const originalState = !this.checked;
            const el = this;

            if (!url || !id) {
                console.error("Thiếu data-url hoặc data-id cho Toggle Status!");
                el.checked = originalState;
                return;
            }

            el.disabled = true;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('field', field);
            formData.append('value', value);

            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                el.disabled = false;
                if (data.success) {
                    if (typeof AppNotify !== 'undefined') {
                        AppNotify.success(data.message || 'Đã cập nhật thành công!');
                    }
                } else {
                    if (typeof AppNotify !== 'undefined') {
                        AppNotify.error(data.message || 'Không thể cập nhật trạng thái.');
                    } else {
                        alert('Lỗi: ' + (data.message || 'Không thể cập nhật trạng thái.'));
                    }
                    el.checked = originalState; // Hoàn tác nếu lỗi
                }
            })
            .catch(err => {
                console.error(err);
                el.disabled = false;
                el.checked = originalState;
                if (typeof AppNotify !== 'undefined') {
                    AppNotify.error('Lỗi kết nối máy chủ.');
                } else {
                    alert('Lỗi kết nối máy chủ.');
                }
            });
        });
    });

    // ==========================================
    // 2. BULK ACTIONS (Thao tác hàng loạt đa năng)
    // ==========================================
    const checkAll = document.querySelector('.check-all');
    const rowChecks = document.querySelectorAll('.row-check');
    const bulkActionPanel = document.getElementById('bulkActionPanel');
    const selectedCount = document.getElementById('selectedCount');
    const btnBulkApply = document.getElementById('btnBulkApply');
    const bulkActionSelect = document.getElementById('bulkActionSelect');

    function updateBulkPanel() {
        if (!bulkActionPanel) return;
        const checked = document.querySelectorAll('.row-check:checked');
        if (selectedCount) selectedCount.textContent = checked.length;
        
        // Cập nhật trạng thái nút Áp dụng
        if (btnBulkApply) {
            btnBulkApply.disabled = checked.length === 0;
        }
        
        if (checkAll) {
            checkAll.checked = (checked.length === rowChecks.length && rowChecks.length > 0);
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            rowChecks.forEach(cb => {
                if(!cb.disabled) cb.checked = this.checked;
            });
            updateBulkPanel();
        });
    }

    rowChecks.forEach(cb => {
        cb.addEventListener('change', updateBulkPanel);
    });

    if (btnBulkApply && bulkActionSelect) {
        btnBulkApply.addEventListener('click', function() {
            const selectedOption = bulkActionSelect.options[bulkActionSelect.selectedIndex];
            const url = selectedOption.getAttribute('data-url');
            const confirmMsg = selectedOption.getAttribute('data-confirm');
            
            if (!selectedOption.value) {
                alert('Vui lòng chọn một thao tác!');
                return;
            }

            if (!url) {
                console.error('Tùy chọn Bulk Action thiếu data-url!');
                return;
            }

            const checked = document.querySelectorAll('.row-check:checked');
            if (checked.length === 0) return;

            // Nếu có cảnh báo confirm
            if (confirmMsg && confirmMsg.trim() !== '') {
                if (!confirm(confirmMsg)) return;
            }

            const ids = Array.from(checked).map(cb => cb.value);
            const formData = new FormData();
            ids.forEach(id => formData.append('ids[]', id));
            // Gửi thêm action code nếu Controller cần dùng chung 1 URL xử lý
            formData.append('action', selectedOption.value); 

            const oldText = this.innerHTML;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...';
            this.disabled = true;

            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                this.innerHTML = oldText;
                this.disabled = false;
                if (data.success) {
                    alert(data.message || 'Thao tác thành công! Trang sẽ tải lại.');
                    window.location.reload();
                } else {
                    alert('Lỗi: ' + (data.message || 'Không thể thực hiện thao tác.'));
                }
            })
            .catch(err => {
                console.error(err);
                this.innerHTML = oldText;
                this.disabled = false;
                alert('Lỗi kết nối máy chủ.');
            });
        });
    }

    // Khởi tạo trạng thái ban đầu
    updateBulkPanel();

    // ==========================================
    // 3. TỰ ĐỘNG CHUYỂN TAB KHI CÓ LỖI VALIDATION
    // ==========================================
    // Tự động chuyển tab nếu có input bị lỗi HTML5 validation (required) nằm trong tab đang ẩn
    document.addEventListener('invalid', function(e) {
        let target = e.target;
        let tabPane = target.closest('.tab-pane:not(.active)');
        
        if (tabPane) {
            let tabId = tabPane.getAttribute('id');
            let tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
            
            if (tabButton && typeof bootstrap !== 'undefined') {
                let tab = new bootstrap.Tab(tabButton);
                tab.show();
                setTimeout(() => target.focus(), 200);
            }
        }
    }, true);
});
