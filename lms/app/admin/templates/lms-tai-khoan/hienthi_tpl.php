<?php
/**
 * Admin LMS — Quản lý tài khoản (2 Tabs: Tài khoản học viên & Tài khoản giáo viên).
 * Mockup: Tai-lieu-phat-trien/admin/Tài khoản HV.png, Tài khoản GV.png.
 */
$studentStatuses = lms_enum_options('student_statuses');
$teacherStatuses = lms_enum_options('teacher_statuses');
$ranks           = lms_enum_options('teacher_ranks');

$studentStatusLabels = $studentStatuses;
?>
<!-- 2 Tabs: Tài khoản học viên / Tài khoản giáo viên -->
<?php lms_component('tabs', array(
    'aria_label' => 'Loại tài khoản',
    'items'      => array(
        array(
            'label'  => 'Tài khoản học viên',
            'href'   => lms_url($alias, 'man', array('tab' => 'hoc-vien')),
            'active' => $tab === 'hoc-vien',
        ),
        array(
            'label'  => 'Tài khoản giáo viên',
            'href'   => lms_url($alias, 'man', array('tab' => 'giao-vien')),
            'active' => $tab === 'giao-vien',
        ),
    ),
)); ?>

<?php if ($tab === 'hoc-vien'): ?>
    <!-- ===================== TAB 1: TÀI KHOẢN HỌC VIÊN ===================== -->
    <div class="lms-tab-content">
        <!-- Bộ lọc học viên (Mockup Tài khoản HV.png) -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <form method="get" action="<?= e(lms_url($alias)) ?>" class="d-flex flex-wrap align-items-center gap-2">
                <input type="hidden" name="tab" value="hoc-vien">
                <div style="min-width: 220px;">
                    <?php lms_component('form/input', array(
                        'name'        => 'q',
                        'value'       => $filterQ,
                        'placeholder' => 'Mã / Tên học viên',
                        'bare'        => true,
                        'class'       => 'form-control',
                    )); ?>
                </div>
                <div style="min-width: 180px;">
                    <?php
                    $subjOpts = array();
                    if (!empty($subjectList)) {
                        foreach ($subjectList as $sb) {
                            $subjOpts[(int) $sb['id']] = $sb['name'];
                        }
                    }
                    lms_component('form/select', array(
                        'name'        => 'subject_id',
                        'value'       => $filterSubject,
                        'options'     => $subjOpts,
                        'empty_label' => 'Chương trình học',
                        'bare'        => true,
                        'class'       => 'form-select',
                    ));
                    ?>
                </div>
                <?php lms_component('button', array(
                    'label'   => 'Tìm',
                    'type'    => 'submit',
                    'variant' => 'outline-secondary',
                    'class'   => 'px-3',
                )); ?>
            </form>
            <?php if ($canEdit): ?>
                <div>
                    <?php lms_component('button', array(
                        'label'   => 'Thêm',
                        'href'    => lms_url($alias, 'them-hoc-vien'),
                        'variant' => 'primary',
                        'size'    => 'md',
                        'icon'    => 'fa-solid fa-plus',
                    )); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="mb-2 fw-bold text-dark">
            Tổng: <?= number_format((int) $total) ?> học sinh
        </div>

        <!-- Bảng danh sách học viên (ghim cột: xem .lms-table-pin trong lms.css) -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0 text-nowrap lms-table lms-table-pin">
                <thead class="table-light text-center">
                    <tr>
                        <th data-pin-left>
                            Số ưu tiên <i class="fa-solid fa-filter fa-xs text-muted ms-1"></i>
                        </th>
                        <th data-pin-left>Username</th>
                        <th>Chương trình học</th>
                        <th style="min-width: 180px;">Lịch học hiện tại</th>
                        <th>Giáo viên 1-1</th>
                        <th style="min-width: 140px;">Ghi chú</th>
                        <th style="width: 130px;">
                            Trạng thái <i class="fa-solid fa-filter fa-xs text-muted ms-1"></i>
                        </th>
                        <th>
                            Chờ sắp xếp từ <i class="fa-solid fa-filter fa-xs text-muted ms-1"></i>
                        </th>
                        <th>
                            Ngày dự kiến bắt đầu học <i class="fa-solid fa-filter fa-xs text-muted ms-1"></i>
                        </th>
                        <th style="width: 130px;" data-pin-right>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                Chưa có học viên nào phù hợp.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $st): ?>
                            <?php
                            $isLocked = (isset($st['user_status']) && $st['user_status'] === 'locked');
                            $curStatus = isset($st['student_status']) ? $st['student_status'] : 'pending_arrange';
                            ?>
                            <tr>
                                <!-- Số ưu tiên (chỉnh inline) -->
                                <td class="text-center p-1" data-pin-left>
                                    <?php if ($canEdit): ?>
                                        <input type="number" 
                                               class="form-control form-control-sm text-center lms-inline-priority" 
                                               style="width: 58px; margin: 0 auto;" 
                                               value="<?= (int) $st['priority_number'] ?>" 
                                               data-user-id="<?= (int) $st['id'] ?>"
                                               min="0">
                                    <?php else: ?>
                                        <?= (int) $st['priority_number'] ?>
                                    <?php endif; ?>
                                </td>

                                <!-- Username -->
                                <td data-pin-left>
                                    <strong><?= e($st['username']) ?></strong>
                                    <?php if ($isLocked): ?>
                                        <?php lms_component('badge', array(
                                            'label'   => 'Khoá',
                                            'variant' => 'danger',
                                            'class'   => 'ms-1',
                                        )); ?>
                                    <?php endif; ?>
                                </td>

                                <!-- Chương trình học -->
                                <td>
                                    <?php
                                    $stSubjNames = array();
                                    if (!empty($st['subject_ids'])) {
                                        $stSubjIds = \Lms\UserService::decodeJsonArray($st['subject_ids']);
                                        foreach ($stSubjIds as $sid) {
                                            if (isset($subjOpts[(int) $sid])) {
                                                $stSubjNames[] = $subjOpts[(int) $sid];
                                            }
                                        }
                                    }
                                    $stSubjDisplay = !empty($stSubjNames)
                                        ? implode(', ', $stSubjNames)
                                        : (!empty($st['subject_name']) ? $st['subject_name'] : '—');
                                    ?>
                                    <?= e($stSubjDisplay) ?>
                                </td>

                                <!-- Lịch học hiện tại -->
                                <td class="text-wrap">
                                    <?php if (!empty($st['schedules_list'])): ?>
                                        <?php foreach ($st['schedules_list'] as $sch): ?>
                                            <div>
                                                <i class="fa-regular fa-clock text-muted me-1"></i>
                                                <?= e(date('d/m', strtotime($sch['session_date']))) ?> 
                                                <?= e(substr($sch['start_time'], 0, 5)) ?>–<?= e(substr($sch['end_time'], 0, 5)) ?>
                                                <span class="text-muted">(<?= e($sch['class_code']) ?>)</span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php elseif (!empty($st['class_names'])): ?>
                                        <?= e($st['class_names']) ?>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Chưa có lịch</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Giáo viên 1-1 -->
                                <td>
                                    <?= e(!empty($st['teacher_1on1']) ? $st['teacher_1on1'] : '—') ?>
                                </td>

                                <!-- Ghi chú -->
                                <td class="text-wrap">
                                    <?= e($st['note'] !== '' ? $st['note'] : '—') ?>
                                </td>

                                <!-- Trạng thái (chỉnh nhanh inline) -->
                                <td class="p-1 text-center">
                                    <?php if ($canEdit): ?>
                                        <select class="form-select form-select-sm lms-inline-status" 
                                                data-user-id="<?= (int) $st['id'] ?>">
                                            <?php foreach ($studentStatusLabels as $sk => $sl): ?>
                                                <option value="<?= e($sk) ?>" <?= $curStatus === $sk ? 'selected' : '' ?>>
                                                    <?= e($sl) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark">
                                            <?= e(isset($studentStatusLabels[$curStatus]) ? $studentStatusLabels[$curStatus] : $curStatus) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Chờ sắp xếp từ -->
                                <td class="text-center text-muted">
                                    <?php
                                    $waitDate = !empty($st['waiting_from']) ? $st['waiting_from'] : $st['created_at'];
                                    ?>
                                    <?= e(!empty($waitDate) ? date('d/m/Y', strtotime($waitDate)) : '—') ?>
                                </td>

                                <!-- Ngày dự kiến bắt đầu học -->
                                <td class="text-center text-muted">
                                    <?php if (!empty($st['expected_start_date'])): ?>
                                        <?= e(date('d/m/Y', strtotime($st['expected_start_date']))) ?>
                                    <?php elseif (!empty($st['created_at'])): ?>
                                        <?= e(date('d/m/Y', strtotime($st['created_at'] . ' +3 days'))) ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>

                                <!-- Hành động: Chi tiết / Khoá (link "Sửa" trùng URL Chi tiết nên đã bỏ) -->
                                <td class="text-center text-nowrap" data-pin-right>
                                    <a href="<?= e(lms_url($alias, 'sua-hoc-vien', array('id' => (int) $st['id']))) ?>" 
                                       class="lms-action-link">Chi tiết</a>
                                    <span class="text-muted">|</span>
                                    <?php if ($isLocked): ?>
                                        <a href="<?= e(lms_url($alias, 'mo-hoc-vien', array('id' => (int) $st['id']))) ?>" 
                                           class="text-success text-decoration-underline" 
                                           onclick="return confirm('Mở khoá tài khoản này?');">Mở</a>
                                    <?php else: ?>
                                        <a href="<?= e(lms_url($alias, 'khoa-hoc-vien', array('id' => (int) $st['id']))) ?>" 
                                           class="lms-action-link" 
                                           onclick="return confirm('Bạn chắc chắn muốn khoá tài khoản này?');">Khoá</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- ===================== TAB 2: TÀI KHOẢN GIÁO VIÊN ===================== -->
    <div class="lms-tab-content">
        <!-- Bộ lọc giáo viên (Mockup Tài khoản GV.png) -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <form method="get" action="<?= e(lms_url($alias)) ?>" class="d-flex flex-wrap align-items-center gap-2">
                <input type="hidden" name="tab" value="giao-vien">
                <div style="min-width: 250px;">
                    <?php lms_component('form/input', array(
                        'name'        => 'q',
                        'value'       => $filterQ,
                        'placeholder' => 'Mã / Tên giáo viên',
                        'bare'        => true,
                        'class'       => 'form-control',
                    )); ?>
                </div>
                <?php lms_component('button', array(
                    'label'   => 'Tìm',
                    'type'    => 'submit',
                    'variant' => 'outline-secondary',
                    'class'   => 'px-3',
                )); ?>
            </form>

            <div class="d-flex gap-2">
                <?php lms_component('button', array(
                    'label'   => 'Thanh toán phí dịch vụ',
                    'type'    => 'button',
                    'variant' => 'secondary',
                    'size'    => 'md',
                    'attrs'   => array(
                        'data-bs-toggle' => 'modal',
                        'data-bs-target' => '#lmsServiceFeeModal',
                    ),
                )); ?>
                <?php if ($canEdit): ?>
                    <?php lms_component('button', array(
                        'label'   => 'Thêm',
                        'href'    => lms_url($alias, 'them-giao-vien'),
                        'variant' => 'primary',
                        'size'    => 'md',
                        'icon'    => 'fa-solid fa-plus',
                    )); ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bảng danh sách giáo viên (ghim cột "Họ và tên" + "Hành động") -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0 text-nowrap lms-table lms-table-pin">
                <thead class="table-light text-center">
                    <tr>
                        <th style="min-width: 150px;" data-pin-left>Họ và tên</th>
                        <th>Username</th>
                        <th>Chương trình dạy</th>
                        <th>Số lớp đang dạy</th>
                        <th>Số điện thoại</th>
                        <th>Cam kết 08</th>
                        <th title="Tổng phí dịch vụ chờ thanh toán (tất cả giao dịch chưa thanh toán)">Phí DV chờ TT</th>
                        <th>Số tick tháng này</th>
                        <th style="width: 130px;" data-pin-right>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                Chưa có giáo viên nào phù hợp.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $t): ?>
                            <?php
                            $isLocked = (isset($t['user_status']) && $t['user_status'] === 'locked');
                            $rank = isset($t['rank']) ? (string) $t['rank'] : 'green';
                            $rankLabel = lms_enum_label('teacher_ranks', $rank, 'Xanh');
                            $rankColor = lms_enum_color('teacher_ranks', $rank, '#10b981');

                            // Format danh sách chương trình dạy
                            $subjNames = array();
                            $subIds = \Lms\UserService::decodeJsonArray(isset($t['teachable_subjects']) ? $t['teachable_subjects'] : array());
                            foreach ($subIds as $sid) {
                                if (isset($subjectMap[(int) $sid])) {
                                    $subjNames[] = $subjectMap[(int) $sid]['name'];
                                }
                            }
                            $subjStr = !empty($subjNames) ? implode(', ', array_slice($subjNames, 0, 2)) : 'Toán THCS';
                            ?>
                            <tr>
                                <!-- Họ và tên + Hạng -->
                                <td data-pin-left>
                                    <div class="fw-bold"><?= e($t['full_name']) ?></div>
                                    <div class="small">
                                        Hạng: <span style="color: <?= $rankColor ?>; font-weight: 600;"><?= e($rankLabel) ?></span>
                                    </div>
                                </td>

                                <!-- Username -->
                                <td>
                                    <strong><?= e($t['username']) ?></strong>
                                    <?php if ($isLocked): ?>
                                        <?php lms_component('badge', array(
                                            'label'   => 'Khoá',
                                            'variant' => 'danger',
                                            'class'   => 'ms-1',
                                        )); ?>
                                    <?php endif; ?>
                                </td>

                                <!-- Chương trình dạy -->
                                <td>
                                    <?= e($subjStr) ?>
                                </td>

                                <!-- Số lớp đang dạy -->
                                <td class="text-center">
                                    <?= (int) $t['active_classes'] ?>
                                </td>

                                <!-- Số điện thoại -->
                                <td>
                                    <?= e($t['phone'] !== '' ? $t['phone'] : '0707676299') ?>
                                </td>

                                <!-- Cam kết 08 -->
                                <td class="text-center">
                                    <?= !empty($t['is_commit_08']) ? '<span class="text-success fw-bold">Có</span>' : '<span class="text-muted">Không</span>' ?>
                                </td>

                                <!-- Phí dịch vụ chờ thanh toán -->
                                <?php $unpaidFee = isset($t['unpaid_fee']) ? (float) $t['unpaid_fee'] : 0.0; ?>
                                <td class="text-end fw-semibold" data-teacher-fee-cell="<?= (int) $t['id'] ?>">
                                    <span class="<?= $unpaidFee < 0 ? 'text-danger' : ($unpaidFee > 0 ? 'text-dark' : 'text-muted') ?>">
                                        <?= ($unpaidFee < 0 ? '-' : '') . number_format(abs($unpaidFee)) ?> đ
                                    </span>
                                </td>

                                <!-- Số tick tháng này -->
                                <td class="text-center">
                                    <?= (int) $t['month_ticks'] ?>
                                </td>

                                <!-- Hành động: Chi tiết / Khoá (link "Sửa" trùng URL Chi tiết nên đã bỏ) -->
                                <td class="text-center text-nowrap" data-pin-right>
                                    <a href="<?= e(lms_url($alias, 'sua-giao-vien', array('id' => (int) $t['id']))) ?>" 
                                       class="lms-action-link">Chi tiết</a>
                                    <span class="text-muted">|</span>
                                    <?php if ($isLocked): ?>
                                        <a href="<?= e(lms_url($alias, 'mo-giao-vien', array('id' => (int) $t['id']))) ?>" 
                                           class="text-success text-decoration-underline" 
                                           onclick="return confirm('Mở khoá tài khoản giáo viên này?');">Mở</a>
                                    <?php else: ?>
                                        <a href="<?= e(lms_url($alias, 'khoa-giao-vien', array('id' => (int) $t['id']))) ?>" 
                                           class="lms-action-link" 
                                           onclick="return confirm('Bạn chắc chắn muốn khoá tài khoản giáo viên này?');">Khoá</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($tab === 'giao-vien'): ?>
