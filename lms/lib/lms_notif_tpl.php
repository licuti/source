<?php
/**
 * View dùng chung cho module lms-thong-bao (T10).
 * Data do lms_notif_page.php chuẩn bị: $notifications, $unreadCount, $typeIcons.
 *
 * Được include từ lms/app/<portal>/templates/lms-thong-bao/hienthi_tpl.php.
 */
?>
<?php if ($unreadCount > 0): ?>
    <div class="lms-alert-banner" style="background:#cff4fc;border-color:#9eeaf9;color:#055160;">
        <i class="fa-solid fa-bell"></i>
        <span>Bạn có <strong><?= e((string) number_format($unreadCount)) ?></strong> thông báo chưa đọc.</span>
        <button type="button" class="btn-close-lms" aria-label="Đóng">&times;</button>
    </div>
<?php endif; ?>

<div class="lms-card">
    <div class="lms-card-header">
        <i class="fa-solid fa-bell me-1"></i> Tất cả thông báo
    </div>
    <div class="lms-card-body">
        <?php if (empty($notifications)): ?>
            <p class="text-muted mb-0 text-center py-4">
                <i class="fa-solid fa-bell-slash d-block mb-2" style="font-size:1.8rem;"></i>
                Chưa có thông báo nào.
            </p>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <?php
                $type = isset($n['type']) ? (string) $n['type'] : 'system';
                $icon = isset($typeIcons[$type]) ? $typeIcons[$type] : $typeIcons['system'];
                $link = !empty($n['link_url']) ? (string) $n['link_url'] : '';
                ?>
                <div class="d-flex gap-3 py-3 border-bottom <?= empty($n['is_read']) ? 'lms-unread-row' : '' ?>"
                     style="<?= empty($n['is_read']) ? 'background:#FDEDE3;' : '' ?>margin:0 -.5rem;padding-left:.5rem;padding-right:.5rem;">
                    <span class="lms-stat-icon" style="width:36px;height:36px;font-size:.95rem;margin-bottom:0;flex:0 0 auto;">
                        <i class="<?= e($icon) ?>"></i>
                    </span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold" style="font-size:.92rem;">
                            <?= e((string) $n['title']) ?>
                            <?php if (empty($n['is_read'])): ?>
                                <span class="lms-badge lms-badge-primary ms-1">Mới</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted" style="font-size:.85rem;"><?= e((string) $n['message']) ?></div>
                        <div class="text-muted mt-1" style="font-size:.75rem;">
                            <i class="fa-regular fa-clock me-1"></i>
                            <?= e((string) $n['created_at']) ?>
                        </div>
                        <?php if ($link !== ''): ?>
                            <a href="<?= e($link) ?>" class="lms-link" style="font-size:.83rem;">
                                Xem chi tiết <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
