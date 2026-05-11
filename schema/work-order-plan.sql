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




=========================================


খুবই চমৎকার এবং প্র্যাকটিক্যাল একটি প্রশ্ন!

এর সহজ উত্তর হলো: ওয়ার্ক অর্ডার (Work Order) সাধারণত প্রজেক্টের পুরো কাজের জন্য একসাথে (একবারে) করা হয়, সাপ্তাহিক ভিত্তিতে নয়।

এখানে বিষয়টার কাজের ফ্লো (Workflow) যেভাবে কাজ করে তা হলো:

১. পুরো কাজের জন্য একটি ওয়ার্ক অর্ডার (Grand Contract): ধরুন আপনি একজন সাব-কন্ট্রাক্টরকে ৫ তলা বিল্ডিংয়ের সব "প্লাস্টারের কাজের" দায়িত্ব দিলেন। এর জন্য আপনি একবারই একটি ওয়ার্ক অর্ডার বানাবেন। সেখানে উল্লেখ থাকবে মোট কত স্কয়ার ফিট প্লাস্টার করতে হবে এবং রেট কত। এই ওয়ার্ক অর্ডারটি মূলত তার সাথে আপনার "মূল চুক্তি" বা মেইন বাজেট।

২. সাপ্তাহিক টার্গেট (Weekly Targets): পুরো কাজের চুক্তি হয়ে যাওয়ার পর, সাইট ইঞ্জিনিয়ার সেই বড় কাজটিকে ভেঙে ভেঙে কন্ট্রাক্টরকে সাপ্তাহিক টার্গেট দেবেন (যেমন: এই সপ্তাহে শুধু নিচতলার ১০০০ স্কয়ার ফিট করতে হবে)।

৩. রানিং বিল (RA Bills): কন্ট্রাক্টর ওই সাপ্তাহিক টার্গেট অনুযায়ী কাজ করবে এবং প্রতি সপ্তাহে (বা ১৫ দিন পরপর) যতটুকু কাজ শেষ করেছে, ঠিক ততটুকুর জন্য একটি "কন্ট্রাক্টর বিল (Running Bill)" সাবমিট করবে। আপনার ওয়ার্ক অর্ডারে থাকা "Running Percent" বা "Retention Percent" এর ওপর ভিত্তি করে তার ওই সপ্তাহের কাজের টাকা পরিশোধ করা হবে।

ফর্মে 'Work Plan' বা 'Target' ফিল্ডগুলো কেন আছে?
ওয়ার্ক অর্ডার তৈরি করার সময় আপনি দেখবেন নিচে "Working Plan" বা "Target" সিলেক্ট করার অপশন আছে। এগুলো অপশনাল।

আপনি যদি কোনো কন্ট্রাক্টরকে অনেক লম্বা সময়ের জন্য নিয়োগ দেন, তাহলে এই ফিল্ডগুলো ফাঁকা রাখতে পারেন।
কিন্তু যদি এমন হয় যে, কোনো ছোট কাজের জন্য একজন কন্ট্রাক্টরকে শুধু ১ সপ্তাহের জন্যই আনা হয়েছে (যেমন: শুধু একটা পানির ট্যাংক পরিষ্কার করা), তখন আপনি সরাসরি ওই সপ্তাহের নির্দিষ্ট টার্গেটের সাথে ওয়ার্ক অর্ডারটি লিংক করে দিতে পারেন।
সংক্ষিপ্ত পরামর্শ: কন্ট্রাক্টরের সাথে যে কাজের জন্য চুক্তি হয়েছে, তার পুরোটার জন্য একসাথে একটি ওয়ার্ক অর্ডার বানিয়ে ফেলুন। এরপর তারা সাপ্তাহিক ভিত্তিতে কাজ করবে এবং বিল নেবে। এতে আপনার হিসাব রাখতে সুবিধা হবে যে— মোট চুক্তির কত টাকার কাজ শেষ হয়েছে এবং কত টাকা বাকি আছে।

=========================================

=========================================
হ্যাঁ, আপনার কনসেপ্ট একদম সঠিক! আপনি খুব সুন্দরভাবে পয়েন্টটা ধরেছেন।