<?php
$feeModalRows = !empty($feeModalTeachers) && is_array($feeModalTeachers) ? $feeModalTeachers : $items;
$defaultMonthStart = date('Y-m-01');
$defaultMonthEnd   = date('Y-m-t');
?>
<!-- ===================== Modal: Thanh toán phí dịch vụ ===================== -->
<div class="modal fade lms-fee-modal" id="lmsServiceFeeModal" tabindex="-1"
     aria-labelledby="lmsServiceFeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="lmsServiceFeeModalLabel">Thanh toán phí dịch vụ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <div class="modal-body">
                <div id="lmsFeeModalAlert" class="alert d-none mb-3" role="alert"></div>

                <!-- Tìm kiếm -->
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <?php lms_component('form/input', array(
                        'name'        => 'fee_search',
                        'id'          => 'lmsFeeSearch',
                        'placeholder' => 'tìm nội dung trong bảng',
                        'bare'        => true,
                        'class'       => 'form-control',
                    )); ?>
                </div>

                <!-- Bảng giáo viên -->
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 lms-fee-table">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">Giáo viên</th>
                                <th class="text-center">Mã GV</th>
                                <th class="text-end" title="Số dư phí dịch vụ chờ thanh toán">Phí DV chờ thanh toán</th>
                                <th class="text-center">Chọn</th>
                            </tr>
                        </thead>
                        <tbody id="lmsFeeTableBody">
                            <?php if (empty($feeModalRows)): ?>
                                <tr data-empty-row="1">
                                    <td colspan="4" class="text-center py-3 text-muted">Chưa có giáo viên nào.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($feeModalRows as $feeRow): ?>
                                    <?php
                                    $feeRank = isset($feeRow['rank']) ? (string) $feeRow['rank'] : 'green';
                                    $feeRankLabel = lms_enum_label('teacher_ranks', $feeRank, 'Xanh');
                                    $feeRankColor = lms_enum_color('teacher_ranks', $feeRank, '#10b981');
                                    $feeUnpaid = isset($feeRow['unpaid_fee']) ? (float) $feeRow['unpaid_fee'] : 0.0;
                                    ?>
                                    <tr class="lms-fee-teacher-row" data-teacher-id="<?= (int) $feeRow['id'] ?>" data-unpaid-fee="<?= (float) $feeUnpaid ?>">
                                        <td>
                                            <div class="fw-semibold"><?= e((string) $feeRow['full_name']) ?></div>
                                            <div class="small text-muted">
                                                Hạng: <span style="color: <?= e($feeRankColor) ?>; font-weight: 600;"><?= e($feeRankLabel) ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center"><?= e((string) $feeRow['teacher_code']) ?></td>
                                        <td class="text-end fw-semibold" data-modal-fee-cell="<?= (int) $feeRow['id'] ?>">
                                            <span class="<?= $feeUnpaid < 0 ? 'text-danger' : ($feeUnpaid > 0 ? 'text-dark' : 'text-muted') ?>">
                                                <?= ($feeUnpaid < 0 ? '-' : '') . number_format(abs($feeUnpaid)) ?> đ
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input lms-fee-row-check"
                                                   value="<?= (int) $feeRow['id'] ?>" aria-label="Chọn <?= e((string) $feeRow['full_name']) ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang + chọn tất cả -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                    <nav aria-label="Phân trang giáo viên" class="lms-pagination">
                        <ul class="pagination pagination-sm mb-0" id="lmsFeePagination"></ul>
                    </nav>
                    <div class="d-flex align-items-center gap-3">
                        <div id="lmsFeeSelectedSummary" class="badge bg-light text-dark border px-3 py-2 small">
                            Đã chọn: <strong id="lmsFeeSelectedCount" class="text-primary">0</strong> GV
                            (Tổng: <strong id="lmsFeeSelectedTotal" class="text-danger">0 đ</strong>)
                        </div>
                        <div class="form-check mb-0">
                            <input type="checkbox" class="form-check-input" id="lmsFeeSelectAll">
                            <label class="form-check-label fw-semibold" for="lmsFeeSelectAll">Chọn tất cả</label>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Thanh toán / cộng lại -->
                <h6 class="fw-bold mb-3">Thanh toán</h6>

                <div class="row g-2 align-items-center mb-2">
                    <div class="col-md-4">
                        <span class="small text-muted">Thanh toán các buổi:</span>
                    </div>
                    <div class="col-md-3">
                        <?php lms_component('form/date_picker', array(
                            'name'  => 'fee_pay_from',
                            'id'    => 'lmsFeePayFrom',
                            'value' => !empty($feeModalEarliestDate) ? $feeModalEarliestDate : $defaultMonthStart,
                            'bare'  => true,
                            'class' => 'form-control',
                        )); ?>
                    </div>
                    <div class="col-md-3">
                        <?php lms_component('form/date_picker', array(
                            'name'  => 'fee_pay_to',
                            'id'    => 'lmsFeePayTo',
                            'value' => $defaultMonthEnd,
                            'bare'  => true,
                            'class' => 'form-control',
                        )); ?>
                    </div>
                    <div class="col-md-2 text-end">
                        <?php lms_component('button', array(
                            'type'    => 'button',
                            'id'      => 'lmsFeeBtnPay',
                            'label'   => 'Thanh toán',
                            'variant' => 'danger',
                            'size'    => 'md',
                            'class'   => 'w-100',
                        )); ?>
                    </div>
                </div>

                <div class="row g-2 align-items-center mb-4">
                    <div class="col-md-4">
                        <span class="small text-muted">Cộng lại các buổi:</span>
                    </div>
                    <div class="col-md-3">
                        <?php lms_component('form/date_picker', array(
                            'name'  => 'fee_add_from',
                            'id'    => 'lmsFeeAddFrom',
                            'value' => !empty($feeModalEarliestDate) ? $feeModalEarliestDate : $defaultMonthStart,
                            'bare'  => true,
                            'class' => 'form-control',
                        )); ?>
                    </div>
                    <div class="col-md-3">
                        <?php lms_component('form/date_picker', array(
                            'name'  => 'fee_add_to',
                            'id'    => 'lmsFeeAddTo',
                            'value' => $defaultMonthEnd,
                            'bare'  => true,
                            'class' => 'form-control',
                        )); ?>
                    </div>
                    <div class="col-md-2 text-end">
                        <?php lms_component('button', array(
                            'type'    => 'button',
                            'id'      => 'lmsFeeBtnUnpay',
                            'label'   => 'Thêm tiền',
                            'variant' => 'primary',
                            'size'    => 'md',
                            'class'   => 'w-100',
                        )); ?>
                    </div>
                </div>

                <!-- Điều chỉnh tiền -->
                <h6 class="fw-bold mb-2">Điều chỉnh số tiền cụ thể</h6>

                <div class="row g-2 mb-2">
                    <div class="col-md-5">
                        <?php lms_component('form/select', array(
                            'name'    => 'fee_adjust_type',
                            'id'      => 'lmsFeeAdjustType',
                            'options' => array(
                                'adjustment'     => 'Điều chỉnh +/- (trước thuế)',
                                'bonus'          => 'Thưởng / Phụ cấp (+ trước thuế)',
                                'penalty'        => 'Phạt vi phạm (- sau thuế)',
                                'deposit_deduct' => 'Trừ ký quỹ (- sau thuế)',
                            ),
                            'value'   => 'adjustment',
                            'bare'    => true,
                            'class'   => 'form-select',
                        )); ?>
                    </div>
                    <div class="col-md-7">
                        <?php lms_component('form/input', array(
                            'name'        => 'fee_adjust_amount',
                            'id'          => 'lmsFeeAdjustAmount',
                            'placeholder' => '+100000, -100000, ...',
                            'bare'        => true,
                            'class'       => 'form-control',
                        )); ?>
                    </div>
                </div>
                <div class="mb-2">
                    <?php lms_component('form/input', array(
                        'name'        => 'fee_adjust_reason',
                        'id'          => 'lmsFeeAdjustReason',
                        'placeholder' => 'Ghi nội dung điều chỉnh tiền tại đây....',
                        'bare'        => true,
                        'class'       => 'form-control',
                    )); ?>
                </div>
                <div class="text-end">
                    <?php lms_component('button', array(
                        'type'    => 'button',
                        'id'      => 'lmsFeeBtnAdjust',
                        'label'   => 'Điều chỉnh tiền',
                        'variant' => 'danger',
                        'size'    => 'md',
                    )); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('lmsServiceFeeModal');
        if (!modal) {
            return;
        }

        var csrfToken = document.querySelector('meta[name="lms-csrf-token"]')?.getAttribute('content')
            || '<?= e(lms_csrf_token()) ?>';
        var payUrl    = '<?= e(lms_url($alias, 'thanh-toan-phi-dv')) ?>';
        var unpayUrl  = '<?= e(lms_url($alias, 'cong-lai-phi-dv')) ?>';
        var adjustUrl = '<?= e(lms_url($alias, 'dieu-chinh-phi-dv')) ?>';

        var search     = document.getElementById('lmsFeeSearch');
        var selectAll  = document.getElementById('lmsFeeSelectAll');
        var tableBody  = document.getElementById('lmsFeeTableBody');
        var pagination = document.getElementById('lmsFeePagination');
        var alertBox   = document.getElementById('lmsFeeModalAlert');

        var btnPay    = document.getElementById('lmsFeeBtnPay');
        var btnUnpay  = document.getElementById('lmsFeeBtnUnpay');
        var btnAdjust = document.getElementById('lmsFeeBtnAdjust');

        var allRows = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('.lms-fee-teacher-row')) : [];
        var perPage = 6;
        var currentPage = 1;

        function formatCurrency(val) {
            var num = Math.round(Math.abs(Number(val) || 0));
            var formatted = num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return (Number(val) < 0 ? '-' : '') + formatted + ' đ';
        }

        function updateTeacherBalanceCells(balances) {
            if (!balances || typeof balances !== 'object') {
                return;
            }
            Object.keys(balances).forEach(function (tid) {
                var val = Number(balances[tid]) || 0;
                var cls = val < 0 ? 'text-danger' : (val > 0 ? 'text-dark' : 'text-muted');
                var html = '<span class="' + cls + '">' + formatCurrency(val) + '</span>';

                var row = document.querySelector('.lms-fee-teacher-row[data-teacher-id="' + tid + '"]');
                if (row) {
                    row.setAttribute('data-unpaid-fee', val);
                }

                var modalCell = document.querySelector('[data-modal-fee-cell="' + tid + '"]');
                if (modalCell) {
                    modalCell.innerHTML = html;
                }
                var pageCell = document.querySelector('[data-teacher-fee-cell="' + tid + '"]');
                if (pageCell) {
                    pageCell.innerHTML = html;
                }
            });
            updateSelectedSummary();
        }

        function updateSelectedSummary() {
            var count = 0;
            var total = 0;
            allRows.forEach(function (row) {
                var cb = row.querySelector('.lms-fee-row-check');
                if (cb && cb.checked) {
                    count++;
                    var fee = parseFloat(row.getAttribute('data-unpaid-fee') || '0');
                    if (!isNaN(fee)) {
                        total += fee;
                    }
                }
            });
            var countEl = document.getElementById('lmsFeeSelectedCount');
            var totalEl = document.getElementById('lmsFeeSelectedTotal');
            if (countEl) countEl.textContent = count;
            if (totalEl) totalEl.textContent = formatCurrency(total);
        }

        function showModalAlert(type, message, warnings) {
            if (!alertBox) {
                return;
            }
            alertBox.className = 'alert alert-' + type + ' mb-3';
            var html = '<div>' + String(message || '') + '</div>';
            if (Array.isArray(warnings) && warnings.length > 0) {
                html += '<ul class="mb-0 mt-1 small">';
                warnings.forEach(function (w) {
                    html += '<li>' + String(w) + '</li>';
                });
                html += '</ul>';
            }
            alertBox.innerHTML = html;
            alertBox.classList.remove('d-none');

            if (typeof Toastify === 'function') {
                Toastify({
                    text: String(message || ''),
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    backgroundColor: type === 'danger' ? '#dc2626' : '#16a34a'
                }).showToast();
            }
        }

        function getFilteredRows() {
            var keyword = search ? String(search.value || '').trim().toLowerCase() : '';
            if (keyword === '') {
                return allRows;
            }
            return allRows.filter(function (row) {
                return row.textContent.toLowerCase().indexOf(keyword) !== -1;
            });
        }

        function renderTablePage() {
            var filtered = getFilteredRows();
            var totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
            if (currentPage > totalPages) {
                currentPage = totalPages;
            }
            var start = (currentPage - 1) * perPage;
            var end   = start + perPage;

            allRows.forEach(function (row) {
                row.classList.add('d-none');
            });
            filtered.slice(start, end).forEach(function (row) {
                row.classList.remove('d-none');
            });

            if (pagination) {
                if (totalPages <= 1) {
                    pagination.innerHTML = '';
                } else {
                    var html = '';
                    html += '<li class="page-item ' + (currentPage <= 1 ? 'disabled' : '') + '">'
                         + '<a class="page-link" href="#" data-page="' + (currentPage - 1) + '">«</a></li>';
                    for (var p = 1; p <= totalPages; p++) {
                        html += '<li class="page-item ' + (p === currentPage ? 'active' : '') + '">'
                             + '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
                    }
                    html += '<li class="page-item ' + (currentPage >= totalPages ? 'disabled' : '') + '">'
                         + '<a class="page-link" href="#" data-page="' + (currentPage + 1) + '">»</a></li>';
                    pagination.innerHTML = html;
                }
            }
        }

        if (pagination) {
            pagination.addEventListener('click', function (e) {
                var link = e.target.closest('[data-page]');
                if (!link) {
                    return;
                }
                e.preventDefault();
                var targetPage = parseInt(link.getAttribute('data-page'), 10);
                if (!isNaN(targetPage) && targetPage >= 1) {
                    currentPage = targetPage;
                    renderTablePage();
                }
            });
        }

        if (search) {
            search.addEventListener('input', function () {
                currentPage = 1;
                renderTablePage();
            });
        }

        if (selectAll && tableBody) {
            selectAll.addEventListener('change', function () {
                var filtered = getFilteredRows();
                filtered.forEach(function (row) {
                    var checkbox = row.querySelector('.lms-fee-row-check');
                    if (checkbox) {
                        checkbox.checked = selectAll.checked;
                    }
                });
                updateSelectedSummary();
            });
        }

        if (tableBody) {
            tableBody.addEventListener('change', function (e) {
                if (e.target && e.target.classList.contains('lms-fee-row-check')) {
                    updateSelectedSummary();
                }
            });
        }

        function getSelectedTeacherIds() {
            var ids = [];
            allRows.forEach(function (row) {
                var cb = row.querySelector('.lms-fee-row-check');
                if (cb && cb.checked) {
                    var id = parseInt(cb.value, 10);
                    if (id > 0) {
                        ids.push(id);
                    }
                }
            });
            return ids;
        }

        function postFeeAction(url, params, onSuccess, onComplete) {
            var fd = new FormData();
            fd.append('csrf_token', csrfToken);
            fd.append('ajax', '1');
            Object.keys(params).forEach(function (k) {
                var v = params[k];
                if (v !== null && v !== undefined) {
                    if (Array.isArray(v)) {
                        v.forEach(function (item) {
                            fd.append(k + '[]', item);
                        });
                    } else {
                        fd.append(k, v);
                    }
                }
            });

            fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (typeof onComplete === 'function') onComplete();
                onSuccess(res);
            })
            .catch(function () {
                if (typeof onComplete === 'function') onComplete();
                showModalAlert('danger', 'Không thể kết nối đến máy chủ. Vui lòng thử lại.');
            });
        }

        if (btnPay) {
            btnPay.addEventListener('click', function () {
                var ids = getSelectedTeacherIds();
                if (ids.length === 0) {
                    showModalAlert('danger', 'Vui lòng tích chọn ít nhất một giáo viên trong bảng.');
                    return;
                }
                var dateFrom = document.getElementById('lmsFeePayFrom')?.value || '';
                var dateTo   = document.getElementById('lmsFeePayTo')?.value || '';
                if (!dateFrom || !dateTo) {
                    showModalAlert('danger', 'Vui lòng chọn khoảng ngày thanh toán.');
                    return;
                }
                var totalEl = document.getElementById('lmsFeeSelectedTotal');
                var totalText = totalEl ? ' (' + totalEl.textContent + ')' : '';
                if (!confirm('Xác nhận thanh toán phí dịch vụ cho ' + ids.length + ' giáo viên' + totalText + ' tính đến ngày ' + dateTo + '?')) {
                    return;
                }

                var groupCode = ids.length > 1 ? ('GRP-' + (new Date()).toISOString().slice(0, 10).replace(/-/g, '') + '-' + Math.random().toString(36).substr(2, 6).toUpperCase()) : null;

                var origText = btnPay.innerHTML;
                btnPay.disabled = true;
                btnPay.textContent = 'Đang xử lý...';

                postFeeAction(payUrl, {
                    teacher_ids: ids,
                    date_from: dateFrom,
                    date_to: dateTo,
                    group_code: groupCode
                }, function (res) {
                    if (res.ok) {
                        updateTeacherBalanceCells(res.balances);
                        showModalAlert(
                            res.warnings && res.warnings.length > 0 ? 'warning' : 'success',
                            res.message,
                            res.warnings
                        );
                    } else {
                        showModalAlert('danger', res.error || 'Không thể thanh toán phí dịch vụ.');
                    }
                }, function () {
                    btnPay.disabled = false;
                    btnPay.innerHTML = origText;
                });
            });
        }

        function executeUnpay(ids, dateFrom, dateTo, confirmPartial) {
            var origText = btnUnpay.innerHTML;
            btnUnpay.disabled = true;
            btnUnpay.textContent = 'Đang xử lý...';

            postFeeAction(unpayUrl, {
                teacher_ids: ids,
                date_from: dateFrom,
                date_to: dateTo,
                confirm_partial: confirmPartial ? '1' : '0'
            }, function (res) {
                if (res.needs_confirm) {
                    if (confirm(res.confirm_message || 'Khoảng ngày chọn thuộc một phần phiên thanh toán. Bạn có đồng ý hoàn tác toàn bộ phiên này không?')) {
                        executeUnpay(ids, dateFrom, dateTo, true);
                    }
                    return;
                }
                if (res.ok) {
                    updateTeacherBalanceCells(res.balances);
                    showModalAlert('success', res.message);
                } else {
                    showModalAlert('danger', res.error || 'Không thể hoàn tác giao dịch.');
                }
            }, function () {
                btnUnpay.disabled = false;
                btnUnpay.innerHTML = origText;
            });
        }

        if (btnUnpay) {
            btnUnpay.addEventListener('click', function () {
                var ids = getSelectedTeacherIds();
                if (ids.length === 0) {
                    showModalAlert('danger', 'Vui lòng tích chọn ít nhất một giáo viên trong bảng.');
                    return;
                }
                var dateFrom = document.getElementById('lmsFeeAddFrom')?.value || '';
                var dateTo   = document.getElementById('lmsFeeAddTo')?.value || '';
                if (!dateFrom || !dateTo) {
                    showModalAlert('danger', 'Vui lòng chọn khoảng ngày cần cộng lại.');
                    return;
                }
                executeUnpay(ids, dateFrom, dateTo, false);
            });
        }

        if (btnAdjust) {
            btnAdjust.addEventListener('click', function () {
                var ids = getSelectedTeacherIds();
                if (ids.length === 0) {
                    showModalAlert('danger', 'Vui lòng tích chọn ít nhất một giáo viên trong bảng.');
                    return;
                }
                var type   = document.getElementById('lmsFeeAdjustType')?.value || 'adjustment';
                var amount = document.getElementById('lmsFeeAdjustAmount')?.value || '';
                var reason = document.getElementById('lmsFeeAdjustReason')?.value || '';

                if (!String(amount).trim()) {
                    showModalAlert('danger', 'Vui lòng nhập số tiền điều chỉnh (VD: +100000 hoặc -100000).');
                    return;
                }
                if (!String(reason).trim()) {
                    showModalAlert('danger', 'Vui lòng nhập nội dung điều chỉnh tiền.');
                    return;
                }

                var idempotencyToken = 'ADJ-' + Date.now() + '-' + Math.random().toString(36).substr(2, 8);
                var origText = btnAdjust.innerHTML;
                btnAdjust.disabled = true;
                btnAdjust.textContent = 'Đang xử lý...';

                postFeeAction(adjustUrl, {
                    teacher_ids: ids,
                    type: type,
                    amount: amount,
                    reason: reason,
                    idempotency_token: idempotencyToken
                }, function (res) {
                    if (res.ok) {
                        updateTeacherBalanceCells(res.balances);
                        showModalAlert('success', res.message);
                        var amountEl = document.getElementById('lmsFeeAdjustAmount');
                        var reasonEl = document.getElementById('lmsFeeAdjustReason');
                        if (amountEl) amountEl.value = '';
                        if (reasonEl) reasonEl.value = '';
                    } else {
                        showModalAlert('danger', res.error || 'Không thể điều chỉnh tiền.');
                    }
                }, function () {
                    btnAdjust.disabled = false;
                    btnAdjust.innerHTML = origText;
                });
            });
        }

        renderTablePage();
        updateSelectedSummary();
    });
})();
</script>
<?php endif; ?>

