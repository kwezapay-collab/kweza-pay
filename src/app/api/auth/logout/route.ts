import { NextRequest, NextResponse } from 'next/server';
import { clearAuthToken } from '@/lib/auth/jwt';

/**
 * POST /api/auth/logout
 * Converts frontend/logout.php
 */
export async function POST(request: NextRequest) {
    clearAuthToken();

    return NextResponse.json({
        success: true,
        message: 'Logged out successfully',
    });
}
