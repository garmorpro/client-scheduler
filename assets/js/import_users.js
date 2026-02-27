document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('importUsersForm');
    const importModal = document.getElementById('importUsersModal');

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

            // Close the import modal first
            const modalInstance = bootstrap.Modal.getInstance(importModal);
            if (modalInstance) {
                modalInstance.hide();
            }

            // Build HTML message
            let htmlMsg = `<p><strong>Successfully imported:</strong> ${data.successCount}</p>`;

            if (data.errors.length) {
                htmlMsg += `<p><strong>Errors:</strong></p><ul>`;
                data.errors.forEach(err => {
                    htmlMsg += `<li>Row ${err.row}: ${err.message}</li>`;
                });
                htmlMsg += `</ul>`;
            }

            // Show SweetAlert2 modal
            Swal.fire({
                title: 'Import Results',
                html: htmlMsg,
                icon: data.errors.length ? 'warning' : 'success',
                confirmButtonText: 'OK',
                width: 500
            }).then(() => {
                // Refresh the page when user clicks OK
                window.location.reload();
            });
        })
        .catch(err => {
            console.error('AJAX Error:', err);
            Swal.fire({
                title: 'Error',
                text: 'Something went wrong. Check console.',
                icon: 'error'
            });
        });
    });
});