<?php
/**
 * Teacher — Nhận xét theo tháng (mockup teacher-ver2 + Form nhận xét lớp nhóm).
 *
 * Data do source chuẩn bị: $classes (mỗi lớp kèm 'students' + 'schedule_rows'),
 * $months (dropdown tháng), $month, $openClassId, $alias.
 *
 * Dùng chung cấu trúc card với trang Student — Nhận xét tháng
 * (.lmnx-card / .lmnx-card-head / .lmnx-cover / .lmnx-toggle).
 */

if (!function_exists('lmnxt_avatar_url')) {
    /**
     * URL avatar học viên (nếu có).
     *
     * @param string $raw
     * @return string
     */
    function lmnxt_avatar_url($raw)
    {
        $raw = trim((string) $raw);
        return $raw !== '' ? lms_upload_url(str_replace('uploads/', '', $raw)) : '';
    }
}
?>
<?php if (empty($classes)): ?>
    <div class="lms-card">
        <div class="lms-card-body text-center py-5 text-muted">
            Bạn chưa có học viên trong lớp đang hoạt động.
        </div>
    </div>
<?php else: ?>

    <div class="lmnx-list">
        <?php foreach ($classes as $class): ?>
            <?php
            $classId = (int) $class['class_id'];
            $isGroup = (string) $class['class_type'] === 'group';
            $students = isset($class['students']) ? $class['students'] : array();

            $title = $isGroup
                ? (string) $class['class_name']
                : (!empty($students[0]['student_name']) ? (string) $students[0]['student_name'] : (string) $class['class_name']);

            $grade = '';
            if (!$isGroup && !empty($students[0]['grade'])) {
                $grade = trim((string) $students[0]['grade']);
            } elseif ($isGroup && !empty($students)) {
                $grades = array();
                foreach ($students as $student) {
                    $g = trim((string) $student['grade']);
                    if ($g !== '') {
                        $grades[$g] = true;
                    }
                }
                $grade = count($grades) === 1 ? (string) key($grades) : 'Lớp nhóm';
            }
            $gradeSubject = trim(($grade !== '' ? $grade . ' - ' : '') . (string) $class['subject_name'], ' -');

            $avatarUrl = '';
            if (!$isGroup && !empty($students[0]['avatar'])) {
                $avatarUrl = lmnxt_avatar_url($students[0]['avatar']);
            }

            $bodyId = 'lmnxt-body-' . $classId;
            $isOpen = $openClassId === $classId;
            ?>
            <div class="lms-card lmnx-card">
                <div class="lmnx-card-head">
                    <?php if ($avatarUrl !== ''): ?>
                        <div class="lmnx-cover">
                            <img src="<?= e($avatarUrl) ?>" alt="<?= e($title) ?>">
                        </div>
                    <?php else: ?>
                        <div class="lmnx-cover lmnx-cover-fallback">
                            <i class="fa-solid <?= $isGroup ? 'fa-user-group' : 'fa-user-graduate' ?>"></i>
                        </div>
                    <?php endif; ?>

                    <div class="lmnx-summary">
                        <div class="lmnx-class-name"><?= e($title) ?></div>
                        <?php if ($gradeSubject !== ''): ?>
                            <div class="lmnx-grade"><?= e($gradeSubject) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($class['schedule_rows'])): ?>
                            <div class="lmnx-schedules">
                                <?php foreach ($class['schedule_rows'] as $slot): ?>
                                    <div class="lmnx-schedule">
                                        <i class="fa-regular fa-calendar"></i>
                                        <span class="lmnx-schedule-day"><?= e((string) $slot['day']) ?></span>
                                        <span class="lmnx-schedule-time"><?= e((string) $slot['time']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="button" class="lmnx-toggle" data-bs-toggle="collapse"
                            data-bs-target="#<?= e($bodyId) ?>" aria-expanded="<?= $isOpen ? 'true' : 'false' ?>"
                            aria-controls="<?= e($bodyId) ?>" aria-label="Mở/đóng form nhận xét">
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                </div>

                <div class="collapse<?= $isOpen ? ' show' : '' ?>" id="<?= e($bodyId) ?>">
                    <div class="lmnx-body">
                        <select class="form-select lmnx-month-select js-lmnxt-month"
                                data-class-id="<?= e((string) $classId) ?>"
                                aria-label="Chọn tháng nhận xét">
                            <?php foreach ($months as $m => $label): ?>
                                <option value="<?= e((string) $m) ?>"<?= $m === $month ? ' selected' : '' ?>>
                                    <?= e((string) $label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <form method="post" action="<?= e(lms_url($alias, 'luu')) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                            <input type="hidden" name="class_id" value="<?= e((string) $classId) ?>">
                            <input type="hidden" name="review_month" value="<?= e($month) ?>">

                            <?php foreach ($students as $student): ?>
                                <div class="lmnx-form-row">
                                    <label class="lmnx-form-label" for="lmnxt-review-<?= e((string) $classId) ?>-<?= e((string) $student['student_id']) ?>">
                                        <?= e((string) $student['student_name']) ?>
                                    </label>
                                    <input type="text" class="form-control lmnx-field-input"
                                           id="lmnxt-review-<?= e((string) $classId) ?>-<?= e((string) $student['student_id']) ?>"
                                           name="reviews[<?= e((string) $student['student_id']) ?>]"
                                           value="<?= e((string) $student['content']) ?>"
                                           maxlength="10000"
                                           placeholder="Nhập nhận xét tháng <?= e($month) ?>…">
                                </div>
                            <?php endforeach; ?>

                            <div class="lmnx-form-actions">
                                <button type="submit" class="btn btn-lms">Cập nhật</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    (function () {
        var selects = document.querySelectorAll('.js-lmnxt-month');
        for (var i = 0; i < selects.length; i++) {
            selects[i].addEventListener('change', function () {
                var url = new URL(window.location.href);
                url.searchParams.set('month', this.value);
                url.searchParams.set('open', this.getAttribute('data-class-id'));
                window.location.href = url.toString();
            });
        }
    })();
    </script>
<?php endif; ?>
