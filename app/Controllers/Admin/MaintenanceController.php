<?php

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Request;

class MaintenanceController extends BaseAdminController
{
    public function index(Request $request)
    {
        // Đọc các giá trị cấu hình hiện tại
        $status = get_option('maintenance_status', '0');
        $metaJson = get_option('maintenance_meta', '{}');
        $contentJson = get_option('maintenance_content', '{}');
        $exceptionsJson = get_option('maintenance_exceptions', '[]');
        $whitelistJson = get_option('maintenance_whitelist_ips', '[]');
        $tokensJson = get_option('maintenance_bypass_tokens', '[]');

        return $this->render('admin.maintenance.index', [
            'status' => $status,
            'meta' => json_decode($metaJson, true) ?: [],
            'content' => json_decode($contentJson, true) ?: [],
            'exceptions' => json_decode($exceptionsJson, true) ?: [],
            'whitelist' => json_decode($whitelistJson, true) ?: [],
            'tokens' => json_decode($tokensJson, true) ?: []
        ]);
    }

    public function save(Request $request)
    {
        // 1. Lưu trạng thái bật/tắt
        $oldStatus = get_option('maintenance_status', '0');
        $newStatus = $request->input('maintenance_status', '0');
        
        set_option('maintenance_status', $newStatus);
        
        // Cập nhật người bật và thời gian bật
        if ($newStatus == '1' && $oldStatus != '1') {
            $admin_id = $_SESSION['id_user'] ?? 0;
            $meta = [
                'user_id' => $admin_id,
                'enabled_at' => date('Y-m-d H:i:s')
            ];
            set_option('maintenance_meta', json_encode($meta));
        } elseif ($newStatus == '0') {
            // Xoá meta nếu tắt
            set_option('maintenance_meta', '{}');
        }

        // 2. Lưu nội dung hiển thị
        $content = [
            'title' => $request->input('title', 'Hệ thống đang bảo trì'),
            'description' => $request->input('description', ''),
            'eta' => $request->input('eta', ''),
            'bg_color' => $request->input('bg_color', '#0f0f13'),
            'logo' => get_option('maintenance_logo', '') // Giữ nguyên logo cũ nếu có
        ];
        
        // Xử lý upload logo (qua file manager trả về text)
        $logo = $request->input('logo', '');
        $content['logo'] = !empty($logo) ? ltrim($logo, '/') : '';
        set_option('maintenance_content', json_encode($content));

        // 3. Lưu mảng ngoại lệ path
        $exceptions = $request->input('exceptions', []);
        // Lọc bỏ phần tử rỗng
        $exceptions = array_filter($exceptions, function($v) { return !empty(trim($v)); });
        set_option('maintenance_exceptions', json_encode(array_values($exceptions)));

        // 4. Lưu mảng Whitelist IP
        $whitelistIps = $request->input('whitelist_ip', []);
        $whitelistLabels = $request->input('whitelist_label', []);
        $whitelist = [];
        if (is_array($whitelistIps)) {
            foreach ($whitelistIps as $idx => $ip) {
                if (!empty(trim($ip))) {
                    $whitelist[] = [
                        'ip' => trim($ip),
                        'label' => trim($whitelistLabels[$idx] ?? '')
                    ];
                }
            }
        }
        set_option('maintenance_whitelist_ips', json_encode($whitelist));

        // 5. Lưu mảng Token Bypass
        $tokenCodes = $request->input('token_code', []);
        $tokenExpires = $request->input('token_expire', []);
        $tokenLabels = $request->input('token_label', []);
        $tokens = [];
        if (is_array($tokenCodes)) {
            foreach ($tokenCodes as $idx => $code) {
                if (!empty(trim($code))) {
                    $tokens[] = [
                        'token' => trim($code),
                        'expires_at' => trim($tokenExpires[$idx] ?? ''),
                        'label' => trim($tokenLabels[$idx] ?? '')
                    ];
                }
            }
        }
        set_option('maintenance_bypass_tokens', json_encode($tokens));

        return $this->redirect(route('admin.maintenance.index'))->with('success', 'Đã lưu cấu hình bảo trì thành công!');
    }

    public function preview(Request $request) {
        // Trả về view trực tiếp để admin test thử giao diện
        $contentJson = get_option('maintenance_content', '{}');
        $content = json_decode($contentJson, true) ?: [];
        
        $viewData = [
            'title' => $content['title'] ?? 'Hệ thống đang bảo trì',
            'description' => $content['description'] ?? '<p>Chúng tôi đang tiến hành nâng cấp hệ thống. Vui lòng quay lại sau.</p>',
            'eta' => $content['eta'] ?? '',
            'bg_color' => $content['bg_color'] ?? '#0f0f13',
            'logo' => $content['logo'] ?? ''
        ];
        
        // Không render layout admin
        $view = new \App\Core\View();
        return $view->setLayout(null)->render('pages.maintenance', $viewData);
    }
}
