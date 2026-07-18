USE `member_system`;

UPDATE `admin_login_logs`
SET `duration_seconds` = GREATEST(0, TIMESTAMPDIFF(SECOND, `login_at`, `logout_at`))
WHERE `logout_at` IS NOT NULL;
