ALTER TABLE `todo_tasks` 
ADD COLUMN `location` VARCHAR(255) DEFAULT NULL AFTER `due_date`,
ADD COLUMN `attachment` VARCHAR(255) DEFAULT NULL AFTER `location`;

CREATE TABLE IF NOT EXISTS `todo_labels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color_code` varchar(20) DEFAULT '#808080',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `todo_task_labels` (
  `todo_task_id` int(11) NOT NULL,
  `todo_label_id` int(11) NOT NULL,
  PRIMARY KEY (`todo_task_id`, `todo_label_id`),
  FOREIGN KEY (`todo_task_id`) REFERENCES `todo_tasks`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`todo_label_id`) REFERENCES `todo_labels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
