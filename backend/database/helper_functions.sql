-- Database Helper Functions for Supabase
-- Run these in your Supabase SQL Editor

-- Function to update wallet balance atomically
CREATE OR REPLACE FUNCTION update_wallet_balance(
  p_user_id INTEGER,
  p_amount NUMERIC
)
RETURNS void
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
  UPDATE users
  SET wallet_balance = wallet_balance + p_amount
  WHERE user_id = p_user_id;
  
  IF NOT FOUND THEN
    RAISE EXCEPTION 'User not found';
  END IF;
  
  -- Check for negative balance
  IF (SELECT wallet_balance FROM users WHERE user_id = p_user_id) < 0 THEN
    RAISE EXCEPTION 'Insufficient balance';
  END IF;
END;
$$;

-- Function to get user transaction summary
CREATE OR REPLACE FUNCTION get_transaction_summary(p_user_id INTEGER)
RETURNS TABLE (
  total_sent NUMERIC,
  total_received NUMERIC,
  transaction_count BIGINT
)
LANGUAGE sql
AS $$
  SELECT 
    COALESCE(SUM(CASE WHEN sender_id = p_user_id THEN amount ELSE 0 END), 0) as total_sent,
    COALESCE(SUM(CASE WHEN receiver_id = p_user_id THEN amount ELSE 0 END), 0) as total_received,
    COUNT(*) as transaction_count
  FROM transactions
  WHERE sender_id = p_user_id OR receiver_id = p_user_id;
$$;
