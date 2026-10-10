<?php
/**
 * Student — Nhận xét tháng (view, mockup §10 + §11).
 * Data do source chuẩn bị: $items (class rows kèm 'reviews' + 'schedules_text'),
 * $months (map 'YYYY-MM' => nhãn), $month (tháng đang chọn), $alias.
 */
?>
<?php if (empty($items)): ?>
    <div class="lms-card">
        <div class="lms-card-body text-center py-5">
            <i class="fa-solid fa-comments" style="font-size:2.4rem;color:#F26B2A;"></i>
            <p class="text-muted mt-3 mb-0">Bạn chưa được xếp vào lớp nào nên chưa có nhận xét tháng.</p>
        </div>
    </div>
<?php else: ?>

    <!-- ===================== Lọc theo tháng (§11) ===================== -->
    <div class="lms-card mb-3">
        <div class="lms-card-body d-flex align-items-center flex-wrap gap-2">
            <form method="get" action="<?= e(lms_url($alias)) ?>" class="d-flex align-items-center gap-2 flex-wrap">
                <label class="lms-hw-label mb-0" for="lmnx-month">Xem nhận xét tháng:</label>
                <select name="month" id="lmnx-month" class="form-select form-select-sm" style="width:auto;"
                        onchange="this.form.submit()">
                    <?php if (empty($months)): ?>
                        <option value="">Chưa có nhận xét</option>
                    <?php else: ?>
                        <?php foreach ($months as $m => $label): ?>
                            <option value="<?= e($m) ?>"<?= $m === $month ? ' selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </form>
            <?php if (!empty($months)): ?>
                <span class="text-muted" style="font-size:.85rem;">
                    <?= e((string) count($months)) ?> tháng có nhận xét
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===================== Danh sách lớp (collapse, §10) ===================== -->
    <?php $first = true; ?>
    <?php foreach ($items as $c): ?>
        <?php $cid = (int) $c['id']; ?>
        <div class="lms-card lms-collapse-card mb-3">
            <div class="lms-card-header lms-collapse-header" data-bs-toggle="collapse"
                 href="#lmnx-class-<?= e((string) $cid) ?>" role="button"
                 aria-expanded="<?= $first ? 'true' : 'false' ?>">
                <span class="lms-collapse-avatar"><i class="fa-solid fa-user-graduate"></i></span>
                <span class="lms-collapse-info">
                    <span class="lms-collapse-name"><?= e((string) $c['name']) ?></span>
                    <span class="lms-collapse-meta">
                        <?= e((string) $c['subject_name']) ?>
                        <?php if (!empty($c['schedules_text'])): ?>
                            <i class="fa-solid fa-clock ms-2 me-1"></i><?= e((string) $c['schedules_text']) ?>
                        <?php endif; ?>
                    </span>
                </span>
                <span class="lms-collapse-count">
                    <?= e((string) count($c['reviews'])) ?> nhận xét
                </span>
                <i class="fa-solid fa-chevron-down lms-subject-chevron"></i>
            </div>
            <div class="collapse<?= $first ? ' show' : '' ?>" id="lmnx-class-<?= e((string) $cid) ?>">
                <div class="lms-card-body">
                    <?php if (empty($c['reviews'])): ?>
                        <p class="text-muted mb-0">
                            <span class="lms-badge lms-badge-secondary me-1">Chưa có nhận xét</span>
                            Giáo viên chưa nhập nhận xét
                            <?= !empty($month) ? ' cho ' . e(\Lms\MonthlyReviewService::monthLabel($month)) : '' ?>.
                        </p>
                    <?php else: ?>
                        <?php foreach ($c['reviews'] as $r): ?>
                            <?php
                            $ratings = \Lms\MonthlyReviewService::OVERALL_RATINGS;
                            $rating = isset($r['overall_rating']) ? (string) $r['overall_rating'] : '';
                            $ratingLabel = isset($ratings[$rating]) ? $ratings[$rating] : $rating;
                            $badge = $rating === 'excellent' ? 'lms-badge-success'
                                : ($rating === 'need_improvement' ? 'lms-badge-danger' : 'lms-badge-primary');
                            $percent = $r['progress_percent'] === null ? null : (int) $r['progress_percent'];
                            ?>
                            <div class="lmnx-review">
                                <div class="lmnx-review-head">
                                    <span class="lms-badge lms-badge-secondary">
                                        <?= e(\Lms\MonthlyReviewService::monthLabel((string) $r['review_month'])) ?>
                                    </span>
                                    <?php if ($rating !== ''): ?>
                                        <span class="lms-badge <?= e($badge) ?>"><?= e($ratingLabel) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($r['teacher_name'])): ?>
                                        <span class="lmnx-review-teacher">
                                            <i class="fa-solid fa-chalkboard-user me-1"></i>GV <?= e((string) $r['teacher_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($r['content'])): ?>
                                    <div class="lmnx-review-content"><?= nl2br(e((string) $r['content'])) ?></div>
                                <?php else: ?>
                                    <div class="lmnx-review-content text-muted"><em>Chưa có nội dung nhận xét.</em></div>
                                <?php endif; ?>
                                <?php if ($percent !== null): ?>
                                    <div class="lmnx-review-progress">
                                        <span class="lmnx-review-progress-label">Tiến độ học tập</span>
                                        <div class="lms-progress">
                                            <div class="lms-progress-bar" style="width: <?= e(min(100, max(0, $percent))) ?>%"></div>
                                        </div>
                                        <span class="lms-progress-percent"><?= e((string) $percent) ?>%</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php $first = false; ?>
    <?php endforeach; ?>
<?php endif; ?>
