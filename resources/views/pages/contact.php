<?php
/**
 * View: Trang liên hệ
 */
?>

<section class="contact-page py-5">
    <div class="container">
        <h1 class="mb-4">Liên hệ với chúng tôi</h1>
        <div class="row">
            <div class="col-md-6">
                <h3>Thông tin liên hệ</h3>
                <p><b>Địa chỉ:</b> <?= $GLOBALS['info']->dia_chi ?? 'Đang cập nhật' ?></p>
                <p><b>Hotline:</b> <?= $GLOBALS['info']->hotline ?? 'Đang cập nhật' ?></p>
                <p><b>Email:</b> <?= $GLOBALS['info']->email ?? 'Đang cập nhật' ?></p>
            </div>
            
            <div class="col-md-6">
                <form action="api/contact/send" method="POST">
                    <div class="mb-3">
                        <label>Họ tên</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Nội dung</label>
                        <textarea name="message" class="form-control" rows="5"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Gửi tin nhắn</button>
                </form>
            </div>
        </div>
    </div>
</section>
