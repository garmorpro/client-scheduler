document.getElementById('openPermissionsBtn').addEventListener('click', function() {
    const isDark = document.body.classList.contains('dark-mode');
    const cardBg = isDark ? '#2a2a3d' : '#f3f4f6';
    const borderColor = isDark ? '#3a3a50' : '#e5e7eb';
    const textColor = isDark ? '#e0e0e0' : '#1a1a1a';
    const mutedColor = isDark ? '#6b6b8a' : '#9ca3af';

    Swal.fire({
        title: '<i class="bi bi-shield-lock me-2"></i>Role Permissions',
        background: isDark ? '#1e1e2f' : '#fff',
        color: textColor,
        width: '800px',
        html: `
            <p style="color:${mutedColor}; margin-bottom: 24px;">Access control and permission levels</p>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; text-align: left;">
                
                <!-- Admin -->
                <div style="background:${cardBg}; border: 1px solid ${borderColor}; border-radius: 12px; padding: 20px;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                        <div style="width:36px; height:36px; background:#7c3aed; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-shield" style="color:white;"></i>
                        </div>
                        <strong style="font-size:15px;">Admin</strong>
                    </div>
                    <span style="background:#7c3aed; color:white; font-size:11px; padding:2px 10px; border-radius:20px; font-weight:600;">Full Access</span>
                    <ul style="margin-top:14px; padding-left:16px; color:${textColor}; font-size:13px; line-height:2;">
                        <li>Edit all schedules</li>
                        <li>Manage holidays</li>
                        <li>Configure settings</li>
                        <li>View all employee details</li>
                    </ul>
                </div>

                <!-- Manager -->
                <div style="background:${cardBg}; border: 1px solid ${borderColor}; border-radius: 12px; padding: 20px;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                        <div style="width:36px; height:36px; background:#2563eb; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-people" style="color:white;"></i>
                        </div>
                        <strong style="font-size:15px;">Manager</strong>
                    </div>
                    <span style="background:#2563eb; color:white; font-size:11px; padding:2px 10px; border-radius:20px; font-weight:600;">Edit Access</span>
                    <ul style="margin-top:14px; padding-left:16px; font-size:13px; line-height:2;">
                        <li style="color:${textColor};">Edit schedules</li>
                        <li style="color:${textColor};">Approve PTO requests</li>
                        <li style="color:${textColor};">View employee details</li>
                        <li style="color:${mutedColor};">No settings access</li>
                    </ul>
                </div>

                <!-- Senior & Staff -->
                <div style="background:${cardBg}; border: 1px solid ${borderColor}; border-radius: 12px; padding: 20px;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                        <div style="width:36px; height:36px; background:#4b5563; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-person" style="color:white;"></i>
                        </div>
                        <strong style="font-size:15px;">Senior & Staff</strong>
                    </div>
                    <span style="background:${isDark ? '#3a3a50' : '#e5e7eb'}; color:${textColor}; font-size:11px; padding:2px 10px; border-radius:20px; font-weight:600;">View Only</span>
                    <ul style="margin-top:14px; padding-left:16px; font-size:13px; line-height:2;">
                        <li style="color:${textColor};">View own schedule</li>
                        <li style="color:${textColor};">View master schedule</li>
                        <li style="color:${mutedColor};">No edit access</li>
                        <li style="color:${mutedColor};">No settings access</li>
                    </ul>
                </div>

            </div>
        `,
        showConfirmButton: false,
        // showCloseButton: true,
    });
});