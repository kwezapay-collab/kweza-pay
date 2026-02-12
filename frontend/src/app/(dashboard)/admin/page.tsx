'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import Modal from '@/components/ui/Modal';
import Toast from '@/components/ui/Toast';
import AnalyticsDashboard from '@/components/analytics/AnalyticsDashboard';
import styles from './admin.module.css';

interface Merchant {
    merchant_id: number;
    user_id: number;
    business_name: string;
    is_approved: boolean;
    created_at: string;
}

interface User {
    user_id: number;
    full_name: string;
    phone_number: string;
    user_type: string;
    created_at: string;
    wallet_balance?: number;
}

export default function AdminDashboard() {
    const router = useRouter();
    const [profile, setProfile] = useState<any>(null);
    const [pendingMerchants, setPendingMerchants] = useState<Merchant[]>([]);
    const [users, setUsers] = useState<User[]>([]);
    const [analytics, setAnalytics] = useState<any>(null);
    const [events, setEvents] = useState<any[]>([]);
    const [cafes, setCafes] = useState<any[]>([]);
    const [transactions, setTransactions] = useState<any[]>([]);
    const [reports, setReports] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [toast, setToast] = useState({ show: false, message: '', type: 'info' as any });

    // UI states
    const [activeSection, setActiveSection] = useState<string>('dashboard');
    const [showAddEvent, setShowAddEvent] = useState(false);
    const [showAddCafe, setShowAddCafe] = useState(false);
    const [showAddMeal, setShowAddMeal] = useState(false);
    const [selectedCafe, setSelectedCafe] = useState<any>(null);

    // Form states
    const [eventForm, setEventForm] = useState({
        event_name: '',
        event_description: '',
        ticket_price: '',
        event_date: '',
        event_location: '',
        airtel_money_code: '',
        max_tickets: ''
    });

    const [cafeForm, setCafeForm] = useState({
        cafe_name: '',
        cafe_description: '',
        airtel_money_code: ''
    });

    const [mealForm, setMealForm] = useState({
        meal_name: '',
        meal_price: '',
        meal_description: ''
    });

    useEffect(() => {
        fetchInitialData();
    }, []);

    const fetchInitialData = async () => {
        setLoading(true);
        try {
            const [profileRes, merchantsRes, usersRes, analyticsRes, eventsRes, cafesRes, txnsRes, reportsRes] = await Promise.all([
                fetch('/api/user/profile'),
                fetch('/api/admin/pending-merchants'),
                fetch('/api/admin/users'),
                fetch('/api/admin/analytics'),
                fetch('/api/events/get-events'),
                fetch('/api/cafes/get-cafes'),
                fetch('/api/admin/transactions'),
                fetch('/api/admin/reports')
            ]);

            const [profileData, merchantsData, usersData, analyticsData, eventsData, cafesData, txnsData, reportsData] = await Promise.all([
                profileRes.json(),
                merchantsRes.json(),
                usersRes.json(),
                analyticsRes.json(),
                eventsRes.json(),
                cafesRes.json(),
                txnsRes.json(),
                reportsRes.json()
            ]);

            if (profileData.success) setProfile(profileData.profile);
            if (merchantsData.success) setPendingMerchants(merchantsData.merchants || []);
            if (usersData.success) setUsers(usersData.users || []);
            if (analyticsData.success) setAnalytics(analyticsData.analytics);
            if (eventsData.success) setEvents(eventsData.events || []);
            if (cafesData.success) setCafes(cafesData.cafes || []);
            if (txnsData.success) setTransactions(txnsData.transactions || []);
            if (reportsData.success) setReports(reportsData.reports || []);

        } catch (error) {
            console.error('Failed to fetch admin data:', error);
            setToast({ show: true, message: 'Failed to load data', type: 'error' });
        } finally {
            setLoading(false);
        }
    };

    const handleAddEvent = async (e: React.FormEvent) => {
        e.preventDefault();
        const formData = new FormData();
        Object.entries(eventForm).forEach(([key, value]) => formData.append(key, value));

        try {
            const res = await fetch('/api/events/create-event', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                setToast({ show: true, message: 'Event created!', type: 'success' });
                setShowAddEvent(false);
                setEventForm({ event_name: '', event_description: '', ticket_price: '', event_date: '', event_location: '', airtel_money_code: '', max_tickets: '' });
                fetchInitialData();
            } else { setToast({ show: true, message: data.error, type: 'error' }); }
        } catch (e) { setToast({ show: true, message: 'Network error', type: 'error' }); }
    };

    const handleAddCafe = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            const res = await fetch('/api/cafes/create-cafe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(cafeForm)
            });
            const data = await res.json();
            if (data.success) {
                setToast({ show: true, message: 'Cafe added!', type: 'success' });
                setShowAddCafe(false);
                setCafeForm({ cafe_name: '', cafe_description: '', airtel_money_code: '' });
                fetchInitialData();
            } else { setToast({ show: true, message: data.error, type: 'error' }); }
        } catch (e) { setToast({ show: true, message: 'Network error', type: 'error' }); }
    };

    const handleAddMeal = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            const res = await fetch('/api/cafes/add-cafe-meal', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...mealForm, cafe_id: selectedCafe.cafe_id })
            });
            const data = await res.json();
            if (data.success) {
                setToast({ show: true, message: 'Meal added!', type: 'success' });
                setShowAddMeal(false);
                setMealForm({ meal_name: '', meal_price: '', meal_description: '' });
            } else { setToast({ show: true, message: data.error, type: 'error' }); }
        } catch (e) { setToast({ show: true, message: 'Network error', type: 'error' }); }
    };

    const approveMerchant = async (merchantId: number) => {
        try {
            const res = await fetch('/api/admin/approve-merchant', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ merchant_id: merchantId }),
            });
            const data = await res.json();
            if (data.success) {
                setToast({ show: true, message: 'Merchant approved!', type: 'success' });
                fetchInitialData();
            } else { setToast({ show: true, message: data.error, type: 'error' }); }
        } catch (error) { setToast({ show: true, message: 'Network error', type: 'error' }); }
    };

    const handleLogout = async () => {
        await fetch('/api/auth/logout', { method: 'POST' });
        router.push('/login');
    };

    const uploadAdminPic = async (e: React.ChangeEvent<HTMLInputElement>) => {
        if (!e.target.files?.[0]) return;
        const formData = new FormData();
        formData.append('image', e.target.files[0]);

        try {
            const res = await fetch('/api/user/upload-profile-pic', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                setToast({ show: true, message: 'Profile picture updated!', type: 'success' });
                fetchInitialData();
            }
        } catch (e) { console.error(e); }
    };

    if (loading) return <div className={styles.loading}>Loading Admin Portal...</div>;

    return (
        <div className={styles.container}>
            {/* Sidebar */}
            <aside className={styles.sidebar}>
                <div className={styles.sidebarLogo}>
                    <img src="/assets/img/logo.png" alt="Kweza Pay" onError={(e) => {
                        e.currentTarget.src = 'https://ui-avatars.com/api/?name=KP&background=0D8ABC&color=fff';
                    }} />
                </div>
                <div className={styles.sidebarBrand}>Admin Portal</div>

                <nav className={styles.nav}>
                    <div className={`${styles.navLink} ${activeSection === 'dashboard' ? styles.activeNavLink : ''}`} onClick={() => setActiveSection('dashboard')}>
                        <i className="fas fa-chart-line"></i> Dashboard
                    </div>
                    <div className={`${styles.navLink} ${activeSection === 'merchants' ? styles.activeNavLink : ''}`} onClick={() => setActiveSection('merchants')}>
                        <i className="fas fa-store"></i> Merchant Apps
                    </div>
                    <div className={`${styles.navLink} ${activeSection === 'users' ? styles.activeNavLink : ''}`} onClick={() => setActiveSection('users')}>
                        <i className="fas fa-users"></i> Users
                    </div>
                    <div className={`${styles.navLink} ${activeSection === 'transactions' ? styles.activeNavLink : ''}`} onClick={() => setActiveSection('transactions')}>
                        <i className="fas fa-exchange-alt"></i> Transactions
                    </div>
                    <div className={`${styles.navLink} ${activeSection === 'events' ? styles.activeNavLink : ''}`} onClick={() => setActiveSection('events')}>
                        <i className="fas fa-ticket-alt"></i> Ticket Events
                    </div>
                    <div className={`${styles.navLink} ${activeSection === 'cafes' ? styles.activeNavLink : ''}`} onClick={() => setActiveSection('cafes')}>
                        <i className="fas fa-utensils"></i> Cafe Meals
                    </div>
                    <div className={`${styles.navLink} ${activeSection === 'reports' ? styles.activeNavLink : ''}`} onClick={() => setActiveSection('reports')}>
                        <i className="fas fa-life-ring"></i> Reports
                    </div>
                    <div className={`${styles.navLink} ${activeSection === 'settings' ? styles.activeNavLink : ''}`} onClick={() => setActiveSection('settings')}>
                        <i className="fas fa-cog"></i> Settings
                    </div>

                    <div className={styles.logoutSection}>
                        <div className={styles.logoutLink} onClick={handleLogout}>
                            <i className="fas fa-power-off"></i> Logout
                        </div>
                    </div>
                </nav>
            </aside>

            {/* Main Content */}
            <main className={styles.mainContent}>
                <header className={styles.header}>
                    <div>
                        <h1 className={styles.headerTitle}>Admin Overview</h1>
                        <p className={styles.headerSub}>Managing Kweza Pay Ecosystem</p>
                    </div>

                    <div className={styles.profileBtn} onClick={() => document.getElementById('adminPicInput')?.click()}>
                        <div className={styles.profileInfo}>
                            <div className={styles.profileName}>{profile?.full_name}</div>
                            <div className={styles.profileRole}>System Administrator</div>
                        </div>
                        {profile?.profile_pic ? (
                            <img src={profile.profile_pic} className={styles.avatar} alt="Admin" />
                        ) : (
                            <div className={styles.avatar}>{profile?.full_name?.substring(0, 1).toUpperCase()}</div>
                        )}
                        <input type="file" id="adminPicInput" style={{ display: 'none' }} accept="image/*" onChange={uploadAdminPic} />
                    </div>
                </header>

                <div className={styles.sectionContainer}>
                    {activeSection === 'dashboard' && (
                        <>
                            <div className={styles.statsGrid}>
                                <div className={styles.statCard}>
                                    <div className={styles.statIcon}><i className="fas fa-users"></i></div>
                                    <div className={styles.statVal}>{users.length}</div>
                                    <div className={styles.statLabel}>Total Users</div>
                                </div>
                                <div className={styles.statCard}>
                                    <div className={styles.statIcon} style={{ background: '#f0fdf4', color: '#10b981' }}><i className="fas fa-graduation-cap"></i></div>
                                    <div className={styles.statVal}>{users.filter(u => u.user_type === 'Student').length}</div>
                                    <div className={styles.statLabel}>Students</div>
                                </div>
                                <div className={styles.statCard}>
                                    <div className={styles.statIcon} style={{ background: '#fff7ed', color: '#f59e0b' }}><i className="fas fa-store"></i></div>
                                    <div className={styles.statVal}>{users.filter(u => u.user_type === 'Merchant').length}</div>
                                    <div className={styles.statLabel}>Merchants</div>
                                </div>
                                <div className={styles.statCard}>
                                    <div className={styles.statIcon} style={{ background: '#fef2f2', color: '#ef4444' }}><i className="fas fa-exchange-alt"></i></div>
                                    <div className={styles.statVal}>{transactions.length}</div>
                                    <div className={styles.statLabel}>Total Txns</div>
                                </div>
                            </div>

                            <div className={styles.sectionContainer} style={{ background: 'white', padding: '30px' }}>
                                <h3 style={{ fontSize: '18px', fontWeight: 800, color: 'var(--pp-dark-blue)', marginBottom: '30px' }}>Growth Analytics</h3>
                                {analytics && <AnalyticsDashboard data={analytics} />}
                            </div>

                            <div className={styles.dataGrid}>
                                <div className={styles.dataCard}>
                                    <h3 style={{ fontSize: '18px', fontWeight: 800, color: 'var(--pp-dark-blue)', marginBottom: '25px' }}>Recent Transactions</h3>
                                    <table className={styles.dataTable}>
                                        <thead>
                                            <tr>
                                                <th>Ref</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {transactions.slice(0, 8).map((t: any) => (
                                                <tr key={t.txn_id}>
                                                    <td style={{ fontFamily: 'monospace', fontWeight: 600 }}>{t.reference_code?.substring(t.reference_code.length - 8)}</td>
                                                    <td>{t.txn_type}</td>
                                                    <td style={{ fontWeight: 700 }}>MWK {t.amount.toLocaleString()}</td>
                                                    <td><span style={{ color: '#10b981', fontWeight: 700, fontSize: '12px' }}><i className="fas fa-check-circle"></i> Success</span></td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div className={styles.dataCard}>
                                    <h3 style={{ fontSize: '18px', fontWeight: 800, color: 'var(--pp-dark-blue)', marginBottom: '25px' }}>Top Wallets</h3>
                                    {users.slice(0, 5).sort((a, b) => (b.wallet_balance || 0) - (a.wallet_balance || 0)).map((u) => (
                                        <div key={u.user_id} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '12px 0', borderBottom: '1px solid #f1f5f9' }}>
                                            <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                                                <div className={styles.avatar} style={{ width: '35px', height: '35px', fontSize: '12px' }}>{u.full_name?.substring(0, 1)}</div>
                                                <div>
                                                    <div style={{ fontWeight: 700, fontSize: '14px' }}>{u.full_name}</div>
                                                    <span className={`${styles.badge} ${u.user_type === 'Student' ? styles.badgeStudent : styles.badgeMerchant}`}>{u.user_type}</span>
                                                </div>
                                            </div>
                                            <div style={{ fontWeight: 800, fontSize: '14px', color: 'var(--pp-blue)' }}>MWK {u.wallet_balance?.toLocaleString()}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </>
                    )}

                    {activeSection === 'merchants' && (
                        <div className={styles.dataCard} style={{ width: '100%' }}>
                            <h3 style={{ fontSize: '18px', fontWeight: 800, color: 'var(--pp-dark-blue)', marginBottom: '25px' }}>Merchant Applications</h3>
                            <table className={styles.dataTable}>
                                <thead>
                                    <tr>
                                        <th>Business Name</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {pendingMerchants.map((m) => (
                                        <tr key={m.merchant_id}>
                                            <td className={styles.businessName}>{m.business_name}</td>
                                            <td>{new Date(m.created_at).toLocaleDateString()}</td>
                                            <td><span className={styles.badgeMerchant}>Pending</span></td>
                                            <td><button className={styles.approveBtn} onClick={() => approveMerchant(m.merchant_id)}>Approve</button></td>
                                        </tr>
                                    ))}
                                    {pendingMerchants.length === 0 && (
                                        <tr>
                                            <td colSpan={4} className={styles.emptyState}>No pending merchant applications</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {activeSection === 'users' && (
                        <div className={styles.dataCard} style={{ width: '100%' }}>
                            <h3 style={{ fontSize: '18px', fontWeight: 800, color: 'var(--pp-dark-blue)', marginBottom: '25px' }}>Platform Users</h3>
                            <table className={styles.dataTable}>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Type</th>
                                        <th>Wallet</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {users.map((u) => (
                                        <tr key={u.user_id}>
                                            <td className={styles.userName}>{u.full_name}</td>
                                            <td>{u.phone_number}</td>
                                            <td><span className={`${styles.badge} ${u.user_type === 'Student' ? styles.badgeStudent : u.user_type === 'Merchant' ? styles.badgeMerchant : styles.badgeAdmin}`}>{u.user_type}</span></td>
                                            <td style={{ fontWeight: 700 }}>MWK {u.wallet_balance?.toLocaleString()}</td>
                                            <td>{new Date(u.created_at).toLocaleDateString()}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {activeSection === 'transactions' && (
                        <div className={styles.dataCard} style={{ width: '100%' }}>
                            <h3 style={{ fontSize: '18px', fontWeight: 800, color: 'var(--pp-dark-blue)', marginBottom: '25px' }}>All Transactions</h3>
                            <table className={styles.dataTable}>
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Type</th>
                                        <th>Sender</th>
                                        <th>Recipient</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {transactions.map((t: any) => (
                                        <tr key={t.txn_id}>
                                            <td style={{ fontFamily: 'monospace' }}>{t.reference_code}</td>
                                            <td>{t.txn_type}</td>
                                            <td>{t.sender?.full_name || 'System'}</td>
                                            <td>{t.recipient?.full_name || 'System'}</td>
                                            <td style={{ fontWeight: 700 }}>MWK {t.amount.toLocaleString()}</td>
                                            <td>{new Date(t.created_at).toLocaleString()}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {activeSection === 'reports' && (
                        <div className={styles.dataCard} style={{ width: '100%' }}>
                            <h3 style={{ fontSize: '18px', fontWeight: 800, color: 'var(--pp-dark-blue)', marginBottom: '25px' }}>Help Desk Reports</h3>
                            <table className={styles.dataTable}>
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Subject</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {reports.map((r: any) => (
                                        <tr key={r.report_id}>
                                            <td>
                                                <div style={{ fontWeight: 700 }}>{r.user?.full_name}</div>
                                                <div style={{ fontSize: '12px', color: '#64748b' }}>{r.user?.phone_number}</div>
                                            </td>
                                            <td style={{ fontWeight: 700 }}>{r.subject}</td>
                                            <td style={{ maxWidth: '300px', fontSize: '13px' }}>{r.message}</td>
                                            <td><span className={styles.badgeMerchant}>{r.status}</span></td>
                                            <td>{new Date(r.created_at).toLocaleDateString()}</td>
                                        </tr>
                                    ))}
                                    {reports.length === 0 && (
                                        <tr>
                                            <td colSpan={5} className={styles.emptyState}>No help reports found</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {activeSection === 'events' && (
                        <div className={styles.dataCard} style={{ width: '100%' }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '25px' }}>
                                <h3 style={{ fontSize: '18px', fontWeight: 800, color: 'var(--pp-dark-blue)', margin: 0 }}>Ticket Events</h3>
                                <button className={styles.submitBtn} onClick={() => setShowAddEvent(true)}>+ Create Event</button>
                            </div>
                            <table className={styles.dataTable}>
                                <thead>
                                    <tr>
                                        <th>Event Name</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Tickets Sold</th>
                                        <th>Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {events.map((e) => (
                                        <tr key={e.event_id}>
                                            <td style={{ fontWeight: 700 }}>{e.event_name}</td>
                                            <td>MWK {e.ticket_price}</td>
                                            <td><span className={e.is_active ? styles.badgeStudent : styles.badgeAdmin}>{e.is_active ? 'Active' : 'Closed'}</span></td>
                                            <td>{e.tickets_sold || 0}</td>
                                            <td>{e.event_location}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {activeSection === 'cafes' && (
                        <div className={styles.dataCard} style={{ width: '100%' }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '25px' }}>
                                <h3 style={{ fontSize: '18px', fontWeight: 800, color: 'var(--pp-dark-blue)', margin: 0 }}>Campus Cafes</h3>
                                <button className={styles.submitBtn} onClick={() => setShowAddCafe(true)}>+ Add Cafe</button>
                            </div>
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))', gap: '20px' }}>
                                {cafes.map((c) => (
                                    <div key={c.cafe_id} className={styles.statCard} style={{ cursor: 'default' }}>
                                        <div style={{ fontSize: '18px', fontWeight: 800, color: 'var(--pp-dark-blue)' }}>{c.cafe_name}</div>
                                        <div style={{ fontSize: '13px', color: '#64748b', margin: '8px 0 15px' }}>{c.cafe_description}</div>
                                        <button className={styles.approveBtn} style={{ width: '100%' }} onClick={() => { setSelectedCafe(c); setShowAddMeal(true); }}>Add Meal Item</button>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </main>

            {/* Modals */}
            <Modal isOpen={showAddEvent} onClose={() => setShowAddEvent(false)} title="Create New Event">
                <form onSubmit={handleAddEvent} className={styles.form}>
                    <div className={styles.formGroup}><label>Event Name</label><input type="text" value={eventForm.event_name} onChange={(e) => setEventForm({ ...eventForm, event_name: e.target.value })} required className={styles.input} /></div>
                    <div className={styles.formGroup}><label>Price (MWK)</label><input type="number" value={eventForm.ticket_price} onChange={(e) => setEventForm({ ...eventForm, ticket_price: e.target.value })} required className={styles.input} /></div>
                    <div className={styles.formGroup}><label>Airtel Code</label><input type="text" value={eventForm.airtel_money_code} onChange={(e) => setEventForm({ ...eventForm, airtel_money_code: e.target.value })} className={styles.input} /></div>
                    <button type="submit" className={styles.submitBtn}>Create Event</button>
                </form>
            </Modal>

            <Modal isOpen={showAddCafe} onClose={() => setShowAddCafe(false)} title="Add Campus Cafe">
                <form onSubmit={handleAddCafe} className={styles.form}>
                    <div className={styles.formGroup}><label>Cafe Name</label><input type="text" value={cafeForm.cafe_name} onChange={(e) => setCafeForm({ ...cafeForm, cafe_name: e.target.value })} required className={styles.input} /></div>
                    <div className={styles.formGroup}><label>Code</label><input type="text" value={cafeForm.airtel_money_code} onChange={(e) => setCafeForm({ ...cafeForm, airtel_money_code: e.target.value })} required className={styles.input} /></div>
                    <button type="submit" className={styles.submitBtn}>Add Cafe</button>
                </form>
            </Modal>

            <Modal isOpen={showAddMeal} onClose={() => setShowAddMeal(false)} title={`Add Meal to ${selectedCafe?.cafe_name}`}>
                <form onSubmit={handleAddMeal} className={styles.form}>
                    <div className={styles.formGroup}><label>Meal Name</label><input type="text" value={mealForm.meal_name} onChange={(e) => setMealForm({ ...mealForm, meal_name: e.target.value })} required className={styles.input} /></div>
                    <div className={styles.formGroup}><label>Price (MWK)</label><input type="number" value={mealForm.meal_price} onChange={(e) => setMealForm({ ...mealForm, meal_price: e.target.value })} required className={styles.input} /></div>
                    <button type="submit" className={styles.submitBtn}>Add Meal</button>
                </form>
            </Modal>

            <Toast message={toast.message} type={toast.type} isVisible={toast.show} onClose={() => setToast({ ...toast, show: false })} />
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
        </div>
    );
}
