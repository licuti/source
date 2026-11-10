<?php
/**
 * Quản lý Đội ngũ giáo viên — Danh sách hồ sơ hiển thị Website
 *
 * PHP 7.3 compatible.
 */

$canEdit = $d->checkPermission_edit($id_module) == 1;
$MODULE  = 'giao-vien';
$page    = (int) ($current_page ?? ($_GET['page'] ?? 1));

component('page_header', [
    'title'      => 'Đội ngũ giáo viên (Hồ sơ Website)',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Đội ngũ giáo viên'],
    ],
]);
?>

<div class="app-content">
    <div class="container-fluid">

        <!-- Thanh tìm kiếm & bộ lọc -->
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body p-3">
                <form method="get" action="index.php" class="row g-2 align-items-center">
                    <input type="hidden" name="p" value="<?= e($MODULE) ?>">
                    <input type="hidden" name="a" value="man">

                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" name="key" class="form-control" placeholder="Tìm theo tên giáo viên, mã GV, chức danh..."
                                   value="<?= e($keyword ?? '') ?>">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">— Tất cả trạng thái web —</option>
                            <option value="1" <?= ($filter_status === '1') ? 'selected' : '' ?>>Đang hiển thị ngoài Website</option>
                            <option value="0" <?= ($filter_status === '0') ? 'selected' : '' ?>>Đang ẩn khỏi Website</option>
                        </select>
                    </div>

                    <div class="col-md-4 text-md-end">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-filter me-1"></i> Lọc
                        </button>
                        <a href="<?= e(admin_url(['p' => $MODULE, 'a' => 'man'])) ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-rotate me-1"></i> Làm mới
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <?php component('card_open', ['body' => 'none']); ?>

            <div class="table-responsive p-3">
                <table class="table table-hover table-bordered align-middle table-admin mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">STT</th>
                            <th class="text-center" style="width: 80px;">Ảnh</th>
                            <th>Giáo viên</th>
                            <th>Chức danh / Badge</th>
                            <th>Slogan / Quote</th>
                            <th class="text-center" style="width: 120px;">Tài liệu / Media</th>
                            <th class="text-center" style="width: 80px;">Thứ tự</th>
                            <th class="text-center" style="width: 130px;">Website</th>
                            <th class="text-center" style="width: 120px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $idx => $row):
                                $avatarUrl = !empty($row['avatar']) ? img_url($row['avatar']) : 'img/no-image.png';
                                $isShown   = !empty($row['show_on_website']) && (int) $row['show_on_website'] === 1;
                                $hasProfile = !empty($row['profile_id']);
                                ?>
                                <tr>
                                    <td class="text-center text-muted small">
                                        <?= (int) (($page - 1) * $limit + $idx + 1) ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="ratio ratio-1x1 rounded-circle overflow-hidden border mx-auto" style="width: 48px; height: 48px;">
                                            <img src="<?= e($avatarUrl) ?>" alt="<?= e($row['full_name']) ?>" class="object-fit-cover w-100 h-100"
                                                 onerror="this.src='img/no-image.png'">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($row['full_name']) ?></div>
                                        <div class="small text-muted">
                                            <span class="badge bg-light text-secondary border"><?= e($row['teacher_code']) ?></span>
                                            <?php if (!empty($row['gender'])): ?>
                                                <span class="ms-1"><?= $row['gender'] === 'female' ? 'Cô' : 'Thầy' ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['title_badge'])): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-7">
                                                <i class="fa-solid fa-award me-1"></i><?= e($row['title_badge']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small italic">Chưa nhập badge</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['quote'])): ?>
                                            <small class="text-secondary fst-italic">"<?= e(mb_strimwidth($row['quote'], 0, 60, '...')) ?>"</small>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center small">
                                        <?php if ($hasProfile): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle" title="Đã có hồ sơ web">
                                                <i class="fa-solid fa-check me-1"></i> Đã có profile
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                Chưa tạo profile
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= (int) ($row['so_thu_tu'] ?? 0) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= e(admin_url(['p' => $MODULE, 'a' => 'toggle', 'id' => (int) $row['user_id']])) ?>"
                                           class="btn btn-sm <?= $isShown ? 'btn-success' : 'btn-outline-secondary' ?> py-0 px-2 rounded-pill"
                                           title="Bấm để thay đổi hiển thị">
                                            <i class="fa-solid <?= $isShown ? 'fa-eye' : 'fa-eye-slash' ?> me-1"></i>
                                            <?= $isShown ? 'Đang hiện' : 'Đang ẩn' ?>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= e(admin_url(['p' => $MODULE, 'a' => 'edit', 'id' => (int) $row['user_id']])) ?>"
                                           class="btn btn-sm btn-primary py-1 px-2"
                                           title="Chỉnh sửa nội dung hiển thị website">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Sửa nội dung
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-user-xmark fs-2 d-block mb-2 text-secondary"></i>
                                    Không tìm thấy giáo viên nào.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php component('card_close'); ?>

        <?php if (!empty($total_page) && $total_page > 1): ?>
            <div class="mt-3">
                <?php component('pagination', [
                    'current' => $page,
                    'total'   => $total_page,
                    'url'     => admin_url([
                        'p'      => $MODULE,
                        'a'      => 'man',
                        'key'    => $keyword ?? '',
                        'status' => $filter_status ?? '',
                        'page'   => '%d',
                    ]),
                ]); ?>
            </div>
        <?php endif; ?>

    </div>
</div>
