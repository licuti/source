<?php
// ============================================================
// REVIEW CONFIG — edit here
// ============================================================
define('REVIEW_MAX_FILES',  10);  // Max files per upload
define('REVIEW_MAX_IMG_MB',  5);  // Max MB per image
define('REVIEW_MAX_VID_MB', 50);  // Max MB per video
define('REVIEW_IMG_TYPES',  'image/jpeg,image/png,image/webp,image/gif');
define('REVIEW_VID_TYPES',  'video/mp4,video/webm,video/quicktime');
define('REVIEW_PER_PAGE',    2);  // Reviews shown initially + per "Load more"

// ── Handle review submission ────────────────────────────────
if (isset($_POST['guibinhluan'])) {
    $review_data = [
        'danh_gia'   => (int)sanitizeHtml(addslashes($_POST['diem']     ?? '5')),
        'tieu_de'    => sanitizeHtml(addslashes($_POST['tieude']     ?? '')),
        'noi_dung'   => sanitizeHtml(addslashes($_POST['noi_dung']   ?? '')),
        'ho_ten'     => sanitizeHtml(addslashes($_POST['ho_ten']     ?? '')),
        'email'      => sanitizeHtml(addslashes($_POST['email']      ?? '')),
        'id_user'    => (int)($_SESSION['id_login'] ?? 0),
        'ngay'       => date('Y-m-d H:i:s'),
        'id_sanpham' => $row->id_code,
        'parent'     => (int)addslashes($_POST['parent'] ?? '0'),
        'trang_thai' => 1,
    ];

    $d->reset();
    $d->setTable('#_binhluan');

    if ($review_id = $d->insert($review_data)) {
        // Save attached media
        $media_names = array_filter(array_map('trim', explode(',', $_POST['review_media_files'] ?? '')));
        $media_types = array_values(array_filter(array_map('trim', explode(',', $_POST['review_media_types'] ?? ''))));

        foreach ($media_names as $idx => $filename) {
            if (empty($filename)) continue;
            $d->reset();
            $d->setTable('#_binhluan_media');
            $d->insert([
                'id_binhluan' => $review_id,
                'loai'        => ($media_types[$idx] ?? '') === 'video' ? 'video' : 'image',
                'ten_file'    => basename($filename),
                'ngay'        => date('Y-m-d H:i:s'),
            ]);
        }

        $thongbao_tt      = '';
        $thongbao_icon    = 'success';
        $thongbao_content = 'Cảm ơn bạn đã đánh giá sản phẩm!';
        $thongbao_url     = _url_page . '#nhanxet';
    }
}

// ── Fetch data ──────────────────────────────────────────────
$product_id   = (int)$row->id_code;
$filter_star  = isset($_GET['bl_star'])  ? (int)$_GET['bl_star']  : 0;
$filter_media = isset($_GET['bl_media']) ? (int)$_GET['bl_media'] : 0;

$filters = [
    'bl_star'  => $filter_star,
    'bl_media' => $filter_media
];

$BinhLuan = new BinhLuanModel();
$filtered_count = $BinhLuan->countForProduct($product_id, $filters);
$reviews = $BinhLuan->getForProduct($product_id, $filters, REVIEW_PER_PAGE, 0);
$initial_count = count($reviews);
$has_more      = $filtered_count > $initial_count;

// Rating summary (unfiltered)
$avg_rating  = isset($sp_avg_rating)  ? $sp_avg_rating  : 0;
$total_count = isset($sp_total_rating) ? $sp_total_rating : 0;
if (!$avg_rating) {
    $summary     = BinhLuanModel::getSummary($product_id);
    $avg_rating  = $summary['avg'];
    $total_count = $summary['total'];
}

$total_reviews = BinhLuanModel::getSummary($product_id)['total'];

// Per-star counts
$star_counts = BinhLuanModel::getStarCounts($product_id);

// Count reviews that have at least one media file
$media_reviews_count = BinhLuanModel::countMediaReviews($product_id);

$base_url = strtok($_SERVER['REQUEST_URI'], '?');
?>

