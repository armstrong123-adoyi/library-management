-- add-phone-and-channels.sql
-- Run in phpMyAdmin's SQL tab. Does NOT delete any existing data.
-- Adds: phone number field for SMS, and channel-tracking columns on
-- notifications so we know if email/SMS actually went out.

ALTER TABLE users ADD COLUMN phone_number VARCHAR(20) NULL AFTER photo_path;

ALTER TABLE notifications
    ADD COLUMN is_automatic TINYINT(1) NOT NULL DEFAULT 0 AFTER recipient_value,
    ADD COLUMN recipient_count INT NOT NULL DEFAULT 0 AFTER is_automatic,
    ADD COLUMN email_sent_count INT NOT NULL DEFAULT 0 AFTER recipient_count,
    ADD COLUMN sms_sent_count INT NOT NULL DEFAULT 0 AFTER email_sent_count;
