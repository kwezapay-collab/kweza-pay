// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { setAuthToken } from '@/lib/auth/jwt';
import bcrypt from 'bcryptjs';

/**
 * POST /api/auth/login
 * Converts backend/api/login.php to Next.js API route
 */
export async function POST(request: NextRequest) {
    try {
        const body = await request.json();
        const { phone, pin, selected_role } = body;

        // Validate input
        if (!phone || !pin) {
            return NextResponse.json(
                { success: false, error: 'Phone and PIN are required' },
                { status: 400 }
            );
        }

        // 1. Authenticate user
        const { data: user, error: userError } = await supabaseServer
            .from('users')
            .select('user_id, full_name, pin_hash, is_verified')
            .eq('phone_number', phone)
            .single();

        if (userError || !user) {
            return NextResponse.json(
                { success: false, error: 'Invalid Phone or PIN' },
                { status: 401 }
            );
        }

        // Verify PIN
        const pinValid = await bcrypt.compare(pin, user.pin_hash);
        if (!pinValid) {
            return NextResponse.json(
                { success: false, error: 'Invalid Phone or PIN' },
                { status: 401 }
            );
        }

        // 2. Check verification
        if (!user.is_verified) {
            return NextResponse.json(
                {
                    success: false,
                    error: 'Please verify your account first. Check your email for the verification code.',
                },
                { status: 403 }
            );
        }

        // 3. Get all available roles for this user
        const { data: rolesData } = await supabaseServer
            .from('user_roles')
            .select('role')
            .eq('user_id', user.user_id);

        let roles: string[] = [];

        if (rolesData && rolesData.length > 0) {
            roles = rolesData.map((r) => r.role);
        } else {
            // Backward compatibility: check users table
            const { data: oldTypeData } = await supabaseServer
                .from('users')
                .select('user_type')
                .eq('user_id', user.user_id)
                .single();

            if (oldTypeData?.user_type) {
                roles = [oldTypeData.user_type];
                // Fix: Insert this role into user_roles for future
                await supabaseServer
                    .from('user_roles')
                    .insert({ user_id: user.user_id, role: oldTypeData.user_type });
            }
        }

        // 4. Handle role selection
        if (roles.length > 1 && !selected_role) {
            // More than one role and none selected
            return NextResponse.json({
                success: true,
                requires_selection: true,
                roles,
                user: {
                    name: user.full_name,
                },
            });
        }

        // 5. Finalize Login
        const finalRole = selected_role || roles[0];

        // Ensure selected role is valid for this user
        if (!roles.includes(finalRole)) {
            return NextResponse.json(
                { success: false, error: 'Invalid role selected for this account.' },
                { status: 403 }
            );
        }

        // Generate JWT token and set cookie
        await setAuthToken({
            userId: user.user_id,
            userType: finalRole as any,
            fullName: user.full_name,
        });

        // Determine Redirect
        let redirect = 'student';
        if (finalRole === 'Merchant') redirect = 'merchant';
        if (finalRole === 'Admin') redirect = 'admin';
        if (finalRole === 'StudentUnion') redirect = 'student-union';
        if (finalRole === 'Person') redirect = 'person';

        return NextResponse.json({
            success: true,
            redirect,
            user: {
                name: user.full_name,
                type: finalRole,
            },
        });
    } catch (error) {
        console.error('Login error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Server Error',
            },
            { status: 500 }
        );
    }
}
