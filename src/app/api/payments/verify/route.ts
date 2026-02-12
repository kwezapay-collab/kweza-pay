import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';

/**
 * GET /api/payments/verify
 * Verifies a payment with PayChangu (Callback URL)
 */
export async function GET(request: NextRequest) {
    try {
        const { searchParams } = new URL(request.url);
        const tx_ref = searchParams.get('tx_ref');

        if (!tx_ref) {
            return NextResponse.redirect(`${process.env.NEXT_PUBLIC_APP_URL}/student?status=error&message=Missing reference`);
        }

        // 1. Call PayChangu to verify the transaction
        const verifyUrl = `${process.env.PAYCHANGU_BASE_URL}/verify-payment/${tx_ref}`;
        const payChanguSecret = process.env.PAYCHANGU_SECRET_KEY;

        const response = await fetch(verifyUrl, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${payChanguSecret}`,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.status === 'success' && data.data.status === 'success') {
            // 2. Update transaction record in Supabase
            const { error: updateError } = await supabaseServer
                .from('transactions')
                .update({
                    status: 'completed',
                    description: `Payment verified via PayChangu. Ref: ${data.data.reference}`
                } as any)
                .eq('reference_code', tx_ref);

            if (updateError) throw updateError;

            // 3. Redirect to student dashboard with success message
            return NextResponse.redirect(`${process.env.NEXT_PUBLIC_APP_URL}/student?status=success&tx_ref=${tx_ref}`);
        } else {
            // Update transaction to failed
            await supabaseServer
                .from('transactions')
                .update({ status: 'failed', description: `Verification failed: ${data.message || 'Payment not successful'}` } as any)
                .eq('reference_code', tx_ref);

            return NextResponse.redirect(`${process.env.NEXT_PUBLIC_APP_URL}/student?status=failed&message=${encodeURIComponent(data.message || 'Payment verification failed')}`);
        }

    } catch (error) {
        console.error('Payment verification error:', error);
        return NextResponse.redirect(`${process.env.NEXT_PUBLIC_APP_URL}/student?status=error&message=Internal server error`);
    }
}
