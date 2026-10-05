<?php
$page_title = "Create User";
$page = 'Add User';
$show_breadcrumb = true;

?>


<div class="row">
    <div class="col-xxl-12 col-xl-12 col-lg-12">

        <div class="card__title-wrap mb-20">
            <h5 class="card__heading-title">Add New User</h5>
        </div>

        <form action="<?= $GLOBALS['base_url'] ?>/store-onboard-client" method="POST" enctype="multipart/form-data" autocomplete="off">
            <div class="card__wrapper mb-20">
                <div class="row g-20 align-items-center justify-content-center">


                    <!-- Lead Booking Select -->
                    <div class="col-lg-6 col-12 mb-3">
                        <label for="lead_id">Select Booking CLient <span>*</span></label>
                        <select class="form-control select2" name="lead_id" id="lead_id">
                            <option value="">Select Client</option>
                            <?php foreach ($onboardClients as $lead): ?>
                                <option value="<?= $lead['lead_id'] ?>" data-booking="<?= $lead['lead_booking_id'] ?>">
                                    <?= $lead['full_name'] ?> | <?= $lead['phone'] ?? '' ?> | <?= $lead['email'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="lead_booking_id" id="lead_booking_id" value="">
                    </div>

                    <!-- Email -->
                    <div class="col-lg-6 col-12 mb-3">
                        <label for="email">Contact Email</label>
                        <input class="form-control" name="email" id="email" type="email" autocomplete="off">
                    </div>

                    <!-- Booking Details Display (Full Width) -->
                    <div class="col-lg-12 col-12 mb-3">
                        <div id="booking-details-container" style="font-size: 14px; font-weight: 500;"></div>
                    </div>
                    
                    <!-- Login Email -->
                    <div class="col-lg-6 col-12 mb-3">
                        <label for="login_email">Login Email <span>*</span></label>
                        <input class="form-control" name="login_email" id="login_email" type="email" autocomplete="off">
                    </div>

                    <!-- Password -->
                    <div class="col-lg-6 mb-3">
                        <label for="password">Password <span>*</span></label>
                        <input class="form-control" name="password" id="password" type="password" autocomplete="new-password">
                    </div>


                    <!-- camera Link 1 -->
                    <div class="col-lg-6 mb-3">
                        <label for="cameraLink1">Camera Link 1</label>
                        <input class="form-control" name="cameraLink1" id="cameraLink1" type="text">
                    </div>
                    
                    <!-- camera Link 2 -->
                    <div class="col-lg-6 col-12 mb-3">
                        <label for="cameraLink2">Camera Link 2</label>
                        <input class="form-control" name="cameraLink2" id="cameraLink2" type="text">
                    </div>

                    <!-- purchaseDetailsLink -->
                    <div class="col-lg-12 col-12 mb-3">
                        <label for="purchaseDetailsLink">Purchase Details Link</label>
                        <input class="form-control" name="purchaseDetailsLink" id="purchaseDetailsLink" type="text">
                    </div>

                    <!-- media_link2 -->
                    <div class="col-lg-12 col-12 mb-3">
                        <label for="moneyReceiptLink">Money Receipt Link</label>
                        <input class="form-control" name="moneyReceiptLink" id="moneyReceiptLink" type="text">
                    </div>



                </div>
            </div>

            <div class="submit__btn text-center">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const leadSelect = document.getElementById('lead_id');
    const container = document.getElementById('booking-details-container');
    const leadBookingIdInput = document.getElementById('lead_booking_id');

    function fetchBookingDetails() {
        const leadId = leadSelect.value;
        const selectedOption = leadSelect.options[leadSelect.selectedIndex];
        
        let bookingId = '';
        if (selectedOption) {
            bookingId = selectedOption.getAttribute('data-booking') || '';
        }
        
        leadBookingIdInput.value = bookingId;
        container.innerHTML = '';

        if (leadId) {
            container.innerHTML = '<span style="color: #6c757d;">Fetching projects...</span>';
            
            fetch('<?= $GLOBALS['base_url'] ?>/get-lead-all-bookings/' + leadId)
                .then(response => response.json())
                .then(data => {
                    if (data && data.success && data.data && data.data.length > 0) {
                        let html = '<strong style="color: #198754;">Booked Projects & Flats:</strong><ul style="margin-bottom: 0; padding-left: 1rem;">';
                        data.data.forEach(item => {
                            html += '<li>' + (item.project_name || 'N/A') + ' - ' + (item.flat_name || 'N/A') + '</li>';
                        });
                        html += '</ul>';
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = '<span style="color: #ffc107;">No booking details found for this client.</span>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching bookings:', error);
                    container.innerHTML = '<span style="color: #dc3545;">Failed to fetch booking details.</span>';
                });
        }
    }

    // Native DOM change event
    leadSelect.addEventListener('change', fetchBookingDetails);

    // If Select2 is used, it often uses jQuery to trigger events. We can listen to it.
    if (typeof jQuery !== 'undefined') {
        jQuery('#lead_id').on('select2:select change', function(e) {
            if (!e.originalEvent) { // Triggered by jQuery/Select2 programmatically
                fetchBookingDetails();
            }
        });
    }

    // Initial fetch if value is already selected
    if (leadSelect.value) {
        fetchBookingDetails();
    }
});
</script>