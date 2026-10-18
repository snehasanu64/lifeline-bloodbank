CREATE DATABASE IF NOT EXISTS bloodbank
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bloodbank;

    collection_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
CREATE TABLE IF NOT EXISTS donors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
INSERT INTO blood_stock (blood_bank_name, blood_group, component, units_available, collection_date, expiry_date)
    age INT NOT NULL,
    ('Lifeline Blood Bank', 'A+', 'Whole Blood', 18, '2026-04-18', '2026-04-28'),
    ('Lifeline Blood Bank', 'A+', 'Packed RBC', 22, '2026-04-16', '2026-05-02'),
    ('Lifeline Blood Bank', 'B+', 'Whole Blood', 14, '2026-04-17', '2026-04-30'),
    ('Lifeline Blood Bank', 'B+', 'Packed RBC', 16, '2026-04-14', '2026-05-01'),
    ('Lifeline Blood Bank', 'O+', 'Whole Blood', 26, '2026-04-19', '2026-05-04'),
    ('Lifeline Blood Bank', 'O+', 'Packed RBC', 28, '2026-04-20', '2026-05-06'),
    ('Lifeline Blood Bank', 'AB+', 'Whole Blood', 8, '2026-04-15', '2026-04-26'),
    ('Lifeline Blood Bank', 'AB+', 'Platelets', 10, '2026-04-21', '2026-04-29'),
    ('Lifeline Blood Bank', 'O-', 'Whole Blood', 6, '2026-04-13', '2026-04-27'),
    ('Lifeline Blood Bank', 'A-', 'Plasma', 7, '2026-04-12', '2026-05-03'),
    ('North Care Blood Center', 'A+', 'Platelets', 15, '2026-04-18', '2026-04-25'),
    ('North Care Blood Center', 'O+', 'Plasma', 19, '2026-04-19', '2026-05-05'),
    ('North Care Blood Center', 'B-', 'Whole Blood', 5, '2026-04-11', '2026-04-24'),
    ('North Care Blood Center', 'AB-', 'Packed RBC', 4, '2026-04-10', '2026-04-23'),
    ('Metro Health Blood Unit', 'A+', 'Plasma', 12, '2026-04-17', '2026-05-01'),
    ('Metro Health Blood Unit', 'O+', 'Platelets', 21, '2026-04-20', '2026-04-28'),
    ('Metro Health Blood Unit', 'B+', 'Plasma', 9, '2026-04-15', '2026-04-30'),
    ('Metro Health Blood Unit', 'O-', 'Packed RBC', 3, '2026-04-09', '2026-04-22'),
    ('Central City Blood Bank', 'A-', 'Whole Blood', 11, '2026-04-16', '2026-04-26'),
    ('Central City Blood Bank', 'B+', 'Platelets', 13, '2026-04-18', '2026-04-29')
    donor_name VARCHAR(150) NOT NULL,
    units_available = VALUES(units_available),
    collection_date = VALUES(collection_date),
    expiry_date = VALUES(expiry_date);
    appointment_date DATE NOT NULL,
    appointment_time VARCHAR(50) NOT NULL,
    location VARCHAR(255) NOT NULL,
    status ENUM('Pending','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_donor_email (donor_email),
    INDEX idx_appointment_date (appointment_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS blood_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blood_bank_name VARCHAR(190) NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    component VARCHAR(80) NOT NULL,
    units_available INT NOT NULL DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_stock_row (blood_bank_name, blood_group, component)
) ENGINE=InnoDB;
    ALTER TABLE blood_stock
        ADD COLUMN collection_date DATE NULL AFTER units_available,
        ADD COLUMN expiry_date DATE NULL AFTER collection_date;

CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trans_date DATE NOT NULL,
    trans_type ENUM('IN','OUT') NOT NULL,
    source_type VARCHAR(80) NOT NULL,
    source_name VARCHAR(150) NOT NULL,
    blood_bank_name VARCHAR(190) NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    component VARCHAR(80) NOT NULL,
    units INT NOT NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_trans_date (trans_date),
    INDEX idx_stock_lookup (blood_bank_name, blood_group, component)
) ENGINE=InnoDB;

INSERT INTO blood_stock (blood_bank_name, blood_group, component, units_available)
VALUES
    ('Lifeline Blood Bank', 'A+', 'Whole Blood', 18),
    ('Lifeline Blood Bank', 'A+', 'Packed RBC', 22),
    ('Lifeline Blood Bank', 'B+', 'Whole Blood', 14),
    ('Lifeline Blood Bank', 'B+', 'Packed RBC', 16),
    ('Lifeline Blood Bank', 'O+', 'Whole Blood', 26),
    ('Lifeline Blood Bank', 'O+', 'Packed RBC', 28),
    ('Lifeline Blood Bank', 'AB+', 'Whole Blood', 8),
    ('Lifeline Blood Bank', 'AB+', 'Platelets', 10),
    ('Lifeline Blood Bank', 'O-', 'Whole Blood', 6),
    ('Lifeline Blood Bank', 'A-', 'Plasma', 7),
    ('North Care Blood Center', 'A+', 'Platelets', 15),
    ('North Care Blood Center', 'O+', 'Plasma', 19),
    ('North Care Blood Center', 'B-', 'Whole Blood', 5),
    ('North Care Blood Center', 'AB-', 'Packed RBC', 4),
    ('Metro Health Blood Unit', 'A+', 'Plasma', 12),
    ('Metro Health Blood Unit', 'O+', 'Platelets', 21),
    ('Metro Health Blood Unit', 'B+', 'Plasma', 9),
    ('Metro Health Blood Unit', 'O-', 'Packed RBC', 3),
    ('Central City Blood Bank', 'A-', 'Whole Blood', 11),
    ('Central City Blood Bank', 'B+', 'Platelets', 13)
