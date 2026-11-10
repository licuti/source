<?php
/**
 * Teacher — Học viên của tôi.
 *
 * Dùng chung cấu trúc HTML card .lmnx-card với các trang Nhận xét tháng
 * Data do source chuẩn bị: $cards.
 */
?>
<?php if (empty($cards)): ?>
    <div class="lms-card">
        <div class="lms-card-body text-muted">Bạn chưa có học viên nào đang học.</div>
    </div>
<?php else: ?>
    <div class="lmnx-list">
        <?php foreach ($cards as $card): ?>
            <?php
            $cardUrl = isset($card['link_url']) ? (string) $card['link_url'] : '';
            $isGroup = !empty($card['is_group']);
            $avatarUrl = isset($card['avatar_url']) ? (string) $card['avatar_url'] : '';
            $ariaAction = $isGroup ? 'Mở chi tiết lớp ' : 'Mở chi tiết khóa học của ';
            ?>
            <a class="lms-card lmnx-card lmnx-card-link lmnx-card--avatar"
               href="<?= e($cardUrl) ?>"
               aria-label="<?= e($ariaAction . (string) $card['card_title']) ?>">
                <div class="lmnx-card-head">
                    <?php if ($avatarUrl !== ''): ?>
                        <div class="lmnx-cover">
                            <img src="<?= e($avatarUrl) ?>" alt="<?= e((string) $card['card_title']) ?>">
                        </div>
                    <?php else: ?>
                        <div class="lmnx-cover lmnx-cover-fallback">
                            <i class="fa-solid <?= $isGroup ? 'fa-user-group' : 'fa-user-graduate' ?>" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>

                    <div class="lmnx-summary">
                        <div class="lmnx-class-name"><?= e((string) $card['card_title']) ?></div>
                        <div class="lmnx-grade"><?= e((string) $card['grade_subject']) ?></div>

                        <?php if (!empty($card['schedule_rows'])): ?>
                            <div class="lmnx-schedules">
                                <?php foreach ($card['schedule_rows'] as $slot): ?>
                                    <div class="lmnx-schedule">
                                        <span class="lmnx-schedule-day"><?= e((string) $slot['day']) ?></span>
                                        <span class="lmnx-schedule-time"><?= e((string) $slot['time']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="lmnx-schedule-empty">—</div>
                        <?php endif; ?>

                        <div class="lmnx-card-status <?= e((string) $card['hw_class']) ?>">
                            <?= e((string) $card['hw_label']) ?>
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
