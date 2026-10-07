-- add-matric-number-column.sql
-- Fixes a bug where the registration form collected a matric number but
-- never actually saved it (the column didn't exist). Run in phpMyAdmin's
-- SQL tab. Does NOT delete any existing data.

ALTER TABLE users ADD COLUMN matric_number VARCHAR(50) NULL AFTER role;
