// @ts-nocheck
import { NextRequest, NextResponse } from 'next/server';
import { supabaseServer } from '@/lib/supabase/server';
import { getCurrentUser } from '@/lib/auth/jwt';

export const dynamic = 'force-dynamic';

/**
 * POST /api/user/upload-profile-pic
 * Upload profile picture to Supabase Storage
 */
export async function POST(request: NextRequest) {
    try {
        const user = await getCurrentUser();
        if (!user) {
            return NextResponse.json(
                { success: false, error: 'Unauthorized' },
                { status: 401 }
            );
        }

        const formData = await request.formData();
        const file = formData.get('file') as File;

        if (!file) {
            return NextResponse.json(
                { success: false, error: 'No file provided' },
                { status: 400 }
            );
        }

        // Validate file type
        if (!file.type.startsWith('image/')) {
            return NextResponse.json(
                { success: false, error: 'File must be an image' },
                { status: 400 }
            );
        }

        // Upload to Supabase Storage
        const fileName = `${user.userId}-${Date.now()}.${file.name.split('.').pop()}`;
        const { data: uploadData, error: uploadError } = await supabaseServer.storage
            .from('profile-pictures')
            .upload(fileName, file, {
                cacheControl: '3600',
                upsert: false,
            });

        if (uploadError) {
            console.error('Upload error:', uploadError);
            return NextResponse.json(
                { success: false, error: 'Failed to upload image' },
                { status: 500 }
            );
        }

        // Get public URL
        const { data: { publicUrl } } = supabaseServer.storage
            .from('profile-pictures')
            .getPublicUrl(fileName);

        // Update user profile
        await supabaseServer
            .from('users')
            .update({ profile_pic: publicUrl })
            .eq('user_id', user.userId);

        return NextResponse.json({
            success: true,
            url: publicUrl,
        });
    } catch (error) {
        console.error('Profile pic upload error:', error);
        return NextResponse.json(
            {
                success: false,
                error: error instanceof Error ? error.message : 'Upload failed',
            },
            { status: 500 }
        );
    }
}
