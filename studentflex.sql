-- Create the database
CREATE DATABASE IF NOT EXISTS studentflex;

USE studentflex;

-- Create users table for both admin and student authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'student') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Initial admin user (password should be hashed in actual implementation)
INSERT INTO users (username, password, full_name, email, role) 
VALUES ('admin', 'admin123', 'System Administrator', 'admin@studentflex.com', 'admin');


-- Subjects Table: Stores all subjects in the curriculum
CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_code VARCHAR(10) NOT NULL UNIQUE,
    subject_name VARCHAR(100) NOT NULL,
    class VARCHAR(10) NOT NULL, -- To track which class/grade this subject belongs to
    is_active ENUM('yes', 'no') DEFAULT 'yes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Exams Table: Stores different exam periods (e.g., Midterm, Final, etc.)
CREATE TABLE exams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_name VARCHAR(50) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    class VARCHAR(10) NOT NULL,
    max_marks INT DEFAULT 100, -- Default maximum marks for this exam
    is_active ENUM('yes', 'no') DEFAULT 'yes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Student Marks Table: Stores individual marks for each student per subject per exam
CREATE TABLE marks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    exam_id INT NOT NULL,
    marks_obtained DECIMAL(5,2) DEFAULT 0.00,
    marks_max DECIMAL(5,2) DEFAULT 100.00,
    grade VARCHAR(2),
    remarks TEXT,
    created_by INT, -- User ID of teacher/admin who recorded the marks
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    UNIQUE KEY unique_mark (student_id, subject_id, exam_id) -- To prevent duplicate entries
);

-- Result Table: Stores the overall result for each student per exam
CREATE TABLE results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    exam_id INT NOT NULL,
    total_marks DECIMAL(6,2) DEFAULT 0.00,
    total_max_marks DECIMAL(6,2) DEFAULT 0.00,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    grade VARCHAR(2),
    rank INT,
    result_status ENUM('pass', 'fail', 'absent', 'pending') DEFAULT 'pending',
    remarks TEXT,
    is_published ENUM('yes', 'no') DEFAULT 'no', -- Controls visibility to students
    published_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    UNIQUE KEY unique_result (student_id, exam_id) -- To prevent duplicate entries
);