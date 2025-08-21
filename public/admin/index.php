<?php
require __DIR__.'/_guard.php';

$page = $_GET['p'] ?? 'dashboard';
$allow = ['dashboard','honongdan','giongxoai','vungtrong','nguoidung','thuocbvtv','phanbon','canhtac'];
if (!in_array($page, $allow)) $page = '404';

require __DIR__.'/layout/header.php';
require __DIR__."/pages/{$page}.php";
require __DIR__.'/layout/footer.php';
