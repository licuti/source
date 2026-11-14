<?php
/**
 * Admin LMS — Quản lý lớp học (ADM-03, Phase 1.2).
 *
 * Action:
 *   - 'man'      (GET)  → danh sách + filter + phân trang.
 *   - 'them'     (GET/POST) → form tạo lớp (POST: CSRF + validate + tạo).
 *   - 'sua'      (GET/POST) → form sửa (POST: CSRF + validate + cập nhật + audit).
 *   - 'lich-hoc' (GET)  → xem danh sách buổi học của lớp.
 *   - 'chinh-so-buoi' (POST) → điều chỉnh số buổi khoá (modal mockup).
 *   - 'tam-dung' / 'mo' (GET) → tạm dừng / tiếp tục lớp.
 *   - 'xoa'      (GET)  → soft-delete lớp + xoá schedules/enrolment/sessions upcoming.
 *
 * Router đã check auth gate + quyền view; mỗi action dưới check quyền edit/delete
 * trước khi xử lý (Huong-Dan-Phat-Trien-Page.md §3.4 + checklist §4).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d         = lms_db();
$user      = lms_current_user();
$alias     = 'lms-lop';
$classSvc  = new \Lms\ClassService($d);

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'them':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_lop_form($classSvc, false);
        break;

    case 'sua':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_lop_form($classSvc, true);
        break;

    case 'lich-hoc':
        lms_form_guard($d, $user, $alias, 'view');
        lms_lop_schedule($classSvc);
        break;

    case 'cap-nhat-lich-co-dinh':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_lop_update_fixed_schedule($classSvc);
        break;

    case 'them-buoi-hoc':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_lop_add_session($classSvc);
        break;

    case 'xoa-buoi-hoc':
        lms_form_guard($d, $user, $alias, 'delete');
        lms_lop_delete_session();
        break;

    case 'danh-dau-nghi':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_lop_mark_absent();
        break;

    case 'buoi':
        lms_form_guard($d, $user, $alias, 'view');
        lms_lop_admin_session($d, $alias);
        break;

    case 'cham-btvn':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_lop_admin_grade($d, $alias);
        break;

    case 'cap-nhat-buoi':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_lop_admin_update_session($d, $alias);
        break;

    case 'chinh-so-buoi':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_lop_adjust_sessions($classSvc);
        break;

    case 'them-khoa-hoc':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_lop_add_course_cycle($d);
        break;

    case 'ajax-danh-sach-lop':
        lms_form_guard($d, $user, $alias, 'view');
        lms_lop_ajax_list_classes($d);
        break;

    case 'dieu-chinh-xoa-buoi':
        lms_form_guard($d, $user, $alias, 'delete');
        lms_lop_bulk_delete_sessions($d, $alias);
        break;

    case 'dieu-chinh-them-buoi':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_lop_bulk_generate_sessions($d, $alias);
        break;

    case 'tam-dung':
    case 'mo':
    case 'kich-hoat':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_lop_toggle_status($classSvc, $action);
        break;

    case 'xoa':
        lms_form_guard($d, $user, $alias, 'delete');
        lms_lop_delete($classSvc);
        break;

    default:
        lms_lop_list($classSvc);
        break;
}

function lms_lop_admin_session(LmsDb $d, $alias)
{
    global $title, $template, $detail, $hidePageTitle;
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $detail = (new \Lms\AdminReportService($d))->sessionDetail($id);
    if (empty($detail)) {
        lms_flash('error', 'Buổi học không tồn tại.');
        header('Location: ' . lms_url($alias));
        exit;
    }
    $title = 'Chi tiết buổi học';
    $template = 'lms-lop/buoi';
    $hidePageTitle = true;
}

function lms_lop_admin_grade(LmsDb $d, $alias)
{
    $sessionId = isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0;
    $ssId = isset($_POST['session_student_id']) ? (int) $_POST['session_student_id'] : 0;
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ.');
    } else {
        $svc = new \Lms\AdminReportService($d);
        $reason = isset($_POST['reason']) && trim((string) $_POST['reason']) !== ''
            ? trim((string) $_POST['reason'])
            : 'Admin duyệt bài tập về nhà';
        $ok = $svc->adminGradeHomework($ssId, $_POST, isset($_FILES['teacher_feedback_files']) ? $_FILES['teacher_feedback_files'] : null, $reason);
        lms_flash($ok ? 'success' : 'error', $ok ? 'Đã duyệt và lưu nhận xét bài tập về nhà.' : (string) reset($svc->errors()));
    }
    header('Location: ' . lms_url($alias, 'buoi', array('id' => $sessionId)));
    exit;
}

function lms_lop_admin_update_session(LmsDb $d, $alias)
{
    $sessionId = isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0;
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ.');
    } else {
        $svc = new \Lms\AdminReportService($d);
        $ok = $svc->adminUpdateSession($sessionId, $_POST, isset($_FILES['hw_files']) ? $_FILES['hw_files'] : null);
        lms_flash($ok ? 'success' : 'error', $ok ? 'Đã cập nhật nội dung buổi học.' : (string) reset($svc->errors()));
    }
    header('Location: ' . lms_url($alias, 'buoi', array('id' => $sessionId)));
    exit;
}

// ===================== Data cho form =====================

/**
 * Danh sách chương trình cho dropdown.
 *
 * @param LmsDb $d
 * @return array
 */
function lms_lop_programs(LmsDb $d)
{
    return $d->safeFetch('select id, name from db_lms_programs order by name');
}

/**
 * Danh sách môn học kèm program_id (option có data-program-id để JS lọc).
 *
 * @param LmsDb $d
 * @return array
 */
function lms_lop_subjects(LmsDb $d)
{
    return $d->safeFetch(
        'select id, program_id, name from db_lms_subjects order by name, id'
    );
}

/**
 * Giáo viên active kèm số lớp đang dạy / max_classes + % phí mặc định.
 *
 * @param LmsDb $d
 * @return array
 */
function lms_lop_teachers(LmsDb $d)
{
    return $d->safeFetch(
        'select t.user_id, t.teacher_code, t.full_name, t.service_fee_percentage,'
        . ' t.max_classes,'
        . ' (select count(*) from db_lms_classes c'
        . '   where c.teacher_id = t.user_id and c.status = "active" and c.deleted_at is null) as active_classes'
        . ' from db_lms_teachers t'
        . ' where t.status = ?'
        . ' order by t.teacher_code',
        array('active')
    );
}

/**
 * Học viên có thể enrol (chưa nghỉ học).
 *
 * @param LmsDb $d
 * @return array
 */
function lms_lop_students(LmsDb $d)
{
    return $d->safeFetch(
        'select s.user_id, s.student_code, s.full_name from db_lms_students s'
        . ' where s.status <> ?'
        . ' order by s.student_code',
        array('quit')
    );
}