আপনার প্রজেক্টের স্ট্রাকচার অনুযায়ী: ১. Work Design Type (design_id): এটি দিয়ে বোঝানো হচ্ছে কাজের বড় ক্যাটাগরি বা মূল ডিসিপ্লিন। যেমন: Electrical, Plumbing, Structural, বা Architectural। ২. Work Type (work_type_id): এটি হলো সেই মূল ডিজাইনের ভেতরের ছোট ছোট নির্দিষ্ট কাজ (Sub-tasks)। যেমন, "Electrical" ডিজাইনের ভেতরে ছোট ছোট Work Type হতে পারে:

Conduit Laying (পাইপ বসানো)
Wiring (তার টানা)
Switchboard Fitting (সুইচবোর্ড বসানো)
এগুলো কি Work Order-এ দরকার আছে?
অবশ্যই দরকার আছে এবং এগুলো রাখা খুবই ভালো প্র্যাকটিস। কারণ:

যখন আপনি বিল বা কাজের হিসাব মেলাবেন, তখন শুধু "Electrical" দেখলে বুঝতে পারবেন না কন্ট্রাক্টর ঠিক কোন কাজের জন্য বিল চাচ্ছে।
কিন্তু যদি Design Type = Electrical এবং Work Type = Wiring দেওয়া থাকে, তখন এক নিমিষেই ক্লিয়ার হয়ে যাবে যে এই কন্ট্রাক্টরকে শুধু তার টানার (Wiring) জন্য নিয়োগ দেওয়া হয়েছে।
Working Plan কি দরকার আছে?
Working Plan হলো আপনার টাইমলাইন বা শিডিউল (যেমন: মে মাসের প্ল্যান)।

একজন কন্ট্রাক্টরকে আপনি পুরো বিল্ডিংয়ের "Wiring" এর কাজ দিলেন (Work Order)।
কিন্তু সে তো পুরো বিল্ডিং একদিনে করবে না। সে আপনার মে মাসের Working Plan অনুযায়ী শুধু নিচতলার কাজ করবে। তাই আপনি চাইলে ওয়ার্ক অর্ডারটিকে ওই নির্দিষ্ট Working Plan-এর সাথে যুক্ত (Link) করে রাখতে পারেন। এটি অপশনাল ফিল্ড হিসেবেই ফর্মে রাখা হয়েছে, আপনি চাইলে ব্যবহার করতে পারেন, না চাইলে ফাঁকা রাখতে পারেন।
সারসংক্ষেপ: আপনার বর্তমান ফ্লো-টি খুবই স্মার্ট! Project ➔ Design Type (বড় ক্যাটাগরি) ➔ Work Type (ছোট নির্দিষ্ট কাজ)। এগুলো ওয়ার্ক অর্ডারে থাকলে ভবিষ্যতে আপনার রিপোর্ট দেখতে এবং বাজেট কন্ট্রোল করতে অনেক সুবিধা হবে।
==========================================
=========================================
খুবই গুরুত্বপূর্ণ একটি প্রশ্ন! আপনার সিস্টেমের আর্কিটেকচার এবং কোড ঘেঁটে আমি যা দেখতে পাচ্ছি, তার ওপর ভিত্তি করে আপনার প্রশ্নের উত্তরগুলো নিচে গুছিয়ে দিচ্ছি:

১. Work Order কে তৈরি করবে এবং Approval System দরকার আছে কি?
হ্যাঁ, অবশ্যই Approval System দরকার আছে এবং আপনার সিস্টেমে সেটি অলরেডি তৈরি করা আছে! আপনার Con_WorkOrderController-এর লজিক অনুযায়ী:

Project Manager (PM) যখন একটি Work Order তৈরি করেন, তখন সেটি "Draft" (খসড়া) স্ট্যাটাসে থাকে।
এরপর Admin (Role 1), Director (Role 3), অথবা PM Head (Role 22)— উনারা এই Work Order টি দেখে "Approve" করবেন।
Approve হওয়ার পরেই Work Order টি "Active" হবে এবং কন্ট্রাক্টর কাজ শুরু করার পারমিশন পাবে। (অর্থাৎ টাকার চুক্তি হচ্ছে, তাই PM একাই এটি ফাইনাল করতে পারবেন না, ওপর মহলের অ্যাপ্রুভাল লাগবেই)।
২. Work Order-কে কার সাথে লিঙ্ক করবেন? (Target নাকি Contractor Bill?)
আপনার সিস্টেমে Contractor Bill-এর সাথেই Work Order লিঙ্ক করতে হবে! Target Fixed-এর সাথে Work Order সরাসরি লিঙ্ক করার কোনো প্রয়োজন নেই।

