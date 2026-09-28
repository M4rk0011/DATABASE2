-- Database Schema for Concern Report Mapping System (Matching ERD)
-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS concern_report_system;
USE concern_report_system;

-- ROLES table
CREATE TABLE IF NOT EXISTS roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role_name (role_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- USERS table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE RESTRICT,
    INDEX idx_email (email),
    INDEX idx_role_id (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LOGIN_SESSIONS table
CREATE TABLE IF NOT EXISTS login_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CONCERN_CATEGORIES table
CREATE TABLE IF NOT EXISTS concern_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LOCATIONS table
CREATE TABLE IF NOT EXISTS locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    building_name VARCHAR(100) NOT NULL,
    floor_level VARCHAR(50),
    room_area VARCHAR(100),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_building_name (building_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CONCERN_REPORTS table
CREATE TABLE IF NOT EXISTS concern_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    location_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    status ENUM('pending', 'investigating', 'resolved', 'closed', 'rejected') NOT NULL DEFAULT 'pending',
    concern_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES concern_categories(category_id) ON DELETE RESTRICT,
    FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE RESTRICT,
    INDEX idx_user_id (user_id),
    INDEX idx_category_id (category_id),
    INDEX idx_location_id (location_id),
    INDEX idx_status (status),
    INDEX idx_severity (severity),
    INDEX idx_concern_date (concern_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- REPORT_ATTACHMENTS table
CREATE TABLE IF NOT EXISTS report_attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    file_url VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES concern_reports(report_id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ADMINS table
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ADMIN_SESSIONS table
CREATE TABLE IF NOT EXISTS admin_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- REPORT_REVIEWS table
CREATE TABLE IF NOT EXISTS report_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    admin_id INT NOT NULL,
    status_set ENUM('pending', 'investigating', 'resolved', 'closed', 'rejected') NOT NULL,
    remarks TEXT,
    reviewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES concern_reports(report_id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id),
    INDEX idx_admin_id (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT INTO roles (role_name) VALUES 
('admin'),
('user'),
('security')
ON DUPLICATE KEY UPDATE role_name=role_name;

-- Insert default concern categories
INSERT INTO concern_categories (category_name, description) VALUES 
('Theft', 'Reports of stolen items or theft incidents'),
('Vandalism', 'Reports of property damage or vandalism'),
('Harassment', 'Reports of harassment or inappropriate behavior'),
('Accident', 'Reports of accidents or injuries'),
('Medical', 'Medical emergencies or health-related incidents'),
('Fire', 'Fire-related incidents or fire hazards'),
('Security', 'Security breaches or suspicious activities'),
('Facility', 'Facility maintenance or infrastructure issues'),
('Technology', 'Technology or equipment-related issues'),
('Other', 'Other types of concerns not covered above')
ON DUPLICATE KEY UPDATE category_name=category_name;

-- Insert default locations
INSERT INTO locations (building_name, floor_level, room_area, details) VALUES 
('Main Building', 'Ground Floor', 'Main Lobby', 'Building entrance and main lobby area'),
('Main Building', '1st Floor', 'Classrooms', 'Classroom areas on first floor'),
('Main Building', '2nd Floor', 'Library', 'Library and study areas'),
('Main Building', '3rd Floor', 'Laboratories', 'Science and computer laboratories'),
('Administration Building', 'Ground Floor', 'Offices', 'Administrative offices'),
('Administration Building', '1st Floor', 'Conference Rooms', 'Meeting and conference rooms'),
('Sports Complex', 'Ground Floor', 'Gymnasium', 'Main gymnasium and sports facilities'),
('Sports Complex', '2nd Floor', 'Fitness Center', 'Fitness and exercise equipment'),
('Student Center', 'Ground Floor', 'Cafeteria', 'Student dining area'),
('Student Center', '1st Floor', 'Student Lounge', 'Student recreation and lounge area')
ON DUPLICATE KEY UPDATE building_name=building_name;

-- Insert default admin user (password: admin123)
INSERT INTO admins (full_name, email, password_hash) 
VALUES ('System Administrator', 'admin@concern.system', '$2y$12$JqqugC85na6kxHGl5t300O/VQVK3YALmhtfo3kxY1bdGankA5SuHy')
ON DUPLICATE KEY UPDATE email=email;

-- Insert default user (password: user123)
INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash) 
VALUES (2, 'John', 'Doe', 'john.doe@concern.system', '+63-777-777-7777', '$2y$12$JqqugC85na6kxHGl5t300O/VQVK3YALmhtfo3kxY1bdGankA5SuHy')
ON DUPLICATE KEY UPDATE email=email;

-- Insert default security user (password: security123)
INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash) 
VALUES (3, 'Jane', 'Smith', 'jane.smith@concern.system', '+63-888-888-8888', '$2y$12$fDeDIE5/snajV8M1HwB48uMlzy2Zp8ZTGNlDFcqcWyTpHbHW95FOK')
ON DUPLICATE KEY UPDATE email=email;