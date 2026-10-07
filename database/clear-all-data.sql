-- clear-all-data.sql
-- Deletes ALL users and ALL books, and resets the ID counters back to 1.
-- Run this in phpMyAdmin's SQL tab (with library_db selected).

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE books;
TRUNCATE TABLE users;

SET FOREIGN_KEY_CHECKS = 1;

-- Note: this does NOT delete the face photo image files sitting in
-- uploads/faces/ on disk - those need to be deleted manually from that
-- folder (or via the file manager / File Explorer) since they're just
-- image files, not part of the database.
