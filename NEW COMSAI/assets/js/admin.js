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
  document.querySelectorAll('a[data-confirm]').forEach((link) => {
    link.addEventListener('click', (event) => {
      if (!window.confirm(link.dataset.confirm)) event.preventDefault();
    });
  });

  const searchForm = document.querySelector('[data-knowledge-search]');
  const searchInput = searchForm?.querySelector('#q');
  const statusFilter = searchForm?.querySelector('#filter-status');
  const entryRows = [...document.querySelectorAll('[data-knowledge-row]')];
  const noResults = document.querySelector('#knowledge-no-results');
  if (searchForm && searchInput && statusFilter && noResults) {
    const filterEntries = () => {
      const query = searchInput.value.trim().toLocaleLowerCase();
      const status = statusFilter.value.toLocaleLowerCase();
      let visibleCount = 0;
      entryRows.forEach((row) => {
        const matchesText = row.textContent.toLocaleLowerCase().includes(query);
        const matchesStatus = !status || row.cells[2]?.textContent.trim().toLocaleLowerCase() === status;
        row.hidden = !(matchesText && matchesStatus);
        if (!row.hidden) visibleCount += 1;
      });
      noResults.hidden = visibleCount > 0;
    };
    searchInput.addEventListener('input', filterEntries);
    statusFilter.addEventListener('change', filterEntries);
    filterEntries();
  }
})();
