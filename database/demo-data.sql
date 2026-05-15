-- Demo seed data for member-system2.
-- This file is safe to commit: all names, emails, phone numbers, ID numbers,
-- tax IDs, and company details below are fictitious sample records.

USE `member_system`;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `members`;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `members`
  (`type`, `name`, `email`, `phone`, `password`, `status`, `id_number`, `birth_date`, `gender`,
   `email_verified_token`, `email_verified_at`, `created_at`, `updated_at`)
VALUES
  ('personal', 'Demo User One', 'demo.personal.one@example.test', '0900-000-001',
   '$2y$10$demoPasswordHashForLocalTestingOnly', 'active', 'A100000001', '1990-01-15', 'male',
   NULL, NOW(), NOW(), NOW()),
  ('personal', 'Demo User Two', 'demo.personal.two@example.test', '0900-000-002',
   '$2y$10$demoPasswordHashForLocalTestingOnly', 'pending', 'B200000002', '1992-06-20', 'female',
   'demo-token-personal-two', NULL, NOW(), NOW()),
  ('personal', 'Demo User Three', 'demo.personal.three@example.test', '0900-000-003',
   '$2y$10$demoPasswordHashForLocalTestingOnly', 'active', 'C100000003', '1988-11-05', 'other',
   NULL, NOW(), NOW(), NOW());

INSERT INTO `members`
  (`type`, `name`, `email`, `phone`, `password`, `status`, `tax_id`, `company_name`, `website`, `industry`,
   `email_verified_token`, `email_verified_at`, `created_at`, `updated_at`)
VALUES
  ('company', 'Demo Company Contact A', 'demo.company.a@example.test', '02-0000-0001',
   '$2y$10$demoPasswordHashForLocalTestingOnly', 'active', '00000001', 'Demo Alpha Co., Ltd.',
   'https://alpha.example.test', 'tech', NULL, NOW(), NOW(), NOW()),
  ('company', 'Demo Company Contact B', 'demo.company.b@example.test', '02-0000-0002',
   '$2y$10$demoPasswordHashForLocalTestingOnly', 'pending', '00000002', 'Demo Beta Studio',
   'https://beta.example.test', 'svc', 'demo-token-company-b', NULL, NOW(), NOW()),
  ('company', 'Demo Company Contact C', 'demo.company.c@example.test', '04-0000-0003',
   '$2y$10$demoPasswordHashForLocalTestingOnly', 'active', '00000003', 'Demo Retail Group',
   'https://retail.example.test', 'retail', NULL, NOW(), NOW(), NOW());
