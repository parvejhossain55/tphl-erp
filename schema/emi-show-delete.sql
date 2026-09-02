SELECT emi_no, emi_amount, status, due_date 
FROM emi_schedules 
WHERE lead_booking_id = 543;
mysql> SELECT emi_no, emi_amount, status, due_date 
    -> FROM emi_schedules 
    -> WHERE lead_booking_id = 543;
+--------+------------+---------+------------+
| emi_no | emi_amount | status  | due_date   |
+--------+------------+---------+------------+
|      1 |   79354.00 | paid    | 2026-04-17 |
|      2 |   79354.00 | paid    | 2026-04-17 |
|      3 |   79354.00 | paid    | 2026-04-17 |
|      4 |      21.00 | paid    | 2026-04-17 |
|      5 |   79354.00 | paid    | 2026-01-09 |
|      6 |   79354.00 | paid    | 2026-01-09 |
|      7 |      45.00 | pending | 2026-01-09 |
+--------+------------+---------+------------+
7 rows in set (0.00 sec)

mysql> 


delete shob emi by lead_booking_id
-- ১. emi_schedules টেবিল থেকে ক্লায়েন্টের সব শিডিউল ডিলিট করতে:
DELETE FROM emi_schedules WHERE lead_booking_id = 543;

-- ২. lead_payments টেবিল থেকে ক্লায়েন্টের শুধু emi পেমেন্টগুলো ডিলিট করতে (অন্যান্য বুকিং মানি বা রেজিস্ট্রেশন পেমেন্ট যেন ঠিক থাকে):
DELETE FROM lead_payments WHERE booking_id = 543 AND payment_type = 'emi';


যেহেতু emi_schedules টেবিলে সরাসরি project_id নেই (সেখানে শুধু lead_booking_id আছে), তাই আপনাকে lead_bookings টেবিলের সাথে JOIN করে আপডেট করতে হবে, কারণ project_id থাকে lead_bookings টেবিলে।

নির্দিষ্ট কোনো প্রজেক্টের (project_id) আওতাভুক্ত সব ক্লায়েন্টের EMI এর অ্যামাউন্ট আপডেট করার জন্য নিচের SQL কুয়েরিটি ব্যবহার করতে পারেন:
UPDATE emi_schedules es
JOIN lead_bookings lb ON es.lead_booking_id = lb.id
SET es.emi_amount = 50000.00   -- এখানে আপনার নতুন EMI এর অ্যামাউন্ট বসাবেন
WHERE lb.project_id = 12;      -- এখানে আপনার কাঙ্ক্ষিত project_id বসাবেন




UPDATE emi_schedules es
JOIN lead_bookings lb ON es.lead_booking_id = lb.id
SET es.emi_amount = 50000.00 
WHERE lb.project_id = 12 AND es.status = 'pending';
