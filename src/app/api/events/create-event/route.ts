// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@/lib/supabase/server';
import { verifyJWT } from '@/lib/auth/jwt';

export const dynamic = 'force-dynamic';

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

        const supabase = await createClient();

        // Verify admin
        const { data: user, error: userError } = await supabase
            .from('users')
            .select('user_type')
            .eq('user_id', payload.userId)
            .single();

        if (userError || user?.user_type !== 'Admin') {
            return NextResponse.json({ error: 'Unauthorized - Admin access required' }, { status: 403 });
        }

        const formData = await request.formData();
        const event_name = formData.get('event_name') as string;
        const event_description = formData.get('event_description') as string;
        const ticket_price = parseFloat(formData.get('ticket_price') as string);
        const event_date = formData.get('event_date') as string;
        const event_location = formData.get('event_location') as string;
        const airtel_money_code = formData.get('airtel_money_code') as string;
        const max_tickets = formData.get('max_tickets') ? parseInt(formData.get('max_tickets') as string) : null;

        if (!event_name || !ticket_price || ticket_price <= 0) {
            return NextResponse.json(
                { error: 'Event name and valid ticket price are required' },
                { status: 400 }
            );
        }

        // Create event
        const { data: event, error: eventError } = await supabase
            .from('events')
            .insert({
                event_name,
                event_description,
                ticket_price,
                event_date: event_date || null,
                event_location,
                airtel_money_code,
                max_tickets,
                created_by: payload.userId,
                is_active: true,
            })
            .select()
            .single();

        if (eventError) {
            console.error('Event creation failed:', eventError);
            return NextResponse.json(
                { error: 'Failed to create event' },
                { status: 500 }
            );
        }

        return NextResponse.json({
            success: true,
            message: 'Event created successfully',
            event_id: event.event_id,
            event,
        });
    } catch (error) {
        console.error('Create event error:', error);
        return NextResponse.json(
            { error: 'Internal server error' },
            { status: 500 }
        );
    }
}
