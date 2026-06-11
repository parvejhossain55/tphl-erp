---
marp: true
theme: gaia
_class: lead
paginate: true
backgroundColor: #0f172a
color: #cbd5e1
style: |
  section {
    font-family: 'Helvetica Neue', Arial, sans-serif;
    padding: 35px;
    font-size: 1.1em;
  }
  h1 {
    color: #38bdf8;
    font-size: 1.6em;
  }
  h2 {
    color: #38bdf8;
    font-size: 1.3em;
    border-bottom: 2px solid #38bdf8;
    padding-bottom: 5px;
    margin-top: 0;
  }
  footer {
    font-size: 0.5em;
    color: #64748b;
  }
  code {
    background-color: #1e293b;
    color: #38bdf8;
    font-family: 'Courier New', Courier, monospace;
    font-size: 0.9em;
  }
  ul {
    margin-top: 10px;
    margin-bottom: 10px;
  }
  li {
    margin-bottom: 5px;
  }
  table {
    font-size: 0.8em;
    border-collapse: collapse;
    width: 100%;
    margin-top: 10px;
  }
  th, td {
    border: 1px solid #475569;
    padding: 8px;
    text-align: left;
  }
  th {
    background-color: #1e293b;
    color: #38bdf8;
  }
---

# **TPHL-ERP**
## **Project Progress & Database Architecture Blueprint**
### **Senior Developer & Project Manager Technical Presentation**

---

<!-- _class: default -->
<!-- backgroundColor: #1e293b -->
<!-- color: #e2e8f0 -->

## 🛠️ Core MVC Directory Mapping

কনস্ট্রাকশন ফেজ ২ প্রজেক্ট ম্যানেজমেন্টের সমস্ত ফাইল নিচের ডিরেক্টরি অনুযায়ী সাজানো রয়েছে:

* **Routes Mapping:** `public/index.php` (সব ডাইনামিক রুটের তালিকা)
* **Controllers:** `app/controllers/Phase2/` (বিজনেস লজিক ও অ্যাক্সেস কন্ট্রোল)
* **Models:** `app/models/` (`Con_` প্রিফিক্স যুক্ত প্রিপেয়ার্ড কুয়েরি)
* **Views:** `app/views/phase2/` (ইউজার ইন্টারফেস ও এইচটিএমএল ভিউ)
* **SQL Migrations:** `schema/custom-migration/` (`tphl_db.sql` ও অন্যান্য ডেটাবেস প্যাচ)

---

## 🏗️ লাইফসাইকেল ট্র্যাকিং এবং ব্যবহৃত টেবিল ম্যাপিং

প্রজেক্ট প্রগ্রেস ট্র্যাকিং মূলত ৫টি প্রধান ডেটাবেস ধাপে কাজ করে:

```
[ Projects ] ➔ [ Initial Estimate ] ➔ [ Phase & Item Weight ]
                                               ↓
[ Daily & Qty Logs ] 🏓 [ QC / PM Approval ] ⟪  [ Monthly & Weekly Targets ]
        ↓
[ WPR & Efficiency Dashboard ] ➔ [ Spent vs Budget Calculation ]
```

---

## 🔗 Database Relational Mapping (ER Diagram)

