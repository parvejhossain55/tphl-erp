CREATE TABLE
    tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        start_date DATE,
        deadline DATETIME,
        employee_accept TINYINT (1) DEFAULT 0,
        status ENUM ('pending', 'in_progress', 'completed', 'expired') DEFAULT 'pending',
        progress INT DEFAULT 0,
        priority ENUM ('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees (id),
        INDEX idx_employee_id (employee_id)
    ) ENGINE = InnoDB;

CREATE TABLE
    departments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        status TINYINT (1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE = InnoDB;

CREATE TABLE
    designations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        department_id INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

CREATE TABLE
    employees (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(200) NOT NULL,
        employee_id VARCHAR(50) NOT NULL UNIQUE,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        father_name VARCHAR(100) NULL,
        phone VARCHAR(20) NOT NULL,
        emergency_contact VARCHAR(20) NULL,
        qualification VARCHAR(100) NULL,
        photo VARCHAR(200) NULL,
        experience TEXT NULL,
        address TEXT NOT NULL,
        pass_num VARCHAR(100) NULL,
        basic_salary VARCHAR(100) NULL,
        department_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        designation_id INT UNSIGNED NULL,
        joining_date DATE NULL,
        account_holder_name VARCHAR(100) NULL,
        account_number VARCHAR(50) NULL,
        bank_name VARCHAR(100) NULL,
        branch_name VARCHAR(100) NULL,
        social_media1 VARCHAR(255) DEFAULT NULL,
        social_media2 VARCHAR(255) DEFAULT NULL,
        social_media3 VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
        FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE CASCADE,
        FOREIGN KEY (designation_id) REFERENCES designations (id) ON DELETE SET NULL
    ) ENGINE = InnoDB;

CREATE TABLE
    `notifications` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `employee_id` INT UNSIGNED NOT NULL,
        `task_id` INT DEFAULT NULL,
        `assigned_by` INT NOT NULL,
        `message` VARCHAR(255) NOT NULL,
        `is_read` TINYINT (1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`employee_id`) REFERENCES employees (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`task_id`) REFERENCES tasks (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`assigned_by`) REFERENCES users (`id`) ON DELETE CASCADE
    );


-- id INT UNSIGNED
-- foreign_id INT UNSIGNED
-- 15/12/25 
-- 1. Create lead_sources table


