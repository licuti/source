<?php
/**
 * Student — Chi tiết khóa học: Lộ trình chuyên đề + Lesson Summary & BTVN.
 * Tham khảo thiết kế: Tai-lieu-phat-trien/student-ver2/Trang Chi tiết khóa học - Test-1.png
 *
 * Data: $class, $topics, $topicGroups, $sessions, $tests, $alias.
 * PHP 7.3 compatible.
 */
?>
<?php $csrf = lms_csrf_token(); ?>

<div class="lms-course-detail-view">
    <!-- Header: Tên khóa học + Link Xem tất cả -->
    <div class="lms-page-head">
        <h1 class="lms-page-title mb-0"><?= e((string) $class['name']) ?></h1>
        <a href="<?= e(lms_url($alias)) ?>" class="lms-view-all-link">
            Xem tất cả <i class="fa-solid fa-chevron-right ms-1"></i>
        </a>
    </div>

    <!-- ===================== Accordion: Lộ trình ===================== -->
    <div class="lms-section-accordion mb-4">
        <div class="lms-section-header" data-bs-toggle="collapse" data-bs-target="#collapseRoadmap" aria-expanded="true">
            <h4 class="lms-section-title mb-0">Lộ trình</h4>
            <i class="fa-solid fa-chevron-up lms-toggle-arrow"></i>
        </div>

        <div id="collapseRoadmap" class="collapse show mt-3">
            <?php if (empty($topicGroups)): ?>
                <div class="text-muted p-3 bg-white rounded-3">Chưa có chuyên đề nào cho khóa học này.</div>
            <?php else: ?>
                <?php $catIndex = 0; ?>
                <?php foreach ($topicGroups as $categoryName => $catTopics): ?>
                    <?php
                    $catIndex++;
                    $collapseCatId = 'collapseCategory' . $catIndex;
                    $isFirstCat = ($catIndex === 1);
                    ?>
                    <div class="lms-category-group mb-4">
                        <div class="lms-category-header" data-bs-toggle="collapse" data-bs-target="#<?= e($collapseCatId) ?>"
                             aria-expanded="<?= $isFirstCat ? 'true' : 'false' ?>">
                            <h5 class="lms-category-title mb-0"><?= e($categoryName) ?></h5>
                            <i class="fa-solid fa-chevron-<?= $isFirstCat ? 'up' : 'down' ?> lms-cat-arrow"></i>
                        </div>

                        <div id="<?= e($collapseCatId) ?>" class="collapse<?= $isFirstCat ? ' show' : '' ?> mt-3">
                            <div class="row g-3">
                                <?php foreach ($catTopics as $t): ?>
                                    <?php
                                    $topicId = (int) $t['id'];
                                    $max = $t['max_sessions'];
                                    $done = (int) $t['done_sessions'];
                                    $doneText = $max === null ? ('Đã dạy: ' . $done . ' buổi') : ('Đã dạy: ' . $done . '/' . (int) $max . ' buổi');

                                    $testScore = isset($t['best_test_score']) ? $t['best_test_score'] : null;
                                    $miniScore = isset($t['best_minitest_score']) ? $t['best_minitest_score'] : null;

                                    $testReady = !empty($t['test_ready']);
                                    $miniReady = !empty($t['minitest_ready']);
                                    $isCompleted = !empty($t['is_completed']);

                                    $topicUrl = lms_url($alias, 'chuyen-de', array('class_id' => (int) $class['id'], 'topic_id' => $topicId));
                                    $testUrl = lms_url($alias, 'chuyen-de', array('class_id' => (int) $class['id'], 'topic_id' => $topicId, 'section' => 'test'));
                                    $miniUrl = lms_url($alias, 'chuyen-de', array('class_id' => (int) $class['id'], 'topic_id' => $topicId, 'section' => 'minitest'));

                                    // Icon chọn theo danh mục hoặc index
                                    $isMathProblems = (strpos(strtolower($categoryName), 'problem') !== false);
                                    ?>
                                    <div class="col-md-6">
                                        <div class="lms-topic-card-v2">
                                            <div class="lms-topic-card-left">
                                                <div class="lms-topic-icon-wrap<?= $isMathProblems ? ' icon-orange' : ' icon-blue' ?>">
                                                    <?php if ($isMathProblems): ?>
                                                        <i class="fa-regular fa-file-lines"></i>
                                                    <?php else: ?>
                                                        <i class="fa-solid fa-book-open-reader"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="lms-topic-card-body">
                                                <a href="<?= e($topicUrl) ?>" class="lms-topic-name-link">
                                                    <?= e((string) $t['name']) ?>
                                                </a>
                                                <div class="lms-topic-meta-done mt-1">
                                                    <?= e($doneText) ?>
                                                </div>
                                                <div class="lms-topic-meta-scores mt-1">
                                                    <div>Điểm test: <span class="fw-semibold"><?= $testScore !== null ? e(number_format((float) $testScore, 1)) . '%' : '--%' ?></span></div>
                                                    <div>Điểm minitest: <span class="fw-semibold"><?= $miniScore !== null ? e(number_format((float) $miniScore, 1)) . '%' : '--%' ?></span></div>
                                                </div>
                                                <div class="lms-topic-actions-wrap mt-3">
                                                    <?php if ($isCompleted): ?>
                                                        <span class="btn btn-success btn-sm w-100 fw-bold disabled lms-btn-completed">
                                                            <i class="fa-solid fa-circle-check me-1"></i> Đã hoàn thành
                                                        </span>
                                                    <?php else: ?>
                                                        <!-- Hiển thị đồng thời 2 nút theo yêu cầu nghiệp vụ -->
                                                        <div class="d-flex gap-2 w-100">
                                                            <?php if ($testReady): ?>
                                                                <a href="<?= e($testUrl) ?>" class="btn btn-lms btn-sm flex-fill">
                                                                    Nộp test
                                                                </a>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-secondary btn-sm flex-fill lms-btn-disabled" disabled
                                                                        title="<?= !empty($t['has_pending_test']) ? 'Đang chờ Admin duyệt test' : 'Chưa có bài test' ?>">
                                                                    Nộp test
                                                                </button>
                                                            <?php endif; ?>

                                                            <?php if ($miniReady): ?>
                                                                <a href="<?= e($miniUrl) ?>" class="btn btn-lms btn-sm flex-fill">
                                                                    Nộp mini test
                                                                </a>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-secondary btn-sm flex-fill lms-btn-disabled" disabled
                                                                        title="Admin chưa duyệt minitest">
                                                                    Nộp mini test
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===================== Accordion: Lesson Summary & BTVN ===================== -->
    <div class="lms-section-accordion mb-4">
        <div class="lms-section-header" data-bs-toggle="collapse" data-bs-target="#collapseSessions" aria-expanded="true">
            <h4 class="lms-section-title mb-0">Lesson Summary &amp; BTVN</h4>
            <i class="fa-solid fa-chevron-up lms-toggle-arrow"></i>
        </div>

        <div id="collapseSessions" class="collapse show mt-3">
            <?php if (empty($sessions)): ?>
                <div class="text-muted p-3 bg-white rounded-3">Chưa có buổi học nào trong khóa.</div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($sessions as $s): ?>
                        <?php
                        $isFuture = !empty($s['is_future']);
                        $hasSummary = !empty($s['lesson_summary']);
                        $hasHw = !empty($s['hw_content']);
                        $isGraded = !empty($s['graded']);
                        $isSubmitted = !empty($s['hw_submitted_at']);

                        // Link nộp BTVN cho buổi học -> Trang Chi tiết buổi học (lms-lich-hoc/buoi)
                        $sessionSubmitUrl = lms_url('lms-lich-hoc', 'buoi', array('id' => (int) $s['id']));
                        ?>
                        <div class="col-md-6">
                            <div class="lms-session-card-v2">
                                <div class="lms-session-card-header d-flex align-items-center justify-content-between">
                                    <div class="fw-bold">
                                        <i class="fa-regular fa-calendar-days me-2"></i>Buổi học <?= e(lmkh_session_title($s)) ?>
                                    </div>
                                    <i class="fa-solid fa-chevron-up text-muted"></i>
                                </div>

                                <div class="lms-session-card-body mt-2">
                                    <?php if ($isFuture && !$hasSummary && !$hasHw): ?>
                                        <div class="lms-session-future-notice text-danger fst-italic py-4 text-center">
                                            Chưa diễn ra
                                        </div>
                                    <?php else: ?>
                                        <!-- Pill 1: Chuyên đề -->
                                        <div class="lms-session-pill">
                                            <i class="fa-regular fa-file-lines me-2 text-muted"></i>
                                            <div>
                                                <div class="lms-pill-label">Chọn các chuyên đề *</div>
                                                <div class="lms-pill-value">
                                                    <?= !empty($s['topic_names']) ? e(implode(', ', $s['topic_names'])) : 'Chưa chọn chuyên đề' ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Pill 2: BTVN -->
                                        <div class="lms-session-pill">
                                            <i class="fa-regular fa-file-lines me-2 text-muted"></i>
                                            <div>
                                                <div class="lms-pill-label">BTVN:</div>
                                                <div class="lms-pill-value">
                                                    <?= $hasHw ? nl2br(e((string) $s['hw_content'])) : 'Chưa có BTVN' ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Pill 3: Hạn nộp -->
                                        <div class="lms-session-pill">
                                            <i class="fa-regular fa-clock me-2 text-muted"></i>
                                            <div>
                                                <div class="lms-pill-label">Hạn nộp:</div>
                                                <div class="lms-pill-value<?= !empty($s['deadline_overdue']) ? ' text-danger' : '' ?>">
                                                    <?= !empty($s['deadline_label']) ? e($s['deadline_label']) : 'Chưa có hạn nộp' ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Pill 4: Nhận xét buổi học -->
                                        <div class="lms-session-pill">
                                            <i class="fa-regular fa-comment-dots me-2 text-muted"></i>
                                            <div>
                                                <div class="lms-pill-label">Nhận xét buổi học:</div>
                                                <div class="lms-pill-value">
                                                    <?= $hasSummary ? nl2br(e((string) $s['lesson_summary'])) : 'Chưa có nhận xét' ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Pill 5: Tệp đính kèm -->
                                        <div class="lms-session-pill">
                                            <i class="fa-solid fa-paperclip me-2 text-muted"></i>
                                            <div>
                                                <div class="lms-pill-label">Tệp đính kèm:</div>
                                                <div class="lms-pill-value">
                                                    <?php if (!empty($s['hw_attached_files'])): ?>
                                                        <?php foreach ($s['hw_attached_files'] as $f): ?>
                                                            <a href="<?= e(lms_upload_url((string) $f)) ?>" target="_blank" rel="noopener" class="d-inline-block me-2">
                                                                <i class="fa-solid fa-download me-1"></i><?= e(basename((string) $f)) ?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Trống</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Nút Nộp BTVN -->
                                        <div class="mt-3">
                                            <a href="<?= e($sessionSubmitUrl) ?>" class="btn btn-lms w-100">
                                                Nộp BTVN
                                            </a>
                                            <div class="text-center mt-2 small">
                                                <?php if ($isGraded): ?>
                                                    <span class="text-success fw-medium">Giáo viên đã chấm BTVN</span>
                                                <?php elseif ($isSubmitted): ?>
                                                    <span class="text-success fw-medium">Học sinh đã nộp BTVN</span>
                                                <?php else: ?>
                                                    <span class="text-danger fst-italic fw-medium">Chưa nộp BTVN</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
