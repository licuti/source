<?php
require 'app/autoload.php';
App\Core\App::getInstance()->boot();
App\Core\DB::statement("UPDATE #_lang SET image = REPLACE(image, '/templates/images/', 'assets/images/')");
echo "DB Updated";