/**
 * Danh sách chuyên đề theo môn (cho multi-select form).
 *
 * @param LmsDb $d
 * @return array
 */
function lms_lop_topics(LmsDb $d)
{
    return $d->safeFetch(
        'select t.id, t.subject_id, t.name, t.has_test, t.has_minitest, s.name as subject_name'
        . ' from db_lms_topics t join db_lms_subjects s on s.id = t.subject_id'
        . ' order by s.name, t.order_index, t.name'
    );
}

// ===================== Xem lịch học của lớp =====================

function lms_lop_schedule(\Lms\ClassService $classSvc)
{
    global $title, $template, $row, $students, $studentText, $topics, $schedules,
           $weekMonday, $prevWeek, $nextWeek, $currWeek, $weekSunday,
           $weekMonth, $weekYear, $daysOfWeek, $calRows, $weekSessions,
           $weekdays, $alias, $canEdit, $canDelete;

    $d    = lms_db();
    $user = lms_current_user();
    $canEdit   = lms_check_permission($d, $user, $alias, 'edit', 'admin');
    $canDelete = lms_check_permission($d, $user, $alias, 'delete', 'admin');

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID lớp học.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $row = $classSvc->getClass($id);
    if (empty($row)) {
        lms_flash('error', 'Lớp học không tồn tại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    // Danh sách học viên active của lớp
    $students = $d->safeFetch(
        'select s.user_id, s.student_code, s.full_name'
        . ' from db_lms_class_students cs'
        . ' join db_lms_students s on s.user_id = cs.student_id'
        . ' where cs.class_id = ? and cs.status = ?'
        . ' order by s.full_name',
        array($id, 'active')
    );
    $stList = array();
    foreach ($students as $st) {
        $stList[] = ($st['student_code'] ? $st['student_code'] . ' - ' : '') . $st['full_name'];
    }
    $studentText = !empty($stList) ? implode(', ', $stList) : 'Chưa có học viên';

    // Danh sách chuyên đề theo lộ trình lớp (cycle snapshot → class topics → legacy subject)
    $activeCycleRow = $d->safeFetchOne(
        'select id from db_lms_course_cycles where class_id = ? and status = ? order by id desc limit 1',
        array($id, 'active')
    );
    $topics = array();
    $hasActiveCycle = !empty($activeCycleRow['id']);
    if ($hasActiveCycle) {
        $topics = $d->safeFetch(
            'select t.id, t.name from db_lms_cycle_topics ct'
            . ' join db_lms_topics t on t.id = ct.topic_id'
            . ' where ct.cycle_id = ? order by ct.order_index, t.id',
            array((int) $activeCycleRow['id'])
        );
    } else {
        $topics = $d->safeFetch(
            'select t.id, t.name from db_lms_class_topics ct'
            . ' join db_lms_topics t on t.id = ct.topic_id'
            . ' where ct.class_id = ? order by ct.order_index, t.id',
            array($id)
        );
        if (empty($topics)) {
            // Lớp cũ chưa có cycle/gán chuyên đề: giữ fallback theo môn học.
            $topics = $d->safeFetch(
                'select id, name from db_lms_topics where subject_id = ? order by order_index, id',
                array((int) $row['subject_id'])
            );
        }
    }

    // Lịch cố định (ưu tiên lịch khóa học đang active nếu có, fallback db_lms_schedules)
    $schedules = array();
    if ($hasActiveCycle) {
        $schedules = $d->safeFetch(
            'select day_of_week, start_time, end_time, effective_from from db_lms_cycle_schedules where cycle_id = ? order by day_of_week, start_time',
            array((int) $activeCycleRow['id'])
        );
    }
    if (empty($schedules)) {
        $schedules = $d->safeFetch(
            'select day_of_week, start_time, end_time, effective_from from db_lms_schedules where class_id = ? order by day_of_week, start_time',
            array($id)
        );
    }

    // Tính toán tuần xem
    $weekParam = isset($_GET['week']) ? trim((string)$_GET['week']) : '';
    $weekTs = strtotime($weekParam);
    if ($weekTs === false) {
        $weekMonday = date('Y-m-d', strtotime('monday this week'));
    } else {
        $weekMonday = date('Y-m-d', strtotime('monday this week', $weekTs));
    }
    $currWeek   = date('Y-m-d', strtotime('monday this week'));
    $prevWeek   = date('Y-m-d', strtotime('-7 days', strtotime($weekMonday)));
    $nextWeek   = date('Y-m-d', strtotime('+7 days', strtotime($weekMonday)));
    $weekSunday = date('Y-m-d', strtotime('+6 days', strtotime($weekMonday)));
    $weekMonth  = (int) date('n', strtotime($weekMonday));
    $weekYear   = (int) date('Y', strtotime($weekMonday));

    $weekdays = \Lms\UserService::WEEKDAYS;

    // 7 ngày trong tuần
    $daysOfWeek = array();
    for ($i = 0; $i < 7; $i++) {
        $ts = strtotime('+' . $i . ' days', strtotime($weekMonday));
        $dow = (int) date('N', $ts) + 1; // 2..8
        $dateStr = date('Y-m-d', $ts);
        $daysOfWeek[] = array(
            'date'     => $dateStr,
            'day'      => (int) date('j', $ts),
            'month'    => (int) date('n', $ts),
            'dow'      => $dow,
            'label'    => isset($weekdays[$dow]) ? $weekdays[$dow] : ('Thứ ' . $dow),
            'is_today' => ($dateStr === date('Y-m-d')),
        );
    }

    // Sessions trong tuần đang xem
    $weekSessions = $d->safeFetch(
        'select s.id, s.session_date, s.start_time, s.end_time, s.status,'
        . ' s.price_per_session, s.teacher_percentage,'
        . ' s.lesson_summary, s.summary_submitted_at, s.is_summary_late, s.hw_content,'
        . ' s.topic_id, t.name as topic_name'
        . ' from db_lms_sessions s'
        . ' left join db_lms_topics t on t.id = s.topic_id'
        . ' where s.class_id = ? and s.session_date between ? and ?'
        . ' order by s.session_date, s.start_time',
        array($id, $weekMonday, $weekSunday)
    );

    // Gom sessions theo ngày
    $sessionsByDate = array();
    foreach ($weekSessions as $s) {
        $date = (string) $s['session_date'];
        if (!isset($sessionsByDate[$date])) {
            $sessionsByDate[$date] = array();
        }
        $sessionsByDate[$date][] = $s;
    }

    // Xây dựng ma trận hàng giờ (1h đến 24h)
    $calRows = array();
    for ($h = 1; $h <= 24; $h++) {
        $cells = array();
        foreach ($daysOfWeek as $dayInfo) {
            $date = $dayInfo['date'];
            $matchingSessions = array();
            if (isset($sessionsByDate[$date])) {
                foreach ($sessionsByDate[$date] as $s) {
                    $startH = (int) substr((string) $s['start_time'], 0, 2);
                    if ($startH === $h) {
                        $matchingSessions[] = $s;
                    }
                }
            }
            $cells[] = array(
                'date'     => $date,
                'dow'      => $dayInfo['dow'],
                'sessions' => $matchingSessions,
            );
        }
        $calRows[] = array(
            'hour'  => $h,
            'label' => sprintf('%02d:00', $h),
            'cells' => $cells,
        );
    }

    $title    = 'Lịch học';
    $template = 'lms-lop/lich-hoc';
}

/**
 * Cập nhật lịch cố định và áp dụng từ ngày được chọn.
 */
function lms_lop_update_fixed_schedule(\Lms\ClassService $classSvc)
{
    global $alias;
    $d = lms_db();
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $week = isset($_POST['week']) ? (string) $_POST['week'] : '';
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID lớp học.');
        header('Location: ' . lms_url($alias));
        exit;
    }
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc hết hạn, vui lòng thử lại.');
        header('Location: ' . lms_url($alias, 'lich-hoc', array('id' => $id, 'week' => $week)));
        exit;
    }

    $class = $classSvc->getClass($id);
    if (empty($class)) {
        lms_flash('error', 'Lớp học không tồn tại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $days   = isset($_POST['sch_day']) ? (array) $_POST['sch_day'] : array();
    $starts = isset($_POST['sch_start']) ? (array) $_POST['sch_start'] : array();
    $ends   = isset($_POST['sch_end']) ? (array) $_POST['sch_end'] : array();
    $effectiveFrom = isset($_POST['effective_from']) && (string) $_POST['effective_from'] !== ''
        ? trim((string) $_POST['effective_from']) : date('Y-m-d');

    $schedules = array();
    foreach ($days as $k => $day) {
        $day   = (int) $day;
        $start = isset($starts[$k]) ? trim((string) $starts[$k]) : '';
        $end   = isset($ends[$k]) ? trim((string) $ends[$k]) : '';
        if ($day >= 2 && $day <= 8 && $start !== '' && $end !== '') {
            $schedules[] = array('day' => $day, 'start' => $start, 'end' => $end);
        }
    }

    if (empty($schedules)) {
        lms_flash('error', 'Vui lòng cấu hình ít nhất 1 buổi học trong lịch cố định.');
        header('Location: ' . lms_url($alias, 'lich-hoc', array('id' => $id, 'week' => $week)));
        exit;
    }

    $schedSvc = new \Lms\ScheduleService($d);
    $cycleSvc = new \Lms\CourseCycleService($d);
    $activeCycleId = $cycleSvc->resolveActiveCycleId($id);

    $d->beginTransaction();
    try {
        $d->safeExec('delete from db_lms_schedules where class_id = ?', array($id));
        foreach ($schedules as $sch) {
            $sid = $schedSvc->addSchedule($id, $sch['day'], $sch['start'], $sch['end'], $effectiveFrom);
            if ($sid === null) {
                $err = $schedSvc->errors();
                throw new \Exception(isset($err['schedule']) ? $err['schedule'] : 'Lịch học bị trùng hoặc không hợp lệ.');
            }
        }

        // Đồng bộ lịch cố định sang khóa học (cycle) đang active
        if ($activeCycleId > 0) {
            $d->safeExec('delete from db_lms_cycle_schedules where cycle_id = ?', array($activeCycleId));
            foreach ($schedules as $sch) {
                $d->insert('db_lms_cycle_schedules', array(
                    'cycle_id'       => $activeCycleId,
                    'day_of_week'    => (int) $sch['day'],
                    'start_time'     => $sch['start'],
                    'end_time'       => $sch['end'],
                    'effective_from' => $effectiveFrom,
                ));
            }
        }

        // Tái sinh các buổi học upcoming từ $effectiveFrom trở đi
        // Giữ lại các buổi upcoming đã gắn chuyên đề hoặc học viên đã nộp BTVN để bảo toàn dữ liệu
        $sessionSvc = new \Lms\SessionService($d);
        $today = date('Y-m-d');
        $genStart = strcmp($effectiveFrom, $today) < 0 ? $today : $effectiveFrom;
        $endDate = date('Y-m-d', strtotime('+8 weeks', strtotime($genStart)));

        $existingUpcoming = $d->safeFetch(
            'select s.id, s.session_date, s.start_time, s.end_time, s.topic_id,'
            . ' (select count(*) from db_lms_session_students ss where ss.session_id = s.id and ss.hw_submitted_at is not null) as hw_sub_count'
            . ' from db_lms_sessions s'
            . ' where s.class_id = ? and s.status = ? and s.session_date >= ?',
            array($id, 'upcoming', $effectiveFrom)
        );

        $deleteIds = array();
        foreach ($existingUpcoming as $upSes) {
            $hasData = !empty($upSes['topic_id']) || (int) $upSes['hw_sub_count'] > 0;
            $matchesNewSchedule = false;
            if (strcmp((string) $upSes['session_date'], $genStart) >= 0) {
                $sesDow = (int) date('N', strtotime((string) $upSes['session_date'])) + 1;
                $sesStart = substr((string) $upSes['start_time'], 0, 5);
                $sesEnd   = substr((string) $upSes['end_time'], 0, 5);
                foreach ($schedules as $sch) {
                    if ((int) $sch['day'] === $sesDow && $sch['start'] === $sesStart && $sch['end'] === $sesEnd) {
                        $matchesNewSchedule = true;
                        break;
                    }
                }
            }
            if (!($hasData && $matchesNewSchedule)) {
                $deleteIds[] = (int) $upSes['id'];
            }
        }

        if (!empty($deleteIds)) {
            $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
            $d->safeExec("delete from db_lms_session_students where session_id in ($placeholders)", $deleteIds);
            $d->safeExec("delete from db_lms_sessions where id in ($placeholders)", $deleteIds);
        }

        $sessionSvc->generateForClass($id, (int) $class['teacher_id'], $genStart, $endDate);

        $upcomingRows = $d->safeFetch(
            'select id from db_lms_sessions where class_id = ? and status = ? and session_date >= ?',
            array($id, 'upcoming', $genStart)
        );
        foreach ($upcomingRows as $uRow) {
            $sessionSvc->ensureSessionStudents((int) $uRow['id']);
        }

        $d->commit();
        lms_flash('success', 'Đã cập nhật lịch cố định và áp dụng từ ngày ' . date('d/m/Y', strtotime($effectiveFrom)) . '.');
    } catch (\Exception $e) {
        $d->rollBack();
        lms_flash('error', $e->getMessage());
    }

    header('Location: ' . lms_url($alias, 'lich-hoc', array('id' => $id, 'week' => $week)));
    exit;
}

/**
 * Thêm buổi học mới (thủ công hoặc qua kéo thả).
 */
function lms_lop_add_session(\Lms\ClassService $classSvc)
{
    global $alias;
    $d = lms_db();
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $week = isset($_POST['week']) ? (string) $_POST['week'] : '';
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID lớp học.');
        header('Location: ' . lms_url($alias));
        exit;
    }
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc hết hạn.');
        header('Location: ' . lms_url($alias, 'lich-hoc', array('id' => $id, 'week' => $week)));
        exit;
    }

    $class = $classSvc->getClass($id);
    if (empty($class)) {
        lms_flash('error', 'Lớp học không tồn tại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $sessionDate = isset($_POST['session_date']) ? trim((string) $_POST['session_date']) : '';
    $startTime   = isset($_POST['start_time']) ? trim((string) $_POST['start_time']) : '';
    $endTime     = isset($_POST['end_time']) ? trim((string) $_POST['end_time']) : '';
    $topicId     = isset($_POST['topic_id']) && (int) $_POST['topic_id'] > 0 ? (int) $_POST['topic_id'] : null;
    $status      = isset($_POST['status']) ? trim((string) $_POST['status']) : '';

    if ($sessionDate === '' || $startTime === '' || $endTime === '') {
        lms_flash('error', 'Vui lòng nhập đầy đủ ngày và khung giờ học.');
        header('Location: ' . lms_url($alias, 'lich-hoc', array('id' => $id, 'week' => $week)));
        exit;
    }

    // Tự động phân loại trạng thái theo mốc thời gian:
    $today = date('Y-m-d');
    if ($sessionDate < $today) {
        if (!in_array($status, array('finished', 'missing', 'cancelled'), true)) {
            $status = 'finished';
        }
    } else {
        $status = 'upcoming';
    }

    $summary   = ($status === 'finished') ? 'Đã hoàn thành buổi học' : null;
    $summaryAt = ($status === 'finished') ? date('Y-m-d H:i:s') : null;
    $hwContent = ($status === 'missing') ? null : 'Bài tập về nhà theo chuyên đề';

    $dbStatus  = ($status === 'missing') ? 'finished' : $status;

    $exist = $d->safeFetchOne(
        'select id from db_lms_sessions where class_id = ? and session_date = ? and start_time = ? limit 1',
        array($id, $sessionDate, $startTime)
    );
    if (!empty($exist)) {
        lms_flash('error', 'Lớp đã có buổi học trong khung giờ này vào ngày ' . date('d/m/Y', strtotime($sessionDate)) . '.');
        header('Location: ' . lms_url($alias, 'lich-hoc', array('id' => $id, 'week' => $week)));
        exit;
    }

    $sessionSvc = new \Lms\SessionService($d);
    $feeSnap = $sessionSvc->resolveSessionFeeSnapshot($id, 0, (int) $class['teacher_id']);
    $sessionPrice = isset($_POST['price_per_session']) && is_numeric($_POST['price_per_session']) && (float) $_POST['price_per_session'] >= 0
        ? (float) $_POST['price_per_session']
        : $feeSnap['price_per_session'];
    $sessionPct = isset($_POST['teacher_percentage']) && is_numeric($_POST['teacher_percentage']) && (float) $_POST['teacher_percentage'] >= 0 && (float) $_POST['teacher_percentage'] <= 100
        ? (float) $_POST['teacher_percentage']
        : $feeSnap['teacher_percentage'];

    $ok = $d->insert('db_lms_sessions', array(
        'class_id'             => $id,
        'teacher_id'           => (int) $class['teacher_id'],
        'topic_id'             => $topicId,
        'session_date'         => $sessionDate,
        'start_time'           => $startTime,
        'end_time'             => $endTime,
        'price_per_session'    => $sessionPrice,
        'teacher_percentage'   => $sessionPct,
        'status'               => $dbStatus,
        'lesson_summary'       => $summary,
        'summary_submitted_at' => $summaryAt,
        'hw_content'           => $hwContent,
        'created_at'           => date('Y-m-d H:i:s'),
        'updated_at'           => date('Y-m-d H:i:s'),
    ));

    if ($ok) {
        $sessionId = (int) $d->insertId;
        $sessionSvc->ensureSessionStudents($sessionId);
        lms_flash('success', 'Đã thêm buổi học ngày ' . date('d/m/Y', strtotime($sessionDate)) . ' (' . $startTime . ' - ' . $endTime . ').');
    } else {
        lms_flash('error', 'Không thể thêm buổi học.');
    }

    header('Location: ' . lms_url($alias, 'lich-hoc', array('id' => $id, 'week' => $week)));
    exit;
}

/**
 * Xoá buổi học.
 */
function lms_lop_delete_session()
{
    global $alias;
    $d = lms_db();
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $sessionId = isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0;
    $week = isset($_POST['week']) ? (string) $_POST['week'] : '';

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc hết hạn.');
        header('Location: ' . lms_url($alias, 'lich-hoc', array('id' => $id, 'week' => $week)));
        exit;
    }

    $session = $d->safeFetchOne('select id, session_date from db_lms_sessions where id = ? and class_id = ? limit 1', array($sessionId, $id));
    if (empty($session)) {
        lms_flash('error', 'Buổi học không tồn tại.');
        header('Location: ' . lms_url($alias, 'lich-hoc', array('id' => $id, 'week' => $week)));
        exit;
    }

    $d->safeExec('delete from db_lms_session_students where session_id = ?', array($sessionId));
    $d->safeExec('delete from db_lms_sessions where id = ?', array($sessionId));

    lms_flash('success', 'Đã xoá buổi học ngày ' . date('d/m/Y', strtotime($session['session_date'])) . '.');
    header('Location: ' . lms_url($alias, 'lich-hoc', array('id' => $id, 'week' => $week)));
    exit;
}

/**
 * Đánh dấu buổi học nghỉ (status = cancelled).
 */
function lms_lop_mark_absent()
{
    global $alias;
    $d = lms_db();
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $sessionId = isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0;
    $week = isset($_POST['week']) ? (string) $_POST['week'] : '';

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc hết hạn.');
        header('Location: ' . lms_url($alias, 'lich-hoc', array('id' => $id, 'week' => $week)));
        exit;
    }

    $session = $d->safeFetchOne('select id, session_date from db_lms_sessions where id = ? and class_id = ? limit 1', array($sessionId, $id));
    if (empty($session)) {
        lms_flash('error', 'Buổi học không tồn tại.');
        header('Location: ' . lms_url($alias, 'lich-hoc', array('id' => $id, 'week' => $week)));
        exit;
    }

    $d->update('db_lms_sessions', array('status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')), 'id = ?', array($sessionId));

    lms_flash('success', 'Đã đánh dấu buổi học ngày ' . date('d/m/Y', strtotime($session['session_date'])) . ' là nghỉ học.');
    header('Location: ' . lms_url($alias, 'lich-hoc', array('id' => $id, 'week' => $week)));
    exit;
}

