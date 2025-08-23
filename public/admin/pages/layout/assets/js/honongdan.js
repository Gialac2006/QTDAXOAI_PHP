// Xác nhận xoá
document.addEventListener('click', function (e) {
  const a = e.target.closest('a[data-confirm]');
  if (a && !confirm(a.getAttribute('data-confirm'))) {
    e.preventDefault();
  }
});

console.debug('[honongdan] loaded');
