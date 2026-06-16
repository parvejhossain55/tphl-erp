১. পর্যায়ভিত্তিক কর্মপদ্ধতি ও চেইনিং (Workflow & Chaining)
ERP সিস্টেমে একটি কাজের পর আরেকটি কাজ চেইন আকারে নিম্নোক্ত উপায়ে অগ্রসর হয়:

mermaid
graph TD
    A[Project Timeline & Floor Setup] --> B[Initial Estimate Budget]
    B --> C[Phase Plan & Phase Items]
    C --> D[Monthly Targets with Weekly Qty]
    D --> E[BOQ Setup & Purchase Cycle]
    E --> F[Material Issue]
    D --> G[Daily & Weekly Progress Entry]
    F --> G
    G --> H[QC & PM Approval]
২. বিস্তারিত ধাপ, অনুমোদনকারী ও ডাটাবেজ টেবিলসমূহ
ধাপ ১: প্রজেক্ট টাইমলাইন এবং ফ্লোর সেটআপ (Project Timeline & Floor Setup)
প্রক্রিয়া: প্রথমে প্রজেক্টের প্রজেক্ট কোড, টাইপ, ক্যাটাগরি, কাজের আনুমানিক সময় নির্ধারণ করে টাইমলাইন চালু করা হয়। এরপর প্রজেক্টের ফ্লোরগুলোর সেটআপ করা হয় (Slab Area, height ইত্যাদি)।
অনুমোদন: PM বা Construction Management টিম এটি সেটআপ করে।
সংশ্লিষ্ট টেবিল:
project_timelines (টাইমলাইন তথ্য)
project_floors (ফ্লোর ভিত্তিক তথ্য ও Slab Area SQFT)
কোড ফাইল:


Con_ProjectTimelineController.php


Con_ProjectTimeline.php
ধাপ ২: ইনিশিয়াল বাজেট/এস্টিমেট তৈরি (Initial Estimate / Budgeting)
প্রক্রিয়া: ড্রয়িং এনালাইসিস করে মেটেরিয়াল কস্ট (Estimated Qty) এবং লেবার কস্ট এস্টিমেট করা হয়।
অনুমোদন (Approval):
প্রথমে Project Manager (PM) এটি রিভিউ করে অনুমোদন করেন (Status: pending $\rightarrow$ pm_approved)।
পরবর্তীতে Management (MD/Owner/Director) চূড়ান্ত অনুমোদন প্রদান করেন (Status: pm_approved $\rightarrow$ approved)। বাজেট অনুমোদিত না হলে কোনো পারচেজ রিকোয়েস্ট (PR) ক্রিয়েট করা যাবে না।
সংশ্লিষ্ট টেবিল:
initial_estimate_budgets (বাজেটের মূল হেডার)
initial_estimate_items (বাজেটের মেটেরিয়াল ও কিউএস আইটেমসমূহ)
initial_estimate_labors (লেবার কস্ট সংক্রান্ত আইটেমসমূহ)
কোড ফাইল:


Con_InitialEstimateController.php


Con_InitialEstimate.php
ধাপ ৩: ফেজ প্ল্যান, ওয়ার্ক টাইপ ও ফেজ আইটেম (Phase Plan, Work Types & Phase Items)
প্রক্রিয়া: প্রজেক্টকে বিভিন্ন ধাপে (যেমন: Substructure, Superstructure) ভাগ করে ফেজ প্ল্যান করা হয়। প্রতিটি ফেজের অধীনে কাজের বিবরণ বা Phase Items (Work Packages) তৈরি করে তাদের Weight Percentage (%) নির্ধারণ করা হয়। Work Types-কে ডিজাইন টাইপ এর সাথে ম্যাপ করা হয়।
অনুমোদন: Project Manager (PM) এই প্ল্যান সাজান।
সংশ্লিষ্ট টেবিল:
project_phases (প্রজেক্ট ফেজসমূহ)
project_phase_items (ফেজের নির্দিষ্ট কাজ ও ওজনের শতকরা হার বা Weight Pct)
work_types (কাজের ধরনসমূহ)
কোড ফাইল:


Con_PhasePlanController.php


Con_ProjectPhase.php


Con_PhaseItemController.php


Con_PhaseItem.php


Con_WorkType.php
ধাপ ৪: মান্থলি টার্গেট ও উইকলি টার্গেট (Monthly & Weekly Target Plans)
প্রক্রিয়া: প্রজেক্ট ম্যানেজার প্রতি মাসের লক্ষ্যমাত্রা বা Monthly Target Qty সেট করেন। এই মান্থলি টার্গেট কোয়ান্টিটিকে ১ম, ২য়, ৩য় ও ৪র্থ সপ্তাহে ভাগ করে Weekly Target Qty নির্ধারণ করা হয়।
অনুমোদন: QC রিভিউ এবং Management/PM অনুমোদন করেন।
সংশ্লিষ্ট টেবিল:
phase_monthly_targets (টার্গেট হেডার - মাস ও বছরের হিসাব)
phase_monthly_target_items (টার্গেট আইটেমসমূহ - যেখানে week_1_qty, week_2_qty, week_3_qty, week_4_qty কলামে উইকলি টার্গেট সেভ থাকে)
কোড ফাইল:


