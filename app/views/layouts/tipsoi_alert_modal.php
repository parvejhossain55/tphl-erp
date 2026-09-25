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

    console.log("[Tipsoi Alert] Script initialized. Last checked:", tipsoiLastCheckTime);

    // For testing purposes, if you want it to show immediately, we can ignore the interval 
    // just by clearing localStorage.
    if (!tipsoiLastCheckTime || (now - parseInt(tipsoiLastCheckTime)) > CHECK_INTERVAL) {
        console.log("[Tipsoi Alert] 30 mins passed or first time. Fetching API after 3 seconds...");
        
        setTimeout(() => {
            let baseUrl = window.base_url || 'https://thepremiumhomesltd.com/TPHL-ERP';
            // Fallback in case window.base_url is empty but we know the subfolder is /TPHL-ERP
            if (baseUrl === '' && window.location.pathname.includes('/TPHL-ERP/')) {
                baseUrl = '/TPHL-ERP';
            }

            const apiUrl = baseUrl + '/api/employee/my-pending-approvals';
            console.log("[Tipsoi Alert] Fetching from:", apiUrl);

            fetch(apiUrl)
                .then(response => {
                    if (!response.ok) throw new Error("HTTP error " + response.status);
                    return response.json();
                })
                .then(res => {
                    console.log("[Tipsoi Alert] API Response:", res);
                    if (res && res.data && res.data.length > 0) {
                        console.log("[Tipsoi Alert] Pending approvals found! Showing popup.");
                        showTipsoiApprovalAlert();
                        localStorage.setItem('tipsoi_alert_last_shown', Date.now().toString());
                    } else if (res && res.data && res.data.length === 0) {
                        console.log("[Tipsoi Alert] No pending approvals.");
                        localStorage.setItem('tipsoi_alert_last_shown', Date.now().toString());
                    }
                })
                .catch(err => {
                    console.error("[Tipsoi Alert] API Fetch Error:", err);
                });
        }, 3000);
    } else {
        let minsLeft = Math.round((CHECK_INTERVAL - (now - parseInt(tipsoiLastCheckTime))) / 60000);
        console.log("[Tipsoi Alert] Skipping check. Next check in " + minsLeft + " minutes.");
    }

    function showTipsoiApprovalAlert() {
        if (typeof Swal !== 'undefined') {
            let baseUrl = window.base_url || 'https://thepremiumhomesltd.com/TPHL-ERP';
            if (baseUrl === '' && window.location.pathname.includes('/TPHL-ERP/')) baseUrl = '/TPHL-ERP';

            Swal.fire({
                html: `
                    <div style="text-align: center; font-family: 'Outfit', sans-serif;">
                        <div style="width: 150px; height: 150px; margin: -60px auto 20px auto; border-radius: 50%; padding: 6px; box-shadow: 0 10px 25px rgba(34, 197, 94, 0.3); background: #fff; position: relative; z-index: 2;">
                            <img src="${baseUrl}/public/assets/images/tipsoi_alert.png" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid #16a34a;" alt="Tipsoi Alert" onerror="this.src='https://via.placeholder.com/150?text=Alert'">
                        </div>
                        <h3 style="margin: 0 0 10px 0; color: #0f172a; font-size: 24px; font-weight: 700;">Tipsoi Approvals!</h3>
                        <p style="margin: 0 0 25px 0; color: #64748b; font-size: 15px; line-height: 1.6;">You have new pending requests awaiting your approval on Tipsoi. Please review them.</p>
                        <a href="${baseUrl}/my-tipsoi-info#profile" class="swal2-confirm swal2-styled" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); padding: 12px 30px; border-radius: 50px; text-decoration: none; display: inline-block; font-weight: 600; box-shadow: 0 8px 20px rgba(22, 163, 74, 0.3);">Check Requests Now</a>
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
        } else {
            console.error("[Tipsoi Alert] SweetAlert (Swal) is not loaded on this page!");
        }
    }
});
</script>
