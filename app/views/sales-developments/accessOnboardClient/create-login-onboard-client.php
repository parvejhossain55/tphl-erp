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

        <form action="<?= $GLOBALS['base_url'] ?>/store-onboard-client" method="POST" enctype="multipart/form-data">
            <div class="card__wrapper mb-20">
                <div class="row g-20 align-items-center justify-content-center">


                    <!-- Lead Booking Select -->
                    <div class="col-lg-6 col-12 mb-3">
                        <label for="lead_booking_id">Select Lead Booking <span>*</span></label>
                        <select class="form-control" name="lead_booking_id" id="lead_booking_id">
                            <option value="">Select Client</option>
                            <?php foreach ($onboardClients as $client): ?>
                                <option value="<?= $client['id'] ?>"><?= $client['full_name'] ?> (<?= $client['booking_pay_date'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Project Name -->
                    <div class="col-lg-6 col-12 mb-3">
                        <label for="project_name">Project</label>
                        <input type="text" class="form-control" id="project_name" readonly>
                    </div>

                    <!-- Flat Name -->
                    <div class="col-lg-6 mb-3">
                        <label for="flat_name">Flat</label>
                        <input type="text" class="form-control" id="flat_name" readonly>
                    </div>

                    <!-- Email -->
                    <div class="col-lg-6 col-12 mb-3">
                        <label for="email">Email <span>*</span></label>
                        <input class="form-control" name="email" id="email" type="email">
                    </div>
                    <!-- Password -->
                    <div class="col-lg-6 mb-3">
                        <label for="password">Password <span>*</span></label>
                        <input class="form-control" name="password" id="password" type="password">
                    </div>


                    <!-- camera Link 2 -->
                    <div class="col-lg-6 col-12 mb-3">
                        <label for="designation">camera Link 2<span></span></label>
                        <input class="form-control" name="cameraLink2" id="cameraLink2" type="text">
                    </div>
                    <!-- camera Link 1 -->
                    <div class="col-lg-6 mb-3">
                        <label for="designation">camera Link 1<span></span></label>
                        <input class="form-control" name="cameraLink1" id="cameraLink1" type="text">
                    </div>
                    <!-- purchaseDetailsLink -->
                    <div class="col-lg-6 col-12 mb-3">
                        <label for="designation">Purchase Details Link<span></span></label>
                        <input class="form-control" name="purchaseDetailsLink" id="purchaseDetailsLink" type="text">
                    </div>
                    <!-- media_link2 -->
                    <div class="col-lg-12 col-12 mb-3">
                        <label for="media_link1">Money Receipt Link <span></span></label>
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
    document.getElementById('lead_booking_id').addEventListener('change', function() {
        const leadBookingId = this.value;
        if (!leadBookingId) {
            document.getElementById('project_name').value = '';
            document.getElementById('flat_name').value = '';
            return;
        }

        fetch(`<?= $GLOBALS['base_url'] ?>/get-lead-booking-details/${leadBookingId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('project_name').value = data.data.project_name;
                    document.getElementById('flat_name').value = data.data.flat_name;
                } else {
                    document.getElementById('project_name').value = '';
                    document.getElementById('flat_name').value = '';
                }
            }).catch(err => console.error(err));

    });
</script>