


আপনার প্রশ্নটি অত্যন্ত যৌক্তিক এবং এটি ERP ও কনস্ট্রাকশন ম্যানেজমেন্টের অন্যতম প্রধান বিজনেস প্রসেস।

সহজ ভাষায় বলতে গেলে, BOQ (Bill of Quantities) এবং Purchase Request (PR) আলাদা রাখার প্রধান কারণ হলো কাজের পরিকল্পনা এবং বাস্তব চাহিদার মধ্যে নিয়ন্ত্রণ রাখা:

১. BOQ হলো "মাস্টার বাজেট ও লিমিট" (Master Plan & Budget)
BOQ সাধারণত পুরো মাসের জন্য বা পুরো প্রজেক্টের একটি কাজের ধাপের (Phase) জন্য একবারে তৈরি করা হয় (যেমন: ১০,০০০ বস্তা সিমেন্ট বা ৫০,০০০ ইট)।
এটি পিএম (PM) এবং ম্যানেজমেন্ট একবারই এপ্রুভ করে। এর মূল উদ্দেশ্য হলো—কোনোভাবেই যেন এপ্রুভড বাজেটের বেশি ম্যাটেরিয়াল সাইটে কেনা না হয়।
২. Purchase Request (PR) হলো "বাস্তব চাহিদা" (Actual Demand)
সাইটে সাধারণত একবারে পুরো ১০,০০০ বস্তা সিমেন্ট আনা হয় না। কারণ:
সাইটে রাখার মতো পর্যাপ্ত জায়গা (Storage space) থাকে না।
বৃষ্টিতে নষ্ট হওয়া বা চুরি হওয়ার ঝুঁকি থাকে।
কোম্পানির ক্যাশ-ফ্লো বা ফান্ডের লিমিটেশন থাকতে পারে।
তাই সাইট ইঞ্জিনিয়ার প্রয়োজন অনুযায়ী ধাপে ধাপে পারচেজ রিকোয়েস্ট (PR) পাঠান। যেমন:
প্রথম সপ্তাহে: ১,০০০ বস্তা সিমেন্ট লাগবে (PR-1)
তৃতীয় সপ্তাহে: আরো ২,০০০ বস্তা সিমেন্ট লাগবে (PR-2)
সিস্টেম স্বয়ংক্রিয়ভাবে ট্র্যাক করে যে ইঞ্জিনিয়ার ধাপে ধাপে রিকোয়েস্ট করে মোট কতো নিচ্ছেন এবং তা BOQ-তে নির্ধারিত মোট লিমিটের ভেতর আছে কি না (যেমনটি আমরা remaining_qty কলামে দেখাই)।
আপনার সিস্টেমে কি সরাসরি করা যেত?
যদি আপনার কাজ এমন হয় যে—BOQ এপ্রুভ হওয়ার সাথে সাথেই পুরো মালামাল একবারে অর্ডার করা হবে, তবে PR আলাদাভাবে তৈরি করাটা অতিরিক্ত কাজ বা ডুপ্লিকেট এন্ট্রি মনে হতে পারে।

সেক্ষেত্রে প্রসেস সহজ করতে আমরা দুটি জিনিস করতে পারি:

অপশন ১: BOQ এপ্রুভ হওয়ার সাথে সাথে সিস্টেম অটোমেটিক্যালি ব্যাকএন্ডে একটি ড্রাফট/সাবমিট করা PR তৈরি করে দিবে। ফলে সাইট ইঞ্জিনিয়ারকে ম্যানুয়ালি আর PR করতে হবে না।
অপশন ২: সাইট ইঞ্জিনিয়ার যখন প্রথমবার রিকোয়েস্ট করবেন, তখন তিনি এক ক্লিকেই সম্পূর্ণ BOQ-কে সরাসরি একটি PR-এ রূপান্তর করতে পারবেন (যা বর্তমানে সিস্টেমে Load BOQ Materials বাটনের মাধ্যমে সহজেই করা যায়)।
আপনার কাজের প্রসেস অনুযায়ী কোনটি করলে আপনার ব্যবহারকারীদের জন্য সুবিধা হবে?








































-- =============================================================================
-- SEED WORK TYPES AND PHASE ITEM TEMPLATES
-- Safe to run multiple times (cleans and repopulates)
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- 1. Alter phase_item_template_defaults to support default work_type_id
ALTER TABLE `phase_item_template_defaults` 
    ADD COLUMN IF NOT EXISTS `work_type_id` BIGINT UNSIGNED NULL DEFAULT NULL 
    AFTER `design_type_id`;

