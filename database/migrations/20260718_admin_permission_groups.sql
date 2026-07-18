USE `member_system`;

INSERT INTO `system_settings` (`key_name`, `value`)
VALUES ('admin_permission_groups', '[]')
ON DUPLICATE KEY UPDATE `key_name` = VALUES(`key_name`);
