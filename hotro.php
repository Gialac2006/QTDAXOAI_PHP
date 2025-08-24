<!doctype html>
<html lang="vi">
<head>
  <link rel="stylesheet" href="assets/css/giaodien.css">
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Hỗ Trợ Kiến Thức – Xoài</title>
  <style>
    /* ===== THEME (Mango/Amber) ===== */
    :root{
      --bg-1:#fff8e1; --bg-2:#fffde7;
      --primary:#ff9800; --primary-2:#ffc107; --primary-dark:#e65100;
      --text:#4e342e; --muted:#6d4c41;
      --bd:rgba(255,152,0,.25); --shadow:rgba(255,152,0,.25);
      --card:#fff;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:Poppins,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:var(--text);background:rgba(241, 236, 139, 1)}

    /* ===== NAVBAR (giống canhtac – brand + menu + toggle) ===== */
    .nav-wrap{
      position:sticky;top:0;z-index:1000;background:#fff;
      border-bottom:2px solid var(--bd); box-shadow:0 4px 16px rgba(0,0,0,.05);
    }
    .nav{
      max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:16px;
      padding:10px 16px;
    }
    .brand{
      display:flex;align-items:center;gap:10px;font-weight:800;font-size:1.05rem;color:var(--primary-dark);text-decoration:none;
    }
    .brand .logo{width:28px;height:28px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(45deg,#ffecb3,#ffe082,#fff8e1);box-shadow:0 4px 10px rgba(0,0,0,.08)}
    .nav-toggle{margin-left:auto;display:none;border:0;background:#fff;font-size:22px;cursor:pointer}
    .menu{display:flex;gap:14px;flex-wrap:wrap;margin-left:auto}
    .menu a{
      text-decoration:none;color:#5d4037;padding:8px 10px;border-radius:10px;border:1px solid transparent;
    }
    .menu a:hover{background:#fff7e6;border-color:var(--bd)}
    .menu a.active{background:linear-gradient(45deg,var(--primary),var(--primary-2));color:#fff}
    @media (max-width: 900px){
      .nav{flex-wrap:wrap}
      .nav-toggle{display:block}
      .menu{width:100%;display:none;flex-direction:column;margin-left:0}
      .menu.open{display:flex}
    }

    /* ===== HEADER ===== */
    .header{
      background:rgba(207, 143, 91, 1);
      color:#fff;text-align:center;padding:56px 16px;border-bottom:5px solid var(--primary);
      box-shadow:0 8px 20px var(--shadow);
    }
    .header h1{margin:0 0 8px;font-size:2rem}
    .header p{margin:0;font-size:1rem}

    /* ===== LAYOUT ===== */
    .container{max-width:1100px;margin:0 auto;padding:22px 16px}
    .toolbar{
      display:flex;gap:12px;flex-wrap:wrap;align-items:center;background:#ffffffcc;border:1px solid #b6bd62ff;border-radius:12px;
      padding:12px 14px;backdrop-filter:blur(6px)
    }
    .toolbar input{
      flex:1;min-width:220px;padding:10px 12px;border:2px solid #eee;border-radius:8px;outline:none
    }
    .toolbar input:focus{border-color:var(--primary)}
    .chip{
      padding:6px 10px;border-radius:999px;background:#fff;border:1px solid var(--bd);cursor:pointer;user-select:none;font-size:14px
    }
    .chip.active{background:linear-gradient(45deg,var(--primary),var(--primary-2));color:#fff;border-color:transparent}

    /* ===== GRID (2 cột hàng ngang) ===== */
    .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;margin-top:18px}
    @media (max-width: 768px){ .grid{grid-template-columns:1fr} }

    /* ===== CARD ===== */
    .card{
      background:var(--card);border:2px solid var(--bd);border-radius:16px;padding:18px;
      box-shadow:0 6px 18px rgba(0,0,0,.06);position:relative;overflow:hidden
    }
    .avatar{width:74px;height:74px;border-radius:50%;display:flex;align-items:center;justify-content:center;
      background:linear-gradient(45deg,#ffecb3,#ffe082,#fff8e1);margin:0 auto 10px;box-shadow:0 6px 14px rgba(0,0,0,.08);font-size:28px}
    .title{color:var(--primary-dark);text-align:center;font-weight:800;font-size:1.15rem;margin:6px 0}
    .meta{color:var(--muted);font-size:.95rem;margin:4px 0;text-align:center}
    .badge{display:inline-block;padding:3px 8px;border-radius:12px;font-size:.8rem;color:#fff;background:linear-gradient(45deg,var(--primary),var(--primary-2))}
    .note{color:#6d4c41;margin-top:8px}
    .actions{margin-top:12px;display:flex;gap:10px;flex-wrap:wrap}
    .btn{padding:8px 12px;border-radius:10px;border:1px solid var(--bd);background:#fff;cursor:pointer}
    .btn.primary{background:linear-gradient(45deg,var(--primary),var(--primary-2));color:#fff;border-color:transparent}
    .empty{display:none;text-align:center;color:#8d6e63;padding:24px 10px}

    /* Animations */
    .support-item{opacity:0;transform:translateY(24px)}
    .support-item.show{opacity:1;transform:translateY(0);transition:all .5s ease}
    .support-item:hover{transform:translateY(-4px);transition:transform .25s ease}

    /* Modal */
    .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.35);z-index:2000}
    .dialog{width:min(720px,94vw);background:#fff;border-radius:14px;padding:18px;border:2px solid var(--bd)}
    .dialog h3{margin:0 0 8px;color:var(--primary-dark)}
    .dialog .close{float:right;cursor:pointer;border:none;background:#fff;font-size:18px}
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <ul class="navbar-menu">
       <li class="navbar-item active"><a href="home.html">Trang chủ</a></li>
        <li class="navbar-item active"><a href="aboutus.html">Đội ngũ</a></li>
        <li class="navbar-item">Nông dân
            <div class="navbar-dropdown">
                <a href="honongdan.php">Danh sách nông dân</a>
                <a href="thietbitheoho.php">Thiết bị theo hộ</a>
                <a href="hotro.php">Hỗ trợ</a>
            </div>
        </li>
        <li class="navbar-item">Đất đai
            <div class="navbar-dropdown">
                <a href="vungtrong.php">Vùng trồng</a>
                <a href="muavu.php">Mùa vụ</a>
                <a href="giongxoai.php">Giống Xoài</a>
                <a href="bandogis.php">Sổ thửa & bản đồ</a>
                <a href="thoitiet.php">Thời tiết</a>
            </div>
        </li>
        <li class="navbar-item">Sản xuất
            <div class="navbar-dropdown">
                <a href="muavu.php">Theo dõi mùa vụ</a>
                <a href="canhtac.php">Canh tác</a>
                <a href="nhatkyphunthuoc.php">Nhật ký phun thuốc</a>
                <a href="thuocbvtv.php">Thuốc bảo vệ thực vật</a>
                <a href="phanbon.php">Phân bón</a>
                <a href="thietbimaymoc.php">Thiết bị máy móc</a>
                <a href="baocaosanluong.php">Báo cáo sản lượng</a>
            </div>
        </li>
        <li class="navbar-item">Thị trường
            <div class="navbar-dropdown">
                <a href="giaxoai.php">Giá xoài</a>
            </div>
        </li>
        <li class="navbar-item">Đào tạo
            <div class="navbar-dropdown">
                <a href="#">Tài liệu hướng dẫn</a>
            </div>
        </li>
        <li class="navbar-item">ADMIN
            <div class="navbar-dropdown">
                <a href="login.html">QUẢN LÝ</a>
            </div>
        </li>
    </ul>
</nav>

<!-- Spacer để không bị che bởi navbar fixed (cao 64px theo CSS hiện tại) -->
<div style="height:64px"></div>


  <!-- HEADER -->
  <section class="header">
    <h1>HỖ TRỢ KIẾN THỨC NÔNG DÂN – XOÀI</h1>
    <p>Tra cứu nhanh quy trình canh tác, phòng trừ sâu bệnh, tưới tiêu, thu hoạch & bảo quản.</p>
  </section>

  <div class="container">
    <!-- TOOLBAR -->
    <div class="toolbar">
      <input id="searchInput" type="text" placeholder="Tìm theo tiêu đề, nội dung, nguồn...">
      <div class="chip" data-topic="Phân bón">Phân bón</div>
      <div class="chip" data-topic="Sâu bệnh">Sâu bệnh</div>
      <div class="chip" data-topic="Tưới tiêu">Tưới tiêu</div>
      <div class="chip" data-topic="Thu hoạch">Thu hoạch</div>
      <div class="chip" data-topic="Bảo quản">Bảo quản</div>
      <div class="chip active" data-topic="__all">Tất cả</div>
    </div>

    <!-- GRID ITEMS (có thể thay bằng dữ liệu thật sau) -->
    <div id="supportContainer" class="grid">
      <!-- Item 1 -->
      <article class="card support-item"
        data-topic="Phân bón"
        data-title="Quy trình bón phân nuôi trái xoài"
        data-content="• Sau đậu trái 2–3 tuần: NPK 16-16-8 + hữu cơ vi sinh.
• 4–6 tuần: NPK 13-13-20 + Bo, Ca.
• Ngưng bón 10–14 ngày trước thu hoạch."
        data-source="Cục Trồng trọt"
        data-date="2025-08-10"
      >
        <div class="avatar">📘</div>
        <div class="title">Quy trình bón phân nuôi trái xoài</div>
        <div class="meta"><span class="badge">Phân bón</span> • Cập nhật: 2025-08-10</div>
        <div class="note">Lịch bón NPK, hữu cơ & vi lượng cho giai đoạn sau đậu trái.</div>
        <div class="actions">
          <button class="btn primary btn-view">Xem chi tiết</button>
          <a class="btn" href="#" target="_blank" rel="noopener">Nguồn</a>
        </div>
      </article>

      <!-- Item 2 -->
      <article class="card support-item"
        data-topic="Sâu bệnh"
        data-title="Phòng trừ bệnh thán thư trên xoài"
        data-content="Tỉa tán thông thoáng, tưới hợp lý; luân phiên hoạt chất nhóm strobilurin + đồng; tuân thủ PHI."
        data-source="TT BVTV"
        data-date="2025-08-08"
      >
        <div class="avatar">🧪</div>
        <div class="title">Phòng trừ bệnh thán thư trên xoài</div>
        <div class="meta"><span class="badge">Sâu bệnh</span> • Cập nhật: 2025-08-08</div>
        <div class="note">Nhận biết đốm nâu đen trên lá, trái; biện pháp canh tác & thuốc BVTV an toàn.</div>
        <div class="actions">
          <button class="btn primary btn-view">Xem chi tiết</button>
          <a class="btn" href="#" target="_blank" rel="noopener">Nguồn</a>
        </div>
      </article>

      <!-- Item 3 -->
      <article class="card support-item"
        data-topic="Thu hoạch"
        data-title="Quy trình thu hoạch & bảo quản trái xoài"
        data-content="Thu hoạch lúc sáng mát, cắt cuống 0.5–1 cm; khử nhựa; tiền lạnh 12–13°C; bao gói đục lỗ; vận chuyển lạnh."
        data-source="FAO"
        data-date="2025-08-01"
      >
        <div class="avatar">📦</div>
        <div class="title">Quy trình thu hoạch & bảo quản trái xoài</div>
        <div class="meta"><span class="badge">Thu hoạch</span> • Cập nhật: 2025-08-01</div>
        <div class="note">Chọn độ già, kỹ thuật cắt cuống, xử lý nhựa, làm mát nhanh & đóng gói.</div>
        <div class="actions">
          <button class="btn primary btn-view">Xem chi tiết</button>
          <a class="btn" href="#" target="_blank" rel="noopener">Nguồn</a>
        </div>
      </article>

      <!-- Item 4 -->
      <article class="card support-item"
        data-topic="Tưới tiêu"
        data-title="Tưới nhỏ giọt cho xoài trong mùa khô"
        data-content="Đặt ẩm kế 20–30 cm; tưới khi đất < 60% FC; 20–40 lít/cây/lần tuỳ tuổi; che phủ gốc giảm bốc hơi."
        data-source="Khuyến nông"
        data-date="2025-07-25"
      >
        <div class="avatar">💧</div>
        <div class="title">Tưới nhỏ giọt cho xoài trong mùa khô</div>
        <div class="meta"><span class="badge">Tưới tiêu</span> • Cập nhật: 2025-07-25</div>
        <div class="note">Lượng tưới theo tuổi cây & ẩm độ đất; lợi ích nhỏ giọt.</div>
        <div class="actions">
          <button class="btn primary btn-view">Xem chi tiết</button>
          <a class="btn" href="#" target="_blank" rel="noopener">Nguồn</a>
        </div>
      </article>
    </div>

    <div id="noResults" class="empty">Không có bài phù hợp. Hãy thử từ khóa/chủ đề khác.</div>
  </div>

  <!-- MODAL -->
  <div id="modal" class="modal" role="dialog" aria-modal="true">
    <div class="dialog">
      <button class="close" id="modalClose">✕</button>
      <h3 id="mTitle">Tiêu đề</h3>
      <div id="mMeta" style="color:#6d4c41;margin:.5rem 0 .75rem;"></div>
      <div id="mContent" style="white-space:pre-wrap;line-height:1.6"></div>
    </div>
  </div>

  <script>
    // Toggle menu (mobile)
    const navToggle = document.getElementById('navToggle');
    const mainMenu  = document.getElementById('mainMenu');
    navToggle?.addEventListener('click', ()=> mainMenu.classList.toggle('open'));

    // Helpers
    const $  = s => document.querySelector(s);
    const $$ = s => Array.from(document.querySelectorAll(s));
    const normalize = s => (s||'').toLowerCase().trim();

    // Elements
    const searchInput = $('#searchInput');
    const chips = $$('.chip');
    const items = $$('.support-item');
    const container = $('#supportContainer');
    const noResults = $('#noResults');

    let activeTopic = '__all';

    function render(){
      const q = normalize(searchInput.value);
      let shown = 0;
      items.forEach((it, idx)=>{
        const topic   = normalize(it.dataset.topic);
        const title   = normalize(it.dataset.title);
        const content = normalize(it.dataset.content);
        const source  = normalize(it.dataset.source);

        const topicOk = (activeTopic==='__all') || (topic === normalize(activeTopic));
        const textOk  = !q || title.includes(q) || content.includes(q) || source.includes(q);
        const ok = topicOk && textOk;

        it.style.display = ok ? 'block' : 'none';
        if (ok){
          shown++;
          // delay nhỏ cho hiệu ứng
          setTimeout(()=> it.classList.add('show'), idx*80);
        } else {
          it.classList.remove('show');
        }
      });
      container.style.display = shown ? 'grid' : 'none';
      noResults.style.display = shown ? 'none' : 'block';
    }

    // Chips
    chips.forEach(ch=>{
      ch.addEventListener('click', ()=>{
        chips.forEach(c=>c.classList.remove('active'));
        ch.classList.add('active');
        activeTopic = ch.dataset.topic || '__all';
        render();
      });
    });

    // Search
    searchInput?.addEventListener('input', render);

    // Modal
    const modal   = $('#modal');
    const mTitle  = $('#mTitle');
    const mMeta   = $('#mMeta');
    const mContent= $('#mContent');
    const close   = ()=> modal.style.display='none';

    $('#modalClose').addEventListener('click', close);
    modal.addEventListener('click', e=>{ if(e.target===modal) close(); });

    $$('.btn-view').forEach(btn=>{
      btn.addEventListener('click', e=>{
        const card = e.target.closest('.support-item');
        mTitle.textContent = card.dataset.title || 'Chi tiết';
        const date   = card.dataset.date   || '';
        const topic  = card.dataset.topic  || 'Khác';
        const source = card.dataset.source || 'Biên tập';
        mMeta.textContent = `Chủ đề: ${topic} • Cập nhật: ${date} • Nguồn: ${source}`;
        mContent.textContent = card.dataset.content || '';
        modal.style.display='flex';
      });
    });

    // Init
    render();
  </script>
</body>
</html>
