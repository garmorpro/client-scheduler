document.addEventListener('DOMContentLoaded', () => {
  const assignmentModal = document.getElementById('assignmentModal');

  assignmentModal.addEventListener('show.bs.modal', () => {
    const dropdownBtn = document.getElementById('dropdownBtn');
    const dropdownList = document.getElementById('dropdownList');
    const selectedClient = document.getElementById('selectedClient');
    const engagementInput = document.getElementById('engagementInput');

    // Reset dropdown on modal open
    closeDropdown();

    dropdownBtn.addEventListener('click', toggleDropdown);
    dropdownBtn.addEventListener('keydown', dropdownBtnKeyHandler);

    dropdownList.querySelectorAll('.dropdown-item').forEach(item => {
      item.addEventListener('click', () => selectClient(item));
      item.addEventListener('keydown', dropdownItemKeyHandler);
    });

    // Close dropdown if clicking outside
    document.addEventListener('click', outsideClickHandler);

    function toggleDropdown() {
  const expanded = dropdownBtn.getAttribute('aria-expanded') === 'true';
  dropdownBtn.setAttribute('aria-expanded', (!expanded).toString());
  dropdownList.style.setProperty('display', expanded ? 'block' : 'none', 'important');

  }


    function dropdownBtnKeyHandler(e) {
      if (['ArrowDown', 'Enter', ' '].includes(e.key)) {
        e.preventDefault();
        dropdownList.style.display = 'block';
        dropdownBtn.setAttribute('aria-expanded', 'true');
        const firstItem = dropdownList.querySelector('.dropdown-item');
        if (firstItem) firstItem.focus();
      }
    }

    function dropdownItemKeyHandler(e) {
      const item = e.target;
      if (['Enter', ' '].includes(e.key)) {
        e.preventDefault();
        selectClient(item);
      } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        const next = item.nextElementSibling || dropdownList.querySelector('.dropdown-item');
        if (next) next.focus();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        const prev = item.previousElementSibling || dropdownList.querySelector('.dropdown-item:last-child');
        if (prev) prev.focus();
      } else if (e.key === 'Escape') {
        closeDropdown();
        dropdownBtn.focus();
      }
    }

    function outsideClickHandler(e) {
      if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
        closeDropdown();
      }
    }

    function selectClient(item) {
      const clientName = item.getAttribute('data-client-name');
      const engagementId = item.getAttribute('data-engagement-id');
      selectedClient.textContent = clientName;
      engagementInput.value = engagementId;
      closeDropdown();
    }

    function closeDropdown() {
      dropdownList.style.display = 'none';
      dropdownBtn.setAttribute('aria-expanded', 'false');
    }
  });
  });