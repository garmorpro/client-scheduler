document.addEventListener("DOMContentLoaded", function() {
    // Main tabs
    const tabs = document.querySelectorAll(".custom-tabs button, .custom-tabs a");
    const tabContents = document.querySelectorAll(".tab-content");

    // Open main tab
    function openTab(tabName) {
        // Reset all main tabs
        tabs.forEach(tab => tab.classList.remove("active"));
        tabContents.forEach(content => content.style.display = "none");

        // Activate main tab
        const activeTab = document.querySelector(`.custom-tabs button[data-tab="${tabName}"], .custom-tabs a[data-tab="${tabName}"]`);
        const activeContent = document.getElementById(tabName);
        if (activeTab && activeContent) {
            activeTab.classList.add("active");
            activeContent.style.display = "block";
        }

        // Update URL hash
        history.replaceState(null, null, "#" + tabName);
    }

    // Main tab click
    tabs.forEach(tab => {
        tab.addEventListener("click", (e) => {
            e.preventDefault();
            openTab(tab.dataset.tab);
        });
    });

    // Open tab on page load based on hash
    const hash = window.location.hash.substring(1); // remove #
    const mainTab = hash || "employees"; // default to employees if no hash
    openTab(mainTab);
});
