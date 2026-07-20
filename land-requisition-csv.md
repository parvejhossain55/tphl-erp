Implementation Plan - Bulk Land Purchase CSV Import
Bulk import historical Land Acquisitions, Requisitions, Bayana Payments, and Registrations from a single CSV file, mapping project names and user emails to their respective IDs in the database.

User Review Required
IMPORTANT

Emails and Project Names Lookup: In the CSV, project names must match the names in the ERP, and user emails (land_dept_email, management_email, account_verify_email) must exist in the users table to map correctly to IDs. If any project or user is not found, the row will fail validation.
Unified Database Transaction: To prevent partial uploads (e.g. creating land acquisition but failing on requisition), the import will run inside a MySQL transaction. If any database insert fails, the whole batch will roll back.
Financial Integration: Marking requisitions as approved/paid in land_fund_requisitions automatically registers them as cash outflows in the Central Statement and Dashboard, meaning no separate Cash Out table insertion is needed.
CSV Columns Format
Only project_name, land_owner_name, total_area, price_per_unit, and land_dept_email are Required. All other columns are Optional (if omitted or empty, those details or payments will not be created).

Column Header	Requirement	Description / Format	Example Value
project_name	Required	Name of the project in TPHL-ERP	The Premium Green Valley
land_owner_name	Required	Full name of the landowner	Mr. Farzana Bhuiyan
land_owner_phone	Optional	Landowner phone number	01910703190
total_area	Required	Total area of land (decimal/katha)	15.5
area_unit	Optional	Unit: decimal or katha (default decimal)	katha
price_per_unit	Required	Price per unit of land	1000000
location	Optional	Location of the land	Ashuliya
remarks	Optional	Acquisition comments/remarks	Selected Ashuliya plot
land_dept_email	Required	Email of Land Officer creating the entry	
landman@premium.com
management_email	Optional	Email of Manager approving (default current user)	
mgmt@premium.com
account_verify_email	Optional	Email of Accountant paying (default current user)	
accounts@premium.com
req_attachment_filename	Optional	Filename of the uploaded requisition document	req_agreement.pdf
bayana_payment_amount	Optional	Amount paid for Bayana (Booking/Advance)	500000
bayana_payment_date	Optional	Date of Bayana payment (YYYY-MM-DD)	2026-06-09
bayana_payment_method	Optional	Method: Bank or Cash (default Bank)	Bank
bayana_transaction_no	Optional	Cheque/Transaction number for Bayana	76544
bayana_document_link	Optional	Google Drive link for Bayana agreement	https://drive.google.com/...
bayana_document_filename	Optional	Filename of the uploaded bayana receipt	bayana_receipt.pdf
registration_no	Optional	Registration deed number (if registered)	Deed-90231
registration_date	Optional	Date of registration (YYYY-MM-DD)	2026-07-01
sub_registry_office	Optional	Registry office name	Savar Registry Office
registration_cost	Optional	Total registration fees/cost	1200000
deed_document_link	Optional	Google Drive link for Registered Deed	https://drive.google.com/...
deed_document_filename	Optional	Filename of the uploaded registration deed file	deed_copy.pdf
mutation_status	Optional	Status: Pending or Completed (default Pending)	Completed
mutation_date	Optional	Date of mutation (YYYY-MM-DD)	2026-07-15
Document Upload Mechanism
Along with the CSV, the upload form will have a file input: <input type="file" name="attachments[]" multiple>. If the user specifies a filename in req_attachment_filename, bayana_document_filename, or deed_document_filename, the system will search for a matching file among the uploaded files, rename it uniquely, move it to public/uploads/receipts/, and store its relative path in the database. Drive links provided in bayana_document_link and deed_document_link will be saved directly in the respective tables.

Proposed Changes
Routes & Controller
[MODIFY] 
construction.php
Register new import routes:
con-land-acquisitions/import-csv mapping to Phase2\LandAcquisitionController@importCsv (GET/POST form render)
con-land-acquisitions/execute-import mapping to Phase2\LandAcquisitionController@executeImport (POST processing)
[MODIFY] 
LandAcquisitionController.php
Implement importCsv() to show the upload form.
Implement executeImport():
Parse CSV header and rows.
Normalize and validate Project name against projects table (case-insensitive & relaxed spaces lookup).
Map user emails (land_dept_email, management_email, account_verify_email) to users.id.
Open a database transaction.
Insert into land_acquisitions, resolving the status as 'registered' if registration details are provided, else 'bayana' if bayana details are provided, else 'selected'.
If bayana_payment_amount > 0:
Insert a paid requisition of type 'Bayana' into land_fund_requisitions (mgmt_status = 'Approved', accounts_status = 'Paid').
Insert the payment into land_bayana_payments.
If registration_cost > 0:
Insert a paid requisition of type 'Registration' into land_fund_requisitions (mgmt_status = 'Approved', accounts_status = 'Paid').
Insert registration details into land_registrations.
Commit transaction on success, or roll back and log errors on failure.
Views
[MODIFY] 
index.php
Add a secondary button "Bulk Import CSV" next to "Record New Land Purchase" leading to the import page.
[NEW] 
import.php
Create the file upload UI with instructions, a list of expected column headers, and validation warnings. Provide a CSV template link/view.
Verification Plan
Manual Verification
Navigate to /con-land-acquisitions.
Click the new Bulk Import CSV button.
Upload a test CSV containing valid project names, emails of active ERP users, and historic acquisition, bayana, and registration figures.
Verify that:
A success message is displayed.
The land acquisitions table shows the new landowner and project.
The Land Ledger Report (/con-land-monthly-reports/ledger) correctly lists the requisitions as Inflows and the payments/registrations as Outflows on their respective dates.
The Cash Book / central statements reflect the deducted amounts under "Land Fund Requisitions" category.
Upload a CSV with an invalid user email or project name and verify it displays clear validation errors and doesn't insert any records (transaction rolled back).



