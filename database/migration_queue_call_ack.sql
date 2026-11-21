-- Migrasi Notifikasi Panggilan Antrean Persisten (UNKLAB SmartClinic)
ALTER TABLE `appointments_queue`
  ADD COLUMN `call_acknowledged` TINYINT(1) NOT NULL DEFAULT 0 AFTER `called_at`,
  ADD COLUMN `call_acknowledged_at` DATETIME NULL AFTER `call_acknowledged`;
