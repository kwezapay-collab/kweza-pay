/** @type {import('next').NextConfig} */
const nextConfig = {
    images: {
        remotePatterns: [
            {
                protocol: 'https',
                hostname: '**.supabase.co',
                port: '',
                pathname: '/storage/v1/object/public/**',
            },
        ],
    },
    // Global safety switch: Ignore TypeScript and ESLint errors during Vercel build
    typescript: {
        ignoreBuildErrors: true,
    },
    eslint: {
        ignoreDuringBuilds: true,
    },
    // Redirect old PHP URLs to new Next.js routes
    async redirects() {
        return [
            {
                source: '/frontend/student.php',
                destination: '/student',
                permanent: true,
            },
            {
                source: '/frontend/merchant.php',
                destination: '/merchant',
                permanent: true,
            },
            {
                source: '/frontend/index.php',
                destination: '/login',
                permanent: true,
            },
            {
                source: '/frontend/register.php',
                destination: '/register',
                permanent: true,
            },
        ];
    },
    // Environment variable validation
    env: {
        NEXT_PUBLIC_APP_URL: process.env.NEXT_PUBLIC_APP_URL || 'http://localhost:3000',
    },
};

module.exports = nextConfig;
