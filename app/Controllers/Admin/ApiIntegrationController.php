<?php

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Request;

class ApiIntegrationController extends BaseAdminController
{
    public function index(Request $request)
    {
        return $this->render('admin.api_integration.index');
    }

    public function save(Request $request)
    {
        // Logic to save settings will be implemented later
        return redirect(route('admin.api_integration.index'))->with('success', 'Cập nhật cài đặt thành công!');
    }
}
