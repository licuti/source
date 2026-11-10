<?php
/**
 * Student — Nhận xét tháng (mockup student-ver2 §10 + §11).
 *
 * Data do source chuẩn bị: $items (mỗi lớp kèm 'reviews', 'months',
 * 'schedule_rows', 'grade', 'subject_name'), $alias.
 *
 * Mỗi card có dropdown tháng riêng; đổi tháng chỉ đổi khung nội dung của lớp đó.
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

    <div class="row g-3">
        <?php foreach ($items as $c): ?>
            <?php
            $cid = (int) $c['id'];
            $reviews = isset($c['reviews']) ? $c['reviews'] : array();
            $months = isset($c['months']) ? $c['months'] : array();
            $cover = lms_subject_cover_url(isset($c['subject_name']) ? $c['subject_name'] : '');
            $gradeRaw = isset($c['grade']) ? trim((string) $c['grade']) : '';
            $grade = $gradeRaw === ''
                ? ''
                : (preg_match('/^lớp/u', mb_strtolower($gradeRaw, 'UTF-8')) ? $gradeRaw : 'Lớp ' . $gradeRaw);
            $bodyId = 'lmnx-body-' . $cid;
            ?>
            <div class="col-lg-6">
                <div class="lms-card lmnx-card">
                    <div class="lmnx-card-head">
                        <?php if ($cover !== ''): ?>
                            <div class="lmnx-cover">
                                <img src="<?= e($cover) ?>" alt="<?= e((string) $c['subject_name']) ?>">
                            </div>
                        <?php else: ?>
                            <div class="lmnx-cover lmnx-cover-fallback">
                                <i class="fa-solid fa-book-open"></i>
                            </div>
                        <?php endif; ?>

                        <div class="lmnx-summary">
                            <div class="lmnx-class-name"><?= e((string) $c['name']) ?></div>
                            <?php if ($grade !== ''): ?>
                                <div class="lmnx-grade"><?= e($grade) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($c['schedule_rows'])): ?>
                                <div class="lmnx-schedules">
                                    <?php foreach ($c['schedule_rows'] as $slot): ?>
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
                                data-bs-target="#<?= e($bodyId) ?>" aria-expanded="true"
                                aria-controls="<?= e($bodyId) ?>" aria-label="Mở/đóng nhận xét">
                            <i class="fa-solid fa-chevron-up"></i>
                        </button>
                    </div>

                    <div class="collapse show" id="<?= e($bodyId) ?>">
                        <div class="lmnx-body">
                            <?php if (!empty($months)): ?>
                                <select class="form-select lmnx-month-select" data-lmnx-class="<?= e((string) $cid) ?>"
                                        aria-label="Chọn tháng nhận xét">
                                    <?php foreach ($months as $m => $label): ?>
                                        <option value="<?= e((string) $m) ?>"><?= e((string) $label) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <?php foreach ($reviews as $r): ?>
                                    <div class="lmnx-review-box" data-lmnx-class="<?= e((string) $cid) ?>"
                                         data-month="<?= e((string) $r['review_month']) ?>">
                                        <?php if (!empty($r['content'])): ?>
                                            <?= nl2br(e((string) $r['content'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted"><em>Chưa có nội dung nhận xét.</em></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="lmnx-empty">Giáo viên chưa nhập nhận xét tháng cho lớp này.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    (function () {
        var sync = function (select) {
            var card = select.closest('.lmnx-card');
            if (!card) {
                return;
            }
            var boxes = card.querySelectorAll('.lmnx-review-box');
            for (var i = 0; i < boxes.length; i++) {
                boxes[i].style.display = boxes[i].getAttribute('data-month') === select.value ? '' : 'none';
            }
        };

        var init = function () {
            var selects = document.querySelectorAll('.lmnx-month-select');
            for (var i = 0; i < selects.length; i++) {
                (function (select) {
                    select.addEventListener('change', function () {
                        sync(select);
                    });
                    sync(select);
                })(selects[i]);
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
    </script>
<?php endif; ?>
