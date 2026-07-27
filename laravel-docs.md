# TPHL-ERP: Raw PHP to Laravel Migration & Simplification Roadmap

এই গাইডলাইনটি আমাদের Raw PHP প্রজেক্টটিকে একটি মডুলার, স্কেলেবল এবং ক্লিন আর্কিটেকচারে লারাভেলে (Laravel) রূপান্তরিত করার জন্য তৈরি করা হয়েছে। এখানে আমাদের ১১০+ টেবিলকে ক্লিন করে **৬টি কোর মডিউলে (Core Modules)** পুনর্বিন্যাস করা হয়েছে। 

প্রতিটি মডিউলের জন্য ডেটাবেস ডিজাইন (লারাভেল মাইগ্রেশন ফ্রেন্ডলি কলাম), এলকোয়েন্ট মডেল রিলেশন (Eloquent Model Relations), UI/UX ডিজাইন পলিসি এবং ট্রানজিশন ফ্লো নিচে বিস্তারিতভাবে দেওয়া হলো।

---

## মডিউল ১: কোর সিস্টেম ও অথেনটিকেশন (Core System & Auth)
এই মডিউলটি পুরো সিস্টেমের মেরুদণ্ড। এখানে ইউজার ডেটা, সাইট সেটিংস এবং রোল-ভিত্তিক অ্যাক্সেস কন্ট্রোল (RBAC) থাকবে।

### ক) ডাটাবেস ডিজাইন (DB Schema & Cleanup)
* `users`
  * `id` (bigint, PK)
  * `name` (string)
  * `email` (string, unique)
  * `password` (string)
  * `phone` (string)
  * `role_id` (unsignedInteger, optional)
  * `status` (enum: 'active', 'inactive')
  * `remember_token` (string, nullable)
  * `created_at` / `updated_at` (timestamps)
* `site_settings`
  * `id` (unsignedInteger, PK)
  * `key` (string, unique)
  * `value` (text)
  * `description` (text, nullable)
* **RBAC (Spatie Laravel-Permission) Tables:**
  * `roles`, `permissions`, `model_has_roles`, `role_has_permissions`, `model_has_permissions`.
* **ক্লিন-আপ নোট:** পুরানো `login_attempts` টেবিলটি ডিলিট করা হবে এবং লারাভেলের বিল্ট-ইন `RateLimiting` (Breeze/Fortify-র মাধ্যমে) ব্যবহার করা হবে।

### খ) এলকোয়েন্ট মডেল রিলেশন (Model & Relations)
* **User Model (`App\Models\User`):**
  * Spatie-র `HasRoles` ট্রেইট ব্যবহার করবে।
  * `hasOne(Employee::class)` (HRM লিঙ্কের জন্য)।

### গ) UI/UX রূপরেখা
* **ডিজাইন পলিসি:** একদম মিনিমাল এবং মডার্ন ড্যাশবোর্ড কার্ড লেআউট।
* **Admin Screen:** একটি একক পেজ থেকে ইউজার তৈরি, স্প্যাটি রোল ও পারমিশন ড্রপডাউন থেকে অ্যাসাইন করার সুবিধা। 

---

## মডিউল ২: সিআরএম ও সেলস (CRM, Bookings & Incentives)
গ্রাহক যোগাযোগ থেকে শুরু করে বুকিং এবং সেলস ইনসেন্টিভ হিসেব করার প্রক্রিয়াকে এই মডিউল একীভূত করবে।

### ক) ডাটাবেস ডিজাইন (DB Schema & Cleanup)
* `leads`
  * `id` (bigint, PK)
  * `name` (string), `phone` (string), `email` (string, nullable)
  * `source_id` (foreignId, `lead_sources.id`)
  * `assigned_to` (foreignId, `users.id`)
  * `status` (enum: 'New', 'Contacted', 'Qualified', 'Booking Pending', 'Won', 'Lost')
  * `budget` (decimal)
  * `notes` (text, nullable)
