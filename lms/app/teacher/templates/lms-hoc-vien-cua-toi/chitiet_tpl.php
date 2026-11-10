<?php
$csrf = lms_csrf_token();
$detailClass = $detail['class'];
$detailStudent = $detail['student'];
$weekdays = \Lms\UserService::WEEKDAYS;
$schedules = isset($detail['schedules']) && is_array($detail['schedules']) ? $detail['schedules'] : array();
$defaultSlotLabel = !empty($schedules)
    ? ((string) $schedules[0]['start_time'] . ' - ' . (string) $schedules[0]['end_time'])
    : '19:00 - 20:30';

// Bản ghi test/minitest mới nhất theo từng chuyên đề + toàn bộ test của chuyên đề.
$testsByTopic = array();
$topicTests = array();
foreach ($detail['tests'] as $test) {
    $topicId = (int) $test['topic_id'];
    $testType = (string) $test['test_type'];
    if ($testType !== 'test' && $testType !== 'minitest') {
        continue;
    }
    $topicTests[$topicId][] = $test;
    if (!isset($testsByTopic[$topicId][$testType])
        || strcmp((string) $test['deadline'], (string) $testsByTopic[$topicId][$testType]['deadline']) >= 0) {
        $testsByTopic[$topicId][$testType] = $test;
    }
}

// Nhóm chuyên đề theo Danh mục.
$topicGroups = array();
foreach ($detail['topics'] as $topic) {
    $groupName = isset($topic['subject_name']) && trim((string) $topic['subject_name']) !== ''
        ? (string) $topic['subject_name']
        : 'Khác';
    $topicGroups[$groupName][] = $topic;
}

// Map topic_id => tên chuyên đề để hiển thị trong card buổi học.
$topicNameMap = array();
foreach ($detail['topics'] as $topicRow) {
    $topicNameMap[(int) $topicRow['topic_id']] = (string) $topicRow['name'];
}

