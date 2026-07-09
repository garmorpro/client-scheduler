document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const userId = btn.dataset.userId;
            const userName = btn.dataset.userName || 'this employee';

            const run = async () => {
                try {
                    const formData = new FormData();
                    formData.append('user_id', userId);
                    const res = await fetch('delete_user.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    if (data.success) {
                        location.reload();
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'error', title: 'Could not delete employee', text: data.error || 'Please try again.' });
                        } else {
                            alert('Error: ' + (data.error || 'Could not delete employee.'));
                        }
                    }
                } catch (err) {
                    console.error('Failed to delete employee', err);
                }
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Delete this employee?',
                    text: `This permanently deletes ${userName}'s account. This cannot be undone.`,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    confirmButtonColor: '#c0392b'
                }).then(result => { if (result.isConfirmed) run(); });
            } else if (confirm(`Delete ${userName}'s account? This cannot be undone.`)) {
                run();
            }
        });
    });
});
