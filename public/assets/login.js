document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
  var input = document.getElementById(btn.getAttribute('data-toggle-password'));
  btn.addEventListener('click', function () {
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? '\uD83D\uDE48' : '\uD83D\uDC41\uFE0F';
    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  });
});
