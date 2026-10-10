<?php
/**
 * Logic chung cho module ẩn `lms-thong-bao` (T10 — chuông thông báo).
 *
 * Được require từ lms/app/<portal>/sources/lms-thong-bao.php của 3 portal.
 * Router đã check auth gate + quyền view trước khi vào đây.
 *
 * Action:
 *   - 'unread'         (GET)  → JSON danh sách thông báo chưa đọc (cho dropdown chuông)
 *   - 'mark_read'      (POST) → JSON đánh dấu 1 thông báo đã đọc (CSRF bắt buộc)
 *   - 'mark_all_read'  (POST) → JSON đánh dấu tất cả đã đọc (CSRF bắt buộc)
 *   - mặc định         (GET)  → trang danh sách đầy đủ (template)
 *
 * Endpoint AJAX có đủ: auth gate (router) + CSRF (POST) + quyền portal (router).
 * Row-level: NotificationService chỉ trả/đánh dấu thông báo của user hiện tại.
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d    = lms_db();
$user = lms_current_user();
$userId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;

$notifSvc = new \Lms\NotificationService($d);

$typeIcons = array(
    'tick_warning'      => 'fa-solid fa-triangle-exclamation',
    'request_approved'  => 'fa-solid fa-circle-check',
    'request_rejected'  => 'fa-solid fa-circle-xmark',
    'session_reminder'  => 'fa-solid fa-calendar-day',
    'payment_received'  => 'fa-solid fa-money-bill-wave',
    'system'            => 'fa-solid fa-circle-info',
);

$typeColors = array(
    'tick_warning'      => '#dc3545',
    'request_approved'  => '#198754',
    'request_rejected'  => '#dc3545',
    'session_reminder'  => '#0d6efd',
    'payment_received'  => '#F26B2A',
    'system'            => '#6c757d',
);

// ===================== AJAX actions =====================

$action = isset($a) ? (string) $a : (isset($_GET['a']) ? (string) $_GET['a'] : '');

if ($action === 'unread') {
    $items = $notifSvc->listForUser($userId, 8);
    $unread = $notifSvc->unreadCount($userId);

    $out = array();
    foreach ($items as $row) {
        $type = isset($row['type']) ? (string) $row['type'] : 'system';
        $out[] = array(
            'id'         => (int) $row['id'],
            'type'       => $type,
            'icon'       => isset($typeIcons[$type]) ? $typeIcons[$type] : $typeIcons['system'],
            'color'      => isset($typeColors[$type]) ? $typeColors[$type] : $typeColors['system'],
            'title'      => (string) $row['title'],
            'message'    => (string) $row['message'],
            'link_url'   => (string) $row['link_url'],
            'is_read'    => (int) $row['is_read'],
            'created_at' => (string) $row['created_at'],
        );
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => true, 'items' => $out, 'unread' => $unread), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'mark_read') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !lms_csrf_verify()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'error' => 'Yêu cầu không hợp lệ (CSRF).'));
        exit;
    }
    $notifId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $notifSvc->markRead($notifId, $userId);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => true, 'unread' => $notifSvc->unreadCount($userId)), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'mark_all_read') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !lms_csrf_verify()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'error' => 'Yêu cầu không hợp lệ (CSRF).'));
        exit;
    }
    $items = $notifSvc->listForUser($userId, 200);
    $cleared = 0;
    foreach ($items as $row) {
        if (empty($row['is_read']) && $notifSvc->markRead((int) $row['id'], $userId)) {
            $cleared++;
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => true, 'cleared' => $cleared, 'unread' => 0), JSON_UNESCAPED_UNICODE);
    exit;
}

// ===================== Trang danh sách đầy đủ =====================

$notifications = $notifSvc->listForUser($userId, 50);
$unreadCount = $notifSvc->unreadCount($userId);

$title    = 'Thông báo';
$template = 'lms-thong-bao/hienthi';
