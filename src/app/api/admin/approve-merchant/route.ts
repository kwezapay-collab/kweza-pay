import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { getCurrentUser } from '@/lib/auth/jwt';

/**
 * POST /api/admin/approve-merchant
 * Approve a merchant application (Admin only)
 */
export async function POST(request: NextRequest) {
    try {
        const user = await getCurrentUser();
        if (!user || user.userType !== 'Admin') {
            return NextResponse.json(
                { success: false, error: 'Unauthorized - Admin access required' },
                { status: 403 }
            );
        }

        const body = await request.json();
        const { merchant_id } = body;

        if (!merchant_id) {
            return NextResponse.json(
                { success: false, error: 'Merchant ID is required' },
                { status: 400 }
            );
        }

        const { error } = await supabaseServer
            .from('merchants')
            .update({ is_approved: true })
            .eq('merchant_id', merchant_id);

        if (error) throw error;

        return NextResponse.json({
            success: true,
            message: 'Merchant approved successfully',
        });
    } catch (error) {
        console.error('Approve merchant error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to approve merchant',
            },
            { status: 500 }
        );
    }
}
