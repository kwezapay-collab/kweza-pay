// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { getCurrentUser } from '@/lib/auth/jwt';

/**
 * GET /api/admin/reports
 * Get all help desk reports (Admin only)
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

        const { data: reports, error } = await supabaseServer
            .from('help_reports')
            .select(`
                *,
                user:users!user_id(full_name, phone_number)
            `)
            .order('created_at', { ascending: false });

        if (error) throw error;

        return NextResponse.json({
            success: true,
            reports: reports || [],
        });
    } catch (error) {
        console.error('Get admin reports error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to fetch reports',
            },
            { status: 500 }
        );
    }
}
