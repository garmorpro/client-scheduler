document.addEventListener('DOMContentLoaded', () => {
  const modalEl = document.getElementById('policyModal');
  if (!modalEl) return;
  const modal = new bootstrap.Modal(modalEl);
  const form = document.getElementById('policyForm');
  const titleInput = document.getElementById('policy_title');
  const effectiveDateInput = document.getElementById('policy_effective_date');
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

  function openModal(mode, data) {
    form.reset();
    quill.setText('');
    idInput.value = '';

    if (mode === 'edit') {
      modalTitleEl.textContent = 'Edit Policy';
      idInput.value = data.policyId;
      titleInput.value = data.title;
      effectiveDateInput.value = data.effectiveDate || '';
      quill.clipboard.dangerouslyPasteHTML(data.content || '');
    } else {
      modalTitleEl.textContent = 'New Policy';
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
      notify('warning', 'Missing information', 'Please add a title and some content.');
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
          effective_date: effectiveDateInput.value || null,
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
