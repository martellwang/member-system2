USE `member_system`;

INSERT INTO `system_settings` (`key_name`, `value`)
VALUES ('admin_allowed_ips', '127.0.0.1\n::1')
ON DUPLICATE KEY UPDATE `key_name` = VALUES(`key_name`);
