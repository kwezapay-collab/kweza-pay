import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@/lib/supabase/server';
import { verifyJWT } from '@/lib/auth/jwt';

export async function POST(request: NextRequest) {
    try {
        // Verify authentication
        const token = request.cookies.get('auth_token')?.value;
        if (!token) {
            return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
        }

        const payload = await verifyJWT(token);
        if (!payload || !payload.userId) {
            return NextResponse.json({ error: 'Invalid token' }, { status: 401 });
        }

        const { cafe_id, amount, description } = await request.json();

        if (!cafe_id || !amount || amount <= 0) {
            return NextResponse.json(
                { error: 'Cafe ID and valid amount are required' },
                { status: 400 }
            );
        }

        const supabase = await createClient();

        // Get user
        const { data: user, error: userError } = await supabase
            .from('users')
            .select('user_id, full_name')
            .eq('user_id', payload.userId)
            .single();

        if (userError || !user) {
            return NextResponse.json({ error: 'User not found' }, { status: 404 });
        }

        // Get cafe details
        const { data: cafe, error: cafeError } = await supabase
            .from('campus_cafes')
            .select('*')
            .eq('cafe_id', cafe_id)
            .eq('is_active', true)
            .single();

        if (cafeError || !cafe) {
            return NextResponse.json(
                { error: 'Cafe not found or inactive' },
                { status: 404 }
            );
        }

        // Generate reference code
        const reference_code = `CAFE-${Date.now()}-${Math.random().toString(36).substr(2, 9).toUpperCase()}`;

        // **PAYMENT INTEGRATION**: Create transaction record
        const { data: transaction, error: txnError } = await supabase
            .from('transactions')
            .insert({
                txn_type: 'CAFE_PAYMENT',
                sender_id: user.user_id,
                receiver_id: cafe.created_by || 1,
                amount: Number(amount),
                reference_code,
                description: description || `Cafe Payment: ${cafe.cafe_name}`,
            })
            .select()
            .single();

        if (txnError) {
            console.error('Transaction creation failed:', txnError);
            return NextResponse.json(
                { error: 'Failed to process payment' },
                { status: 500 }
            );
        }

        return NextResponse.json({
            success: true,
            message: 'Cafe payment successful',
            reference_code,
            cafe_name: cafe.cafe_name,
            amount,
            transaction_id: transaction.txn_id,
        });
    } catch (error) {
        console.error('Cafe payment error:', error);
        return NextResponse.json(
            { error: 'Internal server error' },
            { status: 500 }
        );
    }
}
