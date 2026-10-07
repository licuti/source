<?php
/**
 * page_assets.php — Quyết định nạp asset nào theo module/template.
 *
 * VÌ SAO CẦN:
 *   index.php cũ nạp TOÀN BỘ plugin nặng ở MỌI request (CKEditor ~700KB,
 *   DataTables, Select2, Sortable, SweetAlert2, Fancybox…). Nhưng thực tế:
 *     - DataTables: KHÔNG template admin nào dùng (không có [data-datatable])
 *     - Select2   : KHÔNG template admin nào dùng (không có .select2)
 *     - Sortable  : chỉ category/gallery (có [data-sortable])
 *     - CKEditor  : chỉ cần ở trang form (/them)
 *   Nạp thừa làm admin nặng và chậm.
 *
 * CÁCH DÙNG (trong index.php):
 *   $assets = admin_page_assets($template);
 *   foreach ($assets['head_js'] as $src) { ... }
 *   foreach ($assets['body_js'] as $src) { ... }
 *
 * AN TOÀN: nếu template KHÔNG khớp whitelist, hàm trả về mặc định AN TOÀN
 * (nạp CKEditor + các lib UI) để không làm vỡ module lạ.
 */

if (!function_exists('admin_page_assets')) {
    /**
     * @param  string $template  vd. 'san-pham/them', 'category/hienthi', 'index'
     * @return array{head_js:string[], body_js:string[]}
     */
    function admin_page_assets($template)
    {
        $template = (string) $template;
        $module   = strtok($template, '/');
        $leaf     = (strpos($template, '/') !== false) ? substr($template, strrpos($template, '/') + 1) : $template;

        // Trang nào được coi là FORM (cần CKEditor)
        $isForm = ($leaf === 'them' || $leaf === 'sua');

        // Whitelist CKEditor: các module có nội dung HTML soạn thảo
        $ckeModules = array(
            'san-pham', 'bai-viet', 'noi-dung', 'video', 'category', 'gallery',
            'seo-co-ban', 'text', 'module', 'upload-file', 'cong-tac-vien',
            'ma-khuyen-mai', 'flash-sale', 'doanh-thu', 'filter', 'coupon',
            'nhap-hang', 'quan-ly-van-chuyen', 'thanh-vien', 'thongtin-lienhe',
        );

        // Whitelist Sortable: các trang danh sách có kéo-thả thứ tự
        $sortableModules = array('category', 'gallery', 'menu');

        $needCke      = $isForm && in_array($module, $ckeModules, true);
        $needSortable = in_array($module, $sortableModules, true);

        // Nếu template KHÔNG nhận diện được (module lạ) -> nạp đủ để an toàn
        $isKnown = in_array($module, array_merge($ckeModules, $sortableModules, array('index', 'quan-ly-don-hang', 'bai-viet', 'san-pham')), true);
        if (!$isKnown) {
            $needCke      = true;
            $needSortable = true;
        }

        $headJs = array();
        $bodyJs = array();

        // jQuery + Fancybox là core, luôn nạp (nhiều module cũ phụ thuộc)
        $headJs[] = 'public/plugin/jquery/dist/jquery.min.js';
        $headJs[] = 'public/js/jquery.fancybox.min.js';
        $headJs[] = 'js/jquery.twbsPagination.js';

        // CKEditor (~700KB) chỉ nạp ở form có soạn thảo HTML
        if ($needCke) {
            $headJs[] = 'ckeditor/ckeditor.js';
        }

        // SortableJS chỉ nạp ở trang kéo-thả thứ tự (category/gallery/menu)
        if ($needSortable) {
            $bodyJs[] = 'public/plugin/sortablejs/Sortable.min.js';
        }

        return array(
            'head_js' => $headJs,
            'body_js' => $bodyJs,
        );
    }
}