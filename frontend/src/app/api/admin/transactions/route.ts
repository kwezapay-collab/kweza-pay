import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { getCurrentUser } from '@/lib/auth/jwt';

/**
 * GET /api/admin/transactions
 * Get all transactions (Admin only)
 */
export async function GET(request: NextRequest) {
    try {
        const user = await getCurrentUser();
        if (!user || user.userType !== 'Admin') {
            return NextResponse.json(
                { success: false, error: 'Unauthorized - Admin access required' },
                { status: 403 }
            );
        }

        const { data: transactions, error } = await supabaseServer
            .from('transactions')
            .select(`
                *,
                sender:users!sender_id(full_name),
                recipient:users!recipient_id(full_name)
            `)
            .order('created_at', { ascending: false });

        if (error) throw error;

        return NextResponse.json({
            success: true,
            transactions: transactions || [],
        });
    } catch (error) {
        console.error('Get admin transactions error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to fetch transactions',
            },
            { status: 500 }
        );
    }
}