-- 1. Create lead_sources table
CREATE TABLE
    IF NOT EXISTS `lead_sources` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(191) NOT NULL,
        `slug` VARCHAR(191) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `is_active` TINYINT (1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;




CREATE TABLE
    IF NOT EXISTS `leave_types` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(191) NOT NULL,
        `is_active` TINYINT (1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE
    `sliders` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `subtitle` VARCHAR(255) NOT NULL,
        `link` VARCHAR(255) DEFAULT NULL,
        `image` VARCHAR(255) DEFAULT NULL,
        `status` TINYINT (1) DEFAULT 1 COMMENT '1 = Active, 0 = Inactive',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE
    site_settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        website_title VARCHAR(255) DEFAULT NULL,
        website_url VARCHAR(255) DEFAULT NULL,
        footer_text VARCHAR(255) DEFAULT NULL,
        logo VARCHAR(255) DEFAULT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        alt_contact_phone VARCHAR(50) DEFAULT NULL,
        email VARCHAR(100) DEFAULT NULL,
        alt_email VARCHAR(100) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        copyright_text VARCHAR(255) DEFAULT NULL,
        facebook_link VARCHAR(255) DEFAULT NULL,
        messenger_link VARCHAR(255) DEFAULT NULL,
        instagram_link VARCHAR(255) DEFAULT NULL,
        tiktok_link VARCHAR(255) DEFAULT NULL,
        x_link VARCHAR(255) DEFAULT NULL,
        youtube_link VARCHAR(255) DEFAULT NULL,
        header_code TEXT DEFAULT NULL,
        body_code TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

CREATE TABLE
    blogs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title TEXT NOT NULL,
        excerpt LONGTEXT,
        content LONGTEXT,
        image VARCHAR(255),
        author VARCHAR(100),
        published_date DATE,
        comments INT DEFAULT 0,
        read_time VARCHAR(50),
        status TINYINT DEFAULT 1 COMMENT '1=Published, 0=Draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

-- 17/12
CREATE TABLE
    communities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        status TINYINT DEFAULT 1 COMMENT '1=active, 0=inactive',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

CREATE TABLE
    locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

-- leads

CREATE TABLE
    `leads` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `lead_name` VARCHAR(255) NOT NULL,
        `lead_type` ENUM ('Hot', 'Warm', 'Cold') NOT NULL,
        `company` VARCHAR(255) NULL,
        `phone` VARCHAR(30) NOT NULL,
        `alt_phone_number` VARCHAR(30) NULL,
        `email` VARCHAR(150) NOT NULL,
        `preferred_contact_time` VARCHAR(50) NULL COMMENT 'Preferred contact time in BD time e.g. Morning, Evening, 7pm-10pm';
         `residency_type` ENUM('local','expatriate') DEFAULT 'local',
         `country` INT NULL,
        `source_type_id` INT UNSIGNED NOT NULL,
        `owner_id` INT UNSIGNED NOT NULL,
        `address` VARCHAR(255) NULL,
        `pref_location` VARCHAR(255) NULL,
        `designation` VARCHAR(255) NULL,
        `project_type` ENUM ('ATI Society', 'ASHU Society') NOT NULL,
        `interest_on` VARCHAR(250) NOT NULL,
        `pref_flat_size` VARCHAR(250) NOT NULL,
        `communication_type` ENUM ('WA Messaging', 'WA Conversion') NULL,
        `tags` TEXT NULL,
        `description` TEXT NULL,
        `visibility` ENUM ('public', 'private', 'selected') DEFAULT 'public',
        `status` ENUM ('active', 'inactive') DEFAULT 'active',
        `lead_quality` ENUM ('Not Interested', 'Prospect', 'Pending') DEFAULT 'Pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_leads_owner FOREIGN KEY (`owner_id`) REFERENCES users (`id`) ON DELETE CASCADE,
        CONSTRAINT fk_leads_source_type FOREIGN KEY (`source_type_id`) REFERENCES lead_sources (`id`) ON DELETE CASCADE
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4


CREATE TABLE
    projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        location_id INT,
        community_id INT NOT NULL,
        building_type VARCHAR(50),
        total_share INT,
        unit_per_floor INT,
        types VARCHAR(255),
        image VARCHAR(255),
        image2 VARCHAR(255),
        image3 VARCHAR(255),
        front_road VARCHAR(50),
        hover_text VARCHAR(255),
        price_range ENUM (
            'Under 50 Lac BDT',
            '50 Lac - 1 Crore BDT',
            '1 Crore - 2 Crore BDT',
            '2 Crore - 5 Crore BDT',
            'Above 5 Crore BDT'
        ) DEFAULT NULL,
        tag VARCHAR(50),
        brochure_link VARCHAR(255),
        description TEXT,
        apartment_count INT,
        amenities_details LONGTEXT,
        amenities_image VARCHAR(255),
        map_image VARCHAR(255),
        map_link VARCHAR(255),
        passenger_lift INT,
        cargo_lift INT,
        rooftop_gardening TINYINT DEFAULT 1,
        swimming_pool TINYINT DEFAULT 1,
        convention TINYINT DEFAULT 1,
        electricity_backup TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (location_id) REFERENCES locations (id) ON DELETE SET NULL,
        FOREIGN KEY (community_id) REFERENCES communities (id) ON DELETE RESTRICT
    );

CREATE TABLE
    units (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL, -- Unit A, Unit B
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

--flat
CREATE TABLE
    flats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        unit_id INT NOT NULL,
        flat_name VARCHAR(50),
        sqft INT,
        bhk INT,
        bedroom INT,
        bathroom INT,
        balcony INT,
        drawing_dining_kitchen TINYINT (1),
        common_dining TINYINT (1),
        floor_no INT,
        is_booked INT,
        facing_site VARCHAR(50), -- East / West
        price_value DECIMAL(12, 2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
        FOREIGN KEY (unit_id) REFERENCES units (id) ON DELETE RESTRICT
    );

--  20/12 
-- lead_follow_ups
----------------



CREATE TABLE lead_follow_ups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id INT UNSIGNED NOT NULL,
    project_id INT  NULL,
    flat_id INT  NULL,

    follow_up_date DATE NOT NULL,
    follow_up_time TIME NULL,

    follow_up_type ENUM(
        'call',
        'visit',
        'meeting',
        'whatsapp',
        'email'
    ) NOT NULL,

    status ENUM(
        'pending',
        'completed',
        'missed'
    ) DEFAULT 'pending',

    discussion_note TEXT,

    next_follow_up_date DATE NULL,
    next_action ENUM('site visit','booking','call_back','take_time') DEFAULT NULL,

    created_by INT UNSIGNED NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (flat_id) REFERENCES flats(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;




CREATE TABLE lead_bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    lead_id INT UNSIGNED NOT NULL,
    project_id INT  NOT NULL,
    flat_id INT  NOT NULL,

    agreement_value DECIMAL(12,2) NOT NULL,
    booking_date DATE NOT NULL,

    booking_status ENUM(
        'booked',
        'cancelled',
        'completed'
    ) DEFAULT 'booked',

    created_by INT  NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (lead_id) REFERENCES leads(id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (flat_id) REFERENCES flats(id)
);




CREATE TABLE lead_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Master reference
    booking_id INT UNSIGNED NOT NULL,   -- lead booking / onboard reference

    -- Payment identity
    payment_type ENUM('booking_money','registration','emi') NOT NULL,

    -- Amount info
    amount DECIMAL(12,2) NOT NULL,

    -- Method & status
    payment_method ENUM('cash','bank','online','cheque') DEFAULT 'cash',
    payment_status ENUM('paid','pending','failed') DEFAULT 'paid',

    -- Dates
    payment_date DATE NOT NULL,

    -- Optional EMI info
    emi_no INT NULL,                    -- only for EMI payments
    emi_total INT NULL,                 -- total EMI count (optional)

    -- Transaction reference
    transaction_ref VARCHAR(100) NULL,

--    new add 
     bank_name VARCHAR(100) DEFAULT NULL --AFTER transaction_ref,
     bank_account_no VARCHAR(50) DEFAULT NULL --AFTER bank_name,
     cheque_no VARCHAR(50) DEFAULT NULL --AFTER bank_account_no,
     online_type VARCHAR(50) DEFAULT NULL -- AFTER cheque_no;


    -- Audit
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Relations
    CONSTRAINT fk_payment_booking
        FOREIGN KEY (booking_id)
        REFERENCES lead_bookings(id)
        ON DELETE CASCADE
);


CREATE TABLE emi_schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_booking_id INT UNSIGNED NOT NULL,
    emi_no INT NUll,
    emi_amount DECIMAL(12,2) NOT NULL,
    due_date DATE NOT NULL,

    status ENUM('pending','paid','missed') DEFAULT 'pending',
    lead_payment_id INT UNSIGNED NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (lead_booking_id) REFERENCES lead_bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (lead_payment_id) REFERENCES lead_payments(id) ON DELETE SET NULL
);


--29/12/25
CREATE TABLE payslip_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('earning','deduction') NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);



CREATE TABLE payslips (
    id INT  AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    --- 31/12/25
    payment_method ENUM('bank','cash','mobile_banking') NOT NULL DEFAULT 'bank',
    company_bank_id INT DEFAULT NULL,
    --- 31/12/25
    designation_id INT UNSIGNED  DEFAULT NULL,
    payslip_month DATE NOT NULL,
    total_earning DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_deduction DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    net_salary DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','approved','paid') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (designation_id) REFERENCES designations(id) ON DELETE SET NULL,
    FOREIGN KEY (company_bank_id) REFERENCES company_bank_accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE payslip_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payslip_id INT  NOT NULL,
    payslip_item_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (payslip_id) REFERENCES payslips(id) ON DELETE CASCADE,
    FOREIGN KEY (payslip_item_id) REFERENCES payslip_items(id) ON DELETE CASCADE
);

--- 31/12/25
CREATE TABLE company_bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(255) NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_number VARCHAR(100) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE flat_purchase_rules (
   id INT AUTO_INCREMENT PRIMARY KEY,
   rules_description TEXT,
   created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
   updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);









