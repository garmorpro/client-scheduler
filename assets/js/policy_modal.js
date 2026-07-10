document.addEventListener('DOMContentLoaded', () => {
  const modalEl = document.getElementById('policyModal');
  if (!modalEl) return;
  const modal = new bootstrap.Modal(modalEl);
  const form = document.getElementById('policyForm');
  const sourceToggle = document.getElementById('policySourceToggle');
  const sourceTypeInput = document.getElementById('policy_source_type');
  const editorFields = document.getElementById('policyEditorFields');
  const pdfFields = document.getElementById('policyPdfFields');
  const docTypeField = document.getElementById('policy_doc_type_field');
  const docTypeSelect = document.getElementById('policy_doc_type');
  const titleInput = document.getElementById('policy_title');
  const titleLabel = document.getElementById('policy_title_label');
  const effectiveDateField = document.getElementById('policy_effective_date_field');
  const effectiveDateInput = document.getElementById('policy_effective_date');
  const memoFieldsRow = document.getElementById('policy_memo_fields');
  const memoToInput = document.getElementById('policy_memo_to');
  const memoFromInput = document.getElementById('policy_memo_from');
  const pdfFileInput = document.getElementById('policy_pdf_file');
  const pdfCurrentWrap = document.getElementById('policyPdfCurrent');
  const pdfCurrentName = document.getElementById('policyPdfCurrentName');
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

  function applySourceType(type) {
    sourceTypeInput.value = type;
    sourceToggle.querySelectorAll('button').forEach(b => b.classList.toggle('active', b.dataset.source === type));

    if (type === 'pdf') {
      editorFields.style.display = 'none';
      pdfFields.style.display = 'block';
      docTypeField.style.display = 'none';
      effectiveDateField.style.display = 'none';
      memoFieldsRow.style.display = 'none';
      titleLabel.textContent = 'Title';
    } else {
      editorFields.style.display = '';
      pdfFields.style.display = 'none';
      docTypeField.style.display = '';
      applyDocType(docTypeSelect.value);
    }
  }

  sourceToggle.addEventListener('click', (e) => {
    const btn = e.target.closest('button');
    if (!btn) return;
    applySourceType(btn.dataset.source);
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
    pdfCurrentWrap.style.display = 'none';
    pdfFileInput.required = false;

    if (mode === 'edit') {
      modalTitleEl.textContent = 'Edit Document';
      idInput.value = data.policyId;
      titleInput.value = data.title;
      effectiveDateInput.value = data.effectiveDate || '';
      docTypeSelect.value = data.docType || 'policy';
      memoToInput.value = data.memoTo || '';
      memoFromInput.value = data.memoFrom || '';

      if (data.sourceType === 'pdf') {
        pdfCurrentWrap.style.display = 'flex';
        pdfCurrentName.textContent = data.pdfOriginalName || 'Current file';
      } else {
        quill.clipboard.dangerouslyPasteHTML(data.content || '');
      }

      applySourceType(data.sourceType || 'editor');
    } else {
      modalTitleEl.textContent = 'New Document';
      docTypeSelect.value = 'policy';
      applySourceType('editor');
    }

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
        sourceType: editBtn.dataset.policySourceType,
        pdfOriginalName: editBtn.dataset.policyPdfName,
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
    const isPdf = sourceTypeInput.value === 'pdf';
    const isCreate = !idInput.value;

    if (!title) {
      notify('warning', 'Missing information', 'Please add a title.');
      return;
    }
    if (isPdf) {
      if (isCreate && !pdfFileInput.files.length) {
        notify('warning', 'Missing file', 'Please choose a PDF to upload.');
        return;
      }
    } else if (quill.getText().trim().length === 0) {
      notify('warning', 'Missing information', 'Please add some content.');
      return;
    }

    saveBtn.disabled = true;
    const originalLabel = saveBtn.textContent;
    saveBtn.textContent = 'Saving...';

    try {
      const formData = new FormData();
      formData.append('policy_id', idInput.value || '');
      formData.append('title', title);
      formData.append('source_type', sourceTypeInput.value);

      if (isPdf) {
        if (pdfFileInput.files.length) {
          formData.append('pdf_file', pdfFileInput.files[0]);
        }
      } else {
        formData.append('doc_type', docTypeSelect.value);
        formData.append('effective_date', effectiveDateInput.value || '');
        formData.append('memo_to', memoToInput.value.trim());
        formData.append('memo_from', memoFromInput.value.trim());
        formData.append('content', quill.root.innerHTML);
      }

      const res = await fetch('save_policy.php', { method: 'POST', body: formData });
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
