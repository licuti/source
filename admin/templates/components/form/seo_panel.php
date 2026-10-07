<?php
/**
 * Component: form/seo_panel
 * Khối "Cấu hình SEO" có xem trước kết quả trên Google (SERP preview).
 *
 * Mục tiêu: thay vì một chuỗi ô nhập rời rạc, người biên tập thấy ngay
 * trang của mình sẽ hiển thị thế nào trên Google khi gõ, kèm thanh đo
 * độ dài Title/Description theo ngưỡng khuyến nghị.
 *
 * ⚠️ RÀNG BUỘC KHÔNG ĐƯỢC PHÁ:
 *  - Tên field giữ nguyên dạng mảng: title[], des[], keyword[],
 *    seo_head[], seo_body[] — controller đọc theo đúng thứ tự ngôn ngữ.
 *  - id của ô Title phải là "title_{lang}" vì hàm addText() trong admin.js
 *    tự điền vào selector này khi gõ Tên danh mục.
 *  - Mỗi tab ngôn ngữ chỉ được xuất ĐÚNG MỘT input cho mỗi field ở trên.
 *    Các phần xem trước đều là thẻ tĩnh (div/span), không phải input,
 *    nên không ảnh hưởng dữ liệu gửi lên.
 *
 * Props:
 * @var string $lang       Mã ngôn ngữ, vd 'vi'
 * @var string $suffix     Hậu tố nhãn khi đa ngôn ngữ, vd ' (vi)'
 * @var string $title
 * @var string $des
 * @var string $keyword
 * @var string $seo_head
 * @var string $seo_body
 * @var string $base_url   Tên miền hiển thị trong preview
 * @var string $name_id    id ô "Tên danh mục" (dùng làm title dự phòng)
 * @var string $slug_id    id ô "Tiền tố đường dẫn"
 * @var string $alias_id   id ô "Đường dẫn"
 */
$lang     = $lang     ?? '';
$suffix   = $suffix   ?? '';
$title    = $title    ?? '';
$des      = $des      ?? '';
$keyword  = $keyword  ?? '';
$seo_head = $seo_head ?? '';
$seo_body = $seo_body ?? '';
$base_url = $base_url ?? '';
$name_id  = $name_id  ?? '';
$slug_id  = $slug_id  ?? '';
$alias_id = $alias_id ?? '';

$titleId = 'title_' . $lang;   // BẮT BUỘC giữ đúng định dạng này
$desId   = 'des_' . $lang;
$kwId    = 'keyword_' . $lang;
$headId  = 'seo_head_' . $lang;
$bodyId  = 'seo_body_' . $lang;

// Bỏ giao thức cho gọn khi hiển thị breadcrumb kiểu Google
$displayHost = preg_replace('~^https?://~i', '', rtrim($base_url, '/'));

/*
 * Ngưỡng khuyến nghị (theo độ dài Google thường cắt):
 *  - Title: 50-60 ký tự
 *  - Description: 120-160 ký tự
 * Truyền xuống JS qua data-attribute để không hard-code 2 nơi.
 */
