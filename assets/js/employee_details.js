document.addEventListener('DOMContentLoaded', () => {
    const employeeCells = document.querySelectorAll('td.employee-name');
    const modalEl = document.getElementById('employeeModal');
    const modal = new bootstrap.Modal(modalEl);
    const modalContent = document.getElementById('employeeModalContent');

    function roleLabel(role) {
        const r = (role || '').toLowerCase();
        if (r === 'crm_team') return 'CRM Team';
        if (!r) return '';
        return r.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    // Utility: parse YYYY-MM-DD safely as local date
    function parseDateOnly(yyyyMmDd) {
        const [y, m, d] = yyyyMmDd.split('-').map(Number);
        return new Date(y, m - 1, d);
    }

    // Earliest week to include in the modal: last week onward (drops any
    // older history the master schedule happens to have loaded).
    function getCutoffWeekStart() {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const day = today.getDay(); // 0 = Sunday ... 6 = Saturday
        const diffToMonday = (day === 0 ? -6 : 1) - day;
        const thisMonday = new Date(today);
        thisMonday.setDate(thisMonday.getDate() + diffToMonday);
        const lastMonday = new Date(thisMonday);
        lastMonday.setDate(lastMonday.getDate() - 7);
        return lastMonday;
    }

    // Master list of all clients
    const allClients = Array.from(document.querySelectorAll('td[data-client]'))
        .map(td => td.dataset.client)
        .filter((v, i, a) => a.indexOf(v) === i);

    // Fetch global time off from server
    let globalTimeOffMap = {};
    async function fetchGlobalTimeOff() {
        try {
            const res = await fetch('get_global_pto.php'); // Your PHP file
            const data = await res.json();

            globalTimeOffMap = {};
            data.forEach(item => {
                const weekStart = item.week_start; // <-- use directly
                globalTimeOffMap[weekStart] = parseFloat(item.assigned_hours) || 0;
            });

            console.log("Global Time Off Map:", globalTimeOffMap);
        } catch(err) {
            console.error("Failed to fetch global time off:", err);
        }
    }

    employeeCells.forEach(td => {
        td.style.cursor = 'pointer';
        td.addEventListener('click', async () => {
            const userName = td.dataset.userName;
            const role = td.dataset.role || td.querySelector('.text-muted')?.textContent || 'staff';
            const email = td.dataset.email || '';
            if (!userName) return;

            const initials = userName.split(' ').map(p => p[0].toUpperCase()).join('');

            // Ensure global PTO is loaded before opening modal
            await fetchGlobalTimeOff();

            const row = td.closest('tr');
            const cutoff = getCutoffWeekStart();
            const weekTds = Array.from(row.querySelectorAll('td.addable'))
                .filter(weekTd => parseDateOnly(weekTd.dataset.weekStart) >= cutoff);
            let allAssignments = [];
            let totalHours = 0;
            const uniqueEngagements = new Set();

            const timeOffMap = {};

            // Collect global and personal PTO only for weeks present in the table
            weekTds.forEach(weekTd => {
                const weekStart = weekTd.dataset.weekStart; // <-- no conversion
                const globalHours = globalTimeOffMap[weekStart] || 0;

                const timeOffCorner = weekTd.querySelector('.timeoff-corner');
                const personalHours = timeOffCorner ? parseFloat(timeOffCorner.textContent) || 0 : 0;
                const totalWeekHours = globalHours + personalHours;

                if (totalWeekHours > 0) {
                    timeOffMap[weekStart] = totalWeekHours;
                }

                console.log(`Week ${weekStart} for ${userName}: global=${globalHours}, personal=${personalHours}, total=${totalWeekHours}`);

                // Assignments
                const badges = Array.from(weekTd.querySelectorAll('.draggable-badge'));
                badges.forEach(b => {
                    const match = b.textContent.match(/\(([\d.]+)\)/);
                    const hours = match ? parseFloat(match[1]) : 0;
                    const clientName = b.textContent.split('(')[0].trim();
                    const engagementId = b.dataset.engagementId;
                    const statusMatch = b.className.match(/badge-(confirmed|pending|not-confirmed)/);
                    const statusClass = statusMatch ? statusMatch[1] : 'not-confirmed';
                    allAssignments.push({clientName, hours, status: statusClass, weekStart, engagementId});
                    totalHours += hours;
                    if (engagementId) uniqueEngagements.add(engagementId);
                });
            });

            console.log(`Final timeOffMap for ${userName}:`, timeOffMap);

            const timeOffWeeks = Object.entries(timeOffMap)
                .map(([week, hours]) => ({ week, hours }))
                .sort((a,b) => parseDateOnly(a.week) - parseDateOnly(b.week));

            const totalTimeOffHours = timeOffWeeks.reduce((sum, w) => sum + w.hours, 0);

            // Initialize clients map
            const clientsMap = {};
            allClients.forEach(client => {
                clientsMap[client] = { total: 0, status: 'not-confirmed', weeks: [] };
            });

            allAssignments.forEach(a => {
                if (!clientsMap[a.clientName]) clientsMap[a.clientName] = { total:0, status:a.status, weeks:[] };
                clientsMap[a.clientName].total += a.hours;
                clientsMap[a.clientName].weeks.push({ week: a.weekStart, hours: a.hours });
                clientsMap[a.clientName].status = a.status;
            });

            const STD_THRESHOLD = typeof STANDARD_THRESHOLD !== 'undefined' ? STANDARD_THRESHOLD : 40;
            const BUSY_THRESHOLD = typeof BUSY_SEASON_THRESHOLD !== 'undefined' ? BUSY_SEASON_THRESHOLD : 50;
            const BUSY_START = typeof BUSY_SEASON_START !== 'undefined' ? BUSY_SEASON_START : null;
            const BUSY_END = typeof BUSY_SEASON_END !== 'undefined' ? BUSY_SEASON_END : null;
            function thresholdForWeek(weekDate) {
                if (BUSY_START && BUSY_END && weekDate >= BUSY_START && weekDate <= BUSY_END) return BUSY_THRESHOLD;
                return STD_THRESHOLD;
            }

            const roleColors = {
                senior: 'rgb(230,144,65)',
                staff: 'rgb(66,127,194)',
                intern: 'rgb(76,175,80)'
            };
            const avatarColor = roleColors[role.toLowerCase()] || '#6c757d';

            const formatShort = d => parseDateOnly(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });

            const engagementRows = Object.entries(clientsMap)
                .filter(([, info]) => info.weeks.length > 0)
                .map(([clientName, info]) => ({
                    name: clientName,
                    status: info.status,
                    total: info.total,
                    weeks: info.weeks,
                    timeoff: false
                }));

            const rows = [];
            if (timeOffWeeks.length > 0) {
                rows.push({ name: 'Time Off', status: null, total: totalTimeOffHours, weeks: timeOffWeeks, timeoff: true });
            }
            rows.push(...engagementRows);

            const statusLabel = status => status === 'not-confirmed' ? 'Not Confirmed' : (status.charAt(0).toUpperCase() + status.slice(1));

            const rowsHtml = rows.map(r => {
                const statusClass = r.timeoff ? 'emp-timeoff' : `status-${r.status}`;
                const isOver = !r.timeoff && r.weeks.some(w => w.hours > thresholdForWeek(w.week));
                const weekChips = r.weeks
                    .slice()
                    .sort((a, b) => parseDateOnly(a.week) - parseDateOnly(b.week))
                    .map(w => `
                        <div class="emp-week-chip ${w.hours > thresholdForWeek(w.week) ? 'emp-over-week' : ''}">
                            <span class="emp-week-date">${formatShort(w.week)}</span>
                            <span class="emp-week-hours">${w.hours}h</span>
                        </div>
                    `).join('');

                return `
                    <div class="emp-entry-row ${statusClass}">
                        <div class="emp-entry-dot"></div>
                        <div class="emp-entry-label">
                            <span class="emp-entry-name">${r.name}</span>
                            ${r.status ? `<span class="emp-status-pill status-${r.status}">${statusLabel(r.status)}</span>` : ''}
                        </div>
                        <div class="emp-entry-weeks">${weekChips}</div>
                        <div class="emp-entry-hours ${isOver ? 'over' : ''}">${r.total}h</div>
                    </div>
                `;
            }).join('');

            const html = `
                <div class="ud-hero" style="padding-bottom: 18px;">
                    <div class="ud-header" style="margin-bottom: 0;">
                        <div class="ud-avatar" style="background-color:${avatarColor};">${initials}</div>
                        <div>
                            <div class="ud-name">${userName}</div>
                            <div class="ud-email">${email}</div>
                            <div class="ud-pills">
                                <span class="role-pill text-capitalize">${roleLabel(role)}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ud-body" style="max-height: 52vh;">
                    <div class="stat-row">
                        <div class="stat-card">
                            <div class="stat-title">Engagements</div>
                            <div class="stat-value">${uniqueEngagements.size}</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-title">Assigned Hrs</div>
                            <div class="stat-value">${totalHours}</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-title">Time Off</div>
                            <div class="stat-value">${totalTimeOffHours}</div>
                        </div>
                    </div>

                    <div class="emp-section-title-row">
                        <div class="detail-section-title" style="margin: 0;">Breakdown</div>
                        <div class="emp-section-hint">${rows.length} ${rows.length === 1 ? 'row' : 'rows'}</div>
                    </div>

                    <div class="emp-entry-list">
                        ${rowsHtml || '<div class="emp-entry-row"><div class="emp-entry-label"><span class="emp-entry-name text-muted">No assignments in this window</span></div></div>'}
                    </div>
                </div>
            `;

            modalContent.innerHTML = html;
            modal.show();
        });
    });
});
