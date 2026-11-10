<?php
/** Role-scoped global LMS search (SYS-02). */
if (!defined('_LMS_PORTAL')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/lms_layout.php';
require_once __DIR__ . '/lms_admin_forms.php';
lms_session_ensure();

$d = lms_db();
$user = lms_current_user();
if (!is_array($user) || empty($user['id'])) {
    lms_flash('error', 'Vui lòng đăng nhập để tiếp tục.');
    header('Location: ' . lms_url('', 'login'));
    exit;
}
if (empty($user['role']) || $user['role'] !== _LMS_PORTAL) {
    $portal = !empty($user['role']) ? (string) $user['role'] : 'admin';
    header('Location: ' . lms_url_to($portal, '', 'login'));
    exit;
}
if (!empty($user['must_change_password'])) {
    header('Location: ' . lms_url('', 'doi-mat-khau'));
    exit;
}

$query = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$results = array();
$limit = 50;
$like = '%' . $query . '%';
$userId = (int) $user['id'];

if ($query !== '' && mb_strlen($query, 'UTF-8') >= 2) {
    if (_LMS_PORTAL === 'admin') {
        $results = $d->safeFetch(
            'select * from ('
            . ' select \'Học viên\' as result_type, s.full_name as title, s.student_code as subtitle,'
            . ' concat(\'/lms/admin/lms-tai-khoan/sua-hoc-vien?id=\', s.user_id) as url'
            . ' from db_lms_students s where s.full_name like ? or s.student_code like ?'
            . ' union all'
            . ' select \'Giáo viên\', t.full_name, t.teacher_code,'
            . ' concat(\'/lms/admin/lms-tai-khoan/sua-giao-vien?id=\', t.user_id)'
            . ' from db_lms_teachers t where t.full_name like ? or t.teacher_code like ?'
            . ' union all'
            . ' select \'Lớp học\', c.name, c.class_code,'
            . ' concat(\'/lms/admin/lms-lop/sua?id=\', c.id)'
            . ' from db_lms_classes c where c.deleted_at is null and (c.name like ? or c.class_code like ?)' 
            . ') search_results limit ' . $limit,
            array($like, $like, $like, $like, $like, $like)
        );
    } elseif (_LMS_PORTAL === 'teacher') {
        $results = $d->safeFetch(
            'select * from ('
            . ' select distinct \'Học viên\' as result_type, s.full_name as title, s.student_code as subtitle,'
            . ' \'/lms/teacher/lms-hoc-vien-cua-toi\' as url'
            . ' from db_lms_class_students cs'
            . ' join db_lms_classes c on c.id = cs.class_id and c.deleted_at is null'
            . ' join db_lms_students s on s.user_id = cs.student_id'
            . ' where c.teacher_id = ? and cs.status = \'active\' and (s.full_name like ? or s.student_code like ?)'
            . ' union all'
            . ' select \'Lớp học\', c.name, c.class_code,'
            . ' \'/lms/teacher/lms-lich-day\''
            . ' from db_lms_classes c where c.teacher_id = ? and c.deleted_at is null'
            . ' and (c.name like ? or c.class_code like ?)'
            . ') search_results limit ' . $limit,
            array($userId, $like, $like, $userId, $like, $like)
        );
    } else {
        $results = $d->safeFetch(
            'select * from ('
            . ' select distinct \'Lớp học\' as result_type, c.name as title, c.class_code as subtitle,'
            . ' concat(\'/lms/student/lms-khoa-hoc/lop?class_id=\', c.id) as url'
            . ' from db_lms_class_students cs'
            . ' join db_lms_classes c on c.id = cs.class_id and c.deleted_at is null'
            . ' where cs.student_id = ? and cs.status = \'active\' and c.status = \'active\''
            . ' and (c.name like ? or c.class_code like ?)'
            . ' union all'
            . ' select distinct \'Chuyên đề\', t.name, subj.name,'
            . ' concat(\'/lms/student/lms-khoa-hoc/lop?class_id=\', c.id)'
            . ' from db_lms_class_students cs'
            . ' join db_lms_classes c on c.id = cs.class_id and c.deleted_at is null'
            . ' join db_lms_class_topics ct on ct.class_id = c.id'
            . ' join db_lms_topics t on t.id = ct.topic_id'
            . ' join db_lms_subjects subj on subj.id = t.subject_id'
            . ' where cs.student_id = ? and cs.status = \'active\' and c.status = \'active\' and t.name like ?'
            . ') search_results limit ' . $limit,
            array($userId, $like, $like, $userId, $like)
        );
    }
}

// URL origins in SQL are path-relative to the conventional /lms deployment;
// adapt them to the actual project subdirectory (e.g. /source/lms).
$baseUrl = lms_base_url();
foreach ($results as $i => $result) {
    $results[$i]['url'] = str_replace('/lms/', $baseUrl . '/', (string) $result['url']);
}

$title = 'Kết quả tìm kiếm';
lms_layout_open(array('portal' => _LMS_PORTAL, 'title' => $title, 'active' => ''));
?>
<div class="lms-card">
    <div class="lms-card-header"><i class="fa-solid fa-magnifying-glass me-1"></i> Kết quả tìm kiếm</div>
    <div class="lms-card-body">
        <?php if ($query === ''): ?>
            <p class="text-muted mb-0">Nhập ít nhất 2 ký tự vào ô tìm kiếm phía trên.</p>
        <?php elseif (mb_strlen($query, 'UTF-8') < 2): ?>
            <p class="text-muted mb-0">Từ khoá tìm kiếm phải có ít nhất 2 ký tự.</p>
        <?php elseif (empty($results)): ?>
            <p class="text-muted mb-0">Không tìm thấy kết quả phù hợp với “<?= e($query) ?>”.</p>
        <?php else: ?>
            <p class="text-muted">Tìm thấy <?= e((string) count($results)) ?> kết quả cho “<?= e($query) ?>”.</p>
            <div class="lms-search-results">
                <?php foreach ($results as $result): ?>
                    <a class="lms-search-result" href="<?= e((string) $result['url']) ?>">
                        <span class="lms-search-result-icon"><i class="fa-solid fa-arrow-turn-down"></i></span>
                        <span class="lms-search-result-text">
                            <strong><?= e((string) $result['title']) ?></strong>
                            <small class="text-muted d-block"><?= e((string) $result['result_type']) ?> · <?= e((string) $result['subtitle']) ?></small>
                        </span>
                        <i class="fa-solid fa-arrow-right ms-auto text-muted"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php lms_layout_close(); ?>
