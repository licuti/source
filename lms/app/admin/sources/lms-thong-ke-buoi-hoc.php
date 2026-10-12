<?php
/** Admin LMS — lesson/session statistics per student (read-only + CSV export). */
if (!defined('_source')) { die('Error'); }
$d=lms_db(); $user=lms_current_user(); $alias='lms-thong-ke-buoi-hoc';
lms_form_guard($d,$user,$alias,'view','admin');
$svc=new \Lms\AdminReportService($d);
$studentCode=isset($_GET['student_code'])?trim((string)$_GET['student_code']):'';
$dateFrom=isset($_GET['date_from'])?(string)$_GET['date_from']:'';
$dateTo=isset($_GET['date_to'])?(string)$_GET['date_to']:'';
$page=isset($_GET['page'])?max(1,(int)$_GET['page']):1; $perPage=25;
if(isset($a)&&$a==='xuat'){
    $data=$svc->lessonStats($studentCode,$dateFrom,$dateTo,1,10000);
    if(empty($data['student'])){ lms_flash('error','Không tìm thấy học viên.'); header('Location: '.lms_url($alias)); exit; }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="so-buoi-'.preg_replace('/[^A-Za-z0-9_-]/','',$studentCode).'.csv"');
    echo "\xEF\xBB\xBF";
    $out=fopen('php://output','w');
    fputcsv($out,array('Ngày','Giờ','Lớp','Mã giáo viên','Giáo viên','Trạng thái','Ghi chú'));
    foreach($data['items'] as $r){
        $row=array($r['session_date'],substr((string)$r['start_time'],0,5),$r['class_code'],$r['teacher_code'],$r['teacher_name'],$r['session_status'],$r['lesson_summary']);
        foreach($row as $i=>$cell){ if(is_string($cell)&&preg_match('/^[\s]*[=+@-]/',$cell)){ $row[$i]="'".$cell; } }
        fputcsv($out,$row);
    }
    fclose($out); exit;
}
$data=$svc->lessonStats($studentCode,$dateFrom,$dateTo,$page,$perPage);
$student=$data['student']; $items=$data['items']; $total=$data['total']; $finishedCount=$data['finished_count']; $totalPages=max(1,(int)ceil($total/$perPage));
$title='Thống kê số buổi học'; $template='lms-thong-ke-buoi-hoc/hienthi';
