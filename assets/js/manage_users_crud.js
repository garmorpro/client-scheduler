// manage_users_crud.js — Invite/Edit/Deactivate/Delete for the Manage Users page (admin-only)
document.addEventListener('DOMContentLoaded', function () {
    const ROLES = ['admin', 'manager', 'senior', 'staff', 'intern', 'crm_team'];

    function roleLabel(role) {
        return role.charAt(0).toUpperCase() + role.slice(1).replace('_', ' ');
    }

    function roleOptions(selected) {
        return ROLES.map(r => `<option value="${r}" ${r === selected ? 'selected' : ''}>${roleLabel(r)}</option>`).join('');
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    function swalTheme() {
        const isDark = document.body.classList.contains('dark-mode');
        return {
            background: isDark ? '#1e1e2f' : '#fff',
            color: isDark ? '#e0e0e0' : '#1a1a1a',
        };
    }

    function submitForm(url, fields) {
        const formData = new FormData();
        Object.entries(fields).forEach(([key, value]) => formData.append(key, value));
        return fetch(url, { method: 'POST', body: formData }).then(res => res.json());
    }

    // ---------- Invite / Add User ----------
    const inviteBtn = document.getElementById('inviteUserBtn');
    if (inviteBtn) {
        inviteBtn.addEventListener('click', function () {
            Swal.fire({
                title: 'Invite User',
                ...swalTheme(),
                html: `
                    <div class="mb-3 text-start">
                        <label class="form-label small">Full Name</label>
                        <input type="text" id="newUserFullName" class="form-control form-control-sm" placeholder="Jane Doe">
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label small">Email</label>
                        <input type="email" id="newUserEmail" class="form-control form-control-sm" placeholder="jane@example.com">
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label small">Job Title</label>
                        <input type="text" id="newUserJobTitle" class="form-control form-control-sm" placeholder="Staff Accountant">
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label small">Role</label>
                        <select id="newUserRole" class="form-select form-select-sm">${roleOptions('staff')}</select>
                    </div>
                    <p class="small text-muted mb-0">New users are created with a default password (<code>change_me</code>) and must set a new one on first login.</p>
                `,
                showCancelButton: true,
                confirmButtonText: 'Create User',
                focusConfirm: false,
                preConfirm: () => {
                    const popup = Swal.getPopup();
                    const fullName = popup.querySelector('#newUserFullName').value.trim();
                    const email = popup.querySelector('#newUserEmail').value.trim();
                    const jobTitle = popup.querySelector('#newUserJobTitle').value.trim();
                    const role = popup.querySelector('#newUserRole').value;

                    if (!fullName || !email) {
                        Swal.showValidationMessage('Full name and email are required.');
                        return false;
                    }

                    return submitForm('add_user.php', { full_name: fullName, email, job_title: jobTitle, role })
                        .then(data => {
                            if (!data.success) {
                                Swal.showValidationMessage(data.error || 'Failed to create user.');
                                return false;
                            }
                            return data;
                        })
                        .catch(err => {
                            Swal.showValidationMessage(`Request failed: ${err}`);
                            return false;
                        });
                }
            }).then(result => {
                if (result.isConfirmed) {
                    Swal.fire({ icon: 'success', title: 'User created', timer: 1200, showConfirmButton: false, ...swalTheme() })
                        .then(() => location.reload());
                }
            });
        });
    }

    // ---------- Edit User ----------
    document.querySelectorAll('.edit-user-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const row = btn.closest('tr');
            const userId = row.dataset.userId;
            const fullName = row.dataset.fullName || '';
            const email = row.dataset.email || '';
            const jobTitle = row.dataset.jobTitle || '';
            const role = row.dataset.role || 'staff';
            const status = row.dataset.status || 'active';

            Swal.fire({
                title: 'Edit User',
                ...swalTheme(),
                html: `
                    <div class="mb-3 text-start">
                        <label class="form-label small">Full Name</label>
                        <input type="text" id="editUserFullName" class="form-control form-control-sm" value="${escapeHtml(fullName)}">
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label small">Email</label>
                        <input type="email" id="editUserEmail" class="form-control form-control-sm" value="${escapeHtml(email)}">
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label small">Job Title</label>
                        <input type="text" id="editUserJobTitle" class="form-control form-control-sm" value="${escapeHtml(jobTitle)}">
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label small">Role</label>
                        <select id="editUserRole" class="form-select form-select-sm">${roleOptions(role)}</select>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Save Changes',
                focusConfirm: false,
                preConfirm: () => {
                    const popup = Swal.getPopup();
                    const newFullName = popup.querySelector('#editUserFullName').value.trim();
                    const newEmail = popup.querySelector('#editUserEmail').value.trim();
                    const newJobTitle = popup.querySelector('#editUserJobTitle').value.trim();
                    const newRole = popup.querySelector('#editUserRole').value;

                    if (!newFullName || !newEmail) {
                        Swal.showValidationMessage('Full name and email are required.');
                        return false;
                    }

                    return submitForm('update_user.php', {
                        user_id: userId,
                        full_name: newFullName,
                        email: newEmail,
                        job_title: newJobTitle,
                        role: newRole,
                        status,
                    }).then(data => {
                        if (!data.success) {
                            Swal.showValidationMessage(data.error || 'Failed to update user.');
                            return false;
                        }
                        return data;
                    }).catch(err => {
                        Swal.showValidationMessage(`Request failed: ${err}`);
                        return false;
                    });
                }
            }).then(result => {
                if (result.isConfirmed) {
                    Swal.fire({ icon: 'success', title: 'User updated', timer: 1200, showConfirmButton: false, ...swalTheme() })
                        .then(() => location.reload());
                }
            });
        });
    });

    // ---------- Deactivate / Activate ----------
    document.querySelectorAll('.toggle-status-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const row = btn.closest('tr');
            const userId = row.dataset.userId;
            const currentStatus = row.dataset.status || 'active';
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const actionLabel = newStatus === 'inactive' ? 'deactivate' : 'activate';

            Swal.fire({
                title: `${actionLabel.charAt(0).toUpperCase() + actionLabel.slice(1)} user?`,
                text: `This will ${actionLabel} ${row.dataset.fullName || 'this user'}.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: `Yes, ${actionLabel}`,
                ...swalTheme(),
            }).then(result => {
                if (!result.isConfirmed) return;

                submitForm('update_user.php', {
                    user_id: userId,
                    full_name: row.dataset.fullName || '',
                    email: row.dataset.email || '',
                    job_title: row.dataset.jobTitle || '',
                    role: row.dataset.role || 'staff',
                    status: newStatus,
                }).then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Failed', text: data.error || 'Could not update status.', ...swalTheme() });
                    }
                }).catch(err => Swal.fire({ icon: 'error', title: 'Request failed', text: String(err), ...swalTheme() }));
            });
        });
    });

    // ---------- Delete ----------
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const row = btn.closest('tr');
            const userId = row.dataset.userId;
            const fullName = row.dataset.fullName || 'this user';

            Swal.fire({
                title: 'Delete user?',
                text: `This permanently deletes ${fullName}. This cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                confirmButtonColor: '#dc3545',
                ...swalTheme(),
            }).then(result => {
                if (!result.isConfirmed) return;

                submitForm('delete_user.php', { user_id: userId }).then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Failed', text: data.error || 'Could not delete user.', ...swalTheme() });
                    }
                }).catch(err => Swal.fire({ icon: 'error', title: 'Request failed', text: String(err), ...swalTheme() }));
            });
        });
    });
});
