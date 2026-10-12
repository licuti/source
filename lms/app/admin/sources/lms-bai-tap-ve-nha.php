<?php
/** Admin LMS — báo cáo BTVN/lesson summary, read-only. */
if (!defined('_source')) { die('Error'); }
$d = lms_db(); $user = lms_current_user(); $alias = 'lms-bai-tap-ve-nha';
lms_form_guard($d, $user, $alias, 'view', 'admin');
$svc = new \Lms\AdminReportService($d);
$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$dateFrom = isset($_GET['date_from']) ? (string) $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? (string) $_GET['date_to'] : '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 25;
$res = $svc->homeworkList(array('q' => $q, 'date_from' => $dateFrom, 'date_to' => $dateTo), $page, $perPage);
$items = $res['items']; $total = $res['total']; $totalPages = max(1, (int) ceil($total / $perPage));
$title = 'Quản lý BTVN'; $template = 'lms-bai-tap-ve-nha/hienthi';
