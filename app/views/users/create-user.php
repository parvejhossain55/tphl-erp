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

        <form action="<?= $GLOBALS['base_url'] ?>/store-user" method="POST" enctype="multipart/form-data">
            <div class="card__wrapper mb-20">
                <div class="row g-20 align-items-center justify-content-center">

                    <!-- Full Name -->
                    <div class="col-lg-6 mb-3">
                        <label for="fullName">Full Name <span>*</span></label>
                        <input class="form-control" name="full_name" id="fullName" type="text" required>
                    </div>
                    <!-- User Name -->
                    <div class="col-lg-6 mb-3">
                        <label for="fullName">User Name<span>*</span></label>
                        <input class="form-control" name="user_name" id="user_name" type="text" required>
                    </div>

                    <!-- Contact Number -->
                    <div class="col-lg-6 mb-3">
                        <label for="contactnumber">Contact Number <span>*</span></label>

                        <input class="form-control" minlength="11" maxlength="11" name="phone" min="11" max="11" id="contactnumber" type="text" >
                    </div>

                    <!-- Email -->
                    <div class="col-lg-6 mb-3">
                        <label for="email">Email <span>*</span></label>
                        <input class="form-control" name="email" id="email" type="email" >
                    </div>

                    <!-- Designation -->
                    <div class="col-lg-6 mb-3">
                        <label for="designation">Designation <span>*</span></label>
                        <select class="form-control" name="designation_id" id="designation_id" >
                            <option value="">Select Designation</option>
                            <?php foreach ($designations as $designation): ?>
                                <option value="<?= $designation['id'] ?>"><?= $designation['designation_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Address -->
                    <div class="col-lg-6 mb-3">
                        <label for="address">Address <span>*</span></label>
                        <textarea class="form-control" name="address" id="address" required></textarea>
                    </div>

                    <!-- NID -->
                    <div class="col-lg-6 mb-3">
                        <label for="nid">NID Number <span>*</span></label>
                        <input class="form-control" name="nid" id="nid" type="text" >
                    </div>


                    <!-- Location -->
                    <div class="col-xl-6">
                        <label>Role *</label>
                        <div class="d-flex gap-2">
                            <select name="role_id" class="form-control" required style="flex:1;">
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id']; ?>"><?= $role['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <a href="<?= $GLOBALS['base_url'] ?>/permissions"
                                class="btn btn-secondary"
                                style="height: 38px; display:flex; align-items:center; padding:0 12px;">
                                + role
                            </a>
                        </div>
                    </div>

                    <!-- Designation -->
                    <div class="col-lg-6 mb-3">
                        <label for="designation">First Media Link<span></span></label>
                        <input class="form-control" name="media_link1" id="media_link1" type="text" >
                    </div>
                    <!-- media_link2 -->
                    <div class="col-lg-6 mb-3">
                        <label for="media_link1">Second Media Link <span></span></label>
                        <input class="form-control" name="media_link2" id="media_link2" type="text" >
                    </div>

                    <!-- Password -->
                    <div class="col-lg-6 mb-3">
                        <label for="password">Password <span>*</span></label>
                        <input class="form-control" name="password" id="password" type="password" >
                    </div>

                    <!-- Photo -->
                    <div class="col-lg-6 mb-3">
                        <label for="userphoto">User Photo (100px × 100px)</label>
                        <input class="form-control" name="userphoto" id="userphoto" type="file" accept="image/*">
                    </div>

                </div>
            </div>

            <div class="submit__btn text-center">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>

    </div>
</div>

</div>