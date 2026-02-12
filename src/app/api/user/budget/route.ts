// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@/lib/supabase/server';
import { verifyJWT } from '@/lib/auth/jwt';

export const dynamic = 'force-dynamic';

export async function GET(request: NextRequest) {
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

        const supabase = await createClient();

        // Get user's spending by category from transactions
        const { data: transactions, error } = await supabase
            .from('transactions')
            .select('txn_type, amount, created_at')
            .eq('sender_id', payload.userId)
            .order('created_at', { ascending: false });

        if (error) {
            return NextResponse.json({ error: 'Failed to fetch budget data' }, { status: 500 });
        }

        // Calculate totals by category
        const categoryTotals: Record<string, number> = {};
        let totalSpent = 0;

        transactions?.forEach(txn => {
            const category = txn.txn_type;
            categoryTotals[category] = (categoryTotals[category] || 0) + Number(txn.amount);
            totalSpent += Number(txn.amount);
        });

        // Get this month's spending
        const startOfMonth = new Date();
        startOfMonth.setDate(1);
        startOfMonth.setHours(0, 0, 0, 0);

        const thisMonthTransactions = transactions?.filter(txn =>
            new Date(txn.created_at) >= startOfMonth
        ) || [];

        const thisMonthTotal = thisMonthTransactions.reduce(
            (sum, txn) => sum + Number(txn.amount),
            0
        );

        return NextResponse.json({
            success: true,
            budget: {
                total_spent: totalSpent,
                this_month_spent: thisMonthTotal,
                by_category: categoryTotals,
                transaction_count: transactions?.length || 0,
                recent_transactions: transactions?.slice(0, 10) || [],
            },
        });
    } catch (error) {
        console.error('Budget API error:', error);
        return NextResponse.json({ error: 'Internal server error' }, { status: 500 });
    }
}
