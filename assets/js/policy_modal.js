document.addEventListener('DOMContentLoaded', () => {
  const modalEl = document.getElementById('policyModal');
  if (!modalEl) return;
  const modal = new bootstrap.Modal(modalEl);
  const form = document.getElementById('policyForm');
  const docTypeSelect = document.getElementById('policy_doc_type');
  const titleInput = document.getElementById('policy_title');
  const titleLabel = document.getElementById('policy_title_label');
  const effectiveDateField = document.getElementById('policy_effective_date_field');
  const effectiveDateInput = document.getElementById('policy_effective_date');
  const memoFieldsRow = document.getElementById('policy_memo_fields');
  const memoToInput = document.getElementById('policy_memo_to');
  const memoFromInput = document.getElementById('policy_memo_from');
  const idInput = document.getElementById('policy_id');
  const modalTitleEl = document.getElementById('policyModalTitle');
  const saveBtn = document.getElementById('policySaveBtn');

  const quill = new Quill('#policyQuillEditor', {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ header: [2, 3, false] }],
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link'],
        ['clean']
      ]
    }
  });

  function applyDocType(type) {
    if (type === 'memo') {
      titleLabel.textContent = 'Subject';
      titleInput.placeholder = 'e.g. 2026 Holiday Observances and Paid Time Off Policy';
      effectiveDateField.style.display = 'none';
      memoFieldsRow.style.display = 'grid';
    } else {
      titleLabel.textContent = 'Title';
      titleInput.placeholder = 'e.g. Remote Work Policy';
      effectiveDateField.style.display = '';
      memoFieldsRow.style.display = 'none';
    }
  }

  docTypeSelect.addEventListener('change', () => applyDocType(docTypeSelect.value));

  function openModal(mode, data) {
    form.reset();
    quill.setText('');
    idInput.value = '';

    if (mode === 'edit') {
      modalTitleEl.textContent = 'Edit Document';
      idInput.value = data.policyId;
      titleInput.value = data.title;
      effectiveDateInput.value = data.effectiveDate || '';
      docTypeSelect.value = data.docType || 'policy';
      memoToInput.value = data.memoTo || '';
      memoFromInput.value = data.memoFrom || '';
      quill.clipboard.dangerouslyPasteHTML(data.content || '');
    } else {
      modalTitleEl.textContent = 'New Document';
      docTypeSelect.value = 'policy';
    }

    applyDocType(docTypeSelect.value);
    modal.show();
  }

  const newBtn = document.getElementById('newPolicyBtn');
  if (newBtn) {
    newBtn.addEventListener('click', (e) => {
      e.preventDefault();
      openModal('create', {});
    });
  }

  const editBtn = document.getElementById('editPolicyBtn');
  if (editBtn) {
    editBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const seedEl = document.getElementById('editPolicyContentSeed');
      openModal('edit', {
        policyId: editBtn.dataset.policyId,
        title: editBtn.dataset.policyTitle,
        effectiveDate: editBtn.dataset.policyEffectiveDate,
        docType: editBtn.dataset.policyDocType,
        memoTo: editBtn.dataset.policyMemoTo,
        memoFrom: editBtn.dataset.policyMemoFrom,
        content: seedEl ? seedEl.innerHTML : ''
      });
    });
  }

  function notify(icon, title, text) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({ icon, title, text });
    } else {
      alert(`${title}${text ? ': ' + text : ''}`);
    }
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const title = titleInput.value.trim();
    const content = quill.root.innerHTML;
    const isEmpty = quill.getText().trim().length === 0;
    if (!title || isEmpty) {
      notify('warning', 'Missing information', 'Please add a title/subject and some content.');
      return;
    }

    saveBtn.disabled = true;
    const originalLabel = saveBtn.textContent;
    saveBtn.textContent = 'Saving...';

    try {
      const res = await fetch('save_policy.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          policy_id: idInput.value || null,
          title,
          doc_type: docTypeSelect.value,
          effective_date: effectiveDateInput.value || null,
          memo_to: memoToInput.value.trim() || null,
          memo_from: memoFromInput.value.trim() || null,
          content
        })
      });
      const data = await res.json().catch(() => null);

      if (!res.ok || !data || !data.success) {
        throw new Error((data && data.error) || 'Please try again.');
      }

      window.location.href = `policy.php?id=${encodeURIComponent(data.policy_id)}`;
    } catch (error) {
      console.error('Failed to save policy', error);
      notify('error', 'Could not save policy', error.message);
      saveBtn.disabled = false;
      saveBtn.textContent = originalLabel;
    }
  });
});