ON DUPLICATE KEY UPDATE
    units_available = VALUES(units_available);

INSERT INTO donors (name, email, phone, age, bloodgroup, address, lastdonation)
VALUES
    ('Rahul Sharma', 'rahul@example.com', '9876543210', 29, 'O+', 'Bangalore', '2026-01-15'),
    ('Priya Nair', 'priya@example.com', '9876501234', 26, 'A+', 'Mysore', '2025-12-10'),
    ('Imran Khan', 'imran@example.com', '9876512345', 32, 'B+', 'Hubli', NULL)
ON DUPLICATE KEY UPDATE
    phone = VALUES(phone),
    age = VALUES(age),
    bloodgroup = VALUES(bloodgroup),
    address = VALUES(address),
    lastdonation = VALUES(lastdonation);

INSERT INTO blood_requests (requester_name, requester_email, bloodgroup, quantity, reason, status)
VALUES
    ('City Hospital', 'cityhospital@example.com', 'O+', 4, 'Emergency surgery', 'Pending'),
    ('MediCare Clinic', 'clinic@example.com', 'A+', 2, 'Patient transfusion', 'Fulfilled')
ON DUPLICATE KEY UPDATE
    requester_email = VALUES(requester_email),
    bloodgroup = VALUES(bloodgroup),
    quantity = VALUES(quantity),
    reason = VALUES(reason),
    status = VALUES(status);

INSERT INTO donation_appointments (donor_email, donor_name, bloodgroup, appointment_date, appointment_time, location, status)
VALUES
    ('rahul@example.com', 'Rahul Sharma', 'O+', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '9:00 AM - 11:00 AM', 'Lifeline Office', 'Pending'),
    ('priya@example.com', 'Priya Nair', 'A+', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '11:00 AM - 1:00 PM', 'MG Road Camp', 'Confirmed')
ON DUPLICATE KEY UPDATE
    donor_name = VALUES(donor_name),
    bloodgroup = VALUES(bloodgroup),
    appointment_date = VALUES(appointment_date),
    appointment_time = VALUES(appointment_time),
    location = VALUES(location),
    status = VALUES(status);

INSERT INTO inventory_transactions (trans_date, trans_type, source_type, source_name, blood_bank_name, blood_group, component, units, remarks)
VALUES
    (CURDATE(), 'IN', 'Donation Camp', 'City Mall Drive', 'Lifeline Blood Bank', 'O+', 'Whole Blood', 6, 'Walk-in donors processed'),
    (CURDATE(), 'OUT', 'Hospital Request', 'Apollo Ward', 'Lifeline Blood Bank', 'A+', 'Packed RBC', 3, 'Urgent surgery support'),
    (DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'IN', 'Donation Camp', 'Tech Park Camp', 'North Care Blood Center', 'B+', 'Plasma', 4, 'Scheduled camp collection'),
    (DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'OUT', 'Hospital Request', 'MediCare ICU', 'Metro Health Blood Unit', 'O+', 'Platelets', 2, 'Platelet replacement'),
    (DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'IN', 'Donation Camp', 'University Drive', 'Central City Blood Bank', 'A-', 'Whole Blood', 5, 'Student donor drive'),
    (DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'OUT', 'Hospital Request', 'St. Mary Hospital', 'North Care Blood Center', 'AB-', 'Packed RBC', 1, 'Rare group emergency'),
    (DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'IN', 'Inter-bank Transfer', 'Regional Reserve', 'Metro Health Blood Unit', 'B+', 'Plasma', 3, 'Stock balancing transfer'),
    (DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'OUT', 'Hospital Request', 'City Trauma Center', 'Lifeline Blood Bank', 'O-', 'Whole Blood', 2, 'Trauma support')
ON DUPLICATE KEY UPDATE
    trans_type = VALUES(trans_type),
    source_type = VALUES(source_type),
    source_name = VALUES(source_name),
    blood_bank_name = VALUES(blood_bank_name),
    blood_group = VALUES(blood_group),
    component = VALUES(component),
    units = VALUES(units),
    remarks = VALUES(remarks);

CREATE TABLE IF NOT EXISTS blood_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_date DATE NOT NULL,
    transaction_type ENUM('TAKEN','GIVEN') NOT NULL,
    from_patient_name VARCHAR(150) NULL,
    to_patient_name VARCHAR(150) NULL,
    blood_group VARCHAR(5) NOT NULL,
    component VARCHAR(80) NOT NULL,
    units INT NOT NULL,
    hospital_name VARCHAR(190) NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ledger_entry_date (entry_date),
    INDEX idx_ledger_blood_group (blood_group),
    INDEX idx_ledger_type (transaction_type)
) ENGINE=InnoDB;