Con_MonthlyTargetController.php


Con_MonthlyTarget.php
ধাপ ৫: বিল অফ কোয়ান্টিটি বা মেটেরিয়াল BOQ (Material BOQ)
প্রক্রিয়া: একটি নির্দিষ্ট টার্গেটের বিপরীতে কি পরিমাণ মালামাল লাগবে তার বিবরণ বা BOQ তৈরি করা হয়।
অনুমোদন (Approval):
সাইট ইঞ্জিনিয়ার বা এস্টিমেটর ড্রাফট তৈরি করে সাবমিট করেন (Status: Submitted)।
PM, PM Head, MD অথবা Director এটি অনুমোদন বা রিজেক্ট করতে পারেন (Status: Approved/Rejected)। তবে নির্মাতা (Creator) নিজে নিজের তৈরি BOQ অনুমোদন করতে পারবেন না।
সংশ্লিষ্ট টেবিল:
boq_master (BOQ হেডার - প্রজেক্ট আইডি ও টার্গেট আইডি এর সাথে লিঙ্ক থাকে)
boq_items (মালামালের আইডি ও কোয়ান্টিটি)
কোড ফাইল:


Con_BOQController.php


Con_BOQ.php
ধাপ ৬: মেটেরিয়াল ইস্যু বা মালামাল প্রদান (Material Issue)
প্রক্রিয়া: টার্গেট অনুযায়ী সাইটের স্টোর বা ওয়্যারহাউজ থেকে কন্ট্রাক্টরের নামে মালামাল ইস্যু করা হয়। মেটেরিয়াল ইস্যুর সময় ডাটাবেজে এন্ট্রি করার সাথে সাথে সিস্টেম অটোমেটিকভাবে con_stocks টেবিল থেকে স্টক কমিয়ে দেয় (Stock OUT)।
অনুমোদন: স্টোর কিপার বা সাইট ইঞ্জিনিয়ার মেটেরিয়াল ইস্যু এন্ট্রি দেন।
সংশ্লিষ্ট টেবিল:
con_material_issues (মেটেরিয়াল ইস্যুর মূল এন্ট্রি - এটি target_id দিয়ে phase_monthly_targets এর সাথে লিঙ্ক থাকে)
con_stocks (সাইটের বর্তমান মেটেরিয়াল স্টক)
con_stock_movements (স্টকের হ্রাস-বৃদ্ধির ট্র্যাকিং বা Log Movement)
কোড ফাইল:


Con_MaterialIssueController.php


Con_MaterialIssue.php
ধাপ ৭: দৈনিক ও সাপ্তাহিক কাজের অগ্রগতি (Daily & Weekly Progress / DPR)
প্রক্রিয়া:
সাইট ইঞ্জিনিয়ার প্রতিদিন সাইটের কাজের পরিমাণ ও রিসোর্স ব্যবহারের উপর ভিত্তি করে Daily Progress Report (DPR) সাবমিট করেন।
সিস্টেম প্রতিদিনের সম্পাদিত কোয়ান্টিটি (qty_done_today) অটোমেটিকভাবে যোগ করে সপ্তাহের মোট অর্জিত পরিমাণ বা achieved_qty এবং সর্বমোট প্রগ্রেস ক্যালকুলেট করে।
অনুমোদন (Approval Flow):
Site Engineer উইকলি রিপোর্ট সাবমিট করেন।
Quality Controller (QC) দৈনিক লগ এবং কাজের গুণমান চেক করে অনুমোদন দেন। সব দিনের লগ এপ্রুভ হলে উইকলি প্রগ্রেসের qc_status হয় Approved।
Project Manager (PM) চূড়ান্ত অনুমোদন দেন। PM যদি কোনো একটি দিনের লগও রিজেক্ট করেন, তবে পুরো উইকলি প্রগ্রেসটি পুনরায় Draft মোডে চলে যায় যাতে সাইট ইঞ্জিনিয়ার ভুল সংশোধন করতে পারেন।
সংশ্লিষ্ট টেবিল:
phase_weekly_progress (উইকলি সেশন মাস্টার টেবিল)
phase_weekly_progress_items (উইকলি সেশনের আইটেম ভিত্তিক প্রগ্রেস)
daily_progress_logs (দৈনিক DPR হেডার - লেবার সংখ্যা, সিমেন্ট ব্যাগ ও কংক্রিট ঢালাইয়ের হিসাব)
daily_work_item_logs (প্রতিদিন কোন আইটেমে কতটুকু কাজ করা হলো তার কোয়ান্টিটি ট্র্যাকিং)
কোড ফাইল:


Con_PhaseWeeklyProgressController.php


