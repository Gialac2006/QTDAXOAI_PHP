<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$user = $_SESSION['user'] ?? null;
$who  = htmlspecialchars($user['HoTen'] ?? $user['TenDangNhap'] ?? 'Admin');
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Admin | Quản lý vùng xoài</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <style>
    :root { 
      --primary: #90a5deff;
      --primary-light: #a4afc1ff; 
      --secondary: #059669;
      --accent: #f59e0b;
      --danger: #dc2626;
      --success: #10b981;
      --warning: #f59e0b;
      
      --white: #ffffff;
      --gray-50: #f9fafb;
      --gray-100: #f3f4f6;
      --gray-200: #e5e7eb;
      --gray-300: #d1d5db;
      --gray-400: #9ca3af;
      --gray-500: #6b7280;
      --gray-600: #4b5563;
      --gray-700: #374151;
      --gray-800: #1f2937;
      --gray-900: #111827;
      
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
      --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
      
      --radius-sm: 0.375rem;
      --radius: 0.5rem;
      --radius-lg: 0.75rem;
      --radius-xl: 1rem;
      --radius-2xl: 1.5rem;
    }
    
    * { 
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body { 
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      font-size: 14px;
      line-height: 1.6;
      color: var(--gray-700);
      background: var(--gray-50);
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }
    
    .topbar { 
      display: flex; 
      align-items: center; 
      justify-content: space-between; 
      padding: 0 24px; 
      height: 64px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      color: var(--white); 
      position: sticky; 
      top: 0; 
      z-index: 1000;
      box-shadow: var(--shadow-lg);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .brand { 
      font-weight: 700; 
      font-size: 18px;
      letter-spacing: -0.025em;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .brand::before {
      content: "🥭";
      font-size: 24px;
      background: var(--white);
      width: 40px;
      height: 40px;
      border-radius: var(--radius-lg);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: var(--shadow-md);
    }
    
    .user { 
      display: flex;
      align-items: center;
      gap: 16px;
      font-weight: 500;
    }
    
    .wrap { 
      display: grid; 
      grid-template-columns: 280px 1fr; 
      min-height: calc(100vh - 64px);
    }
    
    .side { 
      background: var(--white);
      border-right: 1px solid var(--gray-200);
      box-shadow: var(--shadow-md);
      position: relative;
    }
    
    .side::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
    }
    
    .menu { 
      list-style: none; 
      margin: 0; 
      padding: 24px 16px;
    }
    
    .menu li {
      margin-bottom: 4px;
    }
    
    .menu a { 
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px; 
      text-decoration: none; 
      color: var(--gray-600); 
      border-radius: var(--radius-lg); 
      font-weight: 500;
      font-size: 14px;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }
    
    .menu a::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
      transition: left 0.5s ease;
    }
    
    .menu a:hover::before {
      left: 100%;
    }
    
    .menu a.active { 
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      color: var(--white);
      font-weight: 600;
      box-shadow: var(--shadow-md);
      transform: translateX(4px);
    }
    
    .menu a.active::after {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 3px;
      height: 24px;
      background: var(--accent);
      border-radius: 0 2px 2px 0;
    }
    
    .menu a:hover { 
      color: var(--primary);
      background: var(--gray-100);
      transform: translateX(2px);
    }
    
    main.container { 
      padding: 32px 24px;
      background: var(--gray-50);
    }
    
    .cards { 
      display: grid; 
      gap: 20px; 
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
      margin: 0 0 32px;
    }
    
    .card { 
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: var(--radius-2xl);
      padding: 24px;
      box-shadow: var(--shadow-md);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }
    
    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 50%, var(--accent) 100%);
    }
    
    .card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-xl);
      border-color: var(--primary-light);
    }
    
    table { 
      width: 100%; 
      border-collapse: collapse; 
      background: var(--white); 
      border-radius: var(--radius-2xl); 
      overflow: hidden; 
      box-shadow: var(--shadow-lg);
      border: 1px solid var(--gray-200);
    }
    
    th, td { 
      padding: 16px 20px; 
      border-bottom: 1px solid var(--gray-200); 
      vertical-align: middle;
    }
    
    th { 
      background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
      text-align: left; 
      font-weight: 600;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--gray-700);
    }
    
    tbody tr {
      transition: all 0.2s ease;
    }
    
    tbody tr:hover {
      background: var(--gray-50);
    }
    
    tbody tr:last-child td {
      border-bottom: none;
    }
    
    .toolbar { 
      display: flex; 
      gap: 12px; 
      align-items: center; 
      margin: 0 0 24px;
      flex-wrap: wrap;
    }
    
    .toolbar input, .toolbar select, .toolbar button { 
      padding: 10px 14px; 
      border: 1px solid var(--gray-300); 
      border-radius: var(--radius-lg);
      background: var(--white);
      color: var(--gray-700);
      font-size: 14px;
      transition: all 0.2s ease;
    }
    
    .toolbar input:focus, .toolbar select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }
    
    .btn { 
      background: linear-gradient(135deg, var(--secondary) 0%, #047857 100%);
      color: var(--white); 
      border: none; 
      cursor: pointer;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      text-decoration: none;
      box-shadow: var(--shadow-md);
    }
    
    .btn:hover {
      transform: translateY(-1px);
      box-shadow: var(--shadow-lg);
    }
    
    .btn:active {
      transform: translateY(0);
    }
    
    .btn.danger { 
      background: linear-gradient(135deg, var(--danger) 0%, #b91c1c 100%);
    }
    
    .btn.primary {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    }
    
    .muted { 
      color: var(--gray-500); 
      font-size: 12px;
    }
    
    .msg { 
      margin: 0 0 24px; 
      padding: 16px 20px; 
      border-radius: var(--radius-xl); 
      display: none;
      border: 1px solid;
      font-weight: 500;
    }
    
    .msg.success {
      background: #ecfdf5;
      color: var(--success);
      border-color: #a7f3d0;
    }
    
    .msg.err { 
      background: #fef2f2; 
      color: var(--danger);
      border-color: #fecaca;
    }
    
    .top-actions {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    
    .top-actions a { 
      color: var(--white); 
      text-decoration: none; 
      padding: 8px 16px;
      border-radius: var(--radius);
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      font-weight: 500;
      transition: all 0.2s ease;
    }
    
    .top-actions a:hover { 
      background: rgba(255, 255, 255, 0.2);
      transform: translateY(-1px);
    }
    
    /* Responsive Design */
    @media (max-width: 900px) { 
      .wrap { 
        grid-template-columns: 1fr;
      } 
      
      .side { 
        position: sticky; 
        top: 64px;
      }
      
      .topbar {
        padding: 0 16px;
      }
      
      .brand {
        font-size: 16px;
      }
      
      main.container {
        padding: 20px 16px;
      }
      
      .cards {
        grid-template-columns: 1fr;
        gap: 16px;
      }
      
      .toolbar {
        flex-direction: column;
        align-items: stretch;
      }
      
      .toolbar input, .toolbar select, .toolbar button {
        width: 100%;
      }
    }
    
    @media (max-width: 640px) {
      .topbar {
        height: 56px;
        padding: 0 12px;
      }
      
      .wrap {
        min-height: calc(100vh - 56px);
      }
      
      .user {
        font-size: 12px;
      }
      
      main.container {
        padding: 16px 12px;
      }
      
      .card {
        padding: 16px;
      }
      
      th, td {
        padding: 12px 16px;
        font-size: 13px;
      }
    }
    
    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .card, table {
      animation: fadeInUp 0.3s ease-out;
    }
    
    /* Scrollbar Styling */
    ::-webkit-scrollbar {
      width: 8px;
    }
    
    ::-webkit-scrollbar-track {
      background: var(--gray-100);
    }
    
    ::-webkit-scrollbar-thumb {
      background: var(--gray-300);
      border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      background: var(--gray-400);
    }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="brand">Quản trị vùng xoài</div>
    <div class="top-actions">
      <span class="user"><?php echo $who; ?> (Admin)</span>
      <a href="../logout.php?redirect=../login.html">Đăng xuất</a>
    </div>
  </header>
  <div class="wrap">
    <aside class="side">
      <ul class="menu">
        <li><a href="index.php?p=dashboard" class="<?php echo $page==='dashboard'?'active':''; ?>">Tổng quan</a></li>
        <li><a href="index.php?p=honongdan" class="<?php echo $page==='honongdan'?'active':''; ?>">Hộ nông dân</a></li>
        <li><a href="index.php?p=giongxoai" class="<?php echo $page==='giongxoai'?'active':''; ?>">Giống xoài</a></li>
        <li><a href="index.php?p=vungtrong" class="<?php echo $page==='vungtrong'?'active':''; ?>">Vùng trồng</a></li>
        <li><a href="index.php?p=nguoidung" class="<?php echo $page==='nguoidung'?'active':''; ?>">Người dùng</a></li>
        <li><a href="index.php?p=thuocbvtv" class="<?php echo $page==='thuocbvtv'?'active':''; ?>">Thuốc bảo vệ thực vật</a></li>
        <li><a href="index.php?p=phanbon" class="<?php echo $page==='phanbon'?'active':''; ?>">Phân bón</a></li>
        <li><a href="index.php?p=canhtac" class="<?php echo $page==='canhtac'?'active':''; ?>">Canh tác</a></li>
        <li><a href="index.php?p=thietbimaymoc" class="<?php echo $page==='thietbimaymoc'?'active':''; ?>">Thiết bị máy móc</a></li>
        <li><a href="index.php?p=thietbiiot" class="<?php echo $page==='thietbiiot'?'active':''; ?>">Thiết bị IOT</a></li>
        <li><a href="index.php?p=nhatkyphunthuoc" class="<?php echo $page==='nhatkyphunthuoc'?'active':''; ?>">Nhật ký phun thuốc</a></li>
        <li><a href="index.php?p=thoitiet" class="<?php echo $page==='thoitiet'?'active':''; ?>">Thời tiết</a></li>
        <li><a href="index.php?p=lichbaotri" class="<?php echo $page==='lichbaotri'?'active':''; ?>">Lịch bảo trì</a></li>
        <li><a href="index.php?p=baocaosanluong" class="<?php echo $page==='baocaosanluong'?'active':''; ?>">Báo cáo sản lượng</a></li>
        <li><a href="index.php?p=bandogis" class="<?php echo $page==='baodogis'?'active':''; ?>">Bản đồ gis</a></li>
        <li><a href="index.php?p=giaxoai" class="<?php echo $page==='giaxoai'?'active':''; ?>">Giá xoài</a></li>

      </ul>
    </aside>
    <main class="container">