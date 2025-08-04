function showTab(tab) {
    document.getElementById('login-form').style.display = tab === 'login' ? 'flex' : 'none';
    document.getElementById('register-form').style.display = tab === 'register' ? 'flex' : 'none';
    var btns = document.querySelectorAll('.tab-btn');
    btns[0].classList.toggle('active', tab === 'login');
    btns[1].classList.toggle('active', tab === 'register');
    // Xóa thông báo khi chuyển tab
    document.getElementById('login-message').innerText = "";
    document.getElementById('register-message').innerText = "";
}

// ========== Tạo 10 trái xoài bay random ==========
function randomInt(a, b) {
    return Math.floor(Math.random() * (b - a + 1)) + a;
}

function createMango(i) {
    const mango = document.createElement('span');
    mango.innerText = '🥭';
    const size = randomInt(34, 60);
    mango.style.position = 'fixed';
    mango.style.top = randomInt(5, 70) + 'vh';
    mango.style.left = '-70px';
    mango.style.fontSize = size + 'px';
    mango.style.color = ['#fcc419', '#fe912c', '#42c77a', '#e2a72a', '#a9c812'][i % 5];
    mango.style.zIndex = 1;
    mango.style.pointerEvents = 'none';
    mango.className = 'flying-mango';
    document.getElementById('mango-container').appendChild(mango);

    // Chuyển động lặp lại random
    function animate() {
        const duration = randomInt(18, 20); // giây
        const top = randomInt(5, 70) + 'vh';
        mango.style.top = top;
        mango.style.transition = `left ${duration}s linear, top 3s ease`;
        mango.style.left = '110vw';
        setTimeout(() => {
            // Reset vị trí và random lại
            mango.style.transition = 'none';
            mango.style.left = '-70px';
            mango.style.top = randomInt(5, 70) + 'vh';
            setTimeout(animate, 100);
        }, duration * 1000);
    }
    setTimeout(animate, randomInt(0, 5000)); // random delay khi vào trang
}

// Tạo 10 trái xoài bay
for(let i = 0; i < 10; ++i) {
    createMango(i);
}

// Xử lý Đăng nhập
document.getElementById('login-form').onsubmit = async function(e) {
    e.preventDefault();
    let username = document.getElementById('login-username').value;
    let password = document.getElementById('login-password').value;
            let res = await fetch('/api/login', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({username, password})
    });
    let data = await res.json();
    document.getElementById('login-message').innerText = data.message;
    if(data.status === 'success') {
        document.getElementById('login-message').style.color = 'green';
        setTimeout(() => {
            if (data.user && data.user.role === 'ADMIN') {
                window.location.href = "/admin";
            } else {
                window.location.href = "/giaodien";
            }
        }, 900); // Chờ 0.9s cho user thấy thông báo
    } else {
        document.getElementById('login-message').style.color = 'red';
    }
};

// Xử lý Đăng ký
document.getElementById('register-form').onsubmit = async function(e) {
    e.preventDefault();
    let username = document.getElementById('reg-username').value;
    let email = document.getElementById('reg-email').value;
    let password = document.getElementById('reg-password').value;
    let repassword = document.getElementById('reg-repassword').value;
    if(password !== repassword) {
        document.getElementById('register-message').innerText = 'Mật khẩu nhập lại không khớp';
        document.getElementById('register-message').style.color = 'red';
        return;
    }
            let res = await fetch('/api/register', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({username, email, password})
    });
    let data = await res.json();
    document.getElementById('register-message').innerText = data.message;
    if(data.status === 'success') {
        document.getElementById('register-message').style.color = 'green';
        setTimeout(()=>showTab('login'), 1000);
    } else {
        document.getElementById('register-message').style.color = 'red';
    }
};