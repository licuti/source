<?php
/** Admin LMS — edit class rules document; student portal renders escaped text. */
if (!defined('_source')) { die('Error'); }
$d=lms_db(); $user=lms_current_user(); $alias='lms-noi-quy';
lms_form_guard($d,$user,$alias,'view','admin');
$svc=new \Lms\ClassRulesService($d);
$errors=array();
if(isset($_SERVER['REQUEST_METHOD'])&&$_SERVER['REQUEST_METHOD']==='POST'){
    lms_form_guard($d,$user,$alias,'edit','admin');
    if(!lms_csrf_verify()){
        lms_flash('error','Phiên làm việc không hợp lệ.'); header('Location: '.lms_url($alias)); exit;
    }
    if($svc->saveRules(isset($_POST['content'])?$_POST['content']:'',(int)$user['id'])){
        lms_flash('success','Đã cập nhật nội quy lớp học.'); header('Location: '.lms_url($alias)); exit;
    }
    $errors=$svc->errors();
}
$rules=$svc->getRules();
if(isset($_POST['content'])){$rules['content']=(string)$_POST['content'];}
$title='Quản lý nội quy'; $template='lms-noi-quy/hienthi';
