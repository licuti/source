<?php
/**
 * Component Cấu Hình SEO
 * Các tham số truyền vào:
 * @param string|false $c Mã ngôn ngữ (vd: 'vi', 'en'). Nếu false hoặc rỗng, dùng chế độ đơn ngữ (Polylang).
 * @param array $item Mảng chứa dữ liệu của bản ghi hiện tại
 */
$isMultiLang = !empty($c);
$suffix = $isMultiLang ? "[$c]" : "";
$cStr = $isMultiLang ? $c : 'vi'; // For data-lang attributes

$seoTitle = $isMultiLang ? ($item['seo_title'][$c] ?? '') : ($item['seo_title'] ?? '');
$seoDescription = $isMultiLang ? ($item['seo_description'][$c] ?? '') : ($item['seo_description'] ?? '');
$keyword = $isMultiLang ? ($item['keyword'][$c] ?? '') : ($item['keyword'] ?? '');
$tags = $isMultiLang ? ($item['tags'][$c] ?? '') : ($item['tags'] ?? '');
$noindex = $isMultiLang ? (isset($item['noindex'][$c]) ? $item['noindex'][$c] : 0) : ($item['noindex'] ?? 0);
$nofollow = $isMultiLang ? (isset($item['nofollow'][$c]) ? $item['nofollow'][$c] : 0) : ($item['nofollow'] ?? 0);
$seoHead = $isMultiLang ? ($item['seo_head'][$c] ?? '') : ($item['seo_head'] ?? '');
$seoBody = $isMultiLang ? ($item['seo_body'][$c] ?? '') : ($item['seo_body'] ?? '');

$fallbackTitle = $isMultiLang ? ($item['ten'][$c] ?? $item['title'][$c] ?? '') : ($item['ten'] ?? $item['title'] ?? '');
$fallbackSlug = $isMultiLang ? ($item['alias'][$c] ?? $item['slug'][$c] ?? 'bai-viet') : ($item['alias'] ?? $item['slug'] ?? 'bai-viet');
?>

<div class="seo-wrapper mt-2">
    <!-- Google Search Snippet Preview -->
    <div class="google-preview mb-4 p-3 border rounded" style="background-color: #f8f9fa;">
        <div class="d-flex align-items-center mb-1">
            <div class="bg-light rounded-circle border d-flex justify-content-center align-items-center me-2" style="width: 28px; height: 28px;">
                <i class="fa-solid fa-globe text-muted" style="font-size: 12px;"></i>
            </div>
            <div>
                <div class="text-dark" style="font-size: 14px; line-height: 1.2;">Your Website Name</div>
                <div class="text-muted" style="font-size: 12px; line-height: 1.2;"><span class="preview-slug" data-lang="<?= $cStr ?>"><?= htmlspecialchars($fallbackSlug) ?></span></div>
            </div>
        </div>
        <a href="#" class="preview-title text-decoration-none" data-lang="<?= $cStr ?>" style="color: #1a0dab; font-size: 20px; font-weight: 400; line-height: 1.3;">
            <?= htmlspecialchars($seoTitle ?: ($fallbackTitle ?: 'Tiêu đề bài viết sẽ hiển thị ở đây')) ?>
        </a>
        <div class="preview-desc mt-1" data-lang="<?= $cStr ?>" style="color: #4d5156; font-size: 14px; line-height: 1.58;">
            <?= htmlspecialchars($seoDescription ?: 'Mô tả bài viết sẽ hiển thị ở đây. Độ dài khuyên dùng khoảng 150-160 kí tự để không bị cắt bớt bởi Google...') ?>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Thẻ Tiêu đề (SEO Title)</label>
        <input type="text" name="seo_title<?= $suffix ?>" class="form-control form-control-sm seo-input-title" data-lang="<?= $cStr ?>" placeholder="Nhập tiêu đề SEO..." value="<?= htmlspecialchars($seoTitle) ?>">
        <small class="text-muted">Độ dài lý tưởng 50-60 kí tự. Nếu để trống sẽ lấy Tiêu đề bài viết.</small>
    </div>

    <div class="mb-3">
        <label class="form-label">Thẻ Mô tả (SEO Description)</label>
        <textarea name="seo_description<?= $suffix ?>" class="form-control form-control-sm seo-input-desc" data-lang="<?= $cStr ?>" rows="3" placeholder="Nhập mô tả SEO..."><?= htmlspecialchars($seoDescription) ?></textarea>
        <small class="text-muted">Độ dài lý tưởng 150-160 kí tự.</small>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Từ khóa (Keywords)</label>
                <input type="text" name="keyword<?= $suffix ?>" class="form-control form-control-sm" placeholder="Từ khóa 1, Từ khóa 2..." value="<?= htmlspecialchars($keyword) ?>">
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Tags bài viết</label>
                <input type="text" name="tags<?= $suffix ?>" class="form-control form-control-sm" placeholder="tag1, tag2..." value="<?= htmlspecialchars($tags) ?>">
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="noindex<?= $suffix ?>" value="1" <?= $noindex ? 'checked' : '' ?>>
                <label class="form-check-label">Ngăn bot lập chỉ mục (Noindex)</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="nofollow<?= $suffix ?>" value="1" <?= $nofollow ? 'checked' : '' ?>>
                <label class="form-check-label">Không theo dõi liên kết (Nofollow)</label>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Mã nhúng Header (Tùy chọn)</label>
        <textarea name="seo_head<?= $suffix ?>" class="form-control text-monospace" rows="2" placeholder="<style>...</style>"><?= htmlspecialchars($seoHead) ?></textarea>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Mã nhúng Body (Tùy chọn)</label>
        <textarea name="seo_body<?= $suffix ?>" class="form-control text-monospace" rows="2" placeholder="<script>...</script>"><?= htmlspecialchars($seoBody) ?></textarea>
    </div>
</div>
