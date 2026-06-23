<?php
$page_title = "Import Payslips";
$page = 'Import Payslips';
$show_breadcrumb = true;
?>

<div class="row">
    <div class="col-xxl-8 col-xl-8 col-lg-10 mx-auto">
        
        <!-- Flash Message Notifications -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div>
                        <strong class="d-block mb-1">Import Failed!</strong>
                        <?= $_SESSION['error'] ?>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <strong>Success!</strong> <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="card__wrapper">
            <div class="card__title-wrap mb-20 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="card__heading-title">Bulk Import Payslips (CSV)</h5>
                <div class="d-flex gap-2">
                    <a href="<?= $GLOBALS['base_url'] ?>/download-sample-payslips-csv" class="btn btn-success btn-sm">
                        <i class="fa-light fa-download me-1"></i> Download Sample CSV
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/payslip-lists" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-light fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>

            <form action="<?= $GLOBALS['base_url'] ?>/execute-import-payslips" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="csv_file" class="form-label fw-bold">Select CSV File <span class="text-danger">*</span></label>
                    <input class="form-control" type="file" name="csv_file" id="csv_file" accept=".csv" required>
                    <div class="form-text mt-2 text-muted">Only standard comma-separated .csv files are supported.</div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="fa-light fa-cloud-arrow-up me-2"></i> Upload and Import
                    </button>
                </div>
            </form>
        </div>

        <!-- CSV Format Instructions Card -->
        <div class="card__wrapper mt-4">
            <div class="card__title-wrap mb-15">
                <h6 class="card__heading-title text-primary"><i class="bi bi-info-circle-fill me-2"></i>CSV File Column Format Instructions</h6>
            </div>
            
            <div class="fs-14 text-dark leading-relaxed">
                <p class="mb-3">আপনার CSV ফাইলে অবশ্যই নিচের নির্দিষ্ট হেডার কলামগুলো সঠিকভাবে থাকতে হবে। নিচে একটি উদাহরণ ও তার নিয়মাবলী দেওয়া হলো:</p>
                
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm fs-13 text-center">
                        <thead class="table-light">
                            <tr>
                                <th>employee_email</th>
                                <th>payslip_month</th>
                                <th>payment_method</th>
                                <th>company_bank_name</th>
                                <th>total_earning</th>
                                <th>total_deduction</th>
                                <th>net_salary</th>
                                <th>Basic Salary (earning)</th>
                                <th>Tax Deduction (deduction)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>employee@company.com</td>
                                <td>2026-06-01</td>
                                <td>bank</td>
                                <td>Sonali Bank</td>
                                <td>25000.00</td>
                                <td>1000.00</td>
                                <td>24000.00</td>
                                <td>25000.00</td>
                                <td>1000.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h6 class="fw-bold fs-14 mb-2">কলাম সমূহের বিবরণ:</h6>
                <ul class="list-unstyled ps-0">
                    <li class="mb-2"><strong class="text-secondary">employee_email:</strong> কর্মচারী বা ইউজারের সঠিক ইমেইল (যা কর্মচারীদের মেলাতে ব্যবহৃত হবে)। ইমেইল ডাটাবেজে না মিললে পুরো ইম্পোর্ট ক্যানসেল ও রোলব্যাক হবে।</li>
                    <li class="mb-2"><strong class="text-secondary">payslip_month:</strong> বেতনের মাস (যেমন: YYYY-MM-DD ফরম্যাটে, মাসের প্রথম দিন `2026-06-01` দিতে হবে)।</li>
                    <li class="mb-2"><strong class="text-secondary">payment_method:</strong> পরিশোধের মাধ্যম। অবশ্যই `bank`, `cash`, অথবা `mobile_banking` লিখতে হবে।</li>
                    <li class="mb-2"><strong class="text-secondary">company_bank_name:</strong> পরিশোধকারী ব্যাংক একাউন্ট (যদি পেমেন্ট মেথড `bank` হয়, তবে এখানে ব্যাংকের নাম লিখতে হবে যা আপনার কোম্পানির ব্যাংক একাউন্ট নামের সাথে আংশিক মিলবে)।</li>
                    <li class="mb-2"><strong class="text-secondary">total_earning / total_deduction / net_salary:</strong> যথাক্রমে মোট আয়, মোট কর্তন এবং সর্বমোট বেতন।</li>
                    <li class="mb-2"><strong class="text-secondary">অন্যান্য ডাইনামিক কলাম:</strong> বাকি কলামগুলোর হেডার হতে হবে `Name (type)` ফরম্যাটে। যেমন: `Basic Salary (earning)` বা `Provident Fund (deduction)`। এগুলো যদি পূর্বে সিস্টেমে যুক্ত না থাকে, সিস্টেম অটোমেটিক নতুন আইটেম তৈরি করবে।</li>
                </ul>

                <div class="alert alert-warning mt-3 mb-0" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>সতর্কতা:</strong> ইম্পোর্টের সময় ডাটার কোনো লাইনে এরর পাওয়া গেলে সম্পূর্ণ আপলোডটি বাতিল (Rollback) করা হবে এবং ডাটাবেজে কোনো পেস্লিপ সেভ হবে না। দয়া করে ডেটা যাচাই করে আপলোড করুন।
                </div>
            </div>
        </div>

    </div>
</div>
