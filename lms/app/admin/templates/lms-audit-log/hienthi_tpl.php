<?php
/** Admin LMS — Nhật ký hệ thống (read-only). */
$actorTypeMap = array(
    'admin'       => array('label' => 'Quản trị', 'variant' => 'primary'),
    'teacher'     => array('label' => 'Giáo viên', 'variant' => 'info'),
    'student'     => array('label' => 'Học viên', 'variant' => 'warning'),
    'user'        => array('label' => 'Người dùng', 'variant' => 'secondary'),
    'system_cron' => array('label' => 'Hệ thống/Cron', 'variant' => 'secondary'),
);
?>
<div class="lms-card">
    <div class="lms-card-header d-flex align-items-center gap-2 flex-wrap">
        <span><i class="fa-solid fa-clipboard-list me-1"></i> Nhật ký hệ thống</span>
        <?php lms_component('badge', array(
            'label'   => number_format((int) $total) . ' bản ghi',
            'type'    => 'lms',
            'variant' => 'secondary',
        )); ?>
    </div>
    <div class="lms-card-body">
        <form method="get" action="<?= e(lms_url($alias)) ?>" class="d-flex flex-wrap align-items-end gap-2 mb-3">
            <div style="min-width: 190px;">
                <label class="form-label small mb-1" for="audit-actor">Người thực hiện</label>
                <?php lms_component('form/select', array(
                    'name'        => 'actor',
                    'id'          => 'audit-actor',
                    'options'     => $actorOptions,
                    'value'       => $filterActor,
                    'empty_label' => 'Tất cả người thực hiện',
                    'bare'        => true,
                )); ?>
            </div>
            <div style="min-width: 170px;">
                <label class="form-label small mb-1" for="audit-action">Thao tác</label>
                <?php lms_component('form/select', array(
                    'name'        => 'action',
                    'id'          => 'audit-action',
                    'options'     => $actionOptions,
                    'value'       => $filterAction,
                    'empty_label' => 'Tất cả thao tác',
                    'bare'        => true,
                )); ?>
            </div>
            <div style="min-width: 160px;">
                <label class="form-label small mb-1" for="audit-target">Đối tượng</label>
                <?php lms_component('form/select', array(
                    'name'        => 'target',
                    'id'          => 'audit-target',
                    'options'     => $targetOptions,
                    'value'       => $filterTarget,
                    'empty_label' => 'Tất cả đối tượng',
                    'bare'        => true,
                )); ?>
            </div>
            <div style="min-width: 150px;">
                <label class="form-label small mb-1" for="audit-from">Từ ngày</label>
                <?php lms_component('form/date_picker', array(
                    'name'  => 'date_from',
                    'id'    => 'audit-from',
                    'value' => $dateFrom,
                    'bare'  => true,
                )); ?>
            </div>
            <div style="min-width: 150px;">
                <label class="form-label small mb-1" for="audit-to">Đến ngày</label>
                <?php lms_component('form/date_picker', array(
                    'name'  => 'date_to',
                    'id'    => 'audit-to',
                    'value' => $dateTo,
                    'bare'  => true,
                )); ?>
            </div>
            <div class="d-flex gap-2">
                <?php lms_component('button', array(
                    'label'   => 'Lọc',
                    'type'    => 'submit',
                    'variant' => 'primary',
                    'icon'    => 'fa-solid fa-magnifying-glass',
                )); ?>
                <?php lms_component('button', array(
                    'label'   => 'Xoá lọc',
                    'href'    => lms_url($alias),
                    'variant' => 'outline-secondary',
                )); ?>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0 lms-table">
                <thead class="table-light text-center">
                    <tr>
                        <th>Thời điểm</th>
                        <th>Người thực hiện</th>
                        <th>Thao tác</th>
                        <th>Đối tượng</th>
                        <th>IP</th>
                        <th style="width: 110px;">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                Không có bản ghi nhật ký phù hợp.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $entry): ?>
                            <?php
                            $before = json_decode((string) $entry['before_data'], true);
                            $after = json_decode((string) $entry['after_data'], true);
                            $hasBefore = is_array($before);
                            $hasAfter = is_array($after);
                            $detailId = 'audit-detail-' . (int) $entry['id'];

                            $actorType = (string) $entry['actor_type'];
                            $actorId = (int) $entry['actor_user_id'];
                            $actorMeta = isset($actorTypeMap[$actorType])
                                ? $actorTypeMap[$actorType]
                                : array('label' => $actorType !== '' ? $actorType : 'Không xác định', 'variant' => 'secondary');
                            $actorName = !empty($entry['username'])
                                ? (string) $entry['username']
                                : ($actorType === 'system_cron' ? 'Hệ thống/Cron' : '—');

                            $targetType = trim((string) $entry['target_type']);
                            $targetId = (int) $entry['target_id'];
                            $ip = trim((string) $entry['ip_address']);
                            $userAgent = trim((string) $entry['user_agent']);
                            ?>
                            <tr>
                                <td class="text-nowrap"><?= e(date('d/m/Y H:i:s', strtotime((string) $entry['created_at']))) ?></td>
                                <td class="text-nowrap">
                                    <div class="fw-semibold"><?= e($actorName) ?></div>
                                    <?php lms_component('badge', array(
                                        'label'   => $actorMeta['label'] . ($actorId > 0 ? ' #' . $actorId : ''),
                                        'type'    => 'lms',
                                        'variant' => $actorMeta['variant'],
                                    )); ?>
                                </td>
                                <td><code><?= e((string) $entry['action']) ?></code></td>
                                <td class="text-nowrap">
                                    <?php if ($targetType === ''): ?>
                                        <span class="text-muted">—</span>
                                    <?php else: ?>
                                        <?= e($targetType) ?><?= $targetId > 0 ? ' #' . $targetId : '' ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap"<?= $userAgent !== '' ? ' title="' . e($userAgent) . '"' : '' ?>>
                                    <?= $ip !== '' ? e($ip) : '<span class="text-muted">—</span>' ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($hasBefore || $hasAfter): ?>
                                        <?php lms_component('button', array(
                                            'label'   => 'Xem JSON',
                                            'type'    => 'button',
                                            'variant' => 'outline-secondary',
                                            'size'    => 'sm',
                                            'attrs'   => array(
                                                'data-bs-toggle' => 'collapse',
                                                'data-bs-target' => '#' . $detailId,
                                                'aria-expanded'  => 'false',
                                                'aria-controls'  => $detailId,
                                            ),
                                        )); ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($hasBefore || $hasAfter): ?>
                                <tr>
                                    <td colspan="6" class="p-0">
                                        <div class="collapse" id="<?= e($detailId) ?>">
                                            <div class="px-3 pt-2 small text-muted">
                                                IP: <?= $ip !== '' ? e($ip) : '—' ?>
                                                <?php if ($userAgent !== ''): ?>
                                                    · Thiết bị: <?= e(mb_strimwidth($userAgent, 0, 120, '…', 'UTF-8')) ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="lms-audit-json-grid<?= ($hasBefore && $hasAfter) ? '' : ' lms-audit-json-grid-single' ?>">
                                                <?php if ($hasBefore): ?>
                                                    <div>
                                                        <strong class="text-danger">Trước</strong>
                                                        <pre><?= e(json_encode($before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($hasAfter): ?>
                                                    <div>
                                                        <strong class="text-success">Sau</strong>
                                                        <pre><?= e(json_encode($after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($items)): ?>
            <?php lms_component('pagination', array(
                'base_url'    => lms_url($alias),
                'page'        => $page,
                'total_pages' => $totalPages,
                'params'      => array(
                    'actor'     => $filterActor,
                    'action'    => $filterAction,
                    'target'    => $filterTarget,
                    'date_from' => $dateFrom,
                    'date_to'   => $dateTo,
                ),
            )); ?>
        <?php endif; ?>
    </div>
</div>