* `lead_sources` (`id`, `name`, `status`)
* `lead_follow_ups` (`id`, `lead_id`, `follow_up_date`, `notes`, `status`, `created_by`)
* `lead_bookings`
  * `id` (bigint, PK), `lead_id` (foreignId, `leads.id`), `flat_id` (foreignId, `flats.id`)
  * `agreed_value` (decimal), `booking_date` (date)
  * `status` (enum: 'Pending', 'Active', 'Cancelled')
* `lead_payments` (`id`, `booking_id`, `amount`, `payment_date`, `payment_method`, `transaction_ref`, `status`)
* `flats`
  * `id` (bigint, PK), `project_id` (foreignId, `projects.id`), `flat_number` (string)
  * `floor` (integer), `size_sqft` (decimal), `status` (enum: 'Available', 'Booked', 'Sold', 'Hold')
* `flat_purchase_rules` (`id`, `project_id`, `min_booking_money`, `max_installments`)
* `project_incentive_claims`
  * `id` (PK), `booking_id` (foreignId), `project_id` (foreignId), `user_id` (foreignId)
  * `is_external` (boolean), `sale_amount` (decimal), `incentive_percentage` (decimal), `incentive_amount` (decimal)
  * `claim_status` (enum: 'eligible', 'claimed', 'approved', 'rejected', 'paid')
* `project_incentive_payments` (`id`, `claim_id`, `user_id`, `payment_amount`, `payment_method`, `transaction_ref`, `payment_date`)
* **ক্লিন-আপ নোট:** বুকিং ও পেমেন্ট রুলসকে সিআরএম-এর মূল মডিউলের সাথে শক্তভাবে লিঙ্ক করা হয়েছে এবং ইনসেন্টিভ টেবিল দুটিকে ডেটাবেসে ফিজিক্যালি যুক্ত করা হয়েছে।

### খ) এলকোয়েন্ট মডেল রিলেশন (Model & Relations)
* **Lead Model (`App\Models\Lead`):**
  * `belongsTo(LeadSource::class)`
  * `hasMany(LeadFollowUp::class)`
  * `hasOne(LeadBooking::class)`
* **Booking Model (`App\Models\LeadBooking`):**
  * `belongsTo(Lead::class)`
  * `belongsTo(Flat::class)`
  * `hasMany(LeadPayment::class)`
  * `hasOne(ProjectIncentiveClaim::class)`

### গ) UI/UX রূপরেখা
* **CRM Pipeline (Kanban Board):** লিডগুলোর স্ট্যাটাস সহজে ট্র্যাক করার জন্য ড্র্যাগ-অ্যান্ড-ড্রপ কানবান বোর্ড।
* **My Incentives Portal:** সেলস টিমের জন্য তাদের অর্জিত বুকিং, বুকিং মানি এবং রেজিস্ট্রেশন কমপ্লিট হওয়ার পর ১-ক্লিকে বোনাস বা ইনসেন্টিভ দাবি করার বাটন।
* **Incentive Queue (Accounts):** অ্যাকাউন্টস টিমের জন্য সমস্ত ক্লেম এবং তাদের ডিসবার্সমেন্ট ট্র্যাকিং উইন্ডো।

---

## মডিউল ৩: ল্যান্ড ও প্রি-কন্সট্রাকশন (Land & Engineering)
প্রজেক্ট শুরু করার পূর্ববর্তী জমি অধিগ্রহণ এবং নকশা (Design) সংক্রান্ত কাজ এই মডিউলের অন্তর্ভুক্ত।

### ক) ডাটাবেস ডিজাইন (DB Schema & Cleanup)
* `land_acquisitions`
  * `id` (bigint, PK), `land_name` (string), `location` (string), `total_area` (decimal), `owner_name` (string), `total_amount` (decimal)
* `land_visibility_studies`
  * `id` (bigint, PK), `land_acquisition_id` (foreignId), `site_engineer_id` (foreignId), `status` (enum: 'Pending', 'Approved', 'Rejected'), `legal_status` (text)
