-- Migration: add profile_blob and profile_blob_mime to site_users
-- Run this against your railway database (phpMyAdmin or mysql CLI)

-- If your MySQL server rejects IF NOT EXISTS, run the plain ALTER statements below.
ALTER TABLE site_users ADD COLUMN profile_blob MEDIUMBLOB DEFAULT NULL;
ALTER TABLE site_users ADD COLUMN profile_blob_mime VARCHAR(50) DEFAULT NULL;
