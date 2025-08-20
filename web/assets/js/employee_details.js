document.addEventListener('DOMContentLoaded', () => {
    const employeeCells = document.querySelectorAll('td.employee-name');
    const modalEl = document.getElementById('employeeModal');
    const modal = new bootstrap.Modal(modalEl);
    const modalContent = document.getElementById('employeeModalContent');

    // Utility: parse YYYY-MM-DD safely as local date
    function parseDateOnly(yyyyMmDd) {
        const [y, m, d] = yyyyMmDd.split('-').map(Number);
        return new Date(y, m - 1, d);
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
            const weekTds = Array.from(row.querySelectorAll('td.addable'));
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

            const avgHoursPerWeek = allAssignments.length > 0 ? (totalHours / allAssignments.length).toFixed(1) : 0;

            // Build modal HTML
            let html = `<div class="d-flex align-items-center mb-3">
                <div class="rounded-circle text-white d-flex align-items-center justify-content-center me-3"
                     style="width:50px;height:50px;font-size:18px;font-weight:500;
                     background-color:${role.toLowerCase() === 'senior' ? 'rgb(230,144,65)' :
                        role.toLowerCase() === 'staff' ? 'rgb(66,127,194)' : '#6c757d'};">
                    ${initials}
                </div>
                <div>
                    <div class="fw-semibold">${userName}</div>
                    <div class="text-muted text-capitalize">${role} <i class="bi bi-dot ms-1 me-1"></i>
                        <span class="small text-lowercase">${email}</span>
                    </div>
                </div>
            </div>

            <div class="mb-3 d-flex gap-3">
                <div class="card flex-fill d-flex" style="border-left: 4px solid rgb(68,125,252);">
                    <div class="card-body w-100 d-flex justify-content-between align-items-center p-3">
                        <div>
                            <small class="text-muted" style="font-size: 14px !important;">Total Engagements</small>
                            <div class="fw-semibold fs-4" style="color: rgb(68,125,252);">${uniqueEngagements.size}</div>
                        </div>
                        <div class="rounded-circle d-flex justify-content-center align-items-center" style="width:40px; height:40px; background-color: rgb(222,234,253);">
                            <i class="bi bi-building" style="color: rgb(68,125,252);"></i>
                        </div>
                    </div>
                </div>

                <div class="card flex-fill d-flex" style="border-left: 4px solid rgb(79,197,95);">
                    <div class="card-body w-100 d-flex justify-content-between align-items-center p-3">
                        <div>
                            <small class="text-muted" style="font-size: 14px !important;">Total Hours</small>
                            <div class="fw-semibold fs-4" style="color: rgb(79,197,95);">${totalHours}</div>
                        </div>
                        <div class="rounded-circle d-flex justify-content-center align-items-center" style="width:40px; height:40px; background-color: rgb(226,251,232);">
                            <i class="bi bi-people" style="color: rgb(79,197,95)"></i>
                        </div>
                    </div>
                </div>

                <div class="card flex-fill d-flex" style="border-left: 4px solid rgb(161,77,253);">
                    <div class="card-body w-100 d-flex justify-content-between align-items-center p-3">
                        <div>
                            <small class="text-muted" style="font-size: 14px !important;">Avg Hours/Entry</small>
                            <div class="fw-semibold fs-4" style="color: rgb(161,77,253);">${avgHoursPerWeek}</div>
                        </div>
                        <div class="rounded-circle d-flex justify-content-center align-items-center" style="width:40px; height:40px; background-color: rgb(241,232,253);">
                            <i class="bi bi-people" style="color: rgb(161,77,253);"></i>
                        </div>
                    </div>
                </div>
            </div>`;

            html += `<div class="border rounded p-3 mb-3">
                <ul class="list-group">
                    <li class="list-group-item d-flex fw-semibold text-muted bg-light">
                        <div class="col-6">Client Name</div>
                        <div class="col-2 text-center">Total Hours</div>
                        <div class="col-4">Week Assignments / Time Off</div>
                    </li>`;

            html += `
                <li class="list-group-item d-flex align-items-center text-truncate" style="background-color: rgb(246, 249, 236); border: 2px dashed rgb(209,226,159);">
                    <div class="col-6 fs-6 fw-semibold text-black">Time Off</div>
                    <div class="col-2 text-center">
                        <span class="fs-5 fw-semibold text-black">${totalTimeOffHours}</span><br>
                        <span class="text-muted" style="font-size: 10px;">hours</span>
                    </div>
                    <div class="col-4 d-flex flex-wrap gap-1">
                        ${timeOffWeeks.map(w => `
                            <div style="background-color:#f5f5f5; padding:4px; min-width:50px; text-align:center; border-radius:4px; font-size:12px;">
                                ${parseDateOnly(w.week).toLocaleDateString('en-US', {month:'short', day:'numeric'})}<br>
                                <span class="fw-semibold text-black">${w.hours}h</span>
                            </div>
                        `).join('')}
                    </div>
                </li>`;

            Object.entries(clientsMap).forEach(([clientName, info]) => {
                html += `
                    <li class="list-group-item d-flex align-items-center text-truncate">
                        <div class="col-6 text-truncate">
                            <span class="fs-6 fw-semibold text-black">${clientName}</span> 
                            <span class="badge badge-status badge-${info.status} ms-1 text-capitalize">
                                ${info.status === 'not-confirmed' ? 'not confirmed' : info.status}
                            </span>
                        </div>
                        <div class="col-2 text-center">
                            <span class="fs-5 fw-semibold text-black">${info.total}</span><br>
                            <span class="text-muted" style="font-size: 10px;">hours</span>
                        </div>
                        <div class="col-4 d-flex flex-wrap gap-1">
                            ${info.weeks.map(w => `
                                <div style="background-color:#f5f5f5; padding:4px; min-width:50px; text-align:center; border-radius:4px; font-size:12px;">
                                    ${parseDateOnly(w.week).toLocaleDateString('en-US', {month:'short', day:'numeric'})}<br>
                                    <span class="fw-semibold text-black">${w.hours}h</span>
                                </div>
                            `).join('')}
                        </div>
                    </li>`;
            });

            html += `</ul></div>`;
            modalContent.innerHTML = html;
            modal.show();
        });
    });
});
