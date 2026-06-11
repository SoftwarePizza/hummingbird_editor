document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.hbe-faq__question').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var item = btn.closest('.hbe-faq__item');
      var isOpen = item.classList.contains('hbe-faq__item--open');
      item.classList.toggle('hbe-faq__item--open', !isOpen);
      btn.setAttribute('aria-expanded', String(!isOpen));
    });
  });
});