// Buổi học hiển thị: tuần hiện tại; nếu trống thì 2 buổi gần nhất.
$today = date('Y-m-d');
$weekMonday = date('Y-m-d', strtotime('monday this week'));
$weekSunday = date('Y-m-d', strtotime($weekMonday . ' +6 days'));
$allSessions = isset($detail['sessions']) && is_array($detail['sessions']) ? $detail['sessions'] : array();
$visibleSessions = array();
foreach ($allSessions as $session) {
    $date = (string) $session['session_date'];
    if ((string) $session['status'] === 'cancelled') {
        continue;
    }
    if (strcmp($date, $weekMonday) >= 0 && strcmp($date, $weekSunday) <= 0) {
        $visibleSessions[] = $session;
    }
}
if (count($visibleSessions) < 3) {
    $seen = array();
    foreach ($visibleSessions as $session) {
        $seen[(int) $session['id']] = true;
    }
    foreach ($allSessions as $session) {
        if ((string) $session['status'] === 'cancelled') {
            continue;
        }
        if (strcmp((string) $session['session_date'], $today) < 0) {
            continue;
        }
        if (isset($seen[(int) $session['id']])) {
            continue;
        }
        $visibleSessions[] = $session;
        $seen[(int) $session['id']] = true;
        if (count($visibleSessions) >= 3) {
            break;
        }
    }
}
if ($visibleSessions === array() && $allSessions !== array()) {
    $nonCancelled = array();
    foreach ($allSessions as $session) {
        if ((string) $session['status'] !== 'cancelled') {
            $nonCancelled[] = $session;
        }
    }
    $visibleSessions = $nonCancelled !== array() ? array_slice($nonCancelled, -2) : array_slice($allSessions, -2);
}
usort($visibleSessions, function ($a, $b) {
    $cmp = strcmp((string) $a['session_date'], (string) $b['session_date']);
    if ($cmp !== 0) {
        return $cmp;
    }
    return strcmp((string) $a['start_time'], (string) $b['start_time']);
});
?>
<!-- ===================== Accordion: Lộ trình ===================== -->
<div class="lms-section-accordion mb-4">
    <div class="lms-section-header" data-bs-toggle="collapse" data-bs-target="#lmsDetailRoadmapCollapse" aria-expanded="true">
        <h4 class="lms-section-title mb-0">Lộ trình</h4>
        <i class="fa-solid fa-chevron-up lms-toggle-arrow"></i>
    </div>

    <div id="lmsDetailRoadmapCollapse" class="collapse show mt-3">
        <?php if (empty($topicGroups)): ?>
            <div class="text-muted p-3 bg-white rounded-3">Lớp chưa được gán chuyên đề.</div>
        <?php else: ?>
            <?php $groupIndex = 0; ?>
            <?php foreach ($topicGroups as $groupName => $groupTopics): ?>
                <?php
                $groupIndex++;
                $collapseCatId = 'lmsRoadmapCollapse' . $groupIndex;
                $isMathProblems = (strpos(strtolower($groupName), 'problem') !== false);
                ?>
                <div class="lms-category-group mb-4">
                    <div class="lms-category-header" data-bs-toggle="collapse"
                         data-bs-target="#<?= e($collapseCatId) ?>" aria-expanded="true">
                        <h5 class="lms-category-title mb-0"><?= e($groupName) ?></h5>
                        <i class="fa-solid fa-chevron-up lms-cat-arrow"></i>
                    </div>

                    <div id="<?= e($collapseCatId) ?>" class="collapse show mt-3">
                        <div class="row g-3">
                            <?php foreach ($groupTopics as $topic): ?>
                                <?php
                                $topicId = (int) $topic['topic_id'];
                                $hasTestFlag = !empty($topic['has_test']);
                                $hasMiniFlag = !empty($topic['has_minitest']);
                                $hasAnyTest = $hasTestFlag || $hasMiniFlag;
                                $testInfo = isset($testsByTopic[$topicId]['test']) ? $testsByTopic[$topicId]['test'] : null;
                                $miniInfo = isset($testsByTopic[$topicId]['minitest']) ? $testsByTopic[$topicId]['minitest'] : null;
                                $testScore = $testInfo !== null && $testInfo['score_y_percent'] !== null
                                    ? rtrim(rtrim(number_format((float) $testInfo['score_y_percent'], 2, '.', ''), '0'), '.') . '%'
                                    : '--%';
                                $miniScore = $miniInfo !== null && $miniInfo['score_y_percent'] !== null
                                    ? rtrim(rtrim(number_format((float) $miniInfo['score_y_percent'], 2, '.', ''), '0'), '.') . '%'
                                    : '--%';

                                $topicTestRows = isset($topicTests[$topicId]) ? $topicTests[$topicId] : array();
                                $hasDraftTest = false;
                                $hasPublishedTest = false;
                                $hasStudentSubmitted = false;
                                $hasUngradedSubmission = false;
                                $hasPendingAdminApproval = false;
                                foreach ($topicTestRows as $testRow) {
                                    if ((string) $testRow['status'] === 'draft') {
                                        $hasDraftTest = true;
                                    }
                                    if ((string) $testRow['status'] === 'published') {
                                        $hasPublishedTest = true;
                                    }
                                    if (!empty($testRow['submitted_at'])) {
                                        $hasStudentSubmitted = true;
                                        if (empty($testRow['graded_at'])) {
                                            if ($testRow['score_x'] === null || $testRow['score_x'] === '') {
                                                $hasUngradedSubmission = true;
                                            } else {
                                                $hasPendingAdminApproval = true;
                                            }
                                        }
                                    }
                                }

                                $isAllCompleted = false;
                                if ($hasTestFlag && $hasMiniFlag) {
                                    $isAllCompleted = ($testInfo !== null && !empty($testInfo['graded_at'])
                                        && $miniInfo !== null && !empty($miniInfo['graded_at']));
                                } elseif ($hasTestFlag) {
                                    $isAllCompleted = ($testInfo !== null && !empty($testInfo['graded_at']));
                                } elseif ($hasMiniFlag) {
                                    $isAllCompleted = ($miniInfo !== null && !empty($miniInfo['graded_at']));
                                }

                                $chamUrl = lms_url('lms-hoc-vien-cua-toi', 'cham-test', array(
                                    'class_id' => (int) $detailClass['id'],
                                    'student_id' => (int) $detailStudent['user_id'],
                                    'cycle_id' => (int) $detail['cycle']['id'],
                                    'topic_id' => $topicId,
                                ));
                                $doneText = $topic['max_sessions'] === null
                                    ? 'Đã dạy: ' . (int) $topic['done_sessions'] . ' buổi'
                                    : 'Đã dạy: ' . (int) $topic['done_sessions'] . '/' . (int) $topic['max_sessions'] . ' buổi';
                                $isLocked = ((string) $topic['unlock_status'] === 'locked');
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
                                            <div class="lms-topic-name-link"><?= e((string) $topic['name']) ?></div>
                                            <div class="lms-topic-meta-done mt-1"><?= e($doneText) ?></div>

                                            <div class="lms-topic-meta-scores mt-1">
                                                <?php if ($hasAnyTest): ?>
                                                    <div>Điểm test: <span class="fw-semibold"><?= e($testScore) ?></span></div>
                                                    <div>Điểm minitest: <span class="fw-semibold"><?= e($miniScore) ?></span></div>
                                                <?php else: ?>
                                                    <div class="text-muted">Không test — dùng báo cáo chung.</div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="lms-topic-actions-wrap mt-3">
                                                <?php if ($isLocked): ?>
                                                    <?php lms_component('button', array(
                                                        'type' => 'button',
                                                        'label' => 'Chưa mở khóa',
                                                        'variant' => 'light',
                                                        'size' => 'sm',
                                                        'class' => 'w-100 lms-btn-disabled',
                                                        'disabled' => true,
                                                    )); ?>
                                                <?php elseif ($hasAnyTest):
                                                    if ($isAllCompleted) {
                                                        lms_component('button', array(
                                                            'type' => 'button',
                                                            'label' => 'Đã hoàn thành',
                                                            'variant' => 'light',
                                                            'size' => 'sm',
                                                            'class' => 'w-100 lms-btn-disabled',
                                                            'disabled' => true,
                                                        ));
                                                    } elseif ($hasUngradedSubmission) {
                                                        lms_component('button', array(
                                                            'href' => $chamUrl,
                                                            'label' => 'Chấm test',
                                                            'variant' => 'primary',
                                                            'size' => 'sm',
                                                            'class' => 'w-100',
                                                        ));
                                                    } elseif ($hasPendingAdminApproval) {
                                                        lms_component('button', array(
                                                            'href' => $chamUrl,
                                                            'label' => 'Chờ duyệt KQ',
                                                            'variant' => 'primary',
                                                            'size' => 'sm',
                                                            'class' => 'w-100',
                                                        ));
                                                    } elseif ($hasPublishedTest) {
                                                        lms_component('button', array(
                                                            'type' => 'button',
                                                            'label' => 'Chờ học sinh nộp',
                                                            'variant' => 'light',
                                                            'size' => 'sm',
                                                            'class' => 'w-100 lms-btn-disabled',
                                                            'disabled' => true,
                                                        ));
                                                    } elseif ($hasDraftTest) {
                                                        lms_component('button', array(
                                                            'type' => 'button',
                                                            'label' => 'Chờ duyệt test',
                                                            'variant' => 'light',
                                                            'size' => 'sm',
                                                            'class' => 'w-100 lms-btn-disabled',
                                                            'disabled' => true,
                                                        ));
                                                    } else {
                                                        ?>
                                                        <div class="d-flex gap-2">
                                                            <?php lms_component('button', array(
                                                                'type' => 'button',
                                                                'label' => 'Gửi test',
                                                                'variant' => 'primary',
                                                                'size' => 'sm',
                                                                'class' => 'flex-fill lms-send-test',
                                                                'attrs' => array(
                                                                    'data-topic-id' => $topicId,
                                                                    'data-topic-name' => (string) $topic['name'],
                                                                ),
                                                            )); ?>
                                                            <?php lms_component('button', array(
                                                                'type' => 'button',
                                                                'label' => 'Thêm buổi',
                                                                'variant' => 'primary',
                                                                'size' => 'sm',
                                                                'class' => 'flex-fill lms-add-session',
                                                                'attrs' => array(
                                                                    'data-topic-id' => $topicId,
                                                                    'data-topic-name' => (string) $topic['name'],
                                                                ),
                                                            )); ?>
                                                        </div>
                                                        <?php
                                                    }
                                                else:
                                                    lms_component('button', array(
                                                        'type' => 'button',
                                                        'label' => 'Thêm buổi',
                                                        'variant' => 'primary',
                                                        'size' => 'sm',
                                                        'class' => 'w-100 lms-add-session',
                                                        'attrs' => array(
                                                            'data-topic-id' => $topicId,
                                                            'data-topic-name' => (string) $topic['name'],
                                                        ),
                                                    ));
                                                endif; ?>
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
    <div class="lms-section-header" data-bs-toggle="collapse" data-bs-target="#lmsDetailSessionsCollapse" aria-expanded="true">
        <h4 class="lms-section-title mb-0">Lesson Summary &amp; BTVN</h4>
        <i class="fa-solid fa-chevron-up lms-toggle-arrow"></i>
    </div>

    <div id="lmsDetailSessionsCollapse" class="collapse show mt-3">
        <?php if (empty($visibleSessions)): ?>
            <div class="text-muted p-3 bg-white rounded-3">Chưa có buổi học trong khóa.</div>
        <?php else: ?>
            <?php
            $registeredCount = 0;
            foreach ($allSessions as $countSession) {
                if ((string) $countSession['status'] !== 'cancelled') {
                    $registeredCount++;
                }
            }
            ?>
            <div class="lms-panel">
                <div class="lms-panel-head is-static">
                    <div class="lms-panel-head-main">
                        <i class="fa-regular fa-calendar-days"></i>
                        <span class="lms-panel-title">Buổi học theo lịch</span>
                    </div>
                    <span class="lms-badge lms-badge-secondary"><?= (int) $registeredCount ?> buổi đã đăng ký</span>
                </div>

                <div class="lms-panel-body">
                    <div class="row g-3">
                        <?php foreach ($visibleSessions as $session): ?>
                            <?php
                            $sessionDate = (string) $session['session_date'];
                            $ts = strtotime($sessionDate);
                            $dow = (int) date('N', $ts) + 1;
                            $dayLabel = isset($weekdays[$dow]) ? $weekdays[$dow] : 'Buổi học';
                            $hasSummary = trim((string) $session['lesson_summary']) !== '';
                            $hasHw = trim((string) $session['hw_content']) !== '';
                            $isFuture = strcmp($sessionDate, $today) > 0;
                            $totalStudents = (int) $session['student_count'];
                            $gradedCount = (int) $session['graded_count'];
                            $isComplete = $hasHw && $totalStudents > 0 && $gradedCount >= $totalStudents;
                            $attachments = \Lms\UserService::decodeJsonArray(
                                isset($session['hw_attached_files']) ? $session['hw_attached_files'] : array()
                            );
                            $sessionTopicIds = \Lms\UserService::decodeJsonArray(
                                isset($session['topic_ids']) ? $session['topic_ids'] : array()
                            );
                            if (empty($sessionTopicIds) && !empty($session['topic_id'])) {
                                $sessionTopicIds = array((int) $session['topic_id']);
                            }
                            $sessionTopicNames = array();
                            foreach ($sessionTopicIds as $sessionTopicId) {
                                $sessionTopicId = (int) $sessionTopicId;
                                $sessionTopicNames[] = isset($topicNameMap[$sessionTopicId])
                                    ? $topicNameMap[$sessionTopicId]
                                    : ('#' . $sessionTopicId);
                            }

                            if ($isFuture && !$hasSummary && !$hasHw) {
                                $stateLabel = 'Chưa diễn ra';
                                $stateClass = 'text-danger fst-italic';
                                $linkLabel = '';
                            } elseif ($isComplete) {
                                $stateLabel = 'Đã hoàn thành';
                                $stateClass = 'text-success';
                                $linkLabel = 'Xem chi tiết';
                            } elseif ($hasHw) {
                                $stateLabel = 'Chưa chấm BTVN';
                                $stateClass = 'text-danger';
                                $linkLabel = 'Chấm BTVN';
                            } elseif ($hasSummary) {
                                $stateLabel = 'Chưa giao BTVN';
                                $stateClass = 'text-danger';
                                $linkLabel = 'Cập nhật';
                            } else {
                                $stateLabel = 'Chưa cập nhật';
                                $stateClass = 'text-muted';
                                $linkLabel = 'Nhập lesson summary';
                            }
                            $sessionUrl = lms_url('lms-lich-day', 'buoi', array('id' => (int) $session['id']));
                            ?>
                            <div class="col-md-6">
                                <div class="lms-session-card-v2">
                                    <div class="lms-session-card-header d-flex align-items-center justify-content-between">
                                        <div class="fw-bold">
                                            <i class="fa-regular fa-calendar-days me-2"></i>Buổi học <?= e($dayLabel) ?> <?= e(date('d/m', $ts)) ?>
                                        </div>
                                    </div>

                                    <div class="lms-session-card-body mt-2">
                                        <?php if ($isFuture && !$hasSummary && !$hasHw): ?>
                                            <div class="lms-session-future-notice text-danger fst-italic py-4 text-center">
                                                Chưa diễn ra
                                            </div>
                                        <?php else: ?>
                                            <div class="lms-session-pill">
                                                <i class="fa-regular fa-file-lines me-2 text-muted"></i>
                                                <div>
                                                    <div class="lms-pill-label">Chọn các chuyên đề *</div>
                                                    <div class="lms-pill-value">
                                                        <?= $sessionTopicNames !== array() ? e(implode(', ', $sessionTopicNames)) : 'Chưa chọn chuyên đề' ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="lms-session-pill">
                                                <i class="fa-regular fa-comment-dots me-2 text-muted"></i>
                                                <div>
                                                    <div class="lms-pill-label">Nhận xét buổi học:</div>
                                                    <div class="lms-pill-value">
                                                        <?= $hasSummary ? nl2br(e((string) $session['lesson_summary'])) : 'Chưa có nhận xét' ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="lms-session-pill">
                                                <i class="fa-regular fa-file-lines me-2 text-muted"></i>
                                                <div>
                                                    <div class="lms-pill-label">BTVN:</div>
                                                    <div class="lms-pill-value">
                                                        <?= $hasHw ? nl2br(e((string) $session['hw_content'])) : 'Chưa có BTVN' ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="lms-session-pill">
                                                <i class="fa-regular fa-clock me-2 text-muted"></i>
                                                <div>
                                                    <div class="lms-pill-label">Hạn nộp:</div>
                                                    <div class="lms-pill-value">
                                                        <?= !empty($session['hw_deadline']) ? e(date('H:i d/m/Y', strtotime((string) $session['hw_deadline']))) : 'Chưa có hạn nộp' ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="lms-session-pill">
                                                <i class="fa-solid fa-paperclip me-2 text-muted"></i>
                                                <div>
                                                    <div class="lms-pill-label">Tệp đính kèm:</div>
                                                    <div class="lms-pill-value">
                                                        <?php if (!empty($attachments)): ?>
                                                            <?php foreach ($attachments as $file): ?>
                                                                <a href="<?= e(lms_upload_url((string) $file)) ?>" target="_blank" rel="noopener" class="d-inline-block me-2">
                                                                    <i class="fa-solid fa-download me-1"></i><?= e(basename((string) $file)) ?>
                                                                </a>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Trống</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-3">
                                                <?php if ($linkLabel !== ''): ?>
                                                    <?php lms_component('button', array(
                                                        'href' => $sessionUrl,
                                                        'label' => $linkLabel,
                                                        'variant' => 'primary',
                                                        'size' => 'sm',
                                                        'class' => 'w-100',
                                                    )); ?>
                                                <?php endif; ?>
                                                <div class="text-center mt-2 small">
                                                    <span class="<?= e($stateClass) ?> fw-medium"><?= e($stateLabel) ?></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Request test -->
