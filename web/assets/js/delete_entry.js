function deleteEntry(assignmentId) {
    if (confirm('Are you sure you want to delete this assignment?')) {
        fetch('delete-assignment.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `assignment_id=${assignmentId}`
        })
        .then(response => response.text())
        .then(result => {
            if (result === 'success') {
                location.reload(); // Reload the page to reflect changes
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