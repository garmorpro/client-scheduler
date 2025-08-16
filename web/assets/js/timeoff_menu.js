(async function() {
    if (!IS_ADMIN) return;

    let activeInput = null;

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

        const otherBadges = Array.from(td.querySelectorAll('.badge')).filter(b => b !== existingBadge);
        const plusIconExists = td.querySelector('.bi-plus');
        if (otherBadges.length === 0 && !plusIconExists) {
            const plusIcon = document.createElement('i');
            plusIcon.className = 'bi bi-plus';
            td.appendChild(plusIcon);
        }

        console.log('Rendered badge:', { td, timeOffValue, entryId });
    }

    function markGlobalTimeOff(td) {
        td.classList.add('timeoff-cell');
        td.dataset.globalTimeoff = '1';
        console.log('Marked global time off for cell:', td);
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

    async function getTimeOffEntryWithHours(td) {
        const user_id = td.dataset.userId;
        const week_start = td.dataset.weekStart;
        
        const response = await safeFetchJSON('get_timeoff_entry.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id, week_start })
        });
    
        console.log('Fetched time off entry with hours:', { td, response });
    
        if (response.ok && response.data?.success) {
            return {
                entryId: response.data.entry_id || null,
                // Only return hours if entry exists
                assigned_hours: response.data.entry_id ? parseFloat(response.data.assigned_hours) : 0
            };
        } else {
            return { entryId: null, assigned_hours: 0 };
        }
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

        const { entryId, assigned_hours } = await getTimeOffEntryWithHours(td);
        const globalHours = await getGlobalTimeOffHours(td.dataset.weekStart) || 0;

        // Show individual (personal) hours in the input only if the entry exists
        const totalHoursPlusGlobal = entryId ? Math.max(assigned_hours + globalHours, 0) : '';

        console.log('Opening input:', { entryId, assigned_hours, globalHours, totalHoursPlusGlobal });

        const input = document.createElement('input');
        input.type = 'text';
        input.value = assigned_hours; // <--- This is where the DB value shows
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
                const personalValue = parseFloat(val);
                console.log('Saving personal time off value:', personalValue);

                if (personalValue === 0 && entryId) {
                    const del = await safeFetchJSON('delete_timeoff_new.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ entry_id: entryId })
                    });

                    if (del.ok && del.data?.success) {
                        const badge = td.querySelector('.timeoff-corner');
                        if (badge) badge.remove();
                        await checkGlobalTimeOff(td);
                        if (!td.querySelector('.bi-plus')) {
                            const plusIcon = document.createElement('i');
                            plusIcon.className = 'bi bi-plus';
                            td.appendChild(plusIcon);
                        }
                        console.log('Deleted personal time off for cell:', td);
                    } else {
                        alert('Failed to delete time off.');
                    }
                } else {
                    const totalHoursToSave = personalValue + globalHours;
                    console.log('Total hours including global PTO (personal + global):', totalHoursToSave);

                    if (entryId) {
                        const update = await safeFetchJSON('update_timeoff_new.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ entry_id: entryId, assigned_hours: personalValue })
                        });
                        if (update.ok && update.data?.success) {
                            renderTimeOff(td, personalValue, entryId);
                        } else {
                            alert('Failed to update time off.');
                        }
                    } else {
                        const add = await safeFetchJSON('add_timeoff_new.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                user_id: td.dataset.userId,
                                week_start: td.dataset.weekStart,
                                assigned_hours: personalValue,
                                is_timeoff: 1
                            })
                        });
                        if (add.ok && add.data?.success && add.data.entry_id) {
                            renderTimeOff(td, totalHoursToSave, add.data.entry_id);
                        } else {
                            alert('Failed to add time off.');
                        }
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