<div class="modal fade" id="lmsSendTestModal" tabindex="-1" aria-labelledby="lmsSendTestLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= e(lms_url('lms-hoc-vien-cua-toi', 'gui-test')) ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="lmsSendTestLabel">Request test</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="class_id" value="<?= e((string) $detailClass['id']) ?>">
                    <input type="hidden" name="student_id" value="<?= e((string) $detailStudent['user_id']) ?>">
                    <input type="hidden" name="cycle_id" value="<?= e((string) $detail['cycle']['id']) ?>">
                    <input type="hidden" name="topic_id" id="lmsSendTestTopicId" value="">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Điểm Review<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="review_score" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deadline<span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <input type="date" class="form-control" name="deadline_date" required
                                       min="<?= e(date('Y-m-d')) ?>"
                                       value="<?= e(date('Y-m-d', strtotime('+2 days'))) ?>">
                            </div>
                            <div class="col-sm-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fa-regular fa-clock"></i></span>
                                    <input type="text" class="form-control" name="deadline_time" required
                                           value="<?= e($defaultSlotLabel) ?>"
                                           placeholder="19:00 - 20:30">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Lesson summary chuyên đề sau<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="lesson_summary_url" required
                               placeholder="Gắn link doc vào đây">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <?php lms_component('button', array(
                        'type' => 'submit',
                        'label' => 'Gửi',
                        'variant' => 'primary',
                        'size' => 'md',
                        'class' => 'px-4',
                    )); ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Request thêm buổi học -->
<div class="modal fade" id="lmsAddSessionModal" tabindex="-1" aria-labelledby="lmsAddSessionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= e(lms_url('lms-hoc-vien-cua-toi', 'them-buoi')) ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="lmsAddSessionLabel">Request thêm buổi học</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="class_id" value="<?= e((string) $detailClass['id']) ?>">
                    <input type="hidden" name="student_id" value="<?= e((string) $detailStudent['user_id']) ?>">
                    <input type="hidden" name="cycle_id" value="<?= e((string) $detail['cycle']['id']) ?>">
                    <input type="hidden" name="topic_id" id="lmsAddSessionTopicId" value="">
                    <input type="hidden" name="topic_name" id="lmsAddSessionTopicName" value="">
                    <div class="mb-3">
                        Chuyên đề: <span id="lmsAddSessionTopicText"></span>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Lý do <span class="text-danger">*</span>:</label>
                        <textarea class="form-control" name="note" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <?php lms_component('button', array(
                        'type' => 'submit',
                        'label' => 'Gửi',
                        'variant' => 'primary',
                        'size' => 'md',
                        'class' => 'px-4',
                    )); ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.bootstrap) {
            return;
        }
        var sendTestModalEl = document.getElementById('lmsSendTestModal');
        if (sendTestModalEl) {
            var sendTopicInput = document.getElementById('lmsSendTestTopicId');
            var sendTitleEl = document.getElementById('lmsSendTestLabel');
            var sendTestModal = bootstrap.Modal.getOrCreateInstance(sendTestModalEl);
            document.querySelectorAll('.lms-send-test').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var topicId = btn.getAttribute('data-topic-id') || '';
                    var topicName = btn.getAttribute('data-topic-name') || '';
                    if (sendTopicInput) sendTopicInput.value = topicId;
                    if (sendTitleEl) {
                        sendTitleEl.textContent = topicName !== '' ? ('Request test - ' + topicName) : 'Request test';
                    }
                    sendTestModal.show();
                });
            });
        }

        var addSessionModalEl = document.getElementById('lmsAddSessionModal');
        if (addSessionModalEl) {
            var addTopicIdInput = document.getElementById('lmsAddSessionTopicId');
            var addTopicNameInput = document.getElementById('lmsAddSessionTopicName');
            var addTopicTextEl = document.getElementById('lmsAddSessionTopicText');
            var addSessionModal = bootstrap.Modal.getOrCreateInstance(addSessionModalEl);
            document.querySelectorAll('.lms-add-session').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var topicId = btn.getAttribute('data-topic-id') || '';
                    var topicName = btn.getAttribute('data-topic-name') || '';
                    if (addTopicIdInput) addTopicIdInput.value = topicId;
                    if (addTopicNameInput) addTopicNameInput.value = topicName;
                    if (addTopicTextEl) addTopicTextEl.textContent = topicName;
                    addSessionModal.show();
                });
            });
        }
    });
})();
</script>
