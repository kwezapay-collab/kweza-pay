// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@/lib/supabase/server';
import { verifyJWT } from '@/lib/auth/jwt';

export const dynamic = 'force-dynamic';

export async function POST(request: NextRequest) {
    try {
        const token = request.cookies.get('auth_token')?.value;
        if (!token) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });

        const payload = await verifyJWT(token);
        if (!payload) return NextResponse.json({ error: 'Invalid token' }, { status: 401 });

        const supabase = await createClient();
        const { data: user } = await supabase.from('users').select('user_type').eq('user_id', payload.userId).single();
        if (user?.user_type !== 'Admin') return NextResponse.json({ error: 'Forbidden' }, { status: 403 });

        const { cafe_id, meal_name, meal_price, meal_description } = await request.json();

        if (!cafe_id || !meal_name || !meal_price) {
            return NextResponse.json({ error: 'Missing required fields' }, { status: 400 });
        }

        const { data: meal, error } = await supabase
            .from('cafe_meals')
            .insert({
                cafe_id,
                meal_name,
                meal_price: parseFloat(meal_price),
                meal_description,
                is_available: true
            })
            .select()
            .single();

        if (error) throw error;

        return NextResponse.json({ success: true, meal });
    } catch (error) {
        return NextResponse.json({ error: 'Internal server error' }, { status: 500 });
    }
}
