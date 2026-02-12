// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';

/**
 * POST /api/auth/resend-code
 * Resend verification code
 */
export async function POST(request: NextRequest) {
    try {
        const body = await request.json();
        const { phone_number } = body;

        if (!phone_number) {
            return NextResponse.json(
                { success: false, error: 'Phone number is required' },
                { status: 400 }
            );
        }

        // Get user
        const { data: user } = await supabaseServer
            .from('users')
            .select('user_id, is_verified')
            .eq('phone_number', phone_number)
            .single();

        if (!user) {
            return NextResponse.json(
                { success: false, error: 'User not found' },
                { status: 404 }
            );
        }

        if (user.is_verified) {
            return NextResponse.json(
                { success: false, error: 'Account already verified' },
                { status: 400 }
            );
        }

        // Generate new code
        const verification_code = Math.floor(100000 + Math.random() * 900000).toString();
        const verification_expires_at = new Date(Date.now() + 15 * 60 * 1000).toISOString();

        // Update user
        await supabaseServer
            .from('users')
            .update({
                verification_code,
                verification_expires_at,
            })
            .eq('user_id', user.user_id);

        // TODO: Send email/SMS with code
        console.log(`[Resend Code] New code for ${phone_number}: ${verification_code}`);

        return NextResponse.json({
            success: true,
            message: 'Verification code resent successfully',
            // In development, return code
            ...(process.env.NODE_ENV === 'development' && { verification_code }),
        });
    } catch (error) {
        console.error('Resend code error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to resend code',
            },
            { status: 500 }
        );
    }
}