-- 2. Populate work_types with typical construction activities
-- We will delete existing dummy rows to avoid duplication, but preserve IDs where appropriate
DELETE FROM `work_types` WHERE `id` >= 10;

INSERT INTO `work_types` (`id`, `work_type_name`, `design_type_id`, `default_phase_name`) VALUES
(10, 'Earth Excavation & Backfilling', 5, 'Structure'),
(11, 'Soling & PCC Work', 5, 'Structure'),
(12, 'Rebar Fabrication & Binding', 5, 'Structure'),
(13, 'Shuttering & Centering Formwork', 5, 'Structure'),
(14, 'Concrete Casting & Pouring', 5, 'Structure'),
(15, 'Concrete Curing & De-shuttering', 5, 'Structure'),
(16, 'Brick Wall Construction - 5" / 10"', 6, 'Brick Work'),
(17, 'Door Frame (Chowkat) Fixing', 6, 'Wood Work'),
(18, 'Door Shutter Fixing', 6, 'Wood Work'),
(19, 'MS Grill & Railing Fabrication & Fixing', 6, 'Metal Work'),
(20, 'Water & Sewer Pipe Laying & Jointing', 3, 'Plumbing & Sanitary'),
(21, 'Pressure Testing of Water Lines', 3, 'Plumbing & Sanitary'),
(22, 'Sanitary Ware & CP Fittings Installation', 3, 'Plumbing & Sanitary'),
(23, 'PVC Conduit Laying in Slabs', 2, 'Electrical Work'),
(24, 'Wall Chasing & Box Fixing', 2, 'Electrical Work'),
(25, 'Cable Pulling & Wiring', 2, 'Electrical Work'),
(26, 'Switch & Socket Fitting', 2, 'Electrical Work'),
(27, 'Cement Sand Plastering', 6, 'Plaster Work'),
(28, 'Tile Fixing with Adhesives/Cement', 6, 'General Floor Tiles'),
(29, 'Kitchen Granite Top Installation', 6, 'Toilet & Kitchen Wall Tiles'),
(30, 'Window Frame Fixing', 6, 'Aluminium Work'),
(31, 'Glass Shutter Sliding Installation', 6, 'Aluminium Work'),
(32, 'EME Equipment & Cabling Work', 2, 'EME'),
(33, 'Lift Guide Rail & Cabin Installation', 2, 'EME'),
(34, 'Fire Hydrant Piping Installation', 4, 'EME'),
(35, 'Wall Sanding & Putty Coating', 6, 'Paint Work'),
(36, 'Interior & Exterior Double Coat Paint', 6, 'Paint Work'),
(37, 'Pavement / Road CC Casting', 9, 'Others Civil Work'),
(38, 'Utility Meter & Connection Line Setup', 6, 'Utility Connection & Bill')
ON DUPLICATE KEY UPDATE 
    `work_type_name` = VALUES(`work_type_name`),
    `design_type_id` = VALUES(`design_type_id`),
    `default_phase_name` = VALUES(`default_phase_name`);


-- 3. Clear and repopulate phase_item_template_defaults
TRUNCATE TABLE `phase_item_template_defaults`;

INSERT INTO `phase_item_template_defaults` 
    (`phase_name`, `item_name`, `item_order`, `weight_pct`, `design_type_id`, `work_type_id`) VALUES
-- Structure (Phase 2, Design: Structural=5)
('Structure', 'Footing & Column Pedestal', 1, 20.00, 5, 14),
('Structure', 'Grade Beam & UGWR Top Slab', 2, 5.00, 5, 14),
('Structure', 'GF Column, Stair etc', 3, 4.00, 5, 14),
('Structure', '1st Floor Slab', 4, 9.00, 5, 14),
('Structure', 'Typical Floor Column (5x3%)', 5, 15.00, 5, 14),
('Structure', '2nd to Roof Slab (5x8%)', 6, 40.00, 5, 14),
('Structure', 'Roof Top', 7, 7.00, 5, 14),

-- Brick Work (Phase 3, Design: Architectural=6)
('Brick Work', 'Ground Floor & Basement Brickwork', 1, 20.00, 6, 16),
('Brick Work', 'Typical Floor Brickwork', 2, 60.00, 6, 16),
('Brick Work', 'Rooftop & Parapet Wall', 3, 20.00, 6, 16),

