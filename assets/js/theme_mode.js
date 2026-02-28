const themeIcon = document.getElementById('themeToggle');

themeIcon.addEventListener('click', () => {
    const isDark = document.body.classList.toggle('dark-mode');

    // Toggle icon
    themeIcon.classList.toggle('bi-moon-fill', !isDark);
    themeIcon.classList.toggle('bi-sun-fill', isDark);

    // Save to server & session, then reload
    fetch('save-theme.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({theme: isDark ? 'dark' : 'light'})
    })
    .then(res => res.json())
    .then(data => {
        if (data.status !== 'success') {
            console.error('Failed to save theme:', data.message);
        } else {
            // Save scroll position and reload
            const container = document.querySelector('.sheet-container');
            if (container) {
                sessionStorage.setItem('scheduleScrollLeft', container.scrollLeft);
                sessionStorage.setItem('scheduleScrollTop', container.scrollTop);
            }
            location.reload();
        }
    })
    .catch(err => console.error('Error saving theme:', err));
});