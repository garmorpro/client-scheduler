document.addEventListener('DOMContentLoaded', function () {
  const entryTypePrompt = document.getElementById('entryTypePrompt');
  const timeOffEntryContent = document.getElementById('timeOffEntryContent');
  const newEntryContent = document.getElementById('newEntryContent');
  const btnTimeOffEntry = document.getElementById('btnTimeOffEntry');
  const btnNewEntry = document.getElementById('btnNewEntry');
  const engagementInput = document.getElementById('engagementInput');
  const selectedClient = document.getElementById('selectedClient');
  const assignedHours = document.getElementById('assignedHours');
  const timeOffHours = document.getElementById('timeOffHours');
  const form = document.getElementById('entryForm');
  const footer = document.getElementById('modal-footer');

  const entryModal = document.getElementById('addEntryModal');  // UPDATED here

  entryModal.addEventListener('show.bs.modal', function (event) {
    entryTypePrompt.classList.remove('d-none');
    timeOffEntryContent.classList.add('d-none');
    newEntryContent.classList.add('d-none');
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
    newEntryContent.classList.add('d-none');
    footer.classList.remove('d-none');

    timeOffHours.required = true;
    assignedHours.required = false;
    engagementInput.required = false;

    engagementInput.value = '';
    assignedHours.value = '';
  });

  btnNewEntry.addEventListener('click', function () {
    entryTypePrompt.classList.add('d-none');
    timeOffEntryContent.classList.add('d-none');
    newEntryContent.classList.remove('d-none');
    footer.classList.remove('d-none');

    engagementInput.required = true;
    assignedHours.required = true;
    timeOffHours.required = false;

    timeOffHours.value = '';
  });
});