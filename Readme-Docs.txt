title Construction Project Management Workflow
autoNumber nested

// Define actors in order
Project Manager [icon: user, color: blue]
Site Engineer [icon: hard-hat, color: blue]
Quality Controller [icon: check-circle, color: teal]
Architect [icon: pen-tool, color: purple]
Electrical Designer [icon: zap, color: purple]
Plumbing Designer [icon: droplet, color: purple]
Structural Designer [icon: box, color: purple]
Estimator [icon: calculator, color: green]
Purchase Head [icon: shopping-cart, color: orange]
Vendor [icon: truck, color: orange]
Contractor [icon: tool, color: red]
Finance [icon: dollar-sign, color: green]
Management [icon: briefcase, color: gold]

// Phase 1: Planning and Design
activate Project Manager
Project Manager > Site Engineer: Initiate land visibility study
Site Engineer > Site Engineer: Conduct survey and collect documents
Site Engineer > Quality Controller: Submit study for quality review
Quality Controller > Quality Controller: Review study documents
alt [label: Study approved, icon: check] {
  Quality Controller > Site Engineer: Approve study
  Site Engineer > Project Manager: Submit study report
}
else [label: Study needs revision, icon: edit] {
  Quality Controller > Site Engineer: Return for corrections
  Site Engineer > Site Engineer: Revise study documents
  Site Engineer > Quality Controller: Resubmit study
}

Project Manager > Architect: Request architecture design
Architect > Project Manager: Submit design drawings

par [label: Site assessments, icon: layers] {
  Site Engineer > Site Engineer: Conduct soil testing
  Architect > Project Manager: Submit project drawings
}

// Phase 1.5: Specialized Design Phase
par [label: Specialized designs, icon: grid] {
  Project Manager > Electrical Designer: Request electrical design
  Electrical Designer > Electrical Designer: Prepare electrical layout
  Electrical Designer > Project Manager: Submit electrical design
}
and {
  Project Manager > Plumbing Designer: Request plumbing design
  Plumbing Designer > Plumbing Designer: Prepare plumbing layout
  Plumbing Designer > Project Manager: Submit plumbing design
}
and {
  Project Manager > Structural Designer: Request structural design
  Structural Designer > Structural Designer: Prepare structural calculations
  Structural Designer > Project Manager: Submit structural design
}

Project Manager > Project Manager: Review all design submissions

// Phase 2: Budgeting with Management Pre-Approval
Project Manager > Management: Request approval to initiate budgeting
alt [label: Budgeting approved, icon: check] {
  Management > Project Manager: Approve budget initiation
  Project Manager > Estimator: Request project budget with all designs
  activate Estimator
  Estimator > Estimator: Review architectural design
  Estimator > Estimator: Review electrical design
  Estimator > Estimator: Review plumbing design
  Estimator > Estimator: Review structural design
  Estimator > Estimator: Prepare comprehensive material estimates
  Estimator > Project Manager: Submit budget for approval
  Project Manager > Management: Submit budget for final approval
  
  alt [label: Budget approved, icon: check] {
    Management > Project Manager: Approve budget
    Project Manager > Estimator: Confirm budget approval
    deactivate Estimator
  }
  else [label: Budget needs revision, icon: edit] {
    Management > Project Manager: Request budget revisions
    Project Manager > Estimator: Request revisions
    Estimator > Project Manager: Resubmit revised budget
  }
}
else [label: Budgeting not approved, icon: x] {
  Management > Project Manager: Reject budget initiation
}

// Phase 3: Target Setting and Procurement
Project Manager > Site Engineer: Define project targets
activate Site Engineer
Site Engineer > Site Engineer: Analyze resource requirements
Site Engineer > Project Manager: Submit target plan

// Monthly target requires QC approval
Project Manager > Quality Controller: Submit monthly target for review
activate Quality Controller
Quality Controller > Quality Controller: Review monthly target plan
alt [label: Monthly target approved, icon: check] {
  Quality Controller > Project Manager: Approve monthly target
}
else [label: Monthly target needs revision, icon: edit] {
  Quality Controller > Project Manager: Return target for revision
  Project Manager > Site Engineer: Revise target plan
  Site Engineer > Project Manager: Resubmit target plan
  Project Manager > Quality Controller: Resubmit for approval
}

