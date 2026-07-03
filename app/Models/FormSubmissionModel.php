<?php
namespace App\Models;

class FormSubmissionModel extends \Model {
    public $table = 'db_form_submissions';
    public bool $use_lang = false;
    public bool $timestamps = true;
    
    // 0: Mới, 1: Đã đọc, 2: Đã phản hồi
    public const STATUS_NEW = 0;
    public const STATUS_READ = 1;
    public const STATUS_REPLIED = 2;
}
