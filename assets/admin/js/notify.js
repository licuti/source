/**
 * AppNotify - Lớp vỏ (Facade) thống nhất quản lý thông báo toàn hệ thống CMS
 * Dễ dàng thay đổi thư viện sau này bằng cách chỉ sửa nội dung file này.
 */

// Cấu hình mặc định cho Toastr
if (typeof toastr !== 'undefined') {
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "3000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
}

const AppNotify = {
    /**
     * Thông báo dạng Toast (không block màn hình)
     */
    success: function(message, title = 'Thành công') {
        if (typeof toastr !== 'undefined') {
            toastr.success(message, title);
        } else {
            alert(message); // Fallback
        }
    },
    error: function(message, title = 'Lỗi') {
        if (typeof toastr !== 'undefined') {
            toastr.error(message, title);
        } else {
            alert(message); // Fallback
        }
    },
    info: function(message, title = 'Thông báo') {
        if (typeof toastr !== 'undefined') {
            toastr.info(message, title);
        } else {
            alert(message); // Fallback
        }
    },
    warning: function(message, title = 'Cảnh báo') {
        if (typeof toastr !== 'undefined') {
            toastr.warning(message, title);
        } else {
            alert(message); // Fallback
        }
    },

    /**
     * Thông báo cần sự xác nhận (Confirmation Dialog)
     */
    confirm: function(message, callback) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Xác nhận!',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (typeof callback === 'function') {
                        callback();
                    }
                }
            });
        } else {
            // Fallback trình duyệt mặc định
            if (confirm(message)) {
                if (typeof callback === 'function') {
                    callback();
                }
            }
        }
    }
};
