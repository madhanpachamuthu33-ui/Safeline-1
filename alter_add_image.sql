-- Run this ONLY if you already created the database before the photo-upload
-- feature was added. Adds the missing column without losing existing data.
--
--   mysql -u root -p safeline < sql/alter_add_image.sql
--
-- (On InfinityFree / phpMyAdmin: open your database → SQL tab → paste and run.)

USE safeline;

ALTER TABLE reports ADD COLUMN image_path VARCHAR(255) NULL AFTER contact_info;
