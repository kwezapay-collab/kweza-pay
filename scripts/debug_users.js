const { createClient } = require('@supabase/supabase-js');

const supabaseUrl = 'https://kfjzrdyumixgdgiegkaf.supabase.co';
const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImtmanpyZHl1bWl4Z2RnaWVna2FmIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3MDgxOTE0NiwiZXhwIjoyMDg2Mzk1MTQ2fQ.bWAvaiJtGXHkbsoROyrsQYsl0I3_BebL1b4NhLH-iZA';

const supabase = createClient(supabaseUrl, supabaseKey);

async function checkUsers() {
    console.log('Checking Supabase connection...');

    // Check public.users table (your app's user table)
    const { data: publicUsers, error: publicError, count: publicCount } = await supabase
        .from('users')
        .select('*', { count: 'exact', head: true });

    if (publicError) {
        console.error('Error fetching public.users:', publicError.message);
    } else {
        console.log(`\n[public.users] Total rows: ${publicCount}`);
    }

    // Check valid auth users (if you were using auth.users directly, though you seem to duplicate)
    // We can't query auth.users directly easily via client usually, but service role can list users
    const { data: { users: authUsers }, error: authError } = await supabase.auth.admin.listUsers();

    if (authError) {
        console.error('Error fetching auth.users:', authError.message);
    } else {
        console.log(`[auth.users]   Total accounts: ${authUsers.length}`);
        if (authUsers.length > 0) {
            console.log('First 3 Auth Users:');
            authUsers.slice(0, 3).forEach(u => console.log(` - ${u.email} (ID: ${u.id})`));
        }
    }
}

checkUsers();
