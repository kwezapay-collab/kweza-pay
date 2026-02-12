// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { verifyToken } from '@/lib/auth/jwt';

export const dynamic = 'force-dynamic';

export async function POST(request: NextRequest) {
    try {
        // Verify authentication
        const token = request.cookies.get('token')?.value;
        if (!token) {
            return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
        }

        const payload = await verifyToken(token);
        if (!payload || !payload.userId) {
            return NextResponse.json({ error: 'Invalid token' }, { status: 401 });
        }

        const { event_id, pin } = await request.json();

        if (!event_id || !pin) {
            return NextResponse.json(
                { error: 'Event ID and PIN are required' },
                { status: 400 }
            );
        }

        // Get user and verify PIN
        const { data: user, error: userError } = await supabaseServer
            .from('users')
            .select('user_id, pin_hash, full_name')
            .eq('user_id', payload.userId)
            .single();

        if (userError || !user) {
            return NextResponse.json({ error: 'User not found' }, { status: 404 });
        }

        // PIN verification
        const bcrypt = require('bcryptjs');
        const pinValid = await bcrypt.compare(pin, user.pin_hash);
        if (!pinValid) {
            return NextResponse.json({ error: 'Invalid PIN' }, { status: 403 });
        }

        // Get event details
        const { data: event, error: eventError } = await supabaseServer
            .from('events')
            .select('*')
            .eq('event_id', event_id)
            .eq('is_active', true)
            .single();

        if (eventError || !event) {
            return NextResponse.json(
                { error: 'Event not found or inactive' },
                { status: 404 }
            );
        }

        // Check if max tickets reached
        if (event.max_tickets && event.tickets_sold >= event.max_tickets) {
            return NextResponse.json(
                { error: 'Event is sold out' },
                { status: 400 }
            );
        }

        // Generate unique codes
        const ticket_code = `TKT-${Date.now()}-${Math.random().toString(36).substr(2, 9).toUpperCase()}`;
        const reference_code = `REF-${Date.now()}-${Math.random().toString(36).substr(2, 9).toUpperCase()}`;

        // Check for available inventory item
        const { data: invItem } = await supabaseServer
            .from('event_ticket_inventory')
            .select('inventory_id, serial_number')
            .eq('event_id', event_id)
            .eq('is_assigned', false)
            .limit(1)
            .single();

        // Create ticket
        const qr_data = JSON.stringify({
            ticket_code,
            event_id,
            user_id: user.user_id,
            serial_number: invItem?.serial_number || null,
        });

        const { data: newTicket, error: ticketError } = await supabaseServer
            .from('event_tickets')
            .insert({
                event_id,
                user_id: user.user_id,
                ticket_code,
                purchase_amount: event.ticket_price,
                qr_code_data: qr_data,
            })
            .select()
            .single();

        if (ticketError) {
            return NextResponse.json(
                { error: 'Failed to create ticket' },
                { status: 500 }
            );
        }

        // Assign inventory item if found
        if (invItem) {
            await supabaseServer
                .from('event_ticket_inventory')
                .update({
                    is_assigned: true,
                    assigned_at: new Date().toISOString(),
                    ticket_id: newTicket.ticket_id,
                })
                .eq('inventory_id', invItem.inventory_id);
        }

        // Update tickets sold count
        await supabaseServer
            .from('events')
            .update({ tickets_sold: event.tickets_sold + 1 })
            .eq('event_id', event_id);

        // **PAYMENT INTEGRATION**: Create transaction record
        const { error: txnError } = await supabaseServer
            .from('transactions')
            .insert({
                txn_type: 'EVENT_TICKET',
                sender_id: user.user_id,
                receiver_id: event.created_by || 1,
                amount: event.ticket_price,
                reference_code,
                description: `Event Ticket: ${event.event_name}`,
                status: 'completed'
            });

        if (txnError) {
            console.error('Transaction creation failed:', txnError);
            // Continue even if transaction logging fails
        }

        return NextResponse.json({
            success: true,
            message: 'Ticket purchased successfully',
            ticket_code,
            serial_number: invItem?.serial_number || null,
            reference_code,
            event_name: event.event_name,
            amount: event.ticket_price,
            event_date: event.event_date,
            event_location: event.event_location,
        });
    } catch (error) {
        console.error('Purchase ticket error:', error);
        return NextResponse.json(
            { error: 'Internal server error' },
            { status: 500 }
        );
    }
}