Con_PhaseWeeklyProgress.php
৩. উইকলি রিপোর্টে প্রজেক্ট এবং টার্গেটের জন্য মেটেরিয়াল ইস্যুর হিসাব
উইকলি প্রগ্রেস রিপোর্ট পেজে বা উইকলি রিপোর্টে নির্দিষ্ট প্রজেক্ট ও টার্গেটের বিপরীতে কতটুকু মেটেরিয়াল ইস্যু করা হয়েছে তা দেখতে সিস্টেমে নিচের কুয়েরিটি ব্যবহৃত হয়:

sql
SELECT mi.id, mi.quantity, mi.issue_date, mi.purpose,
       m.name AS material_name, m.unit,
       c.name AS contractor_name
FROM con_material_issues mi
LEFT JOIN con_materials m ON m.id = mi.material_id
LEFT JOIN contractors  c ON c.id = mi.issued_to
WHERE mi.target_id = ? -- (Target ID হচ্ছে phase_monthly_targets.id)
ORDER BY mi.issue_date DESC, mi.id DESC
এই মেটেরিয়াল ইস্যুর তথ্যসমূহ সরাসরি 

Con_WeeklyReportController.php
 ফাইলটির show() মেথডে প্রজেক্ট আইডি ও মান্থলি টার্গেট আইডি এর মাধ্যমে লোড করা হয় এবং উইকলি প্রগ্রেস রিপোর্টে প্রদর্শন করা হয়।



 আইটেমের প্রগ্রেস ক্যালকুলেশন (Item Progress %)
অনুমোদিত অর্জিত কোয়ান্টিটি (actual_qty) কে মোট বিওকিউ কোয়ান্টিটি (total_boq_qty) দিয়ে ভাগ করে প্রগ্রেস পারসেন্টেজ (actual_progress_pct) বের করতে:

হিসাবের উপাদান	সংশ্লিষ্ট ডেটাবেজ টেবিল (Table)	কলামের নাম (Column)	কাজের বিবরণ
অনুমোদিত অর্জিত কোয়ান্টিটি	phase_monthly_target_items	actual_qty	অনুমোদিত ডেইলি/উইকলি প্রগ্রেস লগের যোগফল এখানে জমা হয়।
মোট বিওকিউ কোয়ান্টিটি	project_phase_item_details	total_boq_qty	এস্টিমেট/বাজেট থেকে নির্ধারিত মোট কাজের কোয়ান্টিটি।
আইটেমের প্রগ্রেস পারসেন্টেজ	phase_monthly_target_items	actual_progress_pct	কাজ সম্পন্ন হওয়ার শতাংশের হার (যেমন: ৬০.৫%)।
নোট: চূড়ান্ত বা ওভারঅল আইটেম প্রগ্রেস পারসেন্টেজটি project_phase_items টেবিলের actual_progress কলামেও সংরক্ষণ বা সিঙ্ক করা হয়।

২. ফেজের প্রগ্রেস ক্যালকুলেশন (Phase Progress %)
ফেজের অধীনে থাকা প্রতিটি Phase Item-এর অর্জিত শতকরা হারকে তাদের নির্দিষ্ট ওয়েট পারসেন্টেজ (weight_pct) দিয়ে গুণ করে পুরো ফেজের প্রগ্রেস বের করতে:

হিসাবের উপাদান	সংশ্লিষ্ট ডেটাবেজ টেবিল (Table)	কলামের নাম (Column)	কাজের বিবরণ
Phase Item-এর অর্জিত শতকরা হার	project_phase_items	actual_progress	ওই নির্দিষ্ট কাজের বর্তমান কাজের অগ্রগতি (০ থেকে ১০০%)।
আইটেমের ওয়েট পারসেন্টেজ	project_phase_items	weight_pct	পুরো ফেজের কাজের ওজনের মধ্যে এই নির্দিষ্ট আইটেমটির ওজন বা গুরুত্ব কতটুকু।
পুরো ফেজের প্রগ্রেস	project_phases	actual_progress	সব সাব-আইটেমের প্রগ্রেস যোগ করে ফেজের সামগ্রিক অগ্রগতি।
৩. প্রজেক্টের ওভারঅল প্রগ্রেস ক্যালকুলেশন (Overall Project Progress %)
আপনার বোঝার সুবিধার্থে প্রজেক্টের মেইন প্রগ্রেসের টেবিল ও কলামও নিচে যুক্ত করে দেওয়া হলো:

হিসাবের উপাদান	সংশ্লিষ্ট ডেটাবেজ টেবিল (Table)	কলামের নাম (Column)	কাজের বিবরণ
ফেজের সামগ্রিক প্রগ্রেস	project_phases	actual_progress	পূর্বের ধাপে ক্যালকুলেট করা ফেজের অগ্রগতি।
ফেজের ওয়েট পারসেন্টেজ	project_phases	weight_percentage	পুরো প্রজেক্টের মধ্যে ওই ফেজটির অবদান বা ওজন কতটুকু (যেমন: ফাউন্ডেশন ফেজের ওজন হতে পারে ২০%)।
প্রজেক্টের ওভারঅল প্রগ্রেস	projects	actual_progress (বা ড্যাশবোর্ড কুয়েরি)	প্রজেক্টের মেইন বা টোটাল কাজের ওভারঅল প্রগ্রেস (০ থেকে ১০০%)।