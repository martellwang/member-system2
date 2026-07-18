USE `member_system`;

ALTER TABLE `members`
  MODIFY `status` ENUM('email_unverified','active','pending','suspended') NOT NULL DEFAULT 'pending';
