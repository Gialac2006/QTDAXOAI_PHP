// Địa chỉ API — điều chỉnh port nếu bạn dùng 8080/8081
const API_URL = 'http://localhost:8081/QTDAXOAI/public/api/vungtrong.php';
let currentPage = 1, totalPages = 1;
let currentMode = 'add', currentMaVung = null;

document.addEventListener('DOMContentLoaded', () => {
  loadData();
  document.getElementById('vungForm').addEventListener('submit', handleFormSubmit);
  document.getElementById('searchInput').addEventListener('keypress', e => {
    if (e.key === 'Enter') searchVung();
  });
  document.getElementById('modal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal();
  });
});

// === GET dữ liệu ===
async function loadData(page = 1) {
  showLoading(true);
  try {
    const res = await fetch(`${API_URL}?page=${page}&limit=10`);
    const json = await res.json();
    if (json.success) {
      displayData(json.data);
      updatePagination(json.pagination);
      currentPage = page;
    } else {
      showAlert('Lỗi khi tải: ' + (json.error||''), 'error');
    }
  } catch {
    showAlert('Không kết nối được server', 'error');
  } finally {
    showLoading(false);
  }
}

// Hiển thị trong bảng
function displayData(data) {
  const tbl = document.getElementById('dataTable'),
        empty = document.getElementById('emptyState'),
        body = document.getElementById('tableBody');
  if (!data.length) {
    tbl.style.display = 'none'; empty.style.display = 'block'; return;
  }
  tbl.style.display = 'table'; empty.style.display = 'none';
  body.innerHTML = data.map(item => `
    <tr>
      <td><strong>${item.MaVung}</strong></td>
      <td>${item.TenVung}</td>
      <td>${item.DiaChi}</td>
      <td>${parseFloat(item.DienTich).toLocaleString()} ha</td>
      <td><span class="status-badge ${item.TinhTrang==='Hoạt động'?'status-active':'status-inactive'}">
            ${item.TinhTrang}
          </span></td>
      <td>${formatDate(item.NgayBatDau)}</td>
      <td>
        <button class="btn btn-warning" onclick="openModal('edit','${item.MaVung}')">Sửa</button>
        <button class="btn btn-danger"  onclick="deleteVung('${item.MaVung}')">Xóa</button>
      </td>
    </tr>
  `).join('');
}

// === Phân trang ===
function updatePagination(p) {
  totalPages = p.total_pages; currentPage = p.current_page;
  document.getElementById('pageInfo').textContent = `Trang ${currentPage} / ${totalPages}`;
  document.getElementById('prevBtn').disabled = currentPage<=1;
  document.getElementById('nextBtn').disabled = currentPage>=totalPages;
}
function changePage(dir) {
  const np = currentPage + dir;
  if (np>=1 && np<=totalPages) loadData(np);
}

// === Tìm kiếm ===
async function searchVung() {
  const term = document.getElementById('searchInput').value.trim();
  if (!term) return loadData();
  showLoading(true);
  try {
    const res = await fetch(`${API_URL}?MaVung=${encodeURIComponent(term)}`);
    const json = await res.json();
    if (json.success) {
      displayData(json.data);
      updatePagination({ current_page:1, total_pages:1, total:json.data.length, per_page:json.data.length });
    } else {
      showAlert('Không tìm thấy', 'error'); displayData([]);
    }
  } catch {
    showAlert('Lỗi tìm kiếm', 'error');
  } finally {
    showLoading(false);
  }
}

// === Modal & Form ===
function openModal(mode, id=null) {
  currentMode = mode; currentMaVung = id;
  const modal = document.getElementById('modal'),
        title = document.getElementById('modalTitle'),
        btn   = document.getElementById('submitBtn'),
        form  = document.getElementById('vungForm');
  if (mode==='add') {
    title.textContent = 'Thêm Vùng Trồng Mới';
    btn.textContent   = 'Thêm mới';
    btn.className     = 'btn btn-success';
    form.reset(); document.getElementById('MaVung').disabled = false;
  } else {
    title.textContent = 'Chỉnh Sửa Vùng Trồng';
    btn.textContent   = 'Cập nhật';
    btn.className     = 'btn btn-warning';
    document.getElementById('MaVung').disabled = true;
    loadVungData(id);
  }
  modal.style.display = 'block';
  setTimeout(()=> modal.style.opacity = '1', 10);
}
function closeModal() {
  const m = document.getElementById('modal');
  m.style.opacity = '0';
  setTimeout(()=> m.style.display = 'none', 300);
}
async function loadVungData(id) {
  try {
    const res  = await fetch(`${API_URL}?MaVung=${encodeURIComponent(id)}`);
    const json = await res.json();
    if (json.success && json.data.length) {
      const v = json.data[0];
      for (let k in v) {
        const el = document.getElementById(k);
        if (el) el.value = v[k];
      }
    }
  } catch { showAlert('Lỗi tải vùng', 'error'); }
}

// === POST / PUT ===
async function handleFormSubmit(e) {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(e.target).entries());
  // Basic validation
  if (!data.MaVung.trim()) { showAlert('Nhập mã vùng', 'error'); return; }
  const btn = document.getElementById('submitBtn'),
        txt = btn.textContent;
  btn.textContent = 'Đang xử lý...'; btn.disabled = true;
  try {
    let res;
    if (currentMode==='add') {
      res = await fetch(API_URL, {
        method:'POST',
        headers:{ 'Content-Type':'application/json' },
        body:JSON.stringify(data)
      });
    } else {
      res = await fetch(`${API_URL}?MaVung=${encodeURIComponent(currentMaVung)}`, {
        method:'PUT',
        headers:{ 'Content-Type':'application/json' },
        body:JSON.stringify(data)
      });
    }
    const json = await res.json();
    if (json.success) {
      showAlert(json.message, 'success');
      closeModal(); loadData(currentPage);
    } else {
      showAlert('Lỗi: '+(json.error||''), 'error');
    }
  } catch {
    showAlert('Không kết nối được', 'error');
  } finally {
    btn.textContent = txt; btn.disabled = false;
  }
}

// === DELETE ===
async function deleteVung(id) {
  if (!confirm('Xác nhận xóa vùng này?')) return;
  try {
    const res  = await fetch(`${API_URL}?MaVung=${encodeURIComponent(id)}`, { method:'DELETE' });
    const json = await res.json();
    if (json.success) {
      showAlert(json.message, 'success');
      loadData(currentPage);
    } else {
      showAlert('Lỗi xóa: '+(json.error||''), 'error');
    }
  } catch {
    showAlert('Không kết nối được', 'error');
  }
}

// === Helpers ===
function showLoading(on) {
  document.getElementById('loading').style.display = on ? 'block' : 'none';
}
function showAlert(msg, type='success') {
  const cont = document.getElementById('alertContainer'),
        div  = document.createElement('div');
  div.className = `alert alert-${type}`;
  div.textContent = msg;
  cont.appendChild(div);
  setTimeout(()=>{
    div.style.opacity = '0';
    setTimeout(()=> cont.removeChild(div), 300);
  }, 5000);
}
function formatDate(s) {
  if (!s) return '';
  const d = new Date(s);
  return d.toLocaleDateString('vi-VN');
}
