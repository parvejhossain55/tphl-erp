৪. Team Cannibal-এর মেম্বারদের লাইভ ডাটাবেজ রেকর্ড (tphl_db.sql)
আপনার লাইভ ডাটাবেজে Team Cannibal-এর মেম্বারদের জন্য নিচের বুকিং রেকর্ডগুলো রয়েছে, যা আপনার স্ক্রিনশটের সাথে হুবহু মিলে যায়:

১) Raisa Ferdousi (User ID: 168, Sales Executive):
বুকিং রেকর্ড: Booking ID #1913
তারিখ: 2026-09-09 | স্ট্যাটাস: completed
প্রজেক্ট: The Premium Vertex Garden | ফ্ল্যাট ID: 3634
ফ্ল্যাটের land_share_value: ১,৬৫০,০০০.০০ (16.50 Lac)
রিপোর্ট ফলাফল:
Units Sold: 1
Unassigned Vol: 1,650,000.00
Total Achieved Volume: 1,650,000.00
২) Md. Saiful Islam Jony (User ID: 32, Sales Team Leader):
বুকিং রেকর্ড: মোট ৪টি বুকিং (সবগুলোই 2026-09-09 তারিখে সম্পন্ন):
Booking ID #1736: The Crown Pavilion (ফ্ল্যাট ID 3284) $\rightarrow$ land_share_value = ১,০১৯,২৫০.০০
Booking ID #1737: The Crown Pavilion (ফ্ল্যাট ID 3285) $\rightarrow$ land_share_value = ৮২৩,৫০০.০০
Booking ID #1930: The Premium Elite Garden (ফ্ল্যাট ID 3651) $\rightarrow$ land_share_value = ১,৪৫০,০০০.০০
Booking ID #1931: The Premium Elite Garden (ফ্ল্যাট ID 3652) $\rightarrow$ land_share_value = ১,৪৫০,০০০.০০
যোগফল: $$\text{Total Vol} = 1,019,250 + 823,500 + 1,450,000 + 1,450,000 = \mathbf{4,742,750.00}$$
রিপোর্ট ফলাফল:
Units Sold: 4
Unassigned Vol: 4,742,750.00
Total Achieved Volume: 4,742,750.00
৩) Rakib Hasan Rubai (User ID: 68, Sales Team Leader):
বুকিং রেকর্ড: Booking ID #2066
তারিখ: 2026-09-08 | স্ট্যাটাস: booked
প্রজেক্ট: TPRC Majesty Tower | ফ্ল্যাট ID: 4551 (Flat 10-C)
ফ্ল্যাটের land_share_value: ১,১২৩,৭৫০.০০ (11.23 Lac)
রিপোর্ট ফলাফল:
Units Sold: 1
Unassigned Vol: 1,123,750.00
Total Achieved Volume: 1,123,750.00
৪) Abu Naym (User ID: 78, Sales Executive):
সেপ্টেম্বর সাইকেলে lead_bookings টেবিলে কোনো বুকিং রেকর্ড নেই।
রিপোর্ট ফলাফল:
Units Sold: 0
Total Achieved Volume: 0.00
৫. Ranking Dashboard-এ র‍্যাঙ্ক কীভাবে নির্ধারিত হলো?
সিস্টেমের 

Performance Score Formula
 ৫টি উপাদানের ওয়েটেড অ্যাভারেজ দিয়ে ক্যালকুলেট করা হয়:

$$\text{Score} = (\text{Volume Ach} \times 45%) + (\text{Unit Ach} \times 20%) + (\text{Visits} \times 15%) + (\text{Contacted Rate} \times 10%) + (\text{Follow-up Rate} \times 10%)$$

Raisa Ferdousi (Rank #1, Score: 83.86):
সেলস সম্পন্ন করার পাশাপাশি তার লিড ফলো-আপ রেট ছিল সর্বোচ্চ ৮৮.৬৩% এবং কনটাক্টেড রেট ১০০%।
Md. Saiful Islam Jony (Rank #2, Score: 77.12):
সর্বোচ্চ ৪টি ইউনিট এবং ৪৭.৪২ লাখ ভলিউম বিক্রি করা সত্ত্বেও ফলো-আপ রেট কম থাকায় (২৫.৫৬%) সামগ্রিক স্কোর হয়েছে ৭৭.১২।
Rakib Hasan Rubai (Rank #3, Score: 75.07):
১টি ইউনিট বিক্রি (১১.২৩ লাখ ভলিউম), কিন্তু ফলো-আপ রেট ছিল ০.৬৫%।
Abu Naym (Rank #4, Score: 16.83):
কোনো সেলস ও ভিজিট ছিল না, শুধুমাত্র লিড ফলো-আপ রেট (৭১.৭৫%) থেকে স্কোর এসেছে।
৬. sales-activity-report?tab=all-team পেজে কী ঘটছে?
যখন আপনি এই URL-এ যান:

কন্ট্রোলার 

SalesActivityReportController.php
 থেকে getTrackerReport(2026, 9, ...) কল হয়।
সার্ভিসে lead_bookings, flats, leads, lead_follow_ups টেবিলগুলো থেকে উপরের ডাটা কোয়েরি হয়ে টিমের মেম্বারদের গ্রুপ করে সাজানো হয়।
Monthly Team Summary ট্যাবে প্রতিটি মেম্বারের রো এবং Ranking Dashboard ট্যাবে স্কোরের ক্রমানুসারে মেম্বারদের কার্ড প্রদর্শিত হয়।
Mam, আপনার এই সংক্রান্ত কোনো বিষয়ে আরও বিস্তারিত বা নির্দিষ্ট কোনো কাস্টমাইজেশন প্রয়োজন হলে দয়া করে জানাবেন, আমি সঙ্গে সঙ্গে ব্যবস্থা নিচ্ছি।