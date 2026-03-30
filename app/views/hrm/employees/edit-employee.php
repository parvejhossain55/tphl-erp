 <?php
    $page_title = "Edit Employee";
    $page = 'Edit Employee';
    $show_breadcrumb = true;

    ?>


 <div class="row">
     <div class="col-xxl-12 col-xl-12 col-lg-12">

         <div class="card__title-wrap mb-20">
             <h5 class="card__heading-title">Edit Employee</h5>
         </div>

         <form action="<?= $GLOBALS['base_url'] ?>/update-employee/<?= $employee['id'] ?>" method="POST" enctype="multipart/form-data">

             <div class="row g-20 gy-20 align-items-center justify-content-center">
                 <!-- Employee ID -->
                 <div class="col-lg-6">
                     <label for="employee_id">Employee ID <span>*</span></label>
                     <input class="form-control" name="employee_id" id="employee_id" type="text"
                         value="<?= htmlspecialchars($employee['employee_id']) ?>" required>
                 </div>

                 <!-- Full Name -->
                 <div class="col-lg-6">
                     <label for="full_name">Full Name <span>*</span></label>
                     <input class="form-control" name="full_name" id="full_name" type="text"
                         value="<?= htmlspecialchars($employee['full_name']) ?>">
                 </div>

                 <!-- Father Name -->
                 <div class="col-lg-6">
                     <label for="father_name">Father Name</label>
                     <input class="form-control" name="father_name" id="father_name" type="text"
                         value="<?= htmlspecialchars($employee['father_name']) ?>">
                 </div>

                 <!-- Phone -->
                 <div class="col-lg-6">
                     <label for="phone">Contact Number <span>*</span></label>
                     <input class="form-control" name="phone" id="phone" type="text" minlength="11" maxlength="11"
                         value="<?= htmlspecialchars($employee['phone']) ?>">
                 </div>

                 <!-- Emergency Contact -->
                 <div class="col-lg-6">
                     <label for="emergency_contact">Emergency Number <span>*</span></label>
                     <input class="form-control" name="emergency_contact" id="emergency_contact" type="text" minlength="11" maxlength="11"
                         value="<?= htmlspecialchars($employee['emergency_contact']) ?>">
                 </div>

                 <!-- Email -->
                 <div class="col-lg-6">
                     <label for="email">Email <span>*</span></label>
                     <input class="form-control" name="email" id="email" type="email"
                         value="<?= htmlspecialchars($employee['email']) ?>">
                 </div>

                 <!-- Username -->
                 <div class="col-lg-6">
                     <label for="username">User Name <span>*</span></label>
                     <input class="form-control" name="username" id="username" type="text"
                         value="<?= htmlspecialchars($employee['username']) ?>">
                 </div>

                 <!-- Qualification -->
                 <div class="col-lg-6">
                     <label for="qualification">Qualification <span>*</span></label>
                     <input class="form-control" name="qualification" id="qualification" type="text"
                         value="<?= htmlspecialchars($employee['qualification']) ?>">
                 </div>

                 <div class="col-lg-6">
                     <div class="from__input-box">
                         <div class="form__input-title">
                             <label>Employee Role<span>*</span></label>
                         </div>

                         <div class="form__input">
                             <select class="form-control" name="role_id">
                                 <option value="">Select Role</option>
                                 <?php foreach ($roles as $role): ?>
                                     <option value="<?= $role['id'] ?>"
                                         <?= (isset($employee['role_id']) && $employee['role_id'] == $role['id']) ? 'selected' : '' ?>>
                                         <?= $role['name'] ?>
                                     </option>
                                 <?php endforeach ?>
                             </select>
                         </div>

                     </div>
                 </div>


                 <!-- Department -->
                 <div class="col-lg-6">
                     <label>Department <span>*</span></label>
                     <select class="form-control" name="department_id">
                         <option value="">Select Department</option>
                         <?php foreach ($departments as $department): ?>
                             <option value="<?= $department['id'] ?>"
                                 <?= $employee['department_id'] == $department['id'] ? 'selected' : '' ?>>
                                 <?= $department['name'] ?>
                             </option>
                         <?php endforeach ?>
                     </select>
                 </div>

                 <!-- Designation -->
                 <div class="col-lg-6">
                     <label>Designation <span>*</span></label>
                     <select class="form-control" name="designation_id">
                         <option value="">Select Designation</option>
                         <?php foreach ($designations as $designation): ?>
                             <option value="<?= $designation['id'] ?>"
                                 <?= $employee['designation_id'] == $designation['id'] ? 'selected' : '' ?>>
                                 <?= $designation['designation_name'] ?>
                             </option>
                         <?php endforeach ?>
                     </select>
                 </div>
                 <!-- Designation -->
                 <div class="col-lg-6"><label>Gross Salary <span>*</span></label><input class="form-control" name="gross_salary" id="gross_salary" type="number" value="<?= $employee['gross_salary'] ?>"></div>
                 <div class="col-lg-6"><label>Basic Salary <span>*</span></label>
                     <input class="form-control" name="basic_salary" id="basic_salary" type="number"
                         value="<?= $employee['basic_salary'] ?>">
                 </div>
                 <?php
                    $joiningDate = $employee['joining_date'];
                    $joiningDateFormatted = ($joiningDate && $joiningDate !== '0000-00-00')
                        ? date('d-m-Y', strtotime($joiningDate))
                        : '';

                    $dob = $employee['dob'];
                    $dobFormatted = ($dob && $dob !== '0000-00-00')
                        ? date('d-m-Y', strtotime($dob))
                        : '';
                    ?>

                 <!-- Joining Date -->
                 <div class="col-lg-6">
                     <label> Date Of Birth<span>*</span></label>
                     <input class="form-control" id="humanFriendlyDates" type="text" name="dob" readonly
                         value="<?= htmlspecialchars($dobFormatted) ?>">
                 </div>
                 <!-- Joining Date -->
                 <div class="col-lg-6">
                     <label>Joining Date <span>*</span></label>
                     <input class="form-control" id="joiningDate" type="text" readonly name="joining_date"
                         value="<?= htmlspecialchars($joiningDateFormatted) ?>">
                 </div>

                 <!-- Account Holder Name -->
                 <div class="col-lg-6">
                     <label for="account_holder_name">Account Holder Name <span>*</span></label>
                     <input class="form-control" name="account_holder_name" id="account_holder_name" type="text"
                         value="<?= htmlspecialchars($employee['account_holder_name']) ?>">
                 </div>

                 <!-- Account Number -->
                 <div class="col-lg-6">
                     <label for="account_number">Account Number <span>*</span></label>
                     <input class="form-control" name="account_number" id="account_number" type="text"
                         value="<?= htmlspecialchars($employee['account_number']) ?>">
                 </div>

                 <!-- Bank Name -->
                 <div class="col-lg-6">
                     <label for="bank_name">Bank Name <span>*</span></label>
                     <input class="form-control" name="bank_name" id="bank_name" type="text"
                         value="<?= htmlspecialchars($employee['bank_name']) ?>">
                 </div>

                 <!-- Branch Name -->
                 <div class="col-lg-6">
                     <label for="branch_name">Branch Name <span>*</span></label>
                     <input class="form-control" name="branch_name" id="branch_name" type="text"
                         value="<?= htmlspecialchars($employee['branch_name']) ?>">
                 </div>
                 <!-- Photo -->
                 <div class="col-lg-6">
                     <label for="photo">Employee Photo (300px*300px)</label>
                     <input class="form-control" type="file" name="photo" id="photo">
                     <?php if (!empty($employee['photo'])): ?>
                         <img src="<?= $GLOBALS['base_url'] ?>/uploads/employees/<?= $employee['photo'] ?>" width="100" class="mt-2">
                     <?php endif; ?>
                 </div>
                 <!-- Address -->
                 <div class="col-lg-6">
                     <label for="address">Address <span>*</span></label>
                     <textarea class="form-control" name="address" id="address"><?= htmlspecialchars($employee['address']) ?></textarea>
                 </div>
                 <!-- Social Media -->
                 <div class="col-lg-6">
                     <label for="social_media1">Social Media Link 1</label>
                     <input class="form-control" name="social_media1" id="social_media1" type="text"
                         value="<?= htmlspecialchars($employee['social_media1']) ?>">
                 </div>
                 <div class="col-lg-6">
                     <label for="social_media2">Social Media Link 2</label>
                     <input class="form-control" name="social_media2" id="social_media2" type="text"
                         value="<?= htmlspecialchars($employee['social_media2']) ?>">
                 </div>

                 <div class="col-lg-6">
                     <label>Employee Type </label>
                     <select name="employee_type" class="form-select">

                         <option value="permanent" <?= ($employee['employee_type'] ?? '') == 'permanent' ? 'selected' : '' ?>>Permanent</option>
                         <option value="contract" <?= ($employee['employee_type'] ?? '') == 'contract' ? 'selected' : '' ?>>Contract</option>
                         <option value="director" <?= ($employee['employee_type'] ?? '') == 'director' ? 'selected' : '' ?>>Director</option>

                     </select>
                 </div>
                 <div class="col-lg-6">
                     <label>Employee Type </label>
                     <select name="employee_status" class="form-select">

                         <option value="active" <?= ($employee['employee_status'] ?? '') == 'active' ? 'selected' : '' ?>>Active</option>
                         <option value="inactive" <?= ($employee['employee_status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                         <option value="resigned" <?= ($employee['employee_status'] ?? '') == 'resigned' ? 'selected' : '' ?>>Resigned</option>
                         <option value="terminated" <?= ($employee['employee_status'] ?? '') == 'terminated' ? 'selected' : '' ?>>Terminated</option>
                     </select>
                 </div>
                 <div class="col-lg-6">
                     <div class="from__input-box">
                         <div class="form__input-title">
                             <label>Project Team<span>*</span></label>
                         </div>

                         <div class="form__input">
                             <select class="form-control" name="team_id">
                                 <option value="">Select Team</option>
                                 <?php foreach ($teams as $team): ?>
                                     <option value="<?= $team['id'] ?>"
                                         <?= ($employee['team_id'] == $team['id']) ? 'selected' : '' ?>>
                                         <?= $team['name'] ?>
                                     </option>
                                 <?php endforeach ?>
                             </select>
                         </div>

                     </div>
                 </div>

                 <div class="col-lg-6">
                     <div class="form__input-box">
                         <div class="form__input-box">
                             <label>
                                 <input type="checkbox" id="make_user" name="make_user" value="1">
                                 Create User Account?
                             </label>

                             <div id="password_box" style="display:none;">
                                 <label>Password</label>
                                 <input type="password" name="password" class="form-control">
                             </div>
                         </div>
                     </div>
                 </div>



                 <!-- Experience -->
                 <div class="col-lg-12 mt-3">
                     <label>Experience <span>*</span></label>
                     <textarea id="tinymce_simple_textarea" name="experience"><?= htmlspecialchars($employee['experience'] ?? '') ?></textarea>
                 </div>

                 <!-- Responsibilities -->
                 <div class="col-lg-12 mt-3">
                     <label>Responsibilities</label>
                     <textarea class="form-control" name="responsibilities" rows="4" placeholder="Enter key responsibilities..."><?= htmlspecialchars($employee['responsibilities'] ?? '') ?></textarea>
                 </div>

                 <!-- Submit -->
                 <div class="col-12 text-center mt-3">
                     <button class="btn btn-primary">Update Employee</button>
                 </div>
             </div>
         </form>

     </div>
 </div>

 <script>
     document.getElementById("make_user").addEventListener("change", function() {
         if (this.checked) {
             document.getElementById("password_box").style.display = "block";
         } else {
             document.getElementById("password_box").style.display = "none";
         }
     });
 </script>