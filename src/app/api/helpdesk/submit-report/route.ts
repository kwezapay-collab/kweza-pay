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

        const { subject, message } = await request.json();

        if (!subject || !message || subject.trim() === '' || message.trim() === '') {
            return NextResponse.json(
                { error: 'Subject and message are required' },
                { status: 400 }
            );
        }

        if (subject.length > 255) {
            return NextResponse.json(
                { error: 'Subject is too long (max 255 characters)' },
                { status: 400 }
            );
        }

        const supabase = await createClient();

        // Get user type
        const { data: user, error: userError } = await supabase
            .from('users')
            .select('user_type')
            .eq('user_id', payload.userId)
            .single();

        if (userError || !user) {
            return NextResponse.json({ error: 'User not found' }, { status: 404 });
        }

        // Create help report
        const { data: report, error: reportError } = await supabase
            .from('help_reports')
            .insert({
                user_id: payload.userId,
                user_type: user.user_type,
                subject: subject.trim(),
                message: message.trim(),
                status: 'NEW',
            })
            .select()
            .single();

        if (reportError) {
            console.error('Help report creation failed:', reportError);
            return NextResponse.json(
                { error: 'Failed to submit report' },
                { status: 500 }
            );
        }

        return NextResponse.json({
            success: true,
            message: 'Report submitted successfully',
            report_id: report.report_id,
        });
    } catch (error) {
        console.error('Submit report error:', error);
        return NextResponse.json(
            { error: 'Internal server error' },
            { status: 500 }
        );
    }
}
