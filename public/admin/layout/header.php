<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$user = $_SESSION['user'] ?? null;
$who  = htmlspecialchars($user['HoTen'] ?? $user['TenDangNhap'] ?? 'Admin');
?>
<!doctype html>
<html lang="vi">
<head>
  <!-- giữ nguyên phần <head> cũ -->

<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Admin | Quản lý vùng xoài</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <style>
    :root{ --green:#2e7d32; --be:#f8f5ec; --ink:#1d252a; --line:#e9ecef; --brand:#fbc02d; }
    *{ box-sizing:border-box }
    body{ margin:0; font:14px/1.5 system-ui,Segoe UI,Roboto; color:var(--ink); background:#fff; }
    .topbar{ display:flex; align-items:center; justify-content:space-between; padding:12px 18px; background:#111; color:#fff; position:sticky; top:0; z-index:10 }
    .topbar .brand{ font-weight:700; letter-spacing:.5px }
    .topbar .user{ opacity:.85 }
    .wrap{ display:grid; grid-template-columns: 220px 1fr; min-height:calc(100vh - 48px) }
    .side{ border-right:1px solid var(--line); background:var(--be) }
    .menu{ list-style:none; margin:0; padding:12px }
    .menu a{ display:block; padding:10px 12px; text-decoration:none; color:#244; border-radius:10px; font-weight:600 }
    .menu a.active{ background:#fff6cc; color:#000 }
    .menu a:hover{ background:#fff; }
    main.container{ padding:18px 20px }
    .cards{ display:grid; gap:14px; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); margin:8px 0 18px }
    .card{ border:1px solid var(--line); border-radius:14px; padding:14px 16px; background:#fff }
    table{ width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; border:1px solid var(--line) }
    th,td{ padding:10px 12px; border-bottom:1px solid var(--line); vertical-align:top }
    th{ background:#fafafa; text-align:left; font-weight:700 }
    .toolbar{ display:flex; gap:8px; align-items:center; margin:8px 0 14px }
    .toolbar input, .toolbar select, .toolbar button { padding:8px 10px; border:1px solid var(--line); border-radius:10px; }
    .btn{ background:var(--green); color:#fff; border:none; cursor:pointer }
    .btn.danger{ background:#c62828 }
    .muted{ color:#6b7280; font-size:12px }
    .msg{ margin:8px 0 14px; padding:9px 12px; border-radius:10px; background:#f4fdf4; color:#0a7; display:none }
    .msg.err{ background:#fff5f5; color:#c33 }
    .top-actions a{ color:#fff; text-decoration:none; margin-left:14px; opacity:.9 }
    .top-actions a:hover{ opacity:1 }
    @media (max-width: 900px){ .wrap{ grid-template-columns:1fr } .side{ position:sticky; top:48px } }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="brand">Quản trị vùng xoài</div>
    <div class="top-actions">
  <span class="user">👤 <?php echo $who; ?> (Admin)</span>
  <a href="../logout.php?redirect=../login.html">Đăng xuất</a>
</div>
  </header>
  <div class="wrap">
    <aside class="side">
      <ul class="menu">
        <li><a href="index.php?p=dashboard" class="<?php echo $page==='dashboard'?'active':''; ?>">📊 Tổng quan</a></li>
        <li><a href="index.php?p=honongdan" class="<?php echo $page==='honongdan'?'active':''; ?>">👨‍🌾 Hộ nông dân</a></li>
      </ul>
    </aside>
    <main class="container">
