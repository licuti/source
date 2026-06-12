<?php
/**
 * Component Cấu Hình SEO Đa Ngôn Ngữ
 * Các tham số truyền vào:
 * @param string $c Mã ngôn ngữ (vd: 'vi', 'en')
 * @param array $item Mảng chứa dữ liệu của bản ghi hiện tại
 */
$seoTitle = $item['seo_title'][$c] ?? '';
$seoDescription = $item['seo_description'][$c] ?? '';
$keyword = $item['keyword'][$c] ?? '';
$tags = $item['tags'][$c] ?? '';
$noindex = isset($item['noindex'][$c]) ? $item['noindex'][$c] : 0;
$nofollow = isset($item['nofollow'][$c]) ? $item['nofollow'][$c] : 0;
$seoHead = $item['seo_head'][$c] ?? '';
$seoBody = $item['seo_body'][$c] ?? '';
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
                <div class="text-muted" style="font-size: 12px; line-height: 1.2;"><?= url('/') ?>/<span class="preview-slug" data-lang="<?= $c ?>"><?= htmlspecialchars($item['alias'][$c] ?? $item['slug'][$c] ?? 'bai-viet') ?></span></div>
            </div>
        </div>
        <a href="#" class="preview-title text-decoration-none" data-lang="<?= $c ?>" style="color: #1a0dab; font-size: 20px; font-weight: 400; line-height: 1.3;">
            <?= htmlspecialchars($seoTitle ?: ($item['ten'][$c] ?? $item['title'][$c] ?? 'Tiêu đề bài viết sẽ hiển thị ở đây')) ?>
        </a>
        <div class="preview-desc mt-1" data-lang="<?= $c ?>" style="color: #4d5156; font-size: 14px; line-height: 1.58;">
            <?= htmlspecialchars($seoDescription ?: 'Mô tả bài viết sẽ hiển thị ở đây. Độ dài khuyên dùng khoảng 150-160 kí tự để không bị cắt bớt bởi Google...') ?>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Thẻ Tiêu đề (SEO Title)</label>
        <input type="text" name="seo_title[<?= $c ?>]" id="seo_title_<?= $c ?>" class="form-control form-control-sm seo-input-title" data-lang="<?= $c ?>" placeholder="Nhập tiêu đề SEO..." value="<?= htmlspecialchars($seoTitle) ?>">
        <small class="text-muted">Độ dài lý tưởng 50-60 kí tự. Nếu để trống sẽ lấy Tiêu đề bài viết.</small>
    </div>

    <div class="mb-3">
        <label class="form-label">Thẻ Mô tả (SEO Description)</label>
        <textarea name="seo_description[<?= $c ?>]" id="seo_desc_<?= $c ?>" class="form-control form-control-sm seo-input-desc" data-lang="<?= $c ?>" rows="3" placeholder="Nhập mô tả SEO..."><?= htmlspecialchars($seoDescription) ?></textarea>
        <small class="text-muted">Độ dài lý tưởng 150-160 kí tự.</small>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Từ khóa (Keywords)</label>
                <input type="text" name="keyword[<?= $c ?>]" class="form-control form-control-sm" placeholder="Từ khóa 1, Từ khóa 2..." value="<?= htmlspecialchars($keyword) ?>">
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Tags bài viết</label>
                <input type="text" name="tags[<?= $c ?>]" class="form-control form-control-sm" placeholder="tag1, tag2..." value="<?= htmlspecialchars($tags) ?>">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="noindex[<?= $c ?>]" id="noindex_<?= $c ?>" <?= $noindex ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="noindex_<?= $c ?>">Noindex (Chặn bot lập chỉ mục)</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="nofollow[<?= $c ?>]" id="nofollow_<?= $c ?>" <?= $nofollow ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="nofollow_<?= $c ?>">Nofollow (Không cho bot theo liên kết)</label>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Mã nhúng (Bên trong thẻ &lt;head&gt;)</label>
                <textarea name="seo_head[<?= $c ?>]" class="form-control form-control-sm text-monospace" rows="3" placeholder="<script>...</script>"><?= htmlspecialchars($seoHead) ?></textarea>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Mã nhúng (Ngay sau mở thẻ &lt;body&gt;)</label>
                <textarea name="seo_body[<?= $c ?>]" class="form-control form-control-sm text-monospace" rows="3" placeholder="<noscript>...</noscript>"><?= htmlspecialchars($seoBody) ?></textarea>
            </div>
        </div>
    </div>
</div>

<?php if (!defined('SEO_SCRIPT_INCLUDED')): ?>
<?php define('SEO_SCRIPT_INCLUDED', true); ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    function updateGooglePreview(lang) {
        let titleInput = document.querySelector(`input[name="ten[${lang}]"]`) || document.querySelector(`input[name="title[${lang}]"]`);
        let seoTitleInput = document.getElementById(`seo_title_${lang}`);
        let slugInput = document.querySelector(`input[name="alias[${lang}]"]`) || document.querySelector(`input[name="slug[${lang}]"]`);
        let seoDescInput = document.getElementById(`seo_desc_${lang}`);

        let previewTitle = document.querySelector(`.preview-title[data-lang="${lang}"]`);
        let previewSlug = document.querySelector(`.preview-slug[data-lang="${lang}"]`);
        let previewDesc = document.querySelector(`.preview-desc[data-lang="${lang}"]`);

        let defaultTitle = 'Tiêu đề bài viết sẽ hiển thị ở đây';
        let defaultDesc = 'Mô tả bài viết sẽ hiển thị ở đây. Độ dài khuyên dùng khoảng 150-160 kí tự để không bị cắt bớt bởi Google...';

        // Update Title
        if (seoTitleInput && seoTitleInput.value.trim() !== '') {
            previewTitle.textContent = seoTitleInput.value;
        } else if (titleInput && titleInput.value.trim() !== '') {
            previewTitle.textContent = titleInput.value;
        } else {
            previewTitle.textContent = defaultTitle;
        }

        // Update Slug
        if (slugInput && slugInput.value.trim() !== '') {
            previewSlug.textContent = slugInput.value;
        } else {
            previewSlug.textContent = 'duong-dan-bai-viet';
        }

        // Update Description
        if (seoDescInput && seoDescInput.value.trim() !== '') {
            previewDesc.textContent = seoDescInput.value;
        } else {
            previewDesc.textContent = defaultDesc;
        }
    }

    // Attach event listeners to all relevant inputs
    document.body.addEventListener('input', function(e) {
        let target = e.target;
        let name = target.getAttribute('name');
        
        if (name) {
            let match = name.match(/^(ten|title|alias|slug|seo_title|seo_description)\[(.*?)\]$/);
            if (match) {
                let lang = match[2];
                updateGooglePreview(lang);
            }
        }
    });
});
</script>
<?php endif; ?>
