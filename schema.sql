CREATE DATABASE IF NOT EXISTS duet_ems CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE duet_ems;

-- D1: Users Table (Process 1.0, 7.0)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('Admin', 'Editor', 'Viewer') DEFAULT 'Viewer',
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- D2: Buildings Table (Process 2.0)
CREATE TABLE IF NOT EXISTS buildings (
    building_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(30) NOT NULL UNIQUE,
    floors INT NOT NULL DEFAULT 1,
    department VARCHAR(100) NOT NULL,
    location VARCHAR(150) NOT NULL,
    status ENUM('Active', 'Maintenance', 'Inactive') DEFAULT 'Active'
);

-- D3: Meters Table (Process 3.0)
CREATE TABLE IF NOT EXISTS meters (
    meter_id INT AUTO_INCREMENT PRIMARY KEY,
    meter_number VARCHAR(50) NOT NULL UNIQUE,
    building_id INT NOT NULL,
    installation_date DATE NOT NULL,
    status ENUM('Online', 'Offline', 'Fault') DEFAULT 'Online',
    FOREIGN KEY (building_id) REFERENCES buildings(building_id) ON DELETE CASCADE
);

-- D4: Energy Readings Table (Process 4.0)
CREATE TABLE IF NOT EXISTS energy_readings (
    reading_id INT AUTO_INCREMENT PRIMARY KEY,
    meter_id INT NOT NULL,
    recorded_at DATETIME NOT NULL,
    voltage DECIMAL(6,2) NOT NULL,
    current DECIMAL(6,2) NOT NULL,
    power DECIMAL(8,2) NOT NULL,
    energy_kwh DECIMAL(10,3) NOT NULL,
    cost DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (meter_id) REFERENCES meters(meter_id) ON DELETE CASCADE
);

-- D5: Alerts Table (Process 6.0)
CREATE TABLE IF NOT EXISTS alerts (
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    type ENUM('Overvoltage', 'Overload', 'Power Spike', 'Offline') NOT NULL,
    location VARCHAR(150) NOT NULL,
    time DATETIME NOT NULL,
    status ENUM('Active', 'Acknowledged', 'Resolved') DEFAULT 'Active'
);

-- D6: Reports Table (Process 5.0)
CREATE TABLE IF NOT EXISTS reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(150) NOT NULL,
    type VARCHAR(50) NOT NULL,
    period VARCHAR(50) NOT NULL,
    generated_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'Generated'
);

-- D7: Messages Table (Process 8.0)
CREATE TABLE IF NOT EXISTS messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender VARCHAR(100) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    body TEXT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Unread', 'Read') DEFAULT 'Unread'
);

-- Seed Default Admin (Password: admin123)
INSERT INTO users (full_name, email, role, password) 
VALUES ('System Admin', 'admin@duet.ac.bd', 'Admin', '$2y$10$tZ92E9v3LzV2yYQ99GjFneW5K6g7M2D.bF6t5KkH7I7VpZ9o8gRfa');

-- Seed Sample DUET Buildings
INSERT INTO buildings (name, code, floors, department, location, status) VALUES
('Old Academic Building', 'OAB-01', 4, 'Civil Engineering', 'North Campus', 'Active'),
('New Academic Building', 'NAB-02', 8, 'Computer Science & Engineering', 'Central Campus', 'Active'),
('Dr. F. R. Khan Hall', 'FRK-01', 5, 'Residential', 'South Campus', 'Active');

-- Seed Sample Meters
INSERT INTO meters (meter_number, building_id, installation_date, status) VALUES
('MTR-DUET-101', 1, '2025-01-10', 'Online'),
('MTR-DUET-102', 2, '2025-02-15', 'Online'),
('MTR-DUET-103', 3, '2025-03-01', 'Online');