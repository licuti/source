<?php
/**
 * Admin LMS — Quản lý giáo viên: view danh sách (ADM-02).
 * Data do source chuẩn bị: $items, $total, $totalPages, $page,
 * $filterQ, $filterStatus, $filterRank, $canEdit, $canDelete, $subjectMap.
 */
$statuses = \Lms\UserService::TEACHER_STATUSES;
$ranks = \Lms\UserService::TEACHER_RANKS;

$badgeClass = array(
    'active' => 'lms-badge-success',
    'paused' => 'lms-badge-warning',
    'quit'   => 'lms-badge-danger',
);
$rankBadge = array(
    'green'  => 'lms-badge-success',
    'gold'   => 'lms-badge-warning',
    'bronze' => 'lms-badge-secondary',
);
?>

<div class="lms-card">
    <div class="lms-card-header d-flex align-items-center gap-2 flex-wrap">
        <i class="fa-solid fa-chalkboard-user me-1"></i> Danh sách giáo viên
        <span class="ms-auto lms-text-muted-sm">Tổng: <?= e((string) number_format((int) $total)) ?> giáo viên</span>
    </div>
    <div class="lms-card-body">
        <form method="get" action="<?= e(lms_url($alias)) ?>" class="lms-filter-bar row g-2 mb-3">
            <div class="col-sm-12 col-md-4">
                <input type="search" name="q" class="form-control" placeholder="Mã / tên / username / SĐT..."
                       value="<?= e($filterQ) ?>" aria-label="Tìm kiếm">
            </div>
            <div class="col-sm-6 col-md-3">
                <select name="status" class="form-select" aria-label="Lọc theo trạng thái">
                    <option value="">Tất cả trạng thái</option>
                    <?php foreach ($statuses as $k => $label): ?>
                        <option value="<?= e($k) ?>"<?= lms_selected($filterStatus, $k) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <select name="rank" class="form-select" aria-label="Lọc theo hạng">
                    <option value="">Tất cả hạng</option>
                    <?php foreach ($ranks as $k => $label): ?>
                        <option value="<?= e($k) ?>"<?= lms_selected($filterRank, $k) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Tìm
                </button>
                <?php if ($canEdit): ?>
                    <a href="<?= e(lms_url($alias, 'them')) ?>" class="btn btn-success">
                        <i class="fa-solid fa-plus me-1"></i> Thêm
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (empty($items)): ?>
            <p class="text-muted mb-0 py-4 text-center">
                <i class="fa-solid fa-inbox d-block mb-2" style="font-size:1.6rem;"></i>
                Chưa có giáo viên nào phù hợp.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="lms-table">
                    <thead>
                        <tr>
                            <th>Giáo viên</th>
                            <th>Mã GV</th>
                            <th>Username</th>
                            <th>Môn dạy</th>
                            <th class="text-end">Số lớp</th>
                            <th>SĐT</th>
                            <th class="text-center">Cam kết 08</th>
                            <th class="text-end">Phí DV</th>
                            <th class="text-end">Tick tháng</th>
                            <th>Trạng thái</th>
                            <?php if ($canEdit || $canDelete): ?>
                                <th class="text-end">Hành động</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $r): ?>
                            <?php
                            $rank = isset($r['rank']) ? (string) $r['rank'] : 'bronze';
                            $stt = isset($r['teacher_status']) ? (string) $r['teacher_status'] : '';
                            $locked = (isset($r['user_status']) ? (string) $r['user_status'] : '') === 'locked';
                            $subjectIds = \Lms\UserService::decodeJsonArray(isset($r['teachable_subjects']) ? $r['teachable_subjects'] : array());
                            $subjectNames = array();
                            foreach ($subjectIds as $sid) {
                                $subjectNames[] = isset($subjectMap[(int) $sid]) ? $subjectMap[(int) $sid]['name'] : '';
                            }
                            $subjectNames = array_filter($subjectNames, function ($v) { return $v !== ''; });
                            ?>
                            <tr>
                                <td>
                                    <?= e((string) ($r['full_name'] ?? '')) ?>
                                    <?php if (isset($ranks[$rank])): ?>
                                        <span class="lms-badge <?= e(isset($rankBadge[$rank]) ? $rankBadge[$rank] : 'lms-badge-secondary') ?> ms-1">
                                            <?= e($ranks[$rank]) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= e((string) ($r['teacher_code'] ?? '')) ?></strong></td>
                                <td><?= e((string) ($r['username'] ?? '')) ?></td>
                                <td>
                                    <?php if ($subjectNames !== array()): ?>
                                        <?= e(implode(', ', $subjectNames)) ?>
                                    <?php else: ?>
                                        <span class="lms-text-muted-sm">Chưa có</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= e((string) number_format((int) ($r['active_classes'] ?? 0))) ?></td>
                                <td><?= e((string) ($r['phone'] ?? '')) ?></td>
                                <td class="text-center">
                                    <?php if (!empty($r['is_commit_08'])): ?>
                                        <i class="fa-solid fa-circle-check text-success" title="Cam kết 08"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-circle-minus text-muted" title="Không cam kết 08"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= e((string) ($r['service_fee_percentage'] ?? '0')) ?>%</td>
                                <td class="text-end">
                                    <?php $ticks = (int) ($r['month_ticks'] ?? 0); ?>
                                    <?= $ticks > 0 ? '<strong class="text-danger">' . e((string) $ticks) . '</strong>' : e('0') ?>
                                </td>
                                <td>
                                    <?php if (isset($statuses[$stt])): ?>
                                        <span class="lms-badge <?= e(isset($badgeClass[$stt]) ? $badgeClass[$stt] : 'lms-badge-secondary') ?>">
                                            <?= e($statuses[$stt]) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($locked): ?>
                                        <span class="lms-badge lms-badge-danger ms-1">Đã khoá</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($canEdit || $canDelete): ?>
                                    <td class="text-end text-nowrap">
                                        <?php if ($canEdit): ?>
                                            <a href="<?= e(lms_url($alias, 'sua', array('id' => (int) $r['id']))) ?>"
                                               class="btn btn-sm btn-outline-lms" title="Sửa">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <?php if ($locked): ?>
                                                <a href="<?= e(lms_url($alias, 'mo', array('id' => (int) $r['id']))) ?>"
                                                   class="btn btn-sm btn-outline-secondary" title="Mở khoá tài khoản">
                                                    <i class="fa-solid fa-lock-open"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= e(lms_url($alias, 'khoa', array('id' => (int) $r['id']))) ?>"
                                                   class="btn btn-sm btn-outline-secondary" title="Khoá tài khoản">
                                                    <i class="fa-solid fa-lock"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($canDelete): ?>
                                            <a href="<?= e(lms_url($alias, 'xoa', array('id' => (int) $r['id']))) ?>"
                                               class="btn btn-sm btn-outline-danger lms-confirm-delete"
                                               title="Xoá giáo viên">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?= lms_pagination(
                lms_url($alias),
                $page,
                $totalPages,
                array('q' => $filterQ, 'status' => $filterStatus, 'rank' => $filterRank)
            ) ?>
        <?php endif; ?>
    </div>
</div>

<script>
    (function ($) {
        'use strict';
        $(function () {
            $('.lms-confirm-delete').on('click', function (e) {
                if (!window.confirm('Bạn có chắc muốn xoá giáo viên này? Hành động không thể hoàn tác.')) {
                    e.preventDefault();
                }
            });
        });
    })(jQuery);
</script>