ফ্লো-টা ঠিক এ রকম হবে:

Work Order তৈরি ও অ্যাপ্রুভ: PM পুরো কাজের (যেমন: ইলেকট্রিক্যাল ওয়্যারিং) জন্য একটি Work Order তৈরি করে অ্যাপ্রুভ করাবেন।
কাজ ও টার্গেট কমপ্লিট: কন্ট্রাক্টর সাইটে কাজ করতে থাকবে এবং সাইট ইঞ্জিনিয়ার তার টার্গেটগুলো কমপ্লিট হিসেবে আপডেট করবে।
Contractor Bill তৈরি (এখানেই লিঙ্ক হবে): যখন কোনো টার্গেট কমপ্লিট হবে এবং কন্ট্রাক্টর বিল চাইবে, তখন আপনি Contractor Bill Create করবেন।
বিল তৈরি করার ফর্মে আপনি প্রজেক্ট সিলেক্ট করার পর "Work Order" সিলেক্ট করার অপশন পাবেন (কারণ contractor_bill_requests টেবিলে work_order_id রাখা আছে)।
আপনি যখনই ওই Work Order টি সিলেক্ট করবেন, সিস্টেম অটোমেটিকভাবে বুঝে যাবে কন্ট্রাক্টরের Unit Rate কত ছিল, মোট কত টাকার চুক্তি ছিল এবং আগে কত টাকা বিল দেওয়া হয়েছে।
সহজ কথায় আপনার করণীয়:
Work Order তৈরি করার সময় Target লিঙ্ক করার দরকার নেই।
কন্ট্রাক্টর যখন কাজের বিল চাইবে, তখন Contractor Bill তৈরি করার সময় সেই নির্দিষ্ট Work Order-টিকে ড্রপডাউন থেকে সিলেক্ট করে (লিঙ্ক করে) বিল সাবমিট করতে হবে। এতে করে চুক্তির চেয়ে বেশি টাকা কখনোই বিল হিসেবে বের হবে না।
আপনার সিস্টেমে এই ফ্লো-টাই সবচেয়ে পারফেক্ট এবং সিকিউরড!
===========================================

===========================================
ম্যানেজমেন্ট (Role ID 1, 3, 22) থেকে ওয়ার্ক অর্ডারটি Approve (অনুমোদন) হওয়ার পর এটি বেশ কয়েকটি ধাপে কাজ করবে এবং কন্ট্রাক্টর বিলিংয়ের সাথে সরাসরি যুক্ত হবে। পুরো প্রসেসটি নিচে বুঝিয়ে দিচ্ছি:

অ্যাপ্রুভ হওয়ার পর নেক্সট কী হবে এবং কোথায় কাজে লাগবে?
১. Active Status (কাজের অনুমতি): অ্যাপ্রুভ হওয়ার সাথে সাথে ওয়ার্ক অর্ডারটির স্ট্যাটাস 'Draft' থেকে পরিবর্তন হয়ে 'Active' হয়ে যাবে। এর মানে হলো ঠিকাদার (Contractor) এখন সাইটে এই অর্ডারের ভিত্তিতে কাজ শুরু করতে পারবেন।

২. Mark Complete (কাজ শেষ হওয়া): ঠিকাদারের কাজ শেষ হয়ে গেলে সাইট ইঞ্জিনিয়ার বা পিএম (PM) সিস্টেম থেকে ওয়ার্ক অর্ডারটিকে 'Mark Complete' করবেন।

৩. QC Approval (কোয়ালিটি কন্ট্রোল টিমের চেক): কাজ Complete মার্ক করার পর সেটি QC (Quality Control) টিমের কাছে যাবে। তারা সাইটে গিয়ে কাজের মান যাচাই করে সিস্টেমে QC Approve করবেন। তখন ওয়ার্ক অর্ডারটি 'Closed' হয়ে যাবে।

