/**
 * Toast Notification Component
 */

'use client';

import { useEffect } from 'react';
import styles from './Toast.module.css';

interface ToastProps {
    message: string;
    type?: 'success' | 'error' | 'info';
    isVisible: boolean;
    onClose: () => void;
    duration?: number;
}

export default function Toast({
    message,
    type = 'info',
    isVisible,
    onClose,
    duration = 3000,
}: ToastProps) {
    useEffect(() => {
        if (isVisible && duration > 0) {
            const timer = setTimeout(onClose, duration);
            return () => clearTimeout(timer);
        }
    }, [isVisible, duration, onClose]);

    if (!isVisible) return null;

    const icon = {
        success: '✓',
        error: '✕',
        info: 'ⓘ',
    }[type];

    return (
        <div className={`${styles.toast} ${styles[type]}`}>
            <span className={styles.icon}>{icon}</span>
            <span className={styles.message}>{message}</span>
            <button className={styles.close} onClick={onClose}>
                ×
            </button>
        </div>
    );
}
