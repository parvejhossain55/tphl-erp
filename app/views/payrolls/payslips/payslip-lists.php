<?php 
    $page_title = "Payroll Lists";
$page = 'Payroll List';
$show_breadcrumb = true;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

    <h4 class="mb-2 mb-md-0">
        Payroll Manage
    </h4>

    <div class="d-flex gap-2">
        <a href="<?= $GLOBALS['base_url'] ?>/create-payslip" class="btn btn-primary"> <i class="fa-light fa-plus me-1"></i> Create Payslip</a>
    </div>

</div>

                <div class="row">
                    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
                        <div class="card__wrapper">
                            <div class="d-flex align-items-center gap-sm">
                                <div class="card__icon">
                                    <span><i class="fa-sharp fa-regular fa-gear"></i></span>
                                </div>
                                <div class="card__title-wrap">
                                    <h6 class="card__sub-title mb-10">Total Employee</h6>
                                    <div class="d-flex flex-wrap align-items-end gap-10">
                                        <h3 class="card__title">8450</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
                        <div class="card__wrapper">
                            <div class="d-flex align-items-center gap-sm">
                                <div class="card__icon">
                                    <span><i class="fa-light fa-badge-check"></i></span>
                                </div>
                                <div class="card__title-wrap">
                                    <h6 class="card__sub-title mb-10">Total Paid</h6>
                                    <div class="d-flex flex-wrap align-items-end gap-10">
                                        <h3 class="card__title">150</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
                        <div class="card__wrapper">
                            <div class="d-flex align-items-center gap-sm">
                                <div class="card__icon">
                                    <span><i class="fa-sharp fa-regular fa-user"></i></span>
                                </div>
                                <div class="card__title-wrap">
                                    <h6 class="card__sub-title mb-10">Total Unpaid</h6>
                                    <div class="d-flex flex-wrap align-items-end gap-10">
                                        <h3 class="card__title">3130</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
                        <div class="card__wrapper">
                            <div class="d-flex align-items-center gap-sm">
                                <div class="card__icon">
                                    <span><i class="fa-sharp fa-regular fa-house-person-leave"></i></span>
                                </div>
                                <div class="card__title-wrap">
                                    <h6 class="card__sub-title mb-10">Total Leave</h6>
                                    <div class="d-flex flex-wrap align-items-end gap-10">
                                        <h3 class="card__title">55</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-12">
                        <div class="card__wrapper">
                            <div class="table__wrapper table-responsive">
                                <table class="table hover mb-20" id="dataTableDefualt">
                                    <thead>
                                        <tr class="table__title">
                                            <th>Employee ID</th>
                                            <th>Employee Name</th>
                                            <th>Designation</th>
                                            <th>Email</th>
                                            <th>Joining Date</th>
                                            <th>Salary (Monthly)</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table__body">
                                        <tr>
                                            <td class="table__employee-id">MZ-114</td>
                                            <td>
                                                <span class="table-avatar">
                                                    <a class="employee__avatar mr-5" href="profile.html"><img
                                                            class="img-48 border-circle"
                                                            src="assets/images/avatar/avatar1.png"
                                                            alt="User Image"></a>
                                                    <a href="profile.html">Sophia Williams</a>
                                                </span>
                                            </td>
                                            <td class="table__employee-position">Business Intelligence Analyst</td>
                                            <td class="table__employee-emaill"><a href="mailto:name@example.com">name@example.com</a></td>
                                            <td class="table__joining-date">Jan 14 2024</td>
                                            <td class="table__salary">$4500</td>
                                            <td class="table__delivery"><span class="bd-badge bg-success">Paid</span>
                                            </td>
                                            <td class="table__icon-box">
                                                <div class="d-flex align-items-center justify-content-start gap-10">
                                                    <a href="payroll-payslip.html" class="table__icon download">
                                                        <i class="fa-regular fa-eye"></i>
                                                    </a>
                                                    <a href="#" class="table__icon edit">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </a>
                                                    <button class="removeBtn table__icon delete"><i
                                                            class="fa-regular fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="table__employee-id">MZ-115</td>
                                            <td>
                                                <span class="table-avatar">
                                                    <a class="employee__avatar mr-5" href="profile.html"><img
                                                            class="img-48 border-circle"
                                                            src="assets/images/avatar/avatar2.png"
                                                            alt="User Image"></a>
                                                    <a href="profile.html">Sophia Williams</a>
                                                </span>
                                            </td>
                                            <td class="table__employee-position">Business Intelligence Analyst</td>
                                            <td class="table__employee-emaill"><a href="mailto:name@example.com">name@example.com</a></td>
                                            <td class="table__joining-date">Dec 30 2024</td>
                                            <td class="table__salary">$5480</td>
                                            <td class="table__delivery"><span class="bd-badge bg-success">Paid</span>
                                            </td>
                                            <td class="table__icon-box">
                                                <div class="d-flex align-items-center justify-content-start gap-10">
                                                    <a href="payroll-payslip.html" class="table__icon download">
                                                        <i class="fa-regular fa-eye"></i>
                                                    </a>
                                                    <a href="#" class="table__icon edit">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </a>
                                                    <button class="removeBtn table__icon delete"><i
                                                            class="fa-regular fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="table__employee-id">MZ-116</td>
                                            <td>
                                                <span class="table-avatar">
                                                    <a class="employee__avatar mr-5" href="profile.html"><img
                                                            class="img-48 border-circle"
                                                            src="assets/images/avatar/avatar3.png"
                                                            alt="User Image"></a>
                                                    <a href="profile.html">Sophia Williams</a>
                                                </span>
                                            </td>
                                            <td class="table__employee-position">Data Scientist</td>
                                            <td class="table__employee-emaill"><a href="mailto:name@example.com">name@example.com</a></td>
                                            <td class="table__joining-date">Dec 28 2024</td>
                                            <td class="table__salary">$7500</td>
                                            <td class="table__delivery"><span class="bd-badge bg-success">Paid</span>
                                            </td>
                                            <td class="table__icon-box">
                                                <div class="d-flex align-items-center justify-content-start gap-10">
                                                    <a href="payroll-payslip.html" class="table__icon download">
                                                        <i class="fa-regular fa-eye"></i>
                                                    </a>
                                                    <a href="#" class="table__icon edit">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </a>
                                                    <button class="removeBtn table__icon delete"><i
                                                            class="fa-regular fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="table__employee-id">MZ-117</td>
                                            <td>
                                                <span class="table-avatar">
                                                    <a class="employee__avatar mr-5" href="profile.html"><img
                                                            class="img-48 border-circle"
                                                            src="assets/images/avatar/avatar4.png"
                                                            alt="User Image"></a>
                                                    <a href="profile.html">Sophia Williams</a>
                                                </span>
                                            </td>
                                            <td class="table__employee-position">Data Scientist</td>
                                            <td class="table__employee-emaill"><a href="mailto:name@example.com">name@example.com</a></td>
                                            <td class="table__joining-date">Nov 28 2024</td>
                                            <td class="table__salary">$4500</td>
                                            <td class="table__delivery"><span
                                                    class="bd-badge bg-secondary">Unpaid</span>
                                            </td>
                                            <td class="table__icon-box">
                                                <div class="d-flex align-items-center justify-content-start gap-10">
                                                    <a href="payroll-payslip.html" class="table__icon download">
                                                        <i class="fa-regular fa-eye"></i>
                                                    </a>
                                                    <a href="#" class="table__icon edit">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </a>
                                                    <button class="removeBtn table__icon delete"><i
                                                            class="fa-regular fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="table__employee-id">MZ-118</td>
                                            <td>
                                                <span class="table-avatar">
                                                    <a class="employee__avatar mr-5" href="profile.html"><img
                                                            class="img-48 border-circle"
                                                            src="assets/images/avatar/avatar5.png"
                                                            alt="User Image"></a>
                                                    <a href="profile.html">Sophia Williams</a>
                                                </span>
                                            </td>
                                            <td class="table__employee-position">Data Scientist</td>
                                            <td class="table__employee-emaill"><a href="mailto:name@example.com">name@example.com</a></td>
                                            <td class="table__joining-date">Oct 11 2024</td>
                                            <td class="table__salary">$2500</td>
                                            <td class="table__delivery"><span
                                                    class="bd-badge bg-secondary">Unpaid</span>
                                            </td>
                                            <td class="table__icon-box">
                                                <div class="d-flex align-items-center justify-content-start gap-10">
                                                    <a href="payroll-payslip.html" class="table__icon download">
                                                        <i class="fa-regular fa-eye"></i>
                                                    </a>
                                                    <a href="#" class="table__icon edit">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </a>
                                                    <button class="removeBtn table__icon delete"><i
                                                            class="fa-regular fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="table__employee-id">MZ-119</td>
                                            <td>
                                                <span class="table-avatar">
                                                    <a class="employee__avatar mr-5" href="profile.html"><img
                                                            class="img-48 border-circle"
                                                            src="assets/images/avatar/avatar6.png"
                                                            alt="User Image"></a>
                                                    <a href="profile.html">Sophia Williams</a>
                                                </span>
                                            </td>
                                            <td class="table__employee-position">Business Intelligence Analyst</td>
                                            <td class="table__employee-emaill"><a href="mailto:name@example.com">name@example.com</a></td>
                                            <td class="table__joining-date">Sep 14 2024</td>
                                            <td class="table__salary">$7500</td>
                                            <td class="table__delivery"><span
                                                    class="bd-badge bg-secondary">Unpaid</span></td>
                                            <td class="table__icon-box">
                                                <div class="d-flex align-items-center justify-content-start gap-10">
                                                    <a href="payroll-payslip.html" class="table__icon download">
                                                        <i class="fa-regular fa-eye"></i>
                                                    </a>
                                                    <a href="#" class="table__icon edit">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </a>
                                                    <button class="removeBtn table__icon delete"><i
                                                            class="fa-regular fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="table__employee-id">MZ-120</td>
                                            <td>
                                                <span class="table-avatar">
                                                    <a class="employee__avatar mr-5" href="profile.html"><img
                                                            class="img-48 border-circle"
                                                            src="assets/images/avatar/avatar7.png"
                                                            alt="User Image"></a>
                                                    <a href="profile.html">Sophia Williams</a>
                                                </span>
                                            </td>
                                            <td class="table__employee-position">Data Scientist</td>
                                            <td class="table__employee-emaill"><a href="mailto:name@example.com">name@example.com</a></td>
                                            <td class="table__joining-date">Aug 11 2024</td>
                                            <td class="table__salary">$4000</td>
                                            <td class="table__delivery"><span class="bd-badge bg-success">Paid</span>
                                            </td>
                                            <td class="table__icon-box">
                                                <div class="d-flex align-items-center justify-content-start gap-10">
                                                    <a href="payroll-payslip.html" class="table__icon download">
                                                        <i class="fa-regular fa-eye"></i>
                                                    </a>
                                                    <a href="#" class="table__icon edit">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </a>
                                                    <button class="removeBtn table__icon delete"><i
                                                            class="fa-regular fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="table__employee-id">MZ-121</td>
                                            <td>
                                                <span class="table-avatar">
                                                    <a class="employee__avatar mr-5" href="profile.html"><img
                                                            class="img-48 border-circle"
                                                            src="assets/images/avatar/avatar8.png"
                                                            alt="User Image"></a>
                                                    <a href="profile.html">Sophia Williams</a>
                                                </span>
                                            </td>
                                            <td class="table__employee-position">Data Scientist</td>
                                            <td class="table__employee-emaill"><a href="mailto:name@example.com">name@example.com</a></td>
                                            <td class="table__joining-date">Jul 14 2024</td>
                                            <td class="table__salary">$1500</td>
                                            <td class="table__delivery"><span class="bd-badge bg-success">Paid</span>
                                            </td>
                                            <td class="table__icon-box">
                                                <div class="d-flex align-items-center justify-content-start gap-10">
                                                    <a href="payroll-payslip.html" class="table__icon download">
                                                        <i class="fa-regular fa-eye"></i>
                                                    </a>
                                                    <a href="#" class="table__icon edit">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </a>
                                                    <button class="removeBtn table__icon delete"><i
                                                            class="fa-regular fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="table__employee-id">MZ-122</td>
                                            <td>
                                                <span class="table-avatar">
                                                    <a class="employee__avatar mr-5" href="profile.html"><img
                                                            class="img-48 border-circle"
                                                            src="assets/images/avatar/avatar9.png"
                                                            alt="User Image"></a>
                                                    <a href="profile.html">Sophia Williams</a>
                                                </span>
                                            </td>
                                            <td class="table__employee-position">Data Scientist</td>
                                            <td class="table__employee-emaill"><a href="mailto:name@example.com">name@example.com</a></td>
                                            <td class="table__joining-date">Jun 29 2024</td>
                                            <td class="table__salary">$2500</td>
                                            <td class="table__delivery"><span class="bd-badge bg-success">Paid</span>
                                            </td>
                                            <td class="table__icon-box">
                                                <div class="d-flex align-items-center justify-content-start gap-10">
                                                    <a href="payroll-payslip.html" class="table__icon download">
                                                        <i class="fa-regular fa-eye"></i>
                                                    </a>
                                                    <a href="#" class="table__icon edit">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </a>
                                                    <button class="removeBtn table__icon delete"><i
                                                            class="fa-regular fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="table__employee-id">MZ-123</td>
                                            <td>
                                                <span class="table-avatar">
                                                    <a class="employee__avatar mr-5" href="profile.html"><img
                                                            class="img-48 border-circle"
                                                            src="assets/images/avatar/avatar10.png"
                                                            alt="User Image"></a>
                                                    <a href="profile.html">Sophia Williams</a>
                                                </span>
                                            </td>
                                            <td class="table__employee-position">Data Scientist</td>
                                            <td class="table__employee-emaill"><a href="mailto:name@example.com">name@example.com</a></td>
                                            <td class="table__joining-date">May 16 2024</td>
                                            <td class="table__salary">$1500</td>
                                            <td class="table__delivery"><span class="bd-badge bg-success">Paid</span>
                                            </td>
                                            <td class="table__icon-box">
                                                <div class="d-flex align-items-center justify-content-start gap-10">
                                                    <a href="payroll-payslip.html" class="table__icon download">
                                                        <i class="fa-regular fa-eye"></i>
                                                    </a>
                                                    <a href="#" class="table__icon edit">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </a>
                                                    <button class="removeBtn table__icon delete"><i
                                                            class="fa-regular fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="table__employee-id">MZ-124</td>
                                            <td>
                                                <span class="table-avatar">
                                                    <a class="employee__avatar mr-5" href="profile.html"><img
                                                            class="img-48 border-circle"
                                                            src="assets/images/avatar/avatar11.png"
                                                            alt="User Image"></a>
                                                    <a href="profile.html">Sophia Williams</a>
                                                </span>
                                            </td>
                                            <td class="table__employee-position">Data Scientist</td>
                                            <td class="table__employee-emaill"><a href="mailto:name@example.com">name@example.com</a></td>
                                            <td class="table__joining-date">Apr 05 2024</td>
                                            <td class="table__salary">$1500</td>
                                            <td class="table__delivery"><span
                                                    class="bd-badge bg-secondary">Unpaid</span>
                                            </td>
                                            <td class="table__icon-box">
                                                <div class="d-flex align-items-center justify-content-start gap-10">
                                                    <a href="payroll-payslip.html" class="table__icon download">
                                                        <i class="fa-regular fa-eye"></i>
                                                    </a>
                                                    <a href="#" class="table__icon edit">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </a>
                                                    <button class="removeBtn table__icon delete"><i
                                                            class="fa-regular fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="table__employee-id">MZ-125</td>
                                            <td>
                                                <span class="table-avatar">
                                                    <a class="employee__avatar mr-5" href="profile.html"><img
                                                            class="img-48 border-circle"
                                                            src="assets/images/avatar/avatar12.png"
                                                            alt="User Image"></a>
                                                    <a href="profile.html">Alexander Smith</a>
                                                </span>
                                            </td>
                                            <td class="table__employee-position">Data Scientist</td>
                                            <td class="table__employee-emaill"><a href="mailto:name@example.com">name@example.com</a></td>
                                            <td class="table__joining-date">Mar 11 2024</td>
                                            <td class="table__salary">$4500</td>
                                            <td class="table__delivery"><span class="bd-badge bg-success">Paid</span>
                                            </td>
                                            <td class="table__icon-box">
                                                <div class="d-flex align-items-center justify-content-start gap-10">
                                                    <a href="payroll-payslip.html" class="table__icon download">
                                                        <i class="fa-regular fa-eye"></i>
                                                    </a>
                                                    <a href="#" class="table__icon edit">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </a>
                                                    <button class="removeBtn table__icon delete"><i
                                                            class="fa-regular fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="table__employee-id">MZ-126</td>
                                            <td>
                                                <span class="table-avatar">
                                                    <a class="employee__avatar mr-5" href="profile.html"><img
                                                            class="img-48 border-circle"
                                                            src="assets/images/avatar/avatar13.png"
                                                            alt="User Image"></a>
                                                    <a href="profile.html">Emily Johnson</a>
                                                </span>
                                            </td>
                                            <td class="table__employee-position">Data Scientist</td>
                                            <td class="table__employee-emaill"><a href="mailto:name@example.com">name@example.com</a></td>
                                            <td class="table__joining-date">Feb 14 2024</td>
                                            <td class="table__salary">$1500</td>
                                            <td class="table__delivery"><span
                                                    class="bd-badge bg-secondary">Unpaid</span>
                                            </td>
                                            <td class="table__icon-box">
                                                <div class="d-flex align-items-center justify-content-start gap-10">
                                                    <a href="payroll-payslip.html" class="table__icon download">
                                                        <i class="fa-regular fa-eye"></i>
                                                    </a>
                                                    <a href="#" class="table__icon edit">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </a>
                                                    <button class="removeBtn table__icon delete"><i
                                                            class="fa-regular fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>