-- migration.sql
-- Moves the database from the old face-recognition setup to the new
-- email + password setup. WARNING: this deletes existing users AND
-- existing books (since books depends on users). You'll re-register
-- students, staff accounts come from config/staff-accounts.php, and
-- you'll need to re-add any books.
--
-- IMPORTANT: if you're pasting this into phpMyAdmin's SQL tab, make
-- sure the "Enable foreign key checks" checkbox below the query box
-- is UNCHECKED before clicking Go.

DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'librarian', 'admin') NOT NULL DEFAULT 'student',
    occupation VARCHAR(100) NULL,
    department VARCHAR(100) NULL,
    level VARCHAR(50) NULL,
    photo_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE books (
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

CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- Next steps after running this:
-- 1. Librarian and admin accounts are defined directly in
--    config/staff-accounts.php - edit that file to set who they are.
-- 2. Register students normally through register.php.
-- 3. Log in through login.php using email + password for everyone.
-- 4. Re-add any books through the Manage Books page.
-- ---------------------------------------------------------------------
