<?php
$title = "Chương trình Flash Sale";
?>
<?= view('admin.components.breadcrumb', [
    'title' => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => $title, 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="row">
            
            <!-- Danh sách -->
            <div class="col-md-8">
                <div class="card card-outline card-primary mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0 fw-bold">Các chiến dịch Flash Sale</h5>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50" class="text-center">#</th>
                                    <th>Tên chương trình</th>
                                    <th class="text-center">Thời gian</th>
                                    <th class="text-center">Số SP</th>
                                    <th class="text-center">Trạng thái</th>
                                    <th width="150" class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($campaigns->items())): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Không có chương trình Flash Sale nào.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $i = 1; foreach($campaigns as $item): 
                                        $now = time();
                                        $start = strtotime($item->start_time);
                                        $end = strtotime($item->end_time);
                                        
                                        if ($now < $start) {
                                            $status = '<span class="badge bg-warning text-dark"><i class="fa-regular fa-clock"></i> Sắp tới</span>';
                                        } elseif ($now >= $start && $now <= $end) {
                                            $status = '<span class="badge bg-danger"><i class="fa-solid fa-bolt"></i> Đang chạy</span>';
                                        } else {
                                            $status = '<span class="badge bg-secondary"><i class="fa-solid fa-check-double"></i> Đã kết thúc</span>';
                                        }
                                    ?>
                                    <tr id="row-<?= $item->id ?>">
                                        <td class="text-center"><?= $i++ ?></td>
                                        <td>
                                            <a href="<?= route('admin.flash_sale.products', ['id' => $item->id]) ?>" class="fw-bold text-primary text-decoration-none">
                                                <?= e($item->name) ?>
                                            </a>
                                        </td>
                                        <td class="text-center small">
                                            <span class="text-success"><?= date('H:i d/m', $start) ?></span><br>
                                            <span class="text-danger"><?= date('H:i d/m', $end) ?></span>
                                        </td>
                                        <td class="text-center fw-bold">
                                            <?= $item->product_count ?>
                                        </td>
                                        <td class="text-center"><?= $status ?></td>
                                        <td class="text-center">
                                            <a href="<?= route('admin.flash_sale.products', ['id' => $item->id]) ?>" class="btn btn-sm btn-outline-primary" title="Quản lý SP">
                                                <i class="fa-solid fa-box-open"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-info btn-edit" 
                                                data-id="<?= $item->id ?>"
                                                data-name="<?= e($item->name) ?>"
                                                data-start="<?= date('Y-m-d\TH:i', $start) ?>"
                                                data-end="<?= date('Y-m-d\TH:i', $end) ?>"
                                                title="Sửa thời gian">
                                                <i class="fa-solid fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="<?= $item->id ?>" title="Xóa chiến dịch">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($campaigns->lastPage() > 1): ?>
                    <div class="card-footer bg-white">
                        <?= $campaigns->links() ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Thêm mới / Cập nhật -->
            <div class="col-md-4">
                <div class="card card-outline card-success mb-4 sticky-top" style="top: 70px; z-index: 10;">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0 fw-bold" id="formTitle"><i class="fa-solid fa-plus text-success"></i> Tạo chiến dịch mới</h5>
                    </div>
                    <form action="<?= route('admin.flash_sale.store_campaign') ?>" method="POST" id="campaignForm">
                        <input type="hidden" name="id" id="campaign_id" value="">
                        <div class="card-body">
                            
                            <?= view('admin.components.input', [
                                'name' => 'name',
                                'label' => 'Tên chương trình',
                                'value' => '',
                                'attrs' => ['required' => true, 'placeholder' => 'VD: Siêu Sale 8/3']
                            ]) ?>
                            
                            <?= view('admin.components.datetime', [
                                'name' => 'start_time',
                                'label' => 'Thời gian Bắt đầu',
                                'value' => date('Y-m-d H:i:s'),
                                'attrs' => ['required' => true]
                            ]) ?>
                            
                            <?= view('admin.components.datetime', [
                                'name' => 'end_time',
                                'label' => 'Thời gian Kết thúc',
                                'value' => date('Y-m-d H:i:s', strtotime('+1 days')),
                                'attrs' => ['required' => true]
                            ]) ?>
                            
                            <div class="alert alert-info py-2 small mb-0">
                                <i class="fa-solid fa-circle-info"></i> Sau khi tạo, bạn có thể click vào nút <strong><i class="fa-solid fa-box-open"></i> Quản lý SP</strong> để thêm sản phẩm.
                            </div>
                        </div>
                        
                        <div class="card-footer bg-light text-end">
                            <button type="button" class="btn btn-secondary btn-sm me-1 d-none" id="btnCancelEdit">Hủy</button>
                            <button type="submit" class="btn btn-success btn-sm fw-bold"><i class="fa-solid fa-save"></i> LƯU CHIẾN DỊCH</button>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    
    $('.btn-edit').click(function() {
        let btn = $(this);
        $('#campaign_id').val(btn.data('id'));
        $('input[name="name"]').val(btn.data('name'));
        $('input[name="start_time"]').val(btn.data('start'));
        $('input[name="end_time"]').val(btn.data('end'));
        
        $('#formTitle').html('<i class="fa-solid fa-edit text-info"></i> Cập nhật chiến dịch');
        $('#btnCancelEdit').removeClass('d-none');
    });
    
    $('#btnCancelEdit').click(function() {
        $('#campaign_id').val('');
        $('#campaignForm')[0].reset();
        $('#formTitle').html('<i class="fa-solid fa-plus text-success"></i> Tạo chiến dịch mới');
        $(this).addClass('d-none');
    });

    $('.btn-delete').click(function() {
        let id = $(this).data('id');
        let row = $('#row-' + id);
        
        Swal.fire({
            title: 'Xóa chiến dịch này?',
            text: "Toàn bộ sản phẩm trong chiến dịch sẽ bị gỡ bỏ Flash Sale!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Đồng ý Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= route('admin.flash_sale.destroy_campaign') ?>',
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