// ===================== Điều chỉnh số buổi khoá =====================

function lms_lop_adjust_sessions(\Lms\ClassService $classSvc)
{
    global $alias;

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $newTotal = isset($_POST['total_sessions']) ? (int) $_POST['total_sessions'] : -1;

    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID lớp học.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias, 'sua', array('id' => $id)));
        exit;
    }

    if ($classSvc->adjustSessions($id, $newTotal)) {
        lms_flash('success', 'Đã cập nhật số buổi khoá học.');
    } else {
        $errors = $classSvc->errors();
        lms_flash('error', isset($errors['total_sessions'])
            ? $errors['total_sessions']
            : (isset($errors['system']) ? $errors['system'] : 'Không thể điều chỉnh số buổi.'));
    }
    header('Location: ' . lms_url($alias, 'sua', array('id' => $id)));
    exit;
}

// ===================== Thêm khoá học mới (Popup Thêm khóa học.png) =====================

function lms_lop_add_course_cycle(LmsDb $d)
{
    global $alias;

    $classId          = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
    $currentRemaining = isset($_POST['current_remaining_sessions']) ? trim((string) $_POST['current_remaining_sessions']) : '';
    $newTotal         = isset($_POST['new_total_sessions']) ? trim((string) $_POST['new_total_sessions']) : '';
    $redirectTo       = isset($_POST['redirect_to']) ? trim((string) $_POST['redirect_to']) : '';

    $backUrl = lms_url($alias);
    if ($redirectTo === 'lms-yeu-cau') {
        $backUrl = lms_url('lms-yeu-cau', '', array('tab' => 'kiem-buoi'));
    } elseif (!empty($_SERVER['HTTP_REFERER'])) {
        $ref = (string) $_SERVER['HTTP_REFERER'];
        $host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
        if ($host !== '' && strpos($ref, $host) !== false) {
            $backUrl = $ref;
        }
    }

    if ($classId <= 0) {
        lms_flash('error', 'Thiếu ID lớp học.');
        header('Location: ' . $backUrl);
        exit;
    }

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . $backUrl);
        exit;
    }

    $cycleSvc = new \Lms\CourseCycleService($d);
    $newCycleId = $cycleSvc->addCourseCycleFromPopup($classId, $currentRemaining, $newTotal);
    if ($newCycleId > 0) {
        lms_flash('success', 'Đã thêm khóa học mới thành công.');
    } else {
        $errors = $cycleSvc->errors();
        $firstErr = !empty($errors) ? (string) reset($errors) : 'Không thể thêm khóa học mới.';
        lms_flash('error', $firstErr);
    }

    header('Location: ' . $backUrl);
    exit;
}

