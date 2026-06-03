<?php
$file = dirname(__DIR__) . '/app/Core/Model.php';
$content = file_get_contents($file);
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (strpos($line, 'relation') !== false || strpos($line, 'variants') !== false || strpos($line, 'with') !== false) {
        if (strpos($line, 'function') !== false || strpos($line, 'class') !== false || strpos($line, '->') !== false) {
            echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
        }
    }
}
