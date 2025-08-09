<?php
// Bảo vệ khu vực admin
session_start();
if (empty($_SESSION['user']) || (($_SESSION['user']['VaiTro'] ?? '') !== 'Admin')) {
header('Location: ../../home.html'); // Không phải admin -> về trang thường
exit;
}
