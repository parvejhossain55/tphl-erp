🏗️ Work Order Module — Full Implementation Plan
TPHL-ERP Phase 2 Construction | Batch-wise Plan

✅ Database (Already Done)
con_work_orders table — তৈরি হয়েছে ✅
contractor_bill_requests.work_order_id column — add হয়েছে ✅
📁 File Structure
app/
├── models/
│   └── Con_WorkOrder.php                    ← Model
├── controllers/Phase2/
│   └── Con_WorkOrderController.php          ← Controller
└── views/phase2/
    └── work-orders/
        ├── work-order-list.php
        ├── create-work-order.php
        ├── show-work-order.php
        └── edit-work-order.php
🔗 Routes (public/index.php তে যোগ হবে)
php
// ── Work Orders ─────────────────────────────────────────────
$routes['con-work-orders']                 = ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'index'];
$routes['con-work-order-create']           = ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'create'];
$routes['con-work-order-store']            = ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'store'];
$routes['con-work-order/(\d+)']            = ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'show'];
$routes['con-work-order-edit/(\d+)']       = ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'edit'];
$routes['con-work-order-update/(\d+)']     = ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'update'];
$routes['con-work-order-approve/(\d+)']    = ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'approve'];
$routes['con-work-order-reject/(\d+)']     = ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'reject'];
$routes['con-work-order-complete/(\d+)']   = ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'markComplete'];
$routes['con-work-order-qc-approve/(\d+)'] = ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'qcApprove'];
$routes['con-work-order-delete']           = ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'delete'];
$routes['con-ajax-wo-by-project']          = ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'ajaxGetByProject'];
🗂️ Sidebar Menu
Permission Name: Work Order

sidebar.php তে Contractor Management section এ add:

php
<?php if (hasPermission('Work Order') || hasPermission('Construction Management')): ?>
    <li class="slide <?= isActive('con-work-orders', $current_route) ?>">
        <a href="<?= $base_url ?>/con-work-orders"
            class="sidebar__menu-item <?= isActive('con-work-orders', $current_route) ?>">
            <i class="bi bi-file-earmark-ruled me-1"></i> Work Orders
        </a>
    </li>
<?php endif; ?>
Placement: con-contractors মেনুর পরে, con-contractor-bills মেনুর আগে।

🔐 Permission Matrix
Action	Role 3 (Director)	Role 15 (PM)	Role 22 (PM Head)	Role 13 (QC)	Role 6 (Accounts)
List/View	✅	✅	✅	✅	✅
Create	✅	✅	✅	❌	❌
Edit (draft only)	✅	✅	✅	❌	❌
Approve	✅	❌	✅	❌	❌
Mark Complete	✅	✅	✅	✅	❌
QC Approve	❌	❌	❌	✅	❌
Delete	✅	❌	❌	❌	❌
Permission DB insert:

sql
INSERT INTO permissions (name) VALUES ('Work Order');
-- তারপর role_permissions এ assign করো
🔄 Full Status Flow
PM creates
    ↓
[draft]
    ↓ Director/PM Head approves
[approved → active]
    ↓ Contractor কাজ করে
    ↓ PM marks complete
[work_completed]
    ↓ QC inspects
[qc_approved]
    ↓ Auto
[closed] ← এখন Contractor Bill তৈরি হতে পারে
💰 Payment Terms Logic
total_amount = unit_rate × quantity
advance_amount   = total_amount × (advance_percent  / 100)   // কাজ শুরুর আগে
running_amount   = total_amount × (running_percent  / 100)   // কাজ চলাকালীন
retention_amount = total_amount × (retention_percent/ 100)   // কাজ শেষে আটকে
Example: ৳10,00,000 কাজ, 20-70-10 terms:

Advance: ৳2,00,000
Running: ৳7,00,000
Retention: ৳1,00,000
📦 Batch Plan
🔵 BATCH 1 — Core CRUD
কাজ	File
Model তৈরি	Con_WorkOrder.php
Controller CRUD	Con_WorkOrderController.php
List view	work-order-list.php
Create form	create-work-order.php
Detail page	show-work-order.php
Routes add	public/index.php
Sidebar menu	sidebar.php
✅ Done when: Work Order তৈরি, list, দেখা যাচ্ছে

🟡 BATCH 2 — Approval Flow
কাজ	Details
approve() method	Role [3, 22] only
reject() method	Role [3, 22] only
markComplete()	Role [3, 15]
show page update	Approval buttons conditional দেখানো
✅ Done when: Director approve → status active, PM complete → work_completed

