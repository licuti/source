<?php
/**
 * TeacherProfileService — hồ sơ + ghi chú tuần của GV (TCH-01/02, mockup §4.1/§4.4).
 *
 * Row-level cố định: thao tác ngay trên $teacherId truyền vào (GV chỉ sửa hồ sơ
 * của chính mình — portal teacher không bao giờ truyền id người khác).
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class TeacherProfileService extends LmsService
{
    /**
     * Hồ sơ GV để render form §4.1.
     *
     * @param int $teacherId
     * @return array
     */
    public function getProfile($teacherId)
    {
        $teacherId = (int) $teacherId;
        if ($teacherId <= 0) {
            return array();
        }

        $row = $this->db->safeFetchOne(
            'select t.user_id, t.teacher_code, t.full_name, t.current_address,'
            . ' t.teachable_districts, t.can_teach_online, t.max_classes, t.status,'
            . ' u.username, u.email, u.phone'
            . ' from db_lms_teachers t'
            . ' join db_lms_users u on u.id = t.user_id'
            . ' where t.user_id = ? limit 1',
            array($teacherId)
        );
        if (empty($row) || !is_array($row)) {
            return array();
        }

        $row['teachable_districts'] = UserService::decodeJsonArray(
            isset($row['teachable_districts']) ? $row['teachable_districts'] : array()
        );
        return $row;
    }

    /**
     * Cập nhật hồ sơ GV (form §4.1).
     *
     * @param int   $teacherId
     * @param array $in current_address, teachable_districts[], can_teach_online, max_classes
     * @return bool
     */
    public function updateProfile($teacherId, array $in)
    {
        $this->clearErrors();

        $teacherId = (int) $teacherId;
        if ($teacherId <= 0) {
            $this->addError('system', 'Giáo viên không hợp lệ.');
            return false;
        }

        $exists = $this->db->safeFetchOne(
            'select user_id from db_lms_teachers where user_id = ? limit 1',
            array($teacherId)
        );
        if (empty($exists)) {
            $this->addError('system', 'Giáo viên không tồn tại.');
            return false;
        }

        // Địa chỉ hiện tại
        $address = isset($in['current_address']) ? trim((string) $in['current_address']) : '';
        if ($address !== '' && mb_strlen($address, 'UTF-8') > 255) {
            $this->addError('current_address', 'Địa chỉ hiện tại không quá 255 ký tự.');
            return false;
        }

        // Quận có thể dạy ⊂ HCMC_DISTRICTS
        $districts = array();
        $rawDistricts = isset($in['teachable_districts']) ? (array) $in['teachable_districts'] : array();
        foreach ($rawDistricts as $d) {
            $d = trim((string) $d);
            if ($d === '') {
                continue;
            }
            if (!in_array($d, UserService::HCMC_DISTRICTS, true)) {
                $this->addError('teachable_districts', 'Khu vực được chọn không hợp lệ.');
                return false;
            }
            $districts[] = $d;
        }
        $districts = array_values(array_unique($districts));

        // Có thể dạy online
        $canOnline = 0;
        if (isset($in['can_teach_online'])) {
            $canOnline = (int) $in['can_teach_online'] === 1 ? 1 : 0;
        }

        // Số lớp tối đa
        $maxClasses = 1;
        if (isset($in['max_classes']) && (string) $in['max_classes'] !== '') {
            if (!is_numeric($in['max_classes']) || (int) $in['max_classes'] < 0 || (int) $in['max_classes'] > 50) {
                $this->addError('max_classes', 'Số lớp tối đa phải từ 0 đến 50.');
                return false;
            }
            $maxClasses = (int) $in['max_classes'];
        }

        $before = $this->db->safeFetchOne(
            'select current_address, teachable_districts, can_teach_online, max_classes'
            . ' from db_lms_teachers where user_id = ? limit 1',
            array($teacherId)
        );

        $ok = $this->db->update('db_lms_teachers', array(
            'current_address'      => $address !== '' ? $address : null,
            'teachable_districts'  => $districts !== array() ? json_encode($districts, JSON_UNESCAPED_UNICODE) : null,
            'can_teach_online'     => $canOnline,
            'max_classes'          => $maxClasses,
        ), 'user_id = ?', array($teacherId));
        if (!$ok) {
            $this->addError('system', 'Không thể cập nhật thông tin. Vui lòng thử lại.');
            return false;
        }

        $after = $this->db->safeFetchOne(
            'select current_address, teachable_districts, can_teach_online, max_classes'
            . ' from db_lms_teachers where user_id = ? limit 1',
            array($teacherId)
        );
        $this->audit('update_teacher_profile', 'teachers', $teacherId, $before, $after, 'teacher');

        return true;
    }

    /**
     * Ghi chú tuần của GV.
     *
     * @param int    $teacherId
     * @param string $weekStart 'Y-m-d' (thứ 2)
     * @return array
     */
    public function getWeeklyNote($teacherId, $weekStart)
    {
        $teacherId = (int) $teacherId;
        $weekStart = (string) $weekStart;
        if ($teacherId <= 0 || $weekStart === '') {
            return array();
        }
        return $this->db->safeFetchOne(
            'select id, teacher_id, week_start, note from db_lms_teacher_notes'
            . ' where teacher_id = ? and week_start = ? limit 1',
            array($teacherId, $weekStart)
        );
    }

    /**
     * Lưu (insert/update) ghi chú tuần.
     *
     * @param int    $teacherId
     * @param string $weekStart 'Y-m-d' phải là thứ 2
     * @param string $note
     * @return bool
     */
    public function upsertWeeklyNote($teacherId, $weekStart, $note)
    {
        $this->clearErrors();

        $teacherId = (int) $teacherId;
        $weekStart = trim((string) $weekStart);
        $note      = isset($note) ? trim((string) $note) : '';

        if ($teacherId <= 0) {
            $this->addError('system', 'Giáo viên không hợp lệ.');
            return false;
        }
        $ts = strtotime($weekStart);
        if ($ts === false || date('N', $ts) !== '1') {
            $this->addError('week_start', 'Tuần phải bắt đầu từ thứ 2.');
            return false;
        }
        if (mb_strlen($note, 'UTF-8') > 5000) {
            $this->addError('note', 'Ghi chú không quá 5000 ký tự.');
            return false;
        }

        $exists = $this->db->safeFetchOne(
            'select id, note from db_lms_teacher_notes'
            . ' where teacher_id = ? and week_start = ? limit 1',
            array($teacherId, $weekStart)
        );

        if (!empty($exists)) {
            $ok = $this->db->update(
                'db_lms_teacher_notes',
                array('note' => $note !== '' ? $note : null),
                'id = ?',
                array((int) $exists['id'])
            );
            $targetId = (int) $exists['id'];
        } else {
            $ok = $this->db->insert('db_lms_teacher_notes', array(
                'teacher_id' => $teacherId,
                'week_start' => $weekStart,
                'note'       => $note !== '' ? $note : null,
            ));
            $targetId = (int) $this->db->insertId;
        }

        if (!$ok) {
            $this->addError('system', 'Không thể lưu ghi chú. Vui lòng thử lại.');
            return false;
        }

        $this->audit('update_teacher_note', 'teacher_notes', $targetId, null, null, 'teacher');

        return true;
    }
}
