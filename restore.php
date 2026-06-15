<?php
$sql = file_get_contents("database/phuongnamv_db.sql");
$lines = explode("\n", $sql);
$create = implode("\n", array_slice($lines, 496, 19));

$insert = "";
for ($i = 520; $i < 600; $i++) {
    $insert .= $lines[$i] . "\n";
    if (strpos($lines[$i], ");") !== false) {
        break;
    }
}

file_put_contents("restore_content.sql", "DROP TABLE IF EXISTS `db_content`;\n" . $create . "\n" . $insert);
echo "Generated restore_content.sql";