![width:680px center](file:///c:/xampp/htdocs/TPHL-ERP/project_progress_architecture.png)

---

## 📅 ধাপ ১: প্রজেক্ট ইনিশিয়ালাইজেশন ও মাস্টার টাইমলাইন

প্রজেক্টের মূল লাইফসাইকেল এবং ডেট রেঞ্জ ডিফাইন করা হয়।

* **ব্যবহৃত টেবিল:** `projects`
* **কোর কলামসমূহ:** 
  * `id` (INT, Primary Key)
  * `name` (VARCHAR - প্রজেক্টের নাম)
  * `start_date` / `end_date` (DATE - টাইমলাইন উইন্ডো)
  * `handover_target` (VARCHAR - হ্যান্ডওভারের মাস ও বছর)
* **কোর পিএইচপি ফাইলস:**
  * **Model:** `Project.php`
  * **Controller:** `ProjectController.php`

---

## 💰 ধাপ ২: ইনিশিয়াল বাজেট এবং মাস্টার BOQ নির্ধারণ (১/২)

ড্রয়িং অনুযায়ী ফিজিক্যাল কাজের জন্য মাস্টার ভলিউম এবং কস্ট বাজেট তৈরি।

* **ব্যবহৃত টেবিল:** 
  * `initial_estimate_budgets` (মাস্টার বাজেট রেকর্ড)
  * `initial_estimate_items` (মাস্টার মেটেরিয়াল BOQ)
  * `initial_estimate_labors` (মাস্টার লেবার কস্ট)
* **ফরেন কি রিলেশন:**
  * `initial_estimate_items.budget_id` ➔ `initial_estimate_budgets.id` (ON DELETE CASCADE)
  * `initial_estimate_labors.budget_id` ➔ `initial_estimate_budgets.id` (ON DELETE CASCADE)

---

## 💰 ধাপ ২: ইনিশিয়াল বাজেট এবং মাস্টার BOQ নির্ধারণ (২/২)

* **কোর কলামসমূহ:**
  * `initial_estimate_budgets`: `id`, `project_id`, `material_cost_total`, `labor_cost_total`, `total_estimated_cost`, `status` (`draft`, `pending`, `pm_approved`, `approved`)
  * `initial_estimate_items`: `id`, `material_id`, `estimated_qty`, `unit_cost_approx`, `volume_qty`
  * `initial_estimate_labors`: `id`, `rate_per_sqft`, `area_sqft`
* **কোর পিএইচপি ফাইলস:**
  * **Model:** `Con_InitialEstimate.php`
  * **Controller:** `Con_InitialEstimateController.php`

---

## 🗂️ ধাপ ৩: ফেজ প্ল্যানিং এবং মাইক্রো-ওয়েট বন্টন

পুরো বিল্ডিং প্রজেক্টের মোট ১০০% গুরুত্ব বা ওজনকে ফেজ এবং সাব-আইটেমের মধ্যে বন্টন করা।

* **ব্যবহৃত টেবিল:** 
  * `project_phases` (ফেজের সামগ্রিক গুরুত্ব)
  * `project_phase_items` (ফেজের অভ্যন্তরীণ সাব-আইটেমের গুরুত্ব)
* **ফরেন কি রিলেশন:**
  * `project_phase_items.phase_id` ➔ `project_phases.id` (ON DELETE CASCADE)
* **কোর কলামসমূহ:**
  * `project_phases`: `weight_percentage` (যেমন: Structure = 35%)
  * `project_phase_items`: `weight_pct` (ফেজের নিজস্ব ১০০%-এর ভেতর গুরুত্ব)

---

## 🎯 ধাপ ৪: মাসিক ও ৪-সাপ্তাহিক লক্ষ্যমাত্রা নির্ধারণ

প্রতি মাসের নির্ধারিত লক্ষ্যমাত্রাকে সাপ্তাহিক ৪টি ভাগে ভাগ করা।

* **ব্যবহৃত টেবিল:** 
  * `phase_monthly_targets` (মাসিক টার্গেটের মাস্টার হেডার)
  * `phase_monthly_target_items` (আইটেম অনুযায়ী সাপ্তাহিক পরিমাণের বন্টন)
* **ফরেন কি রিলেশন:**
  * `phase_monthly_targets.phase_id` ➔ `project_phases.id` (ON DELETE RESTRICT)
  * `phase_monthly_target_items.target_id` ➔ `phase_monthly_targets.id` (ON DELETE CASCADE)
* **কোর কলামসমূহ:**
  * `target_month` (DATE), `monthly_target_qty` (DECIMAL), `week_1_qty`, `week_2_qty`, `week_3_qty`, `week_4_qty`

---

## 📝 ধাপ ৫: দৈনিক সাইট রিপোর্ট ও প্রগ্রেস সেশন (১/২)

সাইট ইঞ্জিনিয়ার কর্তৃক দৈনিক প্রগ্রেস ট্র্যাকিং।

* **ব্যবহৃত টেবিল:** 
  * `phase_weekly_progress` (সাপ্তাহিক প্রগ্রেস সেশন)
  * `phase_weekly_progress_items` (সাপ্তাহিক আইটেম অনুযায়ী পরিমাপ)
* **ফরেন কি রিলেশন:**
  * `phase_weekly_progress.target_id` ➔ `phase_monthly_targets.id`
  * `phase_weekly_progress_items.weekly_progress_id` ➔ `phase_weekly_progress.id`
* **কোর কলামসমূহ:**
  * `entry_status` (`Draft`, `Submitted`, `Approved`), `qc_status`, `pm_status`

---

## 📝 ধাপ ৫: দৈনিক সাইট রিপোর্ট ও প্রগ্রেস সেশন (২/২)

প্রতিদিনের সাইটের রিসোর্স, ব্যবহার ও কাজের বাস্তব অগ্রগতি ধারণকারী টেবিল।

* **ব্যবহৃত টেবিল:** 
  * `daily_progress_logs` (প্রতিদিনের রিসোর্স, আবহাওয়া ও সাইট লগ)
  * `daily_work_item_logs` (প্রতিদিনের সুনির্দিষ্ট কাজের অগ্রগতি ভলিউম)
* **ফরেন কি রিলেশন:**
  * `daily_progress_logs.weekly_progress_id` ➔ `phase_weekly_progress.id`
  * `daily_work_item_logs.daily_log_id` ➔ `daily_progress_logs.id` (ON DELETE CASCADE)
  * `daily_work_item_logs.target_item_id` ➔ `phase_monthly_target_items.id`
* **কোর কলামসমূহ:** `qty_done_today`, `labour_count`, `cement_bags`, `concrete_cft`

---

## 📊 ধাপ ৬: প্রগ্রেস ও পারফরম্যান্স রিপোর্টিং লজিক

সিস্টেমে দুটি সম্পূর্ণ আলাদা ট্র্যাকে রিয়েল-টাইম পারসেন্টেজ ক্যালকুলেট করা হয়:

1. **মাসিক লেভেল এফিসিয়েন্সি (Monthly Efficiency):**
   `Monthly Efficiency % = (Achieved Qty / Target Qty) * 100`
2. **সামগ্রিক প্রজেক্ট প্রগ্রেস (Overall Progress Roll-up):**
   `Phase Progress = Sum( (Item Achieved / Total Master BOQ) * weight_pct )`
   `Overall Actual = Phase Actual * Phase Weight %`

---

## ⚠️ ধাপ ৭: ডিলে গ্যাপ এবং ঝুঁকি ব্যবস্থাপনা

পরিকল্পিত প্রগ্রেস এবং প্রকৃত প্রগ্রেসের বিয়োগফল থেকে DELAY GAP এবং RISK STATUS নির্ধারণ করে।

* **গাণিতিক সূত্র:**
  `Delay Gap = Actual Progress - Planned Progress`
* **রিস্ক ম্যাট্রিক্স লজিক (Automated Risk Engine):**
  * `Gap >= 0%` ➔ **Low Risk / On Track** 🟢
  * `Gap = -1% to -5%` ➔ **Medium Risk / At Risk** 🟡
  * `Gap < -5%` ➔ **High Risk / Delayed** 🔴

---

## 💰 ধাপ ৮: মেটেরিয়াল রিসিভিং ও কন্ট্রাক্টর বিলের মাধ্যমে বাজেট ইউসেজ

ড্যাশবোর্ডের `BUDGET USED` কার্ড সরাসরি নিচের সোর্স থেকে রিয়েল-টাইমে খরচ ট্র্যাক করে:

* **মেটেরিয়াল খরচ (Material Spent):**
  `Material Spent = Sum( received_quantity * unit_price ) [QC/PM Approved GRN]`
* **ঠিকাদার খরচ (Contractor Spent):**
  `Contractor Spent = Sum( approved_amount ) [Approved Bills]`
* **বাজেট ইউসেজ পারসেন্টেজ:**
  `Budget Used % = ( (Material Spent + Contractor Spent) / Approved Budget ) * 100`

---

## 🛡️ ডেটাবেস রিলেশন ও সিস্টেম সেফটি রুলস

* **Zero-Division Protection:**
  কোনো ফেজের যদি বাজেট বা আইটেম না থাকে (`total_boq_qty` = 0), তবে সিস্টেম ক্র্যাশ না করে সেটিকে `0.0%` হিসেবে হ্যান্ডেল করে।
* **UI/UX Transparency (Popovers):**
  প্রতিটি ডাইনামিক সংখ্যার পাশে হভার করলে গাণিতিক সূত্রগুলো লাইভ ভ্যালু সহ বাংলায় প্রদর্শিত হয়।
* **ডিলিট রেস্ট্রিকশন:**
  কোনো ফেজ ডিলিট করতে চাইলে ডাটাবেস চেক করে তার সাথে সম্পর্কিত কোনো টার্গেট বা প্রগ্রেস ডাটা আছে কি না। থাকলে ডিলিট করতে বাধা দেওয়া হয়।
* **ফরেন কি সেফটি:**
  `phase_monthly_targets.phase_id` ➔ `project_phases.id` রিলেশনে `ON DELETE RESTRICT` ব্যবহার করা হয়েছে।





## Risk Dashboard system 
Implementation Plan: Proactive Advanced Risk Tracking
এই প্ল্যানে TPHL-ERP প্রজেক্ট ম্যানেজমেন্টের রিস্ক ড্যাশবোর্ড এবং ওভারভিউ ড্যাশবোর্ডে লাইভ ও প্রোঅ্যাক্টিভ (অগ্রিম সতর্কতা) রিস্ক ট্র্যাকিং ফিচার যুক্ত করার নকশা করা হলো।

User Review Required
IMPORTANT

রিস্ক ট্র্যাকিংয়ের এডভান্সড নিয়মাভলি: ১. Not Started Alert: কোনো সপ্তাহের কাজ শুরু হওয়ার পর যদি প্রগ্রেস 0% থাকে, তবে সেটি রিস্ক হিসেবে চিহ্নিত হবে। ২. Run-Rate/Burn-Rate Alert: চলমান সপ্তাহে কাজের প্রত্যাশিত দৈনিক গতির চেয়ে একচুয়াল গতি কম হলে (Expected Run-rate-এর ৭৫%-এর নিচে) কাজ শেষ হওয়ার আগেই রিস্ক দেখাবে। ৩. Missing Daily Log Alert: চলমান বা পূর্বের সপ্তাহে কোনো দিন পার হয়ে গেছে কিন্তু ঐ নির্দিষ্ট কাজের বিপরীতে কোনো ডেইলি প্রগ্রেস রিপোর্ট বা কোয়ান্টিটি এন্ট্রি করা হয়নি—এমন প্রতিটি দিনের জন্য আলাদা রিস্ক ফ্ল্যাগ করা হবে। ৪. Self-Healing: সাইটে প্রগ্রেস এন্ট্রি বা কাজ সম্পন্ন হওয়া মাত্রই অটো-রিস্কগুলো ড্যাশবোর্ড ও ডেটাবেস থেকে স্বয়ংক্রিয়ভাবে মুছে যাবে।

Proposed Changes
1. Risk Dashboard Backend & Logic
[MODIFY] 
Con_RiskDashboardController.php
detectAndSyncProjectRisks($projectId) নামক একটি নতুন পাবলিক মেথড তৈরি করা হবে যা রিস্ক ডিটেকশন ও ডেটাবেস সিঙ্ক করার কাজ করবে।
সংশোধিত রিস্ক চেকিং লজিকসমূহ:
Week Target Missed: সপ্তাহ পার হওয়ার পর টার্গেট শর্টফল থাকলে। (কোড: AUTO-MISSED-W[weekNo]-[item_id])
Not Started: সপ্তাহ শুরু হওয়ার পরও অগ্রগতি শূন্য থাকলে। (কোড: AUTO-NOTSTARTED-W[weekNo]-[item_id])
Slow Run-Rate: কাজ চলাকালীন প্রত্যাশিত গতির চেয়ে গতি কম থাকলে। (কোড: AUTO-BURNRATE-W[weekNo]-[item_id])
Missing Daily Log: পূর্বে পার হয়ে যাওয়া দিনে কোনো কোয়ান্টিটি এন্ট্রি বা প্রগ্রেস রিপোর্ট না থাকলে। (কোড: AUTO-NOLOG-D[Date]-[item_id])
অটো-রিস্ক সিঙ্কের সময় যে রিস্কগুলো আর সক্রিয় নেই (যেমন- কাজ সম্পন্ন হয়েছে বা পরবর্তী তারিখ আপডেট করা হয়েছে), সেগুলো project_risks থেকে ডিলেট করা হবে।
index($projectId) মেথডে এই নতুন সিঙ্ক ফাংশনটি কল করা হবে।
2. Project Portfolio Overview Dashboard


[MODIFY] 
Con_ProjectOverviewDashboardController.php
ওভারভিউ ড্যাশবোর্ডের index() মেথডে পোর্টফোলিও প্রজেক্টগুলোর জন্য Con_RiskDashboardController ক্লাসের নতুন সিঙ্ক মেথডটি কল করা হবে। এর ফলে প্রজেক্ট ওভারভিউ ড্যাশবোর্ড লোড হওয়ার সাথে সাথে সমস্ত ঝুঁকি লাইভ আপডেট হয়ে যাবে।
Verification Plan
Automated & Manual Verification
১. সাপ্তাহিক প্রগ্রেস পরীক্ষা: একটি প্রজেক্টে টার্গেট সেট করে প্রগ্রেস ০% রাখা হবে এবং ড্যাশবোর্ডে "Not Started" রিস্কটি দেখাচ্ছে কিনা তা পরীক্ষা করা হবে। ২. পাসড ডেট এন্ট্রি মিসিং পরীক্ষা: চলমান সপ্তাহে আজকের আগের ডেটগুলোতে কোনো প্রগ্রেস এন্ট্রি না দিয়ে ড্যাশবোর্ড চেক করা হবে। ড্যাশবোর্ডে "No progress logged for [Item] on [Date]" রিস্কটি জেনারেট হচ্ছে কিনা তা ভেরিফাই করা হবে। ৩. রান-রেট পরীক্ষা: টার্গেটের আংশিক প্রগ্রেস এন্ট্রি (যেমন- ২৫% রান-রেট) দিয়ে "Slow Progress / Burn-rate" রিস্ক ট্র্রিগার হচ্ছে কিনা চেক করা হবে। ৪. সেলফ-হিলিং পরীক্ষা: সাইটে আংশিক বা সম্পূর্ণ কাজ এন্ট্রি করে ড্যাশবোর্ড রিফ্রেশ করা হবে এবং দেখা হবে যে পূর্বে তৈরি হওয়া অটো-রিস্কগুলো ডেটাবেস ও স্ক্রিন থেকে হাওয়া (মুছে) গেছে কিনা।



###### All project Overview te delay ####
আমরা এখন প্রজেক্টের Delay (বিলম্ব) প্রধানত দুটি বিষয়ের সমন্বয়ে হিসাব করছি:

১. অগ্রগতির ব্যবধান (Progress Gap)
প্রথমে প্রজেক্টের প্ল্যানড প্রোগ্রেস (Planned Progress) এবং অ্যাকচুয়াল প্রোগ্রেস (Actual Progress)-এর মধ্যকার ব্যবধান বের করা হয়: $$\text{Gap} = \text{Planned Progress %} - \text{Actual Progress %}$$

যদি এই ব্যবধান ১৫% এর বেশি (Gap > 15%) হয়, তবে প্রজেক্টটিকে সরাসরি "Delayed" হিসেবে গণনা করা হয়।
উদাহরণস্বরূপ: প্ল্যানড প্রোগ্রেস যদি ৫০% হয় এবং অ্যাকচুয়াল প্রোগ্রেস যদি ৩০% হয় (ব্যবধান ২০%), তবে প্রজেক্টটি সরাসরি "Delayed" দেখাবে।
২. ওপেন রিস্কের তীব্রতা (Open Risks Severity)
অগ্রগতির ব্যবধান ১৫% এর কম হলেও, প্রজেক্টে যদি কোনো অতি-গুরুত্বপূর্ণ কাজ বন্ধ থাকে বা বড় ঘাটতি থাকে, তবে রিস্কের তীব্রতা অনুযায়ী প্রজেক্টটিকে "Delayed" করা হয়:

প্রজেক্টের আন্ডারে যদি কোনো High বা Critical তীব্রতার ওপেন রিস্ক থাকে, তবে ব্যবধান যাই হোক না কেন প্রজেক্টটির স্ট্যাটাস ডাউনগ্রেড হয়ে সরাসরি "Delayed" হয়ে যাবে।
কীভাবে এই High/Critical রিস্কগুলো তৈরি হয়?

Not Started Target (কাজ শুরু না হওয়া): চলতি সপ্তাহের প্ল্যান করা কাজের তারিখ শুরু হয়ে গেছে কিন্তু ১ ইউনিট কাজও শুরু হয়নি (Achieved Qty = 0)। এটি সরাসরি High সেভারিটির রিস্ক হিসেবে কাউন্ট হয়। তাই কাজ শুরু না করলেই প্রজেক্টটি "Delayed" দেখাবে।
Critical Burn-rate (অতি ধীর গতি): একটি সপ্তাহে যত দিন পার হয়েছে, সেই তুলনায় কাজের গতি যদি প্রত্যাশিত পরিমাণের ৪০% এর চেয়েও কম হয়, তবে সেটি High সেভারিটির রিস্ক হিসেবে চিহ্নিত হয় এবং প্রজেক্টটি "Delayed" দেখাবে।
Missed Weekly Target (সাপ্তাহিক লক্ষ্যমাত্রা ব্যর্থ): সপ্তাহ শেষ হওয়ার পর যদি লক্ষ্যমাত্রার ৫০% এর বেশি ঘাটতি থাকে, তবে সেটি High সেভারিটির রিস্ক হয়ে প্রজেক্টটিকে "Delayed" করবে।