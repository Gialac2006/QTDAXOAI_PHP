// ==== API tương đối (không lệ thuộc /nhom14) ====
const API = {
  login:    'public/login.php',
  register: 'public/register.php',
};

// ==== TAB ====
function showTab(tab) {
  document.getElementById('login-form').style.display    = (tab === 'login')    ? 'flex' : 'none';
  document.getElementById('register-form').style.display = (tab === 'register') ? 'flex' : 'none';
  const btns = document.querySelectorAll('.tab-btn');
  if (btns[0]) btns[0].classList.toggle('active', tab === 'login');
  if (btns[1]) btns[1].classList.toggle('active', tab === 'register');
  document.getElementById('login-message').innerText = "";
  document.getElementById('register-message').innerText = "";
}

// ==== ĐĂNG NHẬP ====
document.getElementById('login-form').onsubmit = async (e) => {
  e.preventDefault();
  const username = document.getElementById('login-username').value.trim();
  const password = document.getElementById('login-password').value;
  const msg = document.getElementById('login-message');
  msg.style.color = '#0a8'; msg.textContent = 'Đang đăng nhập…';

  try {
    const res  = await fetch(API.login, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ TenDangNhap: username, MatKhau: password })
    });

    // Nếu server trả non-JSON (lỗi PHP) -> hiện thô cho dễ debug
    const text = await res.text();
    let data; try { data = JSON.parse(text); } catch { throw new Error(text || 'Lỗi phản hồi'); }

if (data.success) {
  const role = (data.user?.VaiTro || '').toLowerCase();
  location.href = role === 'admin'
    ? 'public/admin/index.php?p=dashboard'
    : 'home.html';
}

    localStorage.setItem('auth_token', data.token || '');
    localStorage.setItem('auth_user', JSON.stringify(data.user || {}));

    msg.style.color = 'green';
    msg.textContent = 'Đăng nhập thành công! Đang chuyển…';

    const role = String(data.user?.VaiTro || '').trim().toLowerCase();
    // chuyển trang bằng đường dẫn tương đối (khỏi lo prefix /nhom14)
    setTimeout(() => {
      location.href = (role === 'admin')
        ? 'public/admin/index.php?p=dashboard'
        : 'home.html';
    }, 600);

  } catch (err) {
    msg.style.color = 'red';
    msg.textContent = err.message || 'Có lỗi xảy ra';
  }
};

// ==== ĐĂNG KÝ ====
document.getElementById('register-form').onsubmit = async (e) => {
  e.preventDefault();
  const username = document.getElementById('reg-username').value.trim();
  const email    = document.getElementById('reg-email').value.trim();
  const password = document.getElementById('reg-password').value;
  const repass   = document.getElementById('reg-repassword').value;
  const msg = document.getElementById('register-message');

  if (password !== repass) {
    msg.style.color='red'; msg.textContent='Mật khẩu nhập lại không khớp'; return;
  }

  msg.style.color='#0a8'; msg.textContent='Đang tạo tài khoản…';

  try {
    const res  = await fetch(API.register, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        TenDangNhap: username,
        MatKhau: password,
        HoTen: username,
        Email: email,
        VaiTro: 'User'
      })
    });

    const text = await res.text();
    let data; try { data = JSON.parse(text); } catch { throw new Error(text || 'Lỗi phản hồi'); }
    if (!data.success) throw new Error(data.error || data.message || 'Đăng ký thất bại');

    msg.style.color='green';
    msg.textContent='Đăng ký thành công! Bạn có thể đăng nhập.';
    setTimeout(() => {
      showTab('login');
      document.getElementById('login-username').value = username;
      document.getElementById('login-password').value = password;
    }, 700);

  } catch (err) {
    msg.style.color='red';
    msg.textContent = err.message || 'Có lỗi xảy ra';
  }
};