-- Wood Work (Phase 4, Design: Architectural=6)
('Wood Work', 'Door Frame Installation', 1, 40.00, 6, 17),
('Wood Work', 'Door Shutter Fitting', 2, 40.00, 6, 18),
('Wood Work', 'Wooden Cabinets & Decorative Panelling', 3, 20.00, 6, 18),

-- Metal Work (Phase 5, Design: Architectural=6)
('Metal Work', 'Main Boundary Gate', 1, 30.00, 6, 19),
('Metal Work', 'Staircase & Balcony Railings', 2, 50.00, 6, 19),
('Metal Work', 'Window Safety Grills', 3, 20.00, 6, 19),

-- Plumbing & Sanitary (Phase 6, Design: Plumbing=3)
('Plumbing & Sanitary', 'Underground Sewerage & Water Lines', 1, 30.00, 3, 20),
('Plumbing & Sanitary', 'Vertical Shaft Piping Riser', 2, 30.00, 3, 20),
('Plumbing & Sanitary', 'Toilet Concealed Piping', 3, 20.00, 3, 20),
('Plumbing & Sanitary', 'Sanitary Fixture & CP Fittings', 4, 20.00, 3, 22),

-- Electrical Work (Phase 7, Design: Electrical=2)
('Electrical Work', 'Slab Conduit Piping', 1, 25.00, 2, 23),
('Electrical Work', 'Wall Chipping & Box Fixing', 2, 25.00, 2, 24),
('Electrical Work', 'Cable Pulling & Wiring', 3, 30.00, 2, 25),
('Electrical Work', 'Switchboards & Light Fixtures', 4, 20.00, 2, 26),

-- Plaster Work (Phase 8, Design: Architectural=6)
('Plaster Work', 'Internal Wall & Ceiling Plaster', 1, 60.00, 6, 27),
('Plaster Work', 'External Wall Plastering', 2, 40.00, 6, 27),

-- General Floor Tiles (Phase 9, Design: Architectural=6)
('General Floor Tiles', 'Lobby & Common Area Tiles', 1, 30.00, 6, 28),
('General Floor Tiles', 'Flat Internal Floor Tiles', 2, 70.00, 6, 28),

-- Toilet & Kitchen Wall Tiles (Phase 10, Design: Architectural=6)
('Toilet & Kitchen Wall Tiles', 'Bathroom Wall & Floor Tiles', 1, 70.00, 6, 28),
('Toilet & Kitchen Wall Tiles', 'Kitchen Wall & Counter Tiles', 2, 30.00, 6, 29),

-- Aluminium Work (Phase 11, Design: Architectural=6)
('Aluminium Work', 'Window Frame Installation', 1, 50.00, 6, 30),
('Aluminium Work', 'Glass Shutter Fitting', 2, 50.00, 6, 31),

-- EME (Phase 12, Design: Electrical=2, Fire Safety=4)
('EME', 'Generator & Sub-station Set', 1, 30.00, 2, 32),
('EME', 'Lift / Elevator System', 2, 40.00, 2, 33),
('EME', 'Fire Fighting Piping & Alarm', 3, 30.00, 4, 34),

-- Paint Work (Phase 13, Design: Architectural=6)
('Paint Work', 'Internal Wall Painting (Putty + Color)', 1, 65.00, 6, 35),
('Paint Work', 'External Paint (Weather Coat)', 2, 35.00, 6, 36),

-- Others Civil Work (Phase 14, Design: Landscape Design=9, Architectural=6)
('Others Civil Work', 'Internal Road & Pavement', 1, 40.00, 9, 37),
('Others Civil Work', 'Site Boundary Wall', 2, 30.00, 6, 27),
('Others Civil Work', 'Landscaping & Drainage', 3, 30.00, 9, 37),

-- Utility Connection & Bill (Phase 15, Design: Architectural=6)
('Utility Connection & Bill', 'DESCO/DPDC Electricity Line', 1, 40.00, 6, 38),
('Utility Connection & Bill', 'WASA Water & Sewer Connection', 2, 30.00, 6, 38),
('Utility Connection & Bill', 'Gas/LPG System Connection', 3, 30.00, 6, 38);

SET FOREIGN_KEY_CHECKS = 1;
