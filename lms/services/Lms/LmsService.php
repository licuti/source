<?php
/**
 * Base cho mọi service LMS.
 *
 * Service layer giữ business rules (BR-TICK/BR-PAY/BR-SCHED/BR-AUTH) tách khỏi
 * template và router (plan §4.3). Mọi logic tiền bạc bắt buộc có PHPUnit test
 * trước khi merge (R2, Phase 2).
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

abstract class LmsService
{
    /** @var \LmsDb */
    protected $db;

    /** @var array Lỗi validate gần nhất (field => message) */
    protected $errors = array();

    public function __construct(\LmsDb $db = null)
    {
        if ($db === null) {
            $db = function_exists('lms_db') ? lms_db() : null;
        }
        $this->db = $db;
    }

    /**
     * @return \LmsDb
     */
    public function db()
    {
        return $this->db;
    }

    /**
     * @return array lỗi validate
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * @return bool có lỗi validate không
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    protected function addError($field, $message)
    {
        $this->errors[$field] = $message;
    }

    protected function clearErrors()
    {
        $this->errors = array();
    }

    /**
     * Thời gian hiện tại (giờ VN).
     *
     * @return string 'Y-m-d H:i:s'
     */
    protected function now()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Ghi audit log cho thao tác nhạy cảm (RBAC: chỉ admin xem).
     *
     * @param string      $action
     * @param string      $targetType
     * @param int|null    $targetId
     * @param array|null  $before
     * @param array|null  $after
     * @param string|null $actorType 'user' | 'admin' | 'teacher' | 'student' | 'system_cron'
     */
    public function audit($action, $targetType, $targetId = null, $before = null, $after = null, $actorType = 'user')
    {
        if (!$this->db) {
            return;
        }
        $actorId = null;
        if (function_exists('lms_current_user')) {
            $u = lms_current_user();
            if (is_array($u) && !empty($u['id'])) {
                $actorId = (int) $u['id'];
                if ($actorType === 'user' && !empty($u['role'])) {
                    $actorType = $u['role'];
                }
            }
        }
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

        $this->db->insert('db_lms_audit_logs', array(
            'actor_user_id' => $actorId,
            'actor_type'    => $actorType,
            'action'        => $action,
            'target_type'   => $targetType,
            'target_id'     => $targetId === null ? 0 : (int) $targetId,
            'before_data'   => $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE),
            'after_data'    => $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE),
            'ip_address'    => $ip,
            'user_agent'    => $ua,
        ));
    }
}
