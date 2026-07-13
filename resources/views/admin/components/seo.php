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
$seoSchema = $isMultiLang ? ($item['seo_schema'][$c] ?? '') : ($item['seo_schema'] ?? '');
$seoCanonical = $isMultiLang ? ($item['seo_canonical'][$c] ?? '') : ($item['seo_canonical'] ?? '');

$fallbackTitle = $isMultiLang ? ($item['ten'][$c] ?? $item['title'][$c] ?? '') : ($item['ten'] ?? $item['title'] ?? '');
$fallbackSlug = $isMultiLang ? ($item['slug'][$c] ?? $item['slug'][$c] ?? 'bai-viet') : ($item['slug'] ?? $item['slug'] ?? 'bai-viet');
?>

<div class="seo-wrapper mt-2">
    <ul class="nav nav-tabs mb-3" id="seoTabs<?= $cStr ?>" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#seo-overview<?= $cStr ?>" type="button" role="tab"><i class="fa-solid fa-magnifying-glass"></i> Tổng quan</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#seo-advanced<?= $cStr ?>" type="button" role="tab"><i class="fa-solid fa-sliders"></i> Nâng cao</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#seo-schema<?= $cStr ?>" type="button" role="tab"><i class="fa-solid fa-code"></i> Schema</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#seo-social<?= $cStr ?>" type="button" role="tab"><i class="fa-solid fa-share-nodes"></i> Mạng xã hội</button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Tab Tổng Quan -->
        <div class="tab-pane fade show active" id="seo-overview<?= $cStr ?>" role="tabpanel">
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

            <?= view('admin.components.input', [
                'name' => 'seo_title' . $suffix,
                'value' => $seoTitle,
                'label' => 'Thẻ Tiêu đề (SEO Title)',
                'help_text' => 'Độ dài lý tưởng 50-60 kí tự. Nếu để trống sẽ lấy Tiêu đề bài viết.',
                'attrs' => [
                    'class' => 'seo-input-title',
                    'data-lang' => $cStr,
                    'placeholder' => 'Nhập tiêu đề SEO...'
                ]
            ]) ?>

            <?= view('admin.components.textarea', [
                'name' => 'seo_description' . $suffix,
                'value' => $seoDescription,
                'label' => 'Thẻ Mô tả (SEO Description)',
                'help_text' => 'Độ dài lý tưởng 150-160 kí tự.',
                'attrs' => [
                    'class' => 'seo-input-desc',
                    'data-lang' => $cStr,
                    'placeholder' => 'Nhập mô tả SEO...',
                    'rows' => 3
                ]
            ]) ?>

            <div class="row">
                <div class="col-md-6">
                    <?= view('admin.components.input', [
                        'name' => 'keyword' . $suffix,
                        'value' => $keyword,
                        'label' => 'Từ khóa (Keywords)',
                        'attrs' => ['placeholder' => 'Từ khóa 1, Từ khóa 2...']
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= view('admin.components.input', [
                        'name' => 'tags' . $suffix,
                        'value' => $tags,
                        'label' => 'Tags bài viết',
                        'attrs' => ['placeholder' => 'tag1, tag2...']
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- Tab Nâng Cao -->
        <div class="tab-pane fade" id="seo-advanced<?= $cStr ?>" role="tabpanel">
            <div class="mb-3">
                <?= view('admin.components.input', [
                    'name' => 'seo_canonical' . $suffix,
                    'value' => $seoCanonical,
                    'label' => 'Thẻ Canonical (Tùy chỉnh)',
                    'help_text' => 'Nhập nếu bạn muốn trỏ nội dung này về 1 bài gốc khác để tránh trùng lặp nội dung.',
                    'attrs' => ['placeholder' => 'https://...']
                ]) ?>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <?= view('admin.components.switch', [
                        'name' => 'noindex' . $suffix,
                        'checked' => $noindex == 1,
                        'label' => 'Ngăn bot lập chỉ mục (Noindex)',
                        'attrs' => ['value' => '1']
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= view('admin.components.switch', [
                        'name' => 'nofollow' . $suffix,
                        'checked' => $nofollow == 1,
                        'label' => 'Không theo dõi liên kết (Nofollow)',
                        'attrs' => ['value' => '1']
                    ]) ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Mã nhúng Header (Tùy chọn)</label>
                <?= view('admin.components.code_editor', [
                    'name' => 'seo_head' . $suffix,
                    'value' => $seoHead,
                    'mode' => 'htmlmixed',
                    'rows' => 6
                ]) ?>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Mã nhúng Body (Tùy chọn)</label>
                <?= view('admin.components.code_editor', [
                    'name' => 'seo_body' . $suffix,
                    'value' => $seoBody,
                    'mode' => 'htmlmixed',
                    'rows' => 6
                ]) ?>
            </div>
        </div>

        <!-- Tab Schema -->
        <div class="tab-pane fade" id="seo-schema<?= $cStr ?>" role="tabpanel">
            <div class="mb-3">
                <label class="form-label">Loại Schema Markup</label>
                <select name="seo_schema<?= $suffix ?>" class="form-select">
                    <option value="" <?= empty($seoSchema) ? 'selected' : '' ?>>Mặc định (Tự động nhận diện theo Module)</option>
                    <option value="Article" <?= $seoSchema === 'Article' ? 'selected' : '' ?>>Bài viết (Article)</option>
                    <option value="NewsArticle" <?= $seoSchema === 'NewsArticle' ? 'selected' : '' ?>>Tin tức (NewsArticle)</option>
                    <option value="Product" <?= $seoSchema === 'Product' ? 'selected' : '' ?>>Sản phẩm (Product)</option>
                    <option value="ImageGallery" <?= $seoSchema === 'ImageGallery' ? 'selected' : '' ?>>Album ảnh (ImageGallery)</option>
                    <option value="Recipe" <?= $seoSchema === 'Recipe' ? 'selected' : '' ?>>Công thức (Recipe)</option>
                    <option value="Review" <?= $seoSchema === 'Review' ? 'selected' : '' ?>>Đánh giá (Review)</option>
                    <option value="FAQPage" <?= $seoSchema === 'FAQPage' ? 'selected' : '' ?>>Câu hỏi thường gặp (FAQ)</option>
                    <option value="SoftwareApplication" <?= $seoSchema === 'SoftwareApplication' ? 'selected' : '' ?>>Phần mềm (SoftwareApplication)</option>
                </select>
                <div class="form-text text-muted">Hệ thống sẽ tự động sinh Schema chuẩn của Google nếu bạn để Mặc định. Chỉ sử dụng mục này khi bài viết của bạn có tính chất đặc thù.</div>
            </div>
        </div>

        <!-- Tab Mạng xã hội -->
        <div class="tab-pane fade" id="seo-social<?= $cStr ?>" role="tabpanel">
            <div class="alert alert-info">
                <i class="fa-solid fa-circle-info me-2"></i> <strong>Tự động hóa 100%</strong><br>
                Hệ thống đã được thiết lập để tự động trích xuất <b>Tiêu đề</b>, <b>Mô tả</b> và <b>Hình ảnh đại diện</b> của bài viết để tạo thẻ Open Graph (Facebook, Zalo) và Twitter Cards. Khuyên dùng: Bạn không cần phải cấu hình gì thêm ở đây.
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const lang = '<?= $cStr ?>';
    
    const titleInput = document.querySelector(`input.seo-input-title[data-lang="${lang}"]`);
    const descInput = document.querySelector(`textarea.seo-input-desc[data-lang="${lang}"]`);
    const previewTitle = document.querySelector(`.preview-title[data-lang="${lang}"]`);
    const previewDesc = document.querySelector(`.preview-desc[data-lang="${lang}"]`);
    const previewSlug = document.querySelector(`.preview-slug[data-lang="${lang}"]`);
    
    const mainTitleInput = document.querySelector(`[data-slug-source="${lang}"]`) || document.querySelector(`input[name="title"]`) || document.querySelector(`input[name="ten"]`);
    const mainSlugInput = document.querySelector(`[data-slug-target="${lang}"]`) || document.querySelector(`input[name="slug"]`) || document.querySelector(`input[name="alias"]`);

    function updateSeoPreview() {
        if (!titleInput || !previewTitle) return;
        
        let t = titleInput.value.trim();
        if (!t && mainTitleInput) t = mainTitleInput.value.trim();
        previewTitle.textContent = t ? t : 'Tiêu đề bài viết sẽ hiển thị ở đây';
        
        let d = descInput.value.trim();
        previewDesc.textContent = d ? d : 'Mô tả bài viết sẽ hiển thị ở đây. Độ dài khuyên dùng khoảng 150-160 kí tự để không bị cắt bớt bởi Google...';
        
        if (mainSlugInput && previewSlug) {
            let s = mainSlugInput.value.trim();
            previewSlug.textContent = s ? s : 'bai-viet';
        }
    }

    if (titleInput) titleInput.addEventListener('input', updateSeoPreview);
    if (descInput) descInput.addEventListener('input', updateSeoPreview);
    if (mainTitleInput) mainTitleInput.addEventListener('input', updateSeoPreview);
    if (mainSlugInput) {
        mainSlugInput.addEventListener('input', updateSeoPreview);
        mainSlugInput.addEventListener('change', updateSeoPreview);
    }
});
</script>
