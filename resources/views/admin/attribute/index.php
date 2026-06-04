<div class="app-content-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-4">
                <h3 class="mb-0">Quản lý Thuộc tính</h3>
            </div>
            <div class="col-sm-8 text-end">
                <ol class="breadcrumb float-sm-end mb-0 me-3 d-inline-flex align-items-center">
                    <li class="breadcrumb-item"><a href="<?= route('admin.dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Thuộc tính</li>
                </ol>
                <a href="<?= route('admin.attribute.create') ?>" class="btn btn-primary d-inline-block"><i class="fa-solid fa-plus"></i> Thêm mới</a>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;" class="text-center">ID</th>
                                <th>Tên thuộc tính (VI)</th>
                                <th>Kiểu hiển thị (Loại)</th>
                                <th>Giá trị (Values)</th>
                                <th class="text-center">Sắp xếp</th>
                                <th style="width: 120px;" class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($attributes as $item): ?>
                            <tr>
                                <td class="text-center fw-bold text-muted"><?= $item->id_code ?></td>
                                <td><strong class="text-primary"><?= htmlspecialchars($item->ten) ?></strong></td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars(strtoupper($item->loai)) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark me-2"><?= $item->value_count ?> giá trị</span>
                                    <small class="text-muted"><?= htmlspecialchars($item->values_preview) ?><?= $item->value_count > 5 ? '...' : '' ?></small>
                                </td>
                                <td class="text-center"><?= $item->sap_xep ?></td>
                                <td class="text-center">
                                    <a href="<?= route('admin.attribute.edit', ['id' => $item->id_code]) ?>" class="btn btn-sm btn-outline-info" title="Chỉnh sửa"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <a href="<?= route('admin.attribute.destroy', ['id' => $item->id_code]) ?>" class="btn btn-sm btn-outline-danger" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa Thuộc tính này cùng TOÀN BỘ GIÁ TRỊ của nó không?')"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($attributes)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-tags fs-1 mb-2"></i><br>
                                    Chưa có thuộc tính nào được tạo.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
