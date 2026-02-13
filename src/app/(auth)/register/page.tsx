'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import styles from './register.module.css';

export default function RegisterPage() {
    const router = useRouter();
    const [formData, setFormData] = useState({
        phone_number: '',
        full_name: '',
        pin: '',
        confirmPin: '',
        user_type: 'Student',
        email: '',
        registration_number: '',
        university: '',
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');

        if (formData.pin !== formData.confirmPin) {
            setError('PINs do not match');
            return;
        }

        if (formData.pin.length < 4 || formData.pin.length > 6) {
            setError('PIN must be 4-6 digits');
            return;
        }

        setLoading(true);

        try {
            const response = await fetch('/api/auth/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    phone_number: formData.phone_number,
                    full_name: formData.full_name,
                    pin: formData.pin,
                    user_type: formData.user_type,
                    email: formData.email,
                    registration_number: formData.registration_number,
                    university: formData.university,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                setError(data.error || 'Registration failed');
                setLoading(false);
                return;
            }

            // Redirect to verify page
            router.push(`/verify?phone=${formData.phone_number}`);
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

                <h1 className={styles.title}>Create Your Account</h1>
                <p className={styles.subtitle}>Join Kweza Pay today</p>

                {error && <div className={styles.error}>{error}</div>}

                <form onSubmit={handleSubmit} className={styles.form}>
                    <div className={styles.formGroup}>
                        <label htmlFor="full_name">Full Name</label>
                        <input
                            id="full_name"
                            type="text"
                            value={formData.full_name}
                            onChange={(e) => setFormData({ ...formData, full_name: e.target.value })}
                            required
                            disabled={loading}
                            className={styles.input}
                        />
                    </div>

                    <div className={styles.formGroup}>
                        <label htmlFor="phone_number">Phone Number</label>
                        <input
                            id="phone_number"
                            type="tel"
                            value={formData.phone_number}
                            onChange={(e) => setFormData({ ...formData, phone_number: e.target.value })}
                            placeholder="265999123456"
                            required
                            disabled={loading}
                            className={styles.input}
                        />
                    </div>

                    <div className={styles.formGroup}>
                        <label htmlFor="email">Email (Optional)</label>
                        <input
                            id="email"
                            type="email"
                            value={formData.email}
                            onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                            disabled={loading}
                            className={styles.input}
                        />
                    </div>

                    <div className={styles.formGroup}>
                        <label htmlFor="user_type">Account Type</label>
                        <select
                            id="user_type"
                            value={formData.user_type}
                            onChange={(e) => setFormData({ ...formData, user_type: e.target.value })}
                            disabled={loading}
                            className={styles.input}
                        >
                            <option value="Student">Student</option>
                            <option value="Merchant">Merchant</option>
                            <option value="Person">Person</option>
                        </select>
                    </div>

                    {formData.user_type === 'Student' && (
                        <>
                            <div className={styles.formGroup}>
                                <label htmlFor="registration_number">Registration Number</label>
                                <input
                                    id="registration_number"
                                    type="text"
                                    value={formData.registration_number}
                                    onChange={(e) => setFormData({ ...formData, registration_number: e.target.value })}
                                    disabled={loading}
                                    className={styles.input}
                                />
                            </div>
                            <div className={styles.formGroup}>
                                <label htmlFor="university">University</label>
                                <input
                                    id="university"
                                    type="text"
                                    value={formData.university}
                                    onChange={(e) => setFormData({ ...formData, university: e.target.value })}
                                    placeholder="DMI St. John the Baptist University"
                                    disabled={loading}
                                    className={styles.input}
                                />
                            </div>
                        </>
                    )}

                    <div className={styles.formGroup}>
                        <label htmlFor="pin">PIN (4-6 digits)</label>
                        <input
                            id="pin"
                            type="password"
                            value={formData.pin}
                            onChange={(e) => setFormData({ ...formData, pin: e.target.value })}
                            maxLength={6}
                            pattern="\d{4,6}"
                            required
                            disabled={loading}
                            className={styles.input}
                        />
                    </div>

                    <div className={styles.formGroup}>
                        <label htmlFor="confirmPin">Confirm PIN</label>
                        <input
                            id="confirmPin"
                            type="password"
                            value={formData.confirmPin}
                            onChange={(e) => setFormData({ ...formData, confirmPin: e.target.value })}
                            maxLength={6}
                            pattern="\d{4,6}"
                            required
                            disabled={loading}
                            className={styles.input}
                        />
                    </div>

                    <button type="submit" className={styles.button} disabled={loading}>
                        {loading ? 'Creating Account...' : 'Sign Up'}
                    </button>
                </form>

                <div className={styles.footer}>
                    <p>
                        Already have an account? <a href="/login">Log in</a>
                    </p>
                </div>
            </div>
        </div>
    );
}
