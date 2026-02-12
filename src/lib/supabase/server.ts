import { createClient as createSupabaseClient } from '@supabase/supabase-js';
import type { Database } from './types';

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL || 'https://placeholder.supabase.co';
const supabaseServiceKey = process.env.SUPABASE_SERVICE_ROLE_KEY || 'placeholder-key';

if (!process.env.NEXT_PUBLIC_SUPABASE_URL && process.env.NODE_ENV === 'production') {
    console.warn('⚠️ Missing NEXT_PUBLIC_SUPABASE_URL in production build');
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