🟠 BATCH 3 — QC Approval
কাজ	Details
qcApprove() method	Role [13] only
show page update	QC form (notes + photo)
Auto-close logic	qc_approved → closed
✅ Done when: QC approve → status closed, Contractor Bill তৈরি unblock হয়

🟢 BATCH 4 — Contractor Bill Link
কাজ	Details
create-bill.php update	Work Order dropdown (AJAX)
Bill controller update	work_order_id store
show-work-order.php	Linked bills section
AJAX endpoint	ajaxGetByProject() → closed WOs
✅ Done when: Bill তৈরির সময় Work Order select করা যাচ্ছে

🔴 BATCH 5 — Payment Summary (Optional)
কাজ	Details
show page	Advance/Running/Retention paid summary
Dashboard card	WO count per project



================ kivabe 1st to last work order plan implement korbo =================
১. Planning & Budgeting (badge ১)
Initial Budget: Project-এর একদম শুরুতে Estimator বা PM একটা বাজেট তৈরি করবে। এটা Director বা PM Head Approve করবে।
Working Plan: বাজেটের উপর ভিত্তি করে কোন মাসে কোন কাজ হবে তার Plan হবে।
Target Fixed: মাসের Plan-কে ভেঙে সপ্তাহভিত্তিক Target (যেমন: Week 1-এ Foundation ২০%) সেট করবে Site Engineer / PM।
🟡 ২. Work Order তৈরি ও Contractor Assign (badge ২ - নতুন)
কাজ করার জন্য তো ঠিকাদার (Contractor) লাগবে! তাই এখন Target Fixed-এর পর Work Order হবে:

কে তৈরি করবে: Project Manager (PM), PM Head বা Director।
কী করবে: /con-work-order-create এ গিয়ে Project, Contractor, Work Type এবং কোন Target-এর কাজ সেটা সিলেক্ট করবে। এখানে Payment Terms (Advance কত %, Retention কত %) সব উল্লেখ থাকবে।
Status: প্রথমে এটা Draft অবস্থায় থাকবে।
🟠 ৩. Work Order Approval (badge ৩)
কে Approve করবে: Director (Role 3), PM Head (Role 22) অথবা Admin। (PM নিজে তৈরি করে নিজে Approve করতে পারবে না, Management-এর পারমিশন লাগবে।)
কী করবে: Work Order চেক করে Approve অথবা Reject করবে।
Status: Approve হলে এটা Approved হয়ে যাবে। (এখন Contractor কাজ শুরু করবে)।
🔵 ৪. কাজের Report ও সমাপ্তি (badge ৪)
Weekly Report: Contractor মাঠে কাজ করবে, আর Site Engineer প্রতি সপ্তাহে Weekly Progress Report দিবে। রিপোর্ট দেওয়ার সময় সে এই Work Order-টা সিলেক্ট করে দিবে। (যাতে বোঝা যায় এই Work Order-এ কত % কাজ হলো)।
Mark Complete: যখন ঠিকাদারের কাজ শেষ হয়ে যাবে, তখন PM (Role 15) Work Order-এর পেজে গিয়ে "Mark Work Complete" বাটনে ক্লিক করবে।
Status: Work Completed (অর্থাৎ কাজ শেষ, কিন্তু বিল হবে না কারণ QC বাকি)।
🟣 ৫. Quality Control বা QC Checking (badge ৫)
কে করবে: QC Engineer (Role 13)।
কী করবে: QC ইঞ্জিনিয়ার সাইটে গিয়ে কাজ চেক করবে। কাজ ঠিক থাকলে সে Work Order পেজে গিয়ে "QC Approve & Close" বাটনে ক্লিক করবে।
Status: Closed। (এর মানে কাজ শতভাগ বুঝে পাওয়া গেছে এবং Work Order সফলভাবে শেষ!)
💸 ৬. Contractor Bill & Payment (badge ৬)
কাজ তো শেষ, এবার ঠিকাদারের বিল দেওয়ার পালা!

কে করবে: Site Engineer বা PM Contractor Bill Create করবে।
কীভাবে: /con-contractor-bill-create এ গিয়ে Work Order টা সিলেক্ট করে দিবে।
সুবিধা: Accounts ও Management বিল দেওয়ার সময় Work Order-এ ক্লিক করলেই দেখতে পাবে — এই কাজে কত টাকার চুক্তি ছিল, কত % Advance দেওয়া হয়েছিল, এবং কত % Retention (জামানত) রেখে বিল দিতে হবে।
Approval: Accounts / Director বিল Approve করে Payment দিবে।
🎯 একনজরে Approval Role Summary:
Work Order Approve: Director, PM Head, Admin
Work Order Mark Complete: PM, PM Head
QC Approve: QC Engineer
Contractor Bill Approve: Accounts / Director (আগের মতোই)
পুরো Process-টা একটা Corporate Construction Company-তে ঠিক এভাবেই হয়, যাতে কোনো ফাঁকফোকর না থাকে এবং ফাইন্যান্সের টাকা একদম সিকিউর থাকে। পুরো ফ্লো-টা কি আপনার পছন্দ হয়েছে?


