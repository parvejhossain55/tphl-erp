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
                             <select class="form-control select2" name="role_id">
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
                 
                 <div class="col-lg-6">
                     <div class="from__input-box">
                         <div class="form__input-title">
                             <label>Secondary Role<span>(Optional)</span></label>
                         </div>

                         <div class="form__input">
                             <select class="form-control select2" name="secondary_role_id">
                                 <option value="">None</option>
                                 <?php foreach ($roles as $role): ?>
                                     <option value="<?= $role['id'] ?>"
                                         <?= (isset($employee['secondary_role_id']) && $employee['secondary_role_id'] == $role['id']) ? 'selected' : '' ?>>
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
                     <select class="form-control select2" name="department_id">
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
                     <select class="form-control select2" name="designation_id">
                         <option value="">Select Designation</option>
                         <?php foreach ($designations as $designation): ?>
                             <option value="<?= $designation['id'] ?>"
                                 <?= $employee['designation_id'] == $designation['id'] ? 'selected' : '' ?>>
                                 <?= htmlspecialchars($designation['designation_name'] . ' (' . ($designation['department_name'] ?? 'N/A') . ')') ?>
                             </option>
                         <?php endforeach ?>
                     </select>
                 </div>
                 <!-- Designation -->
                 <div class="col-lg-6"><label>Gross Salary <span>*</span></label><input class="form-control" name="gross_salary" id="gross_salary" type="number" value="<?= $employee['gross_salary'] ?>"></div>
                 <div class="col-lg-6"><label>Per Meal Amount</label><input class="form-control" name="per_meal_amount" id="per_meal_amount" type="number" step="0.01" value="<?= $employee['per_meal_amount'] ?? '' ?>"></div>
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

                 <!-- Resign Date -->
                 <div class="col-lg-6">
                     <label>Resign Date</label>
                     <input class="form-control" id="resignDate" type="text" readonly name="resign_date"
                         value="<?= htmlspecialchars($employee['resign_date'] ?? '') ?>">
                 </div>

                 <!-- Account Holder Name -->
                 <div class="col-lg-6">
                     <label for="account_holder_name">Account Holder Name <span>*</span></label>
                     <input class="form-control" name="account_holder_name" id="account_holder_name" type="text"
                         value="<?= htmlspecialchars($employee['account_holder_name'] ?? 'N/A') ?>">
                 </div>

                 <!-- Account Number -->
                 <div class="col-lg-6">
                     <label for="account_number">Account Number <span>*</span></label>
                     <input class="form-control" name="account_number" id="account_number" type="text"
                         value="<?= htmlspecialchars($employee['account_number'] ?? 'N/A') ?>">
                 </div>

                 <!-- Bank Name -->
                 <div class="col-lg-6">
                     <label for="bank_name">Bank Name <span>*</span></label>
                     <input class="form-control" name="bank_name" id="bank_name" type="text"
                         value="<?= htmlspecialchars($employee['bank_name'] ?? 'N/A') ?>">
                 </div>

                 <!-- Branch Name -->
                 <div class="col-lg-6">
                     <label for="branch_name">Branch Name <span>*</span></label>
                     <input class="form-control" name="branch_name" id="branch_name" type="text"
                         value="<?= htmlspecialchars($employee['branch_name'] ?? 'N/A') ?>">
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
                     <textarea class="form-control" name="address" id="address"><?= htmlspecialchars($employee['address'] ?? 'N/A') ?></textarea>
                 </div>
                 <!-- Social Media -->
                 <div class="col-lg-6">
                     <label for="social_media1">Social Media Link 1</label>
                     <input class="form-control" name="social_media1" id="social_media1" type="text"
                         value="<?= htmlspecialchars($employee['social_media1'] ?? 'N/A') ?>">
                 </div>
                 <div class="col-lg-6">
                     <label for="social_media2">Social Media Link 2</label>
                     <input class="form-control" name="social_media2" id="social_media2" type="text"
                         value="<?= htmlspecialchars($employee['social_media2'] ?? 'N/A') ?>">
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

                 <!-- Workplace -->
                 <div class="col-lg-6">
                     <div class="from__input-box">
                         <div class="form__input-title">
                             <label for="workplace">Workplace <span class="text-muted">Optional</span></label>
                         </div>
                         <div class="form__input">
                             <select class="form-control" name="workplace" id="workplace">
                                 <option value="">Select Workplace</option>
                                 <option value="Corporate Office" <?= (($employee['workplace'] ?? '') === 'Corporate Office') ? 'selected' : '' ?>>Corporate Office</option>
                                 <option value="Site Office" <?= (($employee['workplace'] ?? '') === 'Site Office') ? 'selected' : '' ?>>Site Office</option>
                                 <option value="Site Office 2" <?= (($employee['workplace'] ?? '') === 'Site Office 2') ? 'selected' : '' ?>>Ati Society (Site Office 2)</option>
                                 <option value="Zonal Office" <?= (($employee['workplace'] ?? '') === 'Zonal Office') ? 'selected' : '' ?>>Zonal Office</option>
                             </select>
                         </div>
                     </div>
                 </div>

                 <!-- Office Branch -->
                 <div class="col-lg-6">
                     <div class="from__input-box">
                         <div class="form__input-title">
                             <label for="office_branch_id">Office Branch</label>
                         </div>
                         <div class="form__input">
                             <select class="form-control" name="office_branch_id" id="office_branch_id">
                                 <?php foreach ($branches ?? [] as $b): ?>
                                     <option value="<?= $b['id'] ?>" <?= (($employee['office_branch_id'] ?? 2) == $b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                                 <?php endforeach; ?>
                             </select>
                         </div>
                     </div>
                 </div>

                 <!-- Is Sales Co-Leader -->
                 <div class="col-lg-6">
                     <div class="from__input-box">
                         <div class="form__input-title">
                             <label>Is Sales Co-Leader? <span class="text-muted" style="font-size:11px;">(Can view & assign team leads)</span></label>
                         </div>
                         <div class="form__input d-flex gap-4 pt-1">
                             <label class="d-flex align-items-center gap-2">
                                 <input type="radio" name="is_sales_co_leader" value="1"
                                     <?= ((int)($employee['is_sales_co_leader'] ?? 0) === 1) ? 'checked' : '' ?>> Yes
                             </label>
                             <label class="d-flex align-items-center gap-2">
                                 <input type="radio" name="is_sales_co_leader" value="0"
                                     <?= ((int)($employee['is_sales_co_leader'] ?? 0) !== 1) ? 'checked' : '' ?>> No
                             </label>
                         </div>
                     </div>
                 </div>

                 <!-- Is Department Head & Reporting Boss -->
                 <div class="col-lg-6">
                     <div class="from__input-box">
                         <div class="form__input-title">
                             <label class="form-label">Is Department Head?</label>
                         </div>
                         <div class="form__input pt-1">
                             <label class="d-flex align-items-center gap-2">
                                 <input type="checkbox" name="is_dept_head" id="is_dept_head" value="1"
                                     <?= ((int)($employee['is_dept_head'] ?? 0) === 1) ? 'checked' : '' ?>> Yes, this employee is a Department Head
                             </label>
                         </div>
                     </div>
                 </div>

                 <div class="col-lg-6" id="reporting_boss_container">
                     <div class="from__input-box">
                         <div class="form__input-title">
                             <label for="reporting_user_id">Report To (Reporting Boss)</label>
                         </div>
                         <div class="form__input">
                             <select name="reporting_user_id" id="reporting_user_id" class="form-select select2">
                                 <option value="">-- Select Reporting Boss --</option>
                                 <?php foreach ($reportingBosses as $boss): ?>
                                     <option value="<?= $boss['id'] ?>"
                                         <?= (isset($employee['reporting_user_id']) && $employee['reporting_user_id'] == $boss['id']) ? 'selected' : '' ?>>
                                         <?= htmlspecialchars($boss['full_name'] . (!empty($boss['designation_name']) ? ' (' . $boss['designation_name'] . ')' : '')) ?>
                                     </option>
                                 <?php endforeach; ?>
                             </select>
                         </div>
                     </div>
                 </div>

                 <div class="col-lg-6">
                    <div class="form__input-box">
                        <div class="form__input-box">
                            <label>
                                <input type="checkbox" id="make_user" name="make_user" value="1" <?= ($employee['make_user'] == 1) ? 'checked' : '' ?>>
                                Create User Account?
                            </label>

                            <div id="password_box" style="display: <?= ($employee['make_user'] == 1) ? 'block' : 'none' ?>;">
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

                  <?php
                  $existingItems = [];
                  if (!empty($employeeSalaryItems)) {
                      foreach ($employeeSalaryItems as $esi) {
                          $existingItems[$esi['payslip_item_id']] = $esi['amount'];
                      }
                  }
                  ?>
                  <!-- Salary Allowances & Deductions Configuration -->
                  <div class="col-lg-12">
                      <div class="card__wrapper p-4 mt-4 mb-20 border rounded">
                          <div class="card__title-wrap mb-20">
                              <h5 class="card__heading-title">Salary Allowances & Deductions</h5>
                              <small class="text-muted">Set standard monthly allowance/deduction amounts for this employee</small>
                          </div>
                          <div class="row g-20 gy-20">
                              <!-- Earnings -->
                              <div class="col-lg-6">
                                  <h6 class="mb-3 text-success"><i class="fa-solid fa-circle-plus me-2"></i>Earnings / Allowances</h6>
                                  <div class="row g-3">
                                       <?php foreach ($payslipItems as $item): ?>
                                           <?php if ($item['type'] === 'earning'): ?>
                                               <div class="col-12 mb-2">
                                                   <label for="allowance_<?= $item['id'] ?>">
                                                       <?= htmlspecialchars($item['name']) ?>
                                                       <?php if (!empty($item['percentage']) && $item['percentage'] > 0): ?>
                                                           <span class="badge bg-info-transparent text-info ms-1" style="font-size:11px;"><?= number_format($item['percentage'], 2) ?>%</span>
                                                       <?php endif; ?>
                                                   </label>
                                                   <input class="form-control salary-item-input" 
                                                       name="salary_items[<?= $item['id'] ?>]" 
                                                       id="allowance_<?= $item['id'] ?>" 
                                                       type="number" step="0.01" 
                                                       data-percentage="<?= $item['percentage'] ?? '' ?>" 
                                                       value="<?= number_format($existingItems[$item['id']] ?? 0, 2, '.', '') ?>">
                                               </div>
                                           <?php endif; ?>
                                       <?php endforeach; ?>
                                   </div>
                               </div>

                               <!-- Deductions -->
                               <div class="col-lg-6">
                                   <h6 class="mb-3 text-danger"><i class="fa-solid fa-circle-minus me-2"></i>Deductions</h6>
                                   <div class="row g-3">
                                       <?php foreach ($payslipItems as $item): ?>
                                           <?php if ($item['type'] === 'deduction'): ?>
                                               <div class="col-12 mb-2">
                                                   <label for="deduction_<?= $item['id'] ?>">
                                                       <?= htmlspecialchars($item['name']) ?>
                                                       <?php if (!empty($item['percentage']) && $item['percentage'] > 0): ?>
                                                           <span class="badge bg-warning-transparent text-warning ms-1" style="font-size:11px;"><?= number_format($item['percentage'], 2) ?>%</span>
                                                       <?php endif; ?>
                                                   </label>
                                                   <input class="form-control salary-item-input" 
                                                       name="salary_items[<?= $item['id'] ?>]" 
                                                       id="deduction_<?= $item['id'] ?>" 
                                                       type="number" step="0.01" 
                                                       data-percentage="<?= $item['percentage'] ?? '' ?>" 
                                                       value="<?= number_format($existingItems[$item['id']] ?? 0, 2, '.', '') ?>">
                                               </div>
                                           <?php endif; ?>
                                       <?php endforeach; ?>
                                   </div>
                               </div>
                           </div>
                       </div>
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

      const isDeptHeadCheckbox = document.getElementById("is_dept_head");
      const reportingBossContainer = document.getElementById("reporting_boss_container");
      const reportingUserSelect = document.getElementById("reporting_user_id");

      function toggleReportingBoss() {
          if (isDeptHeadCheckbox.checked) {
              reportingBossContainer.style.display = "none";
              if (reportingUserSelect) {
                  reportingUserSelect.value = "";
                  if (typeof $ !== 'undefined' && $.fn.select2 && $(reportingUserSelect).hasClass('select2')) {
                      $(reportingUserSelect).val("").trigger("change");
                  }
              }
          } else {
              reportingBossContainer.style.display = "block";
          }
      }

      if (isDeptHeadCheckbox) {
          isDeptHeadCheckbox.addEventListener("change", toggleReportingBoss);
          toggleReportingBoss();
      }

      // Auto-calculate 50% Basic Salary & Percentage-wise Payslip Items based on Basic Salary
      function calculateSalaryBreakdown() {
          const grossEl = document.getElementById("gross_salary");
          const basicEl = document.getElementById("basic_salary");
          if (!grossEl || !basicEl) return;

          const gross = parseFloat(grossEl.value) || 0;
          let basic = parseFloat(basicEl.value) || 0;

          document.querySelectorAll(".salary-item-input[data-percentage]").forEach(function(input) {
              const pctStr = input.getAttribute("data-percentage");
              if (pctStr && pctStr !== "") {
                  const pct = parseFloat(pctStr);
                  if (pct > 0) {
                      const calculatedAmount = (basic * pct) / 100;
                      input.value = calculatedAmount.toFixed(2);
                  }
              }
          });
      }

      const grossSalaryInput = document.getElementById("gross_salary");
      const basicSalaryInput = document.getElementById("basic_salary");
      if (grossSalaryInput) {
          grossSalaryInput.addEventListener("input", function() {
              const gross = parseFloat(grossSalaryInput.value) || 0;
              if (basicSalaryInput) {
                  basicSalaryInput.value = gross > 0 ? (gross * 0.50).toFixed(2) : "";
              }
              calculateSalaryBreakdown();
          });
          grossSalaryInput.addEventListener("change", calculateSalaryBreakdown);
      }
      if (basicSalaryInput) {
          basicSalaryInput.addEventListener("input", calculateSalaryBreakdown);
          basicSalaryInput.addEventListener("change", calculateSalaryBreakdown);
      }

      // Fallback for flatpickr initialization in case main.js is cached by the browser
      if (typeof $ !== 'undefined' && $.fn.flatpickr) {
          setTimeout(function() {
              if ($("#resignDate").length) {
                  $("#resignDate").flatpickr({
                      altInput: true,
                      altFormat: "F j, Y",
                      dateFormat: "Y-m-d",
                  });
              }
          }, 500);
      }
  </script>