-- add-settings-and-status.sql
-- Run in phpMyAdmin's SQL tab. Does NOT delete any existing data.

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
);

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
    ('fine_per_day', '50'),
    ('loan_duration_days', '14'),
    ('max_books_per_student', '3'),
    ('library_open_time', '08:00'),
    ('library_close_time', '18:00');

ALTER TABLE users ADD COLUMN account_status ENUM('active','suspended') NOT NULL DEFAULT 'active' AFTER phone_number;
