<script>
    // // open ManageorAddModal
      //   const assignments = <?php echo json_encode($assignments); ?>;

      //   document.addEventListener('DOMContentLoaded', () => {
      //   document.querySelectorAll('td.addable').forEach(td => {
      //     td.addEventListener('click', function () {
      //       const userId = this.dataset.userId;
      //       const weekStart = this.dataset.weekStart;
      //       const employeeName = this.dataset.employeeName || 'Employee'; // Optional

      //       console.log('Clicked cell userId:', userId);
      //       console.log('Clicked cell weekStart:', weekStart);

      //       // This function now decides which modal to open
      //       openManageOrAddModal(userId, employeeName, weekStart);
      //     });
      //   });
      //   });

      //     function openManageOrAddModal(user_id, employeeName, weekStart) {
      //   console.log("Modal triggered:", user_id, employeeName, weekStart);

      //   const assignmentsForWeek = assignments[user_id] && assignments[user_id][weekStart] 
      //     ? assignments[user_id][weekStart] 
      //     : [];
      //   console.log("Assignments for week:", assignmentsForWeek);

      //   const hasAssignments = Array.isArray(assignmentsForWeek) && assignmentsForWeek.length > 0;

      //   if (hasAssignments) {
      //     // Show Manage/Add modal
      //     const manageAddModal = new bootstrap.Modal(document.getElementById('manageAddModal'));
      //     manageAddModal.show();

      //     document.getElementById('manageAssignmentsButton').onclick = function() {
      //       openManageAssignmentsModal(user_id, employeeName, weekStart);
      //     };
      //     document.getElementById('addAssignmentsButton').onclick = function() {
      //       openAddAssignmentModal(user_id, employeeName, weekStart);
      //     };
      //   } else {
      //     // Directly open Add Assignment modal
      //     openAddAssignmentModal(user_id, employeeName, weekStart);
      //   }
      //   }
      // // end ManageOrAddModal

      // // open addAssignmentModal
      //     function openAddAssignmentModal(user_id, employeeName, weekStart) {
      //       if (!weekStart || isNaN(new Date(weekStart).getTime())) {
      //         console.warn('Invalid weekStart date:', weekStart);
      //         return;
      //       }

      //       document.getElementById('modalUserId').value = user_id;
      //       document.getElementById('modalWeek').value = weekStart;  // must be "YYYY-MM-DD"
      //       document.getElementById('modalEmployeeNameDisplay').textContent = employeeName;

      //       const options = { year: 'numeric', month: 'short', day: 'numeric' };
      //       const weekDate = new Date(weekStart);
      //       document.getElementById('modalWeekDisplay').textContent = weekDate.toLocaleDateString(undefined, options);

      //       // Reset UI states
      //       document.getElementById('entryTypePrompt').classList.remove('d-none');
      //       document.getElementById('timeOffEntryContent').classList.add('d-none');
      //       document.getElementById('newAssignmentContent').classList.add('d-none');

      //       // Clear inputs
      //       document.getElementById('selectedClient').textContent = 'Select a client';
      //       document.getElementById('engagementInput').value = '';
      //       document.getElementById('assignedHours').value = '';
      //       document.getElementById('timeOffHours').value = '';

      //       // Reset dropdown aria
      //       const dropdownBtn = document.getElementById('dropdownBtn');
      //       if (dropdownBtn) {
      //         dropdownBtn.setAttribute('aria-expanded', 'false');
      //       }

      //       // Show modal
      //       const assignmentModal = new bootstrap.Modal(document.getElementById('assignmentModal'));
      //       assignmentModal.show();
      //     }
      // // end open addAssignmentModal

      // // open manageAssignementModal
      //     function openManageAssignmentsModal(user_id, employeeName, weekStart) {
      //         const formattedDate = new Date(weekStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      //         document.getElementById('assignmentsModalTitle').innerText = `Manage Assignments for Week of ${formattedDate}`;
      //         document.getElementById('assignmentsModalSubheading').innerText = `Consultant: ${employeeName}`;

      //         // Fetch assignments for the user and week
      //         const assignments = <?php echo json_encode($assignments); ?>;
      //         const assignmentsForWeek = assignments[user_id] && assignments[user_id][weekStart] ? assignments[user_id][weekStart] : [];
      //         showAssignments(assignmentsForWeek);

      //         const assignmentsModal = new bootstrap.Modal(document.getElementById('assignmentsModal'));
      //         assignmentsModal.show();
      //     }
      // // end manageAssignementModal
</script>