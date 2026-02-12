// @ts-nocheck
import { SignJWT, jwtVerify } from 'jose';
import { cookies } from 'next/headers';

const JWT_SECRET = new TextEncoder().encode(
    process.env.JWT_SECRET || 'fallback-secret-change-in-production'
);

export interface JWTPayload {
    userId: number;
    userType: 'Student' | 'Merchant' | 'Admin' | 'StudentUnion' | 'Person';
    fullName: string;
    exp: number;
}

/**
 * Generate a JWT token for authenticated user
 * Replaces PHP's $_SESSION
 */
export async function generateToken(payload: Omit<JWTPayload, 'exp'>): Promise<string> {
    const token = await new SignJWT({ ...payload })
        .setProtectedHeader({ alg: 'HS256' })
        .setExpirationTime('24h')
        .setIssuedAt()
        .sign(JWT_SECRET);

    return token;
}

/**
 * Verify and decode a JWT token
 */
export async function verifyToken(token: string): Promise<JWTPayload | null> {
    try {
        const { payload } = await jwtVerify(token, JWT_SECRET);
        return payload as JWTPayload;
    } catch (error) {
        console.error('JWT verification failed:', error);
        return null;
    }
}

/**
 * Alias for verifyToken used in some API routes
 */
export { verifyToken as verifyJWT };

/**
 * Get current user from cookies
 * Replaces PHP's getCurrentUser()
 */
export async function getCurrentUser(): Promise<JWTPayload | null> {
    const cookieStore = cookies();
    const token = cookieStore.get('token')?.value;

    if (!token) {
        return null;
    }

    return verifyToken(token);
}

/**
 * Set auth token in HTTP-only cookie
 */
export async function setAuthToken(payload: Omit<JWTPayload, 'exp'>) {
    const token = await generateToken(payload);

    cookies().set('token', token, {
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'lax',
        maxAge: 60 * 60 * 24, // 24 hours
        path: '/',
    });

    return token;
}

/**
 * Clear auth token (logout)
 */
export function clearAuthToken() {
    cookies().delete('token');
}
