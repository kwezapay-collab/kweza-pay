// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { getCurrentUser } from '@/lib/auth/jwt';

export const dynamic = 'force-dynamic';

/**
 * POST /api/payments/send-money
 * Converts backend/api/send_money.php
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
        const { recipient_phone, amount } = body;

        if (!recipient_phone || !amount) {
            return NextResponse.json(
                { success: false, error: 'Recipient phone and amount are required' },
                { status: 400 }
            );
        }

        // Get sender data
        const { data: sender } = await supabaseServer
            .from('users')
            .select('wallet_balance, full_name')
            .eq('user_id', user.userId)
            .single();

        if (!sender || sender.wallet_balance < amount) {
            return NextResponse.json(
                { success: false, error: 'Insufficient balance' },
                { status: 400 }
            );
        }

        // Get recipient
        const { data: recipient } = await supabaseServer
            .from('users')
            .select('user_id, full_name')
            .eq('phone_number', recipient_phone)
            .single();

        if (!recipient) {
            return NextResponse.json(
                { success: false, error: 'Recipient not found' },
                { status: 404 }
            );
        }

        if (recipient.user_id === user.userId) {
            return NextResponse.json(
                { success: false, error: 'Cannot send money to yourself' },
                { status: 400 }
            );
        }

        const reference_code = `P2P${Date.now()}${Math.floor(Math.random() * 1000)}`;

        // Process transfer
        await supabaseServer.rpc('update_wallet_balance', {
            p_user_id: user.userId,
            p_amount: -amount
        });

        await supabaseServer.rpc('update_wallet_balance', {
            p_user_id: recipient.user_id,
            p_amount: amount
        });

        await supabaseServer
            .from('transactions')
            .insert({
                txn_type: 'P2P',
                sender_id: user.userId,
                receiver_id: recipient.user_id,
                amount,
                reference_code,
                description: `Transfer to ${recipient.full_name}`,
            });

        return NextResponse.json({
            success: true,
            message: `Successfully sent MWK ${amount.toFixed(2)} to ${recipient.full_name}`,
            transaction: {
                reference: reference_code,
                amount,
                recipient: recipient.full_name,
                date: new Date().toISOString(),
            },
        });
    } catch (error) {
        console.error('Send money error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Transfer failed',
            },
            { status: 500 }
        );
    }
}
