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
});