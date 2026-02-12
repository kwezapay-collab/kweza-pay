// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { getCurrentUser } from '@/lib/auth/jwt';

/**
 * GET /api/user/profile
 * Get current user profile
 */
export async function GET(request: NextRequest) {
    try {
        const user = await getCurrentUser();
        if (!user) {
            return NextResponse.json(
                { success: false, error: 'Unauthorized' },
                { status: 401 }
            );
        }

        const { data: profile, error } = await supabaseServer
            .from('users')
            .select('user_id, full_name, phone_number, email, user_type, wallet_balance, profile_pic, created_at')
            .eq('user_id', user.userId)
            .single();

        if (error) throw error;

        // Get user roles
        const { data: roles } = await supabaseServer
            .from('user_roles')
            .select('role')
            .eq('user_id', user.userId);

        return NextResponse.json({
            success: true,
            profile: {
                ...profile,
                roles: roles?.map(r => r.role) || [],
            },
        });
    } catch (error) {
        console.error('Get profile error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to fetch profile',
            },
            { status: 500 }
        );
    }
}
