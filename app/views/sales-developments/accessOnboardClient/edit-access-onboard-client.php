<?php
$page_title = "Edit Onboard Client";
$page = 'Edit User';
$show_breadcrumb = true;
?>

<div class="row">
    <div class="col-xxl-12 col-xl-12 col-lg-12">

        <div class="card__title-wrap mb-20">
            <h5 class="card__heading-title">Edit Onboard Client</h5>
        </div>

        <form action="<?= $GLOBALS['base_url'] ?>/update-onboard-client/<?= $onboardClient['id'] ?>" method="POST" autocomplete="off">
            <div class="card__wrapper mb-20">
                <div class="row g-20 align-items-center justify-content-center">

                    <!-- Email -->
                    <div class="col-lg-6 mb-3">
                        <label for="email">Contact Email</label>
                        <input class="form-control" name="email" id="email" type="email" value="<?= $onboardClient['email'] ?>" autocomplete="off">
                    </div>

                    <!-- Login Email -->
                    <div class="col-lg-6 mb-3">
                        <label for="login_email">Login Email <span>*</span></label>
                        <input class="form-control" name="login_email" id="login_email" type="email" value="<?= $onboardClient['login_email'] ?>" autocomplete="off">
                    </div>

                    <!-- Password -->
                    <div class="col-lg-6 mb-3">
                        <label for="password">Password <span>Before Update Retype*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="password" id="password" placeholder="Enter new password if you want" value="" autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                <i class="fa-regular fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        <small class="text-muted">Leave blank if you do not want to change the password</small>
                    </div>

                    <!-- camera Link 1 -->
                    <div class="col-lg-6 mb-3">
                        <label>Camera Link 1</label>
                        <input class="form-control" name="cameraLink1" type="text" value="<?= $onboardClient['cameraLink1'] ?>">
                    </div>
                    <!-- camera Link 2 -->
                    <div class="col-lg-6 mb-3">
                        <label>Camera Link 2</label>
                        <input class="form-control" name="cameraLink2" type="text" value="<?= $onboardClient['cameraLink2'] ?>">
                    </div>
                    <!-- Purchase Details Link -->
                    <div class="col-lg-6 mb-3">
                        <label>Purchase Details Link</label>
                        <input class="form-control" name="purchaseDetailsLink" type="text" value="<?= $onboardClient['purchaseDetailsLink'] ?>">
                    </div>
                    <!-- Money Receipt Link -->
                    <div class="col-lg-6 mb-3">
                        <label>Money Receipt Link</label>
                        <input class="form-control" name="moneyReceiptLink" type="text" value="<?= $onboardClient['moneyReceiptLink'] ?>">
                    </div>


              

                </div>
            </div>

            <div class="submit__btn text-center">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>

    </div>
</div>

<script>
  

    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('togglePasswordIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
