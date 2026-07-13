<?php

namespace App\Models;

class CustomerModel extends \App\Core\Database\Model
{
    public $table = 'db_customers';
    public $primaryKey = 'id';
    
    
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
