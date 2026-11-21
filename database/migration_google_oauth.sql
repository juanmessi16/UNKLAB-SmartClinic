-- Migrasi Google OAuth 2.0 (Login dengan Akun UNKLAB) - UNKLAB SmartClinic
USE `db_project`;

ALTER TABLE `users`
  MODIFY COLUMN `email` VARCHAR(150) NULL UNIQUE,
  ADD COLUMN IF NOT EXISTS `google_id` VARCHAR(100) NULL UNIQUE AFTER `email`,
  ADD COLUMN IF NOT EXISTS `avatar_url` VARCHAR(255) NULL AFTER `avatar`;