account theke paid 
১. কোড ও রাউট ইমপ্লিমেন্টেশন:
নতুন রাউটসমূহ: 

construction.php
 ফাইলে con-land-acquisitions/sample-csv এবং con-land-acquisitions/execute-import রাউট দুটি যুক্ত করেছি।
কন্ট্রোলার লজিক: 

LandAcquisitionController.php
 ফাইলে দুটি গুরুত্বপূর্ণ মেথড তৈরি করেছি:
downloadSampleCsv(): এটি ইম্পোর্টের জন্য একটি সঠিক ডেমো ডেটাসহ ফরম্যাট করা CSV টেমপ্লেট ডাউনলোড করতে সাহায্য করবে।
executeImport():
ভ্যালিডেশন ফেজ: ফাইল প্রসেস করার আগেই প্রজেক্টের নাম (ERP-র স্পেলিং ও স্পেসের সাথে মিলিয়ে) এবং ৩টি ইমেইল (land_dept_email, management_email, account_verify_email) ইউজার ডাটাবেসে আছে কিনা চেক করবে। কোনো একটি ভুল হলে পুরো ইম্পোর্ট রিজেক্ট করে সুনির্দিষ্ট সারি ও কলামের এরর মেসেজ দেবে।
ডাটাবেস ট্রানজেকশন: সফল ভ্যালিডেশন শেষে begin_transaction() শুরু করবে এবং ৪টি টেবিলে ডেটা পুশ করবে। কোনো কুয়েরি ফেইল করলে সাথে সাথে rollback হবে।
অ্যাটাচমেন্ট ও ড্রাইভ লিংক ম্যাপিং: গুগল ড্রাইভের লিংকগুলো ডাটাবেসে সেভ করবে এবং ইম্পোর্ট ফর্মে একসাথে আপলোড করা ফাইলগুলোর নাম CSV-র সাথে মিলিয়ে স্বয়ংক্রিয়ভাবে রিনেম করে /public/uploads/receipts/ ডিরেক্টরিতে সরিয়ে দেবে।
অটো-ক্যাশআউট রিকুইজিশন: ইম্পোর্ট হওয়া ল্যান্ড ফাইলের বিপরীতে অটোমেটিক Paid রিকুইজিশন তৈরি করবে, যার ফলে প্রজেক্টের লেজার ও অ্যাকাউন্টস ক্যাশআউট স্বয়ংক্রিয়ভাবে আপডেট হবে।
২. ইউজার ইন্টারফেস (UI) আপডেট:


index.php
 ফাইলের উপরে Bulk Import CSV বাটন যুক্ত করেছি।
বাটনে ক্লিক করলে একটি মডাল (#importLandModal) ওপেন হবে, যা আপনাকে:
সহজে CSV ফাইল ফরম্যাট বোঝার জন্য প্রয়োজনীয় সব কলাম এবং ভ্যালিডেশনের নিয়ম দেখাবে।
সরাসরি রেডিমেড Sample CSV Template ডাউনলোডের অপশন দেবে।
CSV ফাইল এবং ঐচ্ছিক ডকুমেন্ট ফাইলসমূহ একসাথে ড্রপ করে আপলোড করার ইনপুট ফিল্ড প্রদান করবে।
আপনার করণীয়:
১. পরিবর্তিত 

LandAcquisitionController.php
, 

construction.php
 এবং 

index.php
 ফাইলগুলো আপনার লাইভ ভিপিএস (VPS) সার্ভারে আপলোড করুন। ২. ল্যান্ড অ্যাকুইজিশন পোর্টফোলিও পেজে (/con-land-acquisitions) যান এবং নতুন Bulk Import CSV বাটনটি পরীক্ষা করে দেখুন।

সম্পূর্ণ কাজের টেকনিক্যাল বিবরণ এবং রিঅ্যাসুরেন্স গাইডটি দেখতে নিচের ফাইলে ক্লিক করুন:


এখানে Land Owner (জমির মালিক) বলতে যার কাছ থেকে জমিটি কেনা হচ্ছে (অর্থাৎ বিক্রেতা বা বিক্রেতার নাম) বোঝানো হয়েছে।

যেহেতু আপনাদের প্রতিষ্ঠান (TPHL) ক্রেতা হিসেবে জমিটি কিনছে, তাই: ১. Land Owner Name: হচ্ছে সেই বিক্রেতা ব্যক্তি/দলিলদাতা যার কাছ থেকে জমিটি কেনা হচ্ছে (যেমন আপনার CSV ফাইলে দেওয়া ছিল: Mr. Farzana Bhuiyan)। ২. Land Owner Phone: হচ্ছে সেই বিক্রেতার মোবাইল নম্বর।

জমিটি ক্রয়ের সময় কার কাছ থেকে কেনা হচ্ছে এবং বায়না বা রেজিস্ট্রেশনের টাকা কাকে দেওয়া হচ্ছে, তা ট্র‍্যাক করার জন্যই এই বিক্রেতার (Land Owner) নাম ও ফোন নম্বর রেকর্ড করে রাখা হয়।