document.querySelectorAll('.add-engagement-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const clientId = this.dataset.clientId;
        const clientName = this.dataset.clientName;
        const isDark = document.body.classList.contains('dark-mode');

        const managerOptions = managersList.map(m => 
            `<option value="${m}">${m}</option>`
        ).join('');

        Swal.fire({
            title: 'Add Engagement',
            background: isDark ? '#2a2a3d' : '#fff',
            color: isDark ? '#e0e0e0' : '#1a1a1a',
            html: `
                <div class="mb-3 text-start">
                    <label class="form-label">Client</label>
                    <input type="text" class="form-control" value="${clientName}" disabled>
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label">Budget Hours</label>
                    <input type="number" min="0" class="form-control" id="swal-budget-hours" required>
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="swal-status">
                        <option value="confirmed">Confirmed</option>
                        <option value="pending">Pending</option>
                        <option value="not_confirmed">Not Confirmed</option>
                    </select>
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label">Manager</label>
                    <select class="form-select" id="swal-manager">
                        <option value="">Select Manager</option>
                        ${managerOptions}
                    </select>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Add',
            cancelButtonText: 'Cancel',
            confirmButtonColor: isDark ? '#3a3a50' : '#003f47',
            cancelButtonColor: isDark ? '#555572' : '#6c757d',
            preConfirm: () => {
                const budgetHours = document.getElementById('swal-budget-hours').value;
                const status = document.getElementById('swal-status').value;
                const manager = document.getElementById('swal-manager').value;

                if (!budgetHours || budgetHours <= 0) {
                    Swal.showValidationMessage('Please enter valid budget hours.');
                    return false;
                }
                if (!manager) {
                    Swal.showValidationMessage('Please select a manager.');
                    return false;
                }

                return fetch('add_engagement.php', {
                    method: 'POST',
                    body: (() => {
                        const formData = new FormData();
                        formData.append('client_id', clientId);
                        formData.append('client_name', clientName);
                        formData.append('budget_hours', budgetHours);
                        formData.append('status', status);
                        formData.append('manager', manager);
                        formData.append('year', new Date().getFullYear());
                        return formData;
                    })()
                })
                .then(res => res.json())
                .catch(err => Swal.showValidationMessage(`Request failed: ${err}`));
            }
        }).then(result => {
            if (result.isConfirmed && result.value && result.value.success) {
                Swal.fire({
                    title: 'Success!',
                    text: 'Engagement added successfully.',
                    icon: 'success',
                    background: isDark ? '#2a2a3d' : '#fff',
                    color: isDark ? '#e0e0e0' : '#1a1a1a',
                    confirmButtonColor: isDark ? '#3a3a50' : '#003f47',
                }).then(() => location.reload());
            }
        });
    });
});