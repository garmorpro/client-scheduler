document.addEventListener("DOMContentLoaded", () => {
            const promoteLinks = document.querySelectorAll(".promote-user");
        
            promoteLinks.forEach(link => {
                link.addEventListener("click", function(e) {
                    e.preventDefault();
                    const userId = this.dataset.userId;
                    const userName = this.dataset.userName;
                    const newRole = this.dataset.newRole;
                
                    if (!confirm(`Are you sure you want to change ${userName}'s role to ${newRole}?`)) return;
                
                    fetch('update_role.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ user_id: userId, new_role: newRole })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // alert("Role updated successfully!");
                            // Optionally reload or update table row dynamically
                            location.reload();
                        } else {
                            alert("Error: " + data.error);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert("AJAX request failed.");
                    });
                });
            });
        });