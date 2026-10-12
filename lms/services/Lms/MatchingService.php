<?php
/**
 * MatchingService — Sắp xếp lớp (ADM-04, Phase 3).
 *
 * SRS §ADM-04: gợi ý GV phù hợp cho học viên chưa có lớp (status
 * pending_arrange), tính điểm match rồi tạo lớp 1-1 tự động với cấu hình mặc định.
 *
 * Hard filter (GV phải thoả tất cả):
 *  - status = active.
 *  - Môn: nếu HV có subject_id → teachable_subjects phải chứa môn đó
 *    (teachable_subjects rỗng/NULL = không giới hạn môn).
 *  - Giới tính: nếu HV có preferred_teacher_gender → gender GV phải khớp.
 *  - Khu vực: HV offline + có district → district ∈ teachable_districts
 *    (rỗng/NULL = không giới hạn quận). HV online → can_teach_online = 1.
     *  - Lịch: ≥ 1 khung giờ rảnh của HV giao với lịch trống GV đang hiệu lực
     *    (sau khi trừ lịch cố định của lớp đang dạy — BR-SCHED-01).
     *  - Sức chứa: số lớp active hiện tại < max_classes (max_classes = 0: không giới hạn).
 *
 * Điểm match (0-100, làm tròn, chặn 100):
 *  - 50: tỉ lệ khung giờ rảnh của HV khớp lịch trống GV.
 *  - 15: đúng khu vực (offline) / dạy được online (online).
 *  - 20 | 12 | 5: hạng green | gold | bronze.
 *  - 15: tỉ lệ sức chứa còn trống.
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class MatchingService extends LmsService
{
    const SCORE_SCHEDULE = 50;
    const SCORE_LOCATION = 15;
    const SCORE_CAPACITY = 15;

    /** Điểm thưởng theo hạng GV (mockup: Xanh > Vàng > Đồng). */
    const RANK_SCORES = array(
        'green'  => 20,
        'gold'   => 12,
        'bronze' => 5,
    );

    /** Cấu hình lớp mặc định khi tạo từ match (SRS ADM-04 bước 4). */
    const DEFAULT_HW_DEADLINE   = '12:00';
    const DEFAULT_HW_CHECK_HOURS = 24;

    /** Trạng thái HV sau khi đã xếp lớp (pipeline: xếp xong → chờ thu phí). */
    const STATUS_AFTER_ARRANGE = 'waiting_fee';

    /** Trạng thái HV (mirror UserService::STUDENT_STATUSES). */
    const STATUS_PENDING = 'pending_arrange';
    const STATUS_QUIT    = 'quit';

    /** @var ClassService */
    private $classes;

    /** @var ScheduleService */
    private $schedules;

    public function __construct(\LmsDb $db = null, ClassService $classes = null, ScheduleService $schedules = null)
    {
        parent::__construct($db);
        $this->classes   = $classes !== null ? $classes : new ClassService($this->db);
        $this->schedules = $schedules !== null ? $schedules : new ScheduleService($this->db);
    }

    /**
     * Danh sách học viên chờ xếp lớp: status pending_arrange VÀ chưa thuộc
     * lớp active nào.
     *
     * @param array $filters q, subject_id, district
     * @param int   $page
     * @param int   $perPage
     * @return array array('items' => array, 'total' => int)
     */
    public function listPendingStudents(array $filters = array(), $page = 1, $perPage = 20)
    {
        $page    = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset  = ($page - 1) * $perPage;

        $where = array('s.status = ?');
        $params = array(self::STATUS_PENDING);

        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $where[] = '(s.student_code like ? or s.full_name like ? or u.username like ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }

        $subjectId = isset($filters['subject_id']) ? (int) $filters['subject_id'] : 0;
        if ($subjectId > 0) {
            $where[] = 's.subject_id = ?';
            $params[] = $subjectId;
        }

        $district = isset($filters['district']) ? (string) $filters['district'] : '';
        if ($district !== '') {
            $where[] = 's.district = ?';
            $params[] = $district;
        }

        // Chưa thuộc lớp active nào (tránh xếp trùng)
        $where[] = 'not exists (select 1 from db_lms_class_students cs'
            . ' join db_lms_classes c on c.id = cs.class_id and c.deleted_at is null'
            . ' where cs.student_id = s.user_id and cs.status = "active")';

        $whereSql = implode(' and ', $where);

        $countRow = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_students s'
            . ' join db_lms_users u on u.id = s.user_id'
            . ' where ' . $whereSql,
            $params
        );
        $total = isset($countRow['c']) ? (int) $countRow['c'] : 0;

        $items = $this->db->safeFetch(
            'select s.user_id, s.student_code, s.full_name, s.grade, s.district, s.ward,'
            . ' s.learning_type, s.priority_number, s.subject_id, subj.name as subject_name,'
            . ' s.preferred_teacher_gender, s.free_time_slots, s.created_at, u.username'
            . ' from db_lms_students s'
            . ' join db_lms_users u on u.id = s.user_id'
            . ' left join db_lms_subjects subj on subj.id = s.subject_id'
            . ' where ' . $whereSql
            . ' order by s.priority_number desc, s.user_id asc'
            . ' limit ' . $offset . ', ' . $perPage,
            $params
        );

        foreach ($items as $i => $it) {
            $items[$i]['free_time_slots'] = \Lms\UserService::decodeJsonArray(
                isset($it['free_time_slots']) ? $it['free_time_slots'] : array()
            );
            $items[$i]['slot_count'] = count($items[$i]['free_time_slots']);
        }

        return array('items' => $items, 'total' => $total);
    }

    /**
     * Chi tiết 1 học viên chờ xếp (dùng cho pane phải của màn hình ADM-04).
     *
     * @param int $studentId
     * @return array rỗng khi không tồn tại
     */
    public function getPendingStudent($studentId)
    {
        $studentId = (int) $studentId;
        if ($studentId <= 0) {
            return array();
        }

        $row = $this->db->safeFetchOne(
            'select s.user_id, s.student_code, s.full_name, s.parent_name, s.parent_phone,'
            . ' s.grade, s.school_name, s.district, s.ward, s.current_address,'
            . ' s.learning_type, s.priority_number, s.status, s.note,'
            . ' s.subject_id, subj.name as subject_name, subj.program_id,'
            . ' s.preferred_teacher_gender, s.free_time_slots, u.username, u.phone'
            . ' from db_lms_students s'
            . ' join db_lms_users u on u.id = s.user_id'
            . ' left join db_lms_subjects subj on subj.id = s.subject_id'
            . ' where s.user_id = ? limit 1',
            array($studentId)
        );
        if (empty($row)) {
            return array();
        }

        $row['free_time_slots'] = \Lms\UserService::decodeJsonArray(
            isset($row['free_time_slots']) ? $row['free_time_slots'] : array()
        );
        return $row;
    }

    /**
     * Tính danh sách GV phù hợp cho 1 học viên, xếp hạng theo điểm match.
     *
     * @param int $studentId
     * @return array mảng GV (điểm giảm dần), rỗng khi HV không hợp lệ/không có slot
     */
    public function matchTeachers($studentId)
    {
        $this->clearErrors();

        $studentId = (int) $studentId;
        if ($studentId <= 0) {
            $this->addError('student_id', 'ID học viên không hợp lệ.');
            return array();
        }

        $student = $this->getPendingStudent($studentId);
        if (empty($student)) {
            $this->addError('student_id', 'Học viên không tồn tại.');
            return array();
        }
        if ((string) $student['status'] !== self::STATUS_PENDING) {
            $this->addError('student_id', 'Học viên không ở trạng thái chờ xếp lớp.');
            return array();
        }

        $slots = isset($student['free_time_slots']) && is_array($student['free_time_slots'])
            ? $student['free_time_slots']
            : array();
        $slots = $this->normalizeSlots($slots);
        if ($slots === array()) {
            $this->addError('free_time_slots', 'Học viên chưa có khung giờ rảnh nào.');
            return array();
        }

        $subjectId = isset($student['subject_id']) ? (int) $student['subject_id'] : 0;
        $district  = isset($student['district']) ? (string) $student['district'] : '';
        $isOnline  = (string) $student['learning_type'] === 'online';
        $wantGender = isset($student['preferred_teacher_gender'])
            ? (string) $student['preferred_teacher_gender']
            : '';

        $teachers = $this->db->safeFetch(
            'select user_id, teacher_code, full_name, gender, current_address,'
            . ' teachable_districts, teachable_subjects, can_teach_online, rank, max_classes, status'
            . ' from db_lms_teachers where status = ? order by teacher_code',
            array('active')
        );
        if ($teachers === array()) {
            return array();
        }

        // Số lớp active hiện tại của mỗi GV (1 query)
        $classCounts = array();
        $rows = $this->db->safeFetch(
            'select teacher_id, count(*) as cnt from db_lms_classes'
            . ' where status = ? and deleted_at is null group by teacher_id',
            array('active')
        );
        foreach ($rows as $r) {
            $classCounts[(int) $r['teacher_id']] = (int) $r['cnt'];
        }

        // Lịch trống hiện hành của mọi GV active (1 query)
        $freeSlotMap = array();
        $today = date('Y-m-d');
        $slotRows = $this->db->safeFetch(
            'select id, teacher_id, day_of_week, start_time, end_time'
            . ' from db_lms_teacher_free_slots'
            . ' where effective_from <= ? and (effective_to is null or effective_to >= ?)'
            . ' order by day_of_week, start_time',
            array($today, $today)
        );
        foreach ($slotRows as $r) {
            $freeSlotMap[(int) $r['teacher_id']][] = $r;
        }

        // Lịch cố định của lớp đang dạy (BR-SCHED-01): các khoảng này bị trừ
        // ra khỏi lịch trống khi ghép, tránh gợi ý GV rồi tạo lớp bị từ chối.
        $fixedSlotMap = array();
        $fixedRows = $this->db->safeFetch(
            'select c.teacher_id, s.day_of_week, s.start_time, s.end_time'
            . ' from db_lms_schedules s'
            . ' join db_lms_classes c on c.id = s.class_id'
            . '   and c.deleted_at is null and c.status = ?'
            . ' where s.effective_from <= ? and (s.effective_to is null or s.effective_to >= ?)',
            array('active', $today, $today)
        );
        foreach ($fixedRows as $r) {
            $fixedSlotMap[(int) $r['teacher_id']][] = $r;
        }

        $out = array();
        foreach ($teachers as $t) {
            $teacherId = (int) $t['user_id'];
            $matched = $this->matchOne(
                $t,
                isset($classCounts[$teacherId]) ? $classCounts[$teacherId] : 0,
                isset($freeSlotMap[$teacherId]) ? $freeSlotMap[$teacherId] : array(),
                isset($fixedSlotMap[$teacherId]) ? $fixedSlotMap[$teacherId] : array(),
                $slots,
                $subjectId,
                $district,
                $isOnline,
                $wantGender
            );
            if ($matched !== null) {
                $out[] = $matched;
            }
        }

        usort($out, array($this, 'compareMatches'));

        return $out;
    }

    /**
     * Chi tiết lịch tuần của 1 GV cho modal "Chi tiết lịch giáo viên" (mockup §4):
     * lịch cố định các lớp đang dạy (kèm tên HV) + lịch trống còn nhận thêm.
     *
     * @param int $teacherId
     * @return array array('teacher' => , 'days' => 2..8, 'schedules' => , 'free_slots' => )
     */
    public function teacherWeekDetail($teacherId)
    {
        $this->clearErrors();

        $teacherId = (int) $teacherId;
        if ($teacherId <= 0) {
            $this->addError('teacher_id', 'ID giáo viên không hợp lệ.');
            return array();
        }

        $teacher = $this->db->safeFetchOne(
            'select user_id, teacher_code, full_name, gender, current_address,'
            . ' teachable_districts, can_teach_online, rank, max_classes, status'
            . ' from db_lms_teachers where user_id = ? limit 1',
            array($teacherId)
        );
        if (empty($teacher)) {
            $this->addError('teacher_id', 'Giáo viên không tồn tại.');
            return array();
        }

        $teacher['current_classes'] = (int) $this->db->count(
            'db_lms_classes',
            'teacher_id = ? and status = ? and deleted_at is null',
            array($teacherId, 'active')
        );
        $teacher['teachable_districts'] = \Lms\UserService::decodeJsonArray(
            isset($teacher['teachable_districts']) ? $teacher['teachable_districts'] : array()
        );
        $teacher['teachable_subjects'] = \Lms\UserService::decodeJsonArray(
            isset($teacher['teachable_subjects']) ? $teacher['teachable_subjects'] : array()
        );

        $today = date('Y-m-d');

        $schedules = $this->db->safeFetch(
            'select s.day_of_week, s.start_time, s.end_time, c.id as class_id,'
            . ' c.class_code, c.name as class_name, c.class_type,'
            . ' (select group_concat(st.full_name order by st.full_name separator ", ")'
            . '    from db_lms_class_students cs'
            . '    join db_lms_students st on st.user_id = cs.student_id'
            . '   where cs.class_id = c.id and cs.status = "active") as student_names'
            . ' from db_lms_schedules s'
            . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
            . ' where c.teacher_id = ? and c.status = ?'
            . ' and (s.effective_to is null or s.effective_to >= ?)'
            . ' order by s.day_of_week, s.start_time',
            array($teacherId, 'active', $today)
        );

        return array(
            'teacher'     => $teacher,
            'days'        => \Lms\UserService::WEEKDAYS,
            'schedules'   => $schedules,
            'free_slots'  => $this->schedules->listFreeSlots($teacherId),
        );
    }

    /**
     * Tạo lớp 1-1 tự động từ kết quả match (SRS ADM-04 bước 4).
     *
     * Lớp dùng lịch = các khung giờ giao nhau giữa lịch rảnh HV và lịch trống
     * GV; cấu hình còn lại theo mặc định (admin sửa lại trong ADM-03).
     * Sau khi tạo: HV chuyển sang trạng thái chờ thu phí, các lịch trống GV
     * giao với lịch lớp mới sẽ được xoá (không nhận thêm lớp cùng giờ).
     *
     * @param int   $studentId
     * @param int   $teacherId
     * @param array $overrides price_per_session, total_sessions, name, start_date
     * @return int|null class id
     */
    public function createClassFromMatch($studentId, $teacherId, array $overrides = array())
    {
        $this->clearErrors();

        $studentId = (int) $studentId;
        $teacherId = (int) $teacherId;
        if ($studentId <= 0 || $teacherId <= 0) {
            $this->addError('system', 'Dữ liệu không hợp lệ.');
            return null;
        }

        $student = $this->getPendingStudent($studentId);
        if (empty($student)) {
            $this->addError('student_id', 'Học viên không tồn tại.');
            return null;
        }

        $inClass = (int) $this->db->count(
            'db_lms_class_students',
            'student_id = ? and status = ?',
            array($studentId, 'active')
        );
        if ($inClass > 0) {
            $this->addError('student_id', 'Học viên đã đang theo lớp, không thể xếp thêm.');
            return null;
        }
        if ((string) $student['status'] === self::STATUS_QUIT) {
            $this->addError('student_id', 'Học viên đã nghỉ học.');
            return null;
        }
        $subjectId = isset($student['subject_id']) ? (int) $student['subject_id'] : 0;
        if ($subjectId <= 0) {
            $this->addError('subject_id', 'Học viên chưa chọn môn học. Hãy cập nhật hồ sơ trước khi xếp lớp.');
            return null;
        }

        // GV phải nằm trong danh sách match (hard filter server-side)
        $matched = $this->matchTeachers($studentId);
        $picked = null;
        foreach ($matched as $m) {
            if ((int) $m['teacher_id'] === $teacherId) {
                $picked = $m;
                break;
            }
        }
        if ($picked === null) {
            $err = $this->errors();
            $this->addError(
                'teacher_id',
                isset($err['free_time_slots'])
                    ? $err['free_time_slots']
                    : 'Giáo viên không phù hợp với tiêu chí của học viên.'
            );
            return null;
        }

        $schedules = array();
        foreach ($picked['matched_slots'] as $slot) {
            $schedules[] = array(
                'day'   => (int) $slot['day'],
                'start' => (string) $slot['start'],
                'end'   => (string) $slot['end'],
            );
        }

        $subjectName = isset($student['subject_name']) ? (string) $student['subject_name'] : 'Lớp';
        $shortName = trim(preg_replace('/\s+/', ' ', (string) $student['full_name']));
        $name = isset($overrides['name']) && trim((string) $overrides['name']) !== ''
            ? trim((string) $overrides['name'])
            : $subjectName . ' 1-1 - ' . $shortName;

        $data = array(
            'program_id'        => (int) $student['program_id'],
            'subject_id'        => $subjectId,
            'teacher_id'        => $teacherId,
            'class_type'        => 'one_on_one',
            'name'              => $name,
            'price_per_session' => isset($overrides['price_per_session'])
                ? (float) $overrides['price_per_session']
                : 0.0,
            'total_sessions'    => isset($overrides['total_sessions'])
                ? (int) $overrides['total_sessions']
                : 0,
            'hw_deadline_time'  => self::DEFAULT_HW_DEADLINE,
            'hw_check_hours'    => self::DEFAULT_HW_CHECK_HOURS,
            'schedules'         => $schedules,
            'student_ids'       => array($studentId),
        );
        if (isset($overrides['start_date'])) {
            $data['start_date'] = (string) $overrides['start_date'];
        }

        $classId = $this->classes->createClass($data);
        if ($classId === null) {
            $err = $this->classes->errors();
            $this->addError('system', $err !== array()
                ? reset($err)
                : 'Không thể tạo lớp. Vui lòng thử lại.');
            return null;
        }

        // Trạng thái HV: đã xếp xong → chờ thu phí
        $before = array('status' => (string) $student['status']);
        $this->db->update(
            'db_lms_students',
            array('status' => self::STATUS_AFTER_ARRANGE),
            'user_id = ?',
            array($studentId)
        );

        // Xoá lịch trống GV đã bị lớp mới chiếm (tránh match trùng khung giờ)
        foreach ($schedules as $sch) {
            $this->db->safeExec(
                'delete from db_lms_teacher_free_slots'
                . ' where teacher_id = ? and day_of_week = ?'
                . ' and start_time < ? and end_time > ?',
                array(
                    $teacherId,
                    (int) $sch['day'],
                    $sch['end'] . ':00',
                    $sch['start'] . ':00',
                )
            );
        }

        $this->audit(
            'arrange_class',
            'students',
            $studentId,
            $before,
            array('status' => self::STATUS_AFTER_ARRANGE, 'class_id' => (int) $classId)
        );

        return (int) $classId;
    }

    // ===================== Nội bộ =====================

    /**
     * Áp hard filter + tính điểm cho 1 GV. Trả null khi GV bị loại.
     *
     * @param array  $teacher
     * @param int    $currentClasses
     * @param array  $freeSlots      rows db_lms_teacher_free_slots của GV
     * @param array  $fixedSlots     rows lịch cố định lớp đang dạy của GV (BR-SCHED-01)
     * @param array  $slots          khung giờ rảnh HV đã chuẩn hoá
     * @param int    $subjectId
     * @param string $district
     * @param bool   $isOnline
     * @param string $wantGender     '' | 'male' | 'female'
     * @return array|null
     */
    private function matchOne(array $teacher, $currentClasses, array $freeSlots, array $fixedSlots, array $slots, $subjectId, $district, $isOnline, $wantGender)
    {
        $teacherId = (int) $teacher['user_id'];
        $maxClasses = isset($teacher['max_classes']) ? (int) $teacher['max_classes'] : 0;

        // Sức chứa
        if ($maxClasses > 0 && $currentClasses >= $maxClasses) {
            return null;
        }

        // Môn học
        if ($subjectId > 0) {
            $subjects = \Lms\UserService::decodeJsonArray(
                isset($teacher['teachable_subjects']) ? $teacher['teachable_subjects'] : array()
            );
            if ($subjects !== array() && !in_array($subjectId, $subjects, true)) {
                return null;
            }
        }

        // Giới tính
        if ($wantGender !== '') {
            $gender = isset($teacher['gender']) ? (string) $teacher['gender'] : '';
            if ($gender !== $wantGender) {
                return null;
            }
        }

        // Khu vực / hình thức học
        $districts = \Lms\UserService::decodeJsonArray(
            isset($teacher['teachable_districts']) ? $teacher['teachable_districts'] : array()
        );
        $canOnline = isset($teacher['can_teach_online']) ? (int) $teacher['can_teach_online'] : 0;
        if ($isOnline) {
            if ($canOnline !== 1) {
                return null;
            }
        } elseif ($district !== '' && $districts !== array() && !in_array($district, $districts, true)) {
            return null;
        }

        // Lịch: khớp ≥ 1 khung giờ (sau khi trừ lịch cố định lớp đang dạy)
        $matchedSlots = array();
        foreach ($slots as $slot) {
            $candidates = array();
            foreach ($freeSlots as $fs) {
                if ((int) $fs['day_of_week'] !== (int) $slot['day']) {
                    continue;
                }
                $fsStart = substr((string) $fs['start_time'], 0, 5);
                $fsEnd   = substr((string) $fs['end_time'], 0, 5);
                $start = strcmp($slot['start'], $fsStart) > 0 ? $slot['start'] : $fsStart;
                $end   = strcmp($slot['end'], $fsEnd) < 0 ? $slot['end'] : $fsEnd;
                if (strcmp($start, $end) >= 0) {
                    continue;
                }
                $iv = $this->subtractFixed(
                    array(array('day' => (int) $slot['day'], 'start' => $start, 'end' => $end)),
                    $fixedSlots
                );
                foreach ($iv as $survivor) {
                    $candidates[] = $survivor;
                }
            }
            if ($candidates !== array()) {
                // Giữ khoảng dài nhất làm lịch lớp dự kiến
                usort($candidates, array($this, 'compareSlotLength'));
                $matchedSlots[] = $candidates[0];
            }
        }
        if ($matchedSlots === array()) {
            return null;
        }

        // Điểm
        $schedulePct = count($matchedSlots) / count($slots);
        $score = self::SCORE_SCHEDULE * $schedulePct;
        $score += self::SCORE_LOCATION;
        $rank = isset($teacher['rank']) ? (string) $teacher['rank'] : 'bronze';
        $score += isset(self::RANK_SCORES[$rank]) ? self::RANK_SCORES[$rank] : 0;
        $capacityRatio = $maxClasses > 0
            ? max(0.0, ($maxClasses - $currentClasses) / $maxClasses)
            : 1.0;
        $score += self::SCORE_CAPACITY * $capacityRatio;
        $score = min(100, (int) round($score));

        return array(
            'teacher_id'      => $teacherId,
            'teacher_code'    => (string) $teacher['teacher_code'],
            'full_name'       => (string) $teacher['full_name'],
            'gender'          => isset($teacher['gender']) ? (string) $teacher['gender'] : '',
            'rank'            => $rank,
            'current_classes' => (int) $currentClasses,
            'max_classes'     => $maxClasses,
            'can_teach_online' => $canOnline === 1,
            'districts'       => $districts,
            'score'           => $score,
            'schedule_pct'    => (int) round($schedulePct * 100),
            'matched_slots'   => $matchedSlots,
        );
    }

    /**
     * Sắp xếp kết quả: điểm ↓, hạng ↓, số lớp hiện tại ↑, mã GV ↑.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    private function compareMatches(array $a, array $b)
    {
        if ((int) $a['score'] !== (int) $b['score']) {
            return (int) $b['score'] - (int) $a['score'];
        }
        $ra = isset(self::RANK_SCORES[$a['rank']]) ? self::RANK_SCORES[$a['rank']] : 0;
        $rb = isset(self::RANK_SCORES[$b['rank']]) ? self::RANK_SCORES[$b['rank']] : 0;
        if ($ra !== $rb) {
            return $rb - $ra;
        }
        if ((int) $a['current_classes'] !== (int) $b['current_classes']) {
            return (int) $a['current_classes'] - (int) $b['current_classes'];
        }
        return strcmp((string) $a['teacher_code'], (string) $b['teacher_code']);
    }

    /**
     * Trừ các lịch cố định (cùng thứ) ra khỏi danh sách khoảng giờ — 1 khoảng
     * có thể bị tách thành 2 khoảng con. Input/output: mảng array('day','start','end').
     *
     * @param array $intervals
     * @param array $fixed rows db_lms_schedules (cùng GV, lọc cùng thứ bên trong)
     * @return array
     */
    private function subtractFixed(array $intervals, array $fixed)
    {
        foreach ($fixed as $fx) {
            $fxDay = (int) $fx['day_of_week'];
            $fxStart = substr((string) $fx['start_time'], 0, 5);
            $fxEnd   = substr((string) $fx['end_time'], 0, 5);
            $next = array();
            foreach ($intervals as $iv) {
                if ((int) $iv['day'] !== $fxDay) {
                    $next[] = $iv;
                    continue;
                }
                // Không giao nhau → giữ nguyên
                if (strcmp($iv['end'], $fxStart) <= 0 || strcmp($fxEnd, $iv['start']) <= 0) {
                    $next[] = $iv;
                    continue;
                }
                // Phần đầu còn lại trước lịch cố định
                if (strcmp($iv['start'], $fxStart) < 0) {
                    $next[] = array('day' => $iv['day'], 'start' => $iv['start'], 'end' => $fxStart);
                }
                // Phần đuôi còn lại sau lịch cố định
                if (strcmp($fxEnd, $iv['end']) < 0) {
                    $next[] = array('day' => $iv['day'], 'start' => $fxEnd, 'end' => $iv['end']);
                }
            }
            $intervals = $next;
        }
        return $intervals;
    }

    /**
     * So sánh 2 khoảng giờ theo độ dài (phút) giảm dần — dùng pick khoảng dài nhất.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    private function compareSlotLength(array $a, array $b)
    {
        return self::slotMinutes($b) - self::slotMinutes($a);
    }

    /**
     * Độ dài 1 khoảng giờ tính bằng phút.
     *
     * @param array $slot array('start' => 'HH:MM', 'end' => 'HH:MM')
     * @return int
     */
    private static function slotMinutes(array $slot)
    {
        $s = explode(':', (string) $slot['start']);
        $e = explode(':', (string) $slot['end']);
        $start = (int) $s[0] * 60 + (int) $s[1];
        $end   = (int) $e[0] * 60 + (int) $e[1];
        return $end - $start;
    }

    /**
     * Chuẩn hoá khung giờ rảnh của HV: chỉ giữ dòng có day/start/end hợp lệ.
     *
     * @param array $slots
     * @return array
     */
    private function normalizeSlots(array $slots)
    {
        $out = array();
        foreach ($slots as $slot) {
            if (!is_array($slot)) {
                continue;
            }
            $day = isset($slot['day']) ? (int) $slot['day'] : 0;
            $start = isset($slot['start']) ? substr(trim((string) $slot['start']), 0, 5) : '';
            $end = isset($slot['end']) ? substr(trim((string) $slot['end']), 0, 5) : '';
            if (!isset(\Lms\UserService::WEEKDAYS[$day])) {
                continue;
            }
            if (!\Lms\UserService::isValidTime($start) || !\Lms\UserService::isValidTime($end)) {
                continue;
            }
            if (strcmp($start, $end) >= 0) {
                continue;
            }
            $out[] = array('day' => $day, 'start' => $start, 'end' => $end);
        }
        return $out;
    }
}
