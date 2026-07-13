<?php

namespace App\Controllers;

class FrontendController extends Controller {
    protected $layout = 'layouts.main';

    /**
     * Render giao diện kèm dữ liệu chung cho Frontend
     */
    protected function render($view, $data = []) {
        // Có thể load các dữ liệu chung cho Frontend ở đây
        // Ví dụ: categories, menu, footer info...

        return parent::render($view, $data);
    }
}
