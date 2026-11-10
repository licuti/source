<?php
/** Admin LMS — audit log viewer (read-only). */
if (!defined('_source')) {
    die('Error');
}

$d = lms_db();
$user = lms_current_user();
$alias = 'lms-audit-log';
lms_form_guard($d, $user, $alias, 'view', 'admin');

$filterActor = isset($_GET['actor']) ? trim((string) $_GET['actor']) : '';
$filterAction = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
$filterTarget = isset($_GET['target']) ? trim((string) $_GET['target']) : '';
$dateFrom = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 50;

$where = array('1=1');
$params = array();
if ($filterActor !== '' && ctype_digit($filterActor)) {
    $where[] = 'a.actor_user_id = ?';
    $params[] = (int) $filterActor;
}
if ($filterAction !== '') {
    $where[] = 'a.action = ?';
    $params[] = $filterAction;
}
if ($filterTarget !== '') {
    $where[] = 'a.target_type = ?';
    $params[] = $filterTarget;
}
if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $where[] = 'a.created_at >= ?';
    $params[] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $where[] = 'a.created_at < date_add(?, interval 1 day)';
    $params[] = $dateTo;
}
$whereSql = implode(' and ', $where);
$fromSql = ' from db_lms_audit_logs a'
    . ' left join db_lms_users u on u.id = a.actor_user_id';

$count = $d->safeFetchOne(
    'select count(*) as c' . $fromSql . ' where ' . $whereSql,
    $params
);
$total = isset($count['c']) ? (int) $count['c'] : 0;
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$items = $d->safeFetch(
    'select a.id, a.actor_user_id, a.actor_type, a.action, a.target_type, a.target_id,'
    . ' a.before_data, a.after_data, a.ip_address, a.user_agent, a.created_at, u.username'
    . $fromSql
    . ' where ' . $whereSql
    . ' order by a.created_at desc, a.id desc'
    . ' limit ' . $offset . ', ' . $perPage,
    $params
);

// Bộ lọc dựng từ dữ liệu thật để dropdown luôn khớp giá trị đang có.
$actorOptions = array();
$actorRows = $d->safeFetch(
    'select a.actor_user_id as id, u.username'
    . $fromSql
    . ' where a.actor_user_id is not null'
    . ' group by a.actor_user_id, u.username'
    . ' order by u.username'
);
foreach ($actorRows as $row) {
    $actorId = (int) $row['id'];
    $username = (string) $row['username'];
    $actorOptions[(string) $actorId] = $username !== '' ? $username . ' (#' . $actorId . ')' : '#' . $actorId;
}

$actionOptions = array();
foreach ($d->safeFetch('select distinct action from db_lms_audit_logs order by action') as $row) {
    $actionOptions[(string) $row['action']] = (string) $row['action'];
}

$targetOptions = array();
foreach ($d->safeFetch('select distinct target_type from db_lms_audit_logs order by target_type') as $row) {
    $targetOptions[(string) $row['target_type']] = (string) $row['target_type'];
}

$title = 'Nhật ký hệ thống';
$template = 'lms-audit-log/hienthi';