Project Manager > Management: Request target approval
alt [label: Targets approved, icon: target] {
  Management > Project Manager: Approve project targets
  Project Manager > Purchase Head: Create purchase request based on targets
  activate Purchase Head
  Purchase Head > Vendor: Request quotations
  
  loop [label: Collect vendor quotes, icon: clipboard] {
    Vendor > Purchase Head: Submit quotation
  }
  
  Purchase Head > Purchase Head: Evaluate quotations
  Purchase Head > Finance: Submit selected vendor for review
  activate Finance
  Finance > Finance: Verify budget availability
  Finance > Management: Submit vendor approval request
  
  alt [label: Vendor approved, icon: check-square] {
    Management > Finance: Approve purchase order
    Finance > Purchase Head: Confirm approval
    Purchase Head > Vendor: Issue final purchase order
  }
  else [label: Vendor rejected, icon: x] {
    Management > Finance: Reject and request alternatives
    Finance > Purchase Head: Request alternative vendors
  }
}
else [label: Targets need revision, icon: edit] {
  Management > Project Manager: Request target revisions
  Project Manager > Site Engineer: Revise targets
}

// Phase 4: Operations
Vendor > Site Engineer: Deliver materials
Site Engineer > Site Engineer: Inspect and verify delivery
Site Engineer > Quality Controller: Submit materials for quality check
Quality Controller > Quality Controller: Inspect material quality

alt [label: Materials approved, icon: check] {
  Quality Controller > Site Engineer: Approve materials
  Site Engineer > Site Engineer: Accept delivery
}
else [label: Materials rejected, icon: x] {
  Quality Controller > Site Engineer: Reject materials
  opt [label: Quality issues found, icon: alert-triangle] {
    Site Engineer > Purchase Head: Report penalty issue
    Purchase Head > Vendor: Issue penalty notice
  }
}

Site Engineer > Project Manager: Set weekly targets
Project Manager > Management: Submit progress for approval

// Phase 4.5: Task Completion Approval
Site Engineer > Site Engineer: Complete assigned task
Site Engineer > Quality Controller: Submit task for quality verification
Quality Controller > Quality Controller: Verify task completion quality

alt [label: Task quality approved, icon: check] {
  Quality Controller > Site Engineer: Approve task quality
  Site Engineer > Project Manager: Report task completion
  Project Manager > Management: Request task completion approval
  
  alt [label: Task approved by management, icon: check] {
    Management > Project Manager: Approve task completion
    Project Manager > Site Engineer: Confirm task closure
  }
  else [label: Task needs review, icon: edit] {
    Management > Project Manager: Request additional verification
    Project Manager > Quality Controller: Request re-verification
  }
}
else [label: Task quality rejected, icon: x] {
  Quality Controller > Site Engineer: Return task for rework
  Site Engineer > Site Engineer: Perform rework
  Site Engineer > Quality Controller: Resubmit for verification
}

deactivate Quality Controller

// Phase 4.6: Contractor Payment Request
activate Contractor
Contractor > Finance: Submit payment request for work completed
Finance > Finance: Verify work completion
Finance > Management: Request contractor payment approval

alt [label: Contractor payment approved, icon: check] {
  Management > Finance: Approve contractor payment
  Finance > Contractor: Process contractor payment
  Finance --> Contractor: Confirm payment complete
}
else [label: Contractor payment on hold, icon: pause] {
  Management > Finance: Hold pending review
  Finance --> Contractor: Notify payment pending
}

deactivate Contractor
deactivate Site Engineer

// Phase 5: Vendor Payment
Purchase Head > Finance: Submit vendor payment request
Finance > Finance: Verify delivery and invoice
Finance > Management: Request vendor payment approval

alt [label: Payment approved, icon: credit-card] {
  Management > Finance: Approve payment
  Finance > Vendor: Process payment
  Finance --> Purchase Head: Confirm transaction complete
}
else [label: Payment on hold, icon: pause] {
  Management > Finance: Hold pending review
  Finance --> Purchase Head: Notify payment pending
}
also need inventory manage like in Readme.md file has tables for material inventroy
Also Contructor bill request and payment approval process by tables

deactivate Finance
deactivate Purchase Head
deactivate Project Manager          

# 🏗️ Construction ERP System – Phase 2 Documentation

