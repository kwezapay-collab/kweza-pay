const { createClient } = require('@supabase/supabase-js');

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL || 'https://kfjzrdyumixgdgiegkaf.supabase.co';
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY || 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImtmanpyZHl1bWl4Z2RnaWVna2FmIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3MDgxOTE0NiwiZXhwIjoyMDg2Mzk1MTQ2fQ.bWAvaiJtGXHkbsoROyrsQYsl0I3_BebL1b4NhLH-iZA';

const supabase = createClient(supabaseUrl, supabaseKey);

async function checkAdmin() {
    console.log('🔍 Checking users table...');

    const { data: users, error } = await supabase
        .from('users')
        .select('user_id, phone_number, full_name, user_type, is_verified')
        .order('created_at', { ascending: false });

    if (error) {
        console.error('❌ Error fetching users:', error.message);
        return;
    }

    console.log(`\nFound ${users.length} users:`);
    users.forEach(u => {
        console.log(` - [${u.user_type}] ${u.full_name} (${u.phone_number}) | Verified: ${u.is_verified}`);
    });
}

checkAdmin();
