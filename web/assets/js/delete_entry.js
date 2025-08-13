function deleteEntry(assignmentId) {
    if (confirm('Are you sure you want to delete this assignment?')) {
        fetch('delete_entry.php', {  // Make sure the filename matches here
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `assignment_id=${encodeURIComponent(assignmentId)}`
        })
        .then(response => response.text())
        .then(result => {
            if (result === 'success') {
                location.reload(); // Reload page to reflect deletion
            } else {
                alert('Failed to delete assignment.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the assignment.');
        });
    }
}
