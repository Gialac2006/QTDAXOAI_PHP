// Xác nhận xoá
document.addEventListener('click', (e) => {
  const a = e.target.closest('a[data-confirm]');
  if (a && !confirm(a.getAttribute('data-confirm'))) e.preventDefault();
});

// Toggle "Khác (nhập)" cho Loại thiết bị
const selLoai = document.getElementById('selLoai');
const inpLoaiNew = document.getElementById('inpLoaiNew');
if (selLoai && inpLoaiNew) {
  const sync = () => {
    if (selLoai.value === '__NEW__') { inpLoaiNew.style.display='block'; inpLoaiNew.required = true; }
    else { inpLoaiNew.style.display='none'; inpLoaiNew.required = false; inpLoaiNew.value=''; }
  };
  selLoai.addEventListener('change', sync);
  sync();
}

console.debug('[thietbimaymoc] assets loaded');
