<?php
$page_title = "Bulk Upload Onboard Clients";
$page = 'Bulk Upload User';
$show_breadcrumb = true;
?>

<div class="row">
    <div class="col-xxl-12 col-xl-12 col-lg-12">
        <div class="card__title-wrap mb-20">
            <h5 class="card__heading-title">Bulk Upload Onboard Clients</h5>
        </div>

        <!-- Instructions -->
        <div class="card__wrapper mb-20">
            <h6 class="mb-3">CSV Column Format:</h6>
            <p>Please upload a CSV file with the following columns. The columns must match exactly (case-insensitive):</p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center">
                    <thead class="bg-light">
                        <tr>
                            <th>project_name <span class="text-danger">*</span></th>
                            <th>flat_name <span class="text-danger">*</span></th>
                            <th>login_email <span class="text-danger">*</span></th>
                            <th>password <span class="text-danger">*</span></th>
                            <th>contact_email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>The Premium Glory Garden</td>
                            <td>7A</td>
                            <td>client.login@example.com</td>
                            <td>password123</td>
                            <td>client@example.com</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="alert alert-info mt-3 mb-0">
                <ul class="mb-0 ps-3">
                    <li><strong>project_name</strong> এবং <strong>flat_name</strong> দিয়ে system-এ lead খোঁজা হবে — এগুলো <strong>lead_bookings</strong> table-এর সাথে match করবে।</li>
                    <li>Match পেলে সেই lead-এর <strong>name</strong> ও <strong>phone</strong> preview-এ দেখাবে — আপনি confirm করলে login save হবে।</li>
                    <li><strong>login_email</strong> ও <strong>password</strong> — client portal-এ login করার জন্য।</li>
                    <li><strong>contact_email</strong> optional — যোগাযোগের email।</li>
                    <li>Booking status অবশ্যই <strong>booked</strong> বা <strong>completed</strong> হতে হবে।</li>
                </ul>
            </div>
        </div>

        <!-- Upload Form -->
        <form action="<?= $GLOBALS['base_url'] ?>/onboard-client-bulk-preview" method="POST" enctype="multipart/form-data">
            <div class="card__wrapper mb-20">
                <div class="row g-20 align-items-center justify-content-center">
                    <div class="col-lg-6 col-12 mb-3">
                        <label for="csv_file">Upload CSV File <span class="text-danger">*</span></label>
                        <input class="form-control" name="csv_file" id="csv_file" type="file" accept=".csv" required>
                    </div>
                </div>
            </div>

            <div class="submit__btn text-center">
                <button type="submit" class="btn btn-primary">Preview Upload</button>
            </div>
        </form>

    </div>
</div>
