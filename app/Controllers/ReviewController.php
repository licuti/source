<?php

namespace App\Controllers;

use App\Models\BinhLuanModel;

use App\Core\Request;
use App\Core\Response;

/**
 * ReviewController
 * Xử lý các chức năng bình luận / đánh giá sản phẩm.
 * Đã migrate từ: sources/ajax/ajax_reviews.php, sources/ajax/ajax_review_media.php
 */
class ReviewController extends Controller {

    // ── Cấu hình Upload ─────────────────────────────────────────
    private const UPLOAD_CONFIG = [
        'max_files'     => 10,
        'max_image_mb'  => 5,
        'max_video_mb'  => 50,
        'allowed_image' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'allowed_video' => ['mp4', 'mov', 'webm', 'avi'],
        'url_prefix'    => 'img_data/review/',
    ];

    /**
     * Load thêm đánh giá (phân trang AJAX)
     * POST /ajax/reviews/load
     */
    public function load(Request $request) {
        $id_sp        = (int) $request->input('id_sanpham', 0);
        $offset       = (int) $request->input('offset', 0);
        $limit        = (int) $request->input('limit', 5);
        $filter_star  = (int) $request->input('filter_star', 0);
        $filter_media = (int) $request->input('filter_media', 0);

        if (!$id_sp) {
            return response()->json(['success' => false, 'message' => 'Invalid product']);
        }

        $limit = max(1, min($limit, 50));

        $filters = [
            'bl_star'  => $filter_star,
            'bl_media' => $filter_media,
        ];

        $BinhLuan = new BinhLuanModel();
        $total    = $BinhLuan->countForProduct($id_sp, $filters);
        $items    = $BinhLuan->getForProduct($id_sp, $filters, $limit, $offset);

        // Render HTML từng review item dùng View Engine
        ob_start();
        foreach ($items as $review) {
            echo view('partials/components/review-item', compact('review'));
        }
        $html = ob_get_clean();

        return response()->json([
            'success'  => true,
            'html'     => $html,
            'loaded'   => count($items),
            'has_more' => ($offset + count($items)) < $total,
            'total'    => $total,
        ]);
    }

    /**
     * Upload ảnh / video cho đánh giá
     * POST /ajax/reviews/media
     */
    public function uploadMedia(Request $request) {
        $saveDir = realpath(__DIR__ . '/../../img_data') . '/review/';

        if (!is_dir($saveDir)) {
            mkdir($saveDir, 0755, true);
        }

        if (empty($_FILES['media'])) {
            return response()->json(['success' => false, 'message' => 'Không có file nào được gửi lên']);
        }

        $files_raw = $_FILES['media'];

        // Normalize sang mảng nếu chỉ upload 1 file
        if (!is_array($files_raw['name'])) {
            $files_raw = array_map(fn($v) => [$v], $files_raw);
        }

        $count = count($files_raw['name']);
        if ($count > self::UPLOAD_CONFIG['max_files']) {
            return response()->json([
                'success' => false,
                'message' => 'Tối đa ' . self::UPLOAD_CONFIG['max_files'] . ' file mỗi lần',
            ]);
        }

        $saved  = [];
        $errors = [];

        for ($i = 0; $i < $count; $i++) {
            if ($files_raw['error'][$i] !== UPLOAD_ERR_OK) {
                $errors[] = 'File ' . ($i + 1) . ': lỗi upload';
                continue;
            }

            $orig_name = $files_raw['name'][$i];
            $tmp       = $files_raw['tmp_name'][$i];
            $size      = $files_raw['size'][$i];
            $ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

            // Xác định loại file và giới hạn kích thước
            if (in_array($ext, self::UPLOAD_CONFIG['allowed_image'])) {
                $type      = 'image';
                $max_bytes = self::UPLOAD_CONFIG['max_image_mb'] * 1024 * 1024;
            } elseif (in_array($ext, self::UPLOAD_CONFIG['allowed_video'])) {
                $type      = 'video';
                $max_bytes = self::UPLOAD_CONFIG['max_video_mb'] * 1024 * 1024;
            } else {
                $errors[] = '"' . htmlspecialchars($orig_name) . '": định dạng không hỗ trợ';
                continue;
            }

            if ($size > $max_bytes) {
                $limit    = $type === 'image' ? self::UPLOAD_CONFIG['max_image_mb'] : self::UPLOAD_CONFIG['max_video_mb'];
                $errors[] = '"' . htmlspecialchars($orig_name) . '": vượt quá ' . $limit . 'MB';
                continue;
            }

            $new_name = date('YmdHis') . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
            $dest     = $saveDir . $new_name;

            if (!move_uploaded_file($tmp, $dest)) {
                $errors[] = '"' . htmlspecialchars($orig_name) . '": không thể lưu file';
                continue;
            }

            $saved[] = [
                'name' => $new_name,
                'url'  => url(self::UPLOAD_CONFIG['url_prefix'] . $new_name),
                'type' => $type,
            ];
        }

        return response()->json([
            'success' => count($saved) > 0,
            'files'   => $saved,
            'errors'  => $errors,
            'message' => count($errors) > 0 ? implode('; ', $errors) : 'Upload thành công',
        ]);
    }
}
