(() => {
  const toggle = document.querySelector(".menu-toggle");
  const sidebar = document.querySelector(".admin-sidebar");
  document.querySelectorAll('.admin-sidebar .nav-link.active').forEach((link) => {
    link.addEventListener('click', (event) => {
      if (event.button === 0 && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey) {
        event.preventDefault();
      }
    });
  });
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

  const draftPagePrefix = `comsai-admin-draft:${location.pathname}:`;
  const adminForms = [...document.querySelectorAll('.admin-form form')];
  const draftForms = adminForms.filter((form) => !form.querySelector('input[type="password"]'));
  const formDraftKeys = new Map();
  const draftableFields = (form) => [...form.querySelectorAll('input, textarea, select')].filter((field) =>
    field.name &&
    field.type !== 'hidden' &&
    field.type !== 'password' &&
    field.type !== 'file' &&
    !/csrf|password|email_code|token/i.test(field.name)
  );
  const clearPageDrafts = () => {
    try {
      Object.keys(sessionStorage).filter((key) => key.startsWith(draftPagePrefix)).forEach((key) => sessionStorage.removeItem(key));
    } catch {}
  };

  if (new URLSearchParams(location.search).has('saved')) {
    clearPageDrafts();
  } else {
    draftForms.forEach((form) => {
      const editId = new URLSearchParams(location.search).get('edit') || 'new';
      const formIndex = adminForms.indexOf(form);
      const key = `${draftPagePrefix}${editId}:${formIndex}`;
      formDraftKeys.set(form, key);
      try {
        const saved = JSON.parse(sessionStorage.getItem(key) || '{}');
        draftableFields(form).forEach((field) => {
          if (Object.hasOwn(saved, field.name)) field.value = saved[field.name];
        });
      } catch {}

      const saveDraft = () => {
        const saved = {};
        draftableFields(form).forEach((field) => { saved[field.name] = field.value; });
        try { sessionStorage.setItem(key, JSON.stringify(saved)); } catch {}
      };
      form.addEventListener('input', saveDraft);
      form.addEventListener('change', saveDraft);
    });
  }

  document.querySelectorAll('a[data-confirm]').forEach((link) => {
    if (!link.textContent.trim().toLocaleLowerCase().startsWith('cancel')) return;
    link.addEventListener('click', (event) => {
      if (event.defaultPrevented) return;
      const form = link.closest('.admin-form')?.querySelector('form');
      const key = form && formDraftKeys.get(form);
      if (key) {
        try { sessionStorage.removeItem(key); } catch {}
      }
    });
  });

  const searchForm = document.querySelector('[data-knowledge-search]');
  const searchInput = searchForm?.querySelector('#q');
  const statusFilter = searchForm?.querySelector('#filter-status');
  const categoryFilter = searchForm?.querySelector('#filter-category');
  const entryRows = [...document.querySelectorAll('[data-knowledge-row]')];
  const noResults = document.querySelector('#knowledge-no-results');
  if (searchForm && searchInput && statusFilter && categoryFilter && noResults) {
    const filterEntries = () => {
      const query = searchInput.value.trim().toLocaleLowerCase();
      const status = statusFilter.value.toLocaleLowerCase();
      const category = categoryFilter.value;
      let visibleCount = 0;
      entryRows.forEach((row) => {
        const matchesText = row.textContent.toLocaleLowerCase().includes(query);
        const matchesStatus = !status || row.cells[2]?.textContent.trim().toLocaleLowerCase() === status;
        const matchesCategory = !category || row.dataset.categoryId === category;
        row.hidden = !(matchesText && matchesStatus && matchesCategory);
        if (!row.hidden) visibleCount += 1;
      });
      noResults.hidden = visibleCount > 0;
    };
    searchInput.addEventListener('input', filterEntries);
    statusFilter.addEventListener('change', filterEntries);
    categoryFilter.addEventListener('change', filterEntries);
    filterEntries();
  }
})();
