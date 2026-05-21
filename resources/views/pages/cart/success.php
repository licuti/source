<?php
    $order_code = isset($_GET['order']) ? htmlspecialchars($_GET['order']) : '';
?>

<div class="block py-5">
    <div class="container container-content">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 text-center mt-5 mb-5">
                <div class="success-icon mb-4">
                    <svg viewBox="0 0 24 24" class="text-success">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                
                <h1 class="fw-bold mb-3"><?= $d->getTxt(145) ?></h1>
                <p class="lead text-muted mb-4">Cảm ơn bạn đã tin tưởng mua sắm tại cửa hàng của chúng tôi!</p>
                
                <div class="order-box p-4 mb-4">
                    <div class="text-secondary small text-uppercase fw-bold mb-1">Mã đơn hàng của bạn</div>
                    <div class="h4 fw-bold text-x"><?= !empty($order_code) ? $order_code : 'Đang xử lý...' ?></div>
                    <div class="mt-2 text-muted small">Chúng tôi sẽ sớm liên hệ để xác nhận đơn hàng của bạn.</div>
                </div>

                <div class="instructions mb-5 text-start">
                    <div class="d-flex align-items-start mb-3">
                        <span class="badge bg-x text-white rounded-circle me-3 badge-circle-step">1</span>
                        <p class="mb-0">Kiểm tra thông tin đơn hàng đã được gửi tới email của bạn.</p>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <span class="badge bg-x text-white rounded-circle me-3 badge-circle-step">2</span>
                        <p class="mb-0">Bộ phận chăm sóc khách hàng sẽ liên hệ qua điện thoại để xác nhận đơn hàng.</p>
                    </div>
                    <div class="d-flex align-items-start">
                        <span class="badge bg-x text-white rounded-circle me-3 badge-circle-step">3</span>
                        <p class="mb-0">Đơn hàng của bạn sẽ sớm được đóng gói và bàn giao cho đơn vị vận chuyển.</p>
                    </div>
                </div>

                <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                    <a href="<?= URLPATH ?>" class="btn btn-lg btn-outline-secondary px-4 py-3 fw-bold btn-success-page">
                        <i class="fa fa-home me-2"></i> Về trang chủ
                    </a>
                    <a href="<?= URLPATH ?>san-pham.html" class="btn btn-lg btn-x text-white px-5 py-3 fw-bold btn-success-page">
                        <i class="fa fa-shopping-cart me-2"></i> Tiếp tục mua sắm
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>