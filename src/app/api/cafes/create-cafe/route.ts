import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@/lib/supabase/server';
import { verifyJWT } from '@/lib/auth/jwt';

export async function POST(request: NextRequest) {
    try {
        const token = request.cookies.get('auth_token')?.value;
        if (!token) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });

        const payload = await verifyJWT(token);
        if (!payload) return NextResponse.json({ error: 'Invalid token' }, { status: 401 });

        const supabase = await createClient();
        const { data: user } = await supabase.from('users').select('user_type').eq('user_id', payload.userId).single();
        if (user?.user_type !== 'Admin') return NextResponse.json({ error: 'Forbidden' }, { status: 403 });

        const { cafe_name, cafe_description, airtel_money_code } = await request.json();

        if (!cafe_name || !airtel_money_code) {
            return NextResponse.json({ error: 'Cafe name and Airtel code are required' }, { status: 400 });
        }

        const { data: cafe, error } = await supabase
            .from('campus_cafes')
            .insert({
                cafe_name,
                cafe_description,
                airtel_money_code,
                created_by: payload.userId,
                is_active: true
            })
            .select()
            .single();

        if (error) throw error;

        return NextResponse.json({ success: true, cafe });
    } catch (error) {
        return NextResponse.json({ error: 'Internal server error' }, { status: 500 });
    }
}
