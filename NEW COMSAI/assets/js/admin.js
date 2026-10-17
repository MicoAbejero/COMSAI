(() => {
  const toggle = document.querySelector(".menu-toggle");
  const sidebar = document.querySelector(".admin-sidebar");
  if (toggle && sidebar)
    toggle.addEventListener("click", () => {
      const expanded = toggle.getAttribute("aria-expanded") === "true";
      toggle.setAttribute("aria-expanded", String(!expanded));
      sidebar.classList.toggle("open", !expanded);
    });
    const passwordToggle = document.querySelector('.toggle-password');
    if (passwordToggle) passwordToggle.addEventListener('click', () => {
      const input = document.querySelector('#password');
      const visible = input.type === 'password';
      input.type = visible ? 'text' : 'password';
      passwordToggle.textContent = visible ? 'Hide' : 'Show';
      passwordToggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
    });
  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
  });
})();