* `land_registrations` (`id`, `land_acquisition_id`, `registration_date`, `amount`, `document_link`)
* `land_bayana_payments` (`id`, `land_acquisition_id`, `amount`, `payment_date`, `payment_method`, `transaction_ref`)
* `land_fund_requisitions`
  * `id` (bigint, PK), `land_acquisition_id` (foreignId), `requested_amount` (decimal), `requisition_type` (string)
  * `mgmt_status` (enum: 'Pending', 'Approved', 'Rejected'), `mgmt_by` (foreignId), `accounts_status` (string)
* `soil_tests` (`id`, `project_id`, `test_date`, `site_engineer_id`, `lab_test_document_link`)
* `design_types` (`id`, `name`, `status`)
* `project_drawings`
  * `id` (bigint, PK), `project_id` (foreignId), `phase_id` (foreignId, nullable), `design_type_id` (foreignId)
  * `approved_by_id` (foreignId, nullable), `status` (enum: 'Draft', 'Submitted', 'Approved', 'Rejected'), `design_link` (string)
* `architect_engineering` (`id`, `project_id`, `user_id`, `full_architecture_design_link`, `status`)
* **ক্লিন-আপ ও এপ্রুভাল নোট:** ল্যান্ড ফান্ড রিকোয়েস্ট সরাসরি পেমেন্ট এবং ক্যাশ রিলিজের সাথে যুক্ত থাকায় এটি তৈরীর পর অবশ্যই **Operation $\rightarrow$ QC $\rightarrow$ Audit $\rightarrow$ Management (MD/Director)** অনুমোদন ফ্লো পার হয়ে অ্যাকাউন্টস পেমেন্টে যাবে।

### খ) এলকোয়েন্ট মডেল রিলেশন (Model & Relations)
* **LandAcquisition Model (`App\Models\LandAcquisition`):**
  * `hasOne(LandVisibilityStudy::class)`
  * `hasMany(LandRegistration::class)`
  * `hasMany(LandBayanaPayment::class)`
  * `hasMany(LandFundRequisition::class)`
* **Project Drawing Model (`App\Models\ProjectDrawing`):**
  * `belongsTo(Project::class)`
  * `belongsTo(DesignType::class)`

### গ) UI/UX রূপরেখা
* **Engineering Timeline Progress:** ল্যান্ড ভিজিবিলিটি স্টাডি $\rightarrow$ সয়েল টেস্ট $\rightarrow$ ড্রয়িং আপলোড $\rightarrow$ স্ট্রাকচারাল ডিজাইন এপ্রুভালের একটি লিনিয়ার টাইমলাইন কার্ড ইন্টারফেস।
* **Drawing Version Control UI:** একই ড্রয়িংয়ের নতুন নতুন সংস্করণ (V1, V2) এবং স্থিতি দেখার জন্য সহজ ট্র্যাকিং উইন্ডো।

---

## মডিউল ৪: প্রজেক্ট পিএম ও প্ল্যানিং (Project PM & Planning)
প্রজেক্টের কাজের সময়সীমা নির্ধারণ, কাজের বিভাজন, মাসিক ও সাপ্তাহিক টার্গেট এবং সাইটের বাস্তব প্রগতির হিসেব করা এই মডিউলের দায়িত্ব।

### ক) ডাটাবেস ডিজাইন (DB Schema & Cleanup)
* `projects`
  * `id` (bigint, PK), `name` (string), `location` (string)
  * `pm_id` (foreignId, `users.id`), `site_engineer_id` (foreignId, `users.id`), `status` (string)
* `project_timelines` (`id`, `project_id`, `handover_months`, `construction_months`, `project_start_date`, `construction_start_date`, `construction_end_date`)
* `project_phases`
  * `id` (bigint, PK), `project_id` (foreignId), `phase_name` (string), `phase_order` (integer)
  * `weight_percentage` (decimal), `planned_start_date` (date), `planned_end_date` (date)
* `project_phase_items`
  * `id` (bigint, PK), `phase_id` (foreignId), `item_name` (string), `weight_pct` (decimal)
  * `design_type_id` (foreignId), `work_type_id` (foreignId)
* `phase_monthly_targets`
  * `id` (bigint, PK), `project_id` (foreignId), `phase_id` (foreignId), `target_month` (date), `total_target_weight_pct` (decimal), `planning_status` (enum: 'Draft', 'In Review', 'Approved')
