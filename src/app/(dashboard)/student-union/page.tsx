'use client';

import { redirect } from 'next/navigation';

/**
 * Student Union Dashboard
 * For now, supports basic functionality similar to person/student
 */
export default function StudentUnionDashboard() {
    redirect('/student');
}
