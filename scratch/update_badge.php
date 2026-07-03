<?php
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4', 'root', '');
$pdo->exec("UPDATE db_module_admin SET badge_query = 'SELECT COUNT(*) FROM db_form_submissions WHERE status = 0' WHERE id = 33");
echo "Updated badge query.";
