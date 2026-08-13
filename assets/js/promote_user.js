document.addEventListener("DOMContentLoaded", () => {
            // "Change Role" flyout submenu - opens on hover via CSS, plus a
            // click-toggle here as a fallback for touch devices (no hover
            // state to reveal it there). Bootstrap's own dropdown-close
            // listener fires on any document click, so opening on click
            // needs its propagation stopped or the parent dropdown would
            // close in the same tick it opens.
            document.querySelectorAll(".dropdown-submenu > .role-submenu-trigger").forEach(trigger => {
                trigger.addEventListener("click", (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const submenu = trigger.closest(".dropdown-submenu");
                    document.querySelectorAll(".dropdown-submenu.open").forEach(other => {
                        if (other !== submenu) other.classList.remove("open");
                    });
                    submenu.classList.toggle("open");
                });
            });

            // Reset any click-opened submenu when its parent dropdown closes,
            // so it doesn't render pre-opened the next time that row's menu
            // is opened.
            document.addEventListener("hidden.bs.dropdown", (e) => {
                e.target.querySelectorAll(".dropdown-submenu.open").forEach(el => el.classList.remove("open"));
            });

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
