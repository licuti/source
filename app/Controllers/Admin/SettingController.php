<?php
namespace App\Controllers\Admin;

use App\Models\SettingModel;
use Illuminate\Http\Request;

class SettingController extends BaseAdminController {
    
    public function index() {
        // Fetch all settings without language constraint
        $records = SettingModel::withoutLang()->get();
        $settings = [];
        $firstItem = null;
        
        foreach($records as $record) {
            $settings[$record->lang] = $record;
            if ($firstItem === null) {
                $firstItem = $record;
            }
        }
        
        return $this->render('admin.setting.index', compact('settings', 'firstItem'));
    }

    public function update() {
        $langs = $this->langs;
        
        // Cấu trúc Schema dùng chung cho mọi ngôn ngữ, lấy từ form submit (tab đầu tiên hoặc input hidden)
        $schemaStr = $_POST['schema_config'] ?? '[]';
        $schema = json_decode($schemaStr, true);
        if (!is_array($schema)) $schema = [];
        
        foreach($langs as $lang) {
            $c = $lang['code'];
            $companyName = $_POST['company_name'][$c] ?? '';
            $logoImage = $_POST['logo_image'][$c] ?? '';
            $faviconImage = $_POST['favicon_image'][$c] ?? '';
            $payload = $_POST['data_payload'][$c] ?? [];
            
            // Tìm bản ghi hoặc tạo mới
            $record = SettingModel::withoutLang()->where('lang', $c)->first();
            if (!$record) {
                $record = new SettingModel();
                $record->lang = $c;
            }
            
            $record->company_name = $companyName;
            $record->logo_image = $logoImage;
            $record->favicon_image = $faviconImage;
            $record->schema_config = $schema;
            $record->data_payload = $payload;
            
            $record->save();
        }
        
        return $this->redirect(route('admin.setting.index'))->with('success', 'Cập nhật cấu hình thành công!');
    }
}