<div class="danh-gia" id="nhanxet"
    data-sp="<?= $product_id ?>"
    data-offset="<?= $initial_count ?>"
    data-limit="<?= REVIEW_PER_PAGE ?>"
    data-filter-star="<?= $filter_star ?>"
    data-filter-media="<?= $filter_media ?>"
    data-ajax-url="<?= URLPATH ?>ajax/reviews/load">

    <!-- Rating overview -->
    <div class="review-overview">

        <div class="review-score-col">
            <div class="review-big-score"><?= number_format($avg_rating, 1) ?></div>
            <div class="review-stars-lg"><?= renderStars($avg_rating, null, 24) ?></div>
            <div class="review-total-text"><?= $total_reviews ?> đánh giá</div>
        </div>

        <div class="review-bars-col">
            <?php foreach ([5, 4, 3, 2, 1] as $star_num):
                $count = $star_counts[$star_num];
                $pct   = getPercent($count, $total_reviews);
                $active = ($filter_star === $star_num);
            ?>
            <a href="<?= reviewFilterUrl($base_url, $active ? 0 : $star_num, $filter_media) ?>"
               class="item-progress<?= $active ? ' active' : '' ?>">
                <span class="rating-num"><?= $star_num ?>★</span>
                <div class="box-progress">
                    <div class="progress">
                        <div class="progress-bar" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
                <span class="rating-num-total"><?= $count ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="review-write-col">
            <p class="text-muted mb-2" style="font-size:.9em">Chia sẻ trải nghiệm của bạn</p>
            <button type="button" class="btn-custom btn-x" data-bs-toggle="modal" data-bs-target="#modalReview">
                <i class="fa fa-pen me-1"></i> Viết đánh giá
            </button>
        </div>

    </div><!-- /review-overview -->

    <!-- Filter chips -->
    <div class="review-filters">
        <span class="filter-label">Lọc:</span>
        <a href="<?= reviewFilterUrl($base_url) ?>"
           class="filter-chip<?= (!$filter_star && !$filter_media) ? ' active' : '' ?>">
            Tất cả (<?= $total_reviews ?>)
        </a>
        <?php foreach ([5, 4, 3, 2, 1] as $star_num): ?>
        <a href="<?= reviewFilterUrl($base_url, $star_num, $filter_media) ?>"
           class="filter-chip<?= $filter_star === $star_num ? ' active' : '' ?>">
            <?= $star_num ?>★ (<?= $star_counts[$star_num] ?>)
        </a>
        <?php endforeach; ?>
        <?php if ($media_reviews_count > 0): ?>
        <a href="<?= reviewFilterUrl($base_url, $filter_star, $filter_media ? 0 : 1) ?>"
           class="filter-chip<?= $filter_media ? ' active' : '' ?>">
            📷 Có ảnh/video (<?= $media_reviews_count ?>)
        </a>
        <?php endif; ?>
    </div>

    <!-- Review list -->
    <div class="row">
        <div class="col-lg-9">

            <?php if (empty($reviews)): ?>

            <div class="review-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <p>Chưa có đánh giá nào.</p>
            </div>

            <?php else: ?>

            <div class="list-binhluan" id="review-list">
                <?php foreach ($reviews as $review):
                    echo view('partials/components/review-item', ['review' => $review]);
                endforeach; ?>
            </div><!-- /list-binhluan -->

            <?php if ($has_more): ?>
            <div id="load-more-wrap" class="review-load-more">
                <button id="btn-load-more" class="btn-load-more" type="button">
                    <span class="load-more-text">
                        Xem thêm đánh giá (<?= $filtered_count - $initial_count ?> còn lại)
                    </span>
                    <span class="load-more-spinner" style="display:none">
                        <i class="fa fa-spinner fa-spin me-1"></i>Đang tải...
                    </span>
                </button>
            </div>
            <?php endif; ?>

            <?php endif; ?>

        </div><!-- /col -->
    </div><!-- /row -->

</div><!-- /danh-gia -->

<!-- ══════════════════════════════════════════════════════
     MODAL: Write a review
