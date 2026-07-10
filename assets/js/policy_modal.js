document.addEventListener('DOMContentLoaded', () => {
  const modalEl = document.getElementById('policyModal');
  if (!modalEl) return;
  const modal = new bootstrap.Modal(modalEl);
  const form = document.getElementById('policyForm');
  const titleInput = document.getElementById('policy_title');
  const pdfFileInput = document.getElementById('policy_pdf_file');
  const pdfCurrentWrap = document.getElementById('policyPdfCurrent');
  const pdfCurrentName = document.getElementById('policyPdfCurrentName');
  const idInput = document.getElementById('policy_id');
  const modalTitleEl = document.getElementById('policyModalTitle');
  const saveBtn = document.getElementById('policySaveBtn');

  function openModal(mode, data) {
    form.reset();
    idInput.value = '';
    pdfCurrentWrap.style.display = 'none';

    if (mode === 'edit') {
      modalTitleEl.textContent = 'Edit Policy';
      idInput.value = data.policyId;
      titleInput.value = data.title;
      pdfCurrentWrap.style.display = 'flex';
      pdfCurrentName.textContent = data.pdfOriginalName || 'Current file';
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
      openModal('edit', {
        policyId: editBtn.dataset.policyId,
        title: editBtn.dataset.policyTitle,
        pdfOriginalName: editBtn.dataset.policyPdfName
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

  const indexWrap = document.querySelector('.indexWrap');
  if (indexWrap) {
    indexWrap.addEventListener('click', (e) => {
      const btn = e.target.closest('.indexDeleteBtn');
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();

      const policyId = btn.dataset.policyId;
      async function run() {
        try {
          const res = await fetch('delete_policy.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ policy_id: policyId })
          });
          const data = await res.json().catch(() => null);
          if (!res.ok || !data || !data.success) {
            throw new Error((data && data.error) || 'Please try again.');
          }
          location.reload();
        } catch (error) {
          console.error('Failed to delete policy', error);
          notify('error', 'Could not delete policy', error.message);
        }
      }

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'warning', title: 'Delete this policy?',
          text: 'This cannot be undone.',
          showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#c0392b'
        }).then(result => { if (result.isConfirmed) run(); });
      } else if (confirm('Delete this policy? This cannot be undone.')) {
        run();
      }
    });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const title = titleInput.value.trim();
    const isCreate = !idInput.value;

    if (!title) {
      notify('warning', 'Missing information', 'Please add a title.');
      return;
    }
    if (isCreate && !pdfFileInput.files.length) {
      notify('warning', 'Missing file', 'Please choose a PDF to upload.');
      return;
    }

    saveBtn.disabled = true;
    const originalLabel = saveBtn.textContent;
    saveBtn.textContent = 'Saving...';

    try {
      const formData = new FormData();
      formData.append('policy_id', idInput.value || '');
      formData.append('title', title);
      if (pdfFileInput.files.length) {
        formData.append('pdf_file', pdfFileInput.files[0]);
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