* `phase_monthly_target_items`
  * `id` (bigint, PK), `target_id` (foreignId), `work_type_id` (foreignId), `work_item_name` (string), `monthly_target_qty` (decimal), `status` (string)
* `phase_weekly_progress`
  * `id` (bigint, PK), `project_id` (foreignId), `phase_id` (foreignId), `target_id` (foreignId)
  * `week_number` (integer), `week_start_date` (date), `week_end_date` (date), `entry_status` (string)
* `phase_weekly_progress_items`
  * `id` (bigint, PK), `weekly_progress_id` (foreignId), `target_item_id` (foreignId)
  * `planned_qty` (decimal), `achieved_qty` (decimal), `percent_complete` (decimal), `status` (string)
* `daily_progress_logs`
  * `id` (bigint, PK), `weekly_progress_id` (foreignId), `project_id` (foreignId)
  * `log_date` (date), `labour_count` (integer), `safety_ok` (boolean), `qc_checked` (boolean)
* `daily_work_item_logs`
  * `id` (bigint, PK), `daily_log_id` (foreignId), `target_item_id` (foreignId), `qty_done_today` (decimal), `remarks` (text)
* **ক্লিন-আপ নোট:** `project_phase_item_details` এবং `recovery_action_items` টেবিলগুলো বাতিল করে মূল আইটেম ও রিকভারি প্ল্যান টেবিলের সাথে মার্জ করা হয়েছে।

### খ) এলকোয়েন্ট মডেল রিলেশন (Model & Relations)
* **Project Model (`App\Models\Project`):**
  * `hasOne(ProjectTimeline::class)`
  * `hasMany(ProjectPhase::class)`
  * `hasMany(PhaseMonthlyTarget::class)`
* **ProjectPhase Model (`App\Models\ProjectPhase`):**
  * `belongsTo(Project::class)`
  * `hasMany(ProjectPhaseItem::class)`
* **MonthlyTarget Model (`App\Models\PhaseMonthlyTarget`):**
  * `belongsTo(Project::class)`
  * `hasMany(PhaseMonthlyTargetItem::class)`
  * `hasMany(PhaseWeeklyProgress::class)`

### গ) UI/UX রূপরেখা
* **Auto-Phasing Project Templates:** প্রজেক্ট শুরুর সময় আগে তৈরি করা টেমপ্লেট হতে ফেজ ও আইটেম অটো-কপি করার সহজ ইউজার ইন্টারফেস।
* **Unified Daily Progress Log (Mobile-First):** সাইট ইঞ্জিনিয়ারদের জন্য ধুলোবালি ও অন-সাইট ব্যবহারের সুবিধার্থে একদম ক্লিন ও সহজ মোবাইল স্ক্রিন। প্রতিদিনের এন্ট্রি দিলে তা অটোমেটিক্যালি ব্যাকএন্ডে সাপ্তাহিক প্রোগ্রেসে কনভার্ট হয়ে যাবে।

---

## মডিউল ৫: প্রকিউরমেন্ট, ইনভেন্টরি ও কিউসি (Procurement, Stores & QC)
উপাদান ক্রয়ের প্রক্রিয়া (PR $\rightarrow$ Quotation $\rightarrow$ CST $\rightarrow$ PO), সাইট গুদাম (Site Store/Warehouse) and কোয়ালিটি কন্ট্রোল (QC) একীভূতকারী মডিউল।

### ক) ডাটাবেস ডিজাইন (DB Schema & Cleanup)
* `vendors` (`id`, `name`, `phone`, `address`, `rating`, `status`)
* `vendor_ratings` (`id`, `vendor_id`, `rating_score`, `feedback`)
* `vendor_penalties` (`id`, `vendor_id`, `purchase_order_id`, `penalty_amount`, `penalty_status`, `reason`)
* `purchase_requests`
  * `id` (bigint, PK), `project_id` (foreignId), `bom_id` (foreignId, nullable)
  * `status` (enum: 'Draft', 'Submitted', 'Approved', 'Rejected')
  * `approved_by_id` (foreignId, PM), `qc_approved_by_id` (foreignId, Operation Head)
