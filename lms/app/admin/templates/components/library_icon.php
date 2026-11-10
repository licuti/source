<?php
/**
 * LMS Shared Component: library_icon
 * Hiển thị biểu tượng tài liệu thư viện chuẩn theo mockup:
 *  - Tai-lieu-phat-trien/admin/Quản lý thư viện.png
 *  - Tai-lieu-phat-trien/teacher/Thư viện.png
 *
 * Props:
 * @var string $file_type  link|pdf|doc|xls|internal|slide|exam|video|audio|document|other
 * @var string $title      Tên tài liệu (hỗ trợ nhận diện tiền tố [Sách], [Review], [Test], [Nội bộ])
 * @var string $file_url   Đường dẫn file hoặc URL
 *
 * PHP 7.3 compatible.
 */

$fileType = isset($file_type) ? (string) $file_type : 'other';
$rawTitle = isset($title) ? (string) $title : '';
$fileUrl  = isset($file_url) ? (string) $file_url : '';

$isInternal = ($fileType === 'internal' || strpos($rawTitle, '[Nội bộ]') === 0);
$isLink     = !$isInternal && ($fileType === 'link' || strpos($rawTitle, '[Sách]') === 0);
$isPdf      = !$isInternal && !$isLink && ($fileType === 'pdf' || strpos($rawTitle, '[Review]') === 0);
$isXls      = !$isInternal && !$isLink && !$isPdf && ($fileType === 'xls' || preg_match('/\.xlsx?$/i', $fileUrl));
$isDoc      = !$isInternal && !$isLink && !$isPdf && !$isXls && ($fileType === 'doc' || $fileType === 'exam' || strpos($rawTitle, '[Test]') === 0);
?>
<?php if ($isInternal): ?>
    <!-- [Nội bộ]: Thẻ trắng trơn không có icon (chuẩn mockup) -->
    <div class="w-100 h-100"></div>

<?php elseif ($isLink): ?>
    <!-- [Sách] / Link: Icon dây xích đen đan chéo góc 45 độ -->
    <svg width="88" height="88" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
    </svg>

<?php elseif ($isPdf): ?>
    <!-- [Review] / PDF: Icon trang giấy gấp góc nét đen, 3 gạch ngang và băng chữ nhật PDF ở giữa -->
    <svg width="86" height="98" viewBox="0 0 76 88" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M12 4H48L64 20V38H12V4Z" fill="#FFFFFF" stroke="#000000" stroke-width="3" stroke-linejoin="round"/>
        <path d="M48 4V20H64" stroke="#000000" stroke-width="3" stroke-linejoin="round"/>
        <line x1="21" y1="15" x2="40" y2="15" stroke="#000000" stroke-width="3" stroke-linecap="round"/>
        <line x1="21" y1="23" x2="54" y2="23" stroke="#000000" stroke-width="3" stroke-linecap="round"/>
        <line x1="21" y1="31" x2="54" y2="31" stroke="#000000" stroke-width="3" stroke-linecap="round"/>
        <rect x="4" y="38" width="68" height="30" fill="#FFFFFF" stroke="#000000" stroke-width="3"/>
        <text x="38" y="60" font-family="Arial, sans-serif" font-weight="800" font-size="20" fill="#000000" text-anchor="middle" letter-spacing="1">PDF</text>
        <path d="M12 68V82H64V68" fill="#FFFFFF" stroke="#000000" stroke-width="3" stroke-linejoin="round"/>
    </svg>

<?php elseif ($isDoc): ?>
    <!-- [Test] / DOC: Icon trang giấy màu xanh dương, góc gấp xanh nhạt, chữ DOC trắng -->
    <svg width="78" height="94" viewBox="0 0 68 84" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M6 6C6 3.79086 7.79086 2 10 2H44L62 20V78C62 80.2091 60.2091 82 58 82H10C7.79086 82 6 80.2091 6 78V6Z" fill="#0b66e4"/>
        <path d="M44 2L62 20H46C44.8954 20 44 19.1046 44 18V2Z" fill="#7fb5ff"/>
        <text x="34" y="53" font-family="Arial, sans-serif" font-weight="800" font-size="18" fill="#FFFFFF" text-anchor="middle" letter-spacing="0.5">DOC</text>
    </svg>

<?php elseif ($isXls): ?>
    <!-- [Test] / XLS: Icon trang giấy xanh lá, nhãn XLS góc trái trên, 2 cột x 3 vạch trắng -->
    <svg width="86" height="94" viewBox="0 0 74 84" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M14 6C14 3.79086 15.7909 2 18 2H50L68 20V78C68 80.2091 66.2091 82 64 82H18C15.7909 82 14 80.2091 14 78V6Z" fill="#137333"/>
        <path d="M50 2L68 20H52C50.8954 20 50 19.1046 50 18V2Z" fill="#1b873f"/>
        <rect x="2" y="13" width="34" height="20" rx="3" fill="#FFFFFF" stroke="#137333" stroke-width="1.2"/>
        <text x="19" y="27" font-family="Arial, sans-serif" font-weight="800" font-size="11" fill="#137333" text-anchor="middle">XLS</text>
        <!-- 2 cột x 3 hàng thanh ngang trắng -->
        <rect x="23" y="44" width="16" height="5" fill="#FFFFFF"/>
        <rect x="43" y="44" width="16" height="5" fill="#FFFFFF"/>
        <rect x="23" y="54" width="16" height="5" fill="#FFFFFF"/>
        <rect x="43" y="54" width="16" height="5" fill="#FFFFFF"/>
        <rect x="23" y="64" width="16" height="5" fill="#FFFFFF"/>
        <rect x="43" y="64" width="16" height="5" fill="#FFFFFF"/>
    </svg>

<?php elseif ($fileType === 'slide'): ?>
    <!-- Slide PPT -->
    <svg width="78" height="94" viewBox="0 0 68 84" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M6 6C6 3.79086 7.79086 2 10 2H44L62 20V78C62 80.2091 60.2091 82 58 82H10C7.79086 82 6 80.2091 6 78V6Z" fill="#ea580c"/>
        <path d="M44 2L62 20H46C44.8954 20 44 19.1046 44 18V2Z" fill="#fb923c"/>
        <text x="34" y="53" font-family="Arial, sans-serif" font-weight="800" font-size="18" fill="#FFFFFF" text-anchor="middle">PPT</text>
    </svg>

<?php else: ?>
    <!-- Tài liệu khác -->
    <i class="fa-solid fa-file-lines text-secondary" style="font-size: 3.2rem;" aria-hidden="true"></i>
<?php endif; ?>
