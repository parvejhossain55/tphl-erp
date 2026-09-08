-- ১. ট্রানজাকশন শুরু
START TRANSACTION;

-- ২. সেলস পেমেন্ট সংক্রান্ত অ্যাকাউন্টিং জার্নাল ডিটেইলস মুছে ফেলা
DELETE jd 
FROM journal_details jd
INNER JOIN journals j ON jd.journal_id = j.id
WHERE j.source_module = 'sales';

-- ৩. সেলস পেমেন্ট সংক্রান্ত প্রধান জার্নাল/ভাউচার মুছে ফেলা
DELETE FROM journals 
WHERE source_module = 'sales';

-- ৪. EMI শিডিউল এবং EMI ফলো-আপ হিস্ট্রি মুছে ফেলা
DELETE FROM emi_schedules;
DELETE FROM emi_followups;

-- ৫. বুকিং ও পেমেন্ট রিলেটেড SMS লগ মুছে ফেলা
DELETE FROM sms_logs 
WHERE booking_id IS NOT NULL OR payment_id IS NOT NULL;

-- ৬. বুকিং ডিসকাউন্ট ও পেমেন্ট সংক্রান্ত অ্যাপ্রুভাল রিকোয়েস্ট মুছে ফেলা
DELETE FROM approval_requests 
WHERE source_table IN ('lead_bookings', 'lead_payments', 'client_refund_requests');

-- ৭. রিফান্ড, অনবোর্ডিং এবং ইনসেনটিভ টেবিল ক্লিন করা
DELETE FROM client_refund_requests;
DELETE FROM onboard_clients;
DELETE FROM project_incentive_claims;
DELETE FROM project_incentive_payments;

-- ৮. সমস্ত ক্লায়েন্ট পেমেন্ট (ক্যাশ-ইন) মুছে ফেলা
DELETE FROM lead_payments;

-- ৯. সব ফ্ল্যাটের স্ট্যাটাস 'available' এবং is_booked = 0 করা
UPDATE flats 
SET status = 'available', 
    is_booked = 0 
WHERE is_booked = 1 OR status != 'available';

-- ১০. সমস্ত লিড বুকিং মুছে ফেলা
DELETE FROM lead_bookings;

-- ১১. (ঐচ্ছিক) বুকড হওয়া লিডগুলোকে সাধারণ লিডে রূপান্তর করা
UPDATE leads 
SET lead_type = 'Prospect' 
WHERE lead_type = 'Sold';

UPDATE leads 
SET last_lead_action = 'new' 
WHERE last_lead_action = 'booked';

-- ১২. সবকিছু সফল হলে ট্রানজাকশন সেভ করা
COMMIT;
