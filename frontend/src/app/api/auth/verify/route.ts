import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';

/**
 * POST /api/auth/verify
 * Converts backend/api/verify.php
 */
export async function POST(request: NextRequest) {
    try {
        const body = await request.json();
        const { phone_number, verification_code } = body;

        if (!phone_number || !verification_code) {
            return NextResponse.json(
                { success: false, error: 'Phone number and verification code are required' },
                { status: 400 }
            );
        }

        // Get user
        const { data: user, error: userError } = await supabaseServer
            .from('users')
            .select('user_id, verification_code, verification_expires_at, is_verified')
            .eq('phone_number', phone_number)
            .single();

        if (userError || !user) {
            return NextResponse.json(
                { success: false, error: 'User not found' },
                { status: 404 }
            );
        }

        // Check if already verified
        if (user.is_verified) {
            return NextResponse.json({
                success: true,
                message: 'Account already verified',
            });
        }

        // Check if code matches
        if (user.verification_code !== verification_code) {
            return NextResponse.json(
                { success: false, error: 'Invalid verification code' },
                { status: 401 }
            );
        }

        // Check if code expired
        if (user.verification_expires_at) {
            const expiresAt = new Date(user.verification_expires_at);
            if (expiresAt < new Date()) {
                return NextResponse.json(
                    { success: false, error: 'Verification code expired. Please request a new one.' },
                    { status: 401 }
                );
            }
        }

        // Mark user as verified
        const { error: updateError } = await supabaseServer
            .from('users')
            .update({
                is_verified: true,
                verification_code: null,
                verification_expires_at: null,
            })
            .eq('user_id', user.user_id);

        if (updateError) {
            console.error('Verification update failed:', updateError);
            return NextResponse.json(
                { success: false, error: 'Verification failed' },
                { status: 500 }
            );
        }

        return NextResponse.json({
            success: true,
            message: 'Account verified successfully! You can now log in.',
        });
    } catch (error) {
        console.error('Verification error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Server Error',
            },
            { status: 500 }
        );
    }
}
