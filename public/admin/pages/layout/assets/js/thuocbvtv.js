// Xác nhận xoá
document.addEventListener('click', (e) => {
  const a = e.target.closest('a[data-confirm]');
  if (a && !confirm(a.getAttribute('data-confirm'))) {
    e.preventDefault();
  }
});

// Toggle "Khác (nhập)" cho đơn vị tính
const selDV = document.getElementById('selDonVi');
const inpDV = document.getElementById('inpDonViNew');
if (selDV && inpDV){
  const sync = () => {
    if (selDV.value === '__NEW__'){ inpDV.style.display = 'block'; inpDV.required = true; }
    else { inpDV.style.display = 'none'; inpDV.required = false; inpDV.value=''; }
  };
  selDV.addEventListener('change', sync);
  sync();
}

console.debug('[thuocbvtv] loaded');