// ===================== Danh sách =====================

function lms_lop_list(\Lms\ClassService $classSvc)
{
    global $title, $template, $items, $total, $totalPages, $page,
           $filterQ, $filterTeacherId, $filterProgramIds, $filterProgramId, $filterDayOfWeek,
           $filterStatus, $filterClassType, $canEdit, $canDelete,
           $teachers, $programs, $d, $user, $alias;

    $filterQ          = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $filterTeacherId  = isset($_GET['teacher_id']) ? (int) $_GET['teacher_id'] : 0;
    $filterProgramId  = isset($_GET['program_id']) ? (int) $_GET['program_id'] : 0;
    $filterProgramIds = array();
    if (isset($_GET['program_ids']) && is_array($_GET['program_ids'])) {
        foreach ($_GET['program_ids'] as $pid) {
            $pid = (int) $pid;
            if ($pid > 0) {
                $filterProgramIds[] = $pid;
            }
        }
    }
    if ($filterProgramId > 0 && !in_array($filterProgramId, $filterProgramIds, true)) {
        $filterProgramIds[] = $filterProgramId;
    }
    $filterDayOfWeek = isset($_GET['day_of_week']) ? (int) $_GET['day_of_week'] : 0;
    $filterStatus    = isset($_GET['status']) ? (string) $_GET['status'] : '';
    $filterClassType = isset($_GET['class_type']) ? (string) $_GET['class_type'] : '';
    $page            = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage         = 20;

    $res = $classSvc->listClasses(array(
        'q'            => $filterQ,
        'teacher_id'   => $filterTeacherId,
        'program_ids'  => $filterProgramIds,
        'day_of_week'  => $filterDayOfWeek,
        'status'       => $filterStatus,
        'class_type'   => $filterClassType,
    ), $page, $perPage);

    $items      = $res['items'];
    $total      = (int) $res['total'];
    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

    $cycleSvc = new \Lms\CourseCycleService($d);
    foreach ($items as $i => $item) {
        $items[$i]['cycles'] = $cycleSvc->listCyclesForClass((int) $item['id']);
        $items[$i]['active_cycle_id'] = $cycleSvc->resolveActiveCycleId((int) $item['id']);
    }

    $teachers = lms_lop_teachers($d);
    $programs = lms_lop_programs($d);

    $canEdit   = lms_check_permission($d, $user, $alias, 'edit', 'admin');
    $canDelete = lms_check_permission($d, $user, $alias, 'delete', 'admin');

    $title    = 'Quản lý lớp học';
    $template = 'lms-lop/hienthi';
}

