(() => {
  const syncPanelFilters = (fields, hiddenNames, linkSelector) => {
    const params = new URLSearchParams(location.search);
    fields.forEach(([key, field]) => {
      if (field.value) params.set(key, field.value); else params.delete(key);
    });
    params.delete('saved');
    const query = params.toString();
    history.replaceState(null, '', `${location.pathname}${query ? `?${query}` : ''}${location.hash}`);
    Object.entries(hiddenNames).forEach(([key, name]) => {
      document.querySelectorAll(`input[name="${name}"]`).forEach((input) => { input.value = fields.find(([fieldKey]) => fieldKey === key)?.[1].value || ''; });
    });
    document.querySelectorAll(linkSelector).forEach((link) => {
      const href = new URL(link.href, location.href);
      fields.forEach(([key, field]) => {
        if (field.value) href.searchParams.set(key, field.value); else href.searchParams.delete(key);
      });
      link.href = href.pathname + href.search;
    });
  };
  const toggle = document.querySelector(".menu-toggle");
  const sidebar = document.querySelector(".admin-sidebar");
  const panelLinks = [...document.querySelectorAll('.admin-sidebar a.nav-link[href]')];
  const panelStateKey = (url) => `comsai-admin-panel:${url.pathname}`;
  panelLinks.forEach((link) => {
    const target = new URL(link.href, location.href);
    if (target.origin !== location.origin || target.pathname === location.pathname) return;
    try {
      const remembered = sessionStorage.getItem(panelStateKey(target));
      if (remembered) link.href = remembered;
    } catch {}
  });
  document.querySelectorAll('.admin-sidebar a.nav-link[href]').forEach((link) => {
    link.addEventListener('click', (event) => {
      if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
      const target = new URL(link.href, location.href);
      if (target.origin !== location.origin || target.pathname === location.pathname) {
        if (target.pathname === location.pathname) event.preventDefault();
        return;
      }
      try { sessionStorage.setItem(panelStateKey(location), location.href); } catch {}
    });
  });
  if (toggle && sidebar)
    toggle.addEventListener("click", () => {
      const expanded = toggle.getAttribute("aria-expanded") === "true";
      toggle.setAttribute("aria-expanded", String(!expanded));
      sidebar.classList.toggle("open", !expanded);
    });
    document.querySelectorAll('.toggle-password').forEach((passwordToggle) => {
      passwordToggle.addEventListener('click', () => {
        const input = document.getElementById(passwordToggle.getAttribute('aria-controls')) || document.querySelector('#password');
        if (!input) return;
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        passwordToggle.textContent = visible ? 'Hide' : 'Show';
        const fieldName = input.labels?.[0]?.textContent?.trim().toLocaleLowerCase() || 'password';
        passwordToggle.setAttribute('aria-label', `${visible ? 'Hide' : 'Show'} ${fieldName}`);
      });
    });
  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
  });
  document.querySelectorAll('form[data-student-interface-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const button = event.submitter || form.querySelector('button[type="submit"]');
      const status = document.querySelector('[data-interface-status]');
      const forms = [...document.querySelectorAll('form[data-student-interface-form]')];
      if (!button || !status) return;
      const action = button.value;
      forms.forEach((item) => { item.querySelector('button').disabled = true; });
      status.textContent = 'Updating…';
      try {
        const data = new FormData(form);
        data.set('action', action);
        const response = await fetch(form.getAttribute('action'), { method: 'POST', body: data, headers: { Accept: 'application/json' } });
        const payload = await response.json();
        if (!response.ok || !payload.ok) throw new Error(payload.error || 'Could not update Student Interface.');
        status.textContent = payload.enabled ? 'Enabled' : 'Disabled';
        forms.forEach((item) => {
          const actionButton = item.querySelector('button');
          actionButton.disabled = (actionButton.value === 'enable') === payload.enabled;
        });
      } catch (error) {
        status.textContent = error.message || 'Could not update Student Interface.';
        forms.forEach((item) => { item.querySelector('button').disabled = false; });
      }
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
    const syncKnowledgeState = () => {
      const params = new URLSearchParams(location.search);
      [['q', searchInput.value], ['status', statusFilter.value], ['category_id', categoryFilter.value]].forEach(([key, value]) => {
        if (value) params.set(key, value); else params.delete(key);
      });
      params.delete('saved');
      const query = params.toString();
      history.replaceState(null, '', `${location.pathname}${query ? `?${query}` : ''}${location.hash}`);
      document.querySelectorAll('input[name="return_q"]').forEach((field) => { field.value = searchInput.value; });
      document.querySelectorAll('input[name="return_status"]').forEach((field) => { field.value = statusFilter.value; });
      document.querySelectorAll('input[name="return_category_id"]').forEach((field) => { field.value = categoryFilter.value; });
      document.querySelectorAll('[data-knowledge-edit], #knowledge-cancel').forEach((link) => {
        const href = new URL(link.href, location.href);
        ['q', 'status', 'category_id'].forEach((key) => {
          const value = params.get(key);
          if (value) href.searchParams.set(key, value); else href.searchParams.delete(key);
        });
        link.href = href.pathname + href.search;
      });
    };
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
    searchInput.addEventListener('input', () => { filterEntries(); syncKnowledgeState(); });
    statusFilter.addEventListener('change', () => { filterEntries(); syncKnowledgeState(); });
    categoryFilter.addEventListener('change', () => { filterEntries(); syncKnowledgeState(); });
    filterEntries();
    syncKnowledgeState();
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
  if (announcementSearchInput && announcementStatusFilter) {
    const syncAnnouncementFilters = () => syncPanelFilters(
      [['q', announcementSearchInput], ['status', announcementStatusFilter]],
      { q: 'return_q', status: 'return_status' },
      '[data-announcement-edit], #announcement-cancel'
    );
    announcementSearchInput.addEventListener('input', syncAnnouncementFilters);
    announcementStatusFilter.addEventListener('change', syncAnnouncementFilters);
    syncAnnouncementFilters();
  }

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
  if (categorySearchInput) {
    const syncCategoryFilters = () => syncPanelFilters(
      [['q', categorySearchInput]],
      { q: 'return_q' },
      '[data-category-edit], #category-cancel'
    );
    categorySearchInput.addEventListener('input', syncCategoryFilters);
    syncCategoryFilters();
  }

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
