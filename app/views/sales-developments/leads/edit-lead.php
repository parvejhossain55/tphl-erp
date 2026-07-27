<?php
$page = "Edit Lead";

$selectedSourceId = $_SESSION['old']['source_type_id']
    ?? $leadData['source_type_id']
    ?? null;
?>


<!-- new lead modal start -->
<div class="row">
    <div class="card__wrapper">

        <form action="<?= $GLOBALS['base_url'] ?>/update-lead/<?= $leadData['id'] ?>"
            method="POST"
            enctype="multipart/form-data">
            <div class="row gy-10">


                <!-- ===== Personal Information (Bangla) ===== -->
                <div class="col-12 mt-4">
                    <h5 class="border-bottom pb-2">Personal Info </h5>
                </div>
                <!-- Lead Name -->
                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="leadName">Lead Name(BLOCK LETTER) *</label>
                        <input class="form-control" name="full_name" id="leadName" type="text" value="<?= htmlspecialchars($leadData['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>


                <div class="col-md-6">
                    <label>নাম (বাংলা)</label>
                    <input type="text" class="form-control" name="lead_name_bn"
                        value="<?= htmlspecialchars($leadData['lead_name_bn'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <!--Father  Name -->
                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="fatherName">Father Name(BLOCK LETTER) *</label>
                        <input class="form-control" name="father_name" id="fatherName" type="text" value="<?= htmlspecialchars($leadData['father_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <label>পিতার নাম (বাংলা)</label>
                    <input type="text" class="form-control" name="father_name_bn"
                        value="<?= htmlspecialchars($leadData['father_name_bn'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <!--Mother  Name -->
                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="motherName">Mother Name(BLOCK LETTER) *</label>
                        <input class="form-control" name="mother_name" id="motherName" type="text" value="<?= htmlspecialchars($leadData['mother_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="col-md-6">
                    <label>মাতার নাম (বাংলা)</label>
                    <input type="text" class="form-control" name="mother_name_bn"
                        value="<?= htmlspecialchars($leadData['mother_name_bn'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-md-6">
                    <label>Date Of Birth</label>
                    <input type="date" class="form-control" name="dob"
                        value="<?= htmlspecialchars($leadData['dob'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Designation -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label>Profession</label>
                        <input type="text" class="form-control" name="designation" value="<?= htmlspecialchars($leadData['designation'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <label>পেশা </label>
                    <input type="text"
                        class="form-control"
                        name="profession"
                        placeholder="যেমন: ব্যবসায়ী / চাকরিজীবী"
                        value="<?= htmlspecialchars($leadData['profession_bn'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>


                <div class="col-md-6">
                    <label>জাতীয়তা</label>
                    <input type="text" class="form-control" name="nationality"
                        value="<?= htmlspecialchars($leadData['nationality'] ?? 'Bangladeshi', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-md-6">
                    <label>Religion </label>
                    <select name="religion" class="form-select">

                        <option value="Islam" <?= ($leadData['religion'] ?? '') == 'Islam' ? 'selected' : '' ?>>Islam</option>
                        <option value="Islam" <?= ($leadData['religion'] ?? '') == 'Islam' ? 'selected' : '' ?>>Islam</option>
                        <option value="Hindu" <?= ($leadData['religion'] ?? '') == 'Hindu' ? 'selected' : '' ?>>Hindu</option>
                        <option value="Christian" <?= ($leadData['religion'] ?? '') == 'Christian' ? 'selected' : '' ?>>Christian</option>
                        <option value="Buddhist" <?= ($leadData['religion'] ?? '') == 'Buddhist' ? 'selected' : '' ?>>Buddhist</option>
                        <option value="Other" <?= ($leadData['religion'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>


                <!-- Phone -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="phoneNumber">Mobile Number(Whatsapp)*</label>
                        <input class="form-control" name="phone" id="phoneNumber" type="text" value="<?= htmlspecialchars($leadData['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>



                <!-- Email -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="emailAddress">Email*</label>
                        <input class="form-control" name="email" id="emailAddress" type="email" value="<?= htmlspecialchars($leadData['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>




                <!-- Address -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label>Present Address</label>
                        <textarea class="form-control" name="address"><?= htmlspecialchars($leadData['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

                <div class="col-md-6">
                    <label>বর্তমান ঠিকানা</label>
                    <textarea class="form-control" name="present_add_bn"><?= htmlspecialchars($leadData['present_add_bn'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <!-- Permanent Address -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label>Permanent Address</label>
                        <textarea class="form-control" name="permanent_address"><?= htmlspecialchars($leadData['permanent_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <label>স্থায়ী ঠিকানা</label>
                    <textarea class="form-control" name="permanent_add_bn"><?= htmlspecialchars($leadData['permanent_add_bn'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>



                <div class="col-md-6">
                    <label>NID / Passport No</label>
                    <input type="text" class="form-control" name="nid_or_passport"
                        value="<?= htmlspecialchars($leadData['nid_or_passport'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-md-6">
                    <label>NID / Passport Type</label>
                    <select name="identity_type" class="form-select">
                        <option value="NID" <?= ($leadData['identity_type'] ?? '') == 'NID' ? 'selected' : '' ?>>NID</option>
                        <option value="Passport" <?= ($leadData['identity_type'] ?? '') == 'Passport' ? 'selected' : '' ?>>Passport</option>
                    </select>
                </div>
                   <!--  Image -->
                <div class="col-md-6">
                    <label>Client Photo</label>
                    <input type="file" class="form-control" name="lead_image">
                </div>

                <!-- ===== Nominee Information ===== -->
                <div class="col-12 mt-4">
                    <h5 class="border-bottom pb-2">Nominee Information</h5>
                </div>

                <!-- Nominee Name -->
                <div class="col-md-6">
                    <label>Nominee Name</label>
                    <input type="text" class="form-control"
                        name="nominee_name"
                        value="<?= htmlspecialchars($leadData['nominee_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Relation With Lead -->
                <div class="col-md-6">
                    <label>Relation With Applicant</label>
                    <input type="text" class="form-control"
                        name="relation_with_lead"
                        value="<?= htmlspecialchars($leadData['relation_with_lead'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Nominee Father's Name -->
                <div class="col-md-6">
                    <label>Father's Name</label>
                    <input type="text" class="form-control"
                        name="nomi_father_name"
                        value="<?= htmlspecialchars($leadData['nomi_father_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Nominee Mother's Name -->
                <div class="col-md-6">
                    <label>Mother's Name</label>
                    <input type="text" class="form-control"
                        name="nomi_mother_name"
                        value="<?= htmlspecialchars($leadData['nomi_mother_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Nominee Profession -->
                <div class="col-md-6">
                    <label>Profession</label>
                    <input type="text" class="form-control"
                        name="nomi_profession"
                        value="<?= htmlspecialchars($leadData['nomi_profession'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Nominee Date of Birth -->
                <div class="col-md-6">
                    <label>Date of Birth</label>
                    <input type="date" class="form-control"
                        name="nomi_dob"
                        value="<?= htmlspecialchars($leadData['nomi_dob'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Nominee Phone -->
                <div class="col-md-6">
                    <label>Mobile Number</label>
                    <input type="text" class="form-control"
                        name="nomi_phone"
                        value="<?= htmlspecialchars($leadData['nomi_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Nominee Email -->
                <div class="col-md-6">
                    <label>Email</label>
                    <input type="email" class="form-control"
                        name="nomi_email"
                        value="<?= htmlspecialchars($leadData['nomi_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- NID / Passport -->
                <div class="col-md-6">
                    <label>NID / Passport</label>
                    <input type="text" class="form-control"
                        name="nomi_nid_passport"
                        value="<?= htmlspecialchars($leadData['nomi_nid_passport'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Present Address -->
                <div class="col-md-6">
                    <label>Present Address</label>
                   <textarea class="form-control"
                        name="nomi_present_address"><?= htmlspecialchars($leadData['nomi_present_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <!-- Permanent Address -->
                <div class="col-md-6">
                    <label>Permanent Address</label>
                    <textarea class="form-control"
                        name="nomi_permanent_address"><?= htmlspecialchars($leadData['nomi_permanent_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <!-- Nominee Image -->
                <div class="col-md-6">
                    <label>Nominee Photo</label>
                    <input type="file" class="form-control" name="nomi_image">
                </div>
    </div>

    <div class="submit__btn text-center mt-4">
        <button class="btn btn-primary">Update</button>
    </div>
    </form>
</div>
</div>
<!-- new lead modal end -->




