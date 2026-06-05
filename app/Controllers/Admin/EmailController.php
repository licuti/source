<?php

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Request;

class EmailController extends BaseAdminController
{
    public function index(Request $request)
    {
        return $this->render('admin.email.index');
    }

    public function save(Request $request)
    {
        // Logic to save settings will be implemented later
        return redirect(route('admin.email.index'))->with('success', 'Cập nhật cài đặt thành công!');
    }
}
