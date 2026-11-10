<?php
/**
 * LMS Form Component: form/multi_select_tags
 * Hộp chọn nhiều mục dạng thẻ tag, có ô tìm kiếm lọc nhanh, phân nhóm linh hoạt.
 *
 * Props:
 * @var string $name        Tên field gửi lên server (VD: 'teachable_districts[]')
 * @var string $label
 * @var array  $selected    Mảng các giá trị đang được chọn
 * @var array  $groups      Mảng phân nhóm: ['Tên Nhóm' => ['Item 1', 'Item 2', ...]]
 * @var array  $options     Mảng value => label (dùng khi item có mã riêng, VD: môn học)
 * @var string $placeholder
 * @var string $hint
 * @var bool   $bare      Chỉ render control, không bọc field_row
 * @var array  $errors
 */

$name        = isset($name) ? (string) $name : 'items[]';
$label       = isset($label) ? (string) $label : '';
$selected    = isset($selected) && is_array($selected) ? $selected : array();
$groups      = isset($groups) && is_array($groups) ? $groups : array();
$options     = isset($options) && is_array($options) ? $options : array();
$placeholder = isset($placeholder) ? (string) $placeholder : 'VD: Quận 1, Quận 2, ...';
$hint        = isset($hint) ? (string) $hint : '';
$baseName    = str_replace(array('[', ']', '_'), array('', '', '-'), $name);
$pickerId    = 'lms-picker-' . $baseName;

if ($options !== array() && $groups === array()) {
    $groups = array('' => $options);
}

$selectedStrings = array_map('strval', $selected);
$itemLabels = array();
foreach ($groups as $groupItems) {
    foreach ($groupItems as $itemKey => $item) {
        if (is_array($item) && isset($item['value'])) {
            $itemLabels[(string) $item['value']] = isset($item['label']) ? (string) $item['label'] : (string) $item['value'];
        } elseif ($options !== array()) {
            $itemLabels[(string) $itemKey] = (string) $item;
        } else {
            $itemLabels[(string) $item] = (string) $item;
        }
    }
}

ob_start();
?>
<div class="lms-district-picker" id="<?= htmlspecialchars($pickerId, ENT_QUOTES, 'UTF-8') ?>">
    <div class="lms-district-tags-box" id="<?= htmlspecialchars($pickerId, ENT_QUOTES, 'UTF-8') ?>-trigger" tabindex="0">
        <div class="lms-district-tags-inner" id="<?= htmlspecialchars($pickerId, ENT_QUOTES, 'UTF-8') ?>-inner">
            <?php if (empty($selected)): ?>
                <span class="lms-district-placeholder"><?= htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') ?></span>
            <?php else: ?>
                <?php foreach ($selected as $itemVal): ?>
                    <span class="lms-district-tag" data-val="<?= htmlspecialchars((string) $itemVal, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars(isset($itemLabels[(string) $itemVal]) ? $itemLabels[(string) $itemVal] : (string) $itemVal, ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="lms-district-tag-remove" aria-label="Xoá">&times;</button>
                    </span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="lms-district-dropdown" id="<?= htmlspecialchars($pickerId, ENT_QUOTES, 'UTF-8') ?>-dropdown">
        <div class="lms-district-search-wrap">
            <input type="text" class="lms-district-search-input" id="<?= htmlspecialchars($pickerId, ENT_QUOTES, 'UTF-8') ?>-search" placeholder="Tìm kiếm nhanh...">
        </div>
        <div class="lms-district-list">
            <?php foreach ($groups as $groupTitle => $items): ?>
                <?php if ($groupTitle !== ''): ?>
                    <div class="lms-district-group-title"><?= htmlspecialchars($groupTitle, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php foreach ($items as $itemKey => $item): ?>
                    <?php
                    if (is_array($item) && isset($item['value'])) {
                        $itemValue = (string) $item['value'];
                        $itemLabel = isset($item['label']) ? (string) $item['label'] : $itemValue;
                    } elseif ($options !== array()) {
                        $itemValue = (string) $itemKey;
                        $itemLabel = (string) $item;
                    } else {
                        $itemValue = (string) $item;
                        $itemLabel = $itemValue;
                    }
                    ?>
                    <label class="lms-district-item">
                        <input type="checkbox" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                               value="<?= htmlspecialchars($itemValue, ENT_QUOTES, 'UTF-8') ?>"
                               data-label="<?= htmlspecialchars($itemLabel, ENT_QUOTES, 'UTF-8') ?>"
                               <?= in_array($itemValue, $selectedStrings, true) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($itemLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
(function() {
    var pId = <?= json_encode($pickerId) ?>;
    var picker = document.getElementById(pId);
    if (!picker || picker.dataset.inited) return;
    picker.dataset.inited = '1';

    var trigger = document.getElementById(pId + '-trigger');
    var dropdown = document.getElementById(pId + '-dropdown');
    var tagsInner = document.getElementById(pId + '-inner');
    var searchInput = document.getElementById(pId + '-search');
    var placeholderText = <?= json_encode($placeholder) ?>;

    trigger.addEventListener('click', function(e) {
        if (e.target.closest('.lms-district-tag-remove')) return;
        dropdown.classList.toggle('show');
        if (dropdown.classList.contains('show') && searchInput) {
            searchInput.focus();
        }
    });

    document.addEventListener('click', function(e) {
        if (!picker.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var kw = this.value.toLowerCase().trim();
            var items = dropdown.querySelectorAll('.lms-district-item');
            items.forEach(function(item) {
                var txt = item.textContent.toLowerCase();
                item.style.display = (txt.indexOf(kw) !== -1) ? 'flex' : 'none';
            });
        });
    }

    var checkboxes = dropdown.querySelectorAll('input[type="checkbox"]');
    function syncTags() {
        var chosen = [];
        checkboxes.forEach(function(cb) {
            if (cb.checked) chosen.push({ value: cb.value, label: cb.getAttribute('data-label') || cb.value });
        });

        tagsInner.innerHTML = '';
        if (chosen.length === 0) {
            tagsInner.innerHTML = '<span class="lms-district-placeholder">' + placeholderText + '</span>';
        } else {
            chosen.forEach(function(item) {
                var tag = document.createElement('span');
                tag.className = 'lms-district-tag';
                tag.dataset.val = item.value;
                var label = document.createTextNode(item.label + ' ');
                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'lms-district-tag-remove';
                remove.setAttribute('aria-label', 'Xoá');
                remove.innerHTML = '&times;';
                tag.appendChild(label);
                tag.appendChild(remove);
                tagsInner.appendChild(tag);
            });
        }
    }

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', syncTags);
    });

    tagsInner.addEventListener('click', function(e) {
        var removeBtn = e.target.closest('.lms-district-tag-remove');
        if (removeBtn) {
            e.stopPropagation();
            var tag = removeBtn.closest('.lms-district-tag');
            if (tag) {
                var val = tag.dataset.val;
                var cb = dropdown.querySelector('input[type="checkbox"][value="' + CSS.escape(val) + '"]');
                if (cb) {
                    cb.checked = false;
                    syncTags();
                }
            }
        }
    });
})();
</script>
<?php
$controlHtml = ob_get_clean();

if (!empty($bare) || !empty($no_wrap) || !empty($no_row)) {
    echo $controlHtml;
    return;
}

lms_component('form/field_row', array(
    'label'    => $label,
    'name'     => str_replace('[]', '', $name),
    'required' => false,
    'control'  => $controlHtml,
    'hint'     => $hint,
    'errors'   => isset($errors) ? $errors : array(),
));
