// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { getCurrentUser } from '@/lib/auth/jwt';

export const dynamic = 'force-dynamic';

/**
 * GET /api/merchant/info
 * Get merchant information
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

        const { data: merchant, error } = await supabaseServer
            .from('merchants')
            .select('*')
            .eq('user_id', user.userId)
            .single();

        if (error) {
            return NextResponse.json(
                { success: false, error: 'Merchant not found' },
                { status: 404 }
            );
        }

        return NextResponse.json({
            success: true,
            merchant,
        });
    } catch (error) {
        console.error('Get merchant info error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to fetch merchant info',
            },
            { status: 500 }
        );
    }
}
