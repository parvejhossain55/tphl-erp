# TPHL ERP & Freshsales CRM Integration Roadmap
## পরবর্তী পদক্ষেপের সম্পূর্ণ গাইডলাইন ও রানিং প্ল্যান (Bangla Guide)

লোকাল ডেভেলপমেন্ট ও ফ্রী ট্রায়াল সফলভাবে পরীক্ষার পর যখন তোমরা এই প্রজেক্টটিকে লাইভ করবে, তখন নিচের ধাপগুলো একে একে শেষ করতে হবে:

---

## 🔷 STAGE 1: লাইভ সার্ভারে ইআরপি প্রজেক্ট আপডেট
১. **কোড আপলোড:** লোকাল কম্পিউটারে আমরা যে ফাইলগুলো আপডেট ও টেস্ট করেছি, সেগুলো তোমার লাইভ সার্ভারের প্রজেক্ট ডিরেক্টরিতে আপলোড করো:
   - Config Configuration: `app/config/config.php`
   - Services File: `app/services/FreshsalesService.php`
   - Webhook & CSV Controller: `app/controllers/LeadWebhookController.php`
   - Route list: `app/routes/crm.php`
   - Bypass configuration: `public/index.php`
   - DigitalMarketing Controller update: `app/controllers/DigitalMarketingController.php`
   - UI Layout view: `app/views/digital-marketing-team/freshsales-leads.php`
   - Sidebar changes: `app/views/layouts/sidebar.php`

২. **ডাটাবেজ টেবিল তৈরি (SQL Migration):** লাইভ সার্ভারের phpMyAdmin-এ গিয়ে ডাটাবেজে নিচের SQL কমান্ড দুটি রান করো:
```sql
CREATE TABLE IF NOT EXISTS freshsales_leads_cache (
    id                                              INT AUTO_INCREMENT PRIMARY KEY,
    freshsales_id                                   VARCHAR(100) UNIQUE,
    campaign_name                                   VARCHAR(255) DEFAULT NULL,
    platform                                        VARCHAR(100) DEFAULT NULL,
    which_apartment_size_are_you_interested_in      TEXT DEFAULT NULL,
    which_floor_are_you_for_shop                    TEXT DEFAULT NULL,
    when_are_you_planning_to_buy                    TEXT DEFAULT NULL,
    would_you_like_to_schedule_a_site_visit         TEXT DEFAULT NULL,
    when_is_the_best_time_to_contact_you            TEXT DEFAULT NULL,
    full_name                                       VARCHAR(255) NOT NULL,
    job_title                                       VARCHAR(255) DEFAULT NULL,
    company_name                                    VARCHAR(255) DEFAULT NULL,
    phone_number                                    VARCHAR(50)  NOT NULL,
    alt_phone                                       VARCHAR(50)  DEFAULT NULL,
    email                                           VARCHAR(150) DEFAULT NULL,
    city                                            VARCHAR(100) DEFAULT NULL,
    country                                         VARCHAR(100) DEFAULT 'Bangladesh',
    is_exported                                     TINYINT(1)   DEFAULT 0 COMMENT '0 = Pending, 1 = Exported',
    exported_at                                     DATETIME     DEFAULT NULL,
    created_at                                      DATETIME     DEFAULT NULL,
    synced_at                                       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS freshsales_webhook_logs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    event_type      VARCHAR(100) NOT NULL,
    freshsales_id   VARCHAR(100) DEFAULT NULL,
    platform        VARCHAR(100) DEFAULT NULL,
    phone_number    VARCHAR(50)  DEFAULT NULL,
    status          ENUM('success', 'skipped', 'failed') DEFAULT 'success',
    error_message   TEXT         DEFAULT NULL,
    received_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_fs_cache_exported ON freshsales_leads_cache(is_exported);
CREATE INDEX idx_fs_cache_phone    ON freshsales_leads_cache(phone_number);
```

---

## 🔷 STAGE 2: Freshsales পেইড প্ল্যান ও এপিআই কনফিগারেশন
১. **পেইড সাবস্ক্রিপশন ক্রয়:** Freshsales Suite / CRM-এর পেইড সাবস্ক্রিপশন (যেমন Growth বা Pro প্ল্যান) কেনা সম্পন্ন করো।
২. **পেইড এপিআই কি ও ডোমেন সংগ্রহ:** 
   - পেইড অ্যাকাউন্টের প্রোফাইল সেটিংস থেকে নতুন API Key কপি করো।
   - ডোমেন লিঙ্কটি নোট করো (যেমন: `tphl-real.myfreshworks.com`)।
