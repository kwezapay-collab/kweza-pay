// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@/lib/supabase/server';

export const dynamic = 'force-dynamic';

export async function GET(request: NextRequest) {
    try {
        const supabase = await createClient();

        // Check if user is admin
        const token = request.cookies.get('auth_token')?.value;
        let isAdmin = false;

        if (token) {
            const { verifyJWT } = await import('@/lib/auth/jwt');
            const payload = await verifyJWT(token);

            if (payload?.userId) {
                const { data: user } = await supabase
                    .from('users')
                    .select('user_type')
                    .eq('user_id', payload.userId)
                    .single();

                isAdmin = user?.user_type === 'Admin';
            }
        }

        // Build query
        let query = supabase
            .from('campus_cafes')
            .select('*')
            .order('created_at', { ascending: false });

        // Only show active cafes to non-admins
        if (!isAdmin) {
            query = query.eq('is_active', true);
        }

        const { data: cafes, error } = await query;

        if (error) {
            return NextResponse.json({ error: 'Failed to fetch cafes' }, { status: 500 });
        }

        return NextResponse.json({
            success: true,
            cafes: cafes || [],
        });
    } catch (error) {
        console.error('Get cafes error:', error);
        return NextResponse.json({ error: 'Internal server error' }, { status: 500 });
    }
}
