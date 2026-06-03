<?php
$file = dirname(__DIR__) . '/app/Core/Model.php';
$content = file_get_contents($file);
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (strpos($line, 'function __get') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