৩. **লাইভ ERP কনফিগ ফাইল এডিট:** লাইভ সার্ভারের `app/config/config.php` ফাইলে সংগৃহীত নতুন পেইড API Key এবং পেইড সাবডোমেন বসাও:
```php
"freshsales_webhook_secret" => "TphlFwSecret2026@#", // তোমাদের দেওয়া ওয়েব হুক পাসওয়ার্ড
"freshsales_api_key"        => "YOUR_REAL_PAID_ACCOUNT_API_KEY", // পেইড এপিআই কি
"freshsales_domain"         => "YOUR_REAL_PAID_SUBDOMAIN_NAME", // পেইড সাবডোমেন নাম
```

---

## 🔷 STAGE 3: ফেসবুক, হোয়াটসঅ্যাপ এবং গুগল অ্যাকাউন্ট কানেকশন
১. **৩টি ফেসবুক পেজ কানেক্ট:** `Admin Settings ➔ Channels ➔ Facebook Messenger` এ গিয়ে তোমার ফেসবুক অ্যাডমিন আইডি দিয়ে ৩টি ফেসবুক পেজই কানেক্ট করো।
২. **৩টি হোয়াটসঅ্যাপ বিজনেস নম্বর কানেক্ট:** `Admin Settings ➔ Channels ➔ WhatsApp` সেকশনে গিয়ে Meta-এর মাধ্যমে ৩টি হোয়াটসঅ্যাপ নম্বরই ভেরিফাই ও কানেক্ট করো।
৩. **গুগল অ্যাড অ্যাকাউন্ট কানেক্ট:** `Admin Settings ➔ Integrations ➔ Google Ads` থেকে গুগল অ্যাড ম্যানেজারটি কানেক্ট করে নাও।

---

## 🔷 STAGE 4: চ্যাটবট ও ওয়েব হুক দিয়ে লিড অটোমেশন
১. **ফোন নম্বর সংগ্রহের চ্যাটবট:**
   - ফ্রেশসেলস ড্যাশবোর্ডের `Settings ➔ Chatbots` এ যাও।
   - একটি অটোমেটিক Lead Generation Bot ফ্লো তৈরি করো। কাস্টমার নক দিলে বট জিজ্ঞেস করবে: *"হ্যালো, আপনার ফোন নম্বরটি কত?"* কাস্টমার নম্বরটি দিলে বট সেটিকে সরাসরি কন্টাক্টের `Mobile` ফিল্ডে সেভ করে নেবে।
২. **লাইভ ওয়েব হুক কনফিগার (রিয়েল-টাইম ডাটা ট্রিকার):**
   - ফ্রেশসেলস ড্যাশবোর্ডের `Admin Settings ➔ Workflows` এ যাও।
   - একটি নতুন Workflow তৈরি করো। 
     - **Trigger:** `When a Contact is created` অথবা `When Contact field 'Mobile' is updated`.
     - **Action:** `Trigger Webhook` সিলেক্ট করো।
     - **Webhook URL:** তোমার লাইভ ডোমেনের লিংকটি দাও: `https://your-live-domain.com/freshsales-webhook`
     - **Header:** `X-Freshsales-Signature` = `TphlFwSecret2026@#` (বা কনফিগে যেটা সেট করেছ)
     - **Payload Content:** `All Fields` সিলেক্ট করে সেভ করে দাও।

---

**অভিনন্দন!** এখন থেকে কোনো কাস্টমার ফেসবুক পেজে বা হোয়াটসঅ্যাপে নক দিয়ে ফোন নম্বর দিলে, তা সাথে সাথে রিয়েল-টাইমে ফ্রেশসেলস হয়ে তোমার লাইভ ERP-তে চলে আসবে এবং DMT টিম তা দেখে ডাউনলোড করতে পারবে।



==============
আমাদের তৈরি করা এপিআই কন্ট্রোলার (

LeadWebhookController.php
) কাস্টম কোশ্চেনের উত্তরগুলো ফ্রেশসেলস-এর থেকে এভাবে ক্যাচ করবে:

php
// Custom Fields Mapping (প্রশ্নোত্তর ফিল্ডসমূহ)
$custom = $lead['custom_field'] ?? [];
$aptSize   = $custom['which_apartment_size_are_you_interested_in'] ?? null;
$shopFloor = $custom['which_floor_are_you_for_shop'] ?? null;
$whenBuy   = $custom['when_are_you_planning_to_buy'] ?? null;
$siteVisit = $custom['would_you_like_to_schedule_a_site_visit'] ?? null;
$bestTime  = $custom['when_is_the_best_time_to_contact_you'] ?? null;
এই কাস্টম ফিল্ডগুলো স্বয়ংক্রিয়ভাবে ডাটাবেজের freshsales_leads_cache টেবিলে সেভ হবে এবং যখন এক্সপোর্ট করবে তখন CSV ফাইলে নির্দিষ্ট কলামেই ডাটাগুলো পেয়ে যাবে।

তোমাকে কোডে নতুন করে কিছুই করতে হবে না, শুধু ফ্রেশসেলস ড্যাশবোর্ডে ফিল্ডগুলো তৈরি করে ম্যাপিং করে দিলেই কাজ করবে!`