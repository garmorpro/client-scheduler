document.addEventListener("DOMContentLoaded", function() {
    const startMonth = document.getElementById("startMonth");
    const endMonth = document.getElementById("endMonth");
    const startWeek = document.getElementById("startWeek");
    const endWeek = document.getElementById("endWeek");
    const weekContainer = document.getElementById("weekSelectorContainer");
    const dayContainer = document.getElementById("dayHoursContainer");
    const dayInputsDiv = document.getElementById("dayInputs");
    const summaryText = document.getElementById("summaryText");

    // ----- helpers (LOCAL time, no timezone shift) -----
    const pad2 = n => String(n).padStart(2, "0");
    function ymd(date) { // format local date as YYYY-MM-DD
        return `${date.getFullYear()}-${pad2(date.getMonth()+1)}-${pad2(date.getDate())}`;
    }
    function parseLocalYMD(s) { // parse 'YYYY-MM-DD' as a local Date
        const [Y,M,D] = s.split("-").map(Number);
        return new Date(Y, M-1, D);
    }

    // Get Mondays (local time) for a month
    function getMondaysInMonth(year, month) {
        const mondays = [];
        let date = new Date(year, month - 1, 1); // local
        while (date.getDay() !== 1) date.setDate(date.getDate() + 1); // to Monday
        while (date.getMonth() === month - 1) {
            mondays.push(new Date(date));
            date.setDate(date.getDate() + 7);
        }
        return mondays;
    }

    function formatDateLong(date) {
        return date.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });
    }

    // Populate week (Monday) selector using LOCAL ymd values
    function populateWeekSelector(selector, month) {
        if (!month) return;
        const weeks = getMondaysInMonth(2025, parseInt(month, 10));
        selector.innerHTML = '<option value="">Select Week</option>';
        weeks.forEach(date => {
            const val = ymd(date); // use local Y-M-D
            const label = formatDateLong(date);
            selector.innerHTML += `<option value="${val}">${label}</option>`;
        });
        weekContainer.style.display = "block";
        dayContainer.style.display = "none";
    }

    startMonth.addEventListener("change", () => populateWeekSelector(startWeek, startMonth.value));
    endMonth.addEventListener("change", () => populateWeekSelector(endWeek, endMonth.value));

    function generateDayInputs(start, end) {
        dayInputsDiv.innerHTML = "";

        // Parse selected weeks as LOCAL dates (not UTC)
        const startMonday = parseLocalYMD(start);
        const endMonday = parseLocalYMD(end);

        let currentWeek = new Date(startMonday);

        while (currentWeek <= endMonday) {
            const rowDiv = document.createElement("div");
            rowDiv.className = "week-row d-flex align-items-center gap-2 mb-3";

            // Store the Monday for this row so submit uses the correct week_start
            rowDiv.dataset.weekStart = ymd(currentWeek);

            // Monday–Friday (i = 0..4)
            for (let i = 0; i < 5; i++) {
                const d = new Date(currentWeek);
                d.setDate(d.getDate() + i);

                const dateStr = (d.getMonth() + 1) + '/' + d.getDate();

                const input = document.createElement("input");
                input.type = "number";
                input.min = 0;
                input.max = 10;
                input.value = 0;
                input.className = "form-control form-control-sm day-hour";
                input.style.width = "55px";
                input.dataset.date = ymd(d); // local YYYY-MM-DD

                const wrapper = document.createElement("div");
                wrapper.className = "d-flex flex-column align-items-center";
                const label = document.createElement("label");
                label.style.fontSize = "0.75rem";
                label.textContent = dateStr;
                wrapper.appendChild(label);
                wrapper.appendChild(input);
                rowDiv.appendChild(wrapper);
            }

            // Inline week total
            const weekTotalLabel = document.createElement("span");
            weekTotalLabel.textContent = "= 0 hrs";
            weekTotalLabel.style.fontWeight = "bold";
            weekTotalLabel.style.marginLeft = "15px";
            weekTotalLabel.className = "week-total";
            rowDiv.appendChild(weekTotalLabel);

            dayInputsDiv.appendChild(rowDiv);

            // Next Monday
            currentWeek.setDate(currentWeek.getDate() + 7);
        }

        dayContainer.style.display = "flex";

        // Totals
        document.querySelectorAll(".day-hour").forEach(input => {
            input.addEventListener("input", updateTotals);
        });
        updateTotals();

        function updateTotals() {
            let totalHours = 0;

            const weekRows = document.querySelectorAll(".week-row");
            weekRows.forEach(row => {
                let weekSum = 0;
                row.querySelectorAll(".day-hour").forEach(input => {
                    weekSum += parseInt(input.value) || 0;
                });
                row.querySelector(".week-total").textContent = `= ${weekSum} hrs`;
                totalHours += weekSum;
            });

            summaryText.textContent = `Total Weeks: ${weekRows.length} | Total Hours: ${totalHours}`;
        }
    }

    startWeek.addEventListener("change", () => {
        if (startWeek.value && endWeek.value) generateDayInputs(startWeek.value, endWeek.value);
    });
    endWeek.addEventListener("change", () => {
        if (startWeek.value && endWeek.value) generateDayInputs(startWeek.value, endWeek.value);
    });

    // Submit one entry per week row using the stored Monday (local)
    const form = document.getElementById("addGlobalPTOForm");
    form.addEventListener("submit", function(e) {
        e.preventDefault();
        const note = document.getElementById("pto_note").value;
        const entries = [];

        document.querySelectorAll(".week-row").forEach(row => {
            let weekSum = 0;
            row.querySelectorAll(".day-hour").forEach(input => {
                weekSum += parseInt(input.value) || 0;
            });
            if (weekSum > 0) {
                entries.push({
                    timeoff_note: note,
                    week_start: row.dataset.weekStart, // exact Monday, no +1 shift
                    assigned_hours: weekSum,
                    is_global_timeoff: 1
                });
            }
        });

        if (entries.length === 0) {
            alert("Please enter hours for at least one day.");
            return;
        }

        console.log(entries);
        alert("Global PTO entries ready to submit. Check console for preview.");
    });
});