<?php
/**
 * Sitemap — xem preview, tạo từ DB, chỉnh sửa XML bằng Ace.
 */

$canEdit = $d->checkPermission_edit($id_module) == 1;
$MODULE  = 'sitemap';

$sitemap_content = $sitemap_content ?? '';
$sitemap_meta    = $sitemap_meta ?? array(
    'exists'     => false,
    'writable'   => false,
    'mtime'      => 0,
    'size'       => 0,
    'url_count'  => 0,
    'public_url' => (defined('URLPATH') ? rtrim(URLPATH, '/') : '') . '/sitemap.xml',
    'has_xsl'    => false,
    'has_bak'    => false,
    'too_many'   => false,
    'last_stats' => null,
);
$sitemap_robots = $sitemap_robots ?? array(
    'status'   => 'ok',
    'message'  => '',
    'has_line' => true,
);

if ($sitemap_content === '' && is_file(dirname(__DIR__, 3) . '/sitemap.xml')) {
    $sitemap_content = (string) @file_get_contents(dirname(__DIR__, 3) . '/sitemap.xml');
}

$publicUrl = $sitemap_meta['public_url'] ?: ((defined('URLPATH') ? rtrim(URLPATH, '/') : '') . '/sitemap.xml');
$cacheBust = !empty($sitemap_meta['mtime']) ? (int) $sitemap_meta['mtime'] : time();
$previewUrl = $publicUrl . (strpos($publicUrl, '?') === false ? '?' : '&') . 'v=' . $cacheBust;
$generateUrl = admin_url(['p' => $MODULE, 'a' => 'generate']);
$saveUrl     = admin_url(['p' => $MODULE, 'a' => 'save']);


$sizeLabel = '';
if (!empty($sitemap_meta['size'])) {
    $b = (int) $sitemap_meta['size'];
    if ($b < 1024) { $sizeLabel = $b . ' B'; }
    elseif ($b < 1048576) { $sizeLabel = round($b / 1024, 1) . ' KB'; }
    else { $sizeLabel = round($b / 1048576, 2) . ' MB'; }
}
$mtimeLabel = !empty($sitemap_meta['mtime']) ? date('d/m/Y H:i', (int) $sitemap_meta['mtime']) : '—';
$lastStats = $sitemap_meta['last_stats'] ?? null;
$robotsOk  = !empty($sitemap_robots['has_line']);
?>

