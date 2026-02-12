// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { getCurrentUser } from '@/lib/auth/jwt';

export const dynamic = 'force-dynamic';

/**
 * POST /api/payments/withdraw
 * Withdraw funds to mobile money
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
        const { amount, phone_number, provider } = body;

        if (!amount || !phone_number || !provider) {
            return NextResponse.json(
                { success: false, error: 'Amount, phone number, and provider are required' },
                { status: 400 }
            );
        }

        // Get user balance
        const { data: userData } = await supabaseServer
            .from('users')
            .select('wallet_balance')
            .eq('user_id', user.userId)
            .single();

        const withdrawalFee = amount * 0.01; // 1% fee
        const totalDeduction = amount + withdrawalFee;

        if (!userData || userData.wallet_balance < totalDeduction) {
            return NextResponse.json(
                { success: false, error: 'Insufficient balance' },
                { status: 400 }
            );
        }

        // TODO: Integrate with actual mobile money API
        // For now, simulate withdrawal

        const reference_code = `WDW${Date.now()}`;

        // Deduct from wallet
        await supabaseServer.rpc('update_wallet_balance', {
            p_user_id: user.userId,
            p_amount: -totalDeduction
        });

        // Record transaction
        await supabaseServer
            .from('transactions')
            .insert({
                txn_type: 'WITHDRAW',
                sender_id: user.userId,
                receiver_id: null,
                amount: totalDeduction,
                reference_code,
                description: `Withdrawal to ${provider} (${phone_number})`,
            });

        return NextResponse.json({
            success: true,
            message: `Withdrawal of MWK ${amount.toFixed(2)} initiated`,
            transaction: {
                reference: reference_code,
                amount,
                fee: withdrawalFee,
                total: totalDeduction,
                provider,
                phone: phone_number,
            },
        });
    } catch (error) {
        console.error('Withdraw error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Withdrawal failed',
            },
            { status: 500 }
        );
    }
}
