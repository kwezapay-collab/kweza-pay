// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';

/**
 * POST /api/payments/webhook
 * Handles PayChangu Webhook notifications
 */
export async function POST(request: NextRequest) {
    try {
        const body = await request.json();
        const { status, data } = body;

        if (status === 'success' && data && data.tx_ref) {
            // Update transaction record in Supabase
            // We use .update().eq() to ensure we only update if it was pending
            // This prevents double processing if the redirect already succeeded
            const { error: updateError } = await supabaseServer
                .from('transactions')
                .update({
                    status: 'completed',
                    description: `Payment verified via Webhook. Ref: ${data.reference}`
                })
                .eq('reference_code', data.tx_ref)
                .eq('status', 'pending');

            if (updateError) throw updateError;

            return NextResponse.json({ success: true, message: 'Webhook processed' });
        }

        return NextResponse.json({ success: true, message: 'Payload received but not matching success criteria' });

    } catch (error) {
        console.error('Webhook processing error:', error);
        return NextResponse.json({
            success: false,
            error: error instanceof Error ? error.message : 'Internal server error'
        }, { status: 500 });
    }
}
