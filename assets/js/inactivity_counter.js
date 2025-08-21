let activityTimeout;
let inactivitySeconds = 0;

// Set limit (1 minute for testing)
const INACTIVITY_LIMIT = 15 * 60 * 1000;

function resetActivityTimer() {
    clearTimeout(activityTimeout);
    inactivitySeconds = 0; // reset counter

    activityTimeout = setTimeout(() => {
        console.log("User inactive, logging out...");
        window.location.href = "/auth/logout.php?timeout=1"; 
    }, INACTIVITY_LIMIT);
}

// Track all relevant activity events
["click", "mousemove", "keydown", "scroll", "touchstart"].forEach(evt => {
    document.addEventListener(evt, resetActivityTimer, true);
});

// Initialize timer
resetActivityTimer();