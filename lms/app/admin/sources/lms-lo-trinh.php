<?php
/** Admin LMS — read-only curriculum/topic catalogue. */
if (!defined('_source')) { die('Error'); }
$d = lms_db(); $user = lms_current_user(); $alias = 'lms-lo-trinh';
lms_form_guard($d, $user, $alias, 'view', 'admin');
$svc = new \Lms\AdminReportService($d);
$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$subjectId = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : 0;
$testFilter = isset($_GET['has_test']) ? (string) $_GET['has_test'] : '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1; $perPage=25;
$res=$svc->topicList(array('q'=>$q,'subject_id'=>$subjectId,'has_test'=>$testFilter),$page,$perPage);
$items=$res['items']; $total=$res['total']; $totalPages=max(1,(int)ceil($total/$perPage));
$subjects=$d->safeFetch('select s.id,s.name,p.name as program_name from db_lms_subjects s join db_lms_programs p on p.id=s.program_id order by p.name,s.name');
$title='Quản lý lộ trình'; $template='lms-lo-trinh/hienthi';
