<?php
/**
 * StudentCourseService — portal Student: khoá học + nộp BTVN/Test (STU-01/02/03).
 *
 * Row-level mọi nơi qua db_lms_class_students (status = 'active') — HV chỉ thấy
 * lớp đang học, session + topic của lớp đó (guide §3.5). Dựa trên enrolment
 * (không phải session_students) để HV enrol giữa khoá vẫn thấy đủ buổi đã học.
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class StudentCourseService extends LmsService
{
    /** Trạng thái nộp BTVN (db_lms_session_students.hw_submission_status). */
    const HW_SUBMISSION_STATUS = array(
        'not_submitted'     => 'Chưa nộp BTVN',
        'submitted_on_time' => 'Đã nộp đúng hạn',
        'submitted_late'    => 'Đã nộp trễ hạn',
    );

    /** Trạng thái nộp test (db_lms_test_submissions.submission_status). */
    const TEST_SUBMISSION_STATUS = array(
        'not_submitted'     => 'Chưa nộp bài',
        'submitted_on_time' => 'Đã nộp đúng hạn',
        'submitted_late'    => 'Đã nộp trễ hạn',
    );

    /** Trạng thái bài test (db_lms_tests.status). */
    const TEST_STATUS = array(
        'draft'     => 'Bản nháp',
        'published' => 'Đã đăng',
        'closed'    => 'Đã đóng',
    );

    /** Số file nộp tối đa (mockup STU-03: 4 ô upload). */
    const MAX_FILES = 5;

    /**
     * Danh sách lớp HV đang học (kèm % hoàn thành).
     *
     * @param int $studentId
     * @return array rows: id, name, class_code, class_type, subject_name,
     *               teacher_name, total_sessions, remaining_sessions, progress_percent
     */
    public function listMyClasses($studentId)
    {
        $studentId = (int) $studentId;
        if ($studentId <= 0) {
            return array();
        }

        $rows = $this->db->safeFetch(
            'select c.id, c.name, c.class_code, c.class_type, c.subject_id,'
            . ' coalesce(cc.id, 0) as cycle_id, coalesce(cc.total_sessions, c.total_sessions) as total_sessions,'
            . ' coalesce(cc.remaining_sessions, c.remaining_sessions) as remaining_sessions,'
            . ' coalesce(cc.start_date, c.start_date) as start_date,'
            . ' sub.name as subject_name,'
            . ' t.full_name as teacher_name'
            . ' from db_lms_course_cycles cc'
            . ' join db_lms_cycle_students cs on cs.cycle_id = cc.id and cs.status = ?'
            . ' join db_lms_class_students legacy_cs on legacy_cs.class_id = cc.class_id'
            . ' and legacy_cs.student_id = cs.student_id and legacy_cs.status = ?'
            . ' join db_lms_classes c on c.id = cc.class_id and c.deleted_at is null'
            . ' left join db_lms_subjects sub on sub.id = c.subject_id'
            . ' left join db_lms_teachers t on t.user_id = cc.teacher_id'
            . ' where cs.student_id = ? and cc.status = ? and c.status = ?'
            . ' order by sub.name, c.name',
            array('active', 'active', $studentId, 'active', 'active')
        );

        foreach ($rows as $i => $row) {
            $total = (int) $row['total_sessions'];
            $remaining = (int) $row['remaining_sessions'];
            $done = $total - $remaining;
            $rows[$i]['done_sessions'] = $done < 0 ? 0 : $done;
            $rows[$i]['progress_percent'] = $total > 0
                ? min(100, max(0, (int) round($done / $total * 100)))
                : 0;
        }
        return $rows;
    }

    /**
     * Chi tiết 1 lớp (row-level: HV phải đang active trong lớp).
     *
     * @param int $classId
     * @param int $studentId
     * @return array|null
     */
    public function getClassForStudent($classId, $studentId)
    {
        $classId = (int) $classId;
        $studentId = (int) $studentId;
        if ($classId <= 0 || $studentId <= 0) {
            return null;
        }

        $row = $this->db->safeFetchOne(
            'select c.id, c.name, c.class_code, c.class_type, c.subject_id,'
            . ' cc.teacher_id, cc.id as cycle_id,'
            . ' coalesce(cc.total_sessions, c.total_sessions) as total_sessions,'
            . ' coalesce(cc.remaining_sessions, c.remaining_sessions) as remaining_sessions,'
            . ' cc.hw_deadline_time, cc.hw_check_hours,'
            . ' sub.name as subject_name,'
            . ' t.full_name as teacher_name'
            . ' from db_lms_course_cycles cc'
            . ' join db_lms_cycle_students cs on cs.cycle_id = cc.id and cs.status = ?'
            . ' join db_lms_class_students legacy_cs on legacy_cs.class_id = cc.class_id'
            . ' and legacy_cs.student_id = cs.student_id and legacy_cs.status = ?'
            . ' join db_lms_classes c on c.id = cc.class_id and c.deleted_at is null'
            . ' left join db_lms_subjects sub on sub.id = c.subject_id'
            . ' left join db_lms_teachers t on t.user_id = cc.teacher_id'
            . ' where cc.class_id = ? and cs.student_id = ? and cc.status = ?'
            . ' limit 1',
            array('active', 'active', $classId, $studentId, 'active')
        );
        if (empty($row) || !is_array($row)) {
            return null;
        }

        $total = (int) $row['total_sessions'];
        $done = $total - (int) $row['remaining_sessions'];
        $row['done_sessions'] = $done < 0 ? 0 : $done;
        $row['progress_percent'] = $total > 0
            ? min(100, max(0, (int) round($done / $total * 100)))
            : 0;

        $row['schedules'] = $this->db->safeFetch(
            'select day_of_week, start_time, end_time from db_lms_cycle_schedules'
            . ' where cycle_id = ? order by day_of_week, start_time',
            array((int) $row['cycle_id'])
        );
        return $row;
    }

    /**
     * Lộ trình chuyên đề của 1 lớp (row-level) + điểm test/minitest + tiến độ.
     *
     * @param int $classId
     * @param int $studentId
     * @return array array('topics' => array, 'tests' => array) — rỗng khi không có quyền
     */
    public function listTopicsForClass($classId, $studentId)
    {
        $classId = (int) $classId;
        $studentId = (int) $studentId;
        if ($classId <= 0 || $studentId <= 0) {
            return array('topics' => array(), 'tests' => array());
        }
        if ($this->getClassForStudent($classId, $studentId) === null) {
            return array('topics' => array(), 'tests' => array());
        }

        $cycleId = $this->resolveActiveCycleId($classId);
        if ($cycleId > 0) {
            $topics = $this->db->safeFetch(
                'select t.id, t.subject_id, t.name, t.content, t.test_file_url, t.minitest_file_url,'
                . ' sub.name as subject_name, ct.has_test, ct.has_minitest,'
                . ' ct.max_sessions, ct.order_index, t.report_template, cst.status as unlock_status'
                . ' from db_lms_cycle_topics ct join db_lms_topics t on t.id = ct.topic_id'
                . ' left join db_lms_subjects sub on sub.id = t.subject_id'
                . ' join db_lms_cycle_student_topics cst on cst.cycle_id = ct.cycle_id'
                . ' and cst.topic_id = ct.topic_id and cst.student_id = ?'
                . ' where ct.cycle_id = ? order by ct.order_index, ct.id',
                array($studentId, $cycleId)
            );
        } else {
            $topics = $this->db->safeFetch(
                'select t.id, t.subject_id, t.name, t.content, t.test_file_url, t.minitest_file_url,'
                . ' sub.name as subject_name, t.has_test, t.has_minitest,'
                . ' t.max_sessions, ct.order_index, t.report_template'
                . ' from db_lms_class_topics ct join db_lms_topics t on t.id = ct.topic_id'
                . ' left join db_lms_subjects sub on sub.id = t.subject_id'
                . ' where ct.class_id = ? order by ct.order_index, t.id',
                array($classId)
            );
            if (empty($topics)) {
                // Lớp cũ chưa được gán chuyên đề: giữ fallback theo môn học.
                $topics = $this->db->safeFetch(
                    'select t.id, t.subject_id, t.name, t.content, t.test_file_url, t.minitest_file_url,'
                    . ' sub.name as subject_name, t.has_test, t.has_minitest,'
                    . ' t.max_sessions, t.order_index, t.report_template'
                    . ' from db_lms_topics t'
                    . ' left join db_lms_subjects sub on sub.id = t.subject_id'
                    . ' where t.subject_id = (select subject_id from db_lms_classes where id = ?)'
                    . ' order by t.order_index, t.name',
                    array($classId)
                );
            }
        }

        if (empty($topics)) {
            return array('topics' => array(), 'tests' => array());
        }

        // ---- Số buổi đã dạy theo chuyên đề (1 query, group PHP) ----
        $today = date('Y-m-d');
        $sessions = $this->db->safeFetch(
            'select id, topic_id, topic_ids, session_date, status'
            . ' from db_lms_sessions'
            . ' where class_id = ? and session_date <= ? and status <> ?'
            . ($cycleId > 0 ? ' and (cycle_id = ? or cycle_id is null)' : ''),
            $cycleId > 0 ? array($classId, $today, 'cancelled', $cycleId) : array($classId, $today, 'cancelled')
        );
        $doneCount = array();
        foreach ($sessions as $s) {
            $topicIds = \Lms\UserService::decodeJsonArray(isset($s['topic_ids']) ? $s['topic_ids'] : array());
            if (!in_array((int) $s['topic_id'], $topicIds, true) && !empty($s['topic_id'])) {
                $topicIds[] = (int) $s['topic_id'];
            }
            foreach ($topicIds as $tid) {
                $tid = (int) $tid;
                if ($tid <= 0) {
                    continue;
                }
                if (!isset($doneCount[$tid])) {
                    $doneCount[$tid] = 0;
                }
                $doneCount[$tid]++;
            }
        }

        // ---- Bài test/minitest + điểm gần nhất của HV (1 query) ----
        $tests = $this->db->safeFetch(
            'select te.id, te.topic_id, te.test_type, te.title, te.test_file_url,'
            . ' te.deadline, te.status, te.max_score,'
            . ' ts.id as submission_id, ts.submitted_at, ts.submission_status,'
            . ' ts.score_x, ts.score_y_percent, ts.graded_at'
            . ' from db_lms_tests te'
            . ' left join db_lms_test_submissions ts'
            . ' on ts.test_id = te.id and ts.student_id = ?'
            . ' where te.class_id = ?'
            . ($cycleId > 0 ? ' and (te.cycle_id = ? or te.cycle_id is null)' : '')
            . ' order by te.test_type, te.deadline, te.id',
            $cycleId > 0 ? array($studentId, $classId, $cycleId) : array($studentId, $classId)
        );

        $testsByTopicType = array();
        foreach ($tests as $te) {
            $topicId = (int) $te['topic_id'];
            $type = (string) $te['test_type'];
            $testsByTopicType[$topicId][$type][] = $te;
        }

        foreach ($topics as $i => $topic) {
            $tid = (int) $topic['id'];
            $done = isset($doneCount[$tid]) ? $doneCount[$tid] : 0;
            $max = $topic['max_sessions'] === null ? null : (int) $topic['max_sessions'];
            $topics[$i]['done_sessions'] = $done;
            $topics[$i]['max_sessions'] = $max;

            $testRows = isset($testsByTopicType[$tid]['test']) ? $testsByTopicType[$tid]['test'] : array();
            $miniRows = isset($testsByTopicType[$tid]['minitest']) ? $testsByTopicType[$tid]['minitest'] : array();

            $testPublished = false;
            $testScore = null;
            $testCompleted = false;
            foreach ($testRows as $tr) {
                if ((string) $tr['status'] === 'published') {
                    $testPublished = true;
                }
                if (!empty($tr['graded_at'])) {
                    $testCompleted = true;
                    if ($tr['score_y_percent'] !== null) {
                        $scoreVal = (float) $tr['score_y_percent'];
                        if ($testScore === null || $scoreVal > $testScore) {
                            $testScore = $scoreVal;
                        }
                    }
                }
            }

            $miniPublished = false;
            $miniScore = null;
            $miniCompleted = false;
            foreach ($miniRows as $mr) {
                if ((string) $mr['status'] === 'published') {
                    $miniPublished = true;
                }
                if (!empty($mr['graded_at'])) {
                    $miniCompleted = true;
                    if ($mr['score_y_percent'] !== null) {
                        $scoreVal = (float) $mr['score_y_percent'];
                        if ($miniScore === null || $scoreVal > $miniScore) {
                            $miniScore = $scoreVal;
                        }
                    }
                }
            }

            $topics[$i]['test_ready'] = $testPublished;
            $topics[$i]['minitest_ready'] = $miniPublished;
            $topics[$i]['best_test_score'] = $testScore;
            $topics[$i]['best_minitest_score'] = $miniScore;
            $topics[$i]['test_completed'] = $testCompleted;
            $topics[$i]['minitest_completed'] = $miniCompleted;
            $topics[$i]['has_published_test'] = $testPublished;
            $topics[$i]['has_published_minitest'] = $miniPublished;
            $topics[$i]['has_pending_test'] = (!empty($testRows) && !$testPublished);

            $hasTest = !empty($topic['has_test']);
            $hasMini = !empty($topic['has_minitest']);
            if ($hasTest && $hasMini) {
                if ($miniPublished) {
                    // Admin đã BẬT Minitest (do Test không đạt) -> cần hoàn thành Minitest
                    $topics[$i]['is_completed'] = $miniCompleted;
                } else {
                    // Minitest chưa bật / không bật -> Chỉ cần hoàn thành Test chính
                    $topics[$i]['is_completed'] = $testCompleted;
                }
            } elseif ($hasTest) {
                $topics[$i]['is_completed'] = $testCompleted;
            } elseif ($hasMini) {
                $topics[$i]['is_completed'] = $miniCompleted;
            } else {
                $topics[$i]['is_completed'] = ($max !== null && $done >= $max);
            }
        }

        return array('topics' => $topics, 'tests' => $tests);
    }

    /**
     * Test + bài nộp của HV cho 1 chuyên đề (dùng cho section Test/Minitest §6/§8).
     *
     * @param int $classId
     * @param int $topicId
     * @param int $studentId
     * @return array
     */
    public function listTestsForTopic($classId, $topicId, $studentId)
    {
        $classId = (int) $classId;
        $topicId = (int) $topicId;
        $studentId = (int) $studentId;
        if ($classId <= 0 || $topicId <= 0 || $studentId <= 0) {
            return array();
        }
        if ($this->getClassForStudent($classId, $studentId) === null) {
            return array();
        }
        $topicProgress = $this->getTopicProgressForClass($classId, $topicId, $studentId);
        if ($topicProgress === null) {
            return array();
        }

        $rows = $this->db->safeFetch(
            'select te.id, te.topic_id, te.test_type, te.title, te.test_file_url,'
            . ' te.deadline, te.status, te.max_score,'
            . ' ts.id as submission_id, ts.submission_files, ts.submitted_at,'
            . ' ts.submission_status, ts.score_x, ts.score_y_percent, ts.graded_at,'
            . ' ts.teacher_feedback_files,'
            . ' t.test_file_url as topic_test_file_url, t.minitest_file_url as topic_minitest_file_url'
            . ' from db_lms_tests te'
            . ' join db_lms_topics t on t.id = te.topic_id'
            . ' left join db_lms_test_submissions ts'
            . ' on ts.test_id = te.id and ts.student_id = ?'
            . ' where te.class_id = ? and te.topic_id = ?'
            . ($this->resolveActiveCycleId($classId) > 0 ? ' and (te.cycle_id = ? or te.cycle_id is null)' : '')
            . ' order by te.test_type, te.deadline, te.id',
            $this->resolveActiveCycleId($classId) > 0
                ? array($studentId, $classId, $topicId, $this->resolveActiveCycleId($classId))
                : array($studentId, $classId, $topicId)
        );

        foreach ($rows as $i => $row) {
            if (empty($row['test_file_url'])) {
                $rows[$i]['test_file_url'] = (string) $row['test_type'] === 'minitest'
                    ? (isset($row['topic_minitest_file_url']) ? $row['topic_minitest_file_url'] : '')
                    : (isset($row['topic_test_file_url']) ? $row['topic_test_file_url'] : '');
            }
            $rows[$i]['test_ready'] = $topicProgress['max_sessions'] === null
                || $topicProgress['done_sessions'] >= $topicProgress['max_sessions'];
            $rows[$i]['submission_files'] = \Lms\UserService::decodeJsonArray(
                isset($row['submission_files']) ? $row['submission_files'] : array()
            );
            $rows[$i]['teacher_feedback_files'] = \Lms\UserService::decodeJsonArray(
                isset($row['teacher_feedback_files']) ? $row['teacher_feedback_files'] : array()
            );
        }
        return $rows;
    }

    /**
     * Danh sách buổi học của 1 chuyên đề (row-level qua enrolment).
     *
     * @param int $classId
     * @param int $topicId
     * @param int $studentId
     * @return array
     */
    public function listSessionsForTopic($classId, $topicId, $studentId)
    {
        $classId = (int) $classId;
        $topicId = (int) $topicId;
        $studentId = (int) $studentId;
        if ($classId <= 0 || $topicId <= 0 || $studentId <= 0) {
            return array();
        }
        if ($this->getClassForStudent($classId, $studentId) === null) {
            return array();
        }
        if ($this->getTopicProgressForClass($classId, $topicId, $studentId) === null) {
            return array();
        }

        $rows = $this->db->safeFetch(
            'select s.id, s.class_id, s.session_date, s.start_time, s.end_time,'
            . ' s.lesson_summary, s.hw_content, s.hw_deadline, s.hw_attached_files,'
            . ' s.status, s.topic_ids, s.topic_id,'
            . ' ss.id as ss_id, ss.hw_submission_files, ss.hw_submitted_at,'
            . ' ss.hw_submission_status, ss.teacher_feedback_text,'
            . ' ss.teacher_feedback_files, ss.hw_graded_at, ss.grade, ss.status as ss_status'
            . ' from db_lms_sessions s'
            . ' left join db_lms_session_students ss'
            . ' on ss.session_id = s.id and ss.student_id = ?'
            . ' where s.class_id = ?'
            . ' and ((case when s.topic_ids is not null and s.topic_ids != \'\' and json_valid(s.topic_ids) then json_contains(s.topic_ids, cast(? as char)) else 0 end = 1) or s.topic_id = ?)'
            . ($this->resolveActiveCycleId($classId) > 0 ? ' and (s.cycle_id = ? or s.cycle_id is null)' : '')
            . ' order by s.session_date, s.start_time',
            $this->resolveActiveCycleId($classId) > 0
                ? array($studentId, $classId, (string) $topicId, $topicId, $this->resolveActiveCycleId($classId))
                : array($studentId, $classId, (string) $topicId, $topicId)
        );

        foreach ($rows as $i => $row) {
            $rows[$i]['hw_attached_files'] = \Lms\UserService::decodeJsonArray(
                isset($row['hw_attached_files']) ? $row['hw_attached_files'] : array()
            );
            $rows[$i]['hw_submission_files'] = \Lms\UserService::decodeJsonArray(
                isset($row['hw_submission_files']) ? $row['hw_submission_files'] : array()
            );
            $rows[$i]['teacher_feedback_files'] = \Lms\UserService::decodeJsonArray(
                isset($row['teacher_feedback_files']) ? $row['teacher_feedback_files'] : array()
            );
            $rows[$i]['topic_ids'] = \Lms\UserService::decodeJsonArray(
                isset($row['topic_ids']) ? $row['topic_ids'] : array()
            );
        }
        return $rows;
    }

    /**
     * Danh sách tất cả buổi học của lớp cho HV (theo cycle active).
     *
     * @param int $classId
     * @param int $studentId
     * @return array
     */
    public function listSessionsForClass($classId, $studentId)
    {
        $classId = (int) $classId;
        $studentId = (int) $studentId;
        if ($classId <= 0 || $studentId <= 0) {
            return array();
        }
        if ($this->getClassForStudent($classId, $studentId) === null) {
            return array();
        }

        $cycleId = $this->resolveActiveCycleId($classId);

        $sessions = $this->db->safeFetch(
            'select s.id, s.class_id, s.session_date, s.start_time, s.end_time,'
            . ' s.lesson_summary, s.hw_content, s.hw_deadline, s.hw_attached_files,'
            . ' s.status, s.topic_ids, s.topic_id,'
            . ' ss.id as ss_id, ss.hw_submission_files, ss.hw_submitted_at,'
            . ' ss.hw_submission_status, ss.teacher_feedback_text,'
            . ' ss.teacher_feedback_files, ss.hw_graded_at, ss.grade, ss.status as ss_status'
            . ' from db_lms_sessions s'
            . ' left join db_lms_session_students ss'
            . '   on ss.session_id = s.id and ss.student_id = ?'
            . ' where s.class_id = ? and s.status <> ?'
            . ($cycleId > 0 ? ' and (s.cycle_id = ? or s.cycle_id is null)' : '')
            . ' order by s.session_date asc, s.start_time asc',
            $cycleId > 0
                ? array($studentId, $classId, 'cancelled', $cycleId)
                : array($studentId, $classId, 'cancelled')
        );

        $topicRows = $this->db->safeFetch(
            'select t.id, t.name from db_lms_topics t'
            . ' join db_lms_classes c on c.subject_id = t.subject_id'
            . ' where c.id = ?',
            array($classId)
        );
        $topicNameMap = array();
        foreach ($topicRows as $tr) {
            $topicNameMap[(int) $tr['id']] = (string) $tr['name'];
        }

        foreach ($sessions as $i => $row) {
            $sessions[$i]['hw_attached_files'] = \Lms\UserService::decodeJsonArray(
                isset($row['hw_attached_files']) ? $row['hw_attached_files'] : array()
            );
            $sessions[$i]['hw_submission_files'] = \Lms\UserService::decodeJsonArray(
                isset($row['hw_submission_files']) ? $row['hw_submission_files'] : array()
            );
            $sessions[$i]['teacher_feedback_files'] = \Lms\UserService::decodeJsonArray(
                isset($row['teacher_feedback_files']) ? $row['teacher_feedback_files'] : array()
            );
            $tids = \Lms\UserService::decodeJsonArray(
                isset($row['topic_ids']) ? $row['topic_ids'] : array()
            );
            if (!empty($row['topic_id']) && !in_array((int) $row['topic_id'], $tids, true)) {
                $tids[] = (int) $row['topic_id'];
            }
            $sessions[$i]['topic_ids'] = $tids;

            $tnames = array();
            foreach ($tids as $tid) {
                if (isset($topicNameMap[(int) $tid])) {
                    $tnames[] = $topicNameMap[(int) $tid];
                }
            }
            $sessions[$i]['topic_names'] = $tnames;
        }

        return $sessions;
    }

    /**
     * Chi tiết 1 buổi học cho HV (row-level qua enrolment lớp).
     *
     * @param int $sessionId
     * @param int $studentId
     * @return array|null
     */
    public function getSessionForStudent($sessionId, $studentId)
    {
        $sessionId = (int) $sessionId;
        $studentId = (int) $studentId;
        if ($sessionId <= 0 || $studentId <= 0) {
            return null;
        }

        $row = $this->db->safeFetchOne(
            'select s.id, s.class_id, s.session_date, s.start_time, s.end_time,'
            . ' s.lesson_summary, s.hw_content, s.hw_deadline, s.hw_attached_files,'
            . ' s.status, s.topic_ids, s.topic_id,'
            . ' c.name as class_name, c.class_code, c.subject_id,'
            . ' cc.id as cycle_id, cc.hw_deadline_time, cc.hw_check_hours,'
            . ' t.full_name as teacher_name,'
            . ' ss.id as ss_id, ss.hw_submission_files, ss.hw_submitted_at,'
            . ' ss.hw_submission_status, ss.teacher_feedback_text,'
            . ' ss.teacher_feedback_files, ss.hw_graded_at, ss.grade, ss.status as ss_status'
            . ' from db_lms_sessions s'
            . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
            . ' join db_lms_course_cycles cc on cc.class_id = c.id and cc.status = ?'
            . ' join db_lms_cycle_students cs'
            . ' on cs.cycle_id = cc.id and cs.student_id = ? and cs.status = ?'
            . ' left join db_lms_teachers t on t.user_id = cc.teacher_id'
            . ' left join db_lms_session_students ss'
            . ' on ss.session_id = s.id and ss.student_id = ?'
            . ' where s.id = ? and (s.cycle_id = cc.id or s.cycle_id is null)'
            . ' limit 1',
            array('active', $studentId, 'active', $studentId, $sessionId)
        );
        if (empty($row) || !is_array($row)) {
            return null;
        }

        $classTopicCount = (int) $this->db->count(
            'db_lms_class_topics',
            'class_id = ?',
            array((int) $row['class_id'])
        );
        if ($classTopicCount > 0) {
            $sessionTopicIds = UserService::decodeJsonArray(
                isset($row['topic_ids']) ? $row['topic_ids'] : array()
            );
            if (!empty($row['topic_id'])
                && !in_array((int) $row['topic_id'], array_map('intval', $sessionTopicIds), true)) {
                $sessionTopicIds[] = (int) $row['topic_id'];
            }
            if ($sessionTopicIds !== array()) {
                $assignedTopicIds = $this->db->safeFetch(
                    'select topic_id from db_lms_class_topics where class_id = ?',
                    array((int) $row['class_id'])
                );
                $assignedTopicIds = array_map('intval', array_column($assignedTopicIds, 'topic_id'));
                if (array_intersect(array_map('intval', $sessionTopicIds), $assignedTopicIds) === array()) {
                    return null;
                }
            }
        }

        $row['hw_attached_files'] = \Lms\UserService::decodeJsonArray(
            isset($row['hw_attached_files']) ? $row['hw_attached_files'] : array()
        );
        $row['hw_submission_files'] = \Lms\UserService::decodeJsonArray(
            isset($row['hw_submission_files']) ? $row['hw_submission_files'] : array()
        );
        $row['teacher_feedback_files'] = \Lms\UserService::decodeJsonArray(
            isset($row['teacher_feedback_files']) ? $row['teacher_feedback_files'] : array()
        );
        $row['topic_ids'] = \Lms\UserService::decodeJsonArray(
            isset($row['topic_ids']) ? $row['topic_ids'] : array()
        );
        $ids = $row['topic_ids'];
        if (empty($ids) && !empty($row['topic_id'])) {
            $ids = array((int) $row['topic_id']);
        }
        $row['topic_names'] = array();
        if (!empty($ids)) {
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
            if ($ids !== array()) {
                $topicRows = $this->db->safeFetch(
                    'select id, name from db_lms_topics where id in (' . implode(',', array_fill(0, count($ids), '?')) . ')',
                    $ids
                );
                $topicMap = array();
                foreach ($topicRows as $tr) {
                    $topicMap[(int) $tr['id']] = (string) $tr['name'];
                }
                foreach ($ids as $tid) {
                    if (isset($topicMap[$tid])) {
                        $row['topic_names'][] = $topicMap[$tid];
                    }
                }
            }
        }
        return $row;
    }

    /**
     * Chi tiết 1 bài test cho HV (row-level: test thuộc lớp HV đang học).
     *
     * @param int $testId
     * @param int $studentId
     * @return array|null
     */
    public function getTestForStudent($testId, $studentId)
    {
        $testId = (int) $testId;
        $studentId = (int) $studentId;
        if ($testId <= 0 || $studentId <= 0) {
            return null;
        }

        $row = $this->db->safeFetchOne(
            'select te.id, te.class_id, te.topic_id, te.test_type, te.title,'
            . ' te.test_file_url, te.deadline, te.status, te.max_score,'
            . ' c.name as class_name, c.subject_id,'
            . ' ts.id as submission_id, ts.submitted_at, ts.submission_status,'
            . ' ts.score_x, ts.score_y_percent, ts.graded_at'
            . ' from db_lms_tests te'
            . ' join db_lms_classes c on c.id = te.class_id and c.deleted_at is null'
            . ' join db_lms_course_cycles cc on cc.class_id = c.id and cc.status = ?'
            . ' join db_lms_cycle_students cs'
            . ' on cs.cycle_id = cc.id and cs.student_id = ? and cs.status = ?'
            . ' left join db_lms_test_submissions ts'
            . ' on ts.test_id = te.id and ts.student_id = ?'
            . ' where te.id = ? and (te.cycle_id = cc.id or te.cycle_id is null)'
            . ' limit 1',
            array('active', $studentId, 'active', $studentId, $testId)
        );
        if (empty($row) || !is_array($row)) {
            return null;
        }
        if ($this->getTopicProgressForClass((int) $row['class_id'], (int) $row['topic_id'], $studentId) === null) {
            return null;
        }
        return $row;
    }

    /**
     * Return topic progress for a class, or null when the topic is not in the
     * class syllabus. Classes without assignment rows retain legacy behavior.
     *
     * @param int $classId
     * @param int $topicId
     * @return array|null
     */
    private function getTopicProgressForClass($classId, $topicId, $studentId = 0)
    {
        $classId = (int) $classId;
        $topicId = (int) $topicId;
        if ($classId <= 0 || $topicId <= 0) {
            return null;
        }

        $cycleId = $this->resolveActiveCycleId($classId);
        if ($cycleId > 0) {
            $sql = 'select ct.topic_id as id, ct.max_sessions from db_lms_cycle_topics ct'
                . ' join db_lms_cycle_student_topics cst'
                . ' on cst.cycle_id = ct.cycle_id and cst.topic_id = ct.topic_id'
                . ' where ct.cycle_id = ? and ct.topic_id = ?';
            $params = array($cycleId, $topicId);
            if ((int) $studentId > 0) {
                $sql .= ' and cst.student_id = ? and cst.status = ?';
                $params[] = (int) $studentId;
                $params[] = 'unlocked';
            }
            $sql .= ' limit 1';
            $topic = $this->db->safeFetchOne($sql, $params);
        } else {
            $topic = $this->db->safeFetchOne(
            'select t.id, t.max_sessions'
            . ' from db_lms_topics t'
            . ' join db_lms_classes c on c.subject_id = t.subject_id and c.id = ?'
            . ' where t.id = ?'
            . ' and (not exists (select 1 from db_lms_class_topics ct0 where ct0.class_id = c.id)'
            . '      or exists (select 1 from db_lms_class_topics ct1'
            . '                 where ct1.class_id = c.id and ct1.topic_id = t.id))'
            . ' limit 1',
                array($classId, $topicId)
            );
        }
        if (empty($topic)) {
            return null;
        }

        $sessions = $this->db->safeFetch(
            'select topic_id, topic_ids from db_lms_sessions'
            . ' where class_id = ? and session_date <= ? and status <> ?'
            . ($cycleId > 0 ? ' and (cycle_id = ? or cycle_id is null)' : ''),
            $cycleId > 0
                ? array($classId, date('Y-m-d'), 'cancelled', $cycleId)
                : array($classId, date('Y-m-d'), 'cancelled')
        );
        $done = 0;
        foreach ($sessions as $session) {
            $topicIds = UserService::decodeJsonArray(
                isset($session['topic_ids']) ? $session['topic_ids'] : array()
            );
            if (!in_array((int) $session['topic_id'], $topicIds, true)
                && !empty($session['topic_id'])) {
                $topicIds[] = (int) $session['topic_id'];
            }
            if (in_array($topicId, array_map('intval', $topicIds), true)) {
                $done++;
            }
        }

        return array(
            'max_sessions' => $topic['max_sessions'] === null ? null : (int) $topic['max_sessions'],
            'done_sessions' => $done,
        );
    }

    private function resolveActiveCycleId($classId)
    {
        $row = $this->db->safeFetchOne(
            'select id from db_lms_course_cycles where class_id = ? and status = ? order by cycle_no desc limit 1',
            array((int) $classId, 'active')
        );
        return empty($row) ? 0 : (int) $row['id'];
    }

    /**
     * Hạn nộp BTVN hiệu lực: hw_deadline nếu GV đã giao, ngược lại tính mặc định
     * (session_date + 1 ngày + classes.hw_deadline_time).
     *
     * @param array $session row từ getSessionForStudent()
     * @return string|null 'Y-m-d H:i:s'
     */
    public function hwDeadline(array $session)
    {
        if (!empty($session['hw_deadline'])) {
            return (string) $session['hw_deadline'];
        }
        $time = isset($session['hw_deadline_time']) ? $session['hw_deadline_time'] : null;
        $ts = strtotime((string) $session['session_date'] . ' +1 day');
        if ($ts === false) {
            return null;
        }
        $time = ($time === null || $time === '') ? '12:00' : substr((string) $time, 0, 5);
        if ($time === '') {
            $time = '12:00';
        }
        return date('Y-m-d', $ts) . ' ' . $time . ':00';
    }

    /**
     * Text đếm ngược hạn nộp (tĩnh tại lúc load — mockup §13).
     *
     * @param string|null $deadline 'Y-m-d H:i:s'
     * @return array array('text' => string, 'overdue' => bool)
     */
    public function deadlineCountdown($deadline)
    {
        if ($deadline === null || $deadline === '') {
            return array('text' => '', 'overdue' => false);
        }
        $ts = strtotime((string) $deadline);
        if ($ts === false) {
            return array('text' => '', 'overdue' => false);
        }
        $diff = $ts - time();
        if ($diff < 0) {
            $mins = (int) round(abs($diff) / 60);
            if ($mins < 60) {
                return array('text' => 'Hạn nộp: bạn đã nộp trễ ' . $mins . ' phút', 'overdue' => true);
            }
            return array(
                'text' => 'Hạn nộp: bạn đã nộp trễ ' . (int) round($mins / 60) . ' giờ',
                'overdue' => true,
            );
        }
        $mins = (int) round($diff / 60);
        if ($mins < 60) {
            return array('text' => 'Hạn nộp: bạn còn ' . $mins . ' phút để nộp', 'overdue' => false);
        }
        $hours = (int) round($mins / 60);
        if ($hours < 48) {
            return array('text' => 'Hạn nộp: bạn còn ' . $hours . ' giờ để nộp', 'overdue' => false);
        }
        return array(
            'text' => 'Hạn nộp: bạn còn ' . (int) round($hours / 24) . ' ngày để nộp',
            'overdue' => false,
        );
    }

    /**
     * Đếm số file hợp lệ trong cấu trúc $_FILES (bỏ qua ô trống).
     *
     * @param array|null $files
     * @return int
     */
    private function countUploadedFiles($files)
    {
        if (!is_array($files) || empty($files)) {
            return 0;
        }
        $count = 0;
        if (isset($files['name']) && is_array($files['name'])) {
            foreach (array_keys($files['name']) as $i) {
                $name = isset($files['name'][$i]) ? (string) $files['name'][$i] : '';
                $err = isset($files['error'][$i]) ? (int) $files['error'][$i] : UPLOAD_ERR_NO_FILE;
                if ($name !== '' && $err !== UPLOAD_ERR_NO_FILE) {
                    $count++;
                }
            }
            return $count;
        }
        $name = isset($files['name']) ? (string) $files['name'] : '';
        $err = isset($files['error']) ? (int) $files['error'] : UPLOAD_ERR_NO_FILE;
        return ($name !== '' && $err !== UPLOAD_ERR_NO_FILE) ? 1 : 0;
    }

    /**
     * HV nộp BTVN của 1 buổi (STU-03).
     *
     * Row-level qua getSessionForStudent; chỉ nộp được khi session_date <= hôm nay
     * (mockup §13: ô buổi chỉ mở khi tới ngày học). Nộp trễ vẫn được (label đỏ).
     *
     * @param int         $sessionId
     * @param int         $studentId
     * @param array|null  $files   $_FILES['hw_files']
     * @param bool        $replaceFiles true → thay file cũ, false → gộp (tối đa 4)
     * @param array       $opts    'cli' (bỏ qua is_uploaded_file khi smoke test)
     * @return bool
     */
    public function submitHomework($sessionId, $studentId, $files, $replaceFiles = false, array $opts = array())
    {
        $this->clearErrors();

        $sessionId = (int) $sessionId;
        $studentId = (int) $studentId;
        if ($sessionId <= 0 || $studentId <= 0) {
            $this->addError('system', 'Dữ liệu không hợp lệ.');
            return false;
        }

        $session = $this->getSessionForStudent($sessionId, $studentId);
        if ($session === null) {
            $this->addError('session_id', 'Buổi học không tồn tại hoặc không thuộc lớp của bạn.');
            return false;
        }

        // Chỉ nộp được khi tới ngày học (mockup §13 L248)
        if (strcmp((string) $session['session_date'], date('Y-m-d')) > 0) {
            $this->addError('session_id', 'Buổi học chưa diễn ra. Bạn chỉ nộp được bài vào ngày học trở đi.');
            return false;
        }

        if ($files === null || !is_array($files) || empty($files)) {
            $this->addError('hw_files', 'Vui lòng chọn ít nhất 1 file bài làm.');
            return false;
        }

        $existing = $replaceFiles
            ? array()
            : (isset($session['hw_submission_files']) ? $session['hw_submission_files'] : array());

        // Đếm file mới hợp lệ để check trước khi lưu (tránh file mồ côi)
        $newCount = $this->countUploadedFiles($files);
        if ($newCount === 0) {
            $this->addError('hw_files', 'Vui lòng chọn ít nhất 1 file bài làm.');
            return false;
        }
        if (count($existing) + $newCount > self::MAX_FILES) {
            $this->addError(
                'hw_files',
                'Tổng số file nộp không quá ' . self::MAX_FILES
                . ' (đã có ' . count($existing) . ' file).'
            );
            return false;
        }

        $month = date('Ym', strtotime((string) $session['session_date']));
        $saveOpts = array('maxFiles' => self::MAX_FILES);
        if (!empty($opts['cli'])) {
            $saveOpts['cli'] = true;
        }
        $res = lms_save_files($files, 'hw/' . $month, $saveOpts);
        if (empty($res['ok'])) {
            $this->addError('hw_files', $res['error'] !== '' ? $res['error'] : 'Không lưu được file bài làm.');
            return false;
        }
        if ($res['paths'] === array()) {
            $this->addError('hw_files', 'Vui lòng chọn ít nhất 1 file bài làm.');
            return false;
        }

        $all = array_merge($existing, $res['paths']);

        // Đảm bảo có row session_students
        $ssId = isset($session['ss_id']) ? (int) $session['ss_id'] : 0;
        if ($ssId <= 0) {
            $ok = $this->db->insert('db_lms_session_students', array(
                'session_id' => $sessionId,
                'student_id' => $studentId,
                'status'     => 'not_submitted',
            ));
            if (!$ok) {
                $this->addError('system', 'Không thể ghi nhận bài nộp. Vui lòng thử lại.');
                return false;
            }
            $ssId = (int) $this->db->insertId;
        }

        $deadline = $this->hwDeadline($session);
        $now = $this->now();
        $status = ($deadline !== null && strcmp($now, $deadline) <= 0)
            ? 'submitted_on_time'
            : 'submitted_late';

        $before = $this->db->safeFetchOne(
            'select * from db_lms_session_students where id = ? limit 1',
            array($ssId)
        );

        $ok = $this->db->update('db_lms_session_students', array(
            'hw_submission_files'  => json_encode($all, JSON_UNESCAPED_UNICODE),
            'hw_submitted_at'      => $now,
            'hw_submission_status' => $status,
            'status'               => 'submitted',
        ), 'id = ?', array($ssId));
        if (!$ok) {
            $this->addError('system', 'Không thể ghi nhận bài nộp. Vui lòng thử lại.');
            return false;
        }

        $after = $this->db->safeFetchOne(
            'select * from db_lms_session_students where id = ? limit 1',
            array($ssId)
        );
        $this->audit('submit_homework', 'session_students', $ssId, $before, $after, 'student');

        return true;
    }

    /**
     * HV nộp bài test/minitest (STU-03).
     *
     * Row-level: test thuộc lớp HV đang active; tests.status phải 'published'
     * (minitest draft → "chưa được admin duyệt", mockup §13 L245). Nộp trễ vẫn
     * được (label đỏ). Đã chấm thì không nộp lại được.
     *
     * @param int         $testId
     * @param int         $studentId
     * @param array|null  $files $_FILES['test_files']
     * @param array       $opts  'cli'
     * @return bool
     */
    public function submitTest($testId, $studentId, $files, array $opts = array())
    {
        $this->clearErrors();

        $testId = (int) $testId;
        $studentId = (int) $studentId;
        if ($testId <= 0 || $studentId <= 0) {
            $this->addError('system', 'Dữ liệu không hợp lệ.');
            return false;
        }

        $test = $this->getTestForStudent($testId, $studentId);
        if ($test === null) {
            $this->addError('test_id', 'Bài test không tồn tại hoặc không thuộc lớp của bạn.');
            return false;
        }

        $testType = (string) $test['test_type'];
        $status = (string) $test['status'];
        $typeLabel = $testType === 'minitest' ? 'minitest' : 'test';
        if ($status === 'draft') {
            $this->addError(
                'test_id',
                $testType === 'minitest'
                    ? 'Bài minitest chưa được admin duyệt nên bạn chưa nộp được.'
                    : 'Bài ' . $typeLabel . ' chưa được admin duyệt nên bạn chưa nộp được.'
            );
            return false;
        }
        if ($status === 'closed') {
            $this->addError('test_id', 'Bài ' . $typeLabel . ' đã đóng, không thể nộp.');
            return false;
        }

        // Đã chấm → không nộp lại
        if (!empty($test['graded_at'])) {
            $this->addError('test_id', 'Bài đã được chấm, không thể nộp lại.');
            return false;
        }

        $progress = $this->getTopicProgressForClass((int) $test['class_id'], (int) $test['topic_id'], $studentId);
        if ($progress === null) {
            $this->addError('test_id', 'Bài test không thuộc lộ trình của lớp.');
            return false;
        }


        $newCount = $this->countUploadedFiles($files);
        if ($newCount === 0) {
            $this->addError('test_files', 'Vui lòng chọn ít nhất 1 file bài làm.');
            return false;
        }
        if ($newCount > self::MAX_FILES) {
            $this->addError('test_files', 'Chỉ được nộp tối đa ' . self::MAX_FILES . ' file.');
            return false;
        }

        $month = date('Ym', strtotime((string) $test['deadline']));
        $saveOpts = array('maxFiles' => self::MAX_FILES);
        if (!empty($opts['cli'])) {
            $saveOpts['cli'] = true;
        }
        $res = lms_save_files($files, 'test/' . $month, $saveOpts);
        if (empty($res['ok'])) {
            $this->addError('test_files', $res['error'] !== '' ? $res['error'] : 'Không lưu được file bài làm.');
            return false;
        }
        if ($res['paths'] === array()) {
            $this->addError('test_files', 'Vui lòng chọn ít nhất 1 file bài làm.');
            return false;
        }

        $now = $this->now();
        $deadline = (string) $test['deadline'];
        $subStatus = strcmp($now, $deadline) <= 0 ? 'submitted_on_time' : 'submitted_late';

        $data = array(
            'submission_files'  => json_encode($res['paths'], JSON_UNESCAPED_UNICODE),
            'submitted_at'      => $now,
            'submission_status' => $subStatus,
        );

        $subId = isset($test['submission_id']) ? (int) $test['submission_id'] : 0;
        $before = null;
        if ($subId > 0) {
            $before = $this->db->safeFetchOne(
                'select * from db_lms_test_submissions where id = ? limit 1',
                array($subId)
            );
            $ok = $this->db->update('db_lms_test_submissions', $data, 'id = ?', array($subId));
        } else {
            $data['test_id'] = $testId;
            $data['student_id'] = $studentId;
            $ok = $this->db->insert('db_lms_test_submissions', $data);
            $subId = (int) $this->db->insertId;
        }
        if (!$ok) {
            $this->addError('system', 'Không thể ghi nhận bài nộp. Vui lòng thử lại.');
            return false;
        }

        $after = $this->db->safeFetchOne(
            'select * from db_lms_test_submissions where id = ? limit 1',
            array($subId)
        );
        $this->audit('submit_test', 'test_submissions', $subId, $before, $after, 'student');

        return true;
    }
}
