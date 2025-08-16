document.addEventListener("DOMContentLoaded", function() {
    // Select both buttons and links
    const tabs = document.querySelectorAll(".custom-tabs button, .custom-tabs a");
    const tabContents = document.querySelectorAll(".tab-content"); // each tab content must have id matching data-tab

    function openTab(tabName) {
        // Remove active from all tabs
        tabs.forEach(tab => tab.classList.remove("active"));
        // Hide all tab content
        tabContents.forEach(content => content.style.display = "none");

        // Activate selected tab
        const activeTab = document.querySelector(`.custom-tabs button[data-tab="${tabName}"], .custom-tabs a[data-tab="${tabName}"]`);
        const activeContent = document.getElementById(tabName);
        if (activeTab && activeContent) {
            activeTab.classList.add("active");
            activeContent.style.display = "block";
            // Update URL hash without scrolling
            history.replaceState(null, null, "#" + tabName);
        }
    }

    // Click handler
    tabs.forEach(tab => {
        tab.addEventListener("click", (e) => {
            e.preventDefault(); // Prevent default link jump
            openTab(tab.dataset.tab);
        });
    });

    // Open tab on page load based on hash or default
    const hash = window.location.hash.substring(1);
    const defaultTab = hash && document.querySelector(`.custom-tabs button[data-tab="${hash}"], .custom-tabs a[data-tab="${hash}"]`) ? hash : "users";
    openTab(defaultTab);
});