================= purchase request er por purchase execution plan =================
Database Modification (কোথায় কলাম অ্যাড করব)
purchase_requests টেবিলে আমরা নতুন একটি কলাম অ্যাড করব।

Table: purchase_requests
New Column: assigned_executive_id (INT, NULL)
SQL Query:
sql
ALTER TABLE purchase_requests ADD assigned_executive_id INT NULL AFTER approved_by_id;
2. Workflow Plan (কাজের ফ্লো কেমন হবে)
Step 1 (Site Engineer): সাইট ইঞ্জিনিয়ার আগের মতোই Purchase Request (PR) তৈরি করবে। তখন assigned_executive_id খালি (Null) থাকবে।
Step 2 (Purchase Head): Purchase Head-এর ড্যাশবোর্ডে বা লিস্টে এই নতুন রিকোয়েস্টগুলো দেখাবে। সেখানে একটি "Assign Executive" অপশন থাকবে। Purchase Head ড্রপডাউন থেকে একজন Purchase Executive (role_id = 23) সিলেক্ট করে অ্যাসাইন করে দিবে। তখন ডাটাবেজে assigned_executive_id আপডেট হয়ে যাবে।
Step 3 (Purchase Executive - Role 23): Purchase Executive লগইন করলে সে শুধু সেই Purchase Request-গুলোই দেখতে পারবে, যেগুলো তাকে অ্যাসাইন করা হয়েছে (WHERE assigned_executive_id = $his_user_id)।
Step 4 (CST Process): এরপর Purchase Executive সেই অ্যাসাইন করা রিকোয়েস্টের জন্য ভেন্ডরদের থেকে কোটেশন নিবে, সিস্টেমে ইনপুট দিবে এবং CST তৈরি করে অ্যাপ্রুভালের জন্য পাঠাবে।
3. Purchase Executive Dashboard Plan
যেহেতু Purchase Executive-এর জন্য আলাদা ড্যাশবোর্ড হবে, সেখানে আমরা নিচের উইজেটগুলো রাখতে পারি:

Total Assigned Requests: তাকে মোট কয়টি PR অ্যাসাইন করা হয়েছে।
Pending CST / Ongoing: কয়টি PR-এর CST এখনো বাকি আছে।
Completed CST: কয়টি রিকোয়েস্টের CST শেষ হয়েছে।
My Projects: সে বর্তমানে কোন কোন প্রজেক্টের PR নিয়ে কাজ করছে তার লিস্ট।
Cost Savings / Efficiency (কত টাকা সেভ করেছে):
এখানে একটা প্রশ্ন আছে: "কত টাকা কম CST করেছে" বা Savings-টা আমরা কীভাবে ক্যালকুলেট করব?
অপশন ১: ভেন্ডররা যে কোটেশনগুলো দিয়েছে, তার মধ্যে সর্বোচ্চ (Highest) দাম থেকে সে বারগেইন করে সবচেয়ে কম (Lowest/Approved) যে দামে ফাইনাল করেছে, তার পার্থক্য (Highest Quote - Approved Quote = Savings)।
অপশন ২: BOM (Bill of Materials)-এর এস্টিমেটেড প্রাইস থেকে সে কত কমে ফাইনাল করেছে (BOM Price - Final CST Price = Savings)। আপনি কোন লজিকে Savings ক্যালকুলেট করতে চাচ্ছেন, সেটা জানালে আমি সেভাবে লজিক লিখে দিব।
পরবর্তী কাজ
আপনি যদি এই প্ল্যানটিতে একমত হন, তাহলে আমি প্রথমে: ১. ডাটাবেজে কলামটি অ্যাড করে দিচ্ছি। ২. Purchase Head-এর লিস্টে Assign to Executive অপশনটি তৈরি করছি। ৩. Purchase Executive-এর জন্য আলাদা Dashboard (role_id=23 অনুযায়ী) এবং Controller/View তৈরি করা শুরু করছি।

প্ল্যানটা ঠিক আছে কি না জানাবেন!