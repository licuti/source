<?php
/**
 * Stores and sanitizes the administrator-authored class-rules document (ADM-11 / STU-12).
 *
 * PHP 7.3 compatible.
 */
namespace Lms;

class ClassRulesService extends LmsService
{
    /** Allowed rich-text HTML tags for class rules. */
    const ALLOWED_TAGS = '<p><br><div><span><b><strong><i><em><u><s><strike><del><sub><sup><h1><h2><h3><h4><h5><h6><blockquote><pre><code><ul><ol><li><a><img><table><thead><tbody><tr><th><td><font><hr>';

    public function getRules()
    {
        $row = $this->db->safeFetchOne(
            'select content, updated_at, updated_by from db_lms_class_rules where id = 1 limit 1'
        );
        return empty($row) ? array('content' => '', 'updated_at' => null, 'updated_by' => null) : $row;
    }

    public function saveRules($content, $adminId)
    {
        $this->clearErrors();
        $rawContent = trim((string) $content);
        $adminId = (int) $adminId;

        if (mb_strlen($rawContent, 'UTF-8') > 50000) {
            $this->addError('content', 'Nội dung không được vượt quá 50.000 ký tự.');
            return false;
        }

        $sanitized = self::sanitizeHtml($rawContent);
        if (self::isEmptyContent($sanitized)) {
            $this->addError('content', 'Nội dung nội quy không được để trống.');
            return false;
        }

        $before = $this->getRules();
        $ok = $this->db->safeExec(
            'insert into db_lms_class_rules (id, content, updated_by) values (1, ?, ?)'
            . ' on duplicate key update content = values(content), updated_by = values(updated_by)',
            array($sanitized, $adminId > 0 ? $adminId : null)
        );
        if (!$ok) {
            $this->addError('system', 'Không thể lưu nội quy.');
            return false;
        }
        $this->audit('update_class_rules', 'class_rules', 1, $before, $this->getRules(), 'admin');
        return true;
    }

    /**
     * Kiểm tra nội dung rich-text hoặc văn bản thuần có thực sự rỗng hay không
     * (kể cả trường hợp trình soạn thảo chỉ còn thẻ rỗng như <p><br></p>).
     *
     * @param string $content
     * @return bool
     */
    public static function isEmptyContent($content)
    {
        $content = trim((string) $content);
        if ($content === '') {
            return true;
        }
        if (preg_match('/<(img|table|hr)\b/i', $content)) {
            return false;
        }
        $plain = html_entity_decode(strip_tags($content), ENT_QUOTES, 'UTF-8');
        $plain = preg_replace('/[\s\x{00A0}]+/u', '', $plain);
        return $plain === '';
    }

    /**
     * Làm sạch HTML từ trình soạn thảo rich text, loại bỏ script và thuộc tính nguy hiểm (XSS)
     * nhưng vẫn giữ nguyên văn bản thuần nếu không chứa thẻ HTML.
     *
     * @param string $content
     * @return string
     */
    public static function sanitizeHtml($content)
    {
        $content = trim((string) $content);
        if ($content === '') {
            return '';
        }
        // Nếu là văn bản thuần không chứa thẻ HTML thì giữ nguyên tuyệt đối
        if ($content === strip_tags($content)) {
            return $content;
        }

        // Xoá các khối thẻ nguy hiểm kèm nội dung bên trong
        $clean = preg_replace('#<(script|style|iframe|object|embed|applet|meta|link|form)\b[^>]*>.*?</\1>#is', '', $content);
        $clean = preg_replace('#<(script|style|iframe|object|embed|applet|meta|link|form|input|button)\b[^>]*\/?>#is', '', (string) $clean);

        // Chỉ giữ lại các thẻ định dạng rich-text an toàn
        $clean = strip_tags((string) $clean, self::ALLOWED_TAGS);

        // Loại bỏ các thuộc tính sự kiện inline (onclick, onerror, onload...)
        $clean = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', (string) $clean);

        // Loại bỏ giao thức javascript: / vbscript: / data:text/html trong href/src
        $clean = preg_replace('/\b(href|src)\s*=\s*("|\')\s*(javascript|vbscript|data\s*:\s*text\/html)\s*:[^"\']*\2/i', '$1=$2#$2', (string) $clean);

        return trim((string) $clean);
    }

    /**
     * Chuẩn hoá nội dung để hiển thị ra giao diện (Student / Admin preview):
     * - Nếu là rich-text HTML: trả về HTML đã sanitize an toàn.
     * - Nếu là văn bản thuần (legacy/seed): escape + nl2br.
     *
     * @param string $content
     * @return string
     */
    public static function formatForDisplay($content)
    {
        $content = trim((string) $content);
        if (self::isEmptyContent($content)) {
            return '';
        }
        if ($content === strip_tags($content)) {
            return nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'));
        }
        return self::sanitizeHtml($content);
    }
}
