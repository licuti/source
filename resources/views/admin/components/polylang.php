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
        
        <?php if (!empty($item['id'])): ?>
            <div class="mt-4 border-top pt-3">
                <h6 class="fw-bold mb-3 fs-6">Các bản dịch khác</h6>
                <ul class="list-group list-group-flush mb-0">
                    <?php foreach ($langs as $l): ?>
                        <?php 
                        if ($l['code'] === $currentLangCode) continue; 
                        
                        $hasTrans = isset($translations[$l['code']]);
                        $flagSrc = !empty($l['image']) ? getImageUrl($l['image']) : '';
                        ?>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center bg-transparent border-bottom border-light">
                            <div class="d-flex align-items-center">
                                <?php if($flagSrc): ?>
                                    <img src="<?= $flagSrc ?>" alt="<?= $l['code'] ?>" style="width: 20px; height: 14px; object-fit: cover; border-radius: 2px;" class="border shadow-sm me-2">
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border me-2"><?= strtoupper($l['code']) ?></span>
                                <?php endif; ?>
                                <span class="<?= $hasTrans ? 'text-dark' : 'text-muted' ?>"><?= htmlspecialchars($l['name']) ?></span>
                            </div>
                            
                            <?php if ($hasTrans): ?>
                                <a href="<?= route($module_route . '.edit', ['id' => $item['id']]) ?>?lang=<?= $l['code'] ?>" class="btn btn-sm btn-light border" title="Chỉnh sửa bản dịch">
                                    <i class="fa-solid fa-pen text-primary" style="font-size: 0.75rem;"></i>
                                </a>
                            <?php else: ?>
                                <a href="<?= route($module_route . '.create') . '?lang=' . $l['code'] . '&source_id=' . $item['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill px-3" title="Thêm bản dịch mới">
                                    <i class="fa-solid fa-plus me-1"></i> Thêm
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="alert alert-light border text-center mb-0 p-3 rounded-3 shadow-sm text-muted">
                <i class="fa-solid fa-circle-info mb-2 fs-4 text-info"></i><br>
                <small>Bạn cần <strong>Lưu</strong> nội dung này trước khi có thể thêm các bản dịch ngôn ngữ khác.</small>
            </div>
        <?php endif; ?>
    </div>
</div>
