<?php
/**
 * AdminRequestService — dữ liệu cho màn Quản lý yêu cầu (ADM-05).
 *
 * 5 tab theo mockup Tai-lieu-phat-trien/admin/Quản lý yêu cầu - *.png:
 *  - them-buoi : request add_session đang chờ duyệt (kèm chuyên đề hiện tại + số buổi đã học)
 *  - test      : bài test/minitest ở trạng thái draft chờ admin xác nhận gửi
 *  - duyet-kq  : bài nộp test/minitest đã nộp, chưa chấm (chờ admin duyệt kết quả)
 *  - doi-lich  : request_leave + change_schedule đang chờ duyệt
 *  - kiem-buoi : lớp đang hoạt động còn dưới 5 buổi
 *
 * Việc duyệt request dùng \Lms\RequestService (transaction + lock) — service này
 * chỉ cung cấp danh sách + thao tác trên test/bài nộp/xoá request.
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class AdminRequestService extends LmsService
{
    const TEST_TYPES = array('test' => 'Test', 'minitest' => 'Minitest');

    /** Ngưỡng cảnh báo "sắp hết buổi". */
    const LOW_SESSION_THRESHOLD = 5;

    // ===================== Danh sách theo tab =====================

    /**
     * Tab 1 — request add_session đang chờ duyệt.
     *
     * @param array $filters q (mã/tên HV), phone (SĐT)
     * @param int   $page
     * @param int   $perPage
     * @return array array('items' => array, 'total' => int)
     */
    public function listAddSessionRequests(array $filters = array(), $page = 1, $perPage = 20)
    {
        $page    = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset  = ($page - 1) * $perPage;

        $where  = array("r.request_type = ?", "r.status = ?");
        $params = array('add_session', 'pending');

        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $where[]  = '(s.student_code like ? or s.full_name like ? or u.username like ?)';
            $like     = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $phone = isset($filters['phone']) ? trim((string) $filters['phone']) : '';
        if ($phone !== '') {
            $where[]  = '(s.parent_phone like ? or u.phone like ?)';
            $like     = '%' . $phone . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = implode(' and ', $where);

        $countRow = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_requests r'
            . ' join db_lms_classes c on c.id = r.class_id and c.deleted_at is null'
            . ' left join db_lms_students s on s.user_id = r.student_id'
            . ' left join db_lms_users u on u.id = r.student_id'
            . ' where ' . $whereSql,
            $params
        );
        $total = isset($countRow['c']) ? (int) $countRow['c'] : 0;

        $items = $this->db->safeFetch(
            'select r.id, r.class_id, r.created_at,'
            . ' c.class_code, c.total_sessions, c.remaining_sessions,'
            . ' t.teacher_code,'
            . ' s.student_code, s.full_name as student_name,'
            . ' (select tp.name from db_lms_sessions ses'
            . '   join db_lms_topics tp on tp.id = ses.topic_id'
            . '   where ses.class_id = c.id and ses.status = ? and ses.topic_id is not null'
            . '   order by ses.session_date desc, ses.start_time desc limit 1) as current_topic,'
            . ' (select ses.topic_id from db_lms_sessions ses'
            . '   where ses.class_id = c.id and ses.status = ? and ses.topic_id is not null'
            . '   order by ses.session_date desc, ses.start_time desc limit 1) as current_topic_id'
            . ' from db_lms_requests r'
            . ' join db_lms_classes c on c.id = r.class_id and c.deleted_at is null'
            . ' left join db_lms_teachers t on t.user_id = r.teacher_id'
            . ' left join db_lms_students s on s.user_id = r.student_id'
            . ' left join db_lms_users u on u.id = r.student_id'
            . ' where ' . $whereSql
            . ' order by r.created_at desc, r.id desc'
            . ' limit ' . $perPage . ' offset ' . $offset,
            array_merge(array('finished', 'finished'), $params)
        );

        $this->attachTopicProgress($items);

        return array('items' => $items, 'total' => $total);
    }

    /**
     * Tab 2 — bài test/minitest draft chờ admin xác nhận gửi.
     *
     * @param array $filters q (mã lớp, mã GV)
     * @param int   $page
     * @param int   $perPage
     * @return array array('items' => array, 'total' => int)
     */
    public function listTestRequests(array $filters = array(), $page = 1, $perPage = 20)
    {
        $page    = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset  = ($page - 1) * $perPage;

        $where  = array("te.status = ?");
        $params = array('draft');

        $this->applyClassTeacherFilter($where, $params, isset($filters['q']) ? $filters['q'] : '');
        $whereSql = implode(' and ', $where);

        $countRow = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_tests te'
            . ' join db_lms_classes c on c.id = te.class_id and c.deleted_at is null'
            . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
            . ' where ' . $whereSql,
            $params
        );
        $total = isset($countRow['c']) ? (int) $countRow['c'] : 0;

        $items = $this->db->safeFetch(
            'select te.id, te.class_id, te.topic_id, te.test_type, te.title,'
            . ' te.test_file_url, te.created_at,'
            . ' c.class_code, c.total_sessions, c.remaining_sessions,'
            . ' t.teacher_code, top.name as topic_name'
            . ' from db_lms_tests te'
            . ' join db_lms_classes c on c.id = te.class_id and c.deleted_at is null'
            . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
            . ' left join db_lms_topics top on top.id = te.topic_id'
            . ' where ' . $whereSql
            . ' order by te.created_at desc, te.id desc'
            . ' limit ' . $perPage . ' offset ' . $offset,
            $params
        );

        $this->attachTopicProgress($items);

        return array('items' => $items, 'total' => $total);
    }

    /**
     * Tab 3 — bài nộp test/minitest đã nộp, chưa chấm (chờ duyệt KQ).
     *
     * @param array $filters q (mã lớp, mã GV)
     * @param int   $page
     * @param int   $perPage
     * @return array array('items' => array, 'total' => int)
     */
    public function listTestResults(array $filters = array(), $page = 1, $perPage = 20)
    {
        $page    = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset  = ($page - 1) * $perPage;

        $where  = array('ts.submitted_at is not null', 'ts.graded_at is null');
        $params = array();

        $this->applyClassTeacherFilter($where, $params, isset($filters['q']) ? $filters['q'] : '');
        $whereSql = implode(' and ', $where);

        $countRow = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_test_submissions ts'
            . ' join db_lms_tests te on te.id = ts.test_id'
            . ' join db_lms_classes c on c.id = te.class_id and c.deleted_at is null'
            . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
            . ' where ' . $whereSql,
            $params
        );
        $total = isset($countRow['c']) ? (int) $countRow['c'] : 0;

        $items = $this->db->safeFetch(
            'select ts.id as submission_id, ts.submitted_at, ts.submission_status,'
            . ' ts.score_x, ts.score_y_percent,'
            . ' te.id as test_id, te.test_type, te.max_score, te.topic_id,'
            . ' top.name as topic_name,'
            . ' c.id as class_id, c.class_code,'
            . ' t.teacher_code,'
            . ' ts.student_id, s.student_code, s.full_name as student_name'
            . ' from db_lms_test_submissions ts'
            . ' join db_lms_tests te on te.id = ts.test_id'
            . ' join db_lms_classes c on c.id = te.class_id and c.deleted_at is null'
            . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
            . ' left join db_lms_topics top on top.id = te.topic_id'
            . ' left join db_lms_students s on s.user_id = ts.student_id'
            . ' where ' . $whereSql
            . ' order by ts.submitted_at desc, ts.id desc'
            . ' limit ' . $perPage . ' offset ' . $offset,
            $params
        );

        return array('items' => $items, 'total' => $total);
    }

    /**
     * Tab 4 — request xin nghỉ / đổi lịch cố định đang chờ duyệt.
     *
     * @param array $filters q (mã lớp, mã GV)
     * @param int   $page
     * @param int   $perPage
     * @return array array('items' => array, 'total' => int)
     */
    public function listScheduleRequests(array $filters = array(), $page = 1, $perPage = 20)
    {
        $page    = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset  = ($page - 1) * $perPage;

        $where  = array('r.request_type in (?, ?)', "r.status = ?");
        $params = array('request_leave', 'change_schedule', 'pending');

        $this->applyClassTeacherFilter($where, $params, isset($filters['q']) ? $filters['q'] : '');
        $whereSql = implode(' and ', $where);

        $countRow = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_requests r'
            . ' join db_lms_classes c on c.id = r.class_id and c.deleted_at is null'
            . ' left join db_lms_teachers t on t.user_id = r.teacher_id'
            . ' where ' . $whereSql,
            $params
        );
        $total = isset($countRow['c']) ? (int) $countRow['c'] : 0;

        $items = $this->db->safeFetch(
            'select r.id, r.request_type, r.class_id, r.session_id, r.payload, r.created_at,'
            . ' c.class_code, t.teacher_code,'
            . ' ses.session_date, ses.start_time, ses.end_time,'
            . ' (select group_concat(case sc.day_of_week when 8 then concat(\'CN \','
            . '     time_format(sc.start_time, \'%H:%i\'), \'-\', time_format(sc.end_time, \'%H:%i\'))'
            . '   else concat(\'T\', sc.day_of_week, \' \','
            . '     time_format(sc.start_time, \'%H:%i\'), \'-\', time_format(sc.end_time, \'%H:%i\')) end'
            . '   order by sc.day_of_week, sc.start_time separator \' / \')'
            . '   from db_lms_schedules sc'
            . '   where sc.class_id = c.id and (sc.effective_to is null or sc.effective_to >= curdate())) as old_schedule'
            . ' from db_lms_requests r'
            . ' join db_lms_classes c on c.id = r.class_id and c.deleted_at is null'
            . ' left join db_lms_teachers t on t.user_id = r.teacher_id'
            . ' left join db_lms_sessions ses on ses.id = r.session_id'
            . ' where ' . $whereSql
            . ' order by r.created_at desc, r.id desc'
            . ' limit ' . $perPage . ' offset ' . $offset,
            $params
        );

        foreach ($items as $i => $row) {
            $items[$i]['payload'] = UserService::decodeJsonArray($row['payload']);
        }

        return array('items' => $items, 'total' => $total);
    }

    /**
     * Tab 5 — lớp đang hoạt động còn ít hơn 5 buổi.
     *
     * @param array $filters q (mã lớp, mã GV)
     * @param int   $page
     * @param int   $perPage
     * @return array array('items' => array, 'total' => int)
     */
    public function listLowSessionClasses(array $filters = array(), $page = 1, $perPage = 20)
    {
        $page    = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset  = ($page - 1) * $perPage;

        $where  = array('c.deleted_at is null', "c.status = ?", 'c.remaining_sessions < ?');
        $params = array('active', self::LOW_SESSION_THRESHOLD);

        $this->applyClassTeacherFilter($where, $params, isset($filters['q']) ? $filters['q'] : '');
        $whereSql = implode(' and ', $where);

        $countRow = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_classes c'
            . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
            . ' where ' . $whereSql,
            $params
        );
        $total = isset($countRow['c']) ? (int) $countRow['c'] : 0;

        $items = $this->db->safeFetch(
            'select c.id, c.class_code, c.total_sessions, c.remaining_sessions, c.teacher_id,'
            . ' t.teacher_code'
            . ' from db_lms_classes c'
            . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
            . ' where ' . $whereSql
            . ' order by c.remaining_sessions asc, c.total_sessions desc, c.id desc'
            . ' limit ' . $perPage . ' offset ' . $offset,
            $params
        );

        return array('items' => $items, 'total' => $total);
    }

    /**
     * Bộ lọc chung "Mã lớp, mã GV" cho các tab 2-5.
     *
     * @param array  $where
     * @param array  $params
     * @param string $q
     * @return void
     */
    private function applyClassTeacherFilter(array &$where, array &$params, $q)
    {
        $q = trim((string) $q);
        if ($q === '') {
            return;
        }
        $where[]  = '(c.class_code like ? or t.teacher_code like ?)';
        $like     = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
    }

    // ===================== Thao tác test =====================

    /**
     * Xác nhận (gửi) bài test đang draft → published.
     *
     * @param int $testId
     * @param int $adminId
     * @return bool
     */
    public function publishTest($testId, $adminId)
    {
        $this->clearErrors();
        $testId  = (int) $testId;
        $adminId = (int) $adminId;

        $before = $this->db->safeFetchOne(
            'select * from db_lms_tests where id = ? limit 1',
            array($testId)
        );
        if (empty($before) || !is_array($before)) {
            $this->addError('system', 'Bài test không tồn tại.');
            return false;
        }
        if ((string) $before['status'] !== 'draft') {
            $this->addError('status', 'Bài test đã được xử lý.');
            return false;
        }

        $updateData = array('status' => 'published');
        if (empty($before['test_file_url']) && !empty($before['topic_id'])) {
            $topic = $this->db->safeFetchOne('select test_file_url, minitest_file_url from db_lms_topics where id = ? limit 1', array((int) $before['topic_id']));
            if (!empty($topic)) {
                $fileUrl = (string) $before['test_type'] === 'minitest' ? $topic['minitest_file_url'] : $topic['test_file_url'];
                if (!empty($fileUrl)) {
                    $updateData['test_file_url'] = $fileUrl;
                }
            }
        }
        $ok = $this->db->update('db_lms_tests', $updateData, 'id = ?', array($testId));
        if (!$ok) {
            $this->addError('system', 'Không thể xác nhận bài test.');
            return false;
        }

        $after = $this->db->safeFetchOne('select * from db_lms_tests where id = ? limit 1', array($testId));
        $this->audit('publish_test', 'tests', $testId, $before, $after, 'admin');

        return true;
    }

    /**
     * Xoá bài test draft (kèm bài nộp nếu có).
     *
     * @param int $testId
     * @param int $adminId
     * @return bool
     */
    public function deleteTest($testId, $adminId)
    {
        $this->clearErrors();
        $testId  = (int) $testId;
        $adminId = (int) $adminId;

        $before = $this->db->safeFetchOne(
            'select * from db_lms_tests where id = ? limit 1',
            array($testId)
        );
        if (empty($before) || !is_array($before)) {
            $this->addError('system', 'Bài test không tồn tại.');
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->db->safeExec('delete from db_lms_test_submissions where test_id = ?', array($testId));
            $this->db->safeExec('delete from db_lms_tests where id = ?', array($testId));
            $this->audit('delete_test', 'tests', $testId, $before, null, 'admin');
            $this->db->commit();
            return true;
        } catch (\Exception $ex) {
            $this->db->rollBack();
            $this->addError('system', 'Không thể xoá bài test.');
            return false;
        }
    }

    // ===================== Chi tiết + chấm kết quả test =====================

    /**
     * Chi tiết KQ test của 1 HV trong 1 chuyên đề (Test + Minitest).
     *
     * @param int $classId
     * @param int $studentId
     * @param int $topicId
     * @return array|null
     */
    public function getTestResultDetail($classId, $studentId, $topicId)
    {
        $classId   = (int) $classId;
        $studentId = (int) $studentId;
        $topicId   = (int) $topicId;
        if ($classId <= 0 || $studentId <= 0 || $topicId <= 0) {
            return null;
        }

        $head = $this->db->safeFetchOne(
            'select c.id as class_id, c.class_code, c.total_sessions, c.remaining_sessions,'
            . ' t.teacher_code, t.full_name as teacher_name,'
            . ' s.student_code, s.full_name as student_name,'
            . ' top.name as topic_name, top.max_sessions as topic_max_sessions'
            . ' from db_lms_classes c'
            . ' join db_lms_students s on s.user_id = ?'
            . ' join db_lms_topics top on top.id = ?'
            . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
            . ' where c.id = ? and c.deleted_at is null'
            . ' limit 1',
            array($studentId, $topicId, $classId)
        );
        if (empty($head) || !is_array($head)) {
            return null;
        }

        $tests = $this->db->safeFetch(
            'select te.id, te.test_type, te.title, te.test_file_url, te.max_score, te.lesson_summary_url,'
            . ' te.status, te.deadline,'
            . ' ts.id as submission_id, ts.submission_files, ts.submitted_at,'
            . ' ts.submission_status, ts.score_x, ts.score_y_percent, ts.graded_at,'
            . ' ts.teacher_feedback_files'
            . ' from db_lms_tests te'
            . ' left join db_lms_test_submissions ts'
            . ' on ts.test_id = te.id and ts.student_id = ?'
            . ' where te.class_id = ? and te.topic_id = ?'
            . " and (te.status <> 'draft' or ts.id is not null)"
            . ' order by te.test_type, te.deadline, te.id',
            array($studentId, $classId, $topicId)
        );

        foreach ($tests as $i => $t) {
            $tests[$i]['submission_files'] = UserService::decodeJsonArray(
                isset($t['submission_files']) ? $t['submission_files'] : array()
            );
            $tests[$i]['teacher_feedback_files'] = UserService::decodeJsonArray(
                isset($t['teacher_feedback_files']) ? $t['teacher_feedback_files'] : array()
            );
        }

        // Logic nghiệp vụ LMS: Mỗi chuyên đề chỉ có tối đa 1 'test' và 1 'minitest'.
        // Phòng thủ chống trùng lặp dữ liệu seed: nhóm theo test_type.
        $dedup = array();
        foreach ($tests as $t) {
            $type = (string) $t['test_type'];
            if (!isset($dedup[$type])) {
                $dedup[$type] = $t;
            } elseif (empty($dedup[$type]['submission_id']) && !empty($t['submission_id'])) {
                $dedup[$type] = $t;
            }
        }
        $tests = array_values($dedup);

        $head['done_sessions'] = $this->countTopicSessions($classId, $topicId);
        $head['max_sessions']  = $head['topic_max_sessions'] === null
            ? null
            : (int) $head['topic_max_sessions'];

        return array('head' => $head, 'tests' => $tests);
    }

    /**
     * Gắn số buổi đã dạy / tối đa của chuyên đề vào từng dòng danh sách.
     *
     * @param array $items
     * @return void
     */
    private function attachTopicProgress(array &$items)
    {
        foreach ($items as $i => $row) {
            $topicId = isset($row['current_topic_id']) && $row['current_topic_id'] !== null
                ? (int) $row['current_topic_id']
                : (isset($row['topic_id']) ? (int) $row['topic_id'] : 0);

            if ($topicId <= 0) {
                $items[$i]['topic_done'] = null;
                $items[$i]['topic_max']  = null;
                continue;
            }

            $topic = $this->db->safeFetchOne(
                'select max_sessions from db_lms_topics where id = ? limit 1',
                array($topicId)
            );
            $items[$i]['topic_done'] = $this->countTopicSessions((int) $row['class_id'], $topicId);
            $items[$i]['topic_max']  = (empty($topic) || $topic['max_sessions'] === null)
                ? null
                : (int) $topic['max_sessions'];
        }
    }

    /**
     * Đếm số buổi đã dạy của chuyên đề trong lớp (topic_id hoặc topic_ids JSON).
     *
     * @param int $classId
     * @param int $topicId
     * @return int
     */
    private function countTopicSessions($classId, $topicId)
    {
        $rows = $this->db->safeFetch(
            'select topic_id, topic_ids from db_lms_sessions'
            . ' where class_id = ? and status = ?',
            array((int) $classId, 'finished')
        );
        $count = 0;
        foreach ($rows as $r) {
            if ((int) $r['topic_id'] === (int) $topicId) {
                $count++;
                continue;
            }
            $ids = UserService::decodeJsonArray(isset($r['topic_ids']) ? $r['topic_ids'] : array());
            foreach ($ids as $id) {
                if ((int) $id === (int) $topicId) {
                    $count++;
                    break;
                }
            }
        }
        return $count;
    }

    /**
     * Cập nhật điểm / file chấm cho 1 bài nộp.
     *
     * @param int        $submissionId
     * @param int        $adminId
     * @param array      $in    score_x, score_y_percent
     * @param array|null $files $_FILES['graded_files']
     * @param bool       $approve true = đánh dấu đã chấm (graded_at)
     * @return bool
     */
    public function updateSubmission($submissionId, $adminId, array $in, $files = null, $approve = false)
    {
        $this->clearErrors();
        $submissionId = (int) $submissionId;

        $before = $this->db->safeFetchOne(
            'select * from db_lms_test_submissions where id = ? limit 1',
            array($submissionId)
        );
        if (empty($before) || !is_array($before)) {
            $this->addError('system', 'Bài nộp không tồn tại.');
            return false;
        }

        $test = $this->db->safeFetchOne(
            'select id, max_score from db_lms_tests where id = ? limit 1',
            array((int) $before['test_id'])
        );
        $maxScore = !empty($test) ? (float) $test['max_score'] : 20.0;

        $rawScoreX = isset($in['score_x']) ? trim((string) $in['score_x']) : '';
        $rawScoreY = isset($in['score_y_percent']) ? trim((string) $in['score_y_percent']) : '';

        $scoreX = null;
        $scoreY = null;
        if ($rawScoreX !== '') {
            if (strpos($rawScoreX, '/') !== false) {
                $parts = explode('/', $rawScoreX);
                $p0 = trim($parts[0]);
                if (!is_numeric($p0)) {
                    $this->addError('score_x', 'Điểm không hợp lệ.');
                    return false;
                }
                $scoreX = (float) $p0;
                if (isset($parts[1]) && trim($parts[1]) !== '' && is_numeric(trim($parts[1])) && (float) trim($parts[1]) > 0) {
                    $maxScore = (float) trim($parts[1]);
                }
            } elseif (is_numeric($rawScoreX)) {
                $scoreX = (float) $rawScoreX;
            } else {
                $this->addError('score_x', 'Điểm không hợp lệ.');
                return false;
            }
            if ($scoreX < 0 || $scoreX > $maxScore) {
                $this->addError('score_x', 'Điểm phải nằm trong khoảng 0 - ' . $maxScore . '.');
                return false;
            }
        }

        if ($rawScoreY !== '') {
            $cleanY = trim(str_replace('%', '', $rawScoreY));
            if (!is_numeric($cleanY)) {
                $this->addError('score_y_percent', 'Phần trăm không hợp lệ.');
                return false;
            }
            $scoreY = (float) $cleanY;
            if ($scoreY < 0 || $scoreY > 100) {
                $this->addError('score_y_percent', 'Phần trăm phải nằm trong khoảng 0 - 100.');
                return false;
            }
        } elseif ($scoreX !== null && $maxScore > 0) {
            $scoreY = round(($scoreX / $maxScore) * 100, 2);
        }

        if ($scoreX === null && $scoreY !== null && $maxScore > 0) {
            $scoreX = round(($scoreY / 100) * $maxScore, 2);
        }

        // Fallback về điểm đã lưu của giáo viên khi bấm Xác nhận nhanh từ bảng danh sách
        if ($scoreX === null && !isset($in['score_x']) && $before['score_x'] !== null) {
            $scoreX = (float) $before['score_x'];
        }
        if ($scoreY === null && !isset($in['score_y_percent']) && $before['score_y_percent'] !== null) {
            $scoreY = (float) $before['score_y_percent'];
        }
        if ($scoreX !== null && $scoreY === null && $maxScore > 0) {
            $scoreY = round(($scoreX / $maxScore) * 100, 2);
        }
        if ($scoreX === null && $scoreY !== null && $maxScore > 0) {
            $scoreX = round(($scoreY / 100) * $maxScore, 2);
        }

        if ($approve && ($scoreX === null || $scoreY === null)) {
            $this->addError('score_x', 'Vui lòng nhập đủ điểm và phần trăm trước khi xác nhận.');
            return false;
        }

        $data = array(
            'score_x'         => $scoreX,
            'score_y_percent' => $scoreY,
        );

        if ($files !== null) {
            $res = lms_save_files($files, 'test-graded/' . date('Ym'), array('maxFiles' => 4));
            if (empty($res['ok'])) {
                $this->addError('graded_files', $res['error'] !== '' ? $res['error'] : 'Không lưu được file chấm.');
                return false;
            }
            if (!empty($res['paths'])) {
                $existingFeedback = array();
                if (!empty($before['teacher_feedback_files'])) {
                    $existingFeedback = \Lms\UserService::decodeJsonArray($before['teacher_feedback_files']);
                }
                $mergedFeedback = array_values(array_unique(array_merge($existingFeedback, $res['paths'])));
                $data['teacher_feedback_files'] = json_encode($mergedFeedback, JSON_UNESCAPED_UNICODE);
            }
        }

        if ($approve) {
            $data['graded_at'] = $this->now();
        }

        $ok = $this->db->update('db_lms_test_submissions', $data, 'id = ?', array($submissionId));
        if (!$ok) {
            $this->addError('system', 'Không thể cập nhật kết quả test.');
            return false;
        }

        $after = $this->db->safeFetchOne(
            'select * from db_lms_test_submissions where id = ? limit 1',
            array($submissionId)
        );
        $this->audit($approve ? 'approve_test_result' : 'update_test_result', 'test_submissions', $submissionId, $before, $after, 'admin');

        return true;
    }

    /**
     * Xoá bài nộp (HV có thể nộp lại).
     *
     * @param int $submissionId
     * @param int $adminId
     * @return bool
     */
    public function deleteSubmission($submissionId, $adminId)
    {
        $this->clearErrors();
        $submissionId = (int) $submissionId;

        $before = $this->db->safeFetchOne(
            'select * from db_lms_test_submissions where id = ? limit 1',
            array($submissionId)
        );
        if (empty($before) || !is_array($before)) {
            $this->addError('system', 'Bài nộp không tồn tại.');
            return false;
        }

        $this->db->safeExec('delete from db_lms_test_submissions where id = ?', array($submissionId));
        $this->audit('delete_test_submission', 'test_submissions', $submissionId, $before, null, 'admin');

        return true;
    }

    // ===================== Xoá request =====================

    /**
     * Xoá request đang chờ duyệt (admin huỷ yêu cầu).
     *
     * @param int $requestId
     * @param int $adminId
     * @return bool
     */
    public function deleteRequest($requestId, $adminId)
    {
        $this->clearErrors();
        $requestId = (int) $requestId;

        $before = $this->db->safeFetchOne(
            'select * from db_lms_requests where id = ? limit 1',
            array($requestId)
        );
        if (empty($before) || !is_array($before)) {
            $this->addError('system', 'Yêu cầu không tồn tại.');
            return false;
        }
        if ((string) $before['status'] !== 'pending') {
            $this->addError('status', 'Yêu cầu đã được xử lý.');
            return false;
        }

        $this->db->safeExec('delete from db_lms_requests where id = ?', array($requestId));
        $this->audit('delete_request', 'requests', $requestId, $before, null, 'admin');

        return true;
    }
}
