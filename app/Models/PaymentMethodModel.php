<?php

namespace App\Models;

class PaymentMethodModel extends \App\Core\Model
{
    public $table = 'db_payment_methods';
    public bool $use_lang = true;
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';

    // Tránh loop vô hạn bằng cách nhận trực tiếp $value từ __get thay vì gọi lại $this->api_config
    public function getApiConfigAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // Tương tự, gán thẳng vào $this->attributes để tránh loop khi dùng __set
    public function setApiConfigAttribute($value)
    {
        $this->attributes['api_config'] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
}
