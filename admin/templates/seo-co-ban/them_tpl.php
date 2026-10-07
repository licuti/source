<?php
/**
 * SEO cơ bản — meta, OG, robots, script, verify, SERP preview.
 */

$canEdit   = $d->checkPermission_edit($id_module) == 1;
$MODULE    = 'seo-co-ban';
$langs     = get_json('lang');
$multiLang = count($langs) > 1;

$rowByLang = $rowByLang ?? [];
$robots    = $robots ?? '';
$seo_head  = $seo_head ?? '';
$seo_body  = $seo_body ?? '';
$seo_writable = $seo_writable ?? ['robots' => true, 'head' => true, 'body' => true];
$seo_verify_files = $seo_verify_files ?? [];
$seo_has_sitemap = !empty($seo_has_sitemap);
$seo_robots_blocks_all = !empty($seo_robots_blocks_all);

$rootPath = dirname(__DIR__, 3);
if ($robots === '' && is_file($rootPath . '/robots.txt')) {
    $robots = (string) @file_get_contents($rootPath . '/robots.txt');
}
if ($seo_head === '' && is_file($rootPath . '/sitemap/seo_head.inc')) {
    $seo_head = (string) @file_get_contents($rootPath . '/sitemap/seo_head.inc');
}
if ($seo_body === '' && is_file($rootPath . '/sitemap/seo_footer.inc')) {
    $seo_body = (string) @file_get_contents($rootPath . '/sitemap/seo_footer.inc');
}
if (empty($rowByLang)) {
    foreach ($langs as $lang) {
        $code = $lang['code'];
        $row  = $d->simple_fetch("select * from #_seo where lang = '" . $code . "'");
        $rowByLang[$code] = is_array($row) ? $row : [];
    }
}

$actionUrl = admin_url(['p' => $MODULE, 'a' => 'save']);
$firstCode = $langs[0]['code'] ?? 'vi';
$siteHost  = defined('URLPATH') ? preg_replace('#^https?://#', '', rtrim(URLPATH, '/')) : 'example.com';

?>