* `purchase_request_items` (`id`, `request_id`, `material_id`, `quantity`)
* `quotations` (`id`, `request_id`, `vendor_id`, `status`, `attachment`)
* `quotation_items` (`id`, `quotation_id`, `material_id`, `quantity`, `unit_price`)
* `quotation_comparisons` (`id`, `request_id`, `prepared_by`) (CST Main)
* `comparison_items` (`id`, `comparison_id`, `material_id`, `vendor_id`, `unit_price`, `is_selected`)
* `purchase_orders`
  * `id` (bigint, PK), `request_id` (foreignId)
  * `status` (enum: 'Draft', 'Issued', 'Partially Received', 'Fully Received', 'Cancelled')
  * `approved_by_pm` (foreignId), `qc_approved_by_id` (foreignId, Operation Head), `mgmt_approved_by_id` (foreignId, Management)
* `purchase_order_items` (`id`, `purchase_order_id`, `material_id`, `selected_vendor_id`, `quantity`, `unit_price`)
* `con_materials` (`id`, `name`, `unit`, `category_id`, `min_stock_level`)
* `con_material_categories` (`id`, `name`, `status`)
* `con_warehouses` (`id`, `name`, `project_id`, `location`)
* `con_stocks` (`id`, `material_id`, `warehouse_id`, `current_quantity`, `unit_price`, `total_value`)
* `con_stock_movements` (`id`, `material_id`, `warehouse_id`, `type` [IN/OUT], `quantity`, `ref_type`, `ref_id`)
* `con_material_issues` (`id`, `project_id`, `warehouse_id`, `material_id`, `quantity`, `issued_to`, `target_id`, `issue_date`)
* `material_receiving` (`id`, `purchase_order_id`, `received_by`, `overall_status` [Pending QC/Accepted], `warehouse_id`) (GRN)
* `material_receiving_items` (`id`, `receiving_id`, `purchase_order_item_id`, `ordered_quantity`, `received_quantity`, `status`)
* `qc_checklists` (`id`, `work_type_id`, `checklist_item_name`)
* `qc_inspections` (`id`, `project_id`, `work_type_id`, `inspection_date`, `overall_status`)
* `qc_inspection_items` (`id`, `qc_inspection_id`, `qc_checklist_id`, `is_passed`)

### খ) লজিক ও অনুমোদন পলিসি (Approval Rules)
* **পারচেজ রিকোয়েস্ট (PR) অনুমোদন:** বাজেটের ভেতরের পারচেজ রিকোয়েস্ট হলে এবং কোনো ক্যাশ বা ডিরেক্ট টাকা ছাড়ের বিষয় জড়িত না থাকলে তা **PM ও Operation Head** অনুমোদন দিলেই চূড়ান্ত এপ্রুভ হবে (ম্যানেজমেন্ট এপ্রুভালের প্রয়োজন নেই)।
* **অতিরিক্ত পারচেজ ও বাজেট বৃদ্ধি:** কোনো ম্যাটেরিয়াল ক্রয়ের প্রাক্কলন মূল প্রজেক্ট বাজেটকে অতিক্রম করলে তা **অপারেশন ও কিউসি ভেরিফিকেশনের পর ম্যানেজমেন্টের অনুমোদন** সাপেক্ষে PO-তে রূপান্তরিত হবে।

### গ) এলকোয়েন্ট মডেল রিলেশন (Model & Relations)
* **PurchaseRequest Model (`App\Models\PurchaseRequest`):**
  * `belongsTo(Project::class)`
  * `hasMany(PurchaseRequestItem::class)`
  * `hasMany(Quotation::class)`
* **PurchaseOrder Model (`App\Models\PurchaseOrder`):**
  * `belongsTo(PurchaseRequest::class)`
  * `hasMany(PurchaseOrderItem::class)`
  * `hasMany(MaterialReceiving::class)`

