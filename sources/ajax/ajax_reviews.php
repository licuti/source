<?php
if (!isset($_SESSION)) {
    session_set_cookie_params(['SameSite' => 'None']);
    session_start();
}
ob_start();
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

    define('_source_lib', '../lib/');
    require_once __DIR__ . '/../../admin/lib/bootstrap.php';
    $d = new func_index($config['database']);
include_once _source_lib . 'lang.php';
include_once _source_lib . 'info.php';
include_once _source_lib . 'function.php';

function json_out($data) { echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

$id_sp       = (int)($_POST['id_sanpham']  ?? 0);
$offset      = (int)($_POST['offset']      ?? 0);
$limit       = (int)($_POST['limit']       ?? 5);
$filter_star = (int)($_POST['filter_star'] ?? 0);
$filter_media= (int)($_POST['filter_media']?? 0);

if (!$id_sp) json_out(['success' => false, 'message' => 'Invalid product']);

$limit = max(1, min($limit, 50)); // clamp 1–50

// Build where
$where = "bl.id_sanpham = $id_sp AND bl.trang_thai = 1 AND bl.parent = 0";
if ($filter_star >= 1 && $filter_star <= 5) $where .= " AND bl.danh_gia = $filter_star";
if ($filter_media == 1) $where .= " AND EXISTS (SELECT 1 FROM #_binhluan_media m WHERE m.id_binhluan = bl.id)";

$total_sql = $d->simple_fetch("SELECT COUNT(bl.id) as cnt FROM #_binhluan bl WHERE $where");
$total = (int)($total_sql['cnt'] ?? 0);

$items = $d->o_fet("SELECT bl.* FROM #_binhluan bl WHERE $where ORDER BY bl.id DESC LIMIT $limit OFFSET $offset");

// Load media in one query
$media_map = [];
$ids = array_column($items, 'id');
if (!empty($ids)) {
    $ids_str = implode(',', array_map('intval', $ids));
    $medias  = $d->o_fet("SELECT * FROM #_binhluan_media WHERE id_binhluan IN ($ids_str) ORDER BY id ASC");
    foreach ($medias as $m) $media_map[$m['id_binhluan']][] = $m;
}

// Render HTML for each review item
ob_start();
foreach ($items as $value):
    $media_items = $media_map[$value['id']] ?? [];
    $replies     = $d->o_fet("SELECT * FROM #_binhluan WHERE parent = " . (int)$value['id'] . " AND trang_thai=1 ORDER BY id ASC");
    $initial = mb_strtoupper(mb_substr($value['ho_ten'] ?: 'A', 0, 1));
?>
<div class="item-binhluan" data-star="<?= (int)$value['danh_gia'] ?>">
    <div class="review-user">
        <div class="review-avatar"><?= $initial ?></div>
        <div>
            <div class="fw-semibold"><?= htmlspecialchars($value['ho_ten']) ?></div>
            <div class="review-date"><?= timeAgo($value['ngay']) ?></div>
        </div>
        <?php if ($value['danh_gia'] > 0): ?>
        <div class="ms-auto"><?= renderStars($value['danh_gia'], null, 14) ?></div>
        <?php endif; ?>
    </div>

    <?php if ($value['tieu_de']): ?><div class="review-title"><?= htmlspecialchars($value['tieu_de']) ?></div><?php endif; ?>
    <p class="review-body"><?= nl2br(htmlspecialchars($value['noi_dung'])) ?></p>

    <?php if (!empty($media_items)): ?>
    <div class="review-media-grid">
        <?php foreach ($media_items as $m):
            $url = URLPATH . 'media/review/' . htmlspecialchars($m['ten_file']);
        ?>
        <?php if ($m['loai'] === 'video'): ?>
            <a href="<?= $url ?>" data-fancybox="review-<?= $value['id'] ?>" class="review-media-item review-media-video">
                <video src="<?= $url ?>" muted playsinline preload="metadata"></video>
                <span class="video-badge"><svg width="20" height="20" viewBox="0 0 24 24" fill="white"><polygon points="5 3 19 12 5 21 5 3"/></svg></span>
            </a>
        <?php else: ?>
            <a href="<?= $url ?>" data-fancybox="review-<?= $value['id'] ?>" class="review-media-item">
                <img src="<?= $url ?>" alt="Ảnh đánh giá" loading="lazy">
            </a>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($replies)): ?>
    <div class="list-traloi mt-2">
        <?php foreach ($replies as $item): ?>
        <div class="item-traloi">
            <strong><?= $item['ho_ten'] == '' ? 'Admin' : htmlspecialchars($item['ho_ten']) ?></strong>
            <i class="text-muted ms-1" style="font-size:.85em"><?= timeAgo($item['ngay']) ?></i>
            <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($item['noi_dung'])) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <a class="review-reply-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#collapse<?= $value['id'] ?>" href="#">
        <i class="fa-solid fa-reply fa-rotate-180"></i> Trả lời
    </a>
    <div id="collapse<?= $value['id'] ?>" class="accordion-collapse collapse panel-traloi content-comment mt-3">
        <form method="POST" action="">
            <input type="hidden" name="parent" value="<?= $value['id'] ?>"/>
            <?php if (!empty($_SESSION['id_login'])): ?>
                <input type="hidden" name="ho_ten" value="<?= htmlspecialchars($_SESSION['ho_ten'] ?? '') ?>"/>
                <input type="hidden" name="email"  value="<?= htmlspecialchars($_SESSION['email']  ?? '') ?>"/>
            <?php else: ?>
                <div class="row g-2 mb-2">
                    <div class="col-md-6"><input type="text"  name="ho_ten" placeholder="Họ tên" class="form-control"/></div>
                    <div class="col-md-6"><input type="email" name="email"  placeholder="Email"  class="form-control"/></div>
                </div>
            <?php endif; ?>
            <textarea class="form-control mb-2" rows="3" name="noi_dung" placeholder="Nhập nội dung trả lời..."></textarea>
            <div class="d-flex gap-2">
                <button type="submit" name="guibinhluan" class="btn-custom btn-x btn-guibl">Gửi</button>
                <a class="btn-link text-muted" data-bs-toggle="collapse" data-bs-target="#collapse<?= $value['id'] ?>">Hủy</a>
            </div>
        </form>
    </div>
</div>
<?php endforeach;
$html = ob_get_clean();

json_out([
    'success'  => true,
    'html'     => $html,
    'loaded'   => count($items),
    'has_more' => ($offset + count($items)) < $total,
    'total'    => $total,
]);
