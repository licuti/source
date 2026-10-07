<?php
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $d->o_que("DELETE FROM #_coupon WHERE id = $id");
    $d->redirect("index.php?p=coupon&a=man");
}

$canEdit   = $d->checkPermission_edit($id_module) == 1;
$canDelete = $d->checkPermission_dele($id_module) == 1;
$MODULE    = 'coupon';
?>

<?php component('page_header', [
    'title'      => 'Khách hàng liên hệ',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Quản trị khách hàng'],
        ['label' => 'Khách hàng liên hệ'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">
        <?php component('card_open', ['title' => 'Danh sách khách hàng liên hệ']); ?>

        <div class="table-responsive p-3">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 70px">STT</th>
                        <th>Thông tin</th>
                        <th style="width: 30%">Nội dung</th>
                        <th class="text-center" style="width: 150px">Ngày gửi</th>
                        <th class="text-center" style="width: 120px">Trạng thái</th>
                        <th class="text-center" style="width: 160px">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Chưa có dữ liệu nào.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $key => $value): ?>
                            <tr>
                                <td class="text-center"><?= $key + 1 ?></td>
                                <td>
                                    <div class="fw-medium"><?= e($value['ho_ten']) ?></div>
                                    <div class="small text-secondary">Điện thoại: <?= e($value['sdt']) ?></div>
                                    <div class="small text-secondary">Địa chỉ: <?= e($value['address']) ?></div>
                                </td>
                                <td><?= e(strip_tags($value['content'])) ?></td>
                                <td class="text-center"><?= e($value['ngay_hoi']) ?></td>
                                <td class="text-center">
                                    <?php if ($value['trang_thai'] == 0): ?>
                                        <span class="badge bg-warning text-dark">Chưa xem</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Đã xem</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-info me-1" data-bs-toggle="modal" data-bs-target="#detailModal" onclick="load_ajax(<?= (int) $value['id'] ?>)" title="Chi tiết">
                                        <i class="fa-solid fa-eye me-1"></i>Chi tiết
                                    </button>
                                    <?php if ($canDelete): ?>
                                        <a href="index.php?p=<?= e($_GET['p']) ?>&a=<?= e($_GET['a']) ?>&delete=<?= (int) $value['id'] ?>" onclick="return confirm('Xác nhận xóa?')" class="btn btn-sm btn-danger" title="Xóa">
                                            <i class="fa-solid fa-trash me-1"></i>Xóa
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php component('card_close'); ?>
    </div>
</div>

<!-- Modal chi tiết -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">Chi tiết liên hệ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body" id="body-lienhe"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
    function load_ajax(id) {
        $.ajax({
            url: "sources/ajax.php",
            type: "post",
            dataType: "text",
            data: {
                do: 'get_coupon',
                id: id
            },
            success: function (result) {
                $('#body-lienhe').html(result);
            }
        });
    }
</script>
