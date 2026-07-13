<?php
namespace App\Controllers\Admin;

use App\Core\Request;

use App\Models\OptionModel;


class SeoConfigController extends BaseAdminController {
    
    public function index() {
        return $this->render('admin.seo_config.index');
    }

    public function save(\App\Core\Request $request) {
        $postData = $request->all();
        $langs = $this->langs;
        
        // Save multi-language fields
        foreach ($langs as $lang) {
            $c = $lang['code'];
            $title = $postData['seo_title'][$c] ?? '';
            $keyword = $postData['seo_keyword'][$c] ?? '';
            $description = $postData['seo_description'][$c] ?? '';
            
            OptionModel::setValue('seo_title_' . $c, $title);
            OptionModel::setValue('seo_keyword_' . $c, $keyword);
            OptionModel::setValue('seo_description_' . $c, $description);
        }
        
        // Save global fields
        OptionModel::setValue('seo_image', $postData['seo_image'] ?? '');
        OptionModel::setValue('seo_facebook_app_id', $postData['seo_facebook_app_id'] ?? '');
        OptionModel::setValue('seo_twitter_site', $postData['seo_twitter_site'] ?? '');
        OptionModel::setValue('seo_noindex', isset($postData['seo_noindex']) ? '1' : '0');
        
        return $this->redirect(route('admin.seo_config.index'))->with('success', 'Đã lưu cấu hình SEO mặc định.');
    }
}
