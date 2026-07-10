document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('companyHolidaysModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    const listEl = document.getElementById('chList');
    const searchInput = document.getElementById('chSearchInput');
    const addBtn = document.getElementById('chAddHolidayBtn');

    let holidays = [];
    let reopenAfterHolidayModal = false;

    function notify(message, isError) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: isError ? 'error' : 'success', title: message, timer: isError ? undefined : 1300, showConfirmButton: !!isError });
        } else if (isError) {
            alert(message);
        }
    }

    function formatDate(dateString) {
        const d = new Date(dateString.length <= 10 ? dateString + 'T00:00:00' : dateString);
        if (isNaN(d)) return dateString;
        return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    }

    function render(filterText) {
        const terms = (filterText || '').trim().toLowerCase().split(',').map(t => t.trim()).filter(t => t.length > 0);
        const filtered = terms.length
            ? holidays.filter(h => terms.some(t => h.name.toLowerCase().includes(t)))
            : holidays;

        if (filtered.length === 0) {
            listEl.innerHTML = `<div class="settings-empty-row">${holidays.length === 0 ? 'No holidays added yet.' : 'No holidays match your search.'}</div>`;
            return;
        }

        listEl.innerHTML = filtered.map(h => {
            const total = h.days.reduce((sum, d) => sum + Number(d.hours), 0);
            const safeName = h.name.replace(/"/g, '&quot;');
            const chips = [...h.days].sort((a, b) => a.date.localeCompare(b.date)).map(d => `
                <span class="ch-date-chip">
                    ${formatDate(d.date)} &middot; <span class="ch-chip-hrs">${d.hours}h</span>
                    <button type="button" class="ch-chip-del" data-date-id="${d.id}" title="Remove this date"><i class="bi bi-x"></i></button>
                </span>
            `).join('');
            return `
                <div class="ch-item">
                    <div class="ch-item-head">
                        <div>
                            <div class="ch-item-name">${h.name}</div>
                            <div class="ch-item-total">${total}h total</div>
                        </div>
                        <div class="ch-item-actions">
                            <button type="button" class="settings-icon-btn ch-edit-btn" data-name="${safeName}" title="Edit"><i class="bi bi-pencil-square"></i></button>
                            <button type="button" class="settings-icon-btn ch-delete-btn" data-name="${safeName}" title="Delete Holiday"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                    <div class="ch-dates">${chips}</div>
                </div>
            `;
        }).join('');
    }

    async function loadHolidays() {
        listEl.innerHTML = '<div class="settings-empty-row">Loading...</div>';
        try {
            const res = await fetch('get_company_holidays.php');
            const data = await res.json();
            if (!data.success) {
                listEl.innerHTML = `<div class="settings-empty-row text-danger">${data.error || 'Could not load holidays.'}</div>`;
                return;
            }
            holidays = data.holidays || [];
            render(searchInput.value);
        } catch (err) {
            console.error('Failed to load company holidays', err);
            listEl.innerHTML = '<div class="settings-empty-row text-danger">Network error loading holidays.</div>';
        }
    }

    modalEl.addEventListener('show.bs.modal', () => {
        searchInput.value = '';
        loadHolidays();
    });

    searchInput.addEventListener('input', () => render(searchInput.value));

    addBtn.addEventListener('click', () => {
        if (!window.HolidayModal) return;
        reopenAfterHolidayModal = true;
        modal.hide();
        window.HolidayModal.openAdd();
    });

    listEl.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.ch-edit-btn');
        if (editBtn && window.HolidayModal) {
            const name = editBtn.dataset.name;
            const holiday = holidays.find(h => h.name === name);
            if (!holiday) return;
            reopenAfterHolidayModal = true;
            modal.hide();
            window.HolidayModal.openEdit(name, holiday.days);
            return;
        }

        const deleteBtn = e.target.closest('.ch-delete-btn');
        if (deleteBtn) {
            const name = deleteBtn.dataset.name;
            const runDelete = () => {
                fetch('delete_holiday.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) loadHolidays();
                        else notify(data.message || 'Could not delete holiday.', true);
                    })
                    .catch(err => console.error('Failed to delete holiday', err));
            };
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Delete holiday?', text: `This will remove all days for "${name}".`, showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#c0392b' })
                    .then(result => { if (result.isConfirmed) runDelete(); });
            } else if (confirm(`Delete all days for "${name}"?`)) {
                runDelete();
            }
            return;
        }

        const chipDelBtn = e.target.closest('.ch-chip-del');
        if (chipDelBtn) {
            const id = chipDelBtn.dataset.dateId;
            if (!id) return;
            const runDelete = () => {
                fetch('delete_holiday_date.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) loadHolidays();
                        else notify(data.message || 'Could not remove date.', true);
                    })
                    .catch(err => console.error('Failed to delete date', err));
            };
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Remove this date?', showCancelButton: true, confirmButtonText: 'Remove', confirmButtonColor: '#c0392b' })
                    .then(result => { if (result.isConfirmed) runDelete(); });
            } else if (confirm('Remove this date?')) {
                runDelete();
            }
        }
    });

    // Reopen this modal once the Add/Edit Holiday form closes, so it reads
    // like a nested panel instead of just vanishing.
    const holidayModalEl = document.getElementById('holidayModal');
    if (holidayModalEl) {
        holidayModalEl.addEventListener('hidden.bs.modal', () => {
            if (!reopenAfterHolidayModal) return;
            reopenAfterHolidayModal = false;
            modal.show();
        });
    }

    document.addEventListener('holidaysUpdated', () => {
        if (modalEl.classList.contains('show')) loadHolidays();
    });
});
