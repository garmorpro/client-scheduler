// Ported from Engagement Tracker's pages/calendar.php inline script.
// Two real adaptations, not just re-pointing: clicking an item opens the
// existing View Engagement modal in place (ViewEngagementModal.open())
// instead of navigating to a different page/drawer, since Client Scheduler
// doesn't have an equivalent full-page engagement view; and items are keyed
// by a real engagement_id (int) instead of engagement_idno (string).

const today = new Date();
today.setHours(0, 0, 0, 0);
const todayIso = today.toISOString().slice(0, 10);
let viewYear = today.getFullYear();
let viewMonth = today.getMonth() + 1; // 1-12

const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
function escAttr(str) {
    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function isRangeItem(item) {
    return !!(item.start_date && item.start_date !== item.date);
}
function fmtDateShort(iso) {
    return new Date(iso + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function itemStatusClass(item) {
    // A standing weekly meeting isn't a deadline - a past occurrence isn't
    // "overdue," it just already happened.
    if (item.type === 'weekly_call') return 'call';
    if (item.completed) return 'completed';
    if (item.date < todayIso) return 'overdue';
    const daysUntil = Math.round((new Date(item.date + 'T00:00:00') - today) / 86400000);
    if (daysUntil <= 7) return 'soon';
    return 'upcoming';
}
function statusColorVar(status) {
    return { overdue: '#c0392b', soon: '#d99a2b', upcoming: 'var(--primary-color)', completed: '#2f9e57', call: 'rgb(155,107,214)' }[status];
}

function openEngagement(item) {
    if (typeof window.ViewEngagementModal === 'undefined') return;
    window.ViewEngagementModal.open(item.engagement_id, item.avatar_color, item.initials, window.restrictEngagementFinancials);
}

function openDayPopover(dateIso, items) {
    const d = new Date(dateIso + 'T00:00:00');
    document.getElementById('dayPopoverTitle').textContent = d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
    const body = document.getElementById('dayPopoverBody');
    body.innerHTML = items.map((item, idx) => {
        const status = itemStatusClass(item);
        const titleLine = isRangeItem(item)
            ? `${escapeHtml(item.title)} (${fmtDateShort(item.start_date)} - ${fmtDateShort(item.date)})`
            : escapeHtml(item.title);
        return `
            <div class="day-popover-item" data-idx="${idx}">
                <span class="day-popover-dot" style="background:${statusColorVar(status)}"></span>
                <div class="day-popover-info">
                    <div class="day-popover-name">${escapeHtml(item.client_name)}</div>
                    <div class="day-popover-title">${titleLine}</div>
                </div>
            </div>
        `;
    }).join('');
    body.querySelectorAll('[data-idx]').forEach(el => {
        el.addEventListener('click', () => openEngagement(items[parseInt(el.dataset.idx)]));
    });
    document.getElementById('dayPopoverScrim').classList.add('open');
}
function closeDayPopover() {
    document.getElementById('dayPopoverScrim').classList.remove('open');
}
document.getElementById('dayPopoverScrim').addEventListener('click', (ev) => {
    if (ev.target.id === 'dayPopoverScrim') closeDayPopover();
});
document.getElementById('dayPopoverCloseBtn').addEventListener('click', closeDayPopover);

async function loadCalendar() {
    document.getElementById('calMonthLabel').textContent = `${MONTH_NAMES[viewMonth - 1]} ${viewYear}`;
    const weeksEl = document.getElementById('calWeeks');
    weeksEl.innerHTML = '<div class="text-center text-muted py-5">Loading&hellip;</div>';

    let items = [];
    try {
        const res = await fetch(`get-calendar-items.php?year=${viewYear}&month=${viewMonth}`);
        const data = await res.json();
        if (data.success) items = data.items;
    } catch (error) {
        console.error('Error:', error);
    }

    const byDate = {};
    items.forEach(item => {
        if (isRangeItem(item)) {
            // Give it an occurrence on every day of its range, not just the
            // end date, so it renders as a bar spanning the whole week.
            const cur = new Date(item.start_date + 'T00:00:00');
            const end = new Date(item.date + 'T00:00:00');
            while (cur <= end) {
                const iso = cur.toISOString().slice(0, 10);
                (byDate[iso] = byDate[iso] || []).push(item);
                cur.setDate(cur.getDate() + 1);
            }
        } else {
            (byDate[item.date] = byDate[item.date] || []).push(item);
        }
    });

    const firstOfMonth = new Date(viewYear, viewMonth - 1, 1);
    const startDow = firstOfMonth.getDay();
    const daysInMonth = new Date(viewYear, viewMonth, 0).getDate();
    const totalCells = Math.ceil((startDow + daysInMonth) / 7) * 7;

    let html = '';
    for (let w = 0; w < totalCells / 7; w++) {
        html += '<div class="cal-week">';
        for (let d = 0; d < 7; d++) {
            const cellDate = new Date(viewYear, viewMonth - 1, 1);
            cellDate.setDate(cellDate.getDate() - startDow + (w * 7 + d));
            const iso = cellDate.toISOString().slice(0, 10);
            // Range items sorted first so their bar segments line up in the
            // same row position from one day to the next.
            const dayItems = (byDate[iso] || []).slice().sort((a, b) => {
                const rangeDiff = isRangeItem(b) - isRangeItem(a);
                return rangeDiff !== 0 ? rangeDiff : a.completed - b.completed;
            });
            // Weekly status calls linked to the same call_group collapse
            // into one chip using the chosen call name, matching the
            // per-day cell display only - the popover still lists every
            // linked engagement individually from the untouched dayItems.
            const displayItems = [];
            const seenGroups = new Set();
            dayItems.forEach(item => {
                if (item.type === 'weekly_call' && item.call_group) {
                    if (seenGroups.has(item.call_group)) return;
                    seenGroups.add(item.call_group);
                    const label = item.call_group_name || item.client_name;
                    displayItems.push(Object.assign({}, item, { client_name: label }));
                } else {
                    displayItems.push(item);
                }
            });
            const isToday = iso === todayIso;
            const isOutside = cellDate.getMonth() !== (viewMonth - 1);
            const dow = cellDate.getDay();

            let chipsHtml = '';
            displayItems.slice(0, 3).forEach(item => {
                const status = itemStatusClass(item);
                const ranged = isRangeItem(item);
                let spanClass = '';
                let showLabel = true;
                if (ranged) {
                    const segStart = iso === item.start_date || dow === 0;
                    const segEnd = iso === item.date || dow === 6;
                    spanClass = 'cal-chip-span' + (segStart ? ' seg-start' : '') + (segEnd ? ' seg-end' : '');
                    showLabel = segStart;
                }
                chipsHtml += `
                    <div class="cal-chip ${status} ${spanClass}" data-date="${iso}" title="${escAttr(item.client_name + ' — ' + item.title + (ranged ? ` (${fmtDateShort(item.start_date)} - ${fmtDateShort(item.date)})` : ''))}">
                        ${(!ranged || showLabel) ? `<span class="dot" style="background:${statusColorVar(status)}"></span>` : ''}
                        ${showLabel ? `<span class="cal-chip-label">${escapeHtml(item.client_name)}</span>` : ''}
                    </div>
                `;
            });
            if (displayItems.length > 3) {
                chipsHtml += `<div class="cal-more" data-date="${iso}">+${displayItems.length - 3} more</div>`;
            }

            html += `
                <div class="cal-day ${isOutside ? 'outside' : ''} ${isToday ? 'today' : ''}">
                    <div class="cal-day-num">${cellDate.getDate()}</div>
                    ${chipsHtml}
                </div>
            `;
        }
        html += '</div>';
    }
    weeksEl.innerHTML = html;

    weeksEl.querySelectorAll('[data-date]').forEach(el => {
        el.addEventListener('click', () => openDayPopover(el.dataset.date, byDate[el.dataset.date] || []));
    });
}

document.getElementById('calPrevBtn').addEventListener('click', () => {
    viewMonth--;
    if (viewMonth < 1) { viewMonth = 12; viewYear--; }
    loadCalendar();
});
document.getElementById('calNextBtn').addEventListener('click', () => {
    viewMonth++;
    if (viewMonth > 12) { viewMonth = 1; viewYear++; }
    loadCalendar();
});
document.getElementById('calTodayBtn').addEventListener('click', () => {
    viewYear = today.getFullYear();
    viewMonth = today.getMonth() + 1;
    loadCalendar();
});

loadCalendar();
