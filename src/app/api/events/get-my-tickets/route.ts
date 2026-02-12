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

        // Get user's tickets with event details
        const { data: tickets, error } = await supabase
            .from('event_tickets')
            .select(`
        ticket_id,
        ticket_code,
        purchase_amount,
        qr_code_data,
        is_used,
        used_at,
        created_at,
        events (
          event_id,
          event_name,
          event_description,
          event_picture,
          event_date,
          event_location
        )
      `)
            .eq('user_id', payload.userId)
            .order('created_at', { ascending: false });

        if (error) {
            return NextResponse.json({ error: 'Failed to fetch tickets' }, { status: 500 });
        }

        return NextResponse.json({
            success: true,
            tickets: tickets || [],
        });
    } catch (error) {
        console.error('Get my tickets error:', error);
        return NextResponse.json({ error: 'Internal server error' }, { status: 500 });
    }
}
