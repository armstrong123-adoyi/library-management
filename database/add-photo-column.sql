-- add-photo-column.sql
-- Run this in phpMyAdmin's SQL tab if you already have a users table
-- and just need to add the new profile photo column (keeps existing data).

ALTER TABLE users ADD COLUMN photo_path VARCHAR(255) NULL AFTER level;
