-- database/schema.sql
-- Run this in phpMyAdmin (XAMPP) or via mysql CLI to create the database

CREATE DATABASE IF NOT EXISTS library_db;
USE library_db;

-- Users table (students, librarians, admins)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'librarian', 'admin') NOT NULL DEFAULT 'student',
    matric_number VARCHAR(50) NULL,
    occupation VARCHAR(100) NULL,   -- student's status, e.g. Undergraduate, Postgraduate
    department VARCHAR(100) NULL,
    level VARCHAR(50) NULL,         -- e.g. 100L, 300L, Year 2
    photo_path VARCHAR(255) NULL,   -- profile photo uploaded at registration
    phone_number VARCHAR(20) NULL,  -- for SMS notifications
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- We will add books, borrow_records, and fines tables in later steps

-- Books table
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    author VARCHAR(100) NOT NULL,
    isbn VARCHAR(30) NULL,
    genre VARCHAR(60) NULL,
    description TEXT NULL,
    cover_image VARCHAR(255) NULL,
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    added_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Tracks recent actions taken by librarians/admins, shown on their dashboard
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications sent by staff to students (in-app, shown on their dashboard)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    type ENUM('book_due', 'fine_reminder', 'new_book', 'announcement') NOT NULL DEFAULT 'announcement',
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    recipient_type ENUM('all', 'department', 'student') NOT NULL DEFAULT 'all',
    recipient_value VARCHAR(100) NULL,
    is_automatic TINYINT(1) NOT NULL DEFAULT 0,
    recipient_count INT NOT NULL DEFAULT 0,
    email_sent_count INT NOT NULL DEFAULT 0,
    sms_sent_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Borrow / return records
CREATE TABLE IF NOT EXISTS borrow_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    student_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    status ENUM('borrowed', 'returned') NOT NULL DEFAULT 'borrowed',
    renewed TINYINT(1) NOT NULL DEFAULT 0,
    fine_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    fine_paid TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Adjustable system settings (key-value store)
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

-- Adds suspend capability to student accounts
ALTER TABLE users ADD COLUMN IF NOT EXISTS account_status ENUM('active','suspended') NOT NULL DEFAULT 'active';

-- Reservations for books that are currently unavailable
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);
