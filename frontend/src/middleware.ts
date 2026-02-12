import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { verifyToken } from './lib/auth/jwt';

// Routes that require authentication
const protectedRoutes = [
    '/student',
    '/merchant',
    '/admin',
    '/student-union',
    '/person',
    '/event-owner',
];

// Routes that should redirect to dashboard if already logged in
const authRoutes = ['/login', '/register'];

export async function middleware(request: NextRequest) {
    const { pathname } = request.nextUrl;
    const token = request.cookies.get('token')?.value;

    // Check if route requires authentication
    const isProtectedRoute = protectedRoutes.some((route) =>
        pathname.startsWith(route)
    );
    const isAuthRoute = authRoutes.some((route) => pathname.startsWith(route));

    // If accessing protected route without token, redirect to login
    if (isProtectedRoute && !token) {
        const url = request.nextUrl.clone();
        url.pathname = '/login';
        return NextResponse.redirect(url);
    }

    // If already logged in and accessing auth routes, redirect to appropriate dashboard
    if (isAuthRoute && token) {
        const user = await verifyToken(token);
        if (user) {
            const url = request.nextUrl.clone();

            // Redirect based on user type
            if (user.userType === 'Student') url.pathname = '/student';
            else if (user.userType === 'Merchant') url.pathname = '/merchant';
            else if (user.userType === 'Admin') url.pathname = '/admin';
            else if (user.userType === 'StudentUnion') url.pathname = '/student-union';
            else if (user.userType === 'Person') url.pathname = '/person';

            return NextResponse.redirect(url);
        }
    }

    // Verify token is valid for protected routes
    if (isProtectedRoute && token) {
        const user = await verifyToken(token);

        // If token is invalid, redirect to login
        if (!user) {
            const url = request.nextUrl.clone();
            url.pathname = '/login';
            const response = NextResponse.redirect(url);
            response.cookies.delete('token');
            return response;
        }

        // Check if user is accessing the correct dashboard for their type
        const userDashboard = `/${user.userType.toLowerCase()}`;
        if (pathname.startsWith('/student') && user.userType !== 'Student') {
            const url = request.nextUrl.clone();
            url.pathname = userDashboard;
            return NextResponse.redirect(url);
        }
    }

    return NextResponse.next();
}

export const config = {
    matcher: [
        /*
         * Match all request paths except for the ones starting with:
         * - api/auth (auth endpoints)
         * - _next/static (static files)
         * - _next/image (image optimization files)
         * - favicon.ico (favicon file)
         * - public folder
         */
        '/((?!api/auth|_next/static|_next/image|favicon.ico|.*\\..*|public).*)',
    ],
};
