import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@/lib/supabase/server';

export async function GET(request: NextRequest) {
    try {
        const supabase = await createClient();

        // Check if user is admin
        const token = request.cookies.get('auth_token')?.value;
        let isAdmin = false;

        if (token) {
            const { verifyJWT } = await import('@/lib/auth/jwt');
            const payload = await verifyJWT(token);

            if (payload?.userId) {
                const { data: user } = await supabase
                    .from('users')
                    .select('user_type')
                    .eq('user_id', payload.userId)
                    .single();

                isAdmin = user?.user_type === 'Admin';
            }
        }

        // Build query
        let query = supabase
            .from('events')
            .select('*')
            .order('created_at', { ascending: false });

        // Only show active events to non-admins
        if (!isAdmin) {
            query = query.eq('is_active', true);
        }

        const { data: events, error } = await query;

        if (error) {
            return NextResponse.json({ error: 'Failed to fetch events' }, { status: 500 });
        }

        // Attach QR image URL if missing but Airtel code exists
        const eventsWithQR = events?.map(event => ({
            ...event,
            airtel_qr_image: event.airtel_qr_image ||
                (event.airtel_money_code
                    ? `https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=${encodeURIComponent(event.airtel_money_code)}`
                    : null)
        }));

        return NextResponse.json({
            success: true,
            events: eventsWithQR || [],
        });
    } catch (error) {
        console.error('Get events error:', error);
        return NextResponse.json({ error: 'Internal server error' }, { status: 500 });
    }
}