<!-- Phân trang chuẩn theo mockup (Dùng component pagination) -->
<?php lms_component('pagination', array(
    'page'        => $page,
    'total_pages' => $totalPages,
    'base_url'    => lms_url($alias, 'man'),
    'params'      => array_filter(array(
        'tab'        => $tab,
        'q'          => $filterQ,
        'subject_id' => $filterSubject,
    ), function ($v) { return $v !== '' && $v !== 0 && $v !== null; }),
)); ?>

<!-- Script chỉnh sửa nhanh inline cho Học viên -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    var csrfToken = document.querySelector('meta[name="lms-csrf-token"]')?.getAttribute('content') || '';
    var updateUrl = '<?= e(lms_url($alias, 'ajax-update-hoc-vien')) ?>';

    function sendUpdate(userId, data) {
        var formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('user_id', userId);
        for (var k in data) {
            formData.append(k, data[k]);
        }

        fetch(updateUrl, {
            method: 'POST',
            body: formData
        })
        .then(function (res) { return res.json(); })
        .then(function (res) {
            if (res.ok) {
                if (typeof Toastify === 'function') {
                    Toastify({ text: 'Đã lưu thay đổi thành công!', duration: 2000, gravity: 'top', position: 'right', backgroundColor: '#16a34a' }).showToast();
                }
            } else {
                alert(res.error || 'Có lỗi xảy ra khi lưu.');
            }
        })
        .catch(function () {
            alert('Không thể kết nối đến máy chủ.');
        });
    }

    // Inline Số ưu tiên
    document.querySelectorAll('.lms-inline-priority').forEach(function (inp) {
        inp.addEventListener('change', function () {
            var userId = inp.getAttribute('data-user-id');
            var val = inp.value;
            sendUpdate(userId, { priority_number: val });
        });
    });

    // Inline Trạng thái
    document.querySelectorAll('.lms-inline-status').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var userId = sel.getAttribute('data-user-id');
            var val = sel.value;
            sendUpdate(userId, { status: val });
        });
    });
});
</script>