---

# 🎯 PURPOSE

এই ERP system এর উদ্দেশ্য:

* Project planning থেকে শুরু করে construction, procurement, inventory, QC, finance — সব এক জায়গায় manage করা
* Internal team দ্বারা controlled workflow (Contractor/Vendor login নেই)
* Approval-based secure process

---

# 🧩 MODULE OVERVIEW

1. Project & Planning
2. BOQ & Budgeting
3. Procurement (PR → CST → PO)
4. Inventory & Material Receiving
5. Operations & Labour
6. Vendor Management (Penalty + Rating)
7. Finance & Payment
8. Approval System

---

# 🟡 MODULE 1: PROJECT & PLANNING

## 🔄 FLOW

Project Create
→ Land Visibility Study
→ Soil Test
→ Design Upload
→ Drawing Approval

## ✅ TABLES

* projects
* land_visibility_study
* soil_tests
* project_drawings
* design_types

## 🔐 APPROVAL FLOW

```text
Drawing তৈরি → Submitted
→ Approved by Architect / Manager
→ Status = Approved
```

---

# 🟢 MODULE 2: BOQ & BUDGET

## 🔄 FLOW

BOQ Create
→ Material + Quantity define
→ Cost estimation
→ Approval

## 📊 WORKING PLAN

BOQ → Monthly plan তৈরি

```text
BOQ → project_working_plan
```

## 💰 BUDGET EXTEND

```text
IF budget exceed
→ budget_extend_requests
→ Approval required
```

## 🔐 APPROVAL

```text
Draft → Submitted → Approved → Active
```

---

# 🟠 MODULE 3: PROCUREMENT

## 🔄 FULL FLOW

```text
Material Need
→ Purchase Request (PR)

PR → Quotation (Vendor wise)

Executive:
→ CST (comparison তৈরি)

Purchase Head:
→ Vendor select (single / multiple)

→ Purchase Order (PO)
```

---

## 🎯 CST LOGIC

* Item-wise vendor selection
* Multiple vendor allowed

---

## 🔐 APPROVAL FLOW

```text
PR → Approved

Quotation → Verified

CST → Approved by Purchase Head

PO → Final Approval
```

---

# 🔵 MODULE 4: INVENTORY & RECEIVING

## 🔄 FLOW

```text
PO → Material আসে

Store / Engineer:
→ Receiving Entry

QC / Engineer:
→ Check
```

---

## 📦 RECEIVING STATUS

* Full
* Partial
* Rejected

---

## 📊 RULE

```text
Received Quantity → Stock add হবে

Rejected → Stock add হবে না
Partial → Only received qty add হবে
```

---

## 🔐 QC APPROVAL

```text
Engineer ✔ OR QC ✔ → Valid

(Option: both required)
```

---

# 🔴 MODULE 5: OPERATIONS & LABOUR

## 🔄 FLOW

```text
Site Work শুরু

→ Target Set (weekly/monthly)
→ Daily Progress Update
→ Material Usage
```

---

## 👷 CONTRACTOR FLOW

```text
Contractor কাজ করে
→ Operation Head informed

Operation Head:
→ Fund Request create
```

---

## 💸 LABOUR PAYMENT FLOW

```text
operational_fund_requests
→ Approved
→ Payment
→ Contractor → Labour payment
```

---

## 🔐 APPROVAL

```text
Request → Manager → Accounts → Paid
```

---

# 🟣 MODULE 6: VENDOR MANAGEMENT

## 🔄 FLOW

```text
Material Receiving

IF Bad Quality:
→ Vendor Penalty

→ Vendor Rating update
```

---

## 📊 PENALTY TYPES

* Quality Issue
* Delay
* Quantity Short

---

## ⭐ RATING LOGIC

```text
Default = 5

Penalty → rating decrease
```

---

# 🟡 MODULE 7: FINANCE & PAYMENT

---

## 💰 VENDOR PAYMENT

```text
Purchase Head:
→ Payment Request create

Conditions:
✔ Delivery Verified
✔ QC Verified

→ Approved
→ Payment
```

---

## 🔴 CONTRACTOR PAYMENT

```text
Operation Head:
→ Fund Request

→ Approved
→ Payment
```

---

## 💳 TRANSACTION FLOW

```text
payment_requests
→ financial_transactions
```

