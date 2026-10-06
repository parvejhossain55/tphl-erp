CREATE TABLE IF NOT EXISTS `todo_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `color_code` varchar(20) DEFAULT '#000000',
  `is_inbox` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `todo_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `todo_project_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `order_index` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`todo_project_id`) REFERENCES `todo_projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `todo_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `todo_project_id` int(11) DEFAULT NULL,
  `todo_section_id` int(11) DEFAULT NULL,
  `parent_todo_task_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` tinyint(4) DEFAULT 4 COMMENT '1=P1, 2=P2, 3=P3, 4=P4',
  `status` enum('pending','completed') DEFAULT 'pending',
  `due_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`todo_project_id`) REFERENCES `todo_projects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`todo_section_id`) REFERENCES `todo_sections`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`parent_todo_task_id`) REFERENCES `todo_tasks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `todo_task_recurrences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `todo_task_id` int(11) NOT NULL,
  `repeat_type` enum('daily','weekly','monthly','custom') NOT NULL,
  `repeat_interval` int(11) DEFAULT 1,
  `repeat_days` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`todo_task_id`) REFERENCES `todo_tasks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `todo_task_reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `todo_task_id` int(11) NOT NULL,
  `trigger_time` datetime NOT NULL,
  `max_reminders` int(11) DEFAULT 1,
  `reminders_sent_count` int(11) DEFAULT 0,
  `repeat_interval_minutes` int(11) DEFAULT 15,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`todo_task_id`) REFERENCES `todo_tasks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
