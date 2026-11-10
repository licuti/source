<?php
/**
 * Admin LMS — Thống kê số buổi học (ADM-15).
 *
 * Đối chiếu chuẩn mockup:
 *  - Tai-lieu-phat-trien/admin/Thống kê số buổi học.png
 *  - Tai-lieu-phat-trien/admin/Tài liệu admin.md (§15. Thống kê số buổi học)
 *
 * Đã chuẩn hoá 100% theo hệ thống LMS UI/Form Components:
 *  - form/input, form/date_picker, form/field_row, button, pagination
 *
 * Data từ source lms-thong-ke-buoi-hoc.php:
 *  $studentCode, $dateFrom, $dateTo, $student, $items, $total,
 *  $finishedCount, $page, $totalPages, $alias.
 */

$avatarSrc = '';
if (!empty($student['avatar'])) {
    $rawAv = (string) $student['avatar'];
    if (strpos($rawAv, 'http://') === 0 || strpos($rawAv, 'https://') === 0) {
        $avatarSrc = $rawAv;
    } elseif (defined('_LMS_ROOT') && file_exists(_LMS_ROOT . '/media/images/' . $rawAv)) {
        $avatarSrc = '/media/images/' . $rawAv;
    } elseif (defined('_LMS_ROOT') && file_exists(_LMS_ROOT . '/' . $rawAv)) {
        $avatarSrc = '/' . ltrim($rawAv, '/');
    } elseif (function_exists('lms_upload_url')) {
        $avatarSrc = lms_upload_url(str_replace('uploads/', '', $rawAv));
    } else {
        $avatarSrc = '/' . ltrim($rawAv, '/');
    }
}

$weekdays = \Lms\UserService::WEEKDAYS;
?>

<!-- Form bộ lọc tra cứu học viên & khoảng thời gian (theo mockup Thống kê số buổi học.png) -->
<form method="get" action="<?= e(lms_url($alias)) ?>" novalidate>
    <div class="row">
        <div class="col-12 col-md-10 col-lg-7">

            <!-- 1. Mã học viên -->
            <?php lms_component('form/input', array(
                'name'        => 'student_code',
                'label'       => 'Mã học viên',
                'value'       => isset($studentCode) ? $studentCode : '',
                'placeholder' => 'VD: Master227, ST001',
            )); ?>

            <!-- 2. Khoảng thời gian (Từ ngày - Đến ngày) -->
            <?php
            ob_start();
            ?>
            <div class="d-flex flex-wrap gap-2">
                <div class="flex-fill">
                    <?php lms_component('form/date_picker', array(
                        'name'  => 'date_from',
                        'id'    => 'fld-date-from',
                        'value' => isset($dateFrom) ? $dateFrom : '',
                        'bare'  => true,
                    )); ?>
                </div>
                <div class="flex-fill">
                    <?php lms_component('form/date_picker', array(
                        'name'  => 'date_to',
                        'id'    => 'fld-date-to',
                        'value' => isset($dateTo) ? $dateTo : '',
                        'bare'  => true,
                    )); ?>
                </div>
            </div>
            <?php
            $dateRangeHtml = ob_get_clean();
            lms_component('form/field_row', array(
                'label'   => 'Khoảng thời gian',
                'id'      => 'fld-date-from',
                'control' => $dateRangeHtml,
            ));
            ?>

            <!-- 3. Nút Tìm căn phải dưới ô Khoảng thời gian -->
            <?php
            ob_start();
            ?>
            <div class="d-flex justify-content-end">
                <?php lms_component('button', array(
                    'label'   => 'Tìm',
                    'type'    => 'submit',
                    'variant' => 'primary',
                    'class'   => 'px-5',
                )); ?>
            </div>
            <?php
            $searchBtnHtml = ob_get_clean();
            lms_component('form/field_row', array(
                'label'   => '',
                'control' => $searchBtnHtml,
            ));
            ?>

        </div>
    </div>
</form>

<hr class="my-4 mx-auto">

<?php if ($studentCode !== '' && empty($student)): ?>
    <div class="alert alert-warning text-center">
        Không tìm thấy học viên với mã <strong><?= e($studentCode) ?></strong>.
    </div>
<?php elseif (empty($student)): ?>
    <p class="text-center text-muted py-4 mb-0">
        <em>Nhập mã học viên và bấm Tìm để xem thống kê số buổi học.</em>
    </p>
<?php else: ?>
    <!-- Card thông tin học viên (Ảnh đại diện + Tên + Mã Học Viên + SĐT + Email) -->
    <div class="d-flex flex-wrap justify-content-center align-items-center gap-4 my-4">
        <?php if ($avatarSrc !== ''): ?>
            <img src="<?= e($avatarSrc) ?>"
                 alt="<?= e((string) $student['full_name']) ?>"
                 style="width: 140px; height: 140px; object-fit: cover; border-radius: 8px;">
        <?php else: ?>
            <div class="d-flex align-items-center justify-content-center bg-light text-secondary border"
                 style="width: 140px; height: 140px; border-radius: 8px;">
                <i class="fa-solid fa-user-graduate" style="font-size: 3rem;"></i>
            </div>
        <?php endif; ?>

        <div style="font-size: 1.125rem;">
            <div>Tên : <strong><?= e((string) $student['full_name']) ?></strong></div>
            <div>Mã Học Viên: <strong><?= e((string) $student['student_code']) ?></strong></div>
            <div>Số Điện Thoại: <strong><?= e(!empty($student['phone']) ? (string) $student['phone'] : '—') ?></strong></div>
            <div>Email: <strong><?= e(!empty($student['email']) ? (string) $student['email'] : '—') ?></strong></div>
        </div>
    </div>

    <!-- Thanh hành động Xuất file CSV bên trái & Tổng số buổi học bên phải -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <?php lms_component('button', array(
                'label'   => 'Xuất file số buổi học',
                'href'    => lms_url($alias, 'xuat', array(
                    'student_code' => $student['student_code'],
                    'date_from'    => $dateFrom,
                    'date_to'      => $dateTo,
                )),
                'variant' => 'primary',
                'class'   => '',
            )); ?>
        </div>
        <div class="fs-4 fw-bold">
            Tổng số buổi học: <?= (int) $total ?> buổi
        </div>
    </div>

    <!-- Bảng chi tiết 5 cột (Thời Gian | Thứ | Số buổi học | Mã giáo viên | Ghi chú) -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0 lms-table text-center">
            <thead class="table-light">
                <tr>
                    <th style="width: 22%;">Thời Gian</th>
                    <th style="width: 16%;">Thứ</th>
                    <th style="width: 16%;">Số buổi học</th>
                    <th style="width: 18%;">Mã giáo viên</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Không có buổi học nào trong khoảng thời gian đã chọn.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $r):
                        $timeLabel = trim((string) $r['session_date'] . ' ' . substr((string) $r['start_time'], 0, 5));
                        $dow = (int) date('N', strtotime((string) $r['session_date']));
                        $dowLabel = isset($weekdays[$dow + 1]) ? $weekdays[$dow + 1] : '';
                    ?>
                        <tr>
                            <td class="py-3"><?= e($timeLabel) ?></td>
                            <td class="py-3"><?= e($dowLabel) ?></td>
                            <td class="py-3">1</td>
                            <td class="py-3"><?= e((string) $r['teacher_code']) ?></td>
                            <td class="py-3"><?= e((string) $r['lesson_summary']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Phân trang (component tự thêm mt-4) -->
    <?php lms_component('pagination', array(
        'base_url'    => lms_url($alias),
        'page'        => isset($page) ? $page : 1,
        'total_pages' => isset($totalPages) ? $totalPages : 1,
        'params'      => array(
            'student_code' => isset($studentCode) ? $studentCode : '',
            'date_from'    => isset($dateFrom) ? $dateFrom : '',
            'date_to'      => isset($dateTo) ? $dateTo : '',
        ),
    )); ?>
<?php endif; ?>
