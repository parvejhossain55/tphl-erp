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
            <h6 class="mb-3">Instructions:</h6>
            <p>Please upload a CSV file with the following columns. The columns must match exactly:</p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center">
                    <thead class="bg-light">
                        <tr>
                            <th>name</th>
                            <th>phone</th>
                            <th>login_email</th>
                            <th>password</th>
                            <th>contact_email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>John Doe</td>
                            <td>01700000000</td>
                            <td>john.login@example.com</td>
                            <td>password123</td>
                            <td>john@example.com</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-muted mt-2"><small>* <strong>name</strong> and <strong>phone</strong> are required to find the exact lead in the system.</small></p>
            <p class="text-muted"><small>* <strong>login_email</strong> and <strong>password</strong> are required for creating login access.</small></p>
            <p class="text-muted"><small>* <strong>contact_email</strong> is optional.</small></p>
        </div>

        <!-- Upload Form -->
        <form action="<?= $GLOBALS['base_url'] ?>/onboard-client-bulk-preview" method="POST" enctype="multipart/form-data">
            <div class="card__wrapper mb-20">
                <div class="row g-20 align-items-center justify-content-center">
                    <div class="col-lg-6 col-12 mb-3">
                        <label for="csv_file">Upload CSV File <span>*</span></label>
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
