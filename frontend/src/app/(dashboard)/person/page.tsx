'use client';

import { redirect } from 'next/navigation';

/**
 * Person Dashboard - Similar to Student but without student-specific features
 */
export default function PersonDashboard() {
    // For now, redirect to Student dashboard as they share similar functionality
    redirect('/student');
}