### ঘ) UI/UX রূপরেখা
* **৩-ধাপের PR জেনারেশন উইজার্ড:** ১. মেটেরিয়াল ও পরিমাণ সিলেক্ট $\rightarrow$ ২. বাজেটের সাথে ম্যাচ চেক $\rightarrow$ ৩. সাবমিট।
* **ডাইনামিক CST কম্পারিশন শিট:** বিভিন্ন ভেন্ডরের দরপত্র আপলোড করার পর সিস্টেম স্বয়ংক্রিয়ভাবে সর্বনিম্ন দরদাতা (L1 Bidder) লাল/সবুজ রঙে হাইলাইট করবে।

---

## মডিউল ৬: এইচআরএম, পে-রোল ও অ্যাকাউন্টস (HRM, Payroll & Accounts)
কর্মচারীদের রেকর্ড, দৈনিক হাজিরা, বেতন শীট তৈরি এবং প্রজেক্টের সামগ্রিক ব্যয়ের খতিয়ান (Ledger) সংরক্ষণ ও বিলিং।

### ক) ডাটাবেস ডিজাইন (DB Schema & Cleanup)
* `employees` (`id`, `user_id`, `employee_code`, `designation_id`, `department_id`, `salary`)
* `departments` (`id`, `name`, `status`)
* `designations` (`id`, `name`, `status`)
* `emp_attendances` (`id`, `employee_id`, `log_date`, `in_time`, `out_time`, `status` [Present/Absent/Late])
* `emp_weekly_day_off` (`id`, `employee_id`, `day_off_name`)
* `holidays` (`id`, `name`, `start_date`, `end_date`)
* `employee_leaves` (`id`, `employee_id`, `leave_type_id`, `start_date`, `end_date`, `status`)
* `leave_types` (`id`, `name`, `status`)
* `employee_leave_balances` (`id`, `employee_id`, `leave_type_id`, `allowed_days`, `used_days`)
* `payslips` (`id`, `employee_id`, `month`, `year`, `net_salary`, `status` [Draft/Generated/Paid])
* `payslip_items` (`id`, `payslip_id`, `item_name`, `type` [Allowance/Deduction], `amount`)
* `banks` (`id`, `name`, `status`)
* `company_bank_accounts` (`id`, `bank_id`, `account_number`, `account_name`, `balance`)
* `contractors` (`id`, `name`, `role`, `trade`, `daily_rate`, `status`)
* `contractor_bill_requests`
  * `id` (bigint, PK), `project_id` (foreignId), `operator_id` (foreignId), `work_order_id` (foreignId)
  * `bill_type` (enum: 'Advance', 'Progress', 'Retention'), `bill_amount` (decimal), `status` (string)
* `con_financial_transactions`
  * `id` (bigint, PK), `ref_type` (enum: 'contractor_bill', 'vendor_payment', 'operational_fund')
  * `ref_id` (bigint), `payment_type` (string), `amount_paid` (decimal), `transaction_date` (timestamp)
* **ক্লিন-আপ ও এপ্রুভাল লজিক:** 
  * `payslip_details` টেবিলটি ডিলিট করে `payslip_items`-এর ভেতর মার্জ করা হয়েছে।
  * `management_cashin`, `management_cashout` এবং ক্যাশআউট রিকোয়েস্ট টেবিলগুলোকে একটিমাত্র জেনারেলাইজড ট্রানজেকশন টেবিল `con_financial_transactions` এ রূপান্তর করা হয়েছে।
  * কন্ট্রাক্টর বিল রিকোয়েস্ট এবং ভেন্ডর পেমেন্ট যেহেতু টাকার বড় পেমেন্ট, তাই এগুলোতে **Operation, QC, PM এবং চূড়ান্ত পর্যায়ে Management (Role 3) অনুমোদন ফ্লো বজায় থাকবে**।

### খ) এলকোয়েন্ট মডেল রিলেশন (Model & Relations)
* **Employee Model (`App\Models\Employee`):**
  * `belongsTo(User::class)`
  * `belongsTo(Department::class)`
  * `belongsTo(Designation::class)`
  * `hasMany(EmpAttendance::class)`
  * `hasMany(Payslip::class)`
* **Payslip Model (`App\Models\Payslip`):**
  * `belongsTo(Employee::class)`
  * `hasMany(PayslipItem::class)`