// ===================== Form tạo / sửa =====================

function lms_lop_form(\Lms\ClassService $classSvc, $isEdit)
{
    global $title, $template, $row, $errors, $isEditMode, $formId,
           $programs, $subjects, $teachers, $students, $topics, $d, $user, $alias;

    $errors     = array();
    $isEditMode = $isEdit;
    $row        = array();
    $formId     = 0;

    if ($isEdit) {
        $formId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($formId <= 0) {
            lms_flash('error', 'Thiếu ID lớp học.');
            header('Location: ' . lms_url($alias));
            exit;
        }
        $row = $classSvc->getClass($formId);
        if (empty($row)) {
            lms_flash('error', 'Lớp học không tồn tại.');
            header('Location: ' . lms_url($alias));
            exit;
        }
        // TIME column trả 'HH:MM:SS' → chỉ giữ 'HH:MM' cho input[type=time]
        if (isset($row['hw_deadline_time'])) {
            $row['hw_deadline_time'] = substr((string) $row['hw_deadline_time'], 0, 5);
        }
        // Lịch từ DB → định dạng mảng cho form
        $schedules = array();
        foreach ((isset($row['schedules']) ? $row['schedules'] : array()) as $sch) {
            $schedules[] = array(
                'day'   => (int) $sch['day_of_week'],
                'start' => substr((string) $sch['start_time'], 0, 5),
                'end'   => substr((string) $sch['end_time'], 0, 5),
            );
        }
        $row['schedules'] = $schedules;
    }

    $isPost = isset($_SERVER['REQUEST_METHOD']) && (string) $_SERVER['REQUEST_METHOD'] === 'POST';

    if ($isPost) {
        if (!lms_csrf_verify()) {
            lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
            header('Location: ' . lms_url($alias, $isEdit ? 'sua' : 'them', $isEdit ? array('id' => $formId) : array()));
            exit;
        }

        $in = $_POST;
        $in['schedules']   = lms_lop_schedules_from_post($_POST);
        $in['student_ids'] = isset($_POST['student_ids']) ? (array) $_POST['student_ids'] : array();
        if (!empty($_POST['cycle_start_date']) && empty($_POST['start_date'])) {
            $in['start_date'] = $_POST['cycle_start_date'];
        }

        if ($isEdit) {
            $ok = $classSvc->updateClass($formId, $in);
        } else {
            $newId = $classSvc->createClass($in);
            $ok = $newId !== null;
            if ($ok) {
                $formId = (int) $newId;
            }
        }

        if ($ok) {
            lms_flash('success', $isEdit ? 'Đã cập nhật lớp học.' : 'Đã tạo lớp học mới.');
            header('Location: ' . lms_url($alias));
            exit;
        }

        $errors = $classSvc->errors();
        $row = array_merge($row, $in);
    }

    // schedules + student_ids luôn là mảng khi render
    if (!isset($row['schedules']) || !is_array($row['schedules'])) {
        $row['schedules'] = array();
    }
    if (!isset($row['student_ids']) || !is_array($row['student_ids'])) {
        $row['student_ids'] = array();
    }
    $scanRules = (new \Lms\TickService($d))->getScanRules();
    $defaultScope = (isset($row['class_type']) && $row['class_type'] === 'group') ? 'group' : 'one_on_one';
    $scopeRule = isset($scanRules[$defaultScope]) ? $scanRules[$defaultScope] : array();

    if (!isset($row['hw_deadline_time']) || (string) $row['hw_deadline_time'] === '') {
        $row['hw_deadline_time'] = isset($scopeRule['hw_deadline_time']) ? (string) $scopeRule['hw_deadline_time'] : '12:00';
    }
    if (!isset($row['hw_check_hours']) || (string) $row['hw_check_hours'] === '') {
        $row['hw_check_hours'] = isset($scopeRule['hw_check_hours']) ? (int) $scopeRule['hw_check_hours'] : ($defaultScope === 'group' ? 24 : 0);
    }
    if (!isset($row['test_grace_hours']) || (string) $row['test_grace_hours'] === '') {
        $row['test_grace_hours'] = isset($scopeRule['test_grace_hours']) ? (int) $scopeRule['test_grace_hours'] : 72;
    }
    if (!isset($row['topic_ids']) || !is_array($row['topic_ids'])) {
        $row['topic_ids'] = array();
    }

    $programs  = lms_lop_programs($d);
    $subjects  = lms_lop_subjects($d);
    $teachers  = lms_lop_teachers($d);
    $students  = lms_lop_students($d);
    $topics    = lms_lop_topics($d);

    $title    = $isEdit ? 'Sửa lớp học' : 'Lớp học mới';
    $template = 'lms-lop/form';
}

