CREATE TABLE email_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    
    INDEX idx_status (status),
    INDEX idx_name (name)
);

----------- Database Design --------------
-- 1. Table to store created Email Alerts
CREATE TABLE email_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email_template_id BIGINT UNSIGNED NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body LONGTEXT NOT NULL,
    
    -- Target filter metadata
    target_type ENUM('all', 'department', 'designation', 'specific_employees') NOT NULL,
    
    -- Status and metadata
    status ENUM('draft', 'scheduled', 'processing', 'sent', 'failed') DEFAULT 'draft',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (email_template_id) REFERENCES email_templates(id) ON DELETE CASCADE
);

CREATE TABLE email_alert_targets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email_alert_id BIGINT UNSIGNED NOT NULL,
    target_type ENUM('department', 'designation', 'specific_employees') NOT NULL,
    target_id BIGINT UNSIGNED NOT NULL, -- Holds department_id, designation_id, or employee_id
    
    FOREIGN KEY (email_alert_id) REFERENCES email_alerts(id) ON DELETE CASCADE,
    INDEX idx_alert_target (email_alert_id, target_type)
);