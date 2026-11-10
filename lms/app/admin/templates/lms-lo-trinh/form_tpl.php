<?php
/**
 * Admin LMS — Quản lý lộ trình: Form Thêm chuyên đề mới / Chỉnh sửa chuyên đề (§18).
 *
 * Đối chiếu chuẩn mockup:
 *  - Tai-lieu-phat-trien/admin/chuyên đề mới.png
 *  - Tai-lieu-phat-trien/admin/chuyên đề mới (2).png (ẩn Template báo cáo khi chọn Có test)
 *  - Tai-lieu-phat-trien/admin/chuyên đề mới (3).png (hiện Template báo cáo khi chọn Không test)
 *  - Tai-lieu-phat-trien/admin/chuyên đề mới (4).png (Danh mục hỗ trợ "+ Thêm loại chuyên đề mới")
 *
 * Đã chuẩn hoá 100% theo hệ thống LMS UI/Form Components:
 *  - form/input, form/select, form/textarea, form/rich_editor, form/actions_bar
 *
 * PHP 7.3 compatible.
 */

$formAction = $isEditMode ? 'sua' : 'them';
$formUrl = $isEditMode
    ? lms_url($alias, $formAction, array('id' => $formId))
    : lms_url($alias, $formAction);
$cancelUrl = lms_url($alias);

$subjectOptions = array();
if (!empty($subjects) && is_array($subjects)) {
    foreach ($subjects as $sub) {
        $subjectOptions[(string) $sub['id']] = (string) $sub['name'];
    }
}
$subjectOptions['__new__'] = '+ Thêm loại chuyên đề mới';

$postArr = isset($_POST) && is_array($_POST) ? $_POST : array();
$rowArr  = isset($row) && is_array($row) ? $row : array();

$selectedSubject = function_exists('lms_form_old')
    ? (string) lms_form_old($postArr, $rowArr, 'subject_id', '')
    : (isset($rowArr['subject_id']) ? (string) $rowArr['subject_id'] : '');

$selectedHasTest = function_exists('lms_form_old')
    ? (string) lms_form_old($postArr, $rowArr, 'has_test', '0')
    : (isset($rowArr['has_test']) ? (string) (int) $rowArr['has_test'] : '0');

$reportTemplateVal = function_exists('lms_form_old')
    ? (string) lms_form_old($postArr, $rowArr, 'report_template', '')
    : (isset($rowArr['report_template']) ? (string) $rowArr['report_template'] : '');
?>

