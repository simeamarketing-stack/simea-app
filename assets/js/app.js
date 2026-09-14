document.addEventListener('DOMContentLoaded', function () {
  // Báo cáo ca: auto-fill suggested target when an order is picked, and make
  // the "chỉ tiêu bù" box visually obvious + required the moment a shortfall
  // is detected, before the user even tries to submit.
  var orderSelect = document.querySelector('[data-order-select]');
  var targetInput = document.querySelector('[name="target_qty"]');
  if (orderSelect && targetInput) {
    orderSelect.addEventListener('change', function () {
      var opt = orderSelect.options[orderSelect.selectedIndex];
      var suggested = opt.getAttribute('data-suggested-target');
      if (suggested && !targetInput.value) {
        targetInput.value = suggested;
      }
    });
  }

  function toggleShortfall() {
    var output = document.querySelector('[name="output_qty"]');
    var target = document.querySelector('[name="target_qty"]');
    var box = document.querySelector('[data-catchup-box]');
    if (!output || !target || !box) {
      return;
    }
    var isShort = output.value !== '' && target.value !== '' && parseInt(output.value, 10) < parseInt(target.value, 10);
    box.classList.toggle('shortfall-active', isShort);
    var catchupInputs = box.querySelectorAll('input, textarea');
    catchupInputs.forEach(function (el) { el.required = isShort; });
  }

  var outputEl = document.querySelector('[name="output_qty"]');
  var targetEl = document.querySelector('[name="target_qty"]');
  if (outputEl) { outputEl.addEventListener('input', toggleShortfall); }
  if (targetEl) { targetEl.addEventListener('input', toggleShortfall); }
  toggleShortfall();
});
