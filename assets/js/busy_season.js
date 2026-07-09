document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('busySeasonForm');
    const modalEl = document.getElementById('busySeasonModal');
    if (!form || !modalEl) return;

    const modal = new bootstrap.Modal(modalEl);

    function notify(icon, title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon, title, text });
        } else {
            alert(title + (text ? ': ' + text : ''));
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const startDate = document.getElementById('bs_start_date').value;
        const endDate = document.getElementById('bs_end_date').value;

        if ((startDate && !endDate) || (!startDate && endDate)) {
            notify('warning', 'Both dates required', 'Set a start and end date, or leave both blank to turn Busy Season off.');
            return;
        }
        if (startDate && endDate && endDate < startDate) {
            notify('warning', 'Invalid date range', 'End date must be on or after the start date.');
            return;
        }

        const submitBtn = form.querySelector('.eng-edit-btn-save');
        submitBtn.disabled = true;
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Saving...';

        try {
            const res = await fetch('settings_backend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    setting_master_key: 'busy_season',
                    settings: { start_date: startDate, end_date: endDate }
                })
            });
            const result = await res.json();
            if (result.success) {
                modal.hide();
                location.reload();
            } else {
                notify('error', 'Could not save Busy Season', result.error || 'Please try again.');
            }
        } catch (err) {
            console.error('Failed to save busy season settings', err);
            notify('error', 'Request failed', String(err));
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
});
