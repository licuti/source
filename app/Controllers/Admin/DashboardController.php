<?php
namespace App\Controllers\Admin;

use App\Core\Request;

class DashboardController extends BaseAdminController {
    public function index(Request $request) {
        return $this->render('admin.dashboard.index');
    }
}
