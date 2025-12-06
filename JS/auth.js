document.addEventListener('DOMContentLoaded', function(){
  // Toggle password visibility
  document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function(e){
      const target = document.querySelector(this.dataset.target);
      if (!target) return;
      if (target.type === 'password') { target.type = 'text'; this.textContent = 'Ẩn'; }
      else { target.type = 'password'; this.textContent = 'Hiện'; }
    });
  });

  // Register form validation: password match + length
  const regForm = document.querySelector('#register-form');
  if (regForm) {
    const pass = regForm.querySelector('#mat_khau');
    const pass2 = regForm.querySelector('#mat_khau_confirm');
    const submit = regForm.querySelector('button[type=submit]');
    const check = function(){
      let ok = true;
      if (!pass.value || pass.value.length < 6) ok = false;
      if (pass.value !== pass2.value) ok = false;
      submit.disabled = !ok;
    };
    pass.addEventListener('input', check);
    pass2.addEventListener('input', check);
    check();
    regForm.addEventListener('submit', function(e){
      // basic client-side guard (server still authoritative)
      if (pass.value.length < 6 || pass.value !== pass2.value) {
        e.preventDefault();
        alert('Mật khẩu không hợp lệ hoặc xác nhận chưa khớp.');
      }
    });
  }

  // Login form: focus first field
  const loginInput = document.querySelector('#tai_khoan');
  if (loginInput) loginInput.focus();
});
