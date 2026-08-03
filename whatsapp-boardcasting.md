১. পেমেন্ট ফর্মে টেস্টিং মোড সুইচ (Form Testing Toggle):

প্রতিটি পেমেন্ট ফর্মে (Sales, CED, Accounts) একটি সুইচার/রেডিও বাটন/চেকবক্স থাকবে: Testing Mode (Skip SMS)।
যদি ডেভেলপমেন্ট বা টেস্ট করার জন্য এই অপশনটি টিক দেওয়া থাকে:
ভুল বা ডামি ফোন নম্বর থাকলেও সিস্টেম পেমেন্ট সাবমিট করতে দেবে।
অটোমাস এপিআই-তে আসল এসএমএস পাঠাবে না (যাতে আপনার কেনা SMS লিমিট বা ক্রেডিট শেষ না হয়)।
ডাটাবেজের sms_logs টেবিলে স্ট্যাটাস থাকবে: skipped_testing।
২. গ্লোবাল কনফিগারেশন সাপোর্ট (config.php):

app/config/config.php ফাইলে sms_testing_mode => true/false ফ্ল্যাগ থাকবে।
লোকাল ডেভেলপমেন্ট সার্ভারে (Local Server) এটি ডিফল্টভাবে true থাকবে, যাতে ডেভেলপার কাজ করার সময় ভুল করে আসল এসএমএস সেন্ড না হয়ে যায়।
প্রোডাকশন সার্ভারে (Production Server) এটি false থাকবে এবং ফর্মের টেস্টিং সুইচ অফ থাকবে।
৩. মূল ফিচার ও ভ্যালিডেশন লজিক (Core Features)
১. প্রোডাকশন মোডে কাস্টমার ফোন নম্বর ভ্যালিডেশন
টেস্টিং মোড অফ থাকলে, কাস্টমারের ১১ ডিজিট মোবাইল নম্বর (01[3-9]\d{8}) আবশ্যিক।
নম্বর না থাকলে বা ভুল থাকলে স্ক্রিনে পপআপ দেখাবে:
"ক্লায়েন্টের ফোন নম্বরটি ভুল বা অনুপস্থিত। পেমেন্ট সম্পূর্ণ করার পূর্বে সঠিক ১১ ডিজিটের মোবাইল নম্বর আপডেট করুন। (অথবা টেস্টিংয়ের জন্য Testing Mode টিক দিন)"
২. ট্রিগার ১: টাকা/চেক রিসিভড এসএমএস (Payment Received SMS)
প্রোডাকশন মোডে সেলস/CED/অ্যাকাউন্টস টাকা বা চেক রিসিভ করে এন্ট্রি দেওয়ার সাথে সাথেই (অ্যাকাউন্টস ভেরিফাই করার আগেই) প্রথম রিসিভড এসএমএস ক্লায়েন্টের মোবাইলে যাবে।
৩. ট্রিগার ২: অ্যাকাউন্টস ভেরিফাইড এসএমএস (Payment Verified SMS)
অ্যাকাউন্টস টিম যখন পেমেন্টটি Verify বাটন চেপে অনুমোদন করবে, তখন দ্বিতীয় কনফার্মেশন এসএমএস যাবে।
৪. ফাইল ও কোড পরিবর্তনের তালিকা (Proposed Code Changes)
ক. ডাটাবেজ টেবিল
[NEW] 
add_sms_logs_table.sql
এসএমএস রেকর্ড রাখার জন্য sms_logs টেবিল তৈরি করা হবে।
কলাম: id, client_id, booking_id, payment_id, mobile_no, sms_type, message, is_testing, status (sent, failed, skipped_testing), created_at
খ. কনফিগারেশন ও সার্ভিস লেয়ার
[MODIFY] 
config.php
অটোমাস এপিআই ড্যাশবোর্ড ক্রেডেনশিয়াল ও টেস্টিং মোড ফ্ল্যাগ যুক্ত করা হবে:
php

"automas_sms_api_url"   => "https://asms.automas.com.bd/api/sendsms",
"automas_sms_api_key"   => "YOUR_AUTOMAS_API_KEY",
"automas_sms_sender_id" => "YOUR_SENDER_ID",
"sms_testing_mode"      => true, // Local/Staging = true, Prod = false
[NEW] 
SmsService.php
isTestingModeEnabled(): ফর্মের ইনপুট অথবা গ্লোবাল কনফিগ থেকে টেস্টিং মোড একটিভ কিনা তা চেক করবে।
sendSms(...): টেস্টিং মোড অন থাকলে এপিআই কল স্কিপ করে ডাটাবেজে status = 'skipped_testing' সেভ করবে। অন না থাকলে সরাসরি অটোমাস এপিআই কল করে এসএমএস পাঠাবে।
গ. কন্ট্রোলার ও লজিক পরিবর্তন
[MODIFY] 
PaymentBookingController.php
[MODIFY] 
AccountClientController.php
পেমেন্ট রিসিভ ও ভেরিফাইয়ের সময় টেস্টিং ফ্ল্যাগ পাস করবে এবং সেই অনুযায়ী এসএমএস হুক কল করবে।
ঘ. ইউজার ইন্টারফেস ও টেস্টিং রেডিও/সুইচ
[MODIFY] 
payment-bookings.php
[MODIFY] 
client-cash-in-form.php
[MODIFY] 
ced-cash-in-form.php
প্রতিটি ফর্মে একটি সুন্দর Testing Mode টগল বা রেডিও বাটন থাকবে:
html

<div class="form-check form-switch mt-2">
    <input class="form-check-input" type="checkbox" name="is_testing_mode" id="testingModeSwitch">
    <label class="form-check-label text-warning fw-semibold" for="testingModeSwitch">
        <i class="fa-solid fa-vial me-1"></i> Testing Mode (Skip SMS & Phone Validation)
    </label>
</div>
জাভাস্ক্রিপ্ট বিহেভিয়ার: টেস্টিং মোড অন করা থাকলে জাভাস্ক্রিপ্ট ফোন নম্বর ব্লক করবে না এবং ইউজার যেকোনো নম্বর দিয়েই টেস্ট পেমেন্ট সাবমিট করতে পারবে।
৫. পরীক্ষা পদ্ধতি (Verification Plan)
১. টেস্টিং মোড অন টেস্ট (Testing Mode ON Check):

ফর্মে "Testing Mode" টিক দিয়ে ভুল/ডামি ফোন নম্বর দিয়ে পেমেন্ট জমা দেওয়া হবে।
পেমেন্ট সাবমিট হবে, কিন্তু বাস্তব জীবনের এসএমএস প্যাকেজের ব্যালেন্স খরচ হবে না এবং ডাটাবেজে skipped_testing লগ থাকবে।
২. প্রোডাকশন মোড টেস্ট (Testing Mode OFF Check):

"Testing Mode" অফ রেখে ভুল নম্বরে পেমেন্ট দিলে ফর্ম ব্লক করবে। সঠিক নম্বরে পেমেন্ট দিলে সাথে সাথেই অটোমাস এপিআই-এর মাধ্যমে রিসিভড এসএমএস ক্লায়েন্টের মোবাইলে চলে যাবে।