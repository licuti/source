<?php
/**
 * UserService — auth + quản lý tài khoản LMS (SYS-03, ADM-01, ADM-02).
 *
 * Thực hiện:
 *  - BR-AUTH: bcrypt + rate-limit + remember-me (copy pattern admin đã hardened)
 *  - D9: đăng nhập sai portal → từ chối kèm portal đúng (không tự chuyển)
 *  - D3: 3 portal dùng chung db_lms_users
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class UserService extends LmsService
{
    const ROLE_ADMIN   = 'admin';
    const ROLE_TEACHER = 'teacher';
    const ROLE_STUDENT = 'student';

    const ROLES = array(self::ROLE_ADMIN, self::ROLE_TEACHER, self::ROLE_STUDENT);

    /**
     * Xác thực đăng nhập cho portal cụ thể.
     *
     * Luồng:
     *  1. Tìm user theo username (chưa phân biệt portal).
     *  2. Rate-limit: nếu đang bị khoá → từ chối.
     *  3. Sai mật khẩu → đếm attempt, từ chối.
     *  4. Đúng mật khẩu nhưng role != portal → từ chối + trả portal đúng (D9).
     *  5. OK: xoá attempt, cập nhật last_login, trả user.
     *
     * @param string $username
     * @param string $password
     * @param string $portal 'admin' | 'teacher' | 'student'
     * @return array array('ok' => bool, 'user' => array|null, 'error' => string, 'correct_portal' => string|null)
     */
    public function authenticate($username, $password, $portal)
    {
        $this->clearErrors();
        $username = trim((string) $username);
        $portal   = (string) $portal;

        if ($username === '' || $password === '') {
            return $this->fail('Tên đăng nhập hoặc mật khẩu không được để trống.');
        }

        // Rate-limit
        if (function_exists('lms_is_login_locked') && lms_is_login_locked($username)) {
            return $this->fail('Bạn đã đăng nhập sai nhiều lần. Vui lòng thử lại sau 15 phút.');
        }

        $user = $this->db->safeFetchOne(
            'select * from db_lms_users where username = ? limit 1',
            array($username)
        );

        if (!is_array($user) || empty($user)) {
            $this->registerFailedAttempt($username);
            return $this->fail('Tên đăng nhập hoặc mật khẩu không đúng.');
        }

        $hash = isset($user['password']) ? (string) $user['password'] : '';
        if (!function_exists('lms_verify_password') || !lms_verify_password($password, $hash)) {
            $this->registerFailedAttempt($username);
            return $this->fail('Tên đăng nhập hoặc mật khẩu không đúng.');
        }

        // D9: sai portal → từ chối, không tự chuyển
        if (!isset($user['role']) || $user['role'] !== $portal) {
            return array(
                'ok'             => false,
                'user'           => null,
                'error'          => 'Tài khoản không thuộc khu vực này.',
                'correct_portal' => isset($user['role']) ? (string) $user['role'] : null,
            );
        }

        // Tài khoản bị khoá
        if (isset($user['status']) && $user['status'] === 'locked') {
            return $this->fail('Tài khoản đã bị khoá. Vui lòng liên hệ quản trị viên.');
        }

        // Thành công
        if (function_exists('lms_login_attempt_clear')) {
            lms_login_attempt_clear($username);
        }

        $this->db->update(
            'db_lms_users',
            array('last_login_at' => $this->now()),
            'id = ?',
            array((int) $user['id'])
        );

        // Bổ sung is_super_admin từ db_lms_admins để RBAC bypass
        $user['is_super_admin'] = 0;
        if ($user['role'] === self::ROLE_ADMIN) {
            $admin = $this->db->safeFetchOne(
                'select role_level from db_lms_admins where user_id = ? limit 1',
                array((int) $user['id'])
            );
            if (is_array($admin) && isset($admin['role_level']) && $admin['role_level'] === 'super_admin') {
                $user['is_super_admin'] = 1;
            }
        }

        unset($user['password']);
        return array('ok' => true, 'user' => $user, 'error' => '', 'correct_portal' => null);
    }

    /**
     * @param string $username
     */
    private function registerFailedAttempt($username)
    {
        if (function_exists('lms_login_attempt_register')) {
            lms_login_attempt_register($username);
        }
    }

    /**
     * @param string $message
     * @return array
     */
    private function fail($message)
    {
        return array('ok' => false, 'user' => null, 'error' => $message, 'correct_portal' => null);
    }

    /**
     * Tạo tài khoản LMS (username unique, password bcrypt).
     *
     * @param string $username
     * @param string $password
     * @param string $role
     * @param array  $extra cột thêm (email, phone, status...)
     * @return int|null user id hoặc null nếu lỗi
     */
    public function createUser($username, $password, $role, array $extra = array())
    {
        $this->clearErrors();
        $username = trim((string) $username);
        $role     = (string) $role;

        if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            $this->addError('username', 'Tên đăng nhập chỉ gồm chữ, số, dấu chấm/gạch dưới/gạch ngang (3-50 ký tự).');
            return null;
        }
        if (strlen($password) < 6) {
            $this->addError('password', 'Mật khẩu tối thiểu 6 ký tự.');
            return null;
        }
        if (!in_array($role, self::ROLES, true)) {
            $this->addError('role', 'Vai trò không hợp lệ.');
            return null;
        }

        $exists = $this->db->safeFetchOne(
            'select id from db_lms_users where username = ? limit 1',
            array($username)
        );
        if (!empty($exists)) {
            $this->addError('username', 'Tên đăng nhập đã tồn tại.');
            return null;
        }

        $data = array_merge(array(
            'username' => $username,
            'password' => lms_hash_password($password),
            'role'     => $role,
            'status'   => 'active',
        ), $extra);

        $ok = $this->db->insert('db_lms_users', $data);
        if (!$ok) {
            $this->addError('system', 'Không thể tạo tài khoản.');
            return null;
        }
        return (int) $this->db->insertId;
    }

    /**
     * Đổi mật khẩu (+rehash tự động).
     *
     * @param int    $userId
     * @param string $newPassword
     * @return bool
     */
    public function changePassword($userId, $newPassword)
    {
        $this->clearErrors();
        if (strlen($newPassword) < 6) {
            $this->addError('password', 'Mật khẩu tối thiểu 6 ký tự.');
            return false;
        }
        return $this->db->update(
            'db_lms_users',
            array(
                'password'          => lms_hash_password($newPassword),
                'password_changed_at' => $this->now(),
            ),
            'id = ?',
            array((int) $userId)
        );
    }

    /**
     * Sinh mật khẩu ngẫu nhiên (dùng khi admin tạo tài khoản mới).
     *
     * @param int $length
     * @return string
     */
    public function generatePassword($length = 10)
    {
        $alphabet = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        try {
            $bytes = random_bytes($length);
        } catch (Exception $e) {
            $bytes = '';
            for ($i = 0; $i < $length; $i++) {
                $bytes .= chr(mt_rand(0, 255));
            }
        }
        $out = '';
        $max = strlen($alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[ord($bytes[$i]) % $max];
        }
        return $out;
    }

    // ===================== Enums dùng chung form ADM-01/ADM-02 =====================

    /** Trạng thái tài khoản (db_lms_users.status). */
    const USER_STATUSES = array(
        'active' => 'Hoạt động',
        'locked' => 'Đã khoá',
    );

    /** Trạng thái pipeline học viên (db_lms_students.status — SRS ADM-01). */
    const STUDENT_STATUSES = array(
        'pending_arrange' => 'Đang sắp xếp',
        'waiting_fee'     => 'Chờ học phí',
        'studying'        => 'Đang học',
        'paused'          => 'Tạm nghỉ',
        'quit'            => 'Nghỉ học',
    );

    const LEARNING_TYPES = array(
        'offline' => 'Offline (tại trung tâm)',
        'online'  => 'Online',
    );

    const GENDERS = array(
        'male'   => 'Nam',
        'female' => 'Nữ',
        'other'  => 'Khác',
    );

    /** Trạng thái giáo viên (db_lms_teachers.status). */
    const TEACHER_STATUSES = array(
        'active' => 'Đang dạy',
        'paused' => 'Tạm ngưng',
        'quit'   => 'Đã nghỉ',
    );

    const TEACHER_RANKS = array(
        'green'  => 'Xanh (ưu tiên cao nhất)',
        'gold'   => 'Vàng',
        'bronze' => 'Đồng',
    );

    /** Thứ trong tuần (giống db_lms_schedules.day_of_week: 2=Thứ 2 .. 8=Chủ nhật). */
    const WEEKDAYS = array(
        2 => 'Thứ 2', 3 => 'Thứ 3', 4 => 'Thứ 4', 5 => 'Thứ 5',
        6 => 'Thứ 6', 7 => 'Thứ 7', 8 => 'Chủ nhật',
    );

    /** Danh sách quận TP.HCM (mockup: dropdown quận; Thủ Đức gộp phường). */
    const HCMC_DISTRICTS = array(
        'Quận 1', 'Quận 3', 'Quận 4', 'Quận 5', 'Quận 6', 'Quận 7', 'Quận 8',
        'Quận 10', 'Quận 11', 'Quận 12', 'Tân Bình', 'Tân Phú', 'Phú Nhuận',
        'Bình Thạnh', 'Gò Vấp', 'Bình Tân', 'Thủ Đức',
    );

    // ===================== Validate helpers =====================

    /**
     * SĐT Việt Nam: 0 + 9-10 chữ số.
     *
     * @param string $phone
     * @return bool
     */
    public static function isValidPhoneVn($phone)
    {
        $phone = preg_replace('/[\s.\-()]/', '', (string) $phone);
        return (bool) preg_match('/^0\d{9,10}$/', $phone);
    }

    /** CCCD 12 chữ số (SRS ADM-02). */
    public static function isValidCccd($value)
    {
        return (bool) preg_match('/^\d{12}$/', (string) $value);
    }

    /** MST 10 chữ số (SRS ADM-02). */
    public static function isValidTaxCode($value)
    {
        return (bool) preg_match('/^\d{10}$/', (string) $value);
    }

    /** Định dạng HH:MM. */
    public static function isValidTime($value)
    {
        return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $value);
    }

    /**
     * Trim an toàn 1 trường input.
     *
     * @param array  $in
     * @param string $key
     * @param string $default
     * @return string
     */
    private function trimIn(array $in, $key, $default = '')
    {
        return isset($in[$key]) ? trim((string) $in[$key]) : (string) $default;
    }

    /**
     * Kiểm tra username unique (bỏ qua chính mình khi sửa).
     *
     * @param string $username
     * @param int    $exceptUserId
     * @return bool
     */
    private function isUsernameTaken($username, $exceptUserId = 0)
    {
        $sql = 'select id from db_lms_users where username = ?';
        $params = array($username);
        if ((int) $exceptUserId > 0) {
            $sql .= ' and id <> ?';
            $params[] = (int) $exceptUserId;
        }
        $row = $this->db->safeFetchOne($sql . ' limit 1', $params);
        return is_array($row) && !empty($row);
    }

    /**
     * Kiểm tra email unique (bỏ qua chính mình khi sửa). Email là UNIQUE key.
     *
     * @param string $email
     * @param int    $exceptUserId
     * @return bool
     */
    private function isEmailTaken($email, $exceptUserId = 0)
    {
        $sql = 'select id from db_lms_users where email = ?';
        $params = array($email);
        if ((int) $exceptUserId > 0) {
            $sql .= ' and id <> ?';
            $params[] = (int) $exceptUserId;
        }
        $row = $this->db->safeFetchOne($sql . ' limit 1', $params);
        return is_array($row) && !empty($row);
    }

    /**
     * Validate chung phần tài khoản (username/password/email/phone) cho form tạo/sửa.
     *
     * @param array  $in
     * @param int    $exceptUserId 0 = tạo mới
     * @param string $password     mật khẩu (rỗng khi sửa = giữ nguyên)
     * @return void
     */
    private function validateAccountFields(array $in, $exceptUserId, $password)
    {
        $username = $this->trimIn($in, 'username');
        $email = $this->trimIn($in, 'email');
        $phone = $this->trimIn($in, 'phone');

        if ($username === '') {
            $this->addError('username', 'Tên đăng nhập là bắt buộc.');
        } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            $this->addError('username', 'Tên đăng nhập chỉ gồm chữ, số, dấu chấm/gạch dưới/gạch ngang (3-50 ký tự).');
        } elseif ($this->isUsernameTaken($username, $exceptUserId)) {
            $this->addError('username', 'Tên đăng nhập đã tồn tại.');
        }

        $isNew = (int) $exceptUserId <= 0;
        if ($isNew && $password === '') {
            $this->addError('password', 'Mật khẩu là bắt buộc khi tạo tài khoản.');
        } elseif ($password !== '' && strlen($password) < 6) {
            $this->addError('password', 'Mật khẩu tối thiểu 6 ký tự.');
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('email', 'Email không đúng định dạng.');
        } elseif ($email !== '' && $this->isEmailTaken($email, $exceptUserId)) {
            $this->addError('email', 'Email đã được sử dụng.');
        }

        if ($phone !== '' && !self::isValidPhoneVn($phone)) {
            $this->addError('phone', 'Số điện thoại không hợp lệ (10-11 số, bắt đầu bằng 0).');
        }
    }

    /**
     * Parse + validate khung giờ rảnh (free_time_slots).
     *
     * Input: mảng các array('day' => int, 'start' => 'HH:MM', 'end' => 'HH:MM').
     * Dòng rỗng (cả 3 trường trống) được bỏ qua.
     *
     * @param array $rows
     * @return array danh sách khung giờ hợp lệ
     */
    public function parseFreeTimeSlots(array $rows)
    {
        $out = array();
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $day = isset($row['day']) ? (int) $row['day'] : 0;
            $start = isset($row['start']) ? trim((string) $row['start']) : '';
            $end = isset($row['end']) ? trim((string) $row['end']) : '';

            if ($day === 0 && $start === '' && $end === '') {
                continue;
            }
            if (!isset(self::WEEKDAYS[$day])) {
                $this->addError('free_time_slots', 'Thứ trong khung giờ rảnh không hợp lệ.');
                continue;
            }
            if ($start === '' || $end === '') {
                $this->addError('free_time_slots', 'Mỗi khung giờ rảnh phải có đủ giờ bắt đầu và kết thúc.');
                continue;
            }
            if (!self::isValidTime($start) || !self::isValidTime($end)) {
                $this->addError('free_time_slots', 'Khung giờ rảnh sai định dạng (HH:MM).');
                continue;
            }
            if (strcmp($start, $end) >= 0) {
                $this->addError('free_time_slots', 'Giờ bắt đầu phải trước giờ kết thúc.');
                continue;
            }
            $out[] = array('day' => $day, 'start' => $start, 'end' => $end);
        }
        return $out;
    }

    // ===================== Hồ sơ học viên (ADM-01) =====================

    /**
     * Sinh mã học viên tiếp theo (Master + số thứ tự, duy nhất).
     *
     * @param string $prefix
     * @return string
     */
    public function nextStudentCode($prefix = 'Master')
    {
        return $this->nextCode('db_lms_students', 'student_code', $prefix);
    }

    /**
     * Tạo tài khoản + hồ sơ học viên.
     *
     * Khóa input: username, password (rỗng → tự sinh), email, phone,
     * full_name, parent_name, parent_phone, grade, school_name, district,
     * ward, current_address, learning_type, priority_number, status, note,
     * free_time_slots (mảng khung giờ).
     *
     * @param array $in
     * @return int|null user_id
     */
    public function createStudent(array $in)
    {
        $this->clearErrors();

        $password = isset($in['password']) ? (string) $in['password'] : '';
        $this->validateAccountFields($in, 0, $password);
        $this->validateStudentProfile($in);
        $slots = $this->parseFreeTimeSlots(isset($in['free_time_slots']) ? $in['free_time_slots'] : array());
        if ($this->hasErrors()) {
            return null;
        }

        $userExtra = array();
        $email = $this->trimIn($in, 'email');
        $phone = $this->trimIn($in, 'phone');
        if ($email !== '') {
            $userExtra['email'] = $email;
        }
        if ($phone !== '') {
            $userExtra['phone'] = $phone;
        }

        $this->db->beginTransaction();
        $userId = $this->createUser($this->trimIn($in, 'username'), $password, self::ROLE_STUDENT, $userExtra);
        if (!$userId) {
            $this->db->rollBack();
            return null;
        }

        $ok = $this->db->insert('db_lms_students', $this->studentRow($in, $userId, true, $slots));
        if (!$ok) {
            $this->db->rollBack();
            $this->addError('system', 'Không thể tạo hồ sơ học viên. Vui lòng thử lại.');
            return null;
        }

        $this->db->commit();
        $this->audit('create_student', 'students', $userId, null, $this->studentRow($in, $userId, true, $slots));
        return $userId;
    }

    /**
     * Cập nhật tài khoản + hồ sơ học viên.
     *
     * @param int   $userId
     * @param array $in
     * @return bool
     */
    public function updateStudent($userId, array $in)
    {
        $this->clearErrors();
        $userId = (int) $userId;

        $existing = $this->db->safeFetchOne('select * from db_lms_users where id = ? limit 1', array($userId));
        $profile = $this->db->safeFetchOne('select * from db_lms_students where user_id = ? limit 1', array($userId));
        if (empty($existing) || empty($profile)) {
            $this->addError('system', 'Học viên không tồn tại.');
            return false;
        }

        $password = isset($in['password']) ? (string) $in['password'] : '';
        $this->validateAccountFields($in, $userId, $password);
        $this->validateStudentProfile($in);
        $slots = $this->parseFreeTimeSlots(isset($in['free_time_slots']) ? $in['free_time_slots'] : array());
        if ($this->hasErrors()) {
            return false;
        }

        $userData = $this->accountRow($in, $existing);
        if ($password !== '') {
            $userData['password'] = lms_hash_password($password);
            $userData['password_changed_at'] = $this->now();
        }

        $studentData = $this->studentRow($in, $userId, false);
        $studentData['free_time_slots'] = $slots !== array() ? json_encode($slots, JSON_UNESCAPED_UNICODE) : null;

        $before = array(
            'user' => $existing,
            'profile' => $profile,
        );

        $this->db->beginTransaction();
        try {
            if (!empty($userData)) {
                $this->db->update('db_lms_users', $userData, 'id = ?', array($userId));
            }
            $this->db->update('db_lms_students', $studentData, 'user_id = ?', array($userId));
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[LMS] updateStudent failed: ' . $e->getMessage());
            $this->addError('system', 'Không thể cập nhật hồ sơ. Vui lòng thử lại.');
            return false;
        }

        $after = array('user' => $userData, 'profile' => $studentData);
        $this->audit('update_student', 'students', $userId, $before, $after);
        return true;
    }

    /**
     * Validate các trường riêng của hồ sơ học viên.
     *
     * @param array $in
     * @return void
     */
    private function validateStudentProfile(array $in)
    {
        $fullName = $this->trimIn($in, 'full_name');
        if ($fullName === '') {
            $this->addError('full_name', 'Họ tên học viên là bắt buộc.');
        } elseif (mb_strlen($fullName, 'UTF-8') > 100) {
            $this->addError('full_name', 'Họ tên không được quá 100 ký tự.');
        }

        $parentPhone = $this->trimIn($in, 'parent_phone');
        if ($parentPhone !== '' && !self::isValidPhoneVn($parentPhone)) {
            $this->addError('parent_phone', 'Số điện thoại phụ huynh không hợp lệ (10-11 số, bắt đầu bằng 0).');
        }

        $learningType = $this->trimIn($in, 'learning_type', 'offline');
        if (!isset(self::LEARNING_TYPES[$learningType])) {
            $this->addError('learning_type', 'Hình thức học không hợp lệ.');
        }

        $status = $this->trimIn($in, 'status', 'pending_arrange');
        if (!isset(self::STUDENT_STATUSES[$status])) {
            $this->addError('status', 'Trạng thái học viên không hợp lệ.');
        }

        $district = $this->trimIn($in, 'district');
        if ($district !== '' && !in_array($district, self::HCMC_DISTRICTS, true)) {
            $this->addError('district', 'Quận không thuộc danh sách TP.HCM.');
        }

        $subjectId = isset($in['subject_id']) ? (int) $in['subject_id'] : 0;
        if ($subjectId > 0) {
            $subject = $this->db->safeFetchOne(
                'select id from db_lms_subjects where id = ? limit 1',
                array($subjectId)
            );
            if (empty($subject)) {
                $this->addError('subject_id', 'Môn học không tồn tại.');
            }
        }

        $preferredGender = $this->trimIn($in, 'preferred_teacher_gender');
        if ($preferredGender !== '' && !in_array($preferredGender, array('male', 'female'), true)) {
            $this->addError('preferred_teacher_gender', 'Yêu cầu giới tính giáo viên không hợp lệ.');
        }
    }

    /**
     * Gom data ghi vào db_lms_students.
     *
     * @param array $in
     * @param int   $userId
     * @param bool  $isCreate
     * @param array $slots khung giờ rảnh đã validate (chỉ dùng khi tạo)
     * @return array
     */
    private function studentRow(array $in, $userId, $isCreate, array $slots = array())
    {
        $row = array(
            'full_name'       => $this->trimIn($in, 'full_name'),
            'parent_name'     => $this->trimIn($in, 'parent_name'),
            'parent_phone'    => $this->trimIn($in, 'parent_phone'),
            'grade'           => $this->trimIn($in, 'grade'),
            'subject_id'      => isset($in['subject_id']) && (int) $in['subject_id'] > 0
                ? (int) $in['subject_id']
                : null,
            'preferred_teacher_gender' => $this->trimIn($in, 'preferred_teacher_gender') !== ''
                ? $this->trimIn($in, 'preferred_teacher_gender')
                : null,
            'school_name'     => $this->trimIn($in, 'school_name'),
            'district'        => $this->trimIn($in, 'district'),
            'ward'            => $this->trimIn($in, 'ward'),
            'current_address' => $this->trimIn($in, 'current_address'),
            'learning_type'   => $this->trimIn($in, 'learning_type', 'offline'),
            'priority_number' => isset($in['priority_number']) ? (int) $in['priority_number'] : 0,
            'status'          => $this->trimIn($in, 'status', 'pending_arrange'),
            'note'            => $this->trimIn($in, 'note'),
        );
        if ($isCreate) {
            $row['user_id'] = (int) $userId;
            $row['student_code'] = $this->trimIn($in, 'student_code');
            if ($row['student_code'] === '') {
                $row['student_code'] = $this->nextStudentCode();
            }
            $row['free_time_slots'] = $slots !== array()
                ? json_encode($slots, JSON_UNESCAPED_UNICODE)
                : null;
        }
        return $row;
    }

    /**
     * Gom data ghi vào db_lms_users từ input form.
     *
     * @param array $in
     * @param array $existing
     * @return array
     */
    private function accountRow(array $in, array $existing)
    {
        $row = array(
            'email'  => $this->trimIn($in, 'email') !== '' ? $this->trimIn($in, 'email') : null,
            'phone'  => $this->trimIn($in, 'phone') !== '' ? $this->trimIn($in, 'phone') : null,
            'status' => $this->trimIn($in, 'user_status', 'active'),
        );
        if (!isset(self::USER_STATUSES[$row['status']])) {
            $row['status'] = 'active';
        }
        $username = $this->trimIn($in, 'username');
        if ($username !== '' && $username !== (string) $existing['username']) {
            $row['username'] = $username;
        }
        return $row;
    }

    /**
     * Chi tiết 1 học viên (join users + students).
     *
     * @param int $userId
     * @return array
     */
    public function getStudent($userId)
    {
        return $this->db->safeFetchOne(
            'select u.id, u.username, u.email, u.phone, u.status as user_status,'
            . ' u.last_login_at, u.created_at,'
            . ' s.user_id, s.student_code, s.full_name, s.parent_name, s.parent_phone,'
            . ' s.grade, s.school_name, s.district, s.ward, s.current_address,'
            . ' s.learning_type, s.priority_number, s.status as student_status, s.note,'
            . ' s.subject_id, s.preferred_teacher_gender, subj.name as subject_name,'
            . ' s.free_time_slots'
            . ' from db_lms_users u'
            . ' join db_lms_students s on s.user_id = u.id'
            . ' left join db_lms_subjects subj on subj.id = s.subject_id'
            . ' where u.id = ? and u.role = ? limit 1',
            array((int) $userId, self::ROLE_STUDENT)
        );
    }

    /**
     * Danh sách học viên + thông tin lớp đang theo (lịch học, GV 1-1).
     *
     * @param array $filters array('q' => string, 'status' => string)
     * @param int   $page
     * @param int   $perPage
     * @return array array('items' => array, 'total' => int)
     */
    public function listStudents(array $filters = array(), $page = 1, $perPage = 20)
    {
        $page = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset = ($page - 1) * $perPage;

        $where = array('u.role = ?');
        $params = array(self::ROLE_STUDENT);

        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $where[] = '(s.student_code like ? or s.full_name like ? or u.username like ? or u.phone like ? or s.parent_phone like ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }

        $status = isset($filters['status']) ? (string) $filters['status'] : '';
        if ($status !== '' && isset(self::STUDENT_STATUSES[$status])) {
            $where[] = 's.status = ?';
            $params[] = $status;
        }

        $whereSql = implode(' and ', $where);

        $countRow = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_users u join db_lms_students s on s.user_id = u.id'
            . ' where ' . $whereSql,
            $params
        );
        $total = isset($countRow['c']) ? (int) $countRow['c'] : 0;

        $items = $this->db->safeFetch(
            'select u.id, u.username, u.email, u.phone, u.status as user_status, u.created_at,'
            . ' s.student_code, s.full_name, s.parent_name, s.parent_phone, s.grade, s.school_name,'
            . ' s.district, s.ward, s.current_address, s.learning_type, s.priority_number,'
            . ' s.status as student_status, s.note, s.subject_id, subj.name as subject_name,'
            . ' (select group_concat(distinct c.name order by c.name separator "; ")'
            . '    from db_lms_class_students cs'
            . '    join db_lms_classes c on c.id = cs.class_id and c.deleted_at is null'
            . '   where cs.student_id = u.id and cs.status = "active") as class_names,'
            . ' (select group_concat(distinct t.full_name order by t.full_name separator "; ")'
            . '    from db_lms_class_students cs'
            . '    join db_lms_classes c on c.id = cs.class_id'
            . '         and c.class_type = "one_on_one" and c.deleted_at is null'
            . '    join db_lms_teachers t on t.user_id = c.teacher_id'
            . '   where cs.student_id = u.id and cs.status = "active") as teacher_1on1'
            . ' from db_lms_users u'
            . ' join db_lms_students s on s.user_id = u.id'
            . ' left join db_lms_subjects subj on subj.id = s.subject_id'
            . ' where ' . $whereSql
            . ' order by s.priority_number desc, u.id desc'
            . ' limit ' . $offset . ', ' . $perPage,
            $params
        );

        return array('items' => $items, 'total' => $total);
    }

    /**
     * Xoá học viên (profile + tài khoản). Đang theo lớp sẽ bị từ chối (FK RESTRICT).
     *
     * @param int $userId
     * @return bool
     */
    public function deleteStudent($userId)
    {
        $this->clearErrors();
        $userId = (int) $userId;
        if ($userId <= 0) {
            $this->addError('system', 'ID không hợp lệ.');
            return false;
        }

        $row = $this->getStudent($userId);
        if (empty($row)) {
            $this->addError('system', 'Học viên không tồn tại.');
            return false;
        }

        $classes = (int) $this->db->count(
            'db_lms_class_students',
            'student_id = ? and status = "active"',
            array($userId)
        );
        if ($classes > 0) {
            $this->addError('system', 'Học viên đang theo ' . $classes . ' lớp. Hãy khoá tài khoản thay vì xoá.');
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->db->safeExec('delete from db_lms_students where user_id = ?', array($userId));
            $this->db->safeExec('delete from db_lms_users where id = ? and role = ?', array($userId, self::ROLE_STUDENT));
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[LMS] deleteStudent failed: ' . $e->getMessage());
            $this->addError('system', 'Không thể xoá học viên (dữ liệu đang được tham chiếu).');
            return false;
        }

        $this->audit('delete_student', 'students', $userId, $row, null);
        return true;
    }

    // ===================== Hồ sơ giáo viên (ADM-02) =====================

    /**
     * Sinh mã giáo viên tiếp theo (TE + số thứ tự, duy nhất).
     *
     * @return string
     */
    public function nextTeacherCode()
    {
        return $this->nextCode('db_lms_teachers', 'teacher_code', 'TE');
    }

    /**
     * Sinh mã có format PREFIX + số thứ tự duy nhất (cho student_code / teacher_code).
     *
     * @param string $table
     * @param string $column
     * @param string $prefix
     * @return string
     */
    private function nextCode($table, $column, $prefix)
    {
        $rows = $this->db->safeFetch(
            'select `' . $column . '` as code from `' . $table . '`'
            . ' where `' . $column . '` like ? order by `' . $column . '` desc limit 1',
            array($prefix . '%')
        );
        $max = 0;
        foreach ($rows as $row) {
            $code = isset($row['code']) ? (string) $row['code'] : '';
            $num = (int) ltrim(preg_replace('/^\D+/', '', $code), '0');
            if ($num > $max) {
                $max = $num;
            }
        }
        return $prefix . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Tạo tài khoản + hồ sơ giáo viên.
     *
     * Khóa input: username, password (rỗng → tự sinh), email, phone,
     * full_name, gender, dob, cccd, issue_date, issue_place, tax_code,
     * teachable_districts (mảng), teachable_subjects (mảng id), can_teach_online,
     * rank, max_classes, status, service_fee_percentage, is_commit_08,
     * bank_name, bank_account, bank_owner.
     *
     * @param array $in
     * @return int|null user_id
     */
    public function createTeacher(array $in)
    {
        $this->clearErrors();

        $password = isset($in['password']) ? (string) $in['password'] : '';
        $this->validateAccountFields($in, 0, $password);
        $this->validateTeacherProfile($in);
        if ($this->hasErrors()) {
            return null;
        }

        $userExtra = array();
        $email = $this->trimIn($in, 'email');
        $phone = $this->trimIn($in, 'phone');
        if ($email !== '') {
            $userExtra['email'] = $email;
        }
        if ($phone !== '') {
            $userExtra['phone'] = $phone;
        }

        $this->db->beginTransaction();
        $userId = $this->createUser($this->trimIn($in, 'username'), $password, self::ROLE_TEACHER, $userExtra);
        if (!$userId) {
            $this->db->rollBack();
            return null;
        }

        $row = $this->teacherRow($in, true);
        $row['user_id'] = $userId;
        $ok = $this->db->insert('db_lms_teachers', $row);
        if (!$ok) {
            $this->db->rollBack();
            $this->addError('system', 'Không thể tạo hồ sơ giáo viên. Vui lòng thử lại.');
            return null;
        }

        $this->db->commit();
        $this->audit('create_teacher', 'teachers', $userId, null, $row);
        return $userId;
    }

    /**
     * Cập nhật tài khoản + hồ sơ giáo viên.
     *
     * @param int   $userId
     * @param array $in
     * @return bool
     */
    public function updateTeacher($userId, array $in)
    {
        $this->clearErrors();
        $userId = (int) $userId;

        $existing = $this->db->safeFetchOne('select * from db_lms_users where id = ? limit 1', array($userId));
        $profile = $this->db->safeFetchOne('select * from db_lms_teachers where user_id = ? limit 1', array($userId));
        if (empty($existing) || empty($profile)) {
            $this->addError('system', 'Giáo viên không tồn tại.');
            return false;
        }

        $password = isset($in['password']) ? (string) $in['password'] : '';
        $this->validateAccountFields($in, $userId, $password);
        $this->validateTeacherProfile($in, $userId);
        if ($this->hasErrors()) {
            return false;
        }

        $userData = $this->accountRow($in, $existing);
        if ($password !== '') {
            $userData['password'] = lms_hash_password($password);
            $userData['password_changed_at'] = $this->now();
        }

        $teacherData = $this->teacherRow($in, false);

        $before = array('user' => $existing, 'profile' => $profile);

        $this->db->beginTransaction();
        try {
            if (!empty($userData)) {
                $this->db->update('db_lms_users', $userData, 'id = ?', array($userId));
            }
            $this->db->update('db_lms_teachers', $teacherData, 'user_id = ?', array($userId));
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[LMS] updateTeacher failed: ' . $e->getMessage());
            $this->addError('system', 'Không thể cập nhật hồ sơ. Vui lòng thử lại.');
            return false;
        }

        $this->audit('update_teacher', 'teachers', $userId, $before, array('user' => $userData, 'profile' => $teacherData));
        return true;
    }

    /**
     * Validate các trường riêng của hồ sơ giáo viên.
     *
     * @param array $in
     * @param int   $userId
     * @return void
     */
    private function validateTeacherProfile(array $in, $userId = 0)
    {
        $fullName = $this->trimIn($in, 'full_name');
        if ($fullName === '') {
            $this->addError('full_name', 'Họ tên giáo viên là bắt buộc.');
        } elseif (mb_strlen($fullName, 'UTF-8') > 100) {
            $this->addError('full_name', 'Họ tên không được quá 100 ký tự.');
        }

        $cccd = $this->trimIn($in, 'cccd');
        if ($cccd !== '' && !self::isValidCccd($cccd)) {
            $this->addError('cccd', 'CCCD phải gồm đúng 12 chữ số.');
        } elseif ($cccd !== '') {
            $dup = $this->db->safeFetchOne(
                'select user_id from db_lms_teachers where cccd = ?'
                . ((int) $userId > 0 ? ' and user_id <> ?' : '')
                . ' limit 1',
                (int) $userId > 0 ? array($cccd, (int) $userId) : array($cccd)
            );
            if (!empty($dup)) {
                $this->addError('cccd', 'CCCD đã được sử dụng bởi giáo viên khác.');
            }
        }

        $taxCode = $this->trimIn($in, 'tax_code');
        if ($taxCode !== '' && !self::isValidTaxCode($taxCode)) {
            $this->addError('tax_code', 'Mã số thuế phải gồm đúng 10 chữ số.');
        }

        $fee = $this->trimIn($in, 'service_fee_percentage');
        if ($fee === '') {
            // Bỏ qua: dùng default của schema (48.00) khi admin không nhập.
        } elseif (!is_numeric($fee) || (float) $fee < 0 || (float) $fee > 100) {
            $this->addError('service_fee_percentage', 'Mức phí dịch vụ phải từ 0 đến 100 (%).');
        }

        $maxClasses = $this->trimIn($in, 'max_classes', '1');
        if (!is_numeric($maxClasses) || (int) $maxClasses < 0 || (int) $maxClasses > 50) {
            $this->addError('max_classes', 'Số lớp tối đa phải từ 0 đến 50.');
        }

        $rank = $this->trimIn($in, 'rank', 'bronze');
        if (!isset(self::TEACHER_RANKS[$rank])) {
            $this->addError('rank', 'Hạng giáo viên không hợp lệ.');
        }

        $gender = $this->trimIn($in, 'gender');
        if ($gender !== '' && !isset(self::GENDERS[$gender])) {
            $this->addError('gender', 'Giới tính không hợp lệ.');
        }

        $status = $this->trimIn($in, 'status', 'active');
        if (!isset(self::TEACHER_STATUSES[$status])) {
            $this->addError('status', 'Trạng thái giáo viên không hợp lệ.');
        }

        $dob = $this->trimIn($in, 'dob');
        if ($dob !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
            $this->addError('dob', 'Ngày sinh sai định dạng.');
        }

        $issueDate = $this->trimIn($in, 'issue_date');
        if ($issueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $issueDate)) {
            $this->addError('issue_date', 'Ngày cấp CCCD sai định dạng.');
        }

        // Quận dạy được: phải nằm trong danh sách HCMC
        $districts = isset($in['teachable_districts']) ? (array) $in['teachable_districts'] : array();
        foreach ($districts as $district) {
            if (!in_array((string) $district, self::HCMC_DISTRICTS, true)) {
                $this->addError('teachable_districts', 'Quận "' . (string) $district . '" không thuộc danh sách TP.HCM.');
                break;
            }
        }

        // Môn dạy được: id phải tồn tại
        $subjectIds = isset($in['teachable_subjects']) ? (array) $in['teachable_subjects'] : array();
        $validIds = array();
        foreach ($subjectIds as $sid) {
            if ((int) $sid > 0) {
                $validIds[] = (int) $sid;
            }
        }
        if ($validIds !== array()) {
            $placeholders = implode(', ', array_fill(0, count($validIds), '?'));
            $found = (int) $this->db->count('db_lms_subjects', 'id in (' . $placeholders . ')', $validIds);
            if ($found !== count($validIds)) {
                $this->addError('teachable_subjects', 'Một số môn học được chọn không tồn tại.');
            }
        }
    }

    /**
     * Gom data ghi vào db_lms_teachers.
     *
     * @param array $in
     * @param bool  $isCreate
     * @return array
     */
    private function teacherRow(array $in, $isCreate)
    {
        $districts = isset($in['teachable_districts']) ? (array) $in['teachable_districts'] : array();
        $districts = array_values(array_filter($districts, function ($v) {
            return (string) $v !== '';
        }));
        $subjects = isset($in['teachable_subjects']) ? (array) $in['teachable_subjects'] : array();
        $subjects = array_values(array_filter(array_map('intval', $subjects), function ($v) {
            return $v > 0;
        }));

        $row = array(
            'full_name'             => $this->trimIn($in, 'full_name'),
            'gender'                => $this->trimIn($in, 'gender') !== '' ? $this->trimIn($in, 'gender') : null,
            'dob'                   => $this->trimIn($in, 'dob') !== '' ? $this->trimIn($in, 'dob') : null,
            'cccd'                  => $this->trimIn($in, 'cccd') !== '' ? $this->trimIn($in, 'cccd') : null,
            'issue_date'            => $this->trimIn($in, 'issue_date') !== '' ? $this->trimIn($in, 'issue_date') : null,
            'issue_place'           => $this->trimIn($in, 'issue_place'),
            'tax_code'              => $this->trimIn($in, 'tax_code') !== '' ? $this->trimIn($in, 'tax_code') : null,
            'teachable_districts'   => $districts !== array() ? json_encode($districts, JSON_UNESCAPED_UNICODE) : null,
            'teachable_subjects'    => $subjects !== array() ? json_encode($subjects) : null,
            'can_teach_online'      => empty($in['can_teach_online']) ? 0 : 1,
            'rank'                  => $this->trimIn($in, 'rank', 'bronze'),
            'max_classes'           => (int) $this->trimIn($in, 'max_classes', '1'),
            'status'                => $this->trimIn($in, 'status', 'active'),
            'service_fee_percentage' => (float) $this->trimIn($in, 'service_fee_percentage', '48'),
            'is_commit_08'          => empty($in['is_commit_08']) ? 0 : 1,
            'bank_name'             => $this->trimIn($in, 'bank_name'),
            'bank_account'          => $this->trimIn($in, 'bank_account'),
            'bank_owner'            => $this->trimIn($in, 'bank_owner'),
        );
        if ($isCreate) {
            $row['teacher_code'] = $this->trimIn($in, 'teacher_code');
            if ($row['teacher_code'] === '') {
                $row['teacher_code'] = $this->nextTeacherCode();
            }
        }
        return $row;
    }

    /**
     * Chi tiết 1 giáo viên (join users + teachers).
     *
     * @param int $userId
     * @return array
     */
    public function getTeacher($userId)
    {
        return $this->db->safeFetchOne(
            'select u.id, u.username, u.email, u.phone, u.status as user_status,'
            . ' u.last_login_at, u.created_at,'
            . ' t.user_id, t.teacher_code, t.full_name, t.gender, t.dob, t.cccd,'
            . ' t.issue_date, t.issue_place, t.tax_code, t.teachable_districts,'
            . ' t.teachable_subjects, t.can_teach_online, t.rank, t.max_classes,'
            . ' t.status as teacher_status, t.service_fee_percentage, t.is_commit_08,'
            . ' t.bank_name, t.bank_account, t.bank_owner'
            . ' from db_lms_users u'
            . ' join db_lms_teachers t on t.user_id = u.id'
            . ' where u.id = ? and u.role = ? limit 1',
            array((int) $userId, self::ROLE_TEACHER)
        );
    }

    /**
     * Danh sách giáo viên kèm số lớp đang dạy + số tick tháng hiện tại.
     *
     * @param array $filters array('q' => string, 'status' => string, 'rank' => string)
     * @param int   $page
     * @param int   $perPage
     * @return array
     */
    public function listTeachers(array $filters = array(), $page = 1, $perPage = 20)
    {
        $page = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset = ($page - 1) * $perPage;

        $where = array('u.role = ?');
        $params = array(self::ROLE_TEACHER);

        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $where[] = '(t.teacher_code like ? or t.full_name like ? or u.username like ? or u.phone like ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }

        $status = isset($filters['status']) ? (string) $filters['status'] : '';
        if ($status !== '' && isset(self::TEACHER_STATUSES[$status])) {
            $where[] = 't.status = ?';
            $params[] = $status;
        }

        $rank = isset($filters['rank']) ? (string) $filters['rank'] : '';
        if ($rank !== '' && isset(self::TEACHER_RANKS[$rank])) {
            $where[] = 't.rank = ?';
            $params[] = $rank;
        }

        $whereSql = implode(' and ', $where);

        $countRow = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_users u join db_lms_teachers t on t.user_id = u.id'
            . ' where ' . $whereSql,
            $params
        );
        $total = isset($countRow['c']) ? (int) $countRow['c'] : 0;

        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        $items = $this->db->safeFetch(
            'select u.id, u.username, u.email, u.phone, u.status as user_status, u.created_at,'
            . ' t.teacher_code, t.full_name, t.gender, t.rank, t.max_classes,'
            . ' t.status as teacher_status, t.service_fee_percentage, t.is_commit_08,'
            . ' t.can_teach_online, t.teachable_districts, t.teachable_subjects,'
            . ' (select count(*) from db_lms_classes c'
            . '    where c.teacher_id = t.user_id and c.status = "active" and c.deleted_at is null) as active_classes,'
            . ' (select coalesce(sum(v.tick_amount), 0) from db_lms_violations v'
            . '    where v.teacher_id = t.user_id and v.violation_date >= ? and v.violation_date <= ?) as month_ticks'
            . ' from db_lms_users u'
            . ' join db_lms_teachers t on t.user_id = u.id'
            . ' where ' . $whereSql
            . ' order by ' . $this->teacherSort($filters) . ', u.id desc'
            . ' limit ' . $offset . ', ' . $perPage,
            array_merge(array($monthStart, $monthEnd), $params)
        );

        return array('items' => $items, 'total' => $total);
    }

    /**
     * Cột sort cho danh sách giáo viên (whitelist, chống injection qua query string).
     *
     * @param array $filters
     * @return string
     */
    private function teacherSort(array $filters)
    {
        $map = array(
            'name'   => 't.full_name asc',
            'code'   => 't.teacher_code asc',
            'rank'   => 'field(t.rank, "green", "gold", "bronze")',
            'fee'    => 't.service_fee_percentage desc',
        );
        $sort = isset($filters['sort']) ? (string) $filters['sort'] : 'name';
        return isset($map[$sort]) ? $map[$sort] : $map['name'];
    }

    /**
     * Xoá giáo viên (profile + tài khoản). Đang chủ nhiệm lớp sẽ bị từ chối.
     *
     * @param int $userId
     * @return bool
     */
    public function deleteTeacher($userId)
    {
        $this->clearErrors();
        $userId = (int) $userId;
        if ($userId <= 0) {
            $this->addError('system', 'ID không hợp lệ.');
            return false;
        }

        $row = $this->getTeacher($userId);
        if (empty($row)) {
            $this->addError('system', 'Giáo viên không tồn tại.');
            return false;
        }

        $classes = (int) $this->db->count(
            'db_lms_classes',
            'teacher_id = ? and status = "active" and deleted_at is null',
            array($userId)
        );
        if ($classes > 0) {
            $this->addError('system', 'Giáo viên đang chủ nhiệm ' . $classes . ' lớp. Hãy chuyển lớp hoặc tạm ngưng thay vì xoá.');
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->db->safeExec('delete from db_lms_teachers where user_id = ?', array($userId));
            $this->db->safeExec('delete from db_lms_users where id = ? and role = ?', array($userId, self::ROLE_TEACHER));
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[LMS] deleteTeacher failed: ' . $e->getMessage());
            $this->addError('system', 'Không thể xoá giáo viên (dữ liệu đang được tham chiếu).');
            return false;
        }

        $this->audit('delete_teacher', 'teachers', $userId, $row, null);
        return true;
    }

    // ===================== Trạng thái tài khoản (dùng chung) =====================

    /**
     * Khoá / mở khoá tài khoản (SRS ADM-01/ADM-02 alternative flow).
     *
     * @param int    $userId
     * @param string $status 'active' | 'locked'
     * @return bool
     */
    public function setUserStatus($userId, $status)
    {
        $this->clearErrors();
        $status = (string) $status;
        if (!isset(self::USER_STATUSES[$status])) {
            $this->addError('system', 'Trạng thái không hợp lệ.');
            return false;
        }
        $userId = (int) $userId;
        $row = $this->db->safeFetchOne('select id, status from db_lms_users where id = ? limit 1', array($userId));
        if (empty($row)) {
            $this->addError('system', 'Tài khoản không tồn tại.');
            return false;
        }
        $ok = $this->db->update('db_lms_users', array('status' => $status), 'id = ?', array($userId));
        if ($ok) {
            $this->audit($status === 'locked' ? 'lock_user' : 'unlock_user', 'users', $userId, $row, array('status' => $status));
        }
        return $ok;
    }

    /**
     * Decode JSON an toàn về mảng.
     *
     * @param mixed  $raw
     * @return array
     */
    public static function decodeJsonArray($raw)
    {
        if (is_array($raw)) {
            return $raw;
        }
        $raw = (string) $raw;
        if ($raw === '') {
            return array();
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : array();
    }
}