/**
 * Chuyển 3 mảng song song từ form (sch_day[]/sch_start[]/sch_end[])
 * thành mảng các khung giờ để service validate.
 *
 * @param array $post
 * @return array
 */
function lms_lop_schedules_from_post($post)
{
    $days   = isset($post['sch_day']) ? (array) $post['sch_day'] : array();
    $starts = isset($post['sch_start']) ? (array) $post['sch_start'] : array();
    $ends   = isset($post['sch_end']) ? (array) $post['sch_end'] : array();

    $out = array();
    $n = max(count($days), count($starts), count($ends));
    for ($i = 0; $i < $n; $i++) {
        $out[] = array(
            'day'   => isset($days[$i]) ? $days[$i] : '',
            'start' => isset($starts[$i]) ? $starts[$i] : '',
            'end'   => isset($ends[$i]) ? $ends[$i] : '',
        );
    }
    return $out;
}

// ===================== Tạm dừng / tiếp tục =====================

function lms_lop_toggle_status(\Lms\ClassService $classSvc, $action)
{
    global $alias;

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $status = $action === 'tam-dung' ? 'paused' : 'active';

    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID lớp học.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if ($classSvc->setClassStatus($id, $status)) {
        lms_flash('success', $status === 'paused' ? 'Đã tạm dừng lớp học.' : 'Đã tiếp tục lớp học.');
    } else {
        $errors = $classSvc->errors();
        lms_flash('error', isset($errors['system']) ? $errors['system'] : 'Không thể thay đổi trạng thái lớp học.');
    }
    header('Location: ' . lms_url($alias));
    exit;
}

// ===================== Xoá =====================

function lms_lop_delete(\Lms\ClassService $classSvc)
{
    global $alias;

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID lớp học.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if ($classSvc->deleteClass($id)) {
        lms_flash('success', 'Đã xoá lớp học. Các buổi đã hoàn thành được giữ lại làm lịch sử.');
    } else {
        $errors = $classSvc->errors();
        lms_flash('error', isset($errors['system']) ? $errors['system'] : 'Không thể xoá lớp học.');
    }
    header('Location: ' . lms_url($alias));
    exit;
}

// ===================== Điều chỉnh số buổi học hàng loạt (Modal ADM-03) =====================

/**
 * AJAX API: Lấy danh sách lớp phân trang phục vụ modal Điều chỉnh số buổi (ADM-03)
 */
