<?php $this->layout('admin/layouts/main', ['title' => 'Chế độ bảo trì']); ?>

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0">Chế độ bảo trì</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="<?= route('admin.dashboard') ?>">Bảng điều khiển</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Chế độ bảo trì
                    </li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Thiết lập Chế độ bảo trì</h3>
                    </div>
                    <form action="<?= route('admin.maintenance.save') ?>" method="POST">
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fa-solid fa-info-circle"></i> Giao diện cấu hình đang được xây dựng...
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
