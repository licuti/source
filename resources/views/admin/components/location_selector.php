<?php
/**
 * Component Location Selector
 * @param array|object $item Dữ liệu bản ghi hiện tại chứa country_id, province_id...
 * @param iterable $countries
 * @param iterable $provinces
 * @param iterable $districts
 * @param iterable $wards
 * @param string $layout Cấu hình class chia cột, mặc định 'col-md-3'
 */
$layoutClass = $layout ?? 'col-md-3 mb-3';

// Helper function to safely get item property whether it's an object or array
$getVal = function($key) use ($item) {
    if (is_array($item)) return $item[$key] ?? 0;
    if (is_object($item)) return $item->{$key} ?? 0;
    return 0;
};

$countryId = $getVal('country_id');
$provinceId = $getVal('province_id');
$districtId = $getVal('district_id');
$wardId = $getVal('ward_id');

// Đảm bảo script chỉ được load 1 lần
$GLOBALS['location_selector_loaded'] = $GLOBALS['location_selector_loaded'] ?? false;
?>

<div class="row location-selector-wrapper">
    <div class="<?= $layoutClass ?> loc-col">
        <label class="form-label fw-bold">Quốc gia <span class="text-danger">*</span></label>
        <select name="country_id" class="form-select form-select-sm select2 loc-country">
            <option value="0">-- Chọn quốc gia --</option>
            <?php foreach ($countries as $country): ?>
                <option value="<?= $country->id ?>" data-code="<?= $country->code ?>" <?= $countryId == $country->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($country->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="<?= $layoutClass ?> loc-col" style="<?= (empty($provinces) && empty($provinceId)) ? 'display: none;' : '' ?>">
        <label class="form-label fw-bold">Tỉnh / Bang</label>
        <select name="province_id" class="form-select form-select-sm select2 loc-province">
            <option value="0">Tất cả tỉnh thành</option>
            <?php if (!empty($provinces)): ?>
                <?php foreach ($provinces as $prov): ?>
                    <option value="<?= $prov->id ?>" <?= $provinceId == $prov->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($prov->name) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
    
    <div class="<?= $layoutClass ?> loc-col" style="<?= (empty($districts) && empty($districtId)) ? 'display: none;' : '' ?>">
        <label class="form-label fw-bold">Quận / Huyện</label>
        <select name="district_id" class="form-select form-select-sm select2 loc-district">
            <option value="0">Tất cả quận huyện</option>
            <?php if (!empty($districts)): ?>
                <?php foreach ($districts as $dist): ?>
                    <option value="<?= $dist->id ?>" <?= $districtId == $dist->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dist->name) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
    
    <div class="<?= $layoutClass ?> loc-col" style="<?= (empty($wards) && empty($wardId)) ? 'display: none;' : '' ?>">
        <label class="form-label fw-bold">Phường / Xã</label>
        <select name="ward_id" class="form-select form-select-sm select2 loc-ward">
            <option value="0">Tất cả phường xã</option>
            <?php if (!empty($wards)): ?>
                <?php foreach ($wards as $ward): ?>
                    <option value="<?= $ward->id ?>" <?= $wardId == $ward->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ward->name) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
</div>
<input type="hidden" class="loc-csrf" value="<?= function_exists('csrf_token') ? csrf_token() : '' ?>">

<?php if (!$GLOBALS['location_selector_loaded']): ?>
    <script src="<?= asset('admin/js/location-selector.js') ?>"></script>
    <?php $GLOBALS['location_selector_loaded'] = true; ?>
<?php endif; ?>
