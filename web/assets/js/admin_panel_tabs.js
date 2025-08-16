document.addEventListener("DOMContentLoaded", function() {
    // Main tabs
    const tabs = document.querySelectorAll(".custom-tabs button, .custom-tabs a");
    const tabContents = document.querySelectorAll(".tab-content");

    // Nested tabs inside #time_off
    const nestedTabs = document.querySelectorAll("#time_off .nested-tabs a");
    const nestedContents = document.querySelectorAll("#time_off .nested-tab-content");

    function openTab(tabName, nestedTabName = null) {
        // Main tab logic
        tabs.forEach(tab => tab.classList.remove("active"));
        tabContents.forEach(content => content.style.display = "none");

        const activeTab = document.querySelector(`.custom-tabs button[data-tab="${tabName}"], .custom-tabs a[data-tab="${tabName}"]`);
        const activeContent = document.getElementById(tabName);
        if (activeTab && activeContent) {
            activeTab.classList.add("active");
            activeContent.style.display = "block";
        }

        // Nested tab logic (if time_off)
        if (tabName === "time_off") {
            const defaultNestedTab = nestedTabName || "individual_pto";
            openNestedTab(defaultNestedTab);
        }

        // Update URL hash with main and nested
        const hash = tabName + (nestedTabName ? "#" + nestedTabName : "");
        history.replaceState(null, null, "#" + hash);
    }

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
            openTab(tab.dataset.tab);
        });
    });

    // Nested tab click
    nestedTabs.forEach(tab => {
        tab.addEventListener("click", (e) => {
            e.preventDefault();
            openNestedTab(tab.dataset.tab);
            // Update hash with both main and nested
            const mainTab = "time_off";
            history.replaceState(null, null, "#" + mainTab + "#" + tab.dataset.tab);
        });
    });

    // Open on page load
    const hash = window.location.hash.substring(1); // remove #
    let mainTab = "users";
    let nestedTab = null;

    if (hash) {
        const parts = hash.split("#");
        mainTab = parts[0] || "users";
        nestedTab = parts[1] || null;
    }

    openTab(mainTab, nestedTab);
});