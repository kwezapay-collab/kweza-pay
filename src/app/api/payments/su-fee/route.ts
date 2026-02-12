// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { getCurrentUser } from '@/lib/auth/jwt';

/**
 * POST /api/payments/su-fee
 * Converts backend/api/pay_su_fee.php
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
        const {
            receipt_type,
            student_name,
            student_id,
            program,
            year,
            university,
            amount_paid,
        } = body;

        if (!receipt_type || !student_name || !student_id || !program || !year || !university || !amount_paid) {
            return NextResponse.json(
                { success: false, error: 'All fields are required' },
                { status: 400 }
            );
        }

        // Get SU user (receiver)
        const { data: suUser } = await supabaseServer
            .from('user_roles')
            .select('user_id')
            .eq('role', 'StudentUnion')
            .limit(1)
            .single();

        if (!suUser) {
            return NextResponse.json(
                { success: false, error: 'Student Union account not configured' },
                { status: 500 }
            );
        }

        // Check sender balance
        const { data: sender } = await supabaseServer
            .from('users')
            .select('wallet_balance')
            .eq('user_id', user.userId)
            .single();

        const totalAmount = parseFloat(amount_paid);
        const serviceFee = totalAmount * 0.02; // 2% service fee
        const total = totalAmount + serviceFee;

        if (!sender || sender.wallet_balance < total) {
            return NextResponse.json(
                { success: false, error: 'Insufficient balance' },
                { status: 400 }
            );
        }

        const reference_number = `SU${Date.now()}`;

        // Process payment
        await supabaseServer.rpc('update_wallet_balance', {
            p_user_id: user.userId,
            p_amount: -total
        });

        await supabaseServer.rpc('update_wallet_balance', {
            p_user_id: suUser.user_id,
            p_amount: totalAmount
        });

        // Create transaction
        await supabaseServer
            .from('transactions')
            .insert({
                txn_type: 'SU_FEE',
                sender_id: user.userId,
                receiver_id: suUser.user_id,
                amount: total,
                reference_code: reference_number,
                description: `${receipt_type} - ${student_name}`,
            });

        // Create SU receipt
        await supabaseServer
            .from('student_union')
            .insert({
                receipt_type,
                date: new Date().toISOString().split('T')[0],
                reference_number,
                student_name,
                student_id,
                program,
                year,
                university,
                description: receipt_type,
                amount_paid: totalAmount,
                service_fee: serviceFee,
                total_amount: total,
                recipient: university,
            });

        return NextResponse.json({
            success: true,
            message: 'Fee payment successful',
            receipt: {
                reference: reference_number,
                student_name,
                student_id,
                amount_paid: totalAmount,
                service_fee: serviceFee,
                total: total,
                date: new Date().toISOString(),
            },
        });
    } catch (error) {
        console.error('SU fee payment error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Payment failed',
            },
            { status: 500 }
        );
    }
}
