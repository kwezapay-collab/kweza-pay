// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { verifyJWT } from '@/lib/auth/jwt';

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

        const supabase = supabaseServer;

        // Verify admin
        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('user_type')
            .eq('user_id', payload.userId)
            .single();

        // @ts-ignore - userData is inferred as never due to complex Supabase query
        if (userError || !userData || userData.user_type !== 'Admin') {
            return NextResponse.json({ error: 'Unauthorized - Admin access required' }, { status: 403 });
        }

        // Get user counts by type
        const { data: userCounts, error: userCountsError } = await supabase
            .from('users')
            .select('user_type')
            .order('user_type');

        const countsByType: Record<string, number> = {};
        if (userCounts) {
            userCounts.forEach(u => {
                // @ts-ignore
                countsByType[u.user_type] = (countsByType[u.user_type] || 0) + 1;
            });
        }

        // Get total transaction volume
        const { data: transactions, error: txnError } = await supabase
            .from('transactions')
            .select('amount, txn_type, created_at');

        // @ts-ignore
        const totalVolume = transactions?.reduce((sum, txn) => sum + Number(txn.amount), 0) || 0;
        const transactionCount = transactions?.length || 0;

        // Get volume by type
        const volumeByType: Record<string, number> = {};
        if (transactions) {
            transactions.forEach(txn => {
                // @ts-ignore
                volumeByType[txn.txn_type] = (volumeByType[txn.txn_type] || 0) + Number(txn.amount);
            });
        }

        // Get this month's data
        const startOfMonth = new Date();
        startOfMonth.setDate(1);
        startOfMonth.setHours(0, 0, 0, 0);

        const thisMonthTransactions = transactions?.filter(txn =>
            // @ts-ignore
            new Date(txn.created_at) >= startOfMonth
        ) || [];

        const thisMonthVolume = thisMonthTransactions.reduce(
            // @ts-ignore
            (sum, txn) => sum + Number(txn.amount),
            0
        );

        // Get event and cafe counts
        const { count: eventCount } = await supabase
            .from('events')
            .select('*', { count: 'exact', head: true });

        const { count: cafeCount } = await supabase
            .from('campus_cafes')
            .select('*', { count: 'exact', head: true });

        const { count: ticketsSold } = await supabase
            .from('event_tickets')
            .select('*', { count: 'exact', head: true });

        return NextResponse.json({
            success: true,
            analytics: {
                users: {
                    total: userCounts?.length || 0,
                    by_type: countsByType,
                },
                transactions: {
                    total_count: transactionCount,
                    total_volume: totalVolume,
                    this_month_volume: thisMonthVolume,
                    this_month_count: thisMonthTransactions.length,
                    by_type: volumeByType,
                },
                events: {
                    total_events: eventCount || 0,
                    tickets_sold: ticketsSold || 0,
                },
                cafes: {
                    total_cafes: cafeCount || 0,
                },
            },
        });
    } catch (error) {
        console.error('Analytics API error:', error);
        return NextResponse.json({ error: 'Internal server error' }, { status: 500 });
    }
}
