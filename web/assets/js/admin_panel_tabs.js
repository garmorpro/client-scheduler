document.addEventListener("DOMContentLoaded", function() {
    // Main tabs
    const tabs = document.querySelectorAll(".custom-tabs button, .custom-tabs a");
    const tabContents = document.querySelectorAll(".tab-content");

    // Nested tabs inside #time_off
    const nestedTabs = document.querySelectorAll("#time_off .nested-tabs a");
    const nestedContents = document.querySelectorAll("#time_off .nested-tab-content");

    // Open main tab
    function openTab(tabName, nestedTabName = null) {
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

        // Nested tab logic for time_off
        if (tabName === "time_off") {
            const defaultNestedTab = nestedTabName || "individual_pto";
            openNestedTab(defaultNestedTab);
        }

        // Update URL hash
        const hash = tabName + (nestedTabName ? "#" + nestedTabName : "");
        history.replaceState(null, null, "#" + hash);
    }

    // Open nested tab
    function openNestedTab(tabName) {
        nestedTabs.forEach(tab => tab.classList.remove("active"));
        nestedContents.forEach(content => content.style.display = "none");

        const activeTab = document.querySelector(`#time_off .nested-tabs a[data-tab="${tabName}"]`);
        const activeContent = document.getElementById(tabName);
        if (activeTab && activeContent) {
            activeTab.classList.add("active");
            activeContent.style.display = "block";
        }
    }

    // Main tab click
    tabs.forEach(tab => {
        tab.addEventListener("click", (e) => {
            e.preventDefault();
            if (tab.dataset.tab === "time_off") {
                openTab("time_off", "individual_pto"); // force default nested
            } else {
                openTab(tab.dataset.tab);
            }
        });
    });

    // Nested tab click
    nestedTabs.forEach(tab => {
        tab.addEventListener("click", (e) => {
            e.preventDefault();
            openNestedTab(tab.dataset.tab);
            history.replaceState(null, null, "#time_off#" + tab.dataset.tab);
        });
    });

    // Open tab on page load based on hash
    const hash = window.location.hash.substring(1); // remove #
    let mainTab = "employees";
    let nestedTab = null;

    if (hash) {
        const parts = hash.split("#");
        mainTab = parts[0] || "employees";
        nestedTab = parts[1] || null;
    }

    openTab(mainTab, nestedTab);
});