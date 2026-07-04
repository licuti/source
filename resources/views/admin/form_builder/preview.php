<?php
$title = 'Xem trước Form: ' . $form->name;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <style>
        body { background-color: #f8f9fa; padding: 40px 0; }
        .preview-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .form-label.required::after {
            content: ' *';
            color: #dc3545;
        }
        .input-group-text {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="preview-container">
        <h2 class="mb-4 text-center"><?= htmlspecialchars($form->name) ?></h2>
        
        <?php if (empty($fields)): ?>
            <div class="alert alert-warning text-center">Form này chưa có trường dữ liệu nào.</div>
        <?php else: ?>
            <form id="previewForm" action="#" method="POST" onsubmit="event.preventDefault(); alert('Đây chỉ là bản xem trước!');">
                <div class="row">
                    <?php 
                    $logicRules = [];
                    foreach ($fields as $field): 
                        $adv = !empty($field->advanced_settings) ? json_decode($field->advanced_settings, true) : [];
                        
                        // Collect Logic Rules for JS
                        if (!empty($adv['logic_enable']) && !empty($adv['logic_field'])) {
                            $logicRules[] = [
                                'target' => $field->name,
                                'trigger' => $adv['logic_field'],
                                'value' => $adv['logic_value'] ?? ''
                            ];
                        }
                        
                        // Extracted attributes
                        $cssClass = htmlspecialchars($adv['css_class'] ?? '');
                        $icon = htmlspecialchars($adv['icon'] ?? '');
                        $defaultVal = htmlspecialchars($adv['default_value'] ?? '');
                        $helpText = htmlspecialchars($adv['help_text'] ?? '');
                        $readonly = !empty($adv['readonly']) ? 'readonly tabindex="-1"' : '';
                        $layout = $adv['layout'] ?? 'stacked'; // stacked or inline
                        
                        // Validation attributes
                        $minL = !empty($adv['min_length']) ? 'minlength="'.(int)$adv['min_length'].'"' : '';
                        $maxL = !empty($adv['max_length']) ? 'maxlength="'.(int)$adv['max_length'].'"' : '';
                        $regex = !empty($adv['regex']) ? 'pattern="'.htmlspecialchars($adv['regex']).'"' : '';
                        $accept = !empty($adv['allowed_ext']) ? 'accept="'.htmlspecialchars($adv['allowed_ext']).'"' : '';
                        $maxSize = !empty($adv['max_size']) ? (int)$adv['max_size'] : 0;
                        
                        $required = $field->is_required ? 'required' : '';
                        $placeholder = htmlspecialchars($field->placeholder ?? '');
                    ?>
                        <div class="<?= htmlspecialchars($field->col_width ?: 'col-md-12') ?> mb-3 field-wrapper-<?= $field->name ?>">
                            <label class="form-label fw-bold <?= $field->is_required ? 'required' : '' ?>">
                                <?= htmlspecialchars($field->label) ?>
                            </label>
                            
                            <?php
                            $options = [];
                            if (!empty($field->options)) {
                                $parsed = json_decode($field->options, true);
                                if (is_array($parsed)) $options = $parsed;
                            }
                            ?>
                            
                            <?php if ($field->type === 'textarea'): ?>
                                <textarea class="form-control <?= $cssClass ?>" name="<?= $field->name ?>" 
                                          placeholder="<?= $placeholder ?>" rows="3" 
                                          <?= $required ?> <?= $readonly ?> <?= $minL ?> <?= $maxL ?> <?= $regex ?>><?= $defaultVal ?></textarea>
                                          
                            <?php elseif ($field->type === 'select'): ?>
                                <select class="form-select <?= $cssClass ?>" name="<?= $field->name ?>" <?= $required ?> <?= $readonly ?>>
                                    <option value="">-- Chọn --</option>
                                    <?php foreach ($options as $opt): ?>
                                        <option value="<?= htmlspecialchars($opt) ?>" <?= $defaultVal === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                
                            <?php elseif ($field->type === 'radio'): ?>
                                <div class="<?= $cssClass ?>">
                                    <?php foreach ($options as $i => $opt): ?>
                                        <div class="form-check <?= $layout === 'inline' ? 'form-check-inline' : '' ?>">
                                            <input class="form-check-input" type="radio" name="<?= $field->name ?>" 
                                                   id="<?= $field->name . '_' . $i ?>" value="<?= htmlspecialchars($opt) ?>" 
                                                   <?= $required ?> <?= $readonly ?> <?= $defaultVal === $opt ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="<?= $field->name . '_' . $i ?>">
                                                <?= htmlspecialchars($opt) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                            <?php elseif ($field->type === 'checkbox'): ?>
                                <div class="<?= $cssClass ?>">
                                    <?php foreach ($options as $i => $opt): ?>
                                        <div class="form-check <?= $layout === 'inline' ? 'form-check-inline' : '' ?>">
                                            <input class="form-check-input" type="checkbox" name="<?= $field->name ?>[]" 
                                                   id="<?= $field->name . '_' . $i ?>" value="<?= htmlspecialchars($opt) ?>"
                                                   <?= $readonly ?>>
                                            <label class="form-check-label" for="<?= $field->name . '_' . $i ?>">
                                                <?= htmlspecialchars($opt) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                            <?php else: ?>
                                <!-- text, email, tel, file -->
                                <?php if ($icon): ?>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="<?= $icon ?>"></i></span>
                                <?php endif; ?>
                                
                                <input type="<?= $field->type === 'file' ? 'file' : htmlspecialchars($field->type) ?>" 
                                       class="form-control <?= $cssClass ?>" name="<?= $field->name ?>" 
                                       placeholder="<?= $placeholder ?>" value="<?= $defaultVal ?>"
                                       <?= $required ?> <?= $readonly ?> <?= $minL ?> <?= $maxL ?> <?= $regex ?> <?= $accept ?>
                                       <?= $field->type === 'file' && $maxSize > 0 ? 'data-max-size="'.$maxSize.'"' : '' ?>>
                                       
                                <?php if ($icon): ?>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($helpText): ?>
                                <div class="form-text text-muted small"><i class="fa-solid fa-circle-info me-1"></i><?= $helpText ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-5 btn-lg shadow-sm">
                        <i class="fa-solid fa-paper-plane me-2"></i> Gửi Thông Tin
                    </button>
                </div>
            </form>
        <?php endif; ?>
        
    </div>
</div>

<script>
$(document).ready(function() {
    const rules = <?= json_encode($logicRules) ?>;
    
    // File size validation logic
    $('input[type="file"][data-max-size]').on('change', function() {
        const maxSizeMB = parseInt($(this).data('max-size'));
        if (maxSizeMB > 0 && this.files[0]) {
            const fileMB = this.files[0].size / 1024 / 1024;
            if (fileMB > maxSizeMB) {
                alert('File tải lên quá lớn. Kích thước tối đa cho phép là ' + maxSizeMB + 'MB.');
                $(this).val('');
            }
        }
    });

    // Conditional Logic Engine
    function evaluateLogic() {
        rules.forEach(rule => {
            // Find trigger field value
            let triggerVal = '';
            let $triggerField = $('[name="' + rule.trigger + '"], [name="' + rule.trigger + '[]"]');
            
            if ($triggerField.length === 0) return;
            
            const type = $triggerField.prop('type') || '';
            if (type === 'radio' || type === 'checkbox') {
                triggerVal = $('[name="' + rule.trigger + '"]:checked').val() || '';
            } else {
                triggerVal = $triggerField.val() || '';
            }
            
            const $targetWrapper = $('.field-wrapper-' + rule.target);
            if (triggerVal === rule.value) {
                $targetWrapper.slideDown();
                $targetWrapper.find('input, select, textarea').prop('disabled', false);
            } else {
                $targetWrapper.slideUp();
                $targetWrapper.find('input, select, textarea').prop('disabled', true);
            }
        });
    }

    if (rules.length > 0) {
        // Evaluate on load
        evaluateLogic();
        
        // Listen to changes on trigger fields
        let triggers = [...new Set(rules.map(r => r.trigger))];
        triggers.forEach(t => {
            $('[name="' + t + '"], [name="' + t + '[]"]').on('change input', evaluateLogic);
        });
    }
});
</script>

</body>
</html>