══════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalReview" tabindex="-1" aria-labelledby="modalReviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content review-modal-content">

            <div class="modal-header review-modal-header">
                <h5 class="modal-title fw-bold" id="modalReviewLabel">
                    <i class="fa fa-star me-2" style="color:#f59e0b"></i>
                    <?= __('Viết đánh giá') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <form method="POST" action="" id="form-review">
                    <input type="hidden" name="parent" value="0"/>

                    <!-- Star rating picker -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Đánh giá của bạn <span class="text-danger">*</span>
                        </label>
                        <div id="cate-rating" class="cate-rating d-flex align-items-center gap-2">
                            <div class="star-picker">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                <a id="star-<?= $s ?>" data="<?= $s ?>" class="star vote-active" title="<?= $s ?> sao">
                                    <i class="fa fa-star"></i>
                                </a>
                                <?php endfor; ?>
                            </div>
                            <span class="star-label text-muted" id="star-label-txt">5 sao - Xuất sắc</span>
                        </div>
                        <input name="diem" type="hidden" value="5" id="diem"/>
                    </div>

                    <!-- Name / email (guest only) -->
                    <?php if (!empty($_SESSION['id_login'])): ?>
                        <input type="hidden" name="ho_ten" value="<?= htmlspecialchars($user_login['ho_ten']) ?>"/>
                        <input type="hidden" name="email"  value="<?= htmlspecialchars($user_login['email']) ?>"/>
                    <?php else: ?>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?= __('Họ tên') ?> <span class="text-danger">*</span></label>
                                <input type="text" name="ho_ten" placeholder="<?= __('Nhập họ tên') ?>"
                                       class="form-control" required/>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" placeholder="<?= __('Nhập email') ?>"
                                       class="form-control" required/>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Review content -->
                    <div class="mb-3">
                        <label class="form-label"><?= __('Nội dung đánh giá') ?> <span class="text-danger">*</span></label>
                        <textarea class="form-control" rows="4" name="noi_dung"
                                  placeholder="<?= __('Nhập nội dung') ?>" required></textarea>
                    </div>

                    <!-- Media upload -->
                    <div class="review-upload-area mb-4" id="review-upload-area">
                        <input type="file" id="review_media_input"
                               accept="<?= REVIEW_IMG_TYPES ?>,<?= REVIEW_VID_TYPES ?>"
                               multiple style="display:none">

                        <div class="upload-drop-zone" id="upload-drop-zone"
                             onclick="document.getElementById('review_media_input').click()">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="1.5">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <span>
                                Thêm ảnh / video
                                <small class="d-block text-muted">
                                    Tối đa <?= REVIEW_MAX_FILES ?> file ·
                                    ảnh ≤ <?= REVIEW_MAX_IMG_MB ?>MB ·
                                    video ≤ <?= REVIEW_MAX_VID_MB ?>MB
                                </small>
                            </span>
                        </div>

                        <div class="upload-preview-grid" id="upload-preview-grid"></div>
                        <div class="upload-error" id="upload-error" style="display:none"></div>

                        <input type="hidden" name="review_media_files" id="review_media_files" value="">
                        <input type="hidden" name="review_media_types" id="review_media_types" value="">
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" name="guibinhluan" class="btn-custom btn-x btn-guibl">
                            <i class="fa fa-paper-plane me-1"></i><?= __('Gửi đánh giá') ?>
                        </button>
                    </div>

                </form>
            </div><!-- /modal-body -->

        </div><!-- /modal-content -->
    </div><!-- /modal-dialog -->
</div><!-- /modal -->

