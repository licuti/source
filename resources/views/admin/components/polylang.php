<?php
/**
 * Component Widget Polylang
 * Hiển thị card quản lý các ngôn ngữ (Bản hiện tại và các bản dịch).
 * 
 * Các tham số cần truyền vào:
 * @param string $module_route Tên base route của module (VD: 'admin.gallery', 'admin.post')
 * @param array $langs Mảng các ngôn ngữ được hỗ trợ (Từ config)
 * @param string $currentLangCode Mã ngôn ngữ của bản ghi hiện tại
 * @param string $currentLangName Tên ngôn ngữ của bản ghi hiện tại
 * @param array $item Dữ liệu của bản ghi hiện tại
 * @param array $translations Mảng map giữa lang code và id bản dịch (VD: ['vi' => 1, 'en' => 2])
 */

$module_route = $module_route ?? '';
$langs = $langs ?? [];
$currentLangCode = $currentLangCode ?? 'vi';
$currentLangName = $currentLangName ?? 'Tiếng Việt';
$item = $item ?? [];
$translations = $translations ?? [];
?>

<div class="card card-outline card-primary shadow-sm mb-4">
    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
        <h6 class="card-title mb-0 fw-bold text-uppercase fs-6">
            <i class="fa-solid fa-earth-americas me-1"></i> Ngôn ngữ
        </h6>
    </div>
    <div class="card-body">
        <?php
        $currentLangItem = $langs[$currentLangCode] ?? null;
        $currentLangImage = $currentLangItem && !empty($currentLangItem['image']) ? getImageUrl($currentLangItem['image']) : '';
        ?>
        <div class="d-flex align-items-center justify-content-between p-2 rounded bg-light border mb-3">
            <span class="fw-medium text-dark">Bản hiện tại:</span>
            <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm d-flex align-items-center gap-1">
                <?php if ($currentLangImage): ?>
                    <img src="<?= $currentLangImage ?>" alt="<?= $currentLangCode ?>" style="width:16px; height:auto; border-radius:2px;" onerror="this.style.display='none';"> 
                <?php endif; ?>
                <?= htmlspecialchars($currentLangName) ?>
            </span>
        </div>
        
        <?php if (isset($item['id_code']) && $item['id_code']): ?>
            <h6 class="fs-6 fw-bold mb-3"><i class="fa-solid fa-language text-secondary me-1"></i> Các bản dịch khác:</h6>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($langs as $l): ?>
                    <?php if ($l['code'] == $currentLangCode) continue; ?>
                    
                    <div class="d-flex align-items-center justify-content-between p-2 border rounded hover-bg-light transition-all">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle overflow-hidden border d-flex align-items-center justify-content-center bg-white shadow-sm" style="width: 28px; height: 28px;">
                                <?php if (!empty($l['image'])): ?>
                                    <img src="<?= getImageUrl($l['image']) ?>" alt="<?= $l['code'] ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <?php endif; ?>
                                <span class="text-uppercase fw-bold text-secondary" style="font-size: 10px; <?= !empty($l['image']) ? 'display: none;' : '' ?>"><?= $l['code'] ?></span>
                            </div>
                            <span class="fw-medium text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($l['name']) ?></span>
                        </div>
                        
                        <?php if (isset($translations[$l['code']])): ?>
                            <a href="<?= route($module_route . '.edit', ['id' => $translations[$l['code']]]) ?>" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" title="Sửa bản dịch này">
                                <i class="fa-solid fa-pencil me-1"></i> Sửa
                            </a>
                        <?php else: ?>
                            <a href="<?= route($module_route . '.create') . '?lang=' . $l['code'] . '&source_id=' . $item['id_code'] ?>" class="btn btn-sm btn-outline-success rounded-pill px-3" title="Thêm bản dịch mới">
                                <i class="fa-solid fa-plus me-1"></i> Thêm
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-light border text-center mb-0 p-3 rounded-3 shadow-sm text-muted">
                <i class="fa-solid fa-circle-info mb-2 fs-4 text-info"></i><br>
                <small>Bạn cần <strong>Lưu</strong> nội dung này trước khi có thể thêm các bản dịch ngôn ngữ khác.</small>
            </div>
        <?php endif; ?>
    </div>
</div>
