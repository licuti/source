/**
 * ============================================================
 *  AUTO SLUG GENERATOR
 *  Tự động tạo Alias (Đường dẫn thân thiện) từ Tiêu đề
 * ============================================================
 */

function createSlug(str) {
    if (!str) return '';
    str = str.toLowerCase();
    
    // Xóa dấu tiếng Việt
    str = str.replace(/á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ/gi, 'a');
    str = str.replace(/é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ/gi, 'e');
    str = str.replace(/i|í|ì|ỉ|ĩ|ị/gi, 'i');
    str = str.replace(/ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ/gi, 'o');
    str = str.replace(/ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự/gi, 'u');
    str = str.replace(/ý|ỳ|ỷ|ỹ|ỵ/gi, 'y');
    str = str.replace(/đ/gi, 'd');
    
    // Thay thế các ký tự đặc biệt bằng dấu gạch ngang
    str = str.replace(/[^a-z0-9\-]+/g, '-');
    
    // Xóa các dấu gạch ngang thừa
    str = str.replace(/\-+/g, '-');
    str = str.replace(/^\-+|\-+$/g, '');
    
    return str;
}

document.addEventListener("DOMContentLoaded", function() {
    // Tìm tất cả các input nguồn (Tiêu đề/Tên)
    const sources = document.querySelectorAll('[data-slug-source]');
    
    sources.forEach(source => {
        const lang = source.getAttribute('data-slug-source');
        const target = document.querySelector(`[data-slug-target="${lang}"]`);
        
        if (target) {
            // Khi gõ vào ô Tiêu đề
            source.addEventListener('input', function() {
                // Chỉ tự động điền nếu ô Alias đang ở chế độ auto-slug
                if (target.classList.contains('auto-slug')) {
                    target.value = createSlug(this.value);
                }
            });

            // Khi người dùng chủ động sửa ô Alias
            target.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    // Nếu họ tự gõ chữ, tắt chế độ tự động
                    this.classList.remove('auto-slug');
                } else {
                    // Nếu họ xóa trắng ô Alias, bật lại chế độ tự động (lấy theo Tiêu đề hiện tại)
                    this.classList.add('auto-slug');
                    this.value = createSlug(source.value);
                }
            });
            
            // Xử lý Blur để làm sạch Alias nếu họ tự nhập bừa bãi
            target.addEventListener('blur', function() {
                if (this.value.trim() !== '') {
                    this.value = createSlug(this.value);
                }
            });
        }
    });
});
