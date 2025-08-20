let activityTimeout;
let inactivitySeconds = 0;

// For testing, set a short limit (1 minute here)
const INACTIVITY_LIMIT = 1 * 60 * 1000; // 1 minute

// Optional: log seconds of inactivity
// setInterval(() => {
//     inactivitySeconds++;
//     console.log("Inactivity time (seconds):", inactivitySeconds);
// }, 1000);

function resetActivityTimer() {
    clearTimeout(activityTimeout);
    inactivitySeconds = 0; // reset inactivity counter

    activityTimeout = setTimeout(() => {
        console.log("User inactive, logging out...");
        // Redirect to logout with timeout flag
        window.location.href = "/auth/logout.php?timeout=1"; 
    }, INACTIVITY_LIMIT);
}

// List of events that count as "activity"
["click", "mousemove", "keydown", "scroll", "touchstart"].forEach(evt => {
    document.addEventListener(evt, resetActivityTimer, true);
});

// Initialize timer on page load
resetActivityTimer();