<form method="post" action="<?= e($formUrl) ?>" id="lmsTopicForm" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">

    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8 col-xl-7">

            <!-- 1. Tên chuyên đề * -->
            <?php lms_component('form/input', array(
                'name'        => 'name',
                'label'       => 'Tên chuyên đề',
                'placeholder' => 'VD: Unit 1 - Flower, How to calculate',
                'required'    => true,
                'row'         => $rowArr,
                'errors'      => $errors,
            )); ?>

            <!-- 2. Danh mục * (hỗ trợ "+ Thêm loại chuyên đề mới" theo chuyên đề mới (4).png) -->
            <?php lms_component('form/select', array(
                'name'        => 'subject_id',
                'id'          => 'fld-subject-id',
                'label'       => 'Danh mục',
                'options'     => $subjectOptions,
                'empty_label' => '— Chọn danh mục —',
                'value'       => $selectedSubject,
                'required'    => true,
                'row'         => $rowArr,
                'errors'      => $errors,
            )); ?>

            <!-- Ô nhập tên danh mục mới (ẩn/hiện tự động khi chọn "+ Thêm loại chuyên đề mới") -->
            <div id="newSubjectRowWrap" style="<?= $selectedSubject === '__new__' ? '' : 'display: none;' ?>">
                <?php lms_component('form/input', array(
                    'name'        => 'new_subject_name',
                    'id'          => 'fld-new-subject-name',
                    'label'       => 'Loại chuyên đề mới',
                    'placeholder' => 'Nhập tên danh mục mới (VD: Math Problems, Reading...)',
                    'required'    => $selectedSubject === '__new__',
                    'row'         => $rowArr,
                    'errors'      => $errors,
                )); ?>
            </div>

            <!-- 3. Loại chuyên đề * (Có test / Không test theo chuyên đề mới.png) -->
            <?php lms_component('form/select', array(
                'name'     => 'has_test',
                'id'       => 'fld-has-test',
                'label'    => 'Loại chuyên đề',
                'options'  => array(
                    '0' => 'Không test',
                    '1' => 'Có test',
                ),
                'value'    => $selectedHasTest,
                'required' => true,
                'hint'     => 'Có test: áp dụng test và minitest | Không test: nhập báo cáo chung.',
                'row'      => $rowArr,
                'errors'   => $errors,
            )); ?>

            <!-- Nhóm tải đề thi: Bài Test chuyên đề & Bài Minitest chuyên đề (Hiện khi Loại chuyên đề = Có test) -->
            <div id="testFilesRowWrap" style="<?= $selectedHasTest === '1' ? '' : 'display: none;' ?>">
                <?php lms_component('form/file_picker', array(
                    'name'        => 'test_file',
                    'id'          => 'fld-test-file',
                    'label'       => 'Bài Test chuyên đề',
                    'button_text' => 'Chọn file đề bài',
                    'accept'      => '.pdf,.doc,.docx,.xls,.xlsx',
                    'value'       => isset($rowArr['test_file_url']) ? (string) $rowArr['test_file_url'] : '',
                    'delete_name' => 'delete_test_file',
                    'hint'        => 'Định dạng .pdf, .doc, .docx (tối đa 20MB). Học viên sẽ tải về làm bài khi mở test.',
                    'errors'      => $errors,
                )); ?>

                <?php lms_component('form/file_picker', array(
                    'name'        => 'minitest_file',
                    'id'          => 'fld-minitest-file',
                    'label'       => 'Bài Minitest chuyên đề',
                    'button_text' => 'Chọn file đề bài',
                    'accept'      => '.pdf,.doc,.docx,.xls,.xlsx',
                    'value'       => isset($rowArr['minitest_file_url']) ? (string) $rowArr['minitest_file_url'] : '',
                    'delete_name' => 'delete_minitest_file',
                    'hint'        => 'Định dạng .pdf, .doc, .docx (tối đa 20MB). Đề bài kiểm tra củng cố kiến thức.',
                    'errors'      => $errors,
                )); ?>
            </div>

            <!-- 4. Nội dung -->
            <?php lms_component('form/textarea', array(
                'name'        => 'content',
                'label'       => 'Nội dung',
                'rows'        => 3,
                'placeholder' => 'Nhập nội dung tóm tắt chuyên đề...',
                'row'         => $rowArr,
                'errors'      => $errors,
            )); ?>

            <!-- 5. Số buổi học tối đa * -->
            <?php lms_component('form/input', array(
                'name'        => 'max_sessions',
                'label'       => 'Số buổi học tối đa',
                'type'        => 'number',
                'min'         => 1,
                'max'         => 500,
                'placeholder' => 'VD: 3',
                'required'    => true,
                'row'         => $rowArr,
                'errors'      => $errors,
            )); ?>

            <!-- 6. Template báo cáo (Hiện khi Không test theo chuyên đề mới (3).png, Ẩn khi Có test theo chuyên đề mới (2).png) -->
            <div id="reportTemplateRowWrap" style="<?= $selectedHasTest === '1' ? 'display: none;' : '' ?>">
                <?php lms_component('form/rich_editor', array(
                    'name'        => 'report_template',
                    'id'          => 'fld-report-template',
                    'label'       => 'Template báo cáo',
                    'placeholder' => 'Nhập mẫu nhận xét / báo cáo...',
                    'min_height'  => 150,
                    'value'       => $reportTemplateVal,
                    'row'         => $rowArr,
                    'errors'      => $errors,
                )); ?>
            </div>

            <!-- 7. Nút Thêm / Lưu chuẩn form/actions_bar -->
            <?php lms_component('form/actions_bar', array(
                'align'       => 'center',
                'label'       => $isEditMode ? 'Lưu' : 'Thêm',
                'variant'     => 'secondary',
                'size'        => 'md',
                'cancel_url'  => $cancelUrl,
            )); ?>

        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var subjectSel = document.getElementById('fld-subject-id');
    var newSubjectWrap = document.getElementById('newSubjectRowWrap');
    var newSubjectInput = document.getElementById('fld-new-subject-name');

    if (subjectSel && newSubjectWrap) {
        var toggleNewSubject = function () {
            var isNew = (subjectSel.value === '__new__');
            newSubjectWrap.style.display = isNew ? '' : 'none';
            if (newSubjectInput) {
                newSubjectInput.required = isNew;
                if (isNew) {
                    newSubjectInput.focus();
                }
            }
        };
        subjectSel.addEventListener('change', toggleNewSubject);
        if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
            jQuery(subjectSel).on('change.select2', toggleNewSubject);
        }
    }

    var hasTestSel = document.getElementById('fld-has-test');
    var tplWrap = document.getElementById('reportTemplateRowWrap');
    var testFilesWrap = document.getElementById('testFilesRowWrap');
    if (hasTestSel) {
        var toggleTestSections = function () {
            var isTest = (hasTestSel.value === '1');
            if (tplWrap) {
                tplWrap.style.display = isTest ? 'none' : '';
            }
            if (testFilesWrap) {
                testFilesWrap.style.display = isTest ? '' : 'none';
            }
        };
        hasTestSel.addEventListener('change', toggleTestSections);
        if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
            jQuery(hasTestSel).on('change.select2', toggleTestSections);
        }
    }
});
</script>