function lms_lop_ajax_list_classes(LmsDb $d)
{
    header('Content-Type: application/json; charset=UTF-8');

    $q = trim((string) ($_GET['q'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = max(1, min(50, (int) ($_GET['per_page'] ?? 7)));
    $offset = ($page - 1) * $perPage;

    $where = array('c.deleted_at is null');
    $params = array();

    if ($q !== '') {
        $kw = '%' . $q . '%';
        $where[] = '(c.class_code LIKE ? OR c.name LIKE ? OR t.teacher_code LIKE ? OR t.full_name LIKE ?'
            . ' OR EXISTS ('
            . '   SELECT 1 FROM db_lms_class_students cs'
            . '   JOIN db_lms_students s ON s.user_id = cs.student_id'
            . '   WHERE cs.class_id = c.id AND cs.status = "active" AND (s.student_code LIKE ? OR s.full_name LIKE ?)'
            . ' )'
            . ' OR EXISTS ('
            . '   SELECT 1 FROM db_lms_cycle_students cys'
            . '   JOIN db_lms_course_cycles cyc ON cyc.id = cys.cycle_id'
            . '   JOIN db_lms_students s2 ON s2.user_id = cys.student_id'
            . '   WHERE cyc.class_id = c.id AND cys.status = "active" AND (s2.student_code LIKE ? OR s2.full_name LIKE ?)'
            . ' ))';
        $params[] = $kw;
        $params[] = $kw;
        $params[] = $kw;
        $params[] = $kw;
        $params[] = $kw;
        $params[] = $kw;
        $params[] = $kw;
        $params[] = $kw;
    }

    $whereSql = implode(' AND ', $where);

    // Đếm tổng số lớp
    $countRow = $d->safeFetchOne("SELECT count(*) as c FROM db_lms_classes c LEFT JOIN db_lms_teachers t ON t.user_id = c.teacher_id WHERE {$whereSql}", $params);
    $total = isset($countRow['c']) ? (int) $countRow['c'] : 0;
    $totalPages = max(1, (int) ceil($total / $perPage));

    // Lấy toàn bộ ID khớp để hỗ trợ nút "Chọn tất cả"
    $allRows = $d->safeFetch("SELECT c.id FROM db_lms_classes c LEFT JOIN db_lms_teachers t ON t.user_id = c.teacher_id WHERE {$whereSql} ORDER BY c.id DESC", $params);
    $allMatchingIds = array_map('intval', array_column($allRows, 'id'));

    // Lấy dữ liệu trang hiện tại
    $classes = $d->safeFetch(
        "SELECT c.id, c.class_code, c.name, t.teacher_code, t.full_name as teacher_name"
        . " FROM db_lms_classes c"
        . " LEFT JOIN db_lms_teachers t ON t.user_id = c.teacher_id"
        . " WHERE {$whereSql}"
        . " ORDER BY c.id DESC"
        . " LIMIT {$offset}, {$perPage}",
        $params
    );

    $items = array();
    foreach ($classes as $cls) {
        $cid = (int) $cls['id'];

        // Lấy danh sách học viên
        $students = $d->safeFetch(
            "SELECT DISTINCT s.student_code, s.full_name"
            . " FROM ("
            . "   SELECT student_id FROM db_lms_class_students WHERE class_id = ? AND status = 'active'"
            . "   UNION"
            . "   SELECT cys.student_id FROM db_lms_cycle_students cys JOIN db_lms_course_cycles cyc ON cyc.id = cys.cycle_id WHERE cyc.class_id = ? AND cys.status = 'active'"
            . " ) st"
            . " JOIN db_lms_students s ON s.user_id = st.student_id"
            . " ORDER BY s.student_code ASC",
            array($cid, $cid)
        );

        $stuLabels = array();
        foreach ($students as $stu) {
            $code = trim((string) ($stu['student_code'] ?? ''));
            $name = trim((string) ($stu['full_name'] ?? ''));
            if ($code && $name) {
                $stuLabels[] = $code . "\n[" . $name . ']';
            } elseif ($code || $name) {
                $stuLabels[] = $code ?: $name;
            }
        }
        $studentsText = !empty($stuLabels) ? implode("\n", $stuLabels) : '—';

        $tCode = trim((string) ($cls['teacher_code'] ?? ''));
        $tName = trim((string) ($cls['teacher_name'] ?? ''));
        if ($tCode && $tName) {
            $teacherText = $tCode . "\n[" . $tName . ']';
        } elseif ($tCode || $tName) {
            $teacherText = $tCode ?: $tName;
        } else {
            $teacherText = '—';
        }

        $items[] = array(
            'id'             => $cid,
            'class_code'     => (string) $cls['class_code'],
            'class_name'     => (string) ($cls['name'] ?: $cls['class_code']),
            'students_label' => $studentsText,
            'teacher_label'  => $teacherText,
        );
    }

    echo json_encode(array(
        'success'          => true,
        'items'            => $items,
        'all_matching_ids' => $allMatchingIds,
        'pagination'       => array(
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ),
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Thao tác hàng loạt: Xoá buổi học chưa diễn ra theo khoảng ngày
 */
function lms_lop_bulk_delete_sessions(LmsDb $d, $alias)
{
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng thử lại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $classIds = isset($_POST['class_ids']) && is_array($_POST['class_ids'])
        ? array_filter(array_map('intval', $_POST['class_ids']))
        : array();

    $fromDate = trim((string) ($_POST['from_date'] ?? ''));
    $toDate   = trim((string) ($_POST['to_date'] ?? ''));

    if (empty($classIds)) {
        lms_flash('error', 'Vui lòng tích chọn ít nhất một lớp học để xoá buổi.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if ($fromDate === '' || $toDate === '' || strcmp($toDate, $fromDate) < 0) {
        lms_flash('error', 'Khoảng ngày xoá không hợp lệ (Từ ngày phải nhỏ hơn hoặc bằng Đến ngày).');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $inPlaceholders = implode(',', array_fill(0, count($classIds), '?'));
    $params = $classIds;
    $params[] = $fromDate;
    $params[] = $toDate;

    // Tìm các buổi học thoả mãn điều kiện an toàn: chỉ xoá buổi CHƯA HOÀN THÀNH và CHƯA BÁO CÁO
    $sessions = $d->safeFetch(
        "SELECT id, class_id, cycle_id FROM db_lms_sessions"
        . " WHERE class_id IN ({$inPlaceholders})"
        . " AND session_date >= ? AND session_date <= ?"
        . " AND status != 'finished'"
        . " AND (summary_submitted_at IS NULL OR summary_submitted_at = '')",
        $params
    );

    if (empty($sessions)) {
        lms_flash('warning', 'Không tìm thấy buổi học nào chưa diễn ra của các lớp đã chọn trong khoảng từ ' . date('d/m/Y', strtotime($fromDate)) . ' đến ' . date('d/m/Y', strtotime($toDate)) . '.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $sessionIds = array_map('intval', array_column($sessions, 'id'));
    $cycleIds = array_filter(array_unique(array_map('intval', array_column($sessions, 'cycle_id'))));
    $sesIn = implode(',', array_fill(0, count($sessionIds), '?'));

    $d->beginTransaction();
    try {
        // Xoá bản ghi học viên của các buổi
        $d->safeExec("DELETE FROM db_lms_session_students WHERE session_id IN ({$sesIn})", $sessionIds);
        // Xoá buổi học
        $d->safeExec("DELETE FROM db_lms_sessions WHERE id IN ({$sesIn})", $sessionIds);

        // Đồng bộ lại total_sessions cho các cycle bị ảnh hưởng
        foreach ($cycleIds as $cycId) {
            $cycCountRow = $d->safeFetchOne('SELECT count(*) as c FROM db_lms_sessions WHERE cycle_id = ?', array($cycId));
            $cycCount = isset($cycCountRow['c']) ? (int) $cycCountRow['c'] : 0;
            $d->safeExec('UPDATE db_lms_course_cycles SET total_sessions = ? WHERE id = ?', array($cycCount, $cycId));
        }

        // Cập nhật lại số buổi còn lại cho các lớp bị ảnh hưởng
        foreach ($classIds as $cid) {
            $d->safeExec(
                "UPDATE db_lms_classes SET remaining_sessions = (SELECT count(*) FROM db_lms_sessions WHERE class_id = ? AND status = 'upcoming') WHERE id = ?",
                array($cid, $cid)
            );
        }
        $d->commit();

        lms_flash('success', 'Đã xoá thành công ' . count($sessionIds) . ' buổi học của ' . count($classIds) . ' lớp học từ ngày ' . date('d/m/Y', strtotime($fromDate)) . ' đến ngày ' . date('d/m/Y', strtotime($toDate)) . '.');
    } catch (\Exception $e) {
        $d->rollBack();
        lms_flash('error', 'Lỗi khi xoá buổi học: ' . $e->getMessage());
    }

    header('Location: ' . lms_url($alias));
    exit;
}

/**
 * Thao tác hàng loạt: Sinh/thêm buổi học theo lịch cố định trong khoảng ngày
 */
function lms_lop_bulk_generate_sessions(LmsDb $d, $alias)
{
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng thử lại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $classIds = isset($_POST['class_ids']) && is_array($_POST['class_ids'])
        ? array_filter(array_map('intval', $_POST['class_ids']))
        : array();

    $fromDate = trim((string) ($_POST['from_date'] ?? ''));
    $toDate   = trim((string) ($_POST['to_date'] ?? ''));

    if (empty($classIds)) {
        lms_flash('error', 'Vui lòng tích chọn ít nhất một lớp học để thêm buổi.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if ($fromDate === '' || $toDate === '' || strcmp($toDate, $fromDate) < 0) {
        lms_flash('error', 'Khoảng ngày thêm không hợp lệ (Từ ngày phải nhỏ hơn hoặc bằng Đến ngày).');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $sessionSvc = new \Lms\SessionService($d);
    $totalCreated = 0;
    $classesAffected = 0;

    foreach ($classIds as $classId) {
        $class = $d->safeFetchOne('SELECT id, teacher_id, status FROM db_lms_classes WHERE id = ?', array($classId));
        if (empty($class)) {
            continue;
        }

        $activeCycle = $d->safeFetchOne(
            'SELECT id, teacher_id FROM db_lms_course_cycles WHERE class_id = ? AND status = "active" ORDER BY cycle_no DESC LIMIT 1',
            array($classId)
        );

        $teacherId = !empty($activeCycle['teacher_id']) ? (int) $activeCycle['teacher_id'] : (int) ($class['teacher_id'] ?? 0);
        $cycleId   = !empty($activeCycle['id']) ? (int) $activeCycle['id'] : 0;

        if ($teacherId <= 0) {
            continue;
        }

        // Lấy danh sách lịch cố định
        $schedules = array();
        if ($cycleId > 0) {
            $schedules = $d->safeFetch(
                'SELECT day_of_week, start_time, end_time, effective_from, effective_to'
                . ' FROM db_lms_cycle_schedules WHERE cycle_id = ? ORDER BY day_of_week, start_time',
                array($cycleId)
            );
        }
        if (empty($schedules)) {
            $schedules = $d->safeFetch(
                'SELECT day_of_week, start_time, end_time, effective_from, effective_to'
                . ' FROM db_lms_schedules WHERE class_id = ? ORDER BY day_of_week, start_time',
                array($classId)
            );
        }

        if (empty($schedules)) {
            continue;
        }

        $feeSnap = $sessionSvc->resolveSessionFeeSnapshot($classId, $cycleId, $teacherId);
        $createdForClass = 0;
        $day = strtotime($fromDate);
        $endTs = strtotime($toDate);

        while ($day <= $endTs) {
            $dateStr = date('Y-m-d', $day);
            $dow = (int) date('N', $day) + 1; // 1=Mon -> 2=T2 ... 7=Sun -> 8=CN

            foreach ($schedules as $sch) {
                if ((int) $sch['day_of_week'] !== $dow) {
                    continue;
                }
                if (!empty($sch['effective_from']) && strcmp($dateStr, (string) $sch['effective_from']) < 0) {
                    continue;
                }
                if (!empty($sch['effective_to']) && strcmp($dateStr, (string) $sch['effective_to']) > 0) {
                    continue;
                }

                // Kiểm tra xem đã tồn tại buổi học cùng ngày và giờ chưa
                $exists = $d->safeFetchOne(
                    'SELECT id FROM db_lms_sessions WHERE class_id = ? AND session_date = ? AND start_time = ? LIMIT 1',
                    array($classId, $dateStr, (string) $sch['start_time'])
                );
                if (!empty($exists)) {
                    continue;
                }

                try {
                    $insertData = array(
                        'class_id'           => $classId,
                        'teacher_id'         => $teacherId,
                        'session_date'       => $dateStr,
                        'start_time'         => $sch['start_time'],
                        'end_time'           => $sch['end_time'],
                        'price_per_session'  => $feeSnap['price_per_session'],
                        'teacher_percentage' => $feeSnap['teacher_percentage'],
                        'status'             => 'upcoming',
                    );
                    if ($cycleId > 0) {
                        $insertData['cycle_id'] = $cycleId;
                    }
                    $ok = $d->insert('db_lms_sessions', $insertData);
                    if ($ok) {
                        $createdForClass++;
                    }
                } catch (\Exception $e) {
                    // Tránh duplicate key nếu chạy song song
                }
            }

            $day = strtotime('+1 day', $day);
        }

        if ($createdForClass > 0) {
            $totalCreated += $createdForClass;
            $classesAffected++;

            // Đồng bộ lại total_sessions cho cycle nếu có
            if ($cycleId > 0) {
                $cycCountRow = $d->safeFetchOne('SELECT count(*) as c FROM db_lms_sessions WHERE cycle_id = ?', array($cycleId));
                $cycCount = isset($cycCountRow['c']) ? (int) $cycCountRow['c'] : 0;
                $d->safeExec('UPDATE db_lms_course_cycles SET total_sessions = ? WHERE id = ?', array($cycCount, $cycleId));
            }

            // Cập nhật remaining_sessions của lớp
            $d->safeExec(
                "UPDATE db_lms_classes SET remaining_sessions = (SELECT count(*) FROM db_lms_sessions WHERE class_id = ? AND status = 'upcoming') WHERE id = ?",
                array($classId, $classId)
            );
        }
    }

    // Gắn học viên cho các session mới sinh
    if ($totalCreated > 0) {
        $weeks = max(4, (int) ceil((strtotime($toDate) - strtotime($fromDate)) / (7 * 86400)) + 4);
        $sessionSvc->ensureUpcomingSessionStudents($fromDate, $weeks);
        lms_flash('success', 'Đã thêm thành công ' . $totalCreated . ' buổi học cho ' . $classesAffected . ' lớp học từ ngày ' . date('d/m/Y', strtotime($fromDate)) . ' đến ngày ' . date('d/m/Y', strtotime($toDate)) . '.');
    } else {
        lms_flash('info', 'Không có buổi học mới nào được tạo (các buổi học trong khoảng ngày này có thể đã tồn tại hoặc lớp chưa có lịch cố định khớp).');
    }

    header('Location: ' . lms_url($alias));
    exit;
}

