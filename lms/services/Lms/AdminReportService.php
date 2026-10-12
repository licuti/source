<?php
/** Read-only admin reports for homework, curriculum and lesson statistics. */
namespace Lms;

class AdminReportService extends LmsService
{
    public function homeworkList(array $filters = array(), $page = 1, $perPage = 20)
    {
        $page = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset = ($page - 1) * $perPage;
        $where = array('1=1');
        $params = array();
        $from = isset($filters['date_from']) ? trim((string) $filters['date_from']) : '';
        $to = isset($filters['date_to']) ? trim((string) $filters['date_to']) : '';
        if ($this->validDate($from)) { $where[] = 's.session_date >= ?'; $params[] = $from; }
        if ($this->validDate($to)) { $where[] = 's.session_date <= ?'; $params[] = $to; }
        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $where[] = '(c.class_code like ? or t.teacher_code like ? or t.full_name like ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        $whereSql = implode(' and ', $where);
        $count = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_sessions s'
            . ' join db_lms_classes c on c.id=s.class_id and c.deleted_at is null'
            . ' join db_lms_teachers t on t.user_id=s.teacher_id'
            . ' where ' . $whereSql . ' and (s.hw_content is not null or s.lesson_summary is not null)',
            $params
        );
        $total = isset($count['c']) ? (int) $count['c'] : 0;
        $items = $this->db->safeFetch(
            'select s.id as session_id, s.session_date, s.start_time, s.end_time, s.status as session_status,'
            . ' s.hw_content, s.lesson_summary, s.updated_at, s.summary_submitted_at,'
            . ' c.id as class_id, c.class_code, c.name as class_name,'
            . ' t.user_id as teacher_id, t.teacher_code, t.full_name as teacher_name,'
            . ' top.name as topic_name,'
            . ' (select count(*) from db_lms_session_students ss where ss.session_id=s.id and ss.hw_submission_status in ("submitted_on_time","submitted_late")) as submissions_count'
            . ' from db_lms_sessions s'
            . ' join db_lms_classes c on c.id=s.class_id and c.deleted_at is null'
            . ' join db_lms_teachers t on t.user_id=s.teacher_id'
            . ' left join db_lms_topics top on top.id=s.topic_id'
            . ' where ' . $whereSql . ' and (s.hw_content is not null or s.lesson_summary is not null)'
            . ' order by s.session_date desc, s.start_time desc limit ' . $offset . ', ' . $perPage,
            $params
        );
        return array('items' => $items, 'total' => $total);
    }

    public function topicList(array $filters = array(), $page = 1, $perPage = 25)
    {
        $page = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset = ($page - 1) * $perPage;
        $where = array('1=1');
        $params = array();
        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $where[] = '(top.name like ? or top.report_template like ? or sub.name like ? or p.name like ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }
        $subjectId = isset($filters['subject_id']) ? (int) $filters['subject_id'] : 0;
        if ($subjectId > 0) { $where[] = 'top.subject_id = ?'; $params[] = $subjectId; }
        $testFilter = isset($filters['has_test']) ? (string) $filters['has_test'] : '';
        if ($testFilter === '1' || $testFilter === '0') { $where[] = 'top.has_test = ?'; $params[] = (int) $testFilter; }
        $whereSql = implode(' and ', $where);
        $count = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_topics top join db_lms_subjects sub on sub.id=top.subject_id'
            . ' join db_lms_programs p on p.id=sub.program_id where ' . $whereSql,
            $params
        );
        $items = $this->db->safeFetch(
            'select top.id, top.name, top.has_test, top.has_minitest, top.max_sessions, top.report_template, top.order_index,'
            . ' sub.id as subject_id, sub.name as subject_name, p.name as program_name,'
            . ' (select count(*) from db_lms_tests x where x.topic_id=top.id) as tests_count'
            . ' from db_lms_topics top join db_lms_subjects sub on sub.id=top.subject_id'
            . ' join db_lms_programs p on p.id=sub.program_id where ' . $whereSql
            . ' order by p.name, sub.name, top.order_index, top.name limit ' . $offset . ', ' . $perPage,
            $params
        );
        return array('items' => $items, 'total' => isset($count['c']) ? (int) $count['c'] : 0);
    }

    public function lessonStats($studentCode, $dateFrom = null, $dateTo = null, $page = 1, $perPage = 25)
    {
        $studentCode = trim((string) $studentCode);
        if ($studentCode === '') { return array('student' => array(), 'items' => array(), 'total' => 0, 'finished_count' => 0); }
        $student = $this->db->safeFetchOne(
            'select s.user_id, s.student_code, s.full_name, s.status, u.username, u.phone, u.email'
            . ' from db_lms_students s join db_lms_users u on u.id=s.user_id'
            . ' where s.student_code = ? limit 1', array($studentCode)
        );
        if (empty($student)) { return array('student' => array(), 'items' => array(), 'total' => 0, 'finished_count' => 0); }
        $page = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset = ($page - 1) * $perPage;
        $where = array('cs.student_id = ?', 's.status <> "cancelled"');
        $params = array((int) $student['user_id']);
        if ($this->validDate($dateFrom)) { $where[] = 's.session_date >= ?'; $params[] = $dateFrom; }
        if ($this->validDate($dateTo)) { $where[] = 's.session_date <= ?'; $params[] = $dateTo; }
        $whereSql = implode(' and ', $where);
        $count = $this->db->safeFetchOne(
            'select count(*) as c, sum(case when s.status="finished" then 1 else 0 end) as finished'
            . ' from db_lms_session_students cs join db_lms_sessions s on s.id=cs.session_id'
            . ' where ' . $whereSql,
            $params
        );
        $items = $this->db->safeFetch(
            'select s.id as session_id, s.session_date, s.start_time, s.end_time, s.status as session_status,'
            . ' s.lesson_summary, c.class_code, c.name as class_name, t.teacher_code, t.full_name as teacher_name'
            . ' from db_lms_session_students cs join db_lms_sessions s on s.id=cs.session_id'
            . ' join db_lms_classes c on c.id=s.class_id'
            . ' join db_lms_teachers t on t.user_id=s.teacher_id'
            . ' where ' . $whereSql . ' order by s.session_date desc, s.start_time desc'
            . ' limit ' . $offset . ', ' . $perPage,
            $params
        );
        return array(
            'student' => $student,
            'items' => $items,
            'total' => isset($count['c']) ? (int) $count['c'] : 0,
            'finished_count' => isset($count['finished']) ? (int) $count['finished'] : 0,
        );
    }

    private function validDate($date)
    {
        $date = (string) $date;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { return false; }
        $parsed = \DateTime::createFromFormat('!Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date;
    }
}
