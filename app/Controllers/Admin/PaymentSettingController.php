<?php

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Request;

class PaymentSettingController extends BaseAdminController
{
    public function index(Request $request)
    {
        return $this->render('admin.payment.index');
    }

    public function save(Request $request)
    {
        // Logic to save settings will be implemented later
        return redirect(route('admin.payment.index'))->with('success', 'Cập nhật cài đặt thành công!');
    }
}
