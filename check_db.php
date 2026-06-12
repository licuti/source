<?php
$db = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4', 'root', '');
$stmt = $db->query('DESCRIBE db_posts');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