---

# 🔐 MODULE 8: APPROVAL SYSTEM (GLOBAL)

---

## 🎯 APPROVAL LEVEL

| Step | Role      |
| ---- | --------- |
| 1    | Executive |
| 2    | Head      |
| 3    | Manager   |
| 4    | Accounts  |

---

## 🔄 GENERIC FLOW

```text
Draft
→ Submitted
→ Verified
→ Approved
→ Completed
```

---

# 🚨 GLOBAL BUSINESS RULES

---

## 📌 PROCUREMENT

* Approved PR ছাড়া PO না
* CST ছাড়া vendor select না

---

## 📌 INVENTORY

* Stock ছাড়া issue না
* QC fail হলে payment block

---

## 📌 FINANCE

* Approval ছাড়া payment না
* Fund request ছাড়া labour payment না

---

## 📌 QUALITY

* Rejected material → stock না
* Penalty mandatory for issue

---

# 🔗 MASTER FLOW (FINAL)

```text
Project
→ BOQ
→ Working Plan
→ PR
→ CST
→ PO
→ Receiving
→ QC
→ Stock

→ Issue to Site
→ Work Execution

→ Contractor Fund Request
→ Vendor Payment

→ Finance → Transaction
```

---

# 🎯 FINAL SUMMARY

এই ERP system দিয়ে তুমি পাবে:

✔ Full construction lifecycle control
✔ Internal approval based system
✔ Multi-vendor procurement
✔ Inventory + QC integration
✔ Contractor & labour payment tracking
✔ Vendor performance monitoring

---

# 🚀 READY FOR IMPLEMENTATION

Next Step:

* Backend API
* Role Permission
* Dashboard UI
* Notification System

---


অপারেশন হেড হিসেবে একটি রিয়েল এস্টেট কোম্পানির পুরো প্রসেসটি কন্ট্রোল করা এবং আপনার দেওয়া টেবিলগুলোর মধ্যে লজিক্যাল চেইন বজায় রাখার জন্য আমি একটি "End-to-End Operational Workflow" সাজিয়ে দিচ্ছি।

আপনার চাহিদা অনুযায়ী Working Plan থেকে শুরু করে Target Fixed, Material Issue, এবং Payment পর্যন্ত সবকিছুর সঠিক ফ্লো নিচে দেওয়া হলো:

১. অপারেশন্স মাস্টার ফ্লো (Step-by-Step Process)
ধাপ ১: মাস্টার শিডিউলিং (Yearly & Monthly Plan)
টেবিল: project_working_plans

কাজ: প্রজেক্টের শুরুতে initial_estimate_budgets এর ওপর ভিত্তি করে পুরো বছরকে মাসে ভাগ করা হয়। এখানে work_description এ লেখা থাকবে কোন মাসে কী বড় কাজ (যেমন: জানুয়ারি-ফেব্রুয়ারি ২০২৬: পাইলিং)।

কন্ট্রোল: এখান থেকে সিস্টেম জানবে এই মাসে আপনার কত টাকা খরচ করার অনুমতি আছে।

ধাপ ২: মাইক্রো-টার্গেট ফিক্সিং (Weekly Targets)
টেবিল: target_fixed

কাজ: মাসের শুরুতে অপারেশন হেড বা প্রজেক্ট ম্যানেজার ওই মাসের কাজকে ৪টি সপ্তাহে ভাগ করবেন।

লজিক: week_number অনুযায়ী নির্দিষ্ট টার্গেট পারসেন্টেজ (যেমন: ১ম সপ্তাহে ২০%) সেট করা হবে। এটি আপনার work_plan_id এর সাথে সরাসরি লিঙ্ক থাকবে।

ধাপ ৩: মেটেরিয়াল রিকোয়েস্ট ও প্রকিউরমেন্ট
টেবিল: purchase_requests -> purchase_orders -> material_receiving

কাজ: টার্গেট পূরণ করতে মালামাল লাগবে। সাইট থেকে PR আসবে, যা আপনার project_working_plans_id এর সাথে ট্যাগ করা থাকবে। মাল রিসিভ হলে সরাসরি con_stocks এ ঢুকবে।

ধাপ ৪: মেটেরিয়াল ইস্যু (Consumption)
টেবিল: con_material_issues & con_material_issue_items

