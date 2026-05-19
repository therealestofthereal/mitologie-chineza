-- Migration: add profile_blob and profile_blob_mime to site_users
-- Run this against your railway database (phpMyAdmin or mysql CLI)

ALTER TABLE site_users ADD COLUMN IF NOT EXISTS profile_blob MEDIUMBLOB DEFAULT NULL;
ALTER TABLE site_users ADD COLUMN IF NOT EXISTS profile_blob_mime VARCHAR(50) DEFAULT NULL;