<script>
(function () {
    // JS config — values sourced from PHP constants
    var CFG = {
        maxFiles:  <?= REVIEW_MAX_FILES ?>,
        maxImgMB:  <?= REVIEW_MAX_IMG_MB ?>,
        maxVidMB:  <?= REVIEW_MAX_VID_MB ?>,
        uploadUrl: '<?= URLPATH ?>ajax/reviews/media',
        imgExt:    ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        vidExt:    ['mp4', 'webm', 'mov', 'avi']
    };

    var starLabels = {
        1: '1 sao - Tệ',
        2: '2 sao - Không hài lòng',
        3: '3 sao - Bình thường',
        4: '4 sao - Tốt',
        5: '5 sao - Xuất sắc'
    };

    // ── Star picker ─────────────────────────────────────────
    function setActiveStars(n) {
        $('.star-picker .star').removeClass('vote-active');
        for (var i = 1; i <= n; i++) $('#star-' + i).addClass('vote-active');
        $('#star-label-txt').text(starLabels[n] || '');
    }

    $(document).on('mouseenter', '.star-picker .star', function () {
        var n = parseInt($(this).attr('data'));
        $('.star-picker .star').removeClass('vote-active vote-hover');
        for (var i = 1; i <= n; i++) $('#star-' + i).addClass('vote-hover');
        $('#star-label-txt').text(starLabels[n] || '');
    }).on('mouseleave', '.star-picker', function () {
        $('.star-picker .star').removeClass('vote-hover');
        setActiveStars(parseInt($('#diem').val()) || 5);
    }).on('click', '.star-picker .star', function (e) {
        e.preventDefault();
        var n = parseInt($(this).attr('data'));
        $('#diem').val(n);
        setActiveStars(n);
    });

    // ── Reset modal on close ────────────────────────────────
    $('#modalReview').on('hidden.bs.modal', function () {
        uploadedFiles = []; uploadedNames = []; uploadedTypes = [];
        $('#upload-preview-grid').empty();
        $('#review_media_files, #review_media_types').val('');
    });

    // ── File upload ─────────────────────────────────────────
    var uploadedFiles = [], uploadedNames = [], uploadedTypes = [];

    function syncInputs() {
        $('#review_media_files').val(uploadedNames.join(','));
        $('#review_media_types').val(uploadedTypes.join(','));
    }

    function showError(msg) {
        $('#upload-error').text(msg).show();
        setTimeout(function () { $('#upload-error').fadeOut(); }, 4000);
    }

    function buildPreviews() {
        var $grid = $('#upload-preview-grid').empty();
        uploadedFiles.forEach(function (file, idx) {
            var $item = $('<div class="upload-preview-item">' +
                '<button type="button" class="upload-remove" data-idx="' + idx + '">×</button>' +
                '</div>');
            if (file.type === 'video') {
                $item.prepend('<video src="' + file.url + '" muted></video>' +
                              '<span class="video-badge-sm">▶</span>');
            } else {
                $item.prepend('<img src="' + file.url + '" alt="">');
            }
            $grid.append($item);
        });
    }

    $(document).on('click', '.upload-remove', function () {
        var idx = parseInt($(this).data('idx'));
        uploadedFiles.splice(idx, 1);
        uploadedNames.splice(idx, 1);
        uploadedTypes.splice(idx, 1);
        syncInputs();
        buildPreviews();
    });

    function doUpload(files) {
        if (!files || !files.length) return;
        if (uploadedFiles.length + files.length > CFG.maxFiles) {
            showError('Tối đa ' + CFG.maxFiles + ' file.');
            return;
        }

        var fd = new FormData(), valid = 0;

        for (var i = 0; i < files.length; i++) {
            var f   = files[i];
            var ext = f.name.split('.').pop().toLowerCase();
            var isImg = CFG.imgExt.indexOf(ext) >= 0;
            var isVid = CFG.vidExt.indexOf(ext) >= 0;

            if (!isImg && !isVid) {
                showError('"' + f.name + '": định dạng không hỗ trợ');
                continue;
            }

            var limitMB = isImg ? CFG.maxImgMB : CFG.maxVidMB;
            if (f.size > limitMB * 1048576) {
                showError('"' + f.name + '" vượt quá ' + limitMB + 'MB');
                continue;
            }

            fd.append('media[]', f);
            valid++;
        }

        if (!valid) return;

        var $dropZone = $('#upload-drop-zone').addClass('uploading');

        $.ajax({
            url:         CFG.uploadUrl,
            type:        'POST',
            data:        fd,
            processData: false,
            contentType: false,
            dataType:    'json',
            success: function (res) {
                $dropZone.removeClass('uploading');
                (res.files || []).forEach(function (file) {
                    uploadedFiles.push(file);
                    uploadedNames.push(file.name);
                    uploadedTypes.push(file.type);
                });
                syncInputs();
                buildPreviews();
                if (res.errors && res.errors.length) showError(res.errors.join('; '));
            },
            error: function () {
                $dropZone.removeClass('uploading');
                showError('Lỗi kết nối, vui lòng thử lại.');
            }
        });
    }

    $('#review_media_input').on('change', function () {
        doUpload(this.files);
        this.value = '';
    });

    var $dropZone = $('#upload-drop-zone');
    $dropZone
        .on('dragover',  function (e) { e.preventDefault(); $(this).addClass('drag-over'); })
        .on('dragleave', function (e) { e.preventDefault(); $(this).removeClass('drag-over'); })
        .on('drop',      function (e) { e.preventDefault(); $(this).removeClass('drag-over'); doUpload(e.originalEvent.dataTransfer.files); });

    // ── Load more ───────────────────────────────────────────
    var $container = $('#nhanxet');
    var $list      = $('#review-list');
    var $btnWrap   = $('#load-more-wrap');
    var isLoading  = false;

    $('#btn-load-more').on('click', function () {
        if (isLoading) return;
        isLoading = true;

        var $btn = $(this);
        $btn.find('.load-more-text').hide();
        $btn.find('.load-more-spinner').show();

        $.ajax({
            url:      $container.data('ajax-url'),
            type:     'POST',
            dataType: 'json',
            data: {
                id_sanpham:   $container.data('sp'),
                offset:       $container.data('offset'),
                limit:        $container.data('limit'),
                filter_star:  $container.data('filter-star'),
                filter_media: $container.data('filter-media')
            },
            success: function (res) {
                if (res.success && res.html) {
                    $list.append(res.html);
                    $container.data('offset', $container.data('offset') + res.loaded);
                }

                if (!res.has_more) {
                    $btnWrap.fadeOut();
                } else {
                    var remaining = res.total - $container.data('offset');
                    $btn.find('.load-more-text').text('Xem thêm đánh giá (' + remaining + ' còn lại)').show();
                    $btn.find('.load-more-spinner').hide();
                }

                isLoading = false;
            },
            error: function () {
                $btn.find('.load-more-text').show();
                $btn.find('.load-more-spinner').hide();
                isLoading = false;
            }
        });
    });

})();
</script>
