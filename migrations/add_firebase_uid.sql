-- Migration: Add firebase_uid column to users table
-- Required for Firebase Auth integration with mobile app

ALTER TABLE `users` 
ADD COLUMN `firebase_uid` VARCHAR(128) NULL DEFAULT NULL AFTER `role`,
ADD UNIQUE INDEX `idx_firebase_uid` (`firebase_uid`);

-- Verify
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'users' AND TABLE_SCHEMA = 'komputer_akuntan';
