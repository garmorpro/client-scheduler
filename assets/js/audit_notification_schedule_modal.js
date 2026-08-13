document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('auditNotificationScheduleModal');
    const openBtn = document.getElementById('configureAuditNotificationBtn');
    const form = document.getElementById('auditNotificationScheduleForm');
    if (!modalEl || !openBtn || !form) return;

    const enabledCb = document.getElementById('auditNotifEnabled');
    const timeInput = document.getElementById('auditNotifTime');
    const statusBanner = document.getElementById('auditNotifCrontabStatus');
    const saveHint = document.getElementById('auditNotifSaveHint');
    const saveBtn = document.getElementById('auditNotifSaveBtn');

    function pad(n) { return String(n).padStart(2, '0'); }

    async function loadSchedule() {
        statusBanner.style.display = 'none';
        try {
            const res = await fetch('get-audit-notification-schedule.php');
            const data = await res.json();
            if (!data.success) return;

            enabledCb.checked = !!data.enabled;
            timeInput.value = `${pad(data.hour)}:${pad(data.minute)}`;

            statusBanner.style.display = 'flex';
            statusBanner.innerHTML = data.installed_in_crontab
                ? '<i class="bi bi-check2-circle"></i><span>Currently scheduled in the server crontab.</span>'
                : '<i class="bi bi-exclamation-circle" style="color:#d99a2b;"></i><span>Not currently in the server crontab — save to install it.</span>';
        } catch (err) {
            console.error('Failed to load audit notification schedule', err);
        }
    }

    openBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        loadSchedule();
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const [hourStr, minuteStr] = (timeInput.value || '08:00').split(':');
        // Round to the nearest quarter-hour, matching the crontab's actual
        // granularity — a raw native time picker can return any minute.
        const rawMinute = parseInt(minuteStr, 10) || 0;
        const roundedMinute = Math.round(rawMinute / 15) * 15;
        const hour = roundedMinute === 60 ? (parseInt(hourStr, 10) + 1) % 24 : parseInt(hourStr, 10);
        const minute = roundedMinute % 60;

        saveBtn.disabled = true;
        saveHint.textContent = 'Saving…';

        try {
            const res = await fetch('update-audit-notification-schedule.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ enabled: enabledCb.checked, hour, minute })
            });
            const data = await res.json();

            saveBtn.disabled = false;

            if (!data.success) {
                saveHint.textContent = '';
                Swal.fire({ icon: 'error', title: 'Could not save schedule', text: data.error || 'Please try again.' });
                return;
            }

            saveHint.textContent = '';
            Swal.fire({
                icon: 'success',
                title: enabledCb.checked ? 'Schedule saved' : 'Digest disabled',
                text: enabledCb.checked
                    ? `Crontab updated (runs as OS user "${data.os_user}").`
                    : 'Removed from the server crontab.',
                timer: 2200,
            });
            loadSchedule();
        } catch (err) {
            saveBtn.disabled = false;
            saveHint.textContent = '';
            console.error('Failed to save audit notification schedule', err);
            Swal.fire({ icon: 'error', title: 'Could not save schedule', text: 'Please try again.' });
        }
    });
});
