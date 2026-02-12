import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { getCurrentUser } from '@/lib/auth/jwt';

/**
 * POST /api/payments/initialize
 * Initializes a payment with PayChangu
 */
export async function POST(request: NextRequest) {
    try {
        const user = await getCurrentUser();
        if (!user) {
            return NextResponse.json({ success: false, error: 'Unauthorized' }, { status: 401 });
        }

        const body = await request.json();
        const { amount, description, receiver_id, txn_type, metadata } = body;

        if (!amount || !receiver_id || !txn_type) {
            return NextResponse.json({ success: false, error: 'Missing required fields' }, { status: 400 });
        }

        // 1. Create a pending transaction record in Supabase
        const tx_ref = `txn_${Date.now()}_${Math.random().toString(36).substring(7)}`;

        const { data: transaction, error: txError } = await supabaseServer
            .from('transactions')
            .insert({
                sender_id: user.userId,
                receiver_id: receiver_id,
                amount: amount,
                txn_type: txn_type as 'EVENT_TICKET' | 'CAFE_PAYMENT' | 'QR_PAY',
                status: 'pending',
                reference_code: tx_ref,
                description: description || `Payment for ${txn_type}`
            })
            .select()
            .single();

        if (txError) throw txError;

        // 2. Call PayChangu to initialize payment
        const payChanguUrl = `${process.env.PAYCHANGU_BASE_URL}/payment`;
        const payChanguSecret = process.env.PAYCHANGU_SECRET_KEY;

        const response = await fetch(payChanguUrl, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${payChanguSecret}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                amount: amount,
                currency: 'MWK',
                email: 'customer@kwezapay.com', // email not in JWT, could fetch from DB if needed
                first_name: user.fullName.split(' ')[0],
                last_name: user.fullName.split(' ').slice(1).join(' ') || 'User',
                tx_ref: tx_ref,
                callback_url: `${process.env.NEXT_PUBLIC_APP_URL}/api/payments/verify`,
                return_url: `${process.env.NEXT_PUBLIC_APP_URL}/student?status=success&tx_ref=${tx_ref}`,
                cancel_url: `${process.env.NEXT_PUBLIC_APP_URL}/student?status=cancelled`,
                meta: {
                    user_id: user.userId,
                    transaction_id: (transaction as any).txn_id,
                    ...metadata
                }
            })
        });

        const data = await response.json();

        if (data.status === 'success') {
            return NextResponse.json({
                success: true,
                checkout_url: data.data.checkout_url,
                tx_ref: tx_ref
            });
        } else {
            // Update transaction to failed if PayChangu initialization fails
            await supabaseServer
                .from('transactions')
                .update({ status: 'failed', description: `PayChangu error: ${data.message}` })
                .eq('txn_id', (transaction as any).txn_id);

            return NextResponse.json({
                success: false,
                error: data.message || 'Failed to initialize payment with PayChangu'
            }, { status: 500 });
        }

    } catch (error) {
        console.error('Payment initialization error:', error);
        return NextResponse.json({
            success: false,
            error: error instanceof Error ? error.message : 'Internal server error'
        }, { status: 500 });
    }
}
