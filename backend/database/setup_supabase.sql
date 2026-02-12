-- =====================================================
-- Kweza Pay: Complete Supabase Setup Script (FIXED)
-- Paste this entire file into the Supabase SQL Editor
-- =====================================================

-- 1. EXTENSIONS
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- 2. USER ROLES & PERMISSIONS
CREATE TABLE IF NOT EXISTS user_roles (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    UNIQUE(user_id, role)
);

-- 3. UPDATING TRANSACTIONS TABLE
-- Ensure the transaction status and types are correct for PayChangu integration
DO $$ 
BEGIN
    -- Add status column if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='transactions' AND column_name='status') THEN
        ALTER TABLE transactions ADD COLUMN status VARCHAR(20) DEFAULT 'completed';
    END IF;

    -- Update status constraint
    IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'transactions_status_check') THEN
        ALTER TABLE transactions DROP CONSTRAINT transactions_status_check;
    END IF;
    ALTER TABLE transactions ADD CONSTRAINT transactions_status_check CHECK (status IN ('pending', 'completed', 'failed', 'cancelled'));

    -- Update txn_type constraint
    IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'transactions_txn_type_check') THEN
        ALTER TABLE transactions DROP CONSTRAINT transactions_txn_type_check;
    END IF;
    ALTER TABLE transactions ADD CONSTRAINT transactions_txn_type_check 
        CHECK (txn_type IN ('QR_PAY', 'P2P', 'TOP_UP', 'SU_FEE', 'WITHDRAWAL', 'SYSTEM_FEE', 'EVENT_TICKET', 'CAFE_PAYMENT', 'CAFE_PAY'));
END $$;

-- 4. EVENTS & CAFES SYSTEM
CREATE TABLE IF NOT EXISTS events (
    event_id SERIAL PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    event_description TEXT,
    event_picture VARCHAR(500),
    ticket_price DECIMAL(15, 2) NOT NULL CHECK (ticket_price > 0),
    ticket_template VARCHAR(50),
    event_date TIMESTAMP,
    event_location VARCHAR(255),
    airtel_money_code VARCHAR(100),
    airtel_money_id VARCHAR(100),
    airtel_qr_image VARCHAR(500),
    max_tickets INT,
    tickets_sold INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS campus_cafes (
    cafe_id SERIAL PRIMARY KEY,
    cafe_name VARCHAR(255) NOT NULL,
    cafe_description TEXT,
    cafe_logo VARCHAR(500),
    airtel_money_code VARCHAR(100) NOT NULL,
    qr_code_image VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    user_id INTEGER, -- Cafe Owner ID
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS event_tickets (
    ticket_id SERIAL PRIMARY KEY,
    event_id INTEGER REFERENCES events(event_id) ON DELETE CASCADE,
    user_id INTEGER,
    ticket_code VARCHAR(50) UNIQUE NOT NULL,
    purchase_amount DECIMAL(15, 2) NOT NULL,
    qr_code_data TEXT,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS help_reports (
    report_id SERIAL PRIMARY KEY,
    user_id INTEGER,
    user_type VARCHAR(50),
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'NEW' CHECK (status IN ('NEW', 'VIEWED', 'RESOLVED')),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- 5. INSERT ADMIN USER (PIN: 4321)
-- Delete existing admin if exists to avoid conflict during setup
DELETE FROM users WHERE phone_number = '0880000000';

INSERT INTO users (
    phone_number,
    email,
    full_name,
    pin_hash,
    user_type,
    is_verified,
    wallet_balance
) VALUES (
    '0880000000',
    'admin@kwezapay.com',
    'Kweza Admin',
    '$2a$10$JFnWHlVdARIMafe.CPrSdO8ADo9AZlW0MrVB8imP/xIU5b0YPiGTW', -- BCrypt for 4321
    'Admin',
    TRUE,
    1000000.00
);

-- Associate Admin Role
DELETE FROM user_roles WHERE user_id = (SELECT user_id FROM users WHERE phone_number = '0880000000');
INSERT INTO user_roles (user_id, role) 
VALUES ((SELECT user_id FROM users WHERE phone_number = '0880000000'), 'Admin');

-- 6. SAMPLE DATA FOR TESTING
-- Sample Event
INSERT INTO events (event_name, event_description, ticket_price, is_active, airtel_money_code)
VALUES ('Kweza Music Fest', 'Grand annual music festival at the campus arena.', 5000.00, TRUE, '123456')
ON CONFLICT DO NOTHING;

-- Sample Cafe
INSERT INTO campus_cafes (cafe_name, cafe_description, is_active, airtel_money_code)
VALUES ('Central Bites', 'The best student meals in the heart of campus.', TRUE, 'CAF001')
ON CONFLICT DO NOTHING;

-- MIGRATION COMPLETE NOTICE
DO $$ 
BEGIN 
    RAISE NOTICE 'Supabase setup completed successfully. Admin PIN is set to 4321.';
END $$;