$limits = ['title' => [50, 60], 'des' => [120, 160]];
?>
<div class="seo-panel"
     data-seo-panel
     data-title-id="<?= e($titleId) ?>"
     data-des-id="<?= e($desId) ?>"
     data-name-id="<?= e($name_id) ?>"
     data-slug-id="<?= e($slug_id) ?>"
     data-alias-id="<?= e($alias_id) ?>"
     data-base-url="<?= e($displayHost) ?>"
     data-title-min="<?= $limits['title'][0] ?>"
     data-title-max="<?= $limits['title'][1] ?>"
     data-des-min="<?= $limits['des'][0] ?>"
     data-des-max="<?= $limits['des'][1] ?>">

    <div class="seo-panel__head">
        <h5 class="seo-panel__heading">
            <i class="fa-solid fa-magnifying-glass-chart"></i>
            Cấu hình SEO<?= e($suffix) ?>
        </h5>

        <div class="btn-group btn-group-sm seo-panel__devices" role="group"
             aria-label="Chế độ xem trước">
            <button type="button" class="btn btn-outline-secondary active"
                    data-seo-device="desktop" aria-pressed="true">
                <i class="fa-solid fa-desktop me-1"></i>Máy tính
            </button>
            <button type="button" class="btn btn-outline-secondary"
                    data-seo-device="mobile" aria-pressed="false">
                <i class="fa-solid fa-mobile-screen-button me-1"></i>Điện thoại
            </button>
        </div>
    </div>

    <!-- ============ Xem trước kết quả tìm kiếm ============ -->
    <div class="seo-preview" data-seo-preview>
        <div class="seo-preview__label">
            <i class="fa-brands fa-google"></i>
            <span>Xem trước trên Google</span>
            <span class="seo-preview__hint">Cập nhật trực tiếp khi bạn gõ</span>
        </div>

        <div class="seo-serp">
            <div class="seo-serp__site">
                <span class="seo-serp__favicon" aria-hidden="true">
                    <i class="fa-solid fa-globe"></i>
                </span>
                <span class="seo-serp__site-text">
                    <span class="seo-serp__host" data-seo-host><?= e($displayHost) ?></span>
                    <span class="seo-serp__url" data-seo-url><?= e($displayHost) ?></span>
                </span>
            </div>

            <div class="seo-serp__title" data-seo-serp-title>
                <?= e($title) ?>
            </div>

            <div class="seo-serp__desc" data-seo-serp-desc>
                <?= e($des) ?>
            </div>

            <div class="seo-serp__empty" data-seo-empty hidden>
                Chưa có dữ liệu — nhập Title và Description để xem trước.
            </div>
        </div>
    </div>

    <!-- ============ Các ô nhập ============ -->
    <div class="seo-fields">

        <div class="seo-field">
            <div class="seo-field__top">
                <label class="form-label mb-0 fw-medium" for="<?= e($titleId) ?>">
                    Thẻ Title
                </label>
                <span class="seo-meter" data-seo-meter="title">
                    <span class="seo-meter__count" data-seo-count>0</span>
                    <span class="seo-meter__sep">/</span>
                    <span class="seo-meter__max"><?= $limits['title'][1] ?></span>
                    <span class="seo-meter__badge" data-seo-badge>Trống</span>
                </span>
            </div>

            <input type="text"
                   class="form-control form-control-sm"
                   name="title[]"
                   id="<?= e($titleId) ?>"
                   value="<?= e($title) ?>"
                   placeholder="Tiêu đề hiển thị trên Google"
                   autocomplete="off">

            <div class="seo-bar"><span class="seo-bar__fill" data-seo-bar></span></div>
            <div class="form-text">
                Nên dài <?= $limits['title'][0] ?>–<?= $limits['title'][1] ?> ký tự.
                Để trống sẽ tự lấy theo tên danh mục.
            </div>
        </div>

        <div class="seo-field">
            <div class="seo-field__top">
                <label class="form-label mb-0 fw-medium" for="<?= e($desId) ?>">
                    Thẻ Description
                </label>
                <span class="seo-meter" data-seo-meter="des">
                    <span class="seo-meter__count" data-seo-count>0</span>
                    <span class="seo-meter__sep">/</span>
                    <span class="seo-meter__max"><?= $limits['des'][1] ?></span>
                    <span class="seo-meter__badge" data-seo-badge>Trống</span>
                </span>
            </div>

            <textarea class="form-control form-control-sm"
                      name="des[]"
                      id="<?= e($desId) ?>"
                      rows="3"
                      placeholder="Mô tả ngắn hiển thị trên Google"><?= e($des) ?></textarea>

            <div class="seo-bar"><span class="seo-bar__fill" data-seo-bar></span></div>
            <div class="form-text">
                Nên dài <?= $limits['des'][0] ?>–<?= $limits['des'][1] ?> ký tự.
                Viết cho người đọc, tóm đúng nội dung trang.
            </div>
        </div>

        <div class="seo-field">
            <label class="form-label fw-medium" for="<?= e($kwId) ?>">Từ khóa</label>
            <textarea class="form-control form-control-sm"
                      name="keyword[]"
                      id="<?= e($kwId) ?>"
                      rows="2"
                      placeholder="Các từ khóa, cách nhau bằng dấu phẩy"><?= e($keyword) ?></textarea>
            <div class="seo-tags" data-seo-tags></div>
        </div>

        <!-- Nâng cao: mặc định thu gọn để không gây rối -->
        <div class="seo-advanced">
            <button class="seo-advanced__toggle collapsed" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#seo-adv-<?= e($lang) ?>"
                    aria-expanded="false"
                    aria-controls="seo-adv-<?= e($lang) ?>">
                <i class="fa-solid fa-chevron-right seo-advanced__caret"></i>
                <i class="fa-solid fa-code"></i>
                Chèn mã nâng cao
                <span class="seo-advanced__note">HTML / JavaScript</span>
            </button>

            <div class="collapse" id="seo-adv-<?= e($lang) ?>">
                <div class="seo-advanced__body">
                    <div class="mb-3">
                        <label class="form-label fw-medium" for="<?= e($headId) ?>">
                            HTML header
                            <code class="ms-1"><?= e('</head>') ?></code>
                        </label>
                        <textarea class="form-control form-control-sm font-monospace small"
                                  name="seo_head[]"
                                  id="<?= e($headId) ?>"
                                  rows="3"
                                  spellcheck="false"><?= e($seo_head) ?></textarea>
                        <div class="form-text">Mã được chèn ngay trước thẻ đóng <?= e('</head>') ?>.</div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-medium" for="<?= e($bodyId) ?>">
                            HTML footer
                            <code class="ms-1"><?= e('</body>') ?></code>
                        </label>
                        <textarea class="form-control form-control-sm font-monospace small"
                                  name="seo_body[]"
                                  id="<?= e($bodyId) ?>"
                                  rows="3"
                                  spellcheck="false"><?= e($seo_body) ?></textarea>
                        <div class="form-text">Mã được chèn ngay trước thẻ đóng <?= e('</body>') ?>.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
