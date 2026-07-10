document.addEventListener('DOMContentLoaded', () => {
  const downloadBtn = document.getElementById('downloadPolicyPdfBtn');
  if (downloadBtn) {
    downloadBtn.addEventListener('click', () => window.print());
  }

  const deleteBtn = document.getElementById('deletePolicyBtn');
  if (!deleteBtn) return;

  function notify(icon, title, text) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({ icon, title, text });
    } else {
      alert(`${title}${text ? ': ' + text : ''}`);
    }
  }

  deleteBtn.addEventListener('click', () => {
    const policyId = deleteBtn.dataset.policyId;

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
        window.location.href = 'policies.php';
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
});
