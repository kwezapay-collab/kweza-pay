CREATE DATABASE IF NOT EXISTS kweza_pay;
USE kweza_pay;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(15) UNIQUE NOT NULL,
    email VARCHAR(255) NULL,
    registration_number VARCHAR(50) NULL,
    full_name VARCHAR(100) NOT NULL,
    university VARCHAR(100) NULL,
    pin_hash VARCHAR(255) NOT NULL,
    user_type ENUM('Student', 'Merchant', 'Admin', 'StudentUnion', 'Person') NOT NULL DEFAULT 'Student',
    wallet_balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00 CHECK (wallet_balance >= 0),
    verification_code VARCHAR(6) NULL,
    is_verified TINYINT(1) DEFAULT 0,
    verification_expires_at DATETIME NULL,
    avatar_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role ENUM('Person', 'Student', 'Merchant', 'Admin', 'StudentUnion') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id, role),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS merchants (
    merchant_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_name VARCHAR(100) NOT NULL,
    qr_code_token VARCHAR(50) UNIQUE NOT NULL,
    agent_code VARCHAR(50) NULL,
    is_approved TINYINT(1) DEFAULT 0,
    fee_paid TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transactions (
    txn_id INT AUTO_INCREMENT PRIMARY KEY,
    txn_type ENUM('QR_PAY', 'P2P', 'TOP_UP', 'SU_FEE') NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL CHECK (amount > 0),
    reference_code VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id),
    FOREIGN KEY (receiver_id) REFERENCES users(user_id)
);

CREATE INDEX idx_phone ON users(phone_number);
CREATE INDEX idx_qr_token ON merchants(qr_code_token);
CREATE INDEX idx_txn_ref ON transactions(reference_code);


CREATE TABLE IF NOT EXISTS student_union (
    su_id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_type VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    reference_number VARCHAR(50) UNIQUE NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    program VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    university VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    amount_paid DECIMAL(15, 2) NOT NULL CHECK (amount_paid >= 0),
    service_fee DECIMAL(15, 2) NOT NULL DEFAULT 0.00 CHECK (service_fee >= 0),
    total_amount DECIMAL(15, 2) NOT NULL CHECK (total_amount >= 0),
    recipient VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);