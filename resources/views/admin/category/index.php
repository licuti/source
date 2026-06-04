<div class="app-content-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-4">
                <h3 class="mb-0">Quản lý Danh mục</h3>
            </div>
            <div class="col-sm-8 text-end">
                <ol class="breadcrumb float-sm-end mb-0 me-3 d-inline-flex align-items-center">
                    <li class="breadcrumb-item"><a href="<?= route('admin.dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Danh mục</li>
                </ol>
                <a href="<?= route('admin.category.create') ?>" class="btn btn-primary d-inline-block"><i class="fa-solid fa-plus"></i> Thêm mới</a>
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
                                <th style="width: 120px;" class="text-center">Hình ảnh</th>
                                <th>Tên danh mục (VI)</th>
                                <th>Danh mục cha</th>
                                <th class="text-center">Sắp xếp</th>
                                <th class="text-center">Trạng thái</th>
                                <th style="width: 120px;" class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($categories as $item): ?>
                            <tr>
                                <td class="text-center fw-bold text-muted"><?= $item->id_code ?></td>
                                <td class="text-center">
                                    <?php if($item->hinh_anh): ?>
                                        <img src="/img_data/images/<?= $item->hinh_anh ?>" alt="Image" class="img-thumbnail" style="height: 45px; width: auto; object-fit: cover;">
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border">Trống</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong class="text-primary"><?= htmlspecialchars($item->ten) ?></strong></td>
                                <td>
                                    <?php if($item->id_loai > 0): ?>
                                        <?= isset($parentMap[$item->id_loai]) ? htmlspecialchars($parentMap[$item->id_loai]) : 'ID: ' . $item->id_loai ?>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="fa-solid fa-folder-tree"></i> Gốc</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= $item->so_thu_tu ?></td>
                                <td class="text-center">
                                    <?php if($item->hien_thi): ?>
                                        <span class="badge bg-success bg-opacity-75"><i class="fa-solid fa-check"></i> Hiển thị</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="fa-solid fa-eye-slash"></i> Đã ẩn</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= route('admin.category.edit', ['id' => $item->id_code]) ?>" class="btn btn-sm btn-outline-info" title="Chỉnh sửa"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <a href="<?= route('admin.category.destroy', ['id' => $item->id_code]) ?>" class="btn btn-sm btn-outline-danger" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục này cùng toàn bộ danh mục con (nếu có)?')"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($categories)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-folder-open fs-1 mb-2"></i><br>
                                    Chưa có danh mục nào được tạo.
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
