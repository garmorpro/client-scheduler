const themeIcon = document.getElementById('themeToggle');

themeIcon.addEventListener('click', () => {
    const isDark = document.body.classList.toggle('dark-mode');

    // Clear any leftover hover background styles
    document.querySelectorAll('.schedule-table td.addable').forEach(td => {
        td.style.removeProperty('background-color');
    });

    // Toggle icon
    themeIcon.classList.toggle('bi-moon-fill', !isDark);
    themeIcon.classList.toggle('bi-sun-fill', isDark);

    // Save to server & session
    fetch('save-theme.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({theme: isDark ? 'dark' : 'light'})
    })
    .then(res => res.json())
    .then(data => {
        if (data.status !== 'success') console.error('Failed to save theme:', data.message);
    })
    .catch(err => console.error('Error saving theme:', err));
});