<?php
/**
 * Admin LMS — Tổng quan (view).
 * Data do source chuẩn bị: $stats, $programStats.
 */
?>
<div class="row g-3 mb-4">
    <?php foreach ($stats as $s): ?>
        <div class="col-6 col-lg-3">
            <div class="lms-stat-card">
                <span class="lms-stat-icon"><i class="<?= e($s['icon']) ?>"></i></span>
                <div class="lms-stat-value"><?= e((string) number_format((int) $s['value'])) ?></div>
                <div class="lms-stat-label"><?= e($s['label']) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="lms-card">
    <div class="lms-card-header">
        <i class="fa-solid fa-layer-group me-1"></i> Thống kê nhóm chương trình
    </div>
    <div class="lms-card-body">
        <?php if (empty($programStats)): ?>
            <p class="text-muted mb-0">Chưa có chương trình nào.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 lms-table">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Nhóm chương trình</th>
                            <th class="text-end">Số lớp đang học</th>
                            <th class="text-end">Số lớp đang sắp xếp</th>
                            <th class="text-end">Số lớp tạm nghỉ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($programStats as $row): ?>
                            <tr>
                                <td><?= e((string) $row['name']) ?></td>
                                <td class="text-end"><?= e((string) number_format((int) $row['active_count'])) ?></td>
                                <td class="text-end"><?= e((string) number_format((int) $row['arranging_count'])) ?></td>
                                <td class="text-end"><?= e((string) number_format((int) $row['paused_count'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===================== Lịch tuần này (timeline 7 ngày) ===================== -->
<div class="lms-card mt-3">
    <div class="lms-card-header">
        <i class="fa-solid fa-calendar-week me-1"></i> Lịch tuần này
        <small class="text-muted fw-normal ms-2">(<?= e(date('d/m/Y', strtotime($weekStart))) ?> → <?= e(date('d/m/Y', strtotime($weekEnd))) ?>)</small>
        <small class="text-muted fw-normal ms-auto">Bấm vào buổi học để xem chi tiết</small>
    </div>
    <div class="lms-card-body">
        <?php
        // Helper gom nhóm các buổi học có cùng khung giờ (start_time & end_time)
        if (!function_exists('lms_group_timeline_sessions')) {
            function lms_group_timeline_sessions(array $events, $dowLabel) {
                if (empty($events)) {
                    return array();
                }

                // Sắp xếp theo start_time tăng dần, end_time tăng dần
                usort($events, function ($a, $b) {
                    if ($a['start_time'] !== $b['start_time']) {
                        return strcmp($a['start_time'], $b['start_time']);
                    }
                    return strcmp($a['end_time'], $b['end_time']);
                });

                $groups = array();
                foreach ($events as $ev) {
                    $key = substr((string) $ev['start_time'], 0, 5) . '-' . substr((string) $ev['end_time'], 0, 5);
                    if (!isset($groups[$key])) {
                        $dateStr = date('d/m/Y', strtotime((string) $ev['session_date']));
                        $timeStr = substr((string) $ev['start_time'], 0, 5) . '-' . substr((string) $ev['end_time'], 0, 5);
                        $groups[$key] = array(
                            'start_time'    => $ev['start_time'],
                            'end_time'      => $ev['end_time'],
                            'session_date'  => $ev['session_date'],
                            'display_title' => $dowLabel . ', ' . $dateStr . ' ' . $timeStr,
                            'items'         => array(),
                        );
                    }
                    $evCopy = $ev;
                    $evCopy['edit_link'] = lms_url('lms-lop', 'sua', array('id' => (int) $ev['class_id']));
                    $groups[$key]['items'][] = $evCopy;
                }
                return array_values($groups);
            }
        }

        // Nhóm buổi học theo thứ trong tuần (2=Thứ 2 .. 8=CN).
        $byDay = array();
        foreach ($weekSessions as $s) {
            // WEEKDAYS uses 2..8 (Monday..Sunday); date('N') uses 1..7.
            $dow = (int) date('N', strtotime((string) $s['session_date'])) + 1;
            $byDay[$dow][] = $s;
        }
        $hourHeight = 44; // px / giờ (khung 1:00–24:00)
        ?>
        <div class="lms-week-timeline">
            <div class="lms-wt-corner">Giờ</div>
            <?php foreach ($weekdays as $dow => $wd): ?>
                <div class="lms-wt-day-head<?= $dow === ((int) date('N') + 1) ? ' is-today' : '' ?>">
                    <?= e($wd) ?>
                    <small class="d-block text-muted">
                        <?= e(date('j/n', strtotime($weekStart . ' +' . ($dow - 2) . ' days'))) ?>
                    </small>
                </div>
            <?php endforeach; ?>

            <div class="lms-wt-gutter">
                <?php for ($h = 1; $h <= 24; $h++): ?>
                    <div class="lms-wt-hour" style="height: <?= $hourHeight ?>px"><?= e((string) $h) ?>:00</div>
                <?php endfor; ?>
            </div>

            <?php foreach ($weekdays as $dow => $wd): ?>
                <div class="lms-wt-day<?= $dow === ((int) date('N') + 1) ? ' is-today' : '' ?>">
                    <div class="lms-wt-day-inner" style="height: <?= $hourHeight * 24 ?>px">
                        <?php if (!empty($byDay[$dow])): ?>
                            <?php
                            $slotGroups = lms_group_timeline_sessions($byDay[$dow], $wd);
                            foreach ($slotGroups as $grp):
                                $startStr  = substr((string) $grp['start_time'], 0, 5);
                                $endStr    = substr((string) $grp['end_time'], 0, 5);
                                $startHour = (int) substr($startStr, 0, 2);
                                // Cùng công thức với lưới lịch: top theo phút lẻ, cao theo thời lượng
                                $top    = (($startHour - 1) * $hourHeight) + lms_cal_offset_px($startStr, $hourHeight);
                                $height = max(22, lms_cal_span_px($startStr, $endStr, $hourHeight) - 6);

                                $count = count($grp['items']);
                                $first = $grp['items'][0];
                                $firstType = (isset($first['class_type']) && $first['class_type'] === 'group') ? 'group' : '1on1';
                            ?>
                                <div class="lms-wt-slot-wrap" style="top: <?= $top ?>px; height: <?= $height ?>px;">
                                    <?php if ($count > 1): ?>
                                        <?php
                                        // Hiển thị các lớp thẻ xếp tầng phía sau (tối đa 3 tầng)
                                        $layerCount = min(3, $count - 1);
                                        for ($li = $layerCount; $li >= 1; $li--):
                                            $layerItem = isset($grp['items'][$li]) ? $grp['items'][$li] : $grp['items'][1];
                                            $layerType = (isset($layerItem['class_type']) && $layerItem['class_type'] === 'group') ? 'group' : '1on1';
                                            $offset = $li * 3; // 3px, 6px, 9px
                                        ?>
                                            <div class="lms-wt-stack-layer lms-wt-card-<?= $layerType ?>" style="transform: translate(-<?= $offset ?>px, -<?= $offset ?>px);"></div>
                                        <?php endfor; ?>
                                    <?php endif; ?>

                                    <button type="button"
                                            class="lms-wt-card lms-wt-card-<?= $firstType ?><?= $count > 1 ? ' lms-wt-card-stacked' : '' ?>"
                                            data-slot-json="<?= e(json_encode($grp)) ?>"
                                            title="Bấm để xem chi tiết buổi học">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="lms-wt-card-code"><?= e((string) $first['class_code']) ?></span>
                                            <?php if ($count > 1): ?>
                                                <span class="lms-wt-card-count">(<?= $count ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="lms-wt-card-time">
                                            <?= e($startStr) ?> - <?= e($endStr) ?>
                                        </div>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Chú thích màu sắc (Legend) theo mockup Tổng quan (2).png -->
        <div class="lms-wt-legend">
            <div class="lms-wt-legend-item">
                <span class="lms-wt-legend-box lms-wt-legend-1on1"></span>
                <span>Lớp 1-1</span>
            </div>
            <div class="lms-wt-legend-item">
                <span class="lms-wt-legend-box lms-wt-legend-group"></span>
                <span>Lớp đông người</span>
            </div>
        </div>
    </div>
</div>

<!-- Popup chi tiết các buổi học (theo mockup Tổng quan (2).png) -->
<div class="modal fade" id="lmsWtModal" tabindex="-1" aria-labelledby="lmsWtModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content">
            <div class="modal-header d-flex flex-column align-items-start border-0 pb-1">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h5 class="lms-wt-modal-title mb-0" id="lmsWtModalTitle">Thứ 3, 17/08/2026 17:30-19:00</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="lms-wt-modal-sub mt-2" id="lmsWtModalSubtitle">5 lớp:</div>
            </div>
            <div class="modal-body pt-1 pb-4">
                <div id="lmsWtModalList" class="d-flex flex-column gap-2">
                    <!-- Populated via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modalEl = document.getElementById('lmsWtModal');
    if (!modalEl) { return; }

    function getModal() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            return bootstrap.Modal.getOrCreateInstance(modalEl);
        }
        return null;
    }

    document.querySelectorAll('.lms-wt-card').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var modal = getModal();
            var raw = btn.getAttribute('data-slot-json');
            if (!raw) { return; }

            try {
                var grp = JSON.parse(raw);
                var titleEl = document.getElementById('lmsWtModalTitle');
                var subEl = document.getElementById('lmsWtModalSubtitle');
                var listEl = document.getElementById('lmsWtModalList');

                if (titleEl) {
                    titleEl.textContent = grp.display_title || '';
                }
                if (subEl) {
                    subEl.textContent = (grp.items ? grp.items.length : 1) + ' lớp:';
                }

                if (listEl && grp.items) {
                    listEl.innerHTML = '';
                    grp.items.forEach(function (item) {
                        var row = document.createElement('div');
                        row.className = 'lms-wt-modal-item';

                        var link = document.createElement('a');
                        link.className = 'lms-wt-modal-link';
                        link.href = item.edit_link || '#';
                        link.textContent = item.class_code + ' - ' + (item.teacher_name || 'Chưa phân công');

                        var stu = document.createElement('div');
                        stu.className = 'lms-wt-modal-students';
                        stu.textContent = 'Sĩ số: ' + (item.student_count || 1);

                        row.appendChild(link);
                        row.appendChild(stu);
                        listEl.appendChild(row);
                    });
                }

                if (modal) {
                    modal.show();
                }
            } catch (err) {
                console.error('[LMS Timeline] Modal parse error:', err);
            }
        });
    });
})();
</script>
