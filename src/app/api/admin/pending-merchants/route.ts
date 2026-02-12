import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { getCurrentUser } from '@/lib/auth/jwt';

/**
 * GET /api/admin/pending-merchants
 * Get pending merchant applications (Admin only)
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

        const { data: merchants, error } = await supabaseServer
            .from('merchants')
            .select('*')
            .eq('is_approved', false)
            .order('created_at', { ascending: false });

        if (error) throw error;

        return NextResponse.json({
            success: true,
            merchants: merchants || [],
        });
    } catch (error) {
        console.error('Get pending merchants error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to fetch merchants',
            },
            { status: 500 }
        );
    }
}
