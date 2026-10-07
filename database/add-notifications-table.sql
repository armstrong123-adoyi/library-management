-- add-notifications-table.sql
-- Run this in phpMyAdmin's SQL tab. Adds the notifications feature
-- without touching any existing data.

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    type ENUM('book_due', 'fine_reminder', 'new_book', 'announcement') NOT NULL DEFAULT 'announcement',
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    recipient_type ENUM('all', 'department', 'student') NOT NULL DEFAULT 'all',
    recipient_value VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);