৪. Contractor Bill (ঠিকাদারের বিল তৈরি): ওয়ার্ক অর্ডারটির সবচেয়ে প্রধান কাজ হলো কন্ট্রাক্টর বিল (Contractor Bill) জেনারেট করা। কাজ শেষ এবং QC অ্যাপ্রুভ হওয়ার পর, ঠিকাদারের বিল তৈরি করার সময় এই ওয়ার্ক অর্ডারের সাথে লিঙ্ক করতে হয়। ওয়ার্ক অর্ডারে আপনি যে রেট (Unit Rate), কোয়ান্টিটি (Quantity), অ্যাডভান্স পার্সেন্টেজ (Advance %) এবং রিটেনশন পার্সেন্টেজ (Retention %) সেট করে দিয়েছিলেন, বিল তৈরির সময় সেই অনুযায়ী স্বয়ংক্রিয়ভাবে টাকার হিসেব (Calculation) হয়ে যাবে।

ওয়ার্ক অর্ডারের লিঙ্ক কোথায় পাবেন?
Dashboard Notification: ড্যাশবোর্ডে ম্যানেজমেন্টের জন্য যে নতুন "Pending Work Orders" টেবিলটি তৈরি করে দিয়েছি, সেখানে ডানপাশে "Review & Approve" বাটনে ক্লিক করলেই সরাসরি ওই ওয়ার্ক অর্ডারের বিস্তারিত পেজে চলে যাবেন।
Main Menu / Sidebar: সাইডবারে Contractor বা Work Orders মেনু থেকে লিস্টে গেলে সব ওয়ার্ক অর্ডার একসাথে দেখা যাবে। এর ব্রাউজার লিঙ্ক হলো: আপনার_ডোমেইন/con-work-orders
Contractor Bill Form: যখন আপনি নতুন কন্ট্রাক্টর বিল তৈরি (Create Contractor Bill) করতে যাবেন, তখন সেখানে "Select Work Order" নামের একটি ড্রপডাউন থাকবে। প্রজেক্ট সিলেক্ট করার পর ওই প্রজেক্টের সব অ্যাপ্রুভড ওয়ার্ক অর্ডারের লিস্ট সেখানে চলে আসবে এবং সেখান থেকে লিঙ্ক করা যাবে।
সংক্ষেপে, ওয়ার্ক অর্ডারটি মূলত কন্ট্রাক্টরদের কাজ বুঝিয়ে দেওয়া এবং পরবর্তীতে তাদের নির্ভুল বিল পেমেন্ট করার মূল ভিত্তি হিসেবে কাজ করবে!


===========================================


===========================================

Working Plan হলো প্রজেক্টের একটি মাসিক (Month-wise) লক্ষ্য। যেমন: "এই মাসে আমরা ২য় তলার ছাদ ঢালাইয়ের কাজ করব।" এটি একটি সাধারণ প্ল্যান, যা কোনো নির্দিষ্ট ঠিকাদারের (Contractor) জন্য নয়।
আপনি চাইলে এই একটি মাত্র Working Plan বাস্তবায়নের জন্য দুজন ভিন্ন ঠিকাদারকে দুটি আলাদা Work Order দিতে পারেন (যেমন: জোন-এ এর কাজ ঠিকাদার X কে এবং জোন-বি এর কাজ ঠিকাদার Y কে)।
যেহেতু একটি Working Plan-এর অধীনে একাধিক Work Order থাকতে পারে, তাই work_plans টেবিলে work_order_id রাখা ডাটাবেজ স্ট্রাকচার অনুযায়ী ভুল হবে। বরং বর্তমান সিস্টেম অনুযায়ী con_work_orders টেবিলেই work_plan_id রাখা আছে, যা একদম সঠিক।
কেন target_fixed টেবিলে রাখা সঠিক?
Target Fixed হলো একেবারে মাঠপর্যায়ের বা সাপ্তাহিক (Weekly) কাজের লক্ষ্য।
সাইট ইঞ্জিনিয়ার যখন সাপ্তাহিক টার্গেট দেন, তখন তিনি নির্দিষ্ট একজন ঠিকাদারকে তার কাজের চুক্তির (Work Order) ওপর ভিত্তি করেই টার্গেট দেন। যেমন: "ঠিকাদার X, আপনার ওয়ার্ক অর্ডারের চুক্তি অনুযায়ী, এই সপ্তাহে আপনাকে ছাদ ঢালাইয়ের ২৫% কাজ শেষ করতে হবে।"
যেহেতু সাপ্তাহিক টার্গেটটি সরাসরি কোনো নির্দিষ্ট ঠিকাদারের ওয়ার্ক অর্ডারের বিপরীতে দেওয়া হচ্ছে, তাই target_fixed টেবিলে work_order_id রাখাটাই ১০০% স্ট্যান্ডার্ড এবং সঠিক প্রসেস।
সারসংক্ষেপ (Hierarchy): ১. Working Plan (মাসিক লক্ষ্য) ২. ➔ Work Order (মাসের লক্ষ্যের ওপর ভিত্তি করে ঠিকাদারকে কাজ দেওয়া। এখানে work_plan_id থাকে।) ৩. ➔ Target Fixed (ঠিকাদারের কাজের ওপর ভিত্তি করে সাপ্তাহিক লক্ষ্য। তাই এখানে work_order_id থাকে, যা আমরা মাত্র যোগ করলাম।)

