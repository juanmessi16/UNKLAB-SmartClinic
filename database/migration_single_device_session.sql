-- Migrasi Single-Device Session Restriction
-- Menambahkan active_session_token dan last_login_at pada tabel users

ALTER TABLE `users`
  ADD COLUMN `active_session_token` VARCHAR(128) NULL AFTER `role`,
  ADD COLUMN `last_login_at` DATETIME NULL AFTER `active_session_token`;
