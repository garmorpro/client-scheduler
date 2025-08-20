
let activityTimeout;
let inactivitySeconds = 0;
const INACTIVITY_LIMIT = 1 * 60 * 1000; // 30 minutes

// Count seconds of inactivity
// setInterval(() => {
//     inactivitySeconds++;
//     console.log("Inactivity time (seconds):", inactivitySeconds);
// }, 1000);

function resetActivityTimer() {
    clearTimeout(activityTimeout);
    inactivitySeconds = 0; // reset inactivity counter

    // Set new timer
    activityTimeout = setTimeout(() => {
        console.log("User inactive, logging out...");
        window.location.href = "/auth/logout.php?timeout=1"; 
    }, INACTIVITY_LIMIT);
}

// List of events that count as "activity"
["click", "mousemove", "keydown", "scroll", "touchstart"].forEach(evt => {
    document.addEventListener(evt, resetActivityTimer, true);
});

// Initialize timer
resetActivityTimer();
