-- =====================================================
-- Events, Cafes, Help Desk, and Analytics Schema
-- Run this in Supabase SQL Editor
-- =====================================================

-- =====================================================
-- 1. EVENTS SYSTEM
-- =====================================================

-- Events table
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
  max_tickets INT CHECK (max_tickets IS NULL OR max_tickets > 0),
  tickets_sold INT DEFAULT 0 CHECK (tickets_sold >= 0),
  is_active BOOLEAN DEFAULT TRUE,
  created_by INT REFERENCES users(user_id) ON DELETE SET NULL,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Event tickets table
CREATE TABLE IF NOT EXISTS event_tickets (
  ticket_id SERIAL PRIMARY KEY,
  event_id INT NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
  user_id INT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
  ticket_code VARCHAR(50) UNIQUE NOT NULL,
  purchase_amount DECIMAL(15, 2) NOT NULL CHECK (purchase_amount > 0),
  qr_code_data TEXT,
  is_used BOOLEAN DEFAULT FALSE,
  used_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT NOW()
);

-- Event ticket inventory (for pre-assigned serial numbers)
CREATE TABLE IF NOT EXISTS event_ticket_inventory (
  inventory_id SERIAL PRIMARY KEY,
  event_id INT NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
  serial_number VARCHAR(100) NOT NULL,
  is_assigned BOOLEAN DEFAULT FALSE,
  assigned_at TIMESTAMP,
  ticket_id INT REFERENCES event_tickets(ticket_id) ON DELETE SET NULL,
  created_at TIMESTAMP DEFAULT NOW(),
  UNIQUE(event_id, serial_number)
);

-- Indexes for events system
CREATE INDEX IF NOT EXISTS idx_events_active ON events(is_active);
CREATE INDEX IF NOT EXISTS idx_events_created_by ON events(created_by);
CREATE INDEX IF NOT EXISTS idx_event_tickets_user ON event_tickets(user_id);
CREATE INDEX IF NOT EXISTS idx_event_tickets_event ON event_tickets(event_id);
CREATE INDEX IF NOT EXISTS idx_ticket_inventory_event ON event_ticket_inventory(event_id, is_assigned);

-- =====================================================
-- 2. CAMPUS CAFES SYSTEM
-- =====================================================

