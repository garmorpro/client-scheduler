document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('onboardEmployeeModal');
    const form = document.getElementById('onboardEmployeeForm');
    if (!modalEl || !form) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const backBtn = document.getElementById('onboardBackBtn');
    const nextBtn = document.getElementById('onboardNextBtn');
    const progressFill = document.getElementById('onboardProgressFill');
    const progressLabel = document.getElementById('onboardProgressLabel');

    // Full sequence, used as the starting assumption before the role is
    // even picked (so it reads "Step 1 of 4" right away instead of "of 1")
    // - computeActiveSteps() below re-derives the real, possibly-shorter
    // list once Next is clicked past Basic Info and the role is known.
    const ALL_STEPS = ['basic', 'manager', 'training', 'review'];

    const roleSelect = document.getElementById('onboard_role');
    const fullNameInput = document.getElementById('onboard_full_name');
    const emailInput = document.getElementById('onboard_email');
    const jobTitleInput = document.getElementById('onboard_job_title');
    const managerSelect = document.getElementById('onboard_manager_id');

    // Manager step only applies to Staff/Senior (matches
    // set_direct_reports.php's own role restriction on users.manager_id);
    // Training step only applies to Staff/Intern (matches add_user.php's
    // auto-restriction seeding). Basic and Review always show.
    function computeActiveSteps() {
        const role = roleSelect.value;
        const steps = ['basic'];
        if (['staff', 'senior'].includes(role)) steps.push('manager');
        if (['staff', 'intern'].includes(role)) steps.push('training');
        steps.push('review');
        return steps;
    }

    let activeSteps = ALL_STEPS;
    let stepIndex = 0;

    function showStep(stepName) {
        form.querySelectorAll('.onboard-step').forEach(el => {
            el.classList.toggle('d-none', el.dataset.step !== stepName);
        });

        progressFill.style.width = `${((stepIndex + 1) / activeSteps.length) * 100}%`;
        progressLabel.textContent = `Step ${stepIndex + 1} of ${activeSteps.length}`;
        backBtn.textContent = stepIndex === 0 ? 'Cancel' : 'Back';
        nextBtn.textContent = stepName === 'review' ? 'Onboard Employee' : 'Next';

        if (stepName === 'review') renderReview();
    }

    function roleLabel(role) {
        if (role === 'crm_team') return 'CRM Team';
        return role.charAt(0).toUpperCase() + role.slice(1);
    }

    function renderReview() {
        const role = roleSelect.value;
        const rows = [
            ['Full Name', fullNameInput.value || '—'],
            ['Email', emailInput.value || '—'],
            ['Role', roleLabel(role)],
            ['Job Title', jobTitleInput.value || '—'],
        ];
        if (activeSteps.includes('manager')) {
            const managerName = managerSelect.selectedOptions[0] ? managerSelect.selectedOptions[0].textContent : 'No manager yet';
            rows.push(['Manager', managerSelect.value ? managerName : 'No manager yet']);
        }
        document.getElementById('onboardReviewList').innerHTML = rows.map(([label, value]) => `
            <div class="onboard-review-row"><span class="label">${label}</span><span class="value">${escapeHtml(value)}</span></div>
        `).join('') + (activeSteps.includes('training')
            ? '<div class="onboard-review-note">Will start restricted on all 13 training criteria - clear them from the Training page as this person is tested and documented on each.</div>'
            : '');
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    backBtn.addEventListener('click', () => {
        if (stepIndex === 0) {
            modal.hide();
            return;
        }
        stepIndex -= 1;
        showStep(activeSteps[stepIndex]);
    });

    nextBtn.addEventListener('click', async () => {
        const currentStep = activeSteps[stepIndex];

        if (currentStep === 'basic') {
            if (!form.reportValidity()) return;
            activeSteps = computeActiveSteps();
            stepIndex = 0;
            const firstName = (fullNameInput.value || '').trim();
            document.getElementById('onboardManagerStepName').textContent = firstName || 'this person';
            document.getElementById('onboardTrainingStepName').textContent = firstName || 'This person';
        }

        if (currentStep === 'review') {
            await submitForm();
            return;
        }

        stepIndex += 1;
        showStep(activeSteps[stepIndex]);
    });

    async function submitForm() {
        nextBtn.disabled = true;
        const originalLabel = nextBtn.textContent;
        nextBtn.textContent = 'Onboarding…';

        // Manager only actually applies when the Manager step was shown -
        // clear it before submitting in case a role switch (back to Basic,
        // change role, forward again) left a stale selection from an
        // earlier pass through a step that no longer applies.
        if (!activeSteps.includes('manager')) managerSelect.value = '';

        try {
            const formData = new FormData(form);
            const response = await fetch('add_user.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                modal.hide();
                location.reload();
            } else {
                if (typeof appNotify !== 'undefined') {
                    appNotify({ icon: 'error', title: 'Could not onboard employee', text: result.error || 'Please try again.' });
                } else {
                    alert('Error: ' + (result.error || 'Could not onboard employee.'));
                }
            }
        } catch (error) {
            console.error('Failed to onboard employee', error);
            if (typeof appNotify !== 'undefined') {
                appNotify({ icon: 'error', title: 'Network error', text: 'Could not save. Please try again.' });
            }
        } finally {
            nextBtn.disabled = false;
            nextBtn.textContent = originalLabel;
        }
    }

    modalEl.addEventListener('show.bs.modal', () => {
        form.reset();
        activeSteps = ALL_STEPS;
        stepIndex = 0;
        showStep('basic');
    });
});
