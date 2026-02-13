const { createClient } = require('@supabase/supabase-js');

// Use service role key to bypass RLS
const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL || 'https://kfjzrdyumixgdgiegkaf.supabase.co';
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY || 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImtmanpyZHl1bWl4Z2RnaWVna2FmIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3MDgxOTE0NiwiZXhwIjoyMDg2Mzk1MTQ2fQ.bWAvaiJtGXHkbsoROyrsQYsl0I3_BebL1b4NhLH-iZA';

const supabase = createClient(supabaseUrl, supabaseKey);

async function cleanupUsers() {
    console.log('🧹 Starting cleanup of stale users...');

    // 1. Delete all rows from public.users (this will cascade to related tables if set up correctly, or fail if not)
    // We want to clear the slate so the user can register fresh.
    const { error } = await supabase
        .from('users')
        .delete()
        .neq('user_id', 0); // Delete everything (assuming IDs are positive integers)

    if (error) {
        console.error('❌ Error deleting users:', error.message);
    } else {
        console.log('✅ Successfully cleared stale records from public.users');
        console.log('   You can now register new accounts immediately.');
    }

    // Double check
    const { count } = await supabase.from('users').select('*', { count: 'exact', head: true });
    console.log(`\nRemaining rows in public.users: ${count}`);
}

cleanupUsers();
