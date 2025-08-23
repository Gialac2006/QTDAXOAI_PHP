    </main>
  </div>
  <script>
    // Xác nhận xóa (áp dụng cho link có data-confirm)
    document.addEventListener('click', function(e){
      const a = e.target.closest('a[data-confirm]');
      if (!a) return;
      const msg = a.getAttribute('data-confirm') || 'Bạn chắc muốn xóa?';
      if (!confirm(msg)) e.preventDefault();
    });
  </script>
</body>
</html>
