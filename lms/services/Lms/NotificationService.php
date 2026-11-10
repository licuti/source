<?php
/**
 * NotificationService — hệ thống notification trong LMS.
 *
 * In-app notifications are stored in db_lms_notifications; password-reset email
 * is handled separately by MailService (SYS-03).
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class NotificationService extends LmsService
{
    /**
     * Tạo 1 thông báo cho 1 user.
     *
     * @param int    $userId
     * @param string $type    'tick_warning' | 'request_approved' | 'request_rejected'
     *                        | 'session_reminder' | 'payment_received' | 'system'
     * @param string $title
     * @param string $message
     * @param string $linkUrl
     * @return int|null notification id
     */
    public function notify($userId, $type, $title, $message = '', $linkUrl = '')
    {
        $this->clearErrors();
        $userId = (int) $userId;
        if ($userId <= 0) {
            $this->addError('user_id', 'User không hợp lệ.');
            return null;
        }

        $ok = $this->db->insert('db_lms_notifications', array(
            'user_id'  => $userId,
            'type'     => (string) $type,
            'title'    => (string) $title,
            'message'  => (string) $message,
            'link_url' => (string) $linkUrl,
            'is_read'  => 0,
        ));
        return $ok ? (int) $this->db->insertId : null;
    }

    /**
     * Thông báo cho nhiều user (VD: cả lớp).
     *
     * @param array  $userIds
     * @param string $type
     * @param string $title
     * @param string $message
     * @param string $linkUrl
     * @return int số thông báo đã tạo
     */
    public function notifyMany(array $userIds, $type, $title, $message = '', $linkUrl = '')
    {
        $count = 0;
        foreach (array_unique($userIds) as $uid) {
            if ($this->notify((int) $uid, $type, $title, $message, $linkUrl) !== null) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Đánh dấu đã đọc.
     *
     * @param int      $notificationId
     * @param int|null $userId verify chủ sở hữu
     * @return bool
     */
    public function markRead($notificationId, $userId = null)
    {
        $params = array((int) $notificationId);
        $where = 'id = ?';
        if ($userId !== null) {
            $where .= ' and user_id = ?';
            $params[] = (int) $userId;
        }
        return $this->db->update(
            'db_lms_notifications',
            array('is_read' => 1, 'read_at' => $this->now()),
            $where,
            $params
        );
    }

    /**
     * Số thông báo chưa đọc (cho badge chuông).
     *
     * @param int $userId
     * @return int
     */
    public function unreadCount($userId)
    {
        return $this->db->count(
            'db_lms_notifications',
            'user_id = ? and is_read = 0',
            array((int) $userId)
        );
    }

    /**
     * Danh sách thông báo (mới trước).
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function listForUser($userId, $limit = 20)
    {
        return $this->db->safeFetch(
            'select * from db_lms_notifications where user_id = ? order by created_at desc limit ' . (int) $limit,
            array((int) $userId)
        );
    }
}
