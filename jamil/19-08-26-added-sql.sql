ALTER TABLE `letter_templates` MODIFY COLUMN `type` ENUM('appointment','joining','promotion','increment','experience','noc','show_cause','warning','caution','improvement') NOT NULL;
ALTER TABLE `letters` MODIFY COLUMN `letter_type` ENUM('appointment','joining','promotion','increment','experience','noc','salary_certificate','show_cause','warning','caution','improvement') NOT NULL;


-- database updates for email station module
CREATE TABLE email_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_name (name)
);