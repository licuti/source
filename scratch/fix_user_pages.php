<?php
$files = [
    __DIR__ . '/../resources/views/pages/user_donhang_ctv.php',
    __DIR__ . '/../resources/views/pages/user_donhang.php',
    __DIR__ . '/../resources/views/pages/user_diachi.php',
    __DIR__ . '/../resources/views/pages/user_ctv.php',
];

foreach ($files as $file) {
    if (!file_exists($file)) { echo "SKIP (not found): $file\n"; continue; }
    $content = file_get_contents($file);
    // Thay URL ajax.php (get_huyen/get_xa) sang location endpoint
    $new = str_replace(
        ['"sources/ajax/ajax.php"', "'sources/ajax/ajax.php'"],
        ['"<?= URLPATH ?>ajax/location/district"', "'<?= URLPATH ?>ajax/location/district'"],
        $content
    );
    file_put_contents($file, $new);
    $diff = $content !== $new ? 'UPDATED' : 'no change';
    echo basename($file) . ": $diff\n";
}
echo "Done.\n";
