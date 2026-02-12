// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';

/**
 * GET /api/merchant/details-by-token
 * Fetches merchant business details using their QR token
 */
export async function GET(request: NextRequest) {
    try {
        const { searchParams } = new URL(request.url);
        const token = searchParams.get('token');

        if (!token) {
            return NextResponse.json({ success: false, error: 'Token is required' }, { status: 400 });
        }

        const { data: merchant, error } = await supabaseServer
            .from('merchants')
            .select(`
                merchant_id,
                business_name,
                user_id
            `)
            .eq('qr_code_token', token)
            .single();

        if (error || !merchant) {
            return NextResponse.json({ success: false, error: 'Merchant not found' }, { status: 404 });
        }

        return NextResponse.json({
            success: true,
            merchant: merchant
        });

    } catch (error) {
        console.error('Merchant details fetch error:', error);
        return NextResponse.json({
            success: false,
            error: 'Internal server error'
        }, { status: 500 });
    }
}
