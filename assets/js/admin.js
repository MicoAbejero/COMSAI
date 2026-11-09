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

  const publicationSelect = document.querySelector('#status[name="status"]');
  const knowledgeSaveButton = document.querySelector('#knowledge-save-button');
  if (publicationSelect && knowledgeSaveButton) {
    const updateKnowledgeSaveLabel = () => {
      knowledgeSaveButton.textContent = publicationSelect.value === 'published' ? 'Publish' : 'Save draft';
    };
    publicationSelect.addEventListener('change', updateKnowledgeSaveLabel);
    updateKnowledgeSaveLabel();
  }
  const announcementSaveButton = document.querySelector('#announcement-save-button');
  if (publicationSelect && announcementSaveButton) {
    const updateAnnouncementSaveLabel = () => {
      announcementSaveButton.textContent = publicationSelect.value === 'published' ? 'Publish' : 'Save draft';
    };
    publicationSelect.addEventListener('change', updateAnnouncementSaveLabel);
    updateAnnouncementSaveLabel();
  }

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
        const statusCellIndex = row.querySelector('input[name="ids[]"]') ? 3 : 2;
        const matchesStatus = !status || row.cells[statusCellIndex]?.textContent.trim().toLocaleLowerCase() === status;
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

  const bulkForm = document.querySelector('#knowledge-bulk-form');
  const selectionBoxes = [...document.querySelectorAll('input[name="ids[]"]')];
  const selectAllButton = document.querySelector('#knowledge-select-all');
  const selectionCount = document.querySelector('#knowledge-selection-count');
  const updateSelectionControls = () => {
    const selectedCount = selectionBoxes.filter((box) => box.checked).length;
    const visibleBoxes = selectionBoxes.filter((box) => !box.closest('tr').hidden);
    const allVisibleSelected = visibleBoxes.length > 0 && visibleBoxes.every((box) => box.checked);
    if (selectionCount) selectionCount.textContent = `${selectedCount} selected`;
    if (selectAllButton) {
      selectAllButton.textContent = allVisibleSelected
        ? 'Unselect all'
        : 'Select all';
    }
  };
  selectionBoxes.forEach((box) => box.addEventListener('change', updateSelectionControls));
  searchInput?.addEventListener('input', updateSelectionControls);
  statusFilter?.addEventListener('change', updateSelectionControls);
  categoryFilter?.addEventListener('change', updateSelectionControls);
  selectAllButton?.addEventListener('click', () => {
    const visibleBoxes = selectionBoxes.filter((box) => !box.closest('tr').hidden);
    const selectAll = visibleBoxes.some((box) => !box.checked);
    visibleBoxes.forEach((box) => {
      box.checked = selectAll;
    });
    updateSelectionControls();
  });
  updateSelectionControls();
  bulkForm?.addEventListener('submit', (event) => {
    const selectedCount = selectionBoxes.filter((box) => box.checked).length;
    if (!selectedCount) {
      event.preventDefault();
      window.alert('Select at least one entry.');
      return;
    }
    const action = bulkForm.querySelector('#knowledge-bulk-action')?.value;
    const actionMessages = {
      bulk_publish: 'Publish the selected information? Students will be able to see it in COMSAI.',
      bulk_unpublish: 'Unpublish the selected information? Students will no longer see it in COMSAI.',
      bulk_delete: 'Delete the selected information? This cannot be undone.',
    };
    const actionText = actionMessages[action];
    if (!actionText) {
      event.preventDefault();
      window.alert('Choose a bulk action.');
    } else if (!window.confirm(`${actionText} (${selectedCount} selected)`)) {
      event.preventDefault();
    }
  });

  const announcementSearch = document.querySelector('[data-announcement-search]');
  const announcementSearchInput = announcementSearch?.querySelector('#q');
  const announcementStatusFilter = announcementSearch?.querySelector('#filter-status');
  const announcementRows = [...document.querySelectorAll('[data-announcement-row]')];
  const announcementNoResults = document.querySelector('#announcement-no-results');
  const filterAnnouncements = () => {
    if (!announcementSearchInput || !announcementStatusFilter || !announcementNoResults) return;
    const query = announcementSearchInput.value.trim().toLocaleLowerCase();
    const status = announcementStatusFilter.value.toLocaleLowerCase();
    let visibleCount = 0;
    announcementRows.forEach((row) => {
      const matchesText = row.textContent.toLocaleLowerCase().includes(query);
      const matchesStatus = !status || row.querySelector('.status')?.textContent.trim().toLocaleLowerCase() === status;
      row.hidden = !(matchesText && matchesStatus);
      if (!row.hidden) visibleCount += 1;
    });
    announcementNoResults.hidden = visibleCount > 0;
  };
  announcementSearchInput?.addEventListener('input', filterAnnouncements);
  announcementStatusFilter?.addEventListener('change', filterAnnouncements);
  filterAnnouncements();

  const announcementBulkForm = document.querySelector('#announcement-bulk-form');
  const announcementBoxes = [...document.querySelectorAll('#announcement-bulk-form ~ .table-wrap input[name="ids[]"], input[name="ids[]"][form="announcement-bulk-form"]')];
  const announcementSelectAll = document.querySelector('#announcement-select-all');
  const announcementSelectionCount = document.querySelector('#announcement-selection-count');
  const updateAnnouncementSelection = () => {
    const selectedCount = announcementBoxes.filter((box) => box.checked).length;
    const visibleBoxes = announcementBoxes.filter((box) => !box.closest('tr')?.hidden);
    const allVisibleSelected = visibleBoxes.length > 0 && visibleBoxes.every((box) => box.checked);
    if (announcementSelectionCount) announcementSelectionCount.textContent = `${selectedCount} selected`;
    if (announcementSelectAll) announcementSelectAll.textContent = allVisibleSelected ? 'Unselect all' : 'Select all';
  };
  announcementBoxes.forEach((box) => box.addEventListener('change', updateAnnouncementSelection));
  announcementSearchInput?.addEventListener('input', updateAnnouncementSelection);
  announcementStatusFilter?.addEventListener('change', updateAnnouncementSelection);
  announcementSelectAll?.addEventListener('click', () => {
    const visibleBoxes = announcementBoxes.filter((box) => !box.closest('tr')?.hidden);
    const selectAll = visibleBoxes.some((box) => !box.checked);
    visibleBoxes.forEach((box) => { box.checked = selectAll; });
    updateAnnouncementSelection();
  });
  updateAnnouncementSelection();
  announcementBulkForm?.addEventListener('submit', (event) => {
    const selectedCount = announcementBoxes.filter((box) => box.checked).length;
    if (!selectedCount) {
      event.preventDefault();
      window.alert('Select at least one announcement.');
      return;
    }
    const action = announcementBulkForm.querySelector('#announcement-bulk-action')?.value;
    const actionMessages = {
      bulk_publish: 'Publish the selected announcements? Students will be able to see them in COMSAI.',
      bulk_unpublish: 'Unpublish the selected announcements? Students will no longer see them in COMSAI.',
      bulk_delete: 'Delete the selected announcements? This cannot be undone.',
    };
    const actionText = actionMessages[action];
    if (!actionText) {
      event.preventDefault();
      window.alert('Choose a bulk action.');
    } else if (!window.confirm(`${actionText} (${selectedCount} selected)`)) {
      event.preventDefault();
    }
  });

  const categorySearch = document.querySelector('[data-category-search]');
  const categorySearchInput = categorySearch?.querySelector('#category-q');
  const categoryRows = [...document.querySelectorAll('[data-category-row]')];
  const categoryNoResults = document.querySelector('#category-no-results');
  const filterCategories = () => {
    if (!categorySearchInput || !categoryNoResults) return;
    const query = categorySearchInput.value.trim().toLocaleLowerCase();
    let visibleCount = 0;
    categoryRows.forEach((row) => {
      row.hidden = !row.textContent.toLocaleLowerCase().includes(query);
      if (!row.hidden) visibleCount += 1;
    });
    categoryNoResults.hidden = visibleCount > 0;
  };
  categorySearchInput?.addEventListener('input', filterCategories);
  filterCategories();

  const categoryBulkForm = document.querySelector('#category-bulk-form');
  const categoryBoxes = [...document.querySelectorAll('input[name="ids[]"][form="category-bulk-form"]')];
  const categorySelectAll = document.querySelector('#category-select-all');
  const categorySelectionCount = document.querySelector('#category-selection-count');
  const updateCategorySelection = () => {
    const selectedCount = categoryBoxes.filter((box) => box.checked).length;
    const visibleBoxes = categoryBoxes.filter((box) => !box.disabled && !box.closest('tr')?.hidden);
    const allVisibleSelected = visibleBoxes.length > 0 && visibleBoxes.every((box) => box.checked);
    if (categorySelectionCount) categorySelectionCount.textContent = `${selectedCount} selected`;
    if (categorySelectAll) categorySelectAll.textContent = allVisibleSelected ? 'Unselect all' : 'Select all';
  };
  categoryBoxes.forEach((box) => box.addEventListener('change', updateCategorySelection));
  categorySearchInput?.addEventListener('input', updateCategorySelection);
  categorySelectAll?.addEventListener('click', () => {
    const visibleBoxes = categoryBoxes.filter((box) => !box.disabled && !box.closest('tr')?.hidden);
    const selectAll = visibleBoxes.some((box) => !box.checked);
    visibleBoxes.forEach((box) => { box.checked = selectAll; });
    updateCategorySelection();
  });
  updateCategorySelection();
  categoryBulkForm?.addEventListener('submit', (event) => {
    const selectedCount = categoryBoxes.filter((box) => box.checked).length;
    if (!selectedCount) {
      event.preventDefault();
      window.alert('Select at least one category.');
    } else if (!window.confirm(`Delete the selected empty categories? This cannot be undone. (${selectedCount} selected)`)) {
      event.preventDefault();
    }
  });
})();
