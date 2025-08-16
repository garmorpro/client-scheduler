(function() {
    if (!IS_ADMIN) return;

    let activeInput = null;

    // Render or update the personal+global time-off badge
    function renderTimeOff(td, timeOffValue, entryId) {
        td.style.position = 'relative';
        td.classList.add('timeoff-cell');

        let existingBadge = td.querySelector('.timeoff-corner');
        if (existingBadge) {
            existingBadge.textContent = timeOffValue;
            existingBadge.dataset.entryId = entryId;
        } else {
            const div = document.createElement('div');
            div.className = 'timeoff-corner';
            div.dataset.entryId = entryId;
            div.textContent = timeOffValue;
            td.appendChild(div);
        }

        // Only add plus icon if there are NO other badges except this one
        const otherBadges = Array.from(td.querySelectorAll('.badge')).filter(b => b !== existingBadge);
        const plusIconExists = td.querySelector('.bi-plus');
        if (otherBadges.length === 0 && !plusIconExists) {
            const plusIcon = document.createElement('i');
            plusIcon.className = 'bi bi-plus';
            td.appendChild(plusIcon);
        }
    }

    // Mark gray background for global time-off without badge
    function markGlobalTimeOff(td) {
        td.classList.add('timeoff-cell');
        td.dataset.globalTimeoff = '1';
    }

    async function safeFetchJSON(url, options) {
        try {
            const resp = await fetch(url, options);
            const text = await resp.text();
            const data = JSON.parse(text);
            return { ok: resp.ok, data };
        } catch (err) {
            console.error('Fetch or parse error:', err);
            return { ok: false, data: null, error: err.message };
        }
    }

    async function getTimeOffEntry(td) {
        const user_id = td.dataset.userId;
        const week_start = td.dataset.weekStart;

        const response = await safeFetchJSON('get_timeoff_entry.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id, week_start })
        });

        if (response.ok && response.data?.success) {
            return response.data.entry_id;
        } else {
            return null;
        }
    }

    async function getTimeOffHours(td) {
        const user_id = td.dataset.userId;
        const week_start = td.dataset.weekStart;

        const response = await safeFetchJSON('get_timeoff_entry.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id, week_start })
        });

        if (response.ok && response.data?.success && response.data.assigned_hours) {
            return parseFloat(response.data.assigned_hours);
        }
        return 0;
    }

    async function getGlobalTimeOffHours(week_start) {
        const response = await safeFetchJSON('check_global_timeoff.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ week_start })
        });

        if (response.ok && response.data?.success && response.data.assigned_hours) {
            return parseFloat(response.data.assigned_hours);
        }
        return 0;
    }

    async function checkGlobalTimeOff(td) {
        const hours = await getGlobalTimeOffHours(td.dataset.weekStart);
        if (hours > 0) {
            markGlobalTimeOff(td);
            return true;
        } else {
            td.classList.remove('timeoff-cell');
            td.removeAttribute('data-global-timeoff');
            return false;
        }
    }

    async function handleTimeOffInput(td) {
        if (activeInput) activeInput.remove();

        const entryId = await getTimeOffEntry(td);
        const personalHours = await getTimeOffHours(td) || 0; // Personal only
        const globalHours = await getGlobalTimeOffHours(td.dataset.weekStart) || 0;

        const input = document.createElement('input');
        input.type = 'text';
        input.value = personalHours; // show ONLY personal hours in the input
        input.className = 'form-control form-control-sm';
        input.style.width = '100%';
        td.appendChild(input);
        input.focus();
        activeInput = input;

        input.addEventListener('keydown', async e => {
            if (e.key === 'Escape') {
                input.remove();
                activeInput = null;
                return;
            }

            if (e.key !== 'Enter') return;

            let val = input.value.trim();
            if (!val) return;

            try {
                if (val === '0' && entryId) {
                    // DELETE personal time off
                    const del = await safeFetchJSON('delete_timeoff_new.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ entry_id: entryId })
                    });

                    if (del.ok && del.data?.success) {
                        const badge = td.querySelector('.timeoff-corner');
                        if (badge) badge.remove();

                        // Remove personal time off class if no other badges exist
                        const otherBadges = td.querySelectorAll('.badge');
                        if (otherBadges.length === 0) td.classList.remove('timeoff-cell');

                        // Keep gray if global time off
                        await checkGlobalTimeOff(td);

                        // Add plus icon if NO other badges
                        if (otherBadges.length === 0 && !td.querySelector('.bi-plus')) {
                            const plusIcon = document.createElement('i');
                            plusIcon.className = 'bi bi-plus';
                            td.appendChild(plusIcon);
                        }
                    } else {
                        alert('Failed to delete time off.');
                    }
                } else if (entryId) {
                    // Update: total = personal + global
                    const totalHours = parseFloat(val) + globalHours;
                    const update = await safeFetchJSON('update_timeoff_new.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ entry_id: entryId, assigned_hours: totalHours })
                    });
                    if (update.ok && update.data?.success) {
                        renderTimeOff(td, totalHours, entryId);
                    } else {
                        alert('Failed to update time off.');
                    }
                } else {
                    // Add: total = personal + global
                    const totalHours = parseFloat(val) + globalHours;
                    const add = await safeFetchJSON('add_timeoff_new.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            user_id: td.dataset.userId, 
                            week_start: td.dataset.weekStart, 
                            assigned_hours: totalHours, 
                            is_timeoff: 1 
                        })
                    });

                    if (add.ok && add.data?.success && add.data.entry_id) {
                        renderTimeOff(td, totalHours, add.data.entry_id);
                    } else {
                        alert('Failed to add time off.');
                    }
                }
            } catch (err) {
                console.error('Network error while saving time off:', err);
                alert('Network error while saving time off.');
            } finally {
                if (input) input.remove();
                activeInput = null;
            }
        });
    }

    document.addEventListener('contextmenu', e => {
        const td = e.target;
        if (td.tagName === 'TD' && td.classList.contains('addable')) {
            e.preventDefault();
            handleTimeOffInput(td);
        }
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && activeInput) {
            activeInput.remove();
            activeInput = null;
        }
    });

})();