### গ) UI/UX রূপরেখা
* **Accounts Disbursement Dashboard (পেমেন্ট কিউ):** অ্যাকাউন্টস টিম এক স্ক্রিনে দেখতে পাবে কোন ভেন্ডরের কত বিল বা কোন কন্ট্রাক্টরের কত পেমেন্ট এপ্রুভড হয়ে পেমেন্টের জন্য ওয়েট করছে।
* **১-ক্লিক কন্ট্রাক্টর বিল ক্রিয়েটর:** WPR এর পরিমাণ এবং ওয়ার্ক অর্ডারের চুক্তি অনুযায়ী রানিং বিল এক ক্লিকে লোড করার জন্য "Auto-Fill" বাটন যুক্ত ফর্ম।
* **HRM Attendance Grid:** কর্মচারীদের পুরো মাসের লেট ও অ্যাবসেন্ট এন্ট্রি এক পলকে দেখার জন্য ছক আকৃতির গ্রিড।

---

## ৫. লারাভেলে রূপান্তরের মডুলার রোডম্যাপ (Implementation Plan)

মাইগ্রেশন কাজটি অত্যন্ত সুরক্ষিত ও সহজভাবে সম্পন্ন করার জন্য নিম্নলিখিত ক্রম অনুসরণ করতে হবে:

```
[Phase 1] Core System (Module 1) ────> [Phase 2] CRM & Sales (Module 2)
                                                 │
                                                 ▼
[Phase 4] Procurement & Inventory (Module 5) <── [Phase 3] PM, Planning & Design (Module 3 & 4)
  │
  ▼
[Phase 5] HRM, Payroll & Accounts (Module 6) ──> [Phase 6] System Integration & Testing
```

1. **Phase 1: Laravel Project Setup & Core System (মডিউল ১)**
   * লারাভেল প্রজেক্ট ইনিশিয়ালাইজেশন।
   * Spatie Roles & Permissions সেটআপ এবং ইউজার অ্যাসাইনমেন্ট সম্পন্ন করা।
2. **Phase 2: CRM & Sales Migration (মডিউল ২)**
   * লিড ট্র্যাকিং, কানবান পাইপলাইন তৈরি এবং বুকিং লজিক স্থানান্তর।
   * ইনসেন্টিভ এপ্রুভাল এবং ডিসবার্সমেন্ট ফ্লো ডেভেলপমেন্ট।
3. **Phase 3: PM, Planning & Pre-construction (মডিউল ৩ ও ৪)**
   * প্রজেক্ট টেমপ্লেট হতে অটো-ফেজ ও সাব-আইটেম ক্লোনিং।
   * সাইট ইঞ্জিনিয়ারদের জন্য মোবাইল-ফার্স্ট ডেইলি সাইট লগ স্ক্রিন তৈরি।
4. **Phase 4: Procurement, Store & QC (মডিউল ৫)**
   * পারচেজ রিকোয়েস্ট তৈরি এবং বাজেটের অভ্যন্তরে থাকলে এমডি/ডিরেক্টর এপ্রুভাল ছাড়াই চূড়ান্ত অনুমোদিত হওয়ার লজিক ইমপ্লিমেন্ট করা।
   * ভেন্ডর পেমেন্ট রিকোয়েস্ট ও গুদাম স্টক-ইন (GRN) ম্যানেজমেন্ট।
5. **Phase 5: HRM, Payroll & Billing (মডিউল ৬)**
   * ওয়ান-ক্লিক কন্ট্রাক্টর বিলিং ভ্যালিডেশন এবং পে-রোল জেনারেশন।
   * অ্যাকাউন্টস পেমেন্ট ট্রানজেকশন ডিসবার্সমেন্ট গেটওয়ে।
6. **Phase 6: Verification, Queued Emails & Live Testing**
   * ব্যাকগ্রাউন্ডে ইমেইল ও নোটিফিকেশন পাঠানোর জন্য লারাভেল কিউ ওয়ার্কার (Queue Worker) কনফিগারেশন করা।
   * রিয়েল-টাইম এলার্ট বা পুশ নোটিফিকেশন যাচাই করা।