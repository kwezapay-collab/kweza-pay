import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { getCurrentUser } from '@/lib/auth/jwt';
import bcrypt from 'bcryptjs';

/**
 * PUT /api/user/update-pin
 * Change user PIN
 */
export async function PUT(request: NextRequest) {
    try {
        const user = await getCurrentUser();
        if (!user) {
            return NextResponse.json(
                { success: false, error: 'Unauthorized' },
                { status: 401 }
            );
        }

        const body = await request.json();
        const { current_pin, new_pin } = body;

        if (!current_pin || !new_pin) {
            return NextResponse.json(
                { success: false, error: 'Current PIN and new PIN are required' },
                { status: 400 }
            );
        }

        if (new_pin.length !== 4) {
            return NextResponse.json(
                { success: false, error: 'PIN must be 4 digits' },
                { status: 400 }
            );
        }

        // Verify current PIN
        const { data: userData } = await supabaseServer
            .from('users')
            .select('pin_hash')
            .eq('user_id', user.userId)
            .single();

        if (!userData) {
            return NextResponse.json(
                { success: false, error: 'User not found' },
                { status: 404 }
            );
        }

        const pinValid = await bcrypt.compare(current_pin, userData.pin_hash);
        if (!pinValid) {
            return NextResponse.json(
                { success: false, error: 'Current PIN is incorrect' },
                { status: 401 }
            );
        }

        // Hash new PIN
        const newPinHash = await bcrypt.hash(new_pin, 10);

        // Update PIN
        await supabaseServer
            .from('users')
            .update({ pin_hash: newPinHash })
            .eq('user_id', user.userId);

        return NextResponse.json({
            success: true,
            message: 'PIN updated successfully',
        });
    } catch (error) {
        console.error('Update PIN error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'PIN update failed',
            },
            { status: 500 }
        );
    }
}