<?php component('page_header', [
    'title'      => 'Sitemap',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Cấu hình website'],
        ['label' => 'Sitemap'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <div class="row g-2 mb-3 align-items-center">
            <div class="col-auto">
                <span class="badge text-bg-<?= !empty($sitemap_meta['exists']) ? 'primary' : 'secondary' ?>">
                    sitemap.xml: <?= !empty($sitemap_meta['exists']) ? 'có' : 'chưa có' ?>
                </span>
            </div>
            <div class="col-auto">
                <span class="badge text-bg-<?= !empty($sitemap_meta['writable']) ? 'success' : 'danger' ?>">
                    <?= !empty($sitemap_meta['writable']) ? 'ghi được' : 'chỉ đọc' ?>
                </span>
            </div>
            <div class="col-auto">
                <span class="badge text-bg-info"><?= (int) ($sitemap_meta['url_count'] ?? 0) ?> URL</span>
            </div>
            <div class="col-auto">
                <span class="badge text-bg-<?= $robotsOk ? 'success' : 'warning' ?>">
                    robots Sitemap: <?= $robotsOk ? 'có' : 'thiếu' ?>
                </span>
            </div>
            <?php if (!empty($sitemap_meta['has_bak'])): ?>
            <div class="col-auto"><span class="badge text-bg-light border">có .bak</span></div>
            <?php endif; ?>
            <?php if ($sizeLabel !== ''): ?>
            <div class="col-auto"><span class="badge text-bg-light border"><?= e($sizeLabel) ?></span></div>
            <?php endif; ?>
            <div class="col-auto"><span class="badge text-bg-light border">Cập nhật: <?= e($mtimeLabel) ?></span></div>
            <div class="col-auto ms-sm-auto d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-copy-sitemap-url" data-url="<?= e($publicUrl) ?>">
                    <i class="fa-regular fa-copy me-1"></i>Copy URL
                </button>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e($publicUrl) ?>" target="_blank" rel="noopener">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Xem sitemap.xml
                </a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url(['p' => 'seo-co-ban', 'a' => 'man'])) ?>">
                    <i class="fa-solid fa-magnifying-glass-chart me-1"></i>SEO cơ bản
                </a>
            </div>
        </div>

        <?php if (!$robotsOk): ?>
            <div class="alert alert-warning py-2 small">
                <strong>robots.txt</strong> chưa có dòng <code>Sitemap:</code>.
                Khi bấm <em>Tạo sitemap từ dữ liệu</em>, hệ thống sẽ tự thêm
                <code>Sitemap: <?= e($publicUrl) ?></code> (nếu ghi được file).
            </div>
        <?php endif; ?>

        <?php if (!empty($sitemap_meta['too_many'])): ?>
            <div class="alert alert-danger py-2 small">
                Sitemap đang có hơn <strong>50.000 URL</strong> — vượt khuyến nghị Google cho 1 file.
            </div>
        <?php endif; ?>

        <?php if (is_array($lastStats) && !empty($lastStats['stats'])): ?>
            <div class="card mb-3 border-0 bg-body-secondary">
                <div class="card-body py-2">
                    <div class="small text-secondary mb-1">
                        Thống kê lần tạo gần nhất
                        <?php if (!empty($lastStats['at'])): ?>
                            (<?= e(date('d/m/Y H:i', (int) $lastStats['at'])) ?>)
                        <?php endif; ?>
                        — tổng <strong><?= (int) ($lastStats['count'] ?? 0) ?></strong> URL
                        <?php if (!empty($lastStats['skipped_noindex'])): ?>
                            · bỏ qua <?= (int) $lastStats['skipped_noindex'] ?> noindex
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($lastStats['stats'] as $st): ?>
                            <?php if ((int) ($st['count'] ?? 0) <= 0) { continue; } ?>
                            <span class="badge text-bg-light border">
                                <?= e($st['label'] ?? '') ?>: <?= (int) $st['count'] ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-lg-6">
                <?php component('card_open', [
                    'title' => 'Xem trước',
                    'icon'  => 'fa-solid fa-eye',
                ]); ?>

                    <div class="mb-3">
                        <iframe
                            class="border rounded w-100"
                            src="<?= e($previewUrl) ?>"
                            title="Sitemap preview"
                            style="min-height: 480px; height: 480px;"
                        ></iframe>
                    </div>

                    <p class="small text-secondary mb-3">
                        File lưu tại <code>/sitemap.xml</code> (root website).
                        Generate quét category, tin tức, album, video, sản phẩm đang hiển thị,
                        bỏ qua <code>noindex</code>, ghi đè file (backup <code>sitemap.xml.bak</code>).
                    </p>

                    <?php if ($canEdit && !empty($sitemap_meta['writable'])): ?>
                    <form method="post" action="<?= e($generateUrl) ?>" class="d-flex flex-wrap gap-2"
                          onsubmit="return confirm('Tạo lại sitemap.xml từ dữ liệu hiện tại? Nội dung chỉnh tay sẽ bị ghi đè (có backup .bak).');">
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Tạo sitemap từ dữ liệu
                        </button>
                    </form>
                    <?php elseif (!$canEdit): ?>
                    <div class="alert alert-secondary py-2 mb-0 small">Bạn không có quyền tạo / ghi sitemap.</div>
                    <?php else: ?>
                    <div class="alert alert-warning py-2 mb-0 small">Thư mục gốc website chỉ đọc — không ghi được sitemap.xml.</div>
                    <?php endif; ?>

                <?php component('card_close'); ?>
            </div>

            <div class="col-lg-6">
                <?php component('card_open', [
                    'title' => 'Chỉnh sửa XML',
                    'icon'  => 'fa-solid fa-code',
                ]); ?>

                    <form method="post" action="<?= e($saveUrl) ?>" id="form-sitemap">
                        <?php component('form/textarea', [
                            'label' => 'Nội dung sitemap.xml',
                            'name'  => 'content_sitemap',
                            'id'    => 'content_sitemap',
                            'value' => $sitemap_content,
                            'rows'  => 22,
                            'attr'  => [
                                'data-ace-editor' => '1',
                                'data-ace-mode'   => 'xml',
                                'data-ace-height' => '480',
                            ],
                            'hint'  => 'Sửa tay khi cần. Khi lưu sẽ kiểm tra XML hợp lệ (urlset + loc).',
                        ]); ?>

                        <?php component('form/actions_bar', [
                            'back_url'   => admin_url(['p' => 'seo-co-ban', 'a' => 'man']),
                            'can_edit'   => $canEdit && !empty($sitemap_meta['writable']),
                            'save_label' => 'Lưu sitemap',
                            'save_edit'  => false,
                        ]); ?>
                    </form>

                <?php component('card_close'); ?>
            </div>
        </div>

    </div>
</div>

<script src="js/ace-editor.js"></script>
<style>
.ace-editor-host { min-height: 200px; background: var(--bs-body-bg, #fff); }
.ace-editor-host .ace_editor {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}
</style>
<script>
(function () {

  var btn = document.getElementById('btn-copy-sitemap-url');
  if (btn) {
    btn.addEventListener('click', function () {
      var url = btn.getAttribute('data-url') || '';
      function ok() {
        if (window.AdminUI && AdminUI.notify) {
          AdminUI.notify.show('Đã copy URL sitemap.', 'success');
        } else if (window.jQuery && jQuery.notify) {
          jQuery.notify('Đã copy URL sitemap.', 'success');
        }
      }
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(ok).catch(function () {
          window.prompt('Copy URL:', url);
        });
      } else {
        window.prompt('Copy URL:', url);
      }
    });
  }
})();
</script>
