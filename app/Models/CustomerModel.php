<?php

namespace App\Models;

class CustomerModel extends \App\Core\Model
{
    public $table = 'db_customers';
    public $primaryKey = 'id';
    public bool $use_lang = false;
    
    protected array $fillable = [
        'code',
        'fullname',
        'phone',
        'email',
        'password',
        'avatar',
        'birthday',
        'gender',
        'address',
        'country_id',
        'province_id',
        'district_id',
        'ward_id',
        'status',
        'google_id',
        'facebook_id'
    ];
}
