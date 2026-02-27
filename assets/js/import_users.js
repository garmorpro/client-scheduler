document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('importUsersForm');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // prevent page reload

        const formData = new FormData(form);

        fetch('../../pages/import_users.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            console.log('AJAX Response:', data);

            // Show success/errors in the modal
            let msg = `Successfully imported: ${data.successCount}\n`;
            if (data.errors.length) {
                msg += 'Errors:\n';
                data.errors.forEach(err => {
                    msg += `Row ${err.row}: ${err.message}\n`;
                });
            }
            alert(msg); // you can replace this with a nicer modal display
        })
        .catch(err => {
            console.error('AJAX Error:', err);
            alert('Something went wrong. Check console.');
        });
    });
});