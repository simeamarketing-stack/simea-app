document.addEventListener('DOMContentLoaded', function () {
  // --- Báo cáo ca: làm nổi bật ô "chỉ tiêu bù" ngay khi phát hiện hụt sản lượng,
  //     trước cả khi người dùng bấm lưu.
  function toggleShortfall() {
    var output = document.querySelector('[name="output_qty"]');
    var target = document.querySelector('[name="target_qty"]');
    var box = document.querySelector('[data-catchup-box]');
    if (!output || !target || !box) {
      return;
    }
    var isShort = output.value !== '' && target.value !== '' && parseInt(output.value, 10) < parseInt(target.value, 10);
    box.classList.toggle('shortfall-active', isShort);
    box.querySelectorAll('input, textarea').forEach(function (el) { el.required = isShort; });
  }

  var outputEl = document.querySelector('[name="output_qty"]');
  var targetEl = document.querySelector('[name="target_qty"]');
  if (outputEl) { outputEl.addEventListener('input', toggleShortfall); }
  if (targetEl) { targetEl.addEventListener('input', toggleShortfall); }
  toggleShortfall();

  // --- Bảng báo cáo theo ngày: cột Lỗi = Thực tế − Thành phẩm, tính ngay khi gõ
  //     để người khai thấy sai sót trước khi lưu.
  function wireDefectCell(row) {
    var output = row.querySelector('.js-output');
    var finished = row.querySelector('.js-finished');
    var cell = row.querySelector('.js-defect');
    if (!output || !finished || !cell) {
      return;
    }
    function recalc() {
      var o = output.value === '' ? null : parseInt(output.value, 10);
      var f = finished.value === '' ? null : parseInt(finished.value, 10);
      if (o === null || f === null || isNaN(o) || isNaN(f)) {
        cell.textContent = '';
        cell.classList.remove('cell-risk');
        finished.setCustomValidity('');
        return;
      }
      if (f > o) {
        cell.textContent = 'sai';
        cell.classList.add('cell-risk');
        finished.setCustomValidity('Thành phẩm không thể lớn hơn số lượng thực tế.');
        return;
      }
      finished.setCustomValidity('');
      cell.textContent = (o - f).toLocaleString('vi-VN');
      cell.classList.toggle('cell-risk', o - f > 0);
    }
    output.addEventListener('input', recalc);
    finished.addEventListener('input', recalc);
  }

  document.querySelectorAll('.day-table tbody tr').forEach(wireDefectCell);
});
