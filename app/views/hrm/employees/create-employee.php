 <?php
    $page_title = "Create Employee";
    $page = 'Add Employee';
    $show_breadcrumb = true;

    ?>


 <div class="row">
     <div class="card__wrapper">
         <div class="col-xxl-12 col-xl-12 col-lg-12">

             <div class="card__title-wrap mb-20">
                 <h5 class="card__heading-title">Add New Employee</h5>
             </div>

             <form action="<?= $GLOBALS['base_url'] ?>/store-employee" method="POST" enctype="multipart/form-data">
                 <div class="card__wrapper mb-20">
                     <div class="row g-20 gy-20 align-items-center justify-content-center">
                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="full_name">Full Name <span>*</span></label>
                                 </div>
                                 <div classqA="form__input">
                                     <input class="form-control" name="full_name" id="full_name" type="text">
                                 </div>
                             </div>
                         </div>

                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label>Employee ID</label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control"
                                         name="employee_id"
                                         required>
                                 </div>
                             </div>
                         </div>

                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="father_name">Father Name</label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control" name="father_name" id="father_name" type="text">
                                 </div>
                             </div>
                         </div>

                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="phone">Contact Number <span>*</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control" name="phone" id="phone" type="text" minlength="11" maxlength="11">
                                 </div>
                             </div>
                         </div>

                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="emergency_contact">Emergency Number <span>*</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control" name="emergency_contact" id="emergency_contact" type="text" minlength="11" maxlength="11">
                                 </div>
                             </div>
                         </div>

                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="email">Email <span>*</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control" name="email" id="email" type="email">
                                 </div>
                             </div>
                         </div>

                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="userName">User Name <span>*</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control" name="username" id="username" type="text" required>

                                 </div>
                             </div>
                         </div>

                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="qualification">Qualification<span>Optional</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control" name="qualification" id="qualification" type="text">
                                 </div>
                             </div>
                         </div>

                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="photo">NID Number <span>Optional</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control" name="nid" id="nid" type="text">
                                 </div>
                             </div>
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
                                             <option value="<?= $role['id'] ?>"><?= $role['name'] ?></option>
                                         <?php endforeach ?>
                                     </select>

                                 </div>
                             </div>
                         </div>
                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label>Employee Department <span>*</span></label>
                                 </div>

                                 <div class="form__input">
                                     <select class="form-control" name="department_id">
                                         <?php foreach ($departments as $department): ?>
                                             <option value="<?= $department['id'] ?>"><?= $department['name'] ?></option>
                                         <?php endforeach ?>
                                     </select>

                                 </div>
                             </div>
                         </div>

                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label>Employee Designation <span>*</span></label>
                                 </div>
                                 <div class="form__input">
                                     <select class="form-control" name="designation_id">
                                         <?php foreach ($designations as $designation): ?>
                                             <option value="<?= $designation['id'] ?>">
                                                 <?= $designation['designation_name'] ?>
                                             </option>
                                         <?php endforeach ?>
                                     </select>

                                 </div>
                             </div>
                         </div>


                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label class="form-check-label">Joining Date <span>*</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input name="joining_date" class="form-control" id="joiningDate" type="date" readonly="readonly">
                                 </div>
                             </div>
                         </div>

                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label class="form-check-label">Date Of Birth <span>Optional</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input name="dob" class="form-control" id="humanFriendlyDates" type="date" readonly>
                                 </div>
                             </div>
                         </div>


                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="account_holder_name">Account Holder Name <span class="text-muted">Optional</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control" name="account_holder_name" id="account_holder_name" type="text">
                                 </div>
                             </div>
                         </div>
                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="account_number">Account Number <span class="text-muted">optional</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control" name="account_number" id="account_number" type="text">
                                 </div>
                             </div>
                         </div>
                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="bank_name">Bank Name <span class="text-muted">Optional</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control" name="bank_name" id="bank_name" type="text">
                                 </div>
                             </div>
                         </div>
                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="branchname">Branch Name <span class="text-muted">optional</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control" name="branch_name" id="branch_name" type="text">

                                 </div>
                             </div>
                         </div>
                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="gross_salary">Gross Salary<span>*</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control" name="gross_salary" id="gross_salary" type="number" required>
                                 </div>
                             </div>
                         </div>
                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="basic_salary">Basic Salary<span>*</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control" name="basic_salary" id="basic_salary" type="number" required>
                                 </div>
                             </div>
                         </div>
                         <div class="col-lg-6 text-center">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="address">Address <span>*</span></label>
                                 </div>
                                 <div class="form__input">
                                     <textarea class="form-control" name="address" id="address"></textarea>
                                 </div>
                             </div>
                         </div>


                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="social_media1">Social Media Link 1 <span class="text-muted"> Optional</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control" name="social_media1" id="social_media1" type="text">
                                 </div>
                             </div>
                         </div>
                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="social_media2">Social Media Link 2 <span class="text-muted">Optional</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control" name="social_media2" id="social_media2" type="text">
                                 </div>
                             </div>
                         </div>
                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <label class="form__input-title" for="employee_type">Employee Type <span class="">*</span></label>
                                 <select name="employee_type" id="employee_type" class="form-select" required>
                                     <option value="">Select</option>
                                     <option value="permanent">Permanent</option>
                                     <option value="contract">Contracted</option>
                                     <option value="director">Director</option>
                                     <option value="intern">Intern</option>
                                     <option value="remote">Remote</option>
                                     <option value="probation">Probation</option>
                                     <option value="management">Management</option>
                                 </select>
                             </div>
                         </div>
                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <label class="form__input-title" for="employeeStatus">Employee Status <span class="">*</span></label>
                                 <select name="employee_status" id="employeeStatus" class="form-select" required>
                                     <option value="">Select</option>
                                     <option value="active">Active</option>
                                     <option value="inactive">Inactive</option>
                                     <option value="resigned">Resigned</option>
                                     <option value="terminated">Terminated</option>
                                 </select>
                             </div>
                         </div>



                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="photo">Employee Photo (300px*300px)<span>Optional</span></label>
                                 </div>
                                 <div class="form__input">
                                     <input class="form-control" id="photo" type="file" name="photo">
                                 </div>
                             </div>
                         </div>
                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label for="workplace">Workplace <span class="text-muted">Optional</span></label>
                                 </div>
                                 <div class="form__input">
                                     <select class="form-control" name="workplace" id="workplace">
                                         <option value="">Select Workplace</option>
                                         <option value="Corporate Office">Corporate Office</option>
                                         <option value="Site Office">Site Office</option>
                                         <option value="Site Office 2">Ati Society (Site Office 2)</option>
                                         <option value="Zonal Office">Zonal Office</option>
                                     </select>
                                 </div>
                             </div>
                         </div>
                         <div class="col-lg-6">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label class="form-label">Select Team <span class="text-danger">Only For Sales Team</span></label>


                                 </div>
                                 <div class="form__input">
                                     <select name="team_id" class="form-select" >
                                         <option value="">-- Select Team --</option>
                                         <?php foreach ($teams as $team): ?>
                                             <option value="<?= $team['id'] ?>">
                                                 <?= htmlspecialchars($team['name']) ?>
                                             </option>
                                         <?php endforeach; ?>
                                     </select>
                                 </div>
                             </div>
                         </div>

                         <!-- Is Sales Co-Leader -->
                         <div class="col-lg-12">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label class="form-label">Is Sales Co-Leader? <span class="text-muted fs-11">(Can view & assign team leads)</span></label>
                                 </div>
                                 <div class="form__input d-flex gap-4 pt-1">
                                     <label class="d-flex align-items-center gap-2">
                                         <input type="radio" name="is_sales_co_leader" value="1"> Yes
                                     </label>
                                     <label class="d-flex align-items-center gap-2">
                                         <input type="radio" name="is_sales_co_leader" value="0" checked> No
                                     </label>
                                 </div>
                             </div>
                         </div>

                         <div class="col-lg-12">
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
                         <div class="col-md-12">
                             <div class="from__input-box">
                                 <div class="form__input-title">
                                     <label>Experience <span>*</span></label>
                                 </div>
                                 <div class="from__input-box">
                                     <textarea id="tinymce_simple_textarea" name="experience"></textarea>
                                 </div>
                             </div>
                             <div class="col-md-12 mt-3">
                                 <div class="from__input-box">
                                     <div class="form__input-title">
                                         <label>Responsibilities</label>
                                     </div>
                                     <div class="from__input-box">
                                         <textarea class="form-control" name="responsibilities" rows="4" placeholder="Enter key responsibilities..."></textarea>
                                     </div>
                                 </div>
                             </div>

                         </div>


                     </div>
                 </div>
                 <div class="submit__btn text-center">
                     <button class="btn btn-primary">Submit</button>
                 </div>
             </form>
         </div>
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