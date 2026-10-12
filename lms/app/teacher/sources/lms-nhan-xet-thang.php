<?php
/** Teacher TCH-06 — monthly student reviews. */
if (!defined('_source')) { die('Error'); }
$d=lms_db(); $user=lms_current_user(); $alias='lms-nhan-xet-thang';
$teacherId=is_array($user)&&!empty($user['id'])?(int)$user['id']:0;
$svc=new \Lms\MonthlyReviewService($d);
$action=isset($a)?(string)$a:'man';
if($action==='luu'){
    lms_form_guard($d,$user,$alias,'edit','teacher');
    if(!lms_csrf_verify()){ lms_flash('error','Phiên làm việc không hợp lệ.'); header('Location: '.lms_url($alias)); exit; }
    $publish=isset($_POST['submit_action'])&&$_POST['submit_action']==='publish';
    if($svc->saveForTeacher($teacherId,$_POST,$publish)){
        lms_flash('success',$publish?'Đã đăng nhận xét tháng cho học viên.':'Đã lưu bản nháp nhận xét tháng.');
    } else { $errors=$svc->errors(); lms_flash('error',$errors?reset($errors):'Không thể lưu nhận xét.'); }
    $month=isset($_POST['review_month'])?(string)$_POST['review_month']:date('Y-m');
    header('Location: '.lms_url($alias,'man',array('month'=>$month))); exit;
}
$month=isset($_GET['month'])&&preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',(string)$_GET['month'])?(string)$_GET['month']:date('Y-m');
$items=$svc->listForTeacher($teacherId,$month);
$title='Nhận xét theo tháng'; $template='lms-nhan-xet-thang/hienthi';
