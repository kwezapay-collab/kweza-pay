'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import Toast from '@/components/ui/Toast';
import styles from './merchant.module.css';

interface MerchantData {
    merchant_id: number;
    business_name: string;
    qr_code_token: string;
    is_approved: boolean;
}

interface UserProfile {
    full_name: string;
    wallet_balance: number;
}

export default function MerchantDashboard() {
    const router = useRouter();
    const [profile, setProfile] = useState<UserProfile | null>(null);
    const [merchant, setMerchant] = useState<MerchantData | null>(null);
    const [transactions, setTransactions] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [toast, setToast] = useState({ show: false, message: '', type: 'info' as any });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const [profileRes, merchantRes, txnsRes] = await Promise.all([
                fetch('/api/user/profile'),
                fetch('/api/merchant/info'),
                fetch('/api/transactions/history')
            ]);

            const profileData = await profileRes.json();
            const merchantData = await merchantRes.json();
            const txnsData = await txnsRes.json();

            if (profileData.success) setProfile(profileData.profile);
            if (merchantData.success) setMerchant(merchantData.merchant);
            if (txnsData.success) setTransactions(txnsData.transactions || []);
        } catch (error) {
            console.error('Failed to fetch data:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleLogout = async () => {
        await fetch('/api/auth/logout', { method: 'POST' });
        router.push('/login');
    };

    if (loading) return <div className={styles.loading}>Loading Merchant Dashboard...</div>;

    if (!merchant?.is_approved) {
        return (
            <div className={styles.container}>
                <div className={styles.pending}>
                    <div className={styles.merchantIdBadge} style={{ background: '#fff7ed', color: '#f59e0b' }}>
                        <i className="fas fa-clock"></i>
                    </div>
                    <h2>Application Pending</h2>
                    <p>Your merchant application is awaiting admin approval. Once approved, you will have access to all features.</p>
                    <button className={styles.logoutBtn} style={{ background: 'var(--pp-blue)' }} onClick={() => router.push('/student')}>Return to Student Portal</button>
                    <a href="#" onClick={handleLogout} style={{ marginTop: '20px', color: '#64748b', fontSize: '14px' }}>Logout</a>
                </div>
            </div>
        );
    }

    return (
        <div className={styles.container}>
            <header className={styles.header}>
                <div className={styles.logo}>
                    <img src="/assets/img/logo.png" alt="Kweza Pay" onError={(e) => {
                        e.currentTarget.src = 'https://ui-avatars.com/api/?name=KP&background=0D8ABC&color=fff';
                    }} />
                </div>
                <div className={styles.headerActions}>
                    <span style={{ color: 'white' }}>{merchant?.business_name}</span>
                    <button onClick={handleLogout} className={styles.logoutBtn}>Logout</button>
                </div>
            </header>

            <main className={styles.mainLayout}>
                <div className={styles.contentArea}>
                    <div className={styles.balanceCard}>
                        <div className={styles.balanceCardContent}>
                            <div className={styles.balanceLabel}>Business Balance</div>
                            <div className={styles.balanceAmount}>MWK {profile?.wallet_balance?.toLocaleString() || '0.00'}</div>
                        </div>
                    </div>

                    <div className={styles.card}>
                        <h3 className={styles.cardTitle}><i className="fas fa-history"></i> Recent Sales</h3>
                        <div className={styles.activityList}>
                            {transactions.length > 0 ? transactions.slice(0, 5).map((t, i) => (
                                <div key={i} className={styles.activityItem}>
                                    <div className={styles.activityInfo}>
                                        <span className={styles.activityName}>{t.sender?.full_name || 'Customer'}</span>
                                        <span className={styles.activityDate}>{new Date(t.created_at).toLocaleString()}</span>
                                    </div>
                                    <span className={styles.activityAmount}>+MWK {t.amount.toLocaleString()}</span>
                                </div>
                            )) : (
                                <div className={styles.emptyState}>No sales yet today</div>
                            )}
                        </div>
                    </div>
                </div>

                <aside className={styles.sidebarArea}>
                    <div className={styles.card}>
                        <h3 className={styles.cardTitle}><i className="fas fa-qrcode"></i> Merchant QR</h3>
                        <div className={styles.qrHero}>
                            <div className={styles.qrImage}>
                                <img
                                    src={`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${merchant?.qr_code_token}`}
                                    alt="Merchant QR"
                                    style={{ width: '100%', height: '100%' }}
                                />
                            </div>
                            <div className={styles.merchantIdBadge}>{merchant?.qr_code_token}</div>
                            <span className={styles.qrInstructions}>Scan to pay merchant</span>
                        </div>
                    </div>

                    <div className={styles.actionsGrid}>
                        <div className={styles.actionItem}>
                            <div className={styles.actionIcon}><i className="fas fa-file-invoice-dollar"></i></div>
                            <span className={styles.actionLabel}>Settlement</span>
                        </div>
                        <div className={styles.actionItem}>
                            <div className={styles.actionIcon}><i className="fas fa-user-cog"></i></div>
                            <span className={styles.actionLabel}>Profile</span>
                        </div>
                        <div className={styles.actionItem} onClick={() => router.refresh()}>
                            <div className={styles.actionIcon}><i className="fas fa-sync-alt"></i></div>
                            <span className={styles.actionLabel}>Refresh</span>
                        </div>
                    </div>
                </aside>
            </main>

            <Toast
                message={toast.message}
                type={toast.type}
                isVisible={toast.show}
                onClose={() => setToast({ ...toast, show: false })}
            />
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
        </div>
    );
}
