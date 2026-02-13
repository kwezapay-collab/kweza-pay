'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import styles from './login.module.css';

/**
 * Login Page - Matches Legacy PHP index.php Design Exactly
 */
export default function LoginPage() {
    const router = useRouter();
    const [phone, setPhone] = useState('');
    const [pin, setPin] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [roles, setRoles] = useState<string[]>([]);
    const [selectedRole, setSelectedRole] = useState('');
    const [userName, setUserName] = useState('');

    const handleSubmit = async (e: React.FormEvent, roleOverride?: string) => {
        if (e) e.preventDefault();
        setError('');
        setLoading(true);

        const roleToSubmit = roleOverride || selectedRole;

        try {
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    phone,
                    pin,
                    selected_role: roleToSubmit || undefined,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                setError(data.error || 'Login failed');
                setLoading(false);
                return;
            }

            if (data.requires_selection) {
                setRoles(data.roles);
                setUserName(data.user.name);
                setLoading(false);
                return;
            }

            // Login successful
            router.push(`/${data.redirect}`);
        } catch (err) {
            setError('Network error. Please try again.');
            setLoading(false);
        }
    };

    const handleRoleSelect = (role: string) => {
        setSelectedRole(role);
        handleSubmit(null as any, role);
    };

    const resetLogin = () => {
        setRoles([]);
        setSelectedRole('');
        setError('');
        setLoading(false);
    };

    return (
        <div className={`kweza-pattern-bg ${styles.container}`}>
            <main className={styles.loginWrapper}>
                {/* Logo Section */}
                <div className={styles.logoSection}>
                    <img src="/assets/img/logo.png" alt="Kweza Pay Logo" className={styles.logoImage} onError={(e) => {
                        e.currentTarget.src = 'https://ui-avatars.com/api/?name=KP&size=128';
                    }} />
                    <h1 className={styles.logoTitle}>Welcome to </h1>
                    <h2 className={styles.logoSubtitle}>Kweza Pay</h2>
                    <p className={styles.tagline}>Secure payments for your community</p>
                </div>

                {/* Form Card */}
                <div className={styles.card}>
                    {/* Tabs */}
                    <div className={styles.tabs}>
                        <button className={`${styles.tab} ${styles.activeTab}`}>Login</button>
                        <button className={`${styles.tab} ${styles.inactiveTab}`} onClick={() => router.push('/register')}>Sign Up</button>
                    </div>

                    <div className={styles.formSection}>
                        {roles.length > 0 ? (
                            <div className={styles.roleSelection}>
                                <div style={{ textAlign: 'center', marginBottom: '16px' }}>
                                    <h3 style={{ fontSize: '18px', fontWeight: 'bold', color: '#1e293b' }}>Select Account</h3>
                                    <p style={{ fontSize: '14px', color: '#64748b' }}>Choose the account you want to enter</p>
                                </div>
                                {roles.map((role) => (
                                    <button
                                        key={role}
                                        type="button"
                                        className={styles.roleBtn}
                                        onClick={() => handleRoleSelect(role)}
                                    >
                                        <span className={`material-symbols-rounded ${styles.roleIcon}`}>
                                            {role === 'Student' ? 'school' :
                                                role === 'Merchant' ? 'storefront' :
                                                    role === 'Admin' ? 'admin_panel_settings' :
                                                        role === 'StudentUnion' ? 'group' : 'person'}
                                        </span>
                                        <span className={styles.roleLabel}>{role} Account</span>
                                    </button>
                                ))}
                                <button type="button" onClick={resetLogin} className={styles.backToLogin}>
                                    Back to login
                                </button>
                            </div>
                        ) : (
                            <form onSubmit={handleSubmit} className={styles.form}>
                                <div className={styles.formGroup}>
                                    <label htmlFor="phone">Phone Number</label>
                                    <div className={styles.inputWrapper}>
                                        <span className={`material-symbols-rounded ${styles.inputIcon}`}>phone_iphone</span>
                                        <input
                                            id="phone"
                                            type="tel"
                                            value={phone}
                                            onChange={(e) => setPhone(e.target.value)}
                                            placeholder="e.g. 0712 345 678"
                                            className={styles.input}
                                            required
                                        />
                                    </div>
                                </div>

                                <div className={styles.formGroup}>
                                    <label htmlFor="pin">
                                        <span>PIN entry</span>
                                        <a href="#" className={styles.forgotPin} onClick={(e) => { e.preventDefault(); alert('Contact Admin to reset PIN'); }}>Forgot PIN?</a>
                                    </label>
                                    <div className={styles.inputWrapper}>
                                        <span className={`material-symbols-rounded ${styles.inputIcon}`}>lock</span>
                                        <input
                                            id="pin"
                                            type="password"
                                            value={pin}
                                            onChange={(e) => setPin(e.target.value)}
                                            placeholder="••••••"
                                            maxLength={6}
                                            className={`${styles.input} ${styles.pinInput}`}
                                            required
                                            autoComplete="off"
                                        />
                                    </div>
                                </div>

                                <div className={styles.rememberMe}>
                                    <input type="checkbox" id="remember" />
                                    <label htmlFor="remember">Keep me logged in</label>
                                </div>

                                {error && <div className={styles.errorMsg}>{error}</div>}

                                <button type="submit" className={styles.submitBtn} disabled={loading}>
                                    {loading ? 'Logging in...' : 'Login to Account'}
                                </button>
                            </form>
                        )}
                    </div>
                </div>

                {/* Footer Links */}
                <div className={styles.footerLinks}>
                    <a href="#">Help Center</a>
                    <a href="#">Contact Support</a>
                    <a href="#">Security</a>
                </div>

                <footer className={styles.pageFooter}>
                    © 2026 Kweza Pay. All rights reserved. Professional financial services for education.
                </footer>
            </main>
        </div>
    );
}
