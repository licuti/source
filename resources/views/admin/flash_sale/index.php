<?php
$title = "Sản phẩm trong Chiến dịch";
?>
<?= view('admin.components.breadcrumb', [
    'title' => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Các chiến dịch Flash Sale', 'url' => route('admin.flash_sale.index')],
        ['name' => $title, 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="row">
            
            <div class="col-12 mb-3">
                <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded border border-info border-2">
                    <div>
                        <h5 class="mb-1 fw-bold text-primary"><i class="fa-solid fa-bullhorn"></i> Chiến dịch: <?= e($campaign->name) ?></h5>
                        <div class="text-muted small">
                            <i class="fa-regular fa-clock"></i> Bắt đầu: <strong class="text-success"><?= date('H:i d/m/Y', strtotime($campaign->start_time)) ?></strong> &nbsp;|&nbsp;
                            <i class="fa-regular fa-clock"></i> Kết thúc: <strong class="text-danger"><?= date('H:i d/m/Y', strtotime($campaign->end_time)) ?></strong>
                        </div>
                    </div>
                    <a href="<?= route('admin.flash_sale.index') ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
                </div>
            </div>

            <!-- Danh sách -->
            <div class="col-md-8">
                <div class="card card-outline card-primary mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0 fw-bold">Danh sách Sản phẩm tham gia</h5>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50" class="text-center">#</th>
                                    <th>Sản phẩm</th>
                                    <th class="text-center">Giá Flash Sale</th>
                                    <th width="80" class="text-center">Gỡ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products->items())): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Không có sản phẩm nào trong chương trình này.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $i = 1; foreach($products as $item): ?>
                                    <tr id="row-<?= $item->id ?>">
                                        <td class="text-center"><?= $i++ ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= getImageUrl($item->thumbnail) ?>" class="rounded border me-2" style="width: 45px; height: 45px; object-fit: cover;">
                                                <div>
                                                    <a href="<?= route('admin.product.edit', ['id' => $item->id]) ?>" class="fw-bold text-dark text-decoration-none" target="_blank">
                                                        <?= e($item->title) ?>
                                                    </a>
                                                    <div class="small text-muted text-decoration-line-through"><?= number_format($item->price, 0, ',', '.') ?>đ</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold text-danger">
                                            <?= number_format($item->gia_flash_sale, 0, ',', '.') ?>đ
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="<?= $item->id ?>">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($products->lastPage() > 1): ?>
                    <div class="card-footer bg-white">
                        <?= $products->links() ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Thêm mới -->
            <div class="col-md-4">
                <div class="card card-outline card-success mb-4 sticky-top" style="top: 70px; z-index: 10;">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-plus text-success"></i> Thêm SP vào Chiến dịch</h5>
                    </div>
                    <form action="<?= route('admin.flash_sale.store_product') ?>" method="POST">
                        <input type="hidden" name="campaign_id" value="<?= $campaign->id ?>">
                        <div class="card-body">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Chọn sản phẩm</label>
                                <select name="product_id" id="product_id" class="form-select" required></select>
                            </div>
                            
                            <?= view('admin.components.input', [
                                'name' => 'gia_flash_sale',
                                'label' => 'Giá Flash Sale (VNĐ)',
                                'value' => '',
                                'class' => 'format-money',
                                'attrs' => ['required' => true]
                            ]) ?>
                            
                            <div class="alert alert-warning py-2 small mb-0">
                                <i class="fa-solid fa-lightbulb"></i> Sản phẩm này sẽ tự động kế thừa thời gian Bắt đầu và Kết thúc của Chiến dịch này!
                            </div>
                            
                        </div>
                        <div class="card-footer bg-light">
                            <button type="submit" class="btn btn-success fw-bold w-100"><i class="fa-solid fa-plus"></i> THÊM VÀO CHIẾN DỊCH</button>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    
    // Select2 Ajax search
    $('#product_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Gõ tên hoặc mã SP để tìm kiếm...',
        allowClear: true,
        ajax: {
            url: '<?= route('admin.flash_sale.search_products') ?>',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.results
                };
            },
            cache: true
        },
        templateResult: formatRepo,
        templateSelection: formatRepoSelection
    });

    function formatRepo (repo) {
        if (repo.loading) {
            return repo.text;
        }
        var $container = $(
            "<div class='d-flex align-items-center " + (repo.disabled ? "opacity-50" : "") + "'>" +
              "<img src='" + repo.thumbnail + "' class='rounded me-2 border' style='width:30px; height:30px; object-fit:cover;' />" +
              "<div class='fw-bold text-truncate' style='max-width: 250px;'>" + repo.text + "</div>" +
            "</div>"
        );
        return $container;
    }

    function formatRepoSelection (repo) {
        return repo.title || repo.text;
    }
    
    // Format money helper
    $('.format-money').on('input', function() {
        let val = $(this).val().replace(/[^0-9]/g, '');
        if(val !== '') {
            $(this).val(parseInt(val, 10).toLocaleString('vi-VN'));
        }
    });

    // Delete Ajax
    $('.btn-delete').click(function() {
        let id = $(this).data('id');
        let row = $('#row-' + id);
        
        Swal.fire({
            title: 'Xóa khỏi chiến dịch?',
            text: "Sản phẩm này sẽ trở về giá bán bình thường!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= route('admin.flash_sale.destroy_product') ?>',
                    type: 'POST',
                    data: { id: id },
                    success: function(res) {
                        if(res.success) {
                            row.fadeOut(300, function() { $(this).remove(); });
                            Toast.fire({ icon: 'success', title: res.message });
                        } else {
                            Toast.fire({ icon: 'error', title: res.message });
                        }
                    }
                });
            }
        });
    });
});
</script>