-- Campus cafes table
CREATE TABLE IF NOT EXISTS campus_cafes (
  cafe_id SERIAL PRIMARY KEY,
  cafe_name VARCHAR(255) NOT NULL,
  cafe_description TEXT,
  cafe_logo VARCHAR(500),
  airtel_money_code VARCHAR(100) NOT NULL,
  qr_code_image VARCHAR(500),
  created_by INT REFERENCES users(user_id) ON DELETE SET NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Cafe meals table
CREATE TABLE IF NOT EXISTS cafe_meals (
  meal_id SERIAL PRIMARY KEY,
  cafe_id INT NOT NULL REFERENCES campus_cafes(cafe_id) ON DELETE CASCADE,
  meal_name VARCHAR(255) NOT NULL,
  meal_description TEXT,
  meal_price DECIMAL(15, 2) NOT NULL CHECK (meal_price > 0),
  is_available BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Indexes for cafes system
CREATE INDEX IF NOT EXISTS idx_cafes_active ON campus_cafes(is_active);
CREATE INDEX IF NOT EXISTS idx_cafe_meals_cafe ON cafe_meals(cafe_id);
CREATE INDEX IF NOT EXISTS idx_cafe_meals_available ON cafe_meals(is_available);

-- =====================================================
-- 3. HELP DESK SYSTEM
-- =====================================================

-- Help reports table
CREATE TABLE IF NOT EXISTS help_reports (
  report_id SERIAL PRIMARY KEY,
  user_id INT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
  user_type VARCHAR(50) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  status VARCHAR(20) DEFAULT 'NEW' CHECK (status IN ('NEW', 'VIEWED', 'RESOLVED')),
  admin_notes TEXT,
  resolved_by INT REFERENCES users(user_id) ON DELETE SET NULL,
  resolved_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Indexes for help desk
CREATE INDEX IF NOT EXISTS idx_help_reports_user ON help_reports(user_id);
CREATE INDEX IF NOT EXISTS idx_help_reports_status ON help_reports(status);

-- =====================================================
-- 4. UPDATE TRANSACTIONS TABLE
-- =====================================================

-- Add new transaction types for events and cafes
-- Note: This uses ALTER TABLE to modify the existing constraint
DO $$ 
BEGIN
  -- Drop the old constraint if it exists
  IF EXISTS (
    SELECT 1 FROM pg_constraint 
    WHERE conname = 'transactions_txn_type_check'
  ) THEN
    ALTER TABLE transactions DROP CONSTRAINT transactions_txn_type_check;
  END IF;
  
  -- Add the new constraint with additional types
  ALTER TABLE transactions ADD CONSTRAINT transactions_txn_type_check 
    CHECK (txn_type IN ('QR_PAY', 'P2P', 'TOP_UP', 'SU_FEE', 'WITHDRAWAL', 'SYSTEM_FEE', 'EVENT_TICKET', 'CAFE_PAYMENT'));
END $$;

-- =====================================================
-- 5. ENABLE ROW LEVEL SECURITY (RLS)
-- =====================================================

-- Enable RLS on new tables
ALTER TABLE events ENABLE ROW LEVEL SECURITY;
ALTER TABLE event_tickets ENABLE ROW LEVEL SECURITY;
ALTER TABLE event_ticket_inventory ENABLE ROW LEVEL SECURITY;
ALTER TABLE campus_cafes ENABLE ROW LEVEL SECURITY;
ALTER TABLE cafe_meals ENABLE ROW LEVEL SECURITY;
ALTER TABLE help_reports ENABLE ROW LEVEL SECURITY;

-- RLS Policies for Events
CREATE POLICY "Events are viewable by everyone" ON events
  FOR SELECT USING (true);

CREATE POLICY "Events can be created by admins" ON events
  FOR INSERT WITH CHECK (
    EXISTS (SELECT 1 FROM users WHERE user_id = created_by AND user_type = 'Admin')
  );

CREATE POLICY "Events can be updated by admins or creators" ON events
  FOR UPDATE USING (
    EXISTS (SELECT 1 FROM users WHERE user_id = created_by AND user_type = 'Admin')
  );

CREATE POLICY "Events can be deleted by admins" ON events
  FOR DELETE USING (
    EXISTS (SELECT 1 FROM users WHERE user_id = created_by AND user_type = 'Admin')
  );

-- RLS Policies for Event Tickets
CREATE POLICY "Users can view their own tickets" ON event_tickets
  FOR SELECT USING (auth.uid()::int = user_id OR 
    EXISTS (SELECT 1 FROM users WHERE user_id = event_tickets.user_id));

CREATE POLICY "Users can purchase tickets" ON event_tickets
  FOR INSERT WITH CHECK (true);

-- RLS Policies for Campus Cafes
CREATE POLICY "Cafes are viewable by everyone" ON campus_cafes
  FOR SELECT USING (true);

CREATE POLICY "Cafes can be managed by admins" ON campus_cafes
  FOR ALL USING (
    EXISTS (SELECT 1 FROM users WHERE user_type = 'Admin')
  );

-- RLS Policies for Cafe Meals
CREATE POLICY "Meals are viewable by everyone" ON cafe_meals
  FOR SELECT USING (true);

CREATE POLICY "Meals can be managed by admins" ON cafe_meals
  FOR ALL USING (
    EXISTS (SELECT 1 FROM users WHERE user_type = 'Admin')
  );

-- RLS Policies for Help Reports
CREATE POLICY "Users can view their own reports" ON help_reports
  FOR SELECT USING (auth.uid()::int = user_id OR 
    EXISTS (SELECT 1 FROM users WHERE user_type = 'Admin'));

CREATE POLICY "Users can create reports" ON help_reports
  FOR INSERT WITH CHECK (true);

CREATE POLICY "Admins can update reports" ON help_reports
  FOR UPDATE USING (
    EXISTS (SELECT 1 FROM users WHERE user_type = 'Admin')
  );

-- =====================================================
-- 6. CREATE HELPFUL VIEWS
-- =====================================================

-- View for event sales summary
CREATE OR REPLACE VIEW event_sales_summary AS
SELECT 
  e.event_id,
  e.event_name,
  e.ticket_price,
  e.max_tickets,
  e.tickets_sold,
  e.tickets_sold * e.ticket_price AS total_revenue,
  CASE 
    WHEN e.max_tickets IS NOT NULL THEN (e.max_tickets - e.tickets_sold)
    ELSE NULL
  END AS tickets_remaining,
  e.is_active,
  e.event_date,
  e.created_at
FROM events e;

-- View for user ticket count
CREATE OR REPLACE VIEW user_ticket_stats AS
SELECT 
  u.user_id,
  u.full_name,
  COUNT(et.ticket_id) AS total_tickets,
  SUM(et.purchase_amount) AS total_spent_on_tickets
FROM users u
LEFT JOIN event_tickets et ON u.user_id = et.user_id
GROUP BY u.user_id, u.full_name;

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================

COMMENT ON TABLE events IS 'Stores campus events and ticket sales information';
COMMENT ON TABLE event_tickets IS 'Stores purchased event tickets with QR codes';
COMMENT ON TABLE event_ticket_inventory IS 'Optional pre-assigned serial numbers for tickets';
COMMENT ON TABLE campus_cafes IS 'Campus cafeterias accepting payments';
COMMENT ON TABLE cafe_meals IS 'Menu items available at each cafe';
COMMENT ON TABLE help_reports IS 'User-submitted help desk tickets';
