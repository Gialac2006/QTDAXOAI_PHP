
// ===== GIA XOÀI – JS (tham khảo mẫu baocaosanluong, giữ nguyên chức năng tìm kiếm + hiệu ứng) =====
document.addEventListener('DOMContentLoaded', function () {
  const searchInput    = document.getElementById('searchInput');
  const priceContainer = document.getElementById('priceContainer');
  const noResults      = document.getElementById('noResults');
  const priceItems     = document.querySelectorAll('.price-item');

  function filterPrices() {
    const searchTerm = (searchInput.value || '').toLowerCase().trim();
    let visibleCount = 0;

    priceItems.forEach(item => {
      // Thuộc tính dữ liệu gợi ý (tuỳ HTML của bạn): data-magiong, data-tengiong, data-giaban, data-ngaycapnhat, data-xuatxu
      const ma     = (item.dataset.magiong      || '').toLowerCase();
      const ten    = (item.dataset.tengiong     || '').toLowerCase();
      const gia    = (item.dataset.giaban       || '').toLowerCase();
      const ngay   = (item.dataset.ngaycapnhat  || '').toLowerCase();
      const xuatxu = (item.dataset.xuatxu       || '').toLowerCase();
      const text   = (item.textContent          || '').toLowerCase(); // dự phòng khi thiếu data-*

      const match = !searchTerm
        || ma.includes(searchTerm)
        || ten.includes(searchTerm)
        || gia.includes(searchTerm)
        || ngay.includes(searchTerm)
        || xuatxu.includes(searchTerm)
        || text.includes(searchTerm);

      if (match) {
        item.style.display = 'block';
        visibleCount++;
      } else {
        item.style.display = 'none';
      }
    });

    if (visibleCount === 0 && searchTerm) {
      noResults.style.display = 'block';
      priceContainer.style.display = 'none';
    } else {
      noResults.style.display = 'none';
      priceContainer.style.display = 'flex';
      priceContainer.style.flexWrap = 'wrap';
      priceContainer.style.gap = '20px';
    }
  }

  // Tìm kiếm theo input
  if (searchInput) searchInput.addEventListener('input', filterPrices);

  // Hiệu ứng xuất hiện lần đầu (giống mẫu: opacity + translateY)
  priceItems.forEach((item, index) => {
    item.style.opacity = '0';
    item.style.transform = 'translateY(30px)';
    setTimeout(() => {
      item.style.transition = 'all 0.6s ease';
      item.style.opacity = '1';
      item.style.transform = 'translateY(0)';
    }, index * 120);
  });

  // Gọi lần đầu để set trạng thái noResults/Container
  filterPrices();
});
