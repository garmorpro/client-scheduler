document.addEventListener("DOMContentLoaded", () => {
            const promoteLinks = document.querySelectorAll(".promote-user");

            function roleLabel(role) {
                const r = (role || '').toLowerCase();
                if (r === 'crm_team') return 'CRM Team';
                if (!r) return '';
                return r.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            }

            function updateRole(userId, newRole) {
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
                        location.reload();
                    } else {
                        if (typeof appNotify !== 'undefined') {
                            appNotify({ icon: 'error', title: 'Could not update role', text: data.error || 'Please try again.' });
                        } else {
                            alert("Error: " + data.error);
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                    if (typeof appNotify !== 'undefined') {
                        appNotify({ icon: 'error', title: 'Request failed', text: String(err) });
                    } else {
                        alert("AJAX request failed.");
                    }
                });
            }

            promoteLinks.forEach(link => {
                link.addEventListener("click", function(e) {
                    e.preventDefault();
                    const userId = this.dataset.userId;
                    const userName = this.dataset.userName;
                    const newRole = this.dataset.newRole;

                    if (typeof appConfirm !== 'undefined') {
                        appConfirm({
                            icon: 'question',
                            title: "Change this employee's role?",
                            text: `Change ${userName}'s role to ${roleLabel(newRole)}?`,
                            confirmText: 'Yes, change it'
                        }).then(confirmed => { if (confirmed) updateRole(userId, newRole); });
                    } else if (confirm(`Are you sure you want to change ${userName}'s role to ${newRole}?`)) {
                        updateRole(userId, newRole);
                    }
                });
            });
        });
