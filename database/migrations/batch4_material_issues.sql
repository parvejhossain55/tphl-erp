-- =============================================
-- Material Issues (Stock OUT) Tables
-- Run this SQL to create the required tables
-- =============================================

CREATE TABLE IF NOT EXISTS `con_material_issues` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `project_id`   INT            NOT NULL,
  `warehouse_id` INT            NOT NULL,
  `issued_by`    INT            DEFAULT NULL,
  `issue_date`   DATE           NOT NULL,
  `purpose`      VARCHAR(500)   DEFAULT NULL COMMENT 'Purpose or recipient of issue',
  `status`       ENUM('Draft','Issued') DEFAULT 'Draft',
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`)   REFERENCES `projects`(`id`),
  FOREIGN KEY (`warehouse_id`) REFERENCES `con_warehouses`(`id`),
  FOREIGN KEY (`issued_by`)    REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `con_material_issue_items` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `issue_id`     INT            NOT NULL,
  `material_id`  INT            NOT NULL,
  `quantity`     DECIMAL(12,3)  NOT NULL DEFAULT 0.000,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`issue_id`)    REFERENCES `con_material_issues`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`material_id`) REFERENCES `con_materials`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
