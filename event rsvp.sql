CREATE DATABASE event_rsvp_system;
USE event_rsvp_system;

-- Use the database
USE event_rsvp_system;

-- Table 1: Guests
CREATE TABLE guests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    rsvp_token VARCHAR(64) NOT NULL UNIQUE,
    rsvp_status ENUM('pending', 'confirmed', 'declined', 'waitlisted') DEFAULT 'pending',
    seat_reserved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table 2: Email Logs
CREATE TABLE email_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guest_id BIGINT UNSIGNED NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'failed') DEFAULT 'sent',
    error_message TEXT,
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE
);

-- Table 3: RSVP Reservations / Attendance
CREATE TABLE reservations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guest_id BIGINT UNSIGNED NOT NULL,
    seat_number INT UNSIGNED DEFAULT NULL,
    reserved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE,
    UNIQUE (guest_id)
);

-- Table 4: Admin Users (for dashboard access)
CREATE TABLE admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table 5: Seat Limit (1,000,000 total seat capacity)
CREATE TABLE seat_limit (
    id INT PRIMARY KEY DEFAULT 1,
    max_seats INT UNSIGNED NOT NULL DEFAULT 1000000,
    current_reserved INT UNSIGNED NOT NULL DEFAULT 0
);

-- Insert the default seat limit row (initial setup)
INSERT INTO seat_limit (id, max_seats, current_reserved) VALUES (1, 1000000, 0);
