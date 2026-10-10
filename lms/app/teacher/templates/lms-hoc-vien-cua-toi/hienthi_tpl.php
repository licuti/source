<?php
/**
 * Teacher — Học viên của tôi (view).
 * Data do source chuẩn bị: $students, $statusLabels, $userStatusLabels.
 */
?>
<div class="lms-card">
    <div class="lms-card-header">
        <i class="fa-solid fa-user-graduate me-1"></i> Danh sách học viên
        <span class="lms-badge lms-badge-secondary ms-1"><?= e((string) count($students)) ?></span>
    </div>
    <div class="lms-card-body">
        <?php if (empty($students)): ?>
            <p class="text-muted mb-0">Bạn chưa có học viên nào.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="lms-table">
                    <thead>
                        <tr>
                            <th>Mã HV</th>
                            <th>Họ tên</th>
                            <th>Liên hệ</th>
                            <th>Lớp</th>
                            <th>Buổi đã dạy</th>
                            <th>Buổi sắp tới</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                            <?php
                            $st = isset($s['student_status']) ? (string) $s['student_status'] : 'pending_arrange';
                            $label = isset($statusLabels[$st]) ? $statusLabels[$st] : array($st, 'lms-badge-secondary');

                            $ust = isset($s['user_status']) ? (string) $s['user_status'] : 'active';
                            $ulabel = isset($userStatusLabels[$ust]) ? $userStatusLabels[$ust] : array($ust, 'lms-badge-secondary');
                            ?>
                            <tr>
                                <td><?= e((string) $s['student_code']) ?></td>
                                <td>
                                    <?= e((string) $s['full_name']) ?>
                                    <?php if (!empty($s['grade'])): ?>
                                        <small class="text-muted d-block"><?= e((string) $s['grade']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($s['phone'])): ?>
                                        <a href="tel:<?= e((string) $s['phone']) ?>"><?= e((string) $s['phone']) ?></a>
                                    <?php endif; ?>
                                    <?php if (!empty($s['email'])): ?>
                                        <small class="text-muted d-block"><?= e((string) $s['email']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= e((int) $s['class_count']) ?></td>
                                <td><?= e((int) $s['sessions_finished']) ?></td>
                                <td><?= e((int) $s['sessions_upcoming']) ?></td>
                                <td>
                                    <span class="lms-badge <?= e($label[1]) ?>"><?= e($label[0]) ?></span>
                                    <?php if ($ust !== 'active'): ?>
                                        <span class="lms-badge <?= e($ulabel[1]) ?> ms-1"><?= e($ulabel[0]) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>