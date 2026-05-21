<?php
/**
 * View partial for a single review item.
 *
 * Variables expected:
 * - $review: object (BinhLuanModel instance or stdClass after fetch)
 * - $initial: string
 * - $media_items: array
 * - $replies: array
 */
global $d, $user_login;

// Ensure dependencies are available even if not passed explicitly (Main loop fix)
$media_items = $media_items ?? $review->media ?? [];
$replies     = $replies     ?? $review->replies ?? [];
$initial     = $initial     ?? mb_strtoupper(mb_substr($review->ho_ten ?: 'A', 0, 1));
?>
<div class="item-binhluan" data-star="<?= (int)$review->danh_gia ?>">
    <div class="review-user">
        <div class="review-avatar"><?= $initial ?></div>
        <div>
            <div class="fw-semibold"><?= htmlspecialchars($review->ho_ten) ?></div>
            <div class="review-date"><?= timeAgo($review->ngay) ?></div>
        </div>
        <?php if ($review->danh_gia > 0): ?>
        <div class="ms-auto"><?= renderStars($review->danh_gia, null, 14) ?></div>
        <?php endif; ?>
    </div>

    <?php if ($review->tieu_de): ?>
    <div class="review-title"><?= htmlspecialchars($review->tieu_de) ?></div>
    <?php endif; ?>

    <p class="review-body"><?= nl2br(htmlspecialchars($review->noi_dung)) ?></p>

    <?php if (!empty($media_items)): ?>
    <div class="review-media-grid">
        <?php foreach ($media_items as $media):
            $media_url = URLPATH . 'img_data/review/' . htmlspecialchars($media->ten_file);
        ?>
        <?php if ($media->loai === 'video'): ?>
            <a href="<?= $media_url ?>" data-fancybox="review-<?= $review->id ?>" class="review-media-item review-media-video">
                <video src="<?= $media_url ?>" muted playsinline preload="metadata"></video>
                <span class="video-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                </span>
            </a>
        <?php else: ?>
            <a href="<?= $media_url ?>" data-fancybox="review-<?= $review->id ?>" class="review-media-item">
                <img src="<?= $media_url ?>" alt="Ảnh đánh giá" loading="lazy">
            </a>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($replies)): ?>
    <div class="list-traloi mt-2">
        <?php foreach ($replies as $reply): ?>
        <div class="item-traloi">
            <strong><?= htmlspecialchars($reply->ho_ten ?: 'Admin') ?></strong>
            <i class="text-muted ms-1" style="font-size:.85em"><?= timeAgo($reply->ngay) ?></i>
            <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($reply->noi_dung)) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <a class="review-reply-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#reply-<?= $review->id ?>" href="#">
        <i class="fa-solid fa-reply fa-rotate-180"></i> <?= __('Trả lời') ?>
    </a>

    <div id="reply-<?= $review->id ?>" class="accordion-collapse collapse panel-traloi content-comment mt-3">
        <form method="POST" action="">
            <input type="hidden" name="parent" value="<?= (int)$review->id ?>"/>
            <?php if (!empty($_SESSION['id_login'])): ?>
                <input type="hidden" name="ho_ten" value="<?= htmlspecialchars($user_login['ho_ten'] ?? '') ?>"/>
                <input type="hidden" name="email"  value="<?= htmlspecialchars($user_login['email'] ?? '') ?>"/>
            <?php else: ?>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <input type="text" name="ho_ten" placeholder="<?= __('Nhập họ tên') ?>" class="form-control" required/>
                    </div>
                    <div class="col-md-6">
                        <input type="email" name="email" placeholder="<?= __('Nhập email') ?>" class="form-control" required/>
                    </div>
                </div>
            <?php endif; ?>
            <textarea class="form-control mb-2" rows="3" name="noi_dung" placeholder="<?= __('Nhập nội dung') ?>" required></textarea>
            <div class="d-flex gap-2">
                <button type="submit" name="guibinhluan" class="btn-custom btn-x btn-guibl">
                    <?= __('Gửi') ?>
                </button>
                <a class="btn-link text-muted" data-bs-toggle="collapse" data-bs-target="#reply-<?= $review->id ?>">
                    <?= __('Hủy') ?>
                </a>
            </div>
        </form>
    </div>
</div>
