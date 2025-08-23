<?php
ini_set('display_errors',1); error_reporting(E_ALL);

define('APP_ROOT', dirname(dirname(__DIR__)));        // /.../QTDAXOAI_PHP
define('PUBLIC_DIR', dirname(__DIR__));               // /.../QTDAXOAI_PHP/public
define('ADMIN_DIR', __DIR__);                         // /.../QTDAXOAI_PHP/public/admin

// URL gốc dự án (để link CSS/JS tuyệt đối)
define('APP_URL', dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])))); // /nhom14/QTDAXOAI_PHP

require ADMIN_DIR.'/_guard.php';

// Router
$page  = $_GET['p'] ?? 'dashboard';
$allow = ['dashboard','honongdan','giongxoai','vungtrong','nguoidung','thuocbvtv','phanbon','canhtac','thietbimaymoc','thietbiiot','nhatkyphunthuoc','thoitiet','lichbaotri','baocaosanluong','bandogis','404'];
if (!in_array($page, $allow, true)) $page = '404';

// Đường dẫn view & layout
$PAGES  = ADMIN_DIR . '/pages';
$LAYOUT = $PAGES . '/layout';
$HEADER = $LAYOUT . '/header.php';
$FOOTER = $LAYOUT . '/footer.php';
$VIEW   = $PAGES  . "/{$page}.php";

foreach ([$HEADER,$VIEW,$FOOTER] as $f) {
  if (!is_file($f)) { http_response_code(500); die("Không tìm thấy: $f"); }
}

require $HEADER;
require $VIEW;
require $FOOTER;
