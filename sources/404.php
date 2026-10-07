<?php
/**
 * Trang 404 — hiển thị khi router không tìm thấy alias khả dụng.
 *
 * Luồng định tuyến:
 * 1. .htaccess rewrite URL .html -> index.php?com=<alias>
 * 2. index.php include sources/lib/file_router_index.php
 * 3. Nếu alias không khớp category / tin tức / sản phẩm / album / video / route tĩnh
 *    -> $source vẫn là '404'
 * 4. index.php include sources/404.php
 */
http_response_code(404);
?>

<section class="page-404">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">

                <h1 class="page-404-num">404</h1>
                <h2 class="page-404-title">Không tìm thấy trang bạn yêu cầu</h2>
                <p class="page-404-desc">
                    Trang bạn đang tìm kiếm có thể đã bị xóa, đổi tên hoặc tạm thời không truy cập được.
                </p>

                <!-- Tìm kiếm -->
                <form action="<?= URLPATH ?>search.html" method="get" class="page-404-search mt-2 mb-5">
                    <div class="input-group">
                        <input type="text"
                               name="keyword"
                               class="form-control shadow-none border"
                               placeholder="Tìm kiếm sản phẩm, bài viết..."
                               aria-label="Tìm kiếm">
                        <button class="btn-custom btn-solid btn-x" type="submit">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Tìm kiếm
                        </button>
                    </div>
                </form>

                <!-- Nút hành động -->
                <div class="page-404-actions">
                    <a href="<?= URLPATH ?>" class="btn-custom btn-solid btn-x">
                        <i class="fa-solid fa-house"></i> Về trang chủ
                    </a>
                    <a href="javascript:history.back()" class="btn-custom btn-outline btn-x">
                        <i class="fa-solid fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <!-- Gợi ý liên hệ -->
                <div class="page-404-help">
                    <h5 class="fs-5 fw-bold">Cần hỗ trợ thêm?</h5>
                    <p class="small">
                        Nếu bạn tin rằng đây là lỗi, vui lòng liên hệ với chúng tôi qua hotline <strong><?= $info['dien_thoai'] ?? '' ?></strong>
                    </p>
                </div>

            </div>
        </div>
    </div>
</section>
