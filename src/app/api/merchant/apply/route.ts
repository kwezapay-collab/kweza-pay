import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { getCurrentUser } from '@/lib/auth/jwt';

/**
 * POST /api/merchant/apply
 * Converts backend/api/apply_merchant.php
 */
export async function POST(request: NextRequest) {
    try {
        const user = await getCurrentUser();
        if (!user) {
            return NextResponse.json(
                { success: false, error: 'Unauthorized' },
                { status: 401 }
            );
        }

        const body = await request.json();
        const { business_name } = body;

        if (!business_name) {
            return NextResponse.json(
                { success: false, error: 'Business name is required' },
                { status: 400 }
            );
        }

        // Check if already applied
        const { data: existing } = await supabaseServer
            .from('merchants')
            .select('merchant_id')
            .eq('user_id', user.userId)
            .single();

        if (existing) {
            return NextResponse.json(
                { success: false, error: 'You have already applied as a merchant' },
                { status: 409 }
            );
        }

        // Generate QR code token
        const qr_code_token = `MER${Date.now()}${user.userId}`;

        // Create merchant application
        await supabaseServer
            .from('merchants')
            .insert({
                user_id: user.userId,
                business_name,
                qr_code_token,
                is_approved: false,
                fee_paid: true, // Assuming fee is paid before application
            });

        // Add Merchant role
        await supabaseServer
            .from('user_roles')
            .insert({
                user_id: user.userId,
                role: 'Merchant',
            });

        return NextResponse.json({
            success: true,
            message: 'Merchant application submitted successfully. Awaiting admin approval.',
        });
    } catch (error) {
        console.error('Merchant application error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Application failed',
            },
            { status: 500 }
        );
    }
}
