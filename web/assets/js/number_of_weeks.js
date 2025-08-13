function generateWeekInputs() {
      const numberOfWeeks = parseInt(document.getElementById('numberOfWeeks').value);
      const selectedStartDate = document.getElementById('modalWeek').value; // Assume you have a date input with id="startDate"
      const weeksContainer = document.getElementById('weeksContainer');
      weeksContainer.innerHTML = ''; // Clear previous input fields

      if (!selectedStartDate) {
          alert("Please select a start date.");
          return;
      }

      const startDate = new Date(selectedStartDate);

      for (let i = 0; i < numberOfWeeks; i++) {
          const weekDate = new Date(startDate);
          weekDate.setDate(startDate.getDate() + (i * 7)); // Add 7 days per week

          // Format the date to YYYY-MM-DD for input[type="date"]
          const formattedDate = weekDate.toISOString().split('T')[0];

          const weekInput = document.createElement('div');
          weekInput.classList.add('mb-3');
          weekInput.innerHTML = `
              <label for="week_${i+1}" class="form-label">Week ${i + 1}</label>
              <div class="d-flex gap-2 flex-wrap">
                  <input type="date" class="form-control" id="week_${i+1}" name="weeks[]" value="${formattedDate}" required>
                  <input type="number" class="form-control" id="assigned_hours_${i+1}" name="assigned_hours[]" min="0" placeholder="Assigned Hours" required>
                  <select class="form-select" name="statuses[]" required>
                      <option value="confirmed">Confirmed</option>
                      <option value="pending">Pending</option>
                      <option value="not_confirmed">Not Confirmed</option>
                  </select>
              </div>
              <div class='mt-3'></div>
              <hr>
              <div class='mt-3'></div>
          `;
          weeksContainer.appendChild(weekInput);
      }
  }