আপনার চিন্তা একদম নিখুঁত ছিল! আমরা target_fixed টেবিলেই কলামটি অ্যাড করেছি এবং এটাই সবচেয়ে বেস্ট প্র্যাকটিস।
=============================





আমি সিস্টেমে যে পরিবর্তনগুলো করেছি: ১. Target Fixed (সাপ্তাহিক টার্গেট তৈরি এবং এডিট করার) ফর্মগুলোতে "Work Order (Optional)" নামে নতুন একটি ড্রপডাউন অপশন যোগ করেছি। ২. আপনি যখনই কোনো প্রজেক্ট সিলেক্ট করবেন, ওই প্রজেক্টের আন্ডারে থাকা সব Active/Approved ওয়ার্ক অর্ডারগুলো স্বয়ংক্রিয়ভাবে ওই ড্রপডাউনে চলে আসবে। ৩. ডাটাবেজ মডেলে (Con_TargetFixed.php) ওয়ার্ক অর্ডারের ডাটা সেভ করা এবং ডাটাবেজ থেকে রিড করার জন্য লজিক আপডেট করে দিয়েছি। ৪. Work Order-এর কন্ট্রোলার এবং মডেল থেকে Target সংক্রান্ত সব অপ্রয়োজনীয়/অব্যবহৃত কোড রিমুভ করে সিস্টেমটি আরও ক্লিন করে দিয়েছি (কারণ টার্গেট এখন ওয়ার্ক অর্ডারের পরে তৈরি হবে)।

এখন আপনি একটি ওয়ার্ক অর্ডার ইস্যু করার পর, খুব সহজেই সেই ওয়ার্ক অর্ডারের রেফারেন্স দিয়ে সাইট ইঞ্জিনিয়ারের জন্য সাপ্তাহিক টার্গেট সেট করতে পারবেন।

আপনি ডাটাবেজে SQL কমান্ডটি রান করে নতুন ফ্লো-টি একবার টেস্ট করে দেখুন। সবকিছু ঠিকঠাক কাজ করছে কি না বা আরও কোনো পরিবর্তন লাগবে কি না, তা আমাকে জানাতে পারেন!
==============================================

Regarding your question about "Rate & Budget":
A Work Order is essentially a contract or agreement with a Sub-contractor to do a specific job (like brickwork, casting, or painting). When you hire a contractor, you have to agree on how much you will pay them.

The Rate & Budget section defines exactly how much you are agreeing to pay the contractor for this job.

Here is what each field means:

Unit: The measurement unit of the work (e.g., sqft, cft, job, tons, kg).
Quantity: The total estimated amount of work you want the contractor to do.
Unit Rate: The agreed price you will pay the contractor for 1 unit of their work.
Total Contract Amount: The total estimated budget for this entire work order. 
The system automatically calculates this as Quantity × Unit Rate.
Example Scenario (Painting Job): If you hire a contractor to paint the walls of a project:

Unit: sqft (Square Feet)
Quantity: 5000 (You estimate there is 5000 sqft of wall to paint)
Unit Rate: 120 (You agree to pay the contractor 120 Taka for every 1 sqft they paint)
Total Contract Amount: 600,000 Taka (This is the total budget allocated for this contractor's work order).
Why is this needed? Later on, when the contractor finishes some work and asks for money, they will submit a Contractor Bill. The system will use this Unit Rate to automatically calculate how much they should be paid for the work they completed, and it will ensure that their total bills never cross the Total Contract Amount.

(Note: If you are doing a "Lumpsum" or fixed-price contract where quantity doesn't matter, you can just put Unit as job, Quantity as 1, and the Unit Rate as the total fixed price).