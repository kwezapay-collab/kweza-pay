-- =====================================================
-- Add Admin User Script
-- Run this in Supabase SQL Editor
-- =====================================================

DO $$
DECLARE
    new_user_id INTEGER;
BEGIN
    -- Insert into users table
    INSERT INTO users (
        phone_number,
        email,
        full_name,
        pin_hash,
        user_type,
        is_verified
    ) VALUES (
        '0880000000', -- Default phone number for admin
        'kwezapay@gmail.com',
        'Kweza Admin',
        '$2a$10$JFnWHlVdARIMafe.CPrSdO8ADo9AZlW0MrVB8imP/xIU5b0YPiGTW', -- bcrypt hash for '4321'
        'Admin',
        TRUE
    )
    RETURNING user_id INTO new_user_id;

    -- Insert into user_roles table
    INSERT INTO user_roles (user_id, role)
    VALUES (new_user_id, 'Admin');

    RAISE NOTICE 'Admin user created with ID %', new_user_id;
END $$;
