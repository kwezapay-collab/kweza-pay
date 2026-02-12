import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { getCurrentUser } from '@/lib/auth/jwt';

/**
 * POST /api/payments/merchant
 * Converts backend/api/pay_merchant.php
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
        const { merchant_id, amount, pin } = body;

        if (!merchant_id || !amount || !pin) {
            return NextResponse.json(
                { success: false, error: 'Merchant ID, amount, and PIN are required' },
                { status: 400 }
            );
        }

        // Verify PIN
        const { data: userData } = await supabaseServer
            .from('users')
            .select('pin_hash, wallet_balance')
            .eq('user_id', user.userId)
            .single();

        if (!userData) {
            return NextResponse.json(
                { success: false, error: 'User not found' },
                { status: 404 }
            );
        }

        const bcrypt = await import('bcryptjs');
        const pinValid = await bcrypt.compare(pin, userData.pin_hash);
        if (!pinValid) {
            return NextResponse.json(
                { success: false, error: 'Invalid PIN' },
                { status: 401 }
            );
        }

        // Check wallet balance
        if (userData.wallet_balance < amount) {
            return NextResponse.json(
                { success: false, error: 'Insufficient balance' },
                { status: 400 }
            );
        }

        // Get merchant info
        const { data: merchant } = await supabaseServer
            .from('merchants')
            .select('user_id, business_name, is_approved')
            .eq(merchant_id.startsWith('MER') ? 'qr_code_token' : 'merchant_id', merchant_id)
            .single();

        if (!merchant) {
            return NextResponse.json(
                { success: false, error: 'Merchant not found' },
                { status: 404 }
            );
        }

        if (!merchant.is_approved) {
            return NextResponse.json(
                { success: false, error: 'Merchant not approved' },
                { status: 403 }
            );
        }

        // Generate reference code
        const reference_code = `TXN${Date.now()}${Math.floor(Math.random() * 1000)}`;

        // Process payment in transaction
        // 1. Debit sender
        await supabaseServer.rpc('update_wallet_balance', {
            p_user_id: user.userId,
            p_amount: -amount
        });

        // 2. Credit merchant
        await supabaseServer.rpc('update_wallet_balance', {
            p_user_id: merchant.user_id,
            p_amount: amount
        });

        // 3. Create transaction record
        const { data: transaction } = await supabaseServer
            .from('transactions')
            .insert({
                txn_type: 'QR_PAY',
                sender_id: user.userId,
                receiver_id: merchant.user_id,
                amount,
                reference_code,
                description: `Payment to ${merchant.business_name}`,
            })
            .select()
            .single();

        return NextResponse.json({
            success: true,
            message: 'Payment successful',
            transaction: {
                reference: reference_code,
                amount,
                merchant: merchant.business_name,
                date: new Date().toISOString(),
            },
        });
    } catch (error) {
        console.error('Payment error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Payment failed',
            },
            { status: 500 }
        );
    }
}
