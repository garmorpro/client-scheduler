document.addEventListener('DOMContentLoaded', () => {
  const deleteBtn = document.getElementById('deletePolicyBtn');
  if (!deleteBtn) return;

  function notify(icon, title, text) {
    if (typeof appNotify !== 'undefined') {
      appNotify({ icon, title, text });
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

    if (typeof appConfirm !== 'undefined') {
      appConfirm({ icon: 'warning', title: 'Delete this policy?', text: 'This cannot be undone.', confirmText: 'Delete', danger: true })
        .then(confirmed => { if (confirmed) run(); });
    } else if (confirm('Delete this policy? This cannot be undone.')) {
      run();
    }
  });
});
