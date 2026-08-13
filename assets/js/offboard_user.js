document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.offboard-user-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const userId = btn.dataset.userId;
            const userName = btn.dataset.userName || 'this employee';

            const run = async () => {
                try {
                    const formData = new FormData();
                    formData.append('user_id', userId);
                    const res = await fetch('offboard_user.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    if (data.success) {
                        if (typeof appNotify !== 'undefined') {
                            const parts = [`${userName} has been set to inactive`];
                            if (data.unassigned > 0) parts.push(`unassigned from ${data.unassigned} schedule ${data.unassigned === 1 ? 'entry' : 'entries'}`);
                            if (data.reports_cleared > 0) parts.push(`${data.reports_cleared} direct report${data.reports_cleared === 1 ? '' : 's'} reassigned`);
                            appNotify({
                                icon: 'success',
                                title: 'Employee offboarded',
                                text: parts.join(', ') + '.',
                                timer: 2200
                            });
                            setTimeout(() => location.reload(), 2200);
                        } else {
                            location.reload();
                        }
                    } else {
                        if (typeof appNotify !== 'undefined') {
                            appNotify({ icon: 'error', title: 'Could not offboard employee', text: data.error || 'Please try again.' });
                        } else {
                            alert('Error: ' + (data.error || 'Could not offboard employee.'));
                        }
                    }
                } catch (err) {
                    console.error('Failed to offboard employee', err);
                }
            };

            if (typeof appConfirm !== 'undefined') {
                appConfirm({
                    icon: 'warning',
                    title: 'Offboard this employee?',
                    text: `This deactivates <b>${userName}</b>'s login, unassigns them from every current schedule entry, and (if they're a manager) clears their direct reports. Their past hours and time-off history are kept. This can be undone later by editing their status back to Active.`,
                    confirmText: 'Yes, offboard',
                    confirmColor: '#c98a1f'
                }).then(confirmed => { if (confirmed) run(); });
            } else if (confirm(`Offboard ${userName}? This deactivates their login and unassigns their current schedule.`)) {
                run();
            }
        });
    });
});
