const { createClient } = require('@supabase/supabase-js');
const bcrypt = require('bcryptjs');

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL || 'https://kfjzrdyumixgdgiegkaf.supabase.co';
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY || 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImtmanpyZHl1bWl4Z2RnaWVna2FmIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3MDgxOTE0NiwiZXhwIjoyMDg2Mzk1MTQ2fQ.bWAvaiJtGXHkbsoROyrsQYsl0I3_BebL1b4NhLH-iZA';

const supabase = createClient(supabaseUrl, supabaseKey);

async function createAdmin() {
    const phone = '0999999999'; // Default Admin Phone
    const pin = '123456';        // Default Admin PIN
    const fullName = 'System Admin';

    console.log(`Creating Admin Account...`);
    console.log(`Phone: ${phone}`);
    console.log(`PIN:   ${pin}`);

    // 1. Hash PIN
    const pinHash = await bcrypt.hash(pin, 10);

    // 2. Create in public.users directly (since we aren't using Supabase Auth for login, but our custom JWT)
    // Wait, the app uses custom JWT auth based on public.users. We don't need auth.users for this specific app structure?
    // Let's check: The login route checks 'users' table (public).

    // Check if exists first
    const { data: existing } = await supabase.from('users').select('user_id').eq('phone_number', phone).single();
    if (existing) {
        console.log('⚠️ Admin user already exists. Updating role...');
        // Ensure role is Admin
        await supabase.from('users').update({ user_type: 'Admin' }).eq('user_id', existing.user_id);
        // Ensure existing user has Admin role in user_roles table
        const { error: roleError } = await supabase
            .from('user_roles')
            .upsert({ user_id: existing.user_id, role: 'Admin' }, { onConflict: 'user_id, role' });

        if (roleError) console.error('Error updating role:', roleError);
        else console.log('✅ Admin role confirmed.');
        return;
    }

    // Insert new Admin
    const { data: newUser, error } = await supabase
        .from('users')
        .insert({
            phone_number: phone,
            full_name: fullName,
            pin_hash: pinHash,
            user_type: 'Admin',
            email: 'admin@kwezapay.com',
            is_verified: true, // Auto-verify admin
            verification_code: null
        })
        .select()
        .single();

    if (error) {
        console.error('❌ Failed to create Admin:', error.message);
        return;
    }

    // Insert role
    const { error: roleError } = await supabase
        .from('user_roles')
        .insert({
            user_id: newUser.user_id,
            role: 'Admin'
        });

    if (roleError) {
        console.error('❌ Created user but failed to assign role:', roleError.message);
    } else {
        console.log('✅ Admin account created successfully! You can now log in.');
    }
}

createAdmin();
