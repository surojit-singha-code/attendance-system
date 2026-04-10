-- ============================================================
-- Attendance Management System - Database Setup
-- Run this in phpMyAdmin or MySQL CLI before starting the app
-- ============================================================

CREATE DATABASE IF NOT EXISTS attendance_db;
USE attendance_db;

-- Users table: login credentials for both teachers and students
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('teacher', 'student') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students table: student profile, linked to a user account
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    roll VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Attendance table: daily attendance records per student
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('Present', 'Absent') NOT NULL DEFAULT 'Absent',
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (student_id, date)
);

-- ============================================================
-- Default Teacher Account
-- Username: teacher  |  Password: teacher123
-- ============================================================
INSERT INTO users (username, password, role)
VALUES ('teacher', '$2y$10$TKh8H1.PfunDb..4ukJe7e1aMJ2mYaOHwxkGOHFSSmJFBeMcDGVXG', 'teacher')
ON DUPLICATE KEY UPDATE id = id;
