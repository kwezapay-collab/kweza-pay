import { createClient as createSupabaseClient } from '@supabase/supabase-js';
import type { Database } from './types';

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL!;
const supabaseServiceKey = process.env.SUPABASE_SERVICE_ROLE_KEY!;

if (!supabaseUrl || !supabaseServiceKey) {
    throw new Error('Missing Supabase server environment variables');
}

// Server-side client with service role key for admin operations
export const supabaseServer = createSupabaseClient<Database>(
    supabaseUrl,
    supabaseServiceKey,
    {
        auth: {
            autoRefreshToken: false,
            persistSession: false,
        },
    }
);

/**
 * Helper to get a Supabase client. 
 * Returns the service role singleton.
 */
export async function createClient() {
    return supabaseServer;
}
