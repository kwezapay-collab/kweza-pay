-- Event Tickets and Campus Cafe Schema
USE kweza_pay;

-- Events table
CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(200) NOT NULL,
    event_description TEXT,
    event_picture VARCHAR(500),
    ticket_price DECIMAL(15, 2) NOT NULL CHECK (ticket_price >= 0),
    ticket_template TEXT,
    event_date DATETIME,
    event_location VARCHAR(200),
    airtel_money_code VARCHAR(100),
    airtel_money_id VARCHAR(100),
    airtel_qr_image VARCHAR(500),
    max_tickets INT DEFAULT NULL,
    tickets_sold INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Event Tickets table
CREATE TABLE IF NOT EXISTS event_tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    ticket_code VARCHAR(50) UNIQUE NOT NULL,
    purchase_amount DECIMAL(15, 2) NOT NULL,
    ticket_status ENUM('valid', 'used', 'cancelled') DEFAULT 'valid',
    qr_code_data TEXT,
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Optional event ticket inventory table (serial IDs managed by event owner)
CREATE TABLE IF NOT EXISTS event_ticket_inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    serial_number VARCHAR(100) NOT NULL,
    is_assigned TINYINT(1) NOT NULL DEFAULT 0,
    assigned_at DATETIME NULL,
    ticket_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_event_serial (event_id, serial_number),
    UNIQUE KEY uniq_inventory_ticket (ticket_id),
    INDEX idx_event_assignment (event_id, is_assigned),
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_id) REFERENCES event_tickets(ticket_id) ON DELETE SET NULL
);

-- Campus Cafes table
CREATE TABLE IF NOT EXISTS campus_cafes (
    cafe_id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_name VARCHAR(200) NOT NULL,
    cafe_description TEXT,
    cafe_logo VARCHAR(500),
    airtel_money_code VARCHAR(50) NOT NULL,
    qr_code_image VARCHAR(500),
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Campus Cafe Transactions table
CREATE TABLE IF NOT EXISTS cafe_transactions (
    cafe_txn_id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL CHECK (amount > 0),
    reference_code VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255),
    payment_method ENUM('airtel_money', 'qr_code') DEFAULT 'airtel_money',
    status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES campus_cafes(cafe_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_event_active ON events(is_active);
CREATE INDEX idx_ticket_code ON event_tickets(ticket_code);
CREATE INDEX idx_cafe_active ON campus_cafes(is_active);
CREATE INDEX idx_cafe_txn_ref ON cafe_transactions(reference_code);

-- Schema patches for existing databases
ALTER TABLE events
    ADD COLUMN IF NOT EXISTS airtel_money_code VARCHAR(100) NULL AFTER event_location;

ALTER TABLE events
    ADD COLUMN IF NOT EXISTS airtel_money_id VARCHAR(100) NULL AFTER airtel_money_code;

ALTER TABLE events
    ADD COLUMN IF NOT EXISTS airtel_qr_image VARCHAR(500) NULL AFTER airtel_money_id;
