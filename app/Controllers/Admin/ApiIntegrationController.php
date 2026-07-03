<?php

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Request;
use App\Models\OptionModel;

class ApiIntegrationController extends BaseAdminController
{
    public function index(Request $request)
    {
        $head_scripts = OptionModel::getValue('api_head_scripts', '');
        $body_scripts = OptionModel::getValue('api_body_scripts', '');
        $footer_scripts = OptionModel::getValue('api_footer_scripts', '');

        return $this->render('admin.api_integration.index', compact('head_scripts', 'body_scripts', 'footer_scripts'));
    }

    public function save(Request $request)
    {
        // Nhận dữ liệu không qua filter để giữ nguyên thẻ <script>
        $head_scripts = $_POST['api_head_scripts'] ?? '';
        $body_scripts = $_POST['api_body_scripts'] ?? '';
        $footer_scripts = $_POST['api_footer_scripts'] ?? '';

        OptionModel::setValue('api_head_scripts', $head_scripts);
        OptionModel::setValue('api_body_scripts', $body_scripts);
        OptionModel::setValue('api_footer_scripts', $footer_scripts);

        return $this->redirect(route('admin.api_integration.index'))->with('success', 'Đã lưu cấu hình Script thành công!');
    }
}