<?php component('page_header', [
    'title'      => 'Cấu hình SEO',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Cấu hình website'],
        ['label' => 'Cấu hình SEO'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <div class="row g-2 mb-3">
            <div class="col-auto">
                <span class="badge text-bg-<?= !empty($seo_writable['robots']) ? 'success' : 'đanger' ?>">
                    robots.txt: <?= !empty($seo_writable['robots']) ? 'ghi được' : 'chỉ đọc' ?>
                </span>
            </div>
            <div class="col-auto">
                <span class="badge text-bg-<?= !empty($seo_writable['head']) ? 'success' : 'đanger' ?>">
                    seo_head: <?= !empty($seo_writable['head']) ? 'ghi được' : 'chỉ đọc' ?>
                </span>
            </div>
            <div class="col-auto">
                <span class="badge text-bg-<?= $seo_has_sitemap ? 'primary' : 'secondary' ?>">
                    sitemap.xml: <?= $seo_has_sitemap ? 'có' : 'chưa có' ?>
                </span>
            </div>
            <div class="col-auto">
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url(['p' => 'sitemap', 'a' => 'man'])) ?>">
                    <i class="fa-solid fa-sitemap me-1"></i>Quản lý Sitemap
                </a>
            </div>
            <div class="col-auto">
                <a class="btn btn-sm btn-outline-secondary" href="../robots.txt" target="_blank" rel="noopener">
                    <i class="fa-solid fa-robot me-1"></i>Xem robots.txt
                </a>
            </div>
        </div>

        <?php if ($seo_robots_blocks_all): ?>
            <div class="alert alert-warning d-flex align-items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                <div>
                    <strong>Cảnh báo:</strong> <code>robots.txt</code> đang
                    <code>Disallow: /</code> — có thể <strong>chặn index toàn site</strong>.
                    Hãy dùng nút "Khôi phục mẫu khuyến nghị" nếu không phải ý đồ.
                </div>
            </div>
        <?php endif; ?>

        <form name="frm" method="post" action="<?= e($actionUrl) ?>"
              enctype="multipart/form-data" data-warn-unsaved id="form-seo-coban">

            <div class="row g-3">
                <div class="col-lg-7">

                    <?php component('card_open', ['title' => 'Meta SEO trang chủ', 'body' => 'none']); ?>
                        <div class="card-body">
                            <?php component('form/lang_tabs', ['langs' => $langs, 'prefix' => 'seo']); ?>
                            <div class="tab-content">
                                <?php foreach ($langs as $i => $lang):
                                    $code = $lang['code'];
                                    $row  = $rowByLang[$code] ?? [];
                                    $sfx  = $multiLang ? ' (' . $code . ')' : '';
                                    ?>
                                    <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
                                         id="seo-<?= e($code) ?>" role="tabpanel" data-seo-lang="<?= e($code) ?>">

                                        <?php component('form/textarea', [
                                            'label' => 'Title' . $sfx, 'name' => 'title[]', 'id' => 'title_' . $code,
                                            'value' => $row['title'] ?? '', 'rows' => 2, 'counter' => true, 'max_hint' => 60,
                                            'placeholder' => 'Tiêu đề SEO', 'hint' => 'Khuyến nghị 50–60 ký tự.',
                                            'class' => 'js-seo-title', 'attr' => ['data-seo-lang' => $code],
                                        ]); ?>

                                        <?php component('form/textarea', [
                                            'label' => 'Keyword' . $sfx, 'name' => 'keyword[]', 'id' => 'keyword_' . $code,
                                            'value' => $row['keyword'] ?? '', 'rows' => 2,
                                            'placeholder' => 'từ-khóa-1, từ-khóa-2',
                                        ]); ?>

                                        <?php component('form/textarea', [
                                            'label' => 'Description' . $sfx, 'name' => 'des[]', 'id' => 'des_' . $code,
                                            'value' => $row['des'] ?? '', 'rows' => 3, 'counter' => true, 'max_hint' => 160,
                                            'placeholder' => 'Mô tả ngắn Google', 'hint' => 'Khuyến nghị 120–160 ký tự.',
                                            'class' => 'js-seo-des', 'attr' => ['data-seo-lang' => $code],
                                        ]); ?>

                                        <hr class="my-3">
                                        <div class="small text-secondary fw-medium mb-2">Open Graph / chia sẻ MXH</div>

                                        <?php component('form/input', [
                                            'label' => 'OG Title' . $sfx, 'name' => 'og_title[]', 'id' => 'og_title_' . $code,
                                            'value' => $row['og_title'] ?? '', 'placeholder' => 'Để trống = dùng Title',
                                        ]); ?>
                                        <?php component('form/textarea', [
                                            'label' => 'OG Description' . $sfx, 'name' => 'og_des[]', 'id' => 'og_des_' . $code,
                                            'value' => $row['og_des'] ?? '', 'rows' => 2, 'placeholder' => 'Để trống = dùng Description',
                                        ]); ?>
                                        <?php component('form/image_picker', [
                                            'label' => 'OG Image' . $sfx, 'name' => 'og_image_' . $code,
                                            'value' => $row['og_image'] ?? '', 'table' => '', 'row_id' => '', 'height' => 120,
                                            'hint' => 'Ảnh chia sẻ FB/Zalo (gợi ý ≥ 1200×630).',
                                        ]); ?>
                                        <input type="hidden" name="og_image[]" class="js-og-image-sync"
                                               data-src="og_image_<?= e($code) ?>" value="<?= e($row['og_image'] ?? '') ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="mt-3 p-3 border rounded bg-body-secondary" id="seo-serp-preview"
                                 data-host="<?= e($siteHost) ?>" data-lang="<?= e($firstCode) ?>">
                                <div class="small text-secondary mb-2"><i class="fa-brands fa-google me-1"></i>Xem trước Google (SERP)</div>
                                <div class="text-success small mb-1" id="serp-url"><?= e($siteHost) ?></div>
                                <div class="text-primary fw-medium mb-1" id="serp-title" style="font-size:1.125rem">Tiêu đề trang</div>
                                <div class="text-secondary small" id="serp-des">Mô tả trang sẽ hiển thị tại đây…</div>
                            </div>
                        </div>
                    <?php component('card_close', ['body' => 'none']); ?>

                    <?php component('card_open', ['title' => 'robots.txt']); ?>
                        <?php if ($canEdit): ?>
                            <div class="mb-2 d-flex flex-wrap gap-2">
                                <button type="submit" name="robots_reset" value="1" class="btn btn-sm btn-outline-warning"
                                        onclick="return confirm('Nạp mẫu robots.txt khuyến nghị (ghi đè nội dung hiện tại khi lưu)?');">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Khôi phục mẫu khuyến nghị
                                </button>
                            </div>
                        <?php endif; ?>
                        <?php component('form/textarea', [
                            'label' => 'Nội dung robots.txt', 'name' => 'robots', 'id' => 'robots',
                            'value' => $robots, 'rows' => 10, 'class' => 'font-monospace',
                            'placeholder' => "User-agent: *\nAllow: /",
                            'hint' => 'Lưu tại /robots.txt. Không dùng Disallow: / nếu muốn Google index.',
                            'attr' => ['data-ace-editor' => '1', 'data-ace-mode' => 'text', 'data-ace-height' => '240'],
                        ]); ?>
                    <?php component('card_close'); ?>
                </div>

                <div class="col-lg-5">
                    <?php component('card_open', ['title' => 'Script & xác minh']); ?>

                        <?php component('form/textarea', [
                            'label' => 'Chèn trước </head>', 'name' => 'head', 'id' => 'seo_head_code',
                            'value' => $seo_head, 'rows' => 8, 'class' => 'font-monospace',
                            'placeholder' => '<!-- GA, meta verify... -->',
                            'hint' => 'sitemap/seo_head.inc',
                            'attr' => ['data-ace-editor' => '1', 'data-ace-mode' => 'html', 'data-ace-height' => '200'],
                        ]); ?>

                        <?php component('form/textarea', [
                            'label' => 'Chèn trước </body>', 'name' => 'body', 'id' => 'seo_body_code',
                            'value' => $seo_body, 'rows' => 8, 'class' => 'font-monospace',
                            'placeholder' => '<!-- chat, pixel... -->',
                            'hint' => 'sitemap/seo_footer.inc',
                            'attr' => ['data-ace-editor' => '1', 'data-ace-mode' => 'html', 'data-ace-height' => '200'],
                        ]); ?>

                        <div class="mb-3">
                            <label class="form-label fw-medium" for="seo_verify_file">File xác minh (Google/HTML)</label>
                            <input type="file" name="file" id="seo_verify_file" class="form-control form-control-sm"
                                   accept=".html,.htm,.txt,.json,text/html,application/json,text/plain"
                                   <?= $canEdit ? '' : 'disabled' ?>>
                            <div class="form-text">Chỉ .html/.htm/.txt/.json — đặt ở root website.</div>
                        </div>

                        <?php if (!empty($seo_verify_files)): ?>
                            <div class="mb-2 small fw-medium">File xác minh hiện có</div>
                            <ul class="list-group list-group-flush mb-3 border rounded">
                                <?php foreach ($seo_verify_files as $vf):
                                    $delUrl = admin_url(['p' => $MODULE, 'a' => 'delete_verify', 'file' => $vf['name']]);
                                    ?>
                                    <li class="list-group-item d-flex align-items-center justify-content-between py-2 px-2">
                                        <a href="../<?= e($vf['name']) ?>" target="_blank" rel="noopener" class="small text-break me-2">
                                            <i class="fa-regular fa-file-code me-1"></i><?= e($vf['name']) ?>
                                        </a>
                                        <?php if ($canEdit): ?>
                                            <a href="<?= e($delUrl) ?>" class="btn btn-sm btn-outline-đanger"
                                               data-confirm-delete data-confirm-name="<?= e($vf['name']) ?>"
                                               onclick="return confirm('Xóa file <?= e(addslashes($vf['name'])) ?>?');">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="form-text mb-3">Chưa có file xác minh trong root.</div>
                        <?php endif; ?>

                    <?php component('card_close'); ?>

                    <?php component('form/actions_bar', [
                        'can_edit' => $canEdit, 'save_edit' => false, 'save_label' => 'Cập nhật', 'sticky' => false,
                    ]); ?>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="js/ace-editor.js"></script>
<style>
.ace-editor-host { min-height: 160px; background: var(--bs-body-bg, #fff); }
.ace-editor-host .ace_editor {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}
#seo-serp-preview .text-primary { color: #1a0dab !important; }
#seo-serp-preview .text-success { color: #006621 !important; }
</style>
<script>
(function () {
  function clip(s, n) {
    s = String(s || "").replace(/\s+/g, " ").trim();
    if (s.length <= n) return s;
    return s.slice(0, n - 1) + "\u2026";
  }
  function activeLang() {
    var pane = document.querySelector("#form-seo-coban .tab-pane.active[data-seo-lang]");
    var box = document.getElementById("seo-serp-preview");
    return pane ? pane.getAttribute("data-seo-lang") : (box ? box.dataset.lang : "");
  }
  function updateSerp() {
    var box = document.getElementById("seo-serp-preview");
    if (!box) return;
    var lang = activeLang() || box.dataset.lang || "";
    var titleEl = document.getElementById("title_" + lang);
    var desEl = document.getElementById("des_" + lang);
    var t = titleEl ? titleEl.value : "";
    var d = desEl ? desEl.value : "";
    document.getElementById("serp-title").textContent = clip(t, 60) || "Tiêu đề trang";
    document.getElementById("serp-des").textContent = clip(d, 160) || "Mô tả trang sẽ hiển thị tại đây…";
    document.getElementById("serp-url").textContent = box.dataset.host || "";
  }
  document.addEventListener("input", function (e) {
    if (e.target && (e.target.classList.contains("js-seo-title") || e.target.classList.contains("js-seo-des"))) {
      updateSerp();
    }
  });
  document.querySelectorAll("[data-bs-toggle=tab], [data-toggle=tab]").forEach(function (tab) {
    tab.addEventListener("shown.bs.tab", updateSerp);
    tab.addEventListener("click", function () { setTimeout(updateSerp, 50); });
  });
  function syncOgImages() {
    document.querySelectorAll(".js-og-image-sync").forEach(function (h) {
      var srcId = h.getAttribute("data-src");
      var src = srcId ? document.getElementById(srcId) : null;
      if (src) h.value = src.value || "";
    });
  }
  var form = document.getElementById("form-seo-coban");
  if (form) form.addEventListener("submit", syncOgImages);
  document.querySelectorAll("input[id^=og_image_]").forEach(function (inp) {
    inp.addEventListener("change", syncOgImages);
  });
  setInterval(syncOgImages, 800);
  updateSerp();

})();
</script>
