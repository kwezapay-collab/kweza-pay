// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import bcrypt from 'bcryptjs';

export const dynamic = 'force-dynamic';

/**
 * POST /api/auth/register
 * Converts backend/api/register.php
 */
export async function POST(request: NextRequest) {
    try {
        const body = await request.json();
        const {
            phone_number,
            full_name,
            pin,
            user_type,
            email,
            registration_number,
            university,
        } = body;

        // Validate input
        if (!phone_number || !full_name || !pin || !user_type) {
            return NextResponse.json(
                { success: false, error: 'Missing required fields' },
                { status: 400 }
            );
        }

        // Check if user already exists
        const { data: existingUser } = await supabaseServer
            .from('users')
            .select('user_id')
            .eq('phone_number', phone_number)
            .single();

        if (existingUser) {
            return NextResponse.json(
                { success: false, error: 'Phone number already registered' },
                { status: 409 }
            );
        }

        // Hash PIN
        const pin_hash = await bcrypt.hash(pin, 10);

        // Generate verification code
        const verification_code = Math.floor(100000 + Math.random() * 900000).toString();
        const verification_expires_at = new Date(Date.now() + 15 * 60 * 1000).toISOString(); // 15 minutes

        // Create user
        const { data: newUser, error: insertError } = await supabaseServer
            .from('users')
            .insert({
                phone_number,
                full_name,
                pin_hash,
                user_type,
                email,
                registration_number,
                university,
                verification_code,
                verification_expires_at,
                is_verified: false,
            })
            .select('user_id, full_name, email')
            .single();

        if (insertError || !newUser) {
            console.error('User creation failed:', insertError);
            return NextResponse.json(
                { success: false, error: 'Registration failed' },
                { status: 500 }
            );
        }

        // Insert user role
        await supabaseServer
            .from('user_roles')
            .insert({
                user_id: newUser.user_id,
                role: user_type,
            });

        // TODO: Send verification email
        // For now, log the code
        console.log(`[Registration] Verification code for ${phone_number}: ${verification_code}`);

        return NextResponse.json({
            success: true,
            message: 'Registration successful. Please check your email for verification code.',
            user_id: newUser.user_id,
            // In development, return code for testing
            ...(process.env.NODE_ENV === 'development' && { verification_code }),
        });
    } catch (error) {
        console.error('Registration error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Server Error',
            },
            { status: 500 }
        );
    }
}
