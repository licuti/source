<?php

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Request;
use LangModel;

class LanguageSettingController extends BaseAdminController
{
    public function index(Request $request)
    {
        $languages = LangModel::orderBy('sort_order', 'ASC')->get();
        return $this->render('admin.language.index', ['languages' => $languages]);
    }

    public function create(Request $request)
    {
        return $this->render('admin.language.form');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        
        $insertData = [
            'code'            => $data['code'] ?? '',
            'name'            => $data['name'] ?? '',
            'label'           => $data['label'] ?? '',
            'image'           => $data['image'] ?? '',
            'price_unit'      => $data['price_unit'] ?? 'VND',
            'currency_symbol' => $data['currency_symbol'] ?? '',
            'locale'          => $data['locale'] ?? '',
            'is_default'      => isset($data['is_default']) ? 1 : 0,
            'is_active'       => isset($data['is_active']) ? 1 : 0,
            'is_rtl'          => isset($data['is_rtl']) ? 1 : 0,
            'sort_order'      => (int)($data['sort_order'] ?? 0)
        ];

        // Validate code uniqueness
        if (empty($insertData['code'])) {
            $_SESSION['error'] = 'Mã ngôn ngữ không được để trống!';
            return $this->redirect(route('admin.language.create'));
        }
        if (LangModel::where('code', $insertData['code'])->first()) {
            $_SESSION['error'] = 'Mã ngôn ngữ ('.$insertData['code'].') đã tồn tại!';
            return $this->redirect(route('admin.language.create'));
        }

        // If this is set as default, unset default for others and force active
        if ($insertData['is_default']) {
            $insertData['is_active'] = 1;
            LangModel::where('id', '>', 0)->update(['is_default' => 0]);
        }

        $newLang = LangModel::create($insertData);
        $this->generateConfigFile();

        if (($request->all()['submit_action'] ?? '') === 'save_and_edit') {
            return $this->redirect(route('admin.language.edit', ['id' => $newLang->id]));
        }
        return $this->redirect(route('admin.language.index'));
    }

    public function edit(Request $request, $id)
    {
        $id = is_array($id) ? ($id['id'] ?? 0) : $id;
        $language = LangModel::find($id);
        if (!$language) {
            return $this->redirect(route('admin.language.index'));
        }

        return $this->render('admin.language.form', ['language' => $language]);
    }

    public function update(Request $request, $id)
    {
        $id = is_array($id) ? ($id['id'] ?? 0) : $id;
        $language = LangModel::find($id);
        if (!$language) {
            return $this->redirect(route('admin.language.index'));
        }

        $data = $request->all();
        
        $updateData = [
            'code'            => $data['code'] ?? $language->code,
            'name'            => $data['name'] ?? $language->name,
            'label'           => $data['label'] ?? $language->label,
            'image'           => $data['image'] ?? $language->image,
            'price_unit'      => $data['price_unit'] ?? $language->price_unit,
            'currency_symbol' => $data['currency_symbol'] ?? $language->currency_symbol,
            'locale'          => $data['locale'] ?? $language->locale,
            'is_default'      => isset($data['is_default']) ? 1 : 0,
            'is_active'       => isset($data['is_active']) ? 1 : 0,
            'is_rtl'          => isset($data['is_rtl']) ? 1 : 0,
            'sort_order'      => (int)($data['sort_order'] ?? 0)
        ];

        // Validate code uniqueness
        if (empty($updateData['code'])) {
            $_SESSION['error'] = 'Mã ngôn ngữ không được để trống!';
            return $this->redirect(route('admin.language.edit', ['id' => $id]));
        }
        $existing = LangModel::where('code', $updateData['code'])->first();
        if ($existing && $existing->id != $id) {
            $_SESSION['error'] = 'Mã ngôn ngữ ('.$updateData['code'].') đã tồn tại ở bản ghi khác!';
            return $this->redirect(route('admin.language.edit', ['id' => $id]));
        }

        // If this is set as default, unset default for others and force active
        if ($updateData['is_default']) {
            $updateData['is_active'] = 1;
            LangModel::where('id', '!=', $id)->update(['is_default' => 0]);
        }

        LangModel::where('id', $id)->update($updateData);
        $this->generateConfigFile();

        if (($data['submit_action'] ?? '') === 'save_and_edit') {
            return $this->redirect(route('admin.language.edit', ['id' => $id]));
        }
        return $this->redirect(route('admin.language.index'));
    }

    public function destroy(Request $request, $id)
    {
        $id = is_array($id) ? ($id['id'] ?? 0) : $id;
        $language = LangModel::find($id);
        if (!$language) {
            return $this->redirect(route('admin.language.index'));
        }

        // Prevent deleting the default language
        if ($language->is_default) {
            return $this->redirect(route('admin.language.index'));
        }

        LangModel::where('id', $id)->delete();
        $this->generateConfigFile();

        return $this->redirect(route('admin.language.index'));
    }

    /**
     * Tự động tạo file config/languages.php dựa trên dữ liệu trong DB
     */
    private function generateConfigFile()
    {
        // Lấy tất cả ngôn ngữ đang active
        $languages = LangModel::where('is_active', 1)->orderBy('sort_order', 'ASC')->get();
        
        $configArray = [];
        foreach ($languages as $lang) {
            $configArray[$lang->code] = [
                "code"            => $lang->code,
                "label"           => $lang->label,
                "name"            => $lang->name,
                "image"           => $lang->image,
                "price"           => $lang->price_unit,
                "currency_symbol" => $lang->currency_symbol,
                "locale"          => $lang->locale,
                "is_rtl"          => $lang->is_rtl
            ];
        }

        $basePath = dirname(dirname(dirname(dirname(__FILE__))));
        $filePath = $basePath . '/config/languages.php';
        
        $content = "<?php\n/**\n * Mảng cấu hình ngôn ngữ này được TỰ ĐỘNG TẠO từ module Quản lý ngôn ngữ trong Admin.\n * Vui lòng KHÔNG chỉnh sửa trực tiếp file này.\n */\n\nreturn [\n";
        
        foreach ($configArray as $code => $data) {
            $content .= "    '{$code}' => [\n";
            $content .= "        \"code\"            => \"{$data['code']}\",\n";
            $content .= "        \"label\"           => \"{$data['label']}\",\n";
            $content .= "        \"name\"            => \"{$data['name']}\",\n";
            $content .= "        \"image\"           => \"{$data['image']}\",\n";
            $content .= "        \"price\"           => \"{$data['price']}\",\n";
            $content .= "        \"currency_symbol\" => \"{$data['currency_symbol']}\",\n";
            $content .= "        \"locale\"          => \"{$data['locale']}\",\n";
            $content .= "        \"is_rtl\"          => " . ($data['is_rtl'] ? "true" : "false") . "\n";
            $content .= "    ],\n";
        }
        $content .= "];\n";

        file_put_contents($filePath, $content);
    }
}
