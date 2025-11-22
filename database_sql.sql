-- =====================================================
-- RFID ATTENDANCE SYSTEM - DATABASE RESET SCRIPT
-- This will DELETE all data and start fresh
-- USE WITH CAUTION!
-- =====================================================

-- Drop existing database and recreate
DROP DATABASE IF EXISTS rfid_attendance;
CREATE DATABASE rfid_attendance;
USE rfid_attendance;

-- =====================================================
-- TABLE: admin
-- Stores administrator credentials
-- =====================================================
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin account
-- Username: admin
-- Password: admin@123
INSERT INTO admin (username, password) VALUES ('admin', 'admin@123');

-- =====================================================
-- TABLE: users
-- Stores registered user information
-- NOTE: photo field removed - photos now stored per session
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rfid_code VARCHAR(20) UNIQUE NOT NULL,
    usn VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rfid (rfid_code),
    INDEX idx_usn (usn)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: sessions
-- Stores attendance sessions
-- =====================================================
CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_name VARCHAR(100) NOT NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    INDEX idx_active (is_active),
    INDEX idx_start_time (start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: attendance
-- Stores attendance records with session-specific photos
-- =====================================================
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    user_id INT NOT NULL,
    rfid_code VARCHAR(20) NOT NULL,
    session_photo VARCHAR(255) NULL,
    photo_captured TINYINT(1) DEFAULT 0,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session (session_id),
    INDEX idx_user (user_id),
    INDEX idx_timestamp (timestamp),
    UNIQUE KEY unique_attendance (session_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: pending_rfid
-- Temporarily stores unregistered RFID scans
-- =====================================================
CREATE TABLE pending_rfid (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rfid_code VARCHAR(20) UNIQUE NOT NULL,
    scan_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_scan_time (scan_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- NOTES:
-- 1. Default admin credentials: admin / admin@123
-- 2. All previous data will be permanently deleted
-- 3. Make sure to backup important data before running
-- 4. Photos are now stored per session in session_photos folder
-- 5. When session ends, attendance and photos are cleared
-- 6. Create session_photos/ folder with write permissions
-- =====================================================