// Xác nhận xoá cho mọi nút có data-confirm
document.addEventListener('click', function (e) {
  const a = e.target.closest('a[data-confirm]');
  if (a && !confirm(a.getAttribute('data-confirm'))) {
    e.preventDefault();
  }
});

// Bạn có thể bổ sung logic riêng cho trang Canh tác ở đây nếu cần.
console.debug('[canhtac] loaded');