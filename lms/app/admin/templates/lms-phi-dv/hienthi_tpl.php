<?php
/**
 * Admin LMS — Thống kê phí dịch vụ (ADM-07).
 * Mockup: Tai-lieu-phat-trien/admin/Thống kê phí dịch vụ*.png.
 *
 * Data do source chuẩn bị: $teacherCode, $dateFrom, $dateTo, $teacher,
 * $transactions, $report, $canEdit, $alias.
 */
?>
<?php
$fmt = function ($v) {
    return number_format((float) $v, 0, ',', ',');
};

$signed = function ($v) {
    $v = (float) $v;
    if ($v == 0) {
        return '';
    }
    return ($v < 0 ? '−' : '+') . number_format(abs($v), 0, ',', ',');
};

$avatarSrc = '';
if (!empty($teacher['avatar'])) {
    $rawAv = (string) $teacher['avatar'];
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

$hasData = !empty($teacher) && !empty($transactions);
?>

<form method="get" action="<?= e(lms_url($alias)) ?>" class="row g-0 align-items-start">
    <div class="col-12 col-lg-8">
        <?php lms_component('form/input', array(
            'name'        => 'teacher_code',
            'label'       => 'Mã giáo viên',
            'value'       => $teacherCode,
            'placeholder' => 'VD: TE125',
            'required'    => true,
        )); ?>

        <div class="row mb-3 align-items-center lms-tf-row">
            <label class="col-12 col-sm-3 col-form-label text-sm-end lms-tf-label" for="fld-date-from">
                Khoảng thời gian
            </label>
            <div class="col-12 col-sm-9 lms-tf-control">
                <div class="d-flex flex-wrap gap-2">
                    <div class="flex-fill">
                        <?php lms_component('form/date_picker', array(
                            'name'     => 'date_from',
                            'id'       => 'fld-date-from',
                            'value'    => $dateFrom,
                            'required' => true,
                            'bare'     => true,
                        )); ?>
                    </div>
                    <div class="flex-fill">
                        <?php lms_component('form/date_picker', array(
                            'name'     => 'date_to',
                            'id'       => 'fld-date-to',
                            'value'    => $dateTo,
                            'required' => true,
                            'bare'     => true,
                        )); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3 lms-tf-row">
            <div class="col-12 col-sm-3"></div>
            <div class="col-12 col-sm-9 d-flex flex-wrap gap-2">
                <?php lms_component('button', array(
                    'label'   => 'Xuất tất cả phí dịch vụ',
                    'href'    => lms_url($alias, 'xuat-tat-ca', array(
                        'date_from' => $dateFrom,
                        'date_to'   => $dateTo,
                    )),
                    'variant' => 'danger',
                    'icon'    => 'fa-solid fa-file-csv',
                    'class'   => 'flex-fill',
                )); ?>
                <?php lms_component('button', array(
                    'label'   => 'Tìm',
                    'type'    => 'submit',
                    'variant' => 'primary',
                    'icon'    => 'fa-solid fa-magnifying-glass',
                    'class'   => 'px-5',
                )); ?>
            </div>
        </div>

        <?php lms_component('form/input', array(
            'name'        => '',
            'id'          => 'fld-ky-quy',
            'label'       => 'Ký quỹ',
            'value'       => $signed($report['ky_quy']),
            'placeholder' => '+100000, -100000, ...',
            'readonly'    => true,
        )); ?>

        <?php lms_component('form/input', array(
            'name'        => '',
            'id'          => 'fld-vi-pham',
            'label'       => 'Vi phạm',
            'value'       => $report['vi_pham'] > 0 ? '− ' . $fmt($report['vi_pham']) : '',
            'placeholder' => '+100000, -100000, ...',
            'readonly'    => true,
        )); ?>

        <?php lms_component('form/input', array(
            'name'        => '',
            'id'          => 'fld-phi-cong-them',
            'label'       => 'Phí dịch vụ cộng thêm',
            'value'       => $signed($report['phi_cong_them']),
            'placeholder' => '+100000, -100000, ...',
            'readonly'    => true,
        )); ?>

        <?php lms_component('form/input', array(
            'name'     => '',
            'id'       => 'fld-thue',
            'label'    => 'Thuế (có điều kiện xét)',
            'value'    => ((float) $report['thue_rate']) . '%',
            'readonly' => true,
            'hint'     => 'nếu có ký cam kết 08 thì thuế là 0%, nếu không ký và tổng phí từ 5tr thì thuế 10%',
        )); ?>
    </div>
</form>

<hr class="my-4">

<?php if (!$hasData): ?>
    <p class="text-center text-muted py-5 mb-0"><em>Chưa có dữ liệu</em></p>
<?php else: ?>
    <div class="d-flex flex-wrap justify-content-center align-items-center gap-4 my-4">
        <?php if ($avatarSrc !== ''): ?>
            <img src="<?= e($avatarSrc) ?>" alt="<?= e((string) $teacher['full_name']) ?>"
                 style="width:156px;height:156px;object-fit:cover;border-radius:8px;">
        <?php else: ?>
            <div class="d-flex align-items-center justify-content-center bg-light text-secondary"
                 style="width:156px;height:156px;border-radius:8px;">
                <i class="fa-solid fa-user" style="font-size:3rem;"></i>
            </div>
        <?php endif; ?>
        <div style="font-size: 1.125rem;">
            <div><strong>Tên :</strong> <?= e((string) $teacher['full_name']) ?></div>
            <div><strong>Mã Giáo Viên:</strong> <?= e((string) $teacher['teacher_code']) ?></div>
            <div><strong>Số Điện Thoại:</strong> <?= e((string) ($teacher['phone'] ?? '—')) ?></div>
            <div><strong>Email:</strong> <?= e((string) ($teacher['email'] ?? '—')) ?></div>
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div class="d-flex flex-wrap gap-2">
            <?php lms_component('button', array(
                'label'   => 'Xuất file phí dịch vụ',
                'href'    => lms_url($alias, 'xuat-phi-dv', array(
                    'teacher_code' => $teacherCode,
                    'date_from'    => $dateFrom,
                    'date_to'      => $dateTo,
                )),
                'variant' => 'primary',
                'icon'    => 'fa-solid fa-file-csv',
            )); ?>
            <?php lms_component('button', array(
                'label'   => 'Xuất file số buổi dạy',
                'href'    => lms_url($alias, 'xuat-buoi-day', array(
                    'teacher_code' => $teacherCode,
                    'date_from'    => $dateFrom,
                    'date_to'      => $dateTo,
                )),
                'variant' => 'outline-primary',
                'icon'    => 'fa-solid fa-calendar-days',
            )); ?>

        </div>
        <div class="fs-4 fw-bold">
            Tổng Tiền: <?= e($fmt($report['thuc_nhan'])) ?> đ
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0 lms-table text-center">
            <thead class="table-light">
                <tr>
                    <th style="width: 20%;">Thời Gian</th>
                    <th style="width: 15%;">Số Tiền</th>
                    <th>Nội Dung</th>
                    <th style="width: 18%;">Trạng Thái</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                    <?php
                    $amount = (float) $t['amount'];
                    $txStatus = isset($t['status']) ? (string) $t['status'] : 'draft';
                    ?>
                    <tr>
                        <td><?= e(lpd_time_label($t)) ?></td>
                        <td>
                            <span class="<?= $amount < 0 ? 'text-danger' : 'text-success' ?>">
                                <?= $amount < 0 ? '− ' : '+ ' ?><?= e($fmt(abs($amount))) ?>
                            </span>
                        </td>
                        <td><?= e(lpd_description($t)) ?></td>
                        <td>
                            <?php if ($txStatus === 'paid'): ?>
                                <?php lms_component('badge', array(
                                    'label'   => 'Đã thanh toán',
                                    'variant' => 'success',
                                )); ?>
                                <?php if (!empty($t['paid_at'])): ?>
                                    <div class="small text-muted mt-1"><?= e(substr((string) $t['paid_at'], 0, 10)) ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php lms_component('badge', array(
                                    'label'   => 'Chờ thanh toán',
                                    'variant' => 'warning',
                                    'class'   => 'text-dark',
                                )); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
