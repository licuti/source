<?php
/**
 * RequestService — GV tạo request (TCH-01/02, mockup §4.5 + §4.6).
 *
 * Module này KHÔNG insert session (BR-SCHED-02): GV chỉ tạo db_lms_requests
 * status 'pending'; admin duyệt ở ADM-05 (lms-sap-xep-lop) mới insert session.
 * Module này cũng không check trùng lịch — admin duyệt sẽ check BR-SCHED-01.
 *
 * 3 loại request:
 *  - add_session     : thêm buổi cho 1 HV đang active trong lớp (payload date/time/note)
 *  - request_leave   : xin nghỉ buổi học (payload reason, makeup_date?)
 *  - change_schedule : đổi lịch cố định (payload slots ≤ 2, từ tuần kế tiếp)
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class RequestService extends LmsService
{
    const REQUEST_TYPES = array('add_session', 'request_leave', 'change_schedule');

    /**
     * Toàn bộ loại request mà schema cho phép (migration core:233) — dùng cho
     * UI filter/render. Chỉ 3 loại trong REQUEST_TYPES mới có flow duyệt.
     */
    const ALL_REQUEST_TYPES = array(
        'add_session'     => 'Thêm buổi học',
        'send_test'       => 'Gửi đề test',
        'change_schedule' => 'Đổi lịch cố định',
        'request_leave'   => 'Xin nghỉ buổi',
        'request_bonus'   => 'Yêu cầu thưởng',
        'other'           => 'Khác',
    );

    const REQUEST_STATUSES = array(
        'pending'  => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối',
    );

    /**
     * Tạo request mới (row-level: class phải thuộc GV).
     *
     * @param int   $teacherId
     * @param array $in request_type, class_id, session_id, student_id + payload
     * @return int|null request id hoặc null khi lỗi
     */
    public function createRequest($teacherId, array $in)
    {
        $this->clearErrors();

        $teacherId = (int) $teacherId;
        if ($teacherId <= 0) {
            $this->addError('system', 'Giáo viên không hợp lệ.');
            return null;
        }

        $requestType = isset($in['request_type']) ? (string) $in['request_type'] : '';
        if (!in_array($requestType, self::REQUEST_TYPES, true)) {
            $this->addError('request_type', 'Loại yêu cầu không hợp lệ.');
            return null;
        }

        $classId = isset($in['class_id']) ? (int) $in['class_id'] : 0;
        if ($classId <= 0) {
            $this->addError('class_id', 'Vui lòng chọn lớp học.');
            return null;
        }

        // Row-level: lớp phải thuộc GV này và chưa xoá
        $class = $this->db->safeFetchOne(
            'select id, class_code, teacher_id, subject_id from db_lms_classes'
            . ' where id = ? and teacher_id = ? and deleted_at is null limit 1',
            array($classId, $teacherId)
        );
        if (empty($class) || !is_array($class)) {
            $this->addError('class_id', 'Lớp học không tồn tại hoặc không phải lớp của bạn.');
            return null;
        }

        $today = date('Y-m-d');
        $payload = array();
        $sessionId = null;
        $studentId = null;
        $title = '';

        if ($requestType === 'add_session') {
            // HV phải đang active trong lớp
            $studentId = isset($in['student_id']) ? (int) $in['student_id'] : 0;
            if ($studentId <= 0) {
                $this->addError('student_id', 'Vui lòng chọn học viên.');
                return null;
            }
            $enrolled = $this->db->safeFetchOne(
                'select id from db_lms_class_students'
                . ' where class_id = ? and student_id = ? and status = ? limit 1',
                array($classId, $studentId, 'active')
            );
            if (empty($enrolled)) {
                $this->addError('student_id', 'Học viên không đang học trong lớp này.');
                return null;
            }

            $sessionDate = isset($in['session_date']) ? trim((string) $in['session_date']) : '';
            $startTime   = isset($in['start_time']) ? trim((string) $in['start_time']) : '';
            $endTime     = isset($in['end_time']) ? trim((string) $in['end_time']) : '';

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sessionDate)) {
                $this->addError('session_date', 'Ngày học sai định dạng (YYYY-MM-DD).');
                return null;
            }
            if (strcmp($sessionDate, $today) < 0) {
                $this->addError('session_date', 'Ngày học phải từ hôm nay trở đi.');
                return null;
            }
            if (!\Lms\UserService::isValidTime($startTime) || !\Lms\UserService::isValidTime($endTime)) {
                $this->addError('start_time', 'Khung giờ sai định dạng (HH:MM).');
                return null;
            }
            if (strcmp($startTime, $endTime) >= 0) {
                $this->addError('end_time', 'Giờ kết thúc phải sau giờ bắt đầu.');
                return null;
            }

            $payload = array(
                'session_date' => $sessionDate,
                'start_time'   => $startTime,
                'end_time'     => $endTime,
                'note'         => isset($in['note']) ? trim((string) $in['note']) : '',
            );
            $title = 'Thêm buổi học ' . $sessionDate . ' ' . $startTime . '-' . $endTime;
        } elseif ($requestType === 'request_leave') {
            // Buổi thuộc lớp, chưa diễn ra
            $sessionId = isset($in['session_id']) ? (int) $in['session_id'] : 0;
            if ($sessionId <= 0) {
                $this->addError('session_id', 'Vui lòng chọn buổi học cần nghỉ.');
                return null;
            }
            $session = $this->db->safeFetchOne(
                'select id, session_date from db_lms_sessions'
                . ' where id = ? and class_id = ? limit 1',
                array($sessionId, $classId)
            );
            if (empty($session) || !is_array($session)) {
                $this->addError('session_id', 'Buổi học không thuộc lớp này.');
                return null;
            }
            if (strcmp((string) $session['session_date'], $today) < 0) {
                $this->addError('session_id', 'Không thể xin nghỉ buổi đã diễn ra.');
                return null;
            }

            $reason = isset($in['reason']) ? trim((string) $in['reason']) : '';
            if ($reason === '') {
                $this->addError('reason', 'Vui lòng nhập lý do xin nghỉ.');
                return null;
            }
            $makeupDate = isset($in['makeup_date']) ? trim((string) $in['makeup_date']) : '';
            if ($makeupDate !== '') {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $makeupDate)) {
                    $this->addError('makeup_date', 'Ngày bù sai định dạng (YYYY-MM-DD).');
                    return null;
                }
                if (strcmp($makeupDate, $today) < 0) {
                    $this->addError('makeup_date', 'Ngày bù phải từ hôm nay trở đi.');
                    return null;
                }
            }

            $payload = array(
                'reason'      => $reason,
                'makeup_date' => $makeupDate,
                'session_date' => (string) $session['session_date'],
            );
            $title = 'Xin nghỉ buổi ' . (string) $session['session_date'];
        } else { // change_schedule
            // BR-SCHED-02: lịch mới từ tuần kế tiếp trở đi (date >= monday + 7 ngày)
            $slots = array();
            $rawSlots = isset($in['slots']) ? (array) $in['slots'] : array();
            foreach ($rawSlots as $slot) {
                if (!is_array($slot)) {
                    continue;
                }
                $date  = isset($slot['date']) ? trim((string) $slot['date']) : '';
                $start = isset($slot['start']) ? trim((string) $slot['start']) : '';
                $end   = isset($slot['end']) ? trim((string) $slot['end']) : '';

                if ($date === '' && $start === '' && $end === '') {
                    continue;
                }
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    $this->addError('slots', 'Ngày trong lịch mới sai định dạng (YYYY-MM-DD).');
                    return null;
                }
                $monday = date('Y-m-d', strtotime('monday this week'));
                if (strcmp($date, date('Y-m-d', strtotime('+7 days', strtotime($monday)))) < 0) {
                    $this->addError('slots', 'Lịch mới chỉ áp dụng từ tuần kế tiếp trở đi.');
                    return null;
                }
                if (!\Lms\UserService::isValidTime($start) || !\Lms\UserService::isValidTime($end)) {
                    $this->addError('slots', 'Khung giờ sai định dạng (HH:MM).');
                    return null;
                }
                if (strcmp($start, $end) >= 0) {
                    $this->addError('slots', 'Giờ kết thúc phải sau giờ bắt đầu.');
                    return null;
                }
                $slots[] = array('date' => $date, 'start' => $start, 'end' => $end);
            }
            if (count($slots) === 0) {
                $this->addError('slots', 'Vui lòng nhập ít nhất một khung giờ mới.');
                return null;
            }
            if (count($slots) > 2) {
                $this->addError('slots', 'Chỉ được đổi tối đa 2 buổi.');
                return null;
            }

            $payload = array('slots' => $slots);
            $title = 'Đổi lịch cố định (' . count($slots) . ' buổi)';
        }

        $ok = $this->db->insert('db_lms_requests', array(
            'request_type' => $requestType,
            'class_id'     => $classId,
            'session_id'   => $sessionId,
            'teacher_id'   => $teacherId,
            'student_id'   => $studentId,
            'title'        => $title,
            'payload'      => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status'       => 'pending',
        ));
        if (!$ok) {
            $this->addError('system', 'Không thể tạo yêu cầu. Vui lòng thử lại.');
            return null;
        }

        $id = (int) $this->db->insertId;
        $this->audit('create_request', 'requests', $id, null, $payload, 'teacher');

        return $id;
    }

    // ===================== ADM-05 — Admin duyệt/từ chối =====================

    /**
     * Đếm request đang chờ duyệt (badge header/sidebar).
     *
     * @return int
     */
    public function countPending()
    {
        return (int) $this->db->count('db_lms_requests', 'status = ?', array('pending'));
    }

    /**
     * Danh sách request cho admin (filter + phân trang, 2 query — không N+1).
     *
     * @param array $filters status, request_type, teacher_id
     * @param int   $page
     * @param int   $perPage
     * @return array array('items' => rows, 'total' => int)
     */
    public function listRequestsForAdmin(array $filters = array(), $page = 1, $perPage = 20)
    {
        $page    = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset  = ($page - 1) * $perPage;

        $where  = array();
        $params = array();

        $status = isset($filters['status']) ? (string) $filters['status'] : '';
        if ($status !== '' && isset(self::REQUEST_STATUSES[$status])) {
            $where[]  = 'r.status = ?';
            $params[] = $status;
        }

        $requestType = isset($filters['request_type']) ? (string) $filters['request_type'] : '';
        if ($requestType !== '' && isset(self::ALL_REQUEST_TYPES[$requestType])) {
            $where[]  = 'r.request_type = ?';
            $params[] = $requestType;
        }

        $teacherId = isset($filters['teacher_id']) ? (int) $filters['teacher_id'] : 0;
        if ($teacherId > 0) {
            $where[]  = 'r.teacher_id = ?';
            $params[] = $teacherId;
        }

        $whereSql = empty($where) ? '1=1' : implode(' and ', $where);

        $countRow = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_requests r where ' . $whereSql,
            $params
        );
        $total = isset($countRow['c']) ? (int) $countRow['c'] : 0;

        $select = 'select r.id, r.request_type, r.class_id, r.session_id, r.teacher_id, r.student_id,'
            . ' r.title, r.payload, r.status, r.processed_by, r.processed_at, r.admin_note, r.created_at,'
            . ' t.full_name as teacher_name, t.teacher_code,'
            . ' c.class_code, c.name as class_name,'
            . ' s.full_name as student_name, s.student_code,'
            . ' ses.session_date, ses.start_time, ses.end_time, ses.status as session_status,'
            . ' a.full_name as processed_name'
            . ' from db_lms_requests r'
            . ' left join db_lms_teachers t on t.user_id = r.teacher_id'
            . ' left join db_lms_classes c on c.id = r.class_id'
            . ' left join db_lms_students s on s.user_id = r.student_id'
            . ' left join db_lms_sessions ses on ses.id = r.session_id'
            . ' left join db_lms_admins a on a.user_id = r.processed_by'
            . ' where ' . $whereSql
            . ' order by r.created_at desc, r.id desc'
            . ' limit ' . $perPage . ' offset ' . $offset;

        $items = $this->db->safeFetch($select, $params);

        return array('items' => $items, 'total' => $total);
    }

    /**
     * Chi tiết 1 request cho admin (join đầy đủ + payload đã decode).
     *
     * @param int $requestId
     * @return array|null
     */
    public function getRequestForAdmin($requestId)
    {
        $requestId = (int) $requestId;
        if ($requestId <= 0) {
            return null;
        }

        $select = 'select r.id, r.request_type, r.class_id, r.session_id, r.teacher_id, r.student_id,'
            . ' r.title, r.description, r.payload, r.status, r.processed_by, r.processed_at,'
            . ' r.admin_note, r.created_at,'
            . ' t.full_name as teacher_name, t.teacher_code, t.status as teacher_status,'
            . ' c.class_code, c.name as class_name, c.status as class_status,'
            . ' c.remaining_sessions, c.deleted_at as class_deleted_at,'
            . ' s.full_name as student_name, s.student_code,'
            . ' ses.session_date, ses.start_time, ses.end_time, ses.status as session_status,'
            . ' a.full_name as processed_name'
            . ' from db_lms_requests r'
            . ' left join db_lms_teachers t on t.user_id = r.teacher_id'
            . ' left join db_lms_classes c on c.id = r.class_id'
            . ' left join db_lms_students s on s.user_id = r.student_id'
            . ' left join db_lms_sessions ses on ses.id = r.session_id'
            . ' left join db_lms_admins a on a.user_id = r.processed_by'
            . ' where r.id = ?'
            . ' limit 1';

        $row = $this->db->safeFetchOne($select, array($requestId));
        if (empty($row) || !is_array($row)) {
            return null;
        }

        $row['payload'] = \Lms\UserService::decodeJsonArray(isset($row['payload']) ? $row['payload'] : array());
        $row['can_approve'] = in_array((string) $row['request_type'], self::REQUEST_TYPES, true);

        return $row;
    }

    /**
     * Duyệt request (transaction + row lock, idempotent).
     *
     * @param int   $requestId
     * @param int   $adminId  id user admin (db_lms_admins.user_id)
     * @param array $in       admin_note (tuỳ chọn)
     * @return bool
     */
    public function approveRequest($requestId, $adminId, array $in = array())
    {
        $this->clearErrors();

        $requestId = (int) $requestId;
        $adminId   = (int) $adminId;
        if ($requestId <= 0) {
            $this->addError('system', 'Thiếu ID yêu cầu.');
            return false;
        }
        if ($adminId <= 0) {
            $this->addError('system', 'Phiên đăng nhập không hợp lệ.');
            return false;
        }

        $sessionSvc = new \Lms\SessionService($this->db);

        $this->db->beginTransaction();
        try {
            $before = $this->db->safeFetchOne(
                'select * from db_lms_requests where id = ? limit 1',
                array($requestId)
            );
            if (empty($before) || !is_array($before)) {
                $this->db->rollBack();
                $this->addError('system', 'Yêu cầu không tồn tại.');
                return false;
            }
            if ((string) $before['status'] !== 'pending') {
                $this->db->rollBack();
                $this->addError('status', 'Yêu cầu đã được xử lý.');
                return false;
            }
            if (!in_array((string) $before['request_type'], self::REQUEST_TYPES, true)) {
                $this->db->rollBack();
                $this->addError('request_type', 'Loại yêu cầu này chưa hỗ trợ duyệt.');
                return false;
            }

            $classId   = (int) $before['class_id'];
            $teacherId = (int) $before['teacher_id'];

            // Lock class row (BR race 2 admin duyệt cùng lúc)
            $class = $this->db->safeFetchOne(
                'select id, class_code, teacher_id, status, remaining_sessions, deleted_at'
                . ' from db_lms_classes where id = ? for update',
                array($classId)
            );
            if (empty($class) || !is_array($class) || $class['deleted_at'] !== null) {
                $this->db->rollBack();
                $this->addError('class_id', 'Lớp học không tồn tại hoặc đã bị xoá.');
                return false;
            }
            if ((string) $class['status'] !== 'active') {
                $this->db->rollBack();
                $this->addError('class_id', 'Lớp học hiện không hoạt động.');
                return false;
            }

            $teacher = $this->db->safeFetchOne(
                'select user_id from db_lms_teachers where user_id = ? and status = ? limit 1',
                array($teacherId, 'active')
            );
            if (empty($teacher) || !is_array($teacher)) {
                $this->db->rollBack();
                $this->addError('teacher_id', 'Giáo viên không còn hoạt động.');
                return false;
            }

            $payload = \Lms\UserService::decodeJsonArray(isset($before['payload']) ? $before['payload'] : array());

            $remainingDelta = 0;

            if ((string) $before['request_type'] === 'add_session') {
                $sessionDate = isset($payload['session_date']) ? trim((string) $payload['session_date']) : '';
                $startTime   = isset($payload['start_time']) ? trim((string) $payload['start_time']) : '';
                $endTime     = isset($payload['end_time']) ? trim((string) $payload['end_time']) : '';

                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sessionDate)
                    || !\Lms\UserService::isValidTime($startTime)
                    || !\Lms\UserService::isValidTime($endTime)) {
                    $this->db->rollBack();
                    $this->addError('payload', 'Dữ liệu buổi học không hợp lệ.');
                    return false;
                }
                if (strcmp($startTime, $endTime) >= 0) {
                    $this->db->rollBack();
                    $this->addError('payload', 'Giờ kết thúc phải sau giờ bắt đầu.');
                    return false;
                }
                if ($sessionSvc->hasTeacherScheduleConflict($teacherId, $sessionDate, $startTime, $endTime)) {
                    $this->db->rollBack();
                    $this->addError('payload', 'Giáo viên đã có buổi học trùng khung giờ này.');
                    return false;
                }
                if ((int) $class['remaining_sessions'] < 1) {
                    $this->db->rollBack();
                    $this->addError('class_id', 'Lớp đã hết số buổi còn lại.');
                    return false;
                }

                $ok = $this->db->insert('db_lms_sessions', array(
                    'class_id'     => $classId,
                    'teacher_id'   => $teacherId,
                    'session_date' => $sessionDate,
                    'start_time'   => $startTime,
                    'end_time'     => $endTime,
                    'status'       => 'upcoming',
                ));
                if (!$ok) {
                    throw new \RuntimeException('Không thể tạo buổi học.');
                }
                $newSessionId = (int) $this->db->insertId;
                $sessionSvc->ensureSessionStudents($newSessionId);
                $remainingDelta = 1;
            } elseif ((string) $before['request_type'] === 'request_leave') {
                $sessionId = (int) $before['session_id'];
                $session = $this->db->safeFetchOne(
                    'select id, session_date, status from db_lms_sessions'
                    . ' where id = ? and class_id = ? for update',
                    array($sessionId, $classId)
                );
                if (empty($session) || !is_array($session)) {
                    $this->db->rollBack();
                    $this->addError('session_id', 'Buổi học không thuộc lớp này.');
                    return false;
                }
                if (strcmp((string) $session['session_date'], date('Y-m-d')) < 0) {
                    $this->db->rollBack();
                    $this->addError('session_id', 'Không thể huỷ buổi đã diễn ra.');
                    return false;
                }
                if ((string) $session['status'] === 'cancelled') {
                    $this->db->rollBack();
                    $this->addError('session_id', 'Buổi học này đã bị huỷ.');
                    return false;
                }

                $ok = $this->db->update(
                    'db_lms_sessions',
                    array('status' => 'cancelled'),
                    'id = ?',
                    array($sessionId)
                );
                if (!$ok) {
                    throw new \RuntimeException('Không thể huỷ buổi học.');
                }
            } else { // change_schedule
                $slots = isset($payload['slots']) && is_array($payload['slots']) ? $payload['slots'] : array();
                if (count($slots) === 0) {
                    $this->db->rollBack();
                    $this->addError('payload', 'Không có khung giờ mới.');
                    return false;
                }

                $monday = date('Y-m-d', strtotime('monday this week'));
                $minDate = date('Y-m-d', strtotime('+7 days', strtotime($monday)));

                // Validate toàn bộ slot trước khi insert (all-or-nothing)
                foreach ($slots as $slot) {
                    if (!is_array($slot)) {
                        $this->db->rollBack();
                        $this->addError('payload', 'Dữ liệu lịch mới không hợp lệ.');
                        return false;
                    }
                    $date  = isset($slot['date']) ? trim((string) $slot['date']) : '';
                    $start = isset($slot['start']) ? trim((string) $slot['start']) : '';
                    $end   = isset($slot['end']) ? trim((string) $slot['end']) : '';

                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                        || !\Lms\UserService::isValidTime($start)
                        || !\Lms\UserService::isValidTime($end)) {
                        $this->db->rollBack();
                        $this->addError('payload', 'Dữ liệu lịch mới không hợp lệ.');
                        return false;
                    }
                    if (strcmp($date, $minDate) < 0) {
                        $this->db->rollBack();
                        $this->addError('payload', 'Lịch mới chỉ áp dụng từ tuần kế tiếp trở đi.');
                        return false;
                    }
                    if (strcmp($start, $end) >= 0) {
                        $this->db->rollBack();
                        $this->addError('payload', 'Giờ kết thúc phải sau giờ bắt đầu.');
                        return false;
                    }
                    if ($sessionSvc->hasTeacherScheduleConflict($teacherId, $date, $start, $end)) {
                        $this->db->rollBack();
                        $this->addError('payload', 'Giáo viên đã có buổi học trùng khung giờ ' . $date . ' ' . $start . '-' . $end . '.');
                        return false;
                    }
                }

                if ((int) $class['remaining_sessions'] < count($slots)) {
                    $this->db->rollBack();
                    $this->addError('class_id', 'Lớp không còn đủ buổi cho lịch mới.');
                    return false;
                }

                foreach ($slots as $slot) {
                    $ok = $this->db->insert('db_lms_sessions', array(
                        'class_id'     => $classId,
                        'teacher_id'   => $teacherId,
                        'session_date' => trim((string) $slot['date']),
                        'start_time'   => trim((string) $slot['start']),
                        'end_time'     => trim((string) $slot['end']),
                        'status'       => 'upcoming',
                    ));
                    if (!$ok) {
                        throw new \RuntimeException('Không thể tạo buổi học.');
                    }
                    $sessionSvc->ensureSessionStudents((int) $this->db->insertId);
                }
                $remainingDelta = count($slots);
            }

            if ($remainingDelta > 0) {
                // Giảm remaining dạng guarded (race-safe) — check affected rows
                $stmt = $this->db->pdo()->prepare(
                    'update db_lms_classes set remaining_sessions = remaining_sessions - ?'
                    . ' where id = ? and remaining_sessions >= ?'
                );
                $stmt->execute(array($remainingDelta, $classId, $remainingDelta));
                if ((int) $stmt->rowCount() !== 1) {
                    throw new \RuntimeException('Không đủ buổi còn lại trong lớp.');
                }
            }

            $adminNote = isset($in['admin_note']) ? trim((string) $in['admin_note']) : '';

            $ok = $this->db->update('db_lms_requests', array(
                'status'       => 'approved',
                'processed_by' => $adminId,
                'processed_at' => $this->now(),
                'admin_note'   => $adminNote !== '' ? $adminNote : null,
            ), 'id = ?', array($requestId));
            if (!$ok) {
                throw new \RuntimeException('Không thể cập nhật yêu cầu.');
            }

            $after = $this->db->safeFetchOne(
                'select * from db_lms_requests where id = ? limit 1',
                array($requestId)
            );
            $this->audit('approve_request', 'requests', $requestId, $before, $after, 'admin');

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->addError('system', $e->getMessage() !== '' ? $e->getMessage() : 'Không thể duyệt yêu cầu. Vui lòng thử lại.');
            return false;
        }
    }

    /**
     * Từ chối request (admin_note required ≥ 5 ký tự).
     *
     * @param int   $requestId
     * @param int   $adminId
     * @param array $in admin_note
     * @return bool
     */
    public function rejectRequest($requestId, $adminId, array $in)
    {
        $this->clearErrors();

        $requestId = (int) $requestId;
        $adminId   = (int) $adminId;
        if ($requestId <= 0) {
            $this->addError('system', 'Thiếu ID yêu cầu.');
            return false;
        }
        if ($adminId <= 0) {
            $this->addError('system', 'Phiên đăng nhập không hợp lệ.');
            return false;
        }

        $note = isset($in['admin_note']) ? trim((string) $in['admin_note']) : '';
        $noteLen = function_exists('mb_strlen') ? mb_strlen($note, 'UTF-8') : strlen($note);
        if ($noteLen < 5) {
            $this->addError('admin_note', 'Vui lòng nhập lý do từ chối (tối thiểu 5 ký tự).');
            return false;
        }

        $this->db->beginTransaction();
        try {
            $before = $this->db->safeFetchOne(
                'select * from db_lms_requests where id = ? for update',
                array($requestId)
            );
            if (empty($before) || !is_array($before)) {
                $this->db->rollBack();
                $this->addError('system', 'Yêu cầu không tồn tại.');
                return false;
            }
            if ((string) $before['status'] !== 'pending') {
                $this->db->rollBack();
                $this->addError('status', 'Yêu cầu đã được xử lý.');
                return false;
            }

            $ok = $this->db->update('db_lms_requests', array(
                'status'       => 'rejected',
                'processed_by' => $adminId,
                'processed_at' => $this->now(),
                'admin_note'   => $note,
            ), 'id = ?', array($requestId));
            if (!$ok) {
                throw new \RuntimeException('Không thể cập nhật yêu cầu.');
            }

            $after = $this->db->safeFetchOne(
                'select * from db_lms_requests where id = ? limit 1',
                array($requestId)
            );
            $this->audit('reject_request', 'requests', $requestId, $before, $after, 'admin');

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->addError('system', $e->getMessage() !== '' ? $e->getMessage() : 'Không thể từ chối yêu cầu. Vui lòng thử lại.');
            return false;
        }
    }
}
