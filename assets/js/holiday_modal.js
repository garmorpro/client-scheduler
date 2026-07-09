document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('holidayModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('holidayForm');
    const titleEl = document.getElementById('holidayModalTitle');
    const nameInput = document.getElementById('holiday_name');
    const originalNameInput = document.getElementById('holiday_original_name');
    const daysContainer = document.getElementById('holidayDaysContainer');
    const addDayBtn = document.getElementById('holidayAddDayBtn');

    let mode = 'add'; // 'add' | 'edit'
    let originalDays = []; // captured at edit-open time, for diffing on save

    function dayRowTemplate(id, date, hours) {
        const row = document.createElement('div');
        row.className = 'hol-day-row';
        if (id) row.dataset.id = id;
        row.innerHTML = `
            <input type="date" class="eng-edit-input date-input" value="${date || ''}" required>
            <input type="number" class="eng-edit-input hours-input" value="${hours || 8}" min="1" max="24" required>
            <button type="button" class="hol-day-remove" title="Remove"><i class="bi bi-x-lg"></i></button>
        `;
        row.querySelector('.hol-day-remove').addEventListener('click', () => row.remove());
        return row;
    }

    addDayBtn.addEventListener('click', () => {
        daysContainer.appendChild(dayRowTemplate(null, '', 8));
    });

    function openAdd() {
        mode = 'add';
        originalDays = [];
        titleEl.textContent = 'Add Holiday';
        nameInput.value = '';
        originalNameInput.value = '';
        daysContainer.innerHTML = '';
        daysContainer.appendChild(dayRowTemplate(null, '', 8));
        modal.show();
    }

    function openEdit(name, days) {
        mode = 'edit';
        originalDays = days;
        titleEl.textContent = 'Edit Holiday';
        nameInput.value = name;
        originalNameInput.value = name;
        daysContainer.innerHTML = '';
        days.forEach(d => daysContainer.appendChild(dayRowTemplate(d.id, d.date, d.hours)));
        modal.show();
    }

    document.getElementById('addHolidayBtn')?.addEventListener('click', openAdd);

    document.querySelectorAll('.edit-holiday-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const name = btn.dataset.name;
            const days = JSON.parse(btn.dataset.days);
            openEdit(name, days);
        });
    });

    function notify(message, isError) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: isError ? 'error' : 'success', title: message, timer: isError ? undefined : 1300, showConfirmButton: !!isError });
        } else if (isError) {
            alert(message);
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = nameInput.value.trim();
        if (!name) return;

        const rows = Array.from(daysContainer.querySelectorAll('.hol-day-row'));
        if (rows.length === 0) {
            notify('Please add at least one day off.', true);
            return;
        }

        try {
            let res, data;
            if (mode === 'add') {
                const days = rows.map(row => ({
                    date: row.querySelector('.date-input').value,
                    hours: row.querySelector('.hours-input').value
                }));
                res = await fetch('save_holiday.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, days })
                });
            } else {
                const updatedDays = [];
                const newDays = [];
                rows.forEach(row => {
                    const id = row.dataset.id;
                    const date = row.querySelector('.date-input').value;
                    const hours = row.querySelector('.hours-input').value;
                    if (id) updatedDays.push({ id, date, hours });
                    else newDays.push({ date, hours });
                });
                const keptIds = updatedDays.map(d => String(d.id));
                const deletedIds = originalDays.filter(d => !keptIds.includes(String(d.id))).map(d => d.id);

                res = await fetch('update_holiday.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ originalName: originalNameInput.value, newName: name, updatedDays, newDays, deletedIds })
                });
            }
            data = await res.json();
            if (data.success) {
                notify(mode === 'add' ? 'Holiday added!' : 'Holiday updated!', false);
                setTimeout(() => location.reload(), 600);
            } else {
                notify(data.message || 'Something went wrong.', true);
            }
        } catch (err) {
            console.error('Failed to save holiday', err);
            notify('Network error. Please try again.', true);
        }
    });

    // Delete a single date (hover x in the table, no need to open Edit)
    document.querySelectorAll('.date-row .chip-del').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            if (!id) return;
            const runDelete = () => {
                fetch('delete_holiday_date.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                })
                .then(res => res.json())
                .then(data => { if (data.success) location.reload(); else notify(data.message || 'Could not remove date.', true); })
                .catch(err => console.error('Failed to delete date', err));
            };
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Remove this date?', showCancelButton: true, confirmButtonText: 'Remove', confirmButtonColor: '#c0392b' })
                    .then(result => { if (result.isConfirmed) runDelete(); });
            } else if (confirm('Remove this date?')) {
                runDelete();
            }
        });
    });

    // Delete the whole holiday group
    document.querySelectorAll('.delete-holiday-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const name = btn.dataset.name;
            const runDelete = () => {
                fetch('delete_holiday.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name })
                })
                .then(res => res.json())
                .then(data => { if (data.success) location.reload(); else notify(data.message || 'Could not delete holiday.', true); })
                .catch(err => console.error('Failed to delete holiday', err));
            };
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Delete holiday?', text: `This will remove all days for "${name}".`, showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#c0392b' })
                    .then(result => { if (result.isConfirmed) runDelete(); });
            } else if (confirm(`Delete all days for "${name}"?`)) {
                runDelete();
            }
        });
    });
});
