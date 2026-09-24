<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Only run for logged in users
if (empty($_SESSION['user']['id'])) {
    return;
}
?>
<style>
/* Custom styling for the tipsoi alert modal */
.tipsoi-swal-popup {
    border-radius: 24px !important;
    padding: 30px !important;
}
.tipsoi-swal-backdrop {
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    background: rgba(15, 23, 42, 0.7) !important;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 30 minutes in milliseconds
    const CHECK_INTERVAL = 30 * 60 * 1000; 
    const tipsoiLastCheckTime = localStorage.getItem('tipsoi_alert_last_shown');
    const now = Date.now();

    // Check if 30 minutes have passed since we last showed the alert
    if (!tipsoiLastCheckTime || (now - parseInt(tipsoiLastCheckTime)) > CHECK_INTERVAL) {
        // Delay the fetch by 3 seconds so it doesn't block other dashboard AJAX calls or session locks
        setTimeout(() => {
            fetch((window.base_url || '') + '/api/employee/my-pending-approvals')
                .then(response => response.json())
                .then(res => {
                    if (res && res.data && res.data.length > 0) {
                        showTipsoiApprovalAlert();
                        localStorage.setItem('tipsoi_alert_last_shown', Date.now().toString());
                    } else if (res && res.data && res.data.length === 0) {
                        localStorage.setItem('tipsoi_alert_last_shown', Date.now().toString());
                    }
                })
                .catch(err => {
                    console.error("Tipsoi Alert API Error:", err);
                });
        }, 3000);
    }

    function showTipsoiApprovalAlert() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                html: `
                    <div style="text-align: center; font-family: 'Outfit', sans-serif;">
                        <div style="width: 150px; height: 150px; margin: -60px auto 20px auto; border-radius: 50%; padding: 6px; box-shadow: 0 10px 25px rgba(34, 197, 94, 0.3); background: #fff; position: relative; z-index: 2;">
                            <img src="${(window.base_url || '')}/public/assets/images/tipsoi_alert.png" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid #16a34a;" alt="Tipsoi Alert">
                        </div>
                        <h3 style="margin: 0 0 10px 0; color: #0f172a; font-size: 24px; font-weight: 700;">Tipsoi Approvals!</h3>
                        <p style="margin: 0 0 25px 0; color: #64748b; font-size: 15px; line-height: 1.6;">You have new pending requests awaiting your approval on Tipsoi. Please review them.</p>
                        <a href="${(window.base_url || '')}/my-tipsoi-info#profile" class="swal2-confirm swal2-styled" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); padding: 12px 30px; border-radius: 50px; text-decoration: none; display: inline-block; font-weight: 600; box-shadow: 0 8px 20px rgba(22, 163, 74, 0.3);">Check Requests Now</a>
                    </div>
                `,
                showConfirmButton: false,
                showCloseButton: true,
                background: '#ffffff',
                customClass: {
                    popup: 'tipsoi-swal-popup',
                    backdrop: 'tipsoi-swal-backdrop'
                }
            });
        }
    }
});
</script>
