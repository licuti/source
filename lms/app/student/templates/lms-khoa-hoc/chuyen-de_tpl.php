<?php
/**
 * Student — Chi tiết chuyên đề: Nộp test/minitest + BTVN các buổi học.
 * Tham khảo thiết kế: Tai-lieu-phat-trien/student-ver2/Trang Chi tiết Nộp test.png
 *
 * Data: $class, $topic, $sessions, $tests, $alias.
 * PHP 7.3 compatible.
 */
?>
<?php $csrf = lms_csrf_token(); ?>

<div class="lms-topic-detail-view">
    <!-- Header: Tên khóa học + Link Xem tất cả -->
    <div class="lms-page-head">
        <h1 class="lms-page-title mb-0">
            <a href="<?= e(lms_url($alias, 'lop', array('class_id' => (int) $class['id']))) ?>">
                <?= e((string) $class['name']) ?>
            </a>
        </h1>
        <a href="<?= e(lms_url($alias, 'lop', array('class_id' => (int) $class['id']))) ?>" class="lms-view-all-link">
            Xem tất cả <i class="fa-solid fa-chevron-right ms-1"></i>
        </a>
    </div>

    <!-- Breadcrumb danh mục -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">Lộ trình</li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= !empty($topic['subject_name']) ? e($topic['subject_name']) : 'Chuyên đề' ?>
            </li>
        </ol>
    </nav>

    <!-- ===================== Card 1: Thông tin chuyên đề & Mục tiêu then chốt ===================== -->
    <div class="lms-panel mb-4">
        <div class="lms-panel-body">
            <h4 class="lms-topic-headline fw-bold mb-1"><?= e((string) $topic['name']) ?></h4>
            <div class="lms-topic-sessions-count text-danger fw-semibold mb-3">
                <?php if ($topic['max_sessions'] === null): ?>
                    Đã dạy: <?= e((string) number_format((int) $topic['done_sessions'])) ?> buổi
                <?php else: ?>
                    Đã dạy: <?= e((string) number_format((int) $topic['done_sessions'])) ?> / <?= e((string) number_format((int) $topic['max_sessions'])) ?> buổi
                <?php endif; ?>
            </div>

            <div class="lms-topic-objectives-section">
                <div class="fw-bold mb-2">Mục tiêu then chốt</div>
                <div class="lms-objectives-box p-3">
                    <?php if (!empty($topic['content'])): ?>
                        <div class="lms-objectives-content"><?= nl2br(e((string) $topic['content'])) ?></div>
                    <?php else: ?>
                        <span class="text-muted fst-italic">Nội dung có sẵn của chuyên đề.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tách bài test & minitest từ danh sách $tests -->
    <?php
    $testItem = null;
    $minitestItem = null;
    foreach ($tests as $tRow) {
        if ((string) $tRow['test_type'] === 'minitest' && $minitestItem === null) {
            $minitestItem = $tRow;
        } elseif ((string) $tRow['test_type'] === 'test' && $testItem === null) {
            $testItem = $tRow;
        }
    }
    if ($testItem === null) {
        $testItem = array(
            'test_type' => 'test',
            'status' => 'draft',
            'test_file_url' => isset($topic['test_file_url']) ? $topic['test_file_url'] : '',
        );
    }
    if ($minitestItem === null) {
        $minitestItem = array(
            'test_type' => 'minitest',
            'status' => 'draft',
            'test_file_url' => isset($topic['minitest_file_url']) ? $topic['minitest_file_url'] : '',
        );
    }
    ?>

    <!-- ===================== Card 2: Accordion Bài test chuyên đề ===================== -->
    <?php
    $t = $testItem;
    $type = 'test';
    include __DIR__ . '/_test_section_tpl.php';
    ?>

    <!-- ===================== Card 3: Accordion Minitest chuyên đề ===================== -->
    <?php
    $t = $minitestItem;
    $type = 'minitest';
    include __DIR__ . '/_test_section_tpl.php';
    ?>
</div>
