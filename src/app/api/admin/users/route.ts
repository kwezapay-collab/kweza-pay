// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { getCurrentUser } from '@/lib/auth/jwt';

/**
 * GET /api/admin/users
 * Get all users (Admin only)
 */
export async function GET(request: NextRequest) {
    try {
        const user = await getCurrentUser();
        if (!user || user.userType !== 'Admin') {
            return NextResponse.json(
                { success: false, error: 'Unauthorized - Admin access required' },
                { status: 403 }
            );
        }

        const { data: users, error } = await supabaseServer
            .from('users')
            .select('user_id, full_name, phone_number, user_type, email, created_at, wallet_balance')
            .order('created_at', { ascending: false });

        if (error) throw error;

        return NextResponse.json({
            success: true,
            users: users || [],
        });
    } catch (error) {
        console.error('Get users error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to fetch users',
            },
            { status: 500 }
        );
    }
}
