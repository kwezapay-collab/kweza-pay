'use client';

import { useState, useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import styles from './verify.module.css';

export default function VerifyPage() {
    const router = useRouter();
    const searchParams = useSearchParams();
    const phoneFromUrl = searchParams.get('phone') || '';

    const [phone, setPhone] = useState(phoneFromUrl);
    const [code, setCode] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState(false);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');
        setLoading(true);

        try {
            const response = await fetch('/api/auth/verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    phone_number: phone,
                    verification_code: code,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                setError(data.error || 'Verification failed');
                setLoading(false);
                return;
            }

            setSuccess(true);

            // Redirect to login after 2 seconds
            setTimeout(() => {
                router.push('/login');
            }, 2000);
        } catch (err) {
            setError('Network error. Please try again.');
            setLoading(false);
        }
    };

    return (
        <div className={styles.container}>
            <div className={styles.card}>
                <div className={styles.logo}>
                    <img src="/assets/img/logo.png" alt="Kweza Pay" onError={(e) => {
                        e.currentTarget.src = 'https://ui-avatars.com/api/?name=Kweza+Pay&background=0D8ABC&color=fff';
                    }} />
                </div>

                <h1 className={styles.title}>Verify Your Account</h1>
                <p className={styles.subtitle}>
                    Enter the 6-digit code sent to your phone/email
                </p>

                {error && <div className={styles.error}>{error}</div>}
                {success && (
                    <div className={styles.success}>
                        ✓ Account verified successfully! Redirecting to login...
                    </div>
                )}

                {!success && (
                    <form onSubmit={handleSubmit} className={styles.form}>
                        <div className={styles.formGroup}>
                            <label htmlFor="phone">Phone Number</label>
                            <input
                                id="phone"
                                type="tel"
                                value={phone}
                                onChange={(e) => setPhone(e.target.value)}
                                placeholder="265999123456"
                                required
                                disabled={loading}
                                className={styles.input}
                            />
                        </div>

                        <div className={styles.formGroup}>
                            <label htmlFor="code">Verification Code</label>
                            <input
                                id="code"
                                type="text"
                                value={code}
                                onChange={(e) => setCode(e.target.value)}
                                placeholder="123456"
                                maxLength={6}
                                pattern="\d{6}"
                                required
                                disabled={loading}
                                className={styles.codeInput}
                            />
                        </div>

                        <button type="submit" className={styles.button} disabled={loading}>
                            {loading ? 'Verifying...' : 'Verify Account'}
                        </button>
                    </form>
                )}

                <div className={styles.footer}>
                    <p>
                        Didn't receive a code? <a href="#">Resend</a>
                    </p>
                    <p>
                        <a href="/login">Back to Login</a>
                    </p>
                </div>
            </div>
        </div>
    );
}
