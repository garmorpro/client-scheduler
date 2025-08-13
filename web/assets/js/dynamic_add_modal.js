document.addEventListener('DOMContentLoaded', function () {
  const entryTypePrompt = document.getElementById('entryTypePrompt');
  const timeOffEntryContent = document.getElementById('timeOffEntryContent');
  const newAssignmentContent = document.getElementById('newAssignmentContent');
  const btnTimeOffEntry = document.getElementById('btnTimeOffEntry');
  const btnNewAssignment = document.getElementById('btnNewAssignment');
  const engagementInput = document.getElementById('engagementInput');
  const selectedClient = document.getElementById('selectedClient');
  const assignedHours = document.getElementById('assignedHours');
  const timeOffHours = document.getElementById('timeOffHours');
  const form = document.getElementById('assignmentForm');
  const footer = document.getElementById('modal-footer');

  const assignmentModal = document.getElementById('addEntryModal');  // UPDATED here

  assignmentModal.addEventListener('show.bs.modal', function (event) {
    entryTypePrompt.classList.remove('d-none');
    timeOffEntryContent.classList.add('d-none');
    newAssignmentContent.classList.add('d-none');
    footer.classList.add('d-none');

    engagementInput.value = '';
    assignedHours.value = '';
    assignedHours.required = false;
    timeOffHours.value = '';
    timeOffHours.required = false;
    selectedClient.textContent = 'Select a client';
  });

  btnTimeOffEntry.addEventListener('click', function () {
    entryTypePrompt.classList.add('d-none');
    timeOffEntryContent.classList.remove('d-none');
    newAssignmentContent.classList.add('d-none');
    footer.classList.remove('d-none');

    timeOffHours.required = true;
    assignedHours.required = false;
    engagementInput.required = false;

    engagementInput.value = '';
    assignedHours.value = '';
  });

  btnNewAssignment.addEventListener('click', function () {
    entryTypePrompt.classList.add('d-none');
    timeOffEntryContent.classList.add('d-none');
    newAssignmentContent.classList.remove('d-none');
    footer.classList.remove('d-none');

    engagementInput.required = true;
    assignedHours.required = true;
    timeOffHours.required = false;

    timeOffHours.value = '';
  });

     // --- Client dropdown logic ---

     const dropdownBtn = document.getElementById('dropdownBtn');
     const dropdownList = document.getElementById('dropdownList');

     dropdownBtn.addEventListener('click', () => {
       const expanded = dropdownBtn.getAttribute('aria-expanded') === 'true';
       dropdownBtn.setAttribute('aria-expanded', !expanded);
       dropdownList.style.display = expanded ? 'none' : 'block';
     });

     // Close dropdown if clicked outside
     document.addEventListener('click', (e) => {
       if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
         dropdownBtn.setAttribute('aria-expanded', 'false');
         dropdownList.style.display = 'none';
       }
     });

     // Handle selecting a client
     dropdownList.querySelectorAll('.dropdown-item').forEach(item => {
       item.addEventListener('click', () => {
         const clientId = item.getAttribute('data-engagement-id');
         const clientName = item.getAttribute('data-client-name');

         engagementInput.value = clientId;
         selectedClient.textContent = clientName;

         dropdownBtn.setAttribute('aria-expanded', 'false');
         dropdownList.style.display = 'none';
       });
     });
   });