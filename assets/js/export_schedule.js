(function () {
    function csvEscape(value) {
        const str = String(value ?? '');
        if (/[",\n]/.test(str)) {
            return '"' + str.replace(/"/g, '""') + '"';
        }
        return str;
    }

    function cellText(td) {
        const parts = [];

        const timeoff = td.querySelector('.timeoff-corner');
        if (timeoff) {
            parts.push(`Time Off (${timeoff.textContent.trim()}h)`);
        }

        td.querySelectorAll('.badge-status').forEach(badge => {
            parts.push(badge.textContent.trim());
        });

        return parts.join('; ');
    }

    function buildScheduleCSV() {
        const table = document.querySelector('.schedule-table');
        if (!table) return null;

        const headerCells = Array.from(table.querySelectorAll('thead th'));
        const headerRow = headerCells.map((th, i) => {
            if (i === 0) return 'Employee';
            return th.textContent.trim().replace(/\s+/g, ' ');
        });
        headerRow.splice(1, 0, 'Role'); // Role column right after Employee

        const rows = [headerRow];

        document.querySelectorAll('#employeesTableBody tr').forEach(row => {
            if (row.style.display === 'none') return; // respect active filters

            const nameCell = row.querySelector('.employee-name');
            const name = nameCell ? nameCell.dataset.userName || nameCell.textContent.trim() : '';
            const role = row.dataset.role || '';

            const weekCells = Array.from(row.querySelectorAll('td')).slice(1);
            const rowOut = [name, role, ...weekCells.map(cellText)];
            rows.push(rowOut);
        });

        return rows.map(r => r.map(csvEscape).join(',')).join('\r\n');
    }

    function downloadCSV() {
        const csv = buildScheduleCSV();
        if (!csv) return;

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        const today = new Date().toISOString().slice(0, 10);

        link.href = url;
        link.download = `master-schedule-${today}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.getElementById('exportScheduleBtn');
        if (!btn) return;
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            downloadCSV();
        });
    });
})();
