/**
 * ============================================================
 *  COMMON ADMIN SCRIPTS
 *  Chứa các hàm tái sử dụng cho toàn hệ thống Admin
 * ============================================================
 */

document.addEventListener("DOMContentLoaded", function() {

    // Helper wrapper cho hệ thống thông báo (Sử dụng AppNotify nếu có, nếu không fallback alert/confirm)
    const notify = typeof AppNotify !== 'undefined' ? AppNotify : {
        success: function(msg) { alert(msg); },
        error: function(msg) { alert('Lỗi: ' + msg); },
        warning: function(msg) { alert('Cảnh báo: ' + msg); },
        info: function(msg) { alert('Thông báo: ' + msg); },
        confirm: function(msg, cb) { if (confirm(msg)) cb(); }
    };

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
                    notify.success(data.message || 'Đã cập nhật thành công!');
                } else {
                    notify.error(data.message || 'Không thể cập nhật trạng thái.');
                    el.checked = originalState; // Hoàn tác nếu lỗi
                }
            })
            .catch(err => {
                console.error(err);
                el.disabled = false;
                el.checked = originalState;
                notify.error('Lỗi kết nối máy chủ.');
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
        const checked = document.querySelectorAll('.row-check:checked');
        if (selectedCount) selectedCount.textContent = checked.length;
        
        // Cập nhật trạng thái nút Áp dụng
        if (btnBulkApply) {
            const isDisabled = (checked.length === 0 || (bulkActionSelect && bulkActionSelect.value === ''));
            btnBulkApply.disabled = isDisabled;
            if (isDisabled) {
                btnBulkApply.classList.remove('btn-primary');
                btnBulkApply.classList.add('btn-outline-secondary');
            } else {
                btnBulkApply.classList.remove('btn-outline-secondary');
                btnBulkApply.classList.add('btn-primary');
            }
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

    if (bulkActionSelect) {
        bulkActionSelect.addEventListener('change', updateBulkPanel);
    }

    if (btnBulkApply && bulkActionSelect) {
        btnBulkApply.addEventListener('click', function() {
            const selectedOption = bulkActionSelect.options[bulkActionSelect.selectedIndex];
            const url = selectedOption.getAttribute('data-url');
            const confirmMsg = selectedOption.getAttribute('data-confirm');
            
            if (!selectedOption.value) {
                notify.warning('Vui lòng chọn một thao tác!');
                return;
            }

            if (!url) {
                console.error('Tùy chọn Bulk Action thiếu data-url!');
                return;
            }

            const checked = document.querySelectorAll('.row-check:checked');
            if (checked.length === 0) return;

            const executeAction = () => {
                const ids = Array.from(checked).map(cb => cb.value);
                const formData = new FormData();
                ids.forEach(id => formData.append('ids[]', id));
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
                        notify.success(data.message || 'Thao tác thành công! Trang sẽ tải lại.');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        notify.error(data.message || 'Không thể thực hiện thao tác.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    this.innerHTML = oldText;
                    this.disabled = false;
                    notify.error('Lỗi kết nối máy chủ.');
                });
            };

            // Nếu có cảnh báo confirm
            if (confirmMsg && confirmMsg.trim() !== '') {
                notify.confirm(confirmMsg, executeAction);
            } else {
                executeAction();
            }
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

    // ==========================================
    // 4. GLOBAL CONFIRM ACTION (Hộp thoại xác nhận hệ thống)
    // ==========================================
    document.addEventListener('click', function(e) {
        const target = e.target.closest('.confirm-delete, .btn-confirm-delete, [data-confirm-notify]');
        if (!target) return;

        e.preventDefault();
        const href = target.getAttribute('href');
        const formId = target.getAttribute('data-form-id');
        const confirmMsg = target.getAttribute('data-confirm-notify') || target.getAttribute('data-confirm') || 'Bạn có chắc chắn muốn thực hiện thao tác này?';

        notify.confirm(confirmMsg, function() {
            if (href && href !== '#' && href !== 'javascript:void(0);') {
                window.location.href = href;
            } else if (formId) {
                const form = document.getElementById(formId);
                if (form) form.submit();
            } else {
                const parentForm = target.closest('form');
                if (parentForm) parentForm.submit();
            }
        });
    });
});
