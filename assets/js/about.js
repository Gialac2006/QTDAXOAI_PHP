// Play video trong box bên phải
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.video-play');
  if (!btn) return;

  const box = document.getElementById('about-video-box');
  if (!box) return;

  // Xóa ảnh, gắn video vào box
  box.innerHTML = '';
  const v = document.createElement('video');
  v.src = '../assets/videos/backkground.mp4';  // giữ nguyên tên file bạn đang dùng
  v.autoplay = true;
  v.controls = true;
  v.playsInline = true;
  v.muted = false;
  box.appendChild(v);
});
