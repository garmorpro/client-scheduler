document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('weekAvailabilityModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    const subtitleEl = document.getElementById('waSubtitle');
    const listEl = document.getElementById('waList');

    const palette = ['#4f8ef7', '#9b6bd6', '#4fbf9f', '#e0994c', '#5fb85f', '#5aa8d6', '#d67aa8', '#7a8fd6'];
    function hashColor(name) {
        let hash = 0;
        for (let i = 0; i < (name || '').length; i++) hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
        return palette[hash % palette.length];
    }
    function initials(name) {
        return (name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0].toUpperCase()).join('');
    }
    function roleLabel(role) {
        const r = (role || '').toLowerCase();
        return r ? r.charAt(0).toUpperCase() + r.slice(1) : '';
    }

    const ROLE_GROUPS = [
        { key: 'senior', label: 'Seniors' },
        { key: 'staff', label: 'Staff' },
        { key: 'intern', label: 'Interns' },
    ];

    function empRowHtml(emp) {
        return `
            <div class="eng-vm-emp-row">
                <div class="eng-vm-emp-avatar" style="background-color:${hashColor(emp.full_name)};color:#fff;">${initials(emp.full_name)}</div>
                <div class="eng-vm-emp-info">
                    <div class="eng-vm-emp-name">${emp.full_name}</div>
                    <div class="eng-vm-emp-role">${roleLabel(emp.role)}</div>
                </div>
                <span class="wa-available-pill">${emp.available_hours}h open</span>
            </div>
        `;
    }

    function render(data) {
        const weekLabel = new Date(data.week_start + 'T00:00:00').toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        subtitleEl.textContent = `Week of ${weekLabel} · under ${data.threshold}h assigned${data.is_busy_season ? ' (busy season)' : ''}`;

        if (!data.employees || data.employees.length === 0) {
            listEl.innerHTML = '<div class="settings-empty-row">Everyone\'s fully booked this week.</div>';
            return;
        }

        const sections = ROLE_GROUPS.map(group => {
            const emps = data.employees.filter(e => (e.role || '').toLowerCase() === group.key);
            if (emps.length === 0) return '';
            return `<div class="wa-role-heading">${group.label}</div>${emps.map(empRowHtml).join('')}`;
        }).join('');

        listEl.innerHTML = sections || '<div class="settings-empty-row">Everyone\'s fully booked this week.</div>';
    }

    async function openForWeek(weekStart) {
        subtitleEl.textContent = ' ';
        listEl.innerHTML = '<div class="settings-empty-row">Loading...</div>';
        modal.show();

        try {
            const res = await fetch(`get_week_availability.php?week_start=${encodeURIComponent(weekStart)}`);
            const data = await res.json();
            if (!data.success) {
                listEl.innerHTML = `<div class="settings-empty-row text-danger">${data.error || 'Could not load availability.'}</div>`;
                return;
            }
            render(data);
        } catch (err) {
            console.error('Failed to load week availability', err);
            listEl.innerHTML = '<div class="settings-empty-row text-danger">Network error loading availability.</div>';
        }
    }

    document.querySelectorAll('th.week[data-week-start]').forEach(th => {
        th.addEventListener('click', () => openForWeek(th.dataset.weekStart));
    });
});