কাজ: টার্গেট অনুযায়ী কাজ শুরু হলে গুদাম থেকে মাল বের হবে।

লজিক: এখানে material_id এবং quantity ইনপুট দিলে con_stocks থেকে মাল কমে যাবে। এটি টার্গেটের সাথে মেলালে বোঝা যাবে কতটুকু মাল ব্যবহারের বিপরীতে কতটুকু কাজ সম্পন্ন হয়েছে।

ধাপ ৫: ডেইলি প্রোগ্রেস ও পেমেন্ট রিকোয়েস্ট
টেবিল: daily_progress_reports & contractor_bill_requests

কাজ: প্রতিদিনের কাজ শেষে DPR সাবমিট হবে। মাস শেষে বা নির্দিষ্ট কাজ শেষে কন্ট্রাক্টর বিল সাবমিট করবে।

কন্ট্রোল: অপারেশন হেড বিল চেক করবেন gain_percentage দেখে। যদি টার্গেট ছিল ৮০% আর অর্জন হয়েছে ৬০%, তবে বিল সেই অনুযায়ী অ্যাডজাস্ট হবে।

২. অপারেশনাল চেইন ও ডাটাবেজ ইন্টিগ্রেশন ডায়াগ্রাম
৩. আপনার সিস্টেমে যা যা এখনো বাকি আছে (Critical Gaps)
অপারেশন হেড হিসেবে আপনি নিখুঁতভাবে সব কন্ট্রোল করতে চাইলে নিচের টেবিল বা কলামগুলো প্রয়োজন:

ক) con_material_issue_items এ টার্গেট রেফারেন্স
মেটেরিয়াল ইস্যু করার সময় সেটি কোন টার্গেটের জন্য নেওয়া হচ্ছে তা ট্র্যাক করার জন্য একটি target_id কলাম থাকা উচিত।

কেন: তাহলে আপনি দেখতে পারবেন: "এই সপ্তাহের ২০% টার্গেটের জন্য ৫০০ ব্যাগ সিমেন্ট নেওয়ার কথা ছিল, কিন্তু কেন ৭০০ ব্যাগ নেওয়া হলো?"

খ) contractor_bill_requests এ টার্গেট ভেরিফিকেশন
বিলের টেবিলে টার্গেটের একটা যোগসূত্র থাকতে হবে।

প্রস্তাবিত কলাম: target_id অথবা work_plan_id বিল রিকোয়েস্ট টেবিলে যোগ করুন।

গ) vendor_payment_requests এর সাথে পেনাল্টি কানেকশন
ভেন্ডরকে পেমেন্ট দেওয়ার আগে vendor_penalties চেক করার একটি অটোমেটেড সিস্টেম লাগবে। পেমেন্ট রিকোয়েস্ট টেবিলে total_penalty_deduction নামক একটি কলাম রাখা ভালো।

৪. অপারেশনাল লজিক চেকিস্ট (Rules of Thumb)
Stock Validation: con_material_issues টেবিলে ডাটা সেভ করার আগে চেক করতে হবে con_stocks এ পর্যাপ্ত ব্যালেন্স আছে কি না।

Budget Lock: purchase_requests এ মালামাল কেনার আগে চেক করতে হবে সেটি project_working_plans এর ওই মাসের বাজেটের ভেতরে আছে কি না।

Payment Lock: কন্ট্রাক্টর বিল অ্যাপ্রুভ করার আগে daily_progress_reports এর গড় প্রোগ্রেস টার্গেটের সাথে মিলছে কি না তা অটোমেটিক ড্যাশবোর্ডে দেখাতে হবে।

৫. পরবর্তী পদক্ষেপ (Next Step for Claude)
আপনি ক্লাউডকে বলতে পারেন:

"Now implement the Daily Progress vs Weekly Target reporting logic. Ensure that when a contractor submits a bill (contractor_bill_requests), the system automatically fetches the gain_percentage from the target_fixed table for the respective period to verify the work done."

এই প্রসেসটি ফলো করলে আপনার কোম্পানিতে কোনো টাকা বা মালামাল নয়ছয় হওয়ার সুযোগ থাকবে না। আপনি কি এখন বিলিং এবং পেমেন্ট মডিউল নিয়ে কাজ শুরু করতে চান?