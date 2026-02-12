'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import Modal from '@/components/ui/Modal';
import Toast from '@/components/ui/Toast';
import EventCard from '@/components/events/EventCard';
import TicketCard from '@/components/events/TicketCard';
import CafeCard from '@/components/cafes/CafeCard';
import BudgetChart from '@/components/analytics/BudgetChart';
import QRScanner from '@/components/ui/QRScanner';
import styles from './student.module.css';

interface Transaction {
    txn_id: number;
    txn_type: string;
    sender_id: number;
    receiver_id: number;
    amount: number;
    reference_code: string;
    description: string;
    created_at: string;
}

interface UserProfile {
    user_id: number;
    full_name: string;
    phone_number: string;
    wallet_balance: number;
    profile_pic: string | null;
}

export default function StudentDashboard() {
    const router = useRouter();
    const [profile, setProfile] = useState<UserProfile | null>(null);
    const [transactions, setTransactions] = useState<Transaction[]>([]);
    const [loading, setLoading] = useState(true);

    // Feature data states
    const [events, setEvents] = useState([]);
    const [tickets, setTickets] = useState([]);
    const [cafes, setCafes] = useState([]);
    const [budgetData, setBudgetData] = useState(null);

    // Modal states
    const [showSendMoney, setShowSendMoney] = useState(false);
    const [showMerchantApply, setShowMerchantApply] = useState(false);
    const [showEvents, setShowEvents] = useState(false);
    const [showTickets, setShowTickets] = useState(false);
    const [showCafes, setShowCafes] = useState(false);
    const [showHelpDesk, setShowHelpDesk] = useState(false);
    const [showBudget, setShowBudget] = useState(false);

    // Purchase/Payment states
    const [selectedEvent, setSelectedEvent] = useState<any>(null);
    const [selectedCafe, setSelectedCafe] = useState<any>(null);
    const [selectedMerchant, setSelectedMerchant] = useState<any>(null);
    const [cafeAmount, setCafeAmount] = useState('');
    const [merchantAmount, setMerchantAmount] = useState('');
    const [showScanner, setShowScanner] = useState(false);
    const [userPin, setUserPin] = useState('');

    // Toast state
    const [toast, setToast] = useState({ show: false, message: '', type: 'info' as 'success' | 'error' | 'info' });

    // Form states
    const [sendMoneyData, setSendMoneyData] = useState({ phone: '', amount: '' });
    const [businessName, setBusinessName] = useState('');
    const [helpData, setHelpData] = useState({ subject: '', message: '' });

    useEffect(() => {
        // Handle payment status from URL
        const params = new URLSearchParams(window.location.search);
        const status = params.get('status');
        const message = params.get('message');

        if (status === 'success') {
            setToast({ show: true, message: 'Payment successful!', type: 'success' });
            // Clean up URL
            window.history.replaceState({}, '', window.location.pathname);
        } else if (status === 'failed' || status === 'error') {
            setToast({ show: true, message: message || 'Payment failed', type: 'error' });
            window.history.replaceState({}, '', window.location.pathname);
        }

        fetchProfile();
        fetchTransactions();
        fetchEvents();
        fetchTickets();
        fetchCafes();
        fetchBudget();
    }, []);

    const fetchProfile = async () => {
        try {
            const res = await fetch('/api/user/profile');
            const data = await res.json();
            if (data.success) {
                setProfile(data.profile);
            }
        } catch (error) {
            console.error('Failed to fetch profile:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchTransactions = async () => {
        try {
            const res = await fetch('/api/transactions/list?limit=5');
            const data = await res.json();
            if (data.success) {
                setTransactions(data.transactions);
            }
        } catch (error) {
            console.error('Failed to fetch transactions:', error);
        }
    };

    const fetchEvents = async () => {
        try {
            const res = await fetch('/api/events/get-events');
            const data = await res.json();
            if (data.success) setEvents(data.events);
        } catch (e) { console.error(e); }
    };

    const fetchTickets = async () => {
        try {
            const res = await fetch('/api/events/get-my-tickets');
            const data = await res.json();
            if (data.success) setTickets(data.tickets);
        } catch (e) { console.error(e); }
    };

    const fetchCafes = async () => {
        try {
            const res = await fetch('/api/cafes/get-cafes');
            const data = await res.json();
            if (data.success) setCafes(data.cafes);
        } catch (e) { console.error(e); }
    };

    const fetchBudget = async () => {
        try {
            const res = await fetch('/api/user/budget');
            const data = await res.json();
            if (data.success) setBudgetData(data.budget);
        } catch (e) { console.error(e); }
    };

    const handleSendMoney = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            const res = await fetch('/api/payments/send-money', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    recipient_phone: sendMoneyData.phone,
                    amount: parseFloat(sendMoneyData.amount),
                }),
            });
            const data = await res.json();
            if (data.success) {
                setToast({ show: true, message: data.message, type: 'success' });
                setShowSendMoney(false);
                setSendMoneyData({ phone: '', amount: '' });
                fetchProfile(); fetchTransactions(); fetchBudget();
            } else { setToast({ show: true, message: data.error, type: 'error' }); }
        } catch (error) { setToast({ show: true, message: 'Network error', type: 'error' }); }
    };

    const handlePurchaseTicket = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            const res = await fetch('/api/payments/initialize', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    amount: selectedEvent.ticket_price,
                    description: `Ticket: ${selectedEvent.event_name}`,
                    receiver_id: 1, // System admin or event owner ID
                    txn_type: 'EVENT_TICKET',
                    metadata: { event_id: selectedEvent.event_id }
                }),
            });
            const data = await res.json();
            if (data.success && data.checkout_url) {
                window.location.href = data.checkout_url;
            } else {
                setToast({ show: true, message: data.error || 'Failed to initialize payment', type: 'error' });
            }
        } catch (e) { setToast({ show: true, message: 'Network error', type: 'error' }); }
    };

    const handleCafePayment = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            const res = await fetch('/api/payments/initialize', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    amount: parseFloat(cafeAmount),
                    description: `Cafe Payment to ${selectedCafe.cafe_name}`,
                    receiver_id: selectedCafe.user_id || 1, // Cafe owner ID
                    txn_type: 'CAFE_PAY',
                    metadata: { cafe_id: selectedCafe.cafe_id }
                }),
            });
            const data = await res.json();
            if (data.success && data.checkout_url) {
                window.location.href = data.checkout_url;
            } else {
                setToast({ show: true, message: data.error || 'Failed to initialize payment', type: 'error' });
            }
        } catch (e) { setToast({ show: true, message: 'Network error', type: 'error' }); }
    };

    const handleHelpSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            const res = await fetch('/api/helpdesk/submit-report', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(helpData),
            });
            const data = await res.json();
            if (data.success) {
                setToast({ show: true, message: 'Report submitted!', type: 'success' });
                setShowHelpDesk(false); setHelpData({ subject: '', message: '' });
            } else { setToast({ show: true, message: data.error, type: 'error' }); }
        } catch (e) { setToast({ show: true, message: 'Network error', type: 'error' }); }
    };

    const handleScanSuccess = async (decodedText: string) => {
        setShowScanner(false);
        setToast({ show: true, message: 'QR Code Scanned!', type: 'success' });

        try {
            // Check if it's a merchant token
            const res = await fetch(`/api/merchant/details-by-token?token=${decodedText}`);
            const data = await res.json();
            if (data.success) {
                setSelectedMerchant(data.merchant);
            } else {
                setToast({ show: true, message: 'Invalid Merchant QR Code', type: 'error' });
            }
        } catch (e) {
            setToast({ show: true, message: 'Failed to identify merchant', type: 'error' });
        }
    };

    const handleMerchantPayment = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            const res = await fetch('/api/payments/initialize', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    amount: parseFloat(merchantAmount),
                    description: `Payment to ${selectedMerchant.business_name}`,
                    receiver_id: selectedMerchant.user_id,
                    txn_type: 'QR_PAY',
                    metadata: { merchant_id: selectedMerchant.merchant_id }
                }),
            });
            const data = await res.json();
            if (data.success && data.checkout_url) {
                window.location.href = data.checkout_url;
            } else {
                setToast({ show: true, message: data.error || 'Failed to initialize payment', type: 'error' });
            }
        } catch (e) { setToast({ show: true, message: 'Network error', type: 'error' }); }
    };

    const handleLogout = async () => {
        await fetch('/api/auth/logout', { method: 'POST' });
        router.push('/login');
    };

    if (loading) {
        return <div className={styles.loading}>Loading...</div>;
    }

    return (
        <div className="kweza-pattern-bg">
            <div className={styles.container}>
                {/* Header */}
                <header className={styles.header}>
                    <div className={styles.logo}>
                        <img src="/assets/img/logo.png" alt="Kweza Pay" onError={(e) => {
                            e.currentTarget.src = 'https://ui-avatars.com/api/?name=Kweza+Pay&background=0D8ABC&color=fff';
                        }} />
                    </div>
                    <div className={styles.headerActions}>
                        <span className={styles.userName}>Hello, {profile?.full_name}</span>
                        <button onClick={handleLogout} className={styles.logoutBtn}>
                            Logout
                        </button>
                    </div>
                </header>

                <main className={styles.mainLayout}>
                    {/* Left Column: Balance & Activity */}
                    <div className={styles.leftCol}>
                        <div className={styles.balanceCard}>
                            <span className={styles.balanceLabel}>Kweza Pay Overview</span>
                            <div className={styles.balanceAmount}>
                                MWK {profile?.wallet_balance.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00'}
                            </div>
                            <div className={styles.balanceAvailable}>Total Transactions Value</div>
                            <button className={styles.submitBtn} style={{ width: 'auto', padding: '10px 25px' }} onClick={() => setShowTickets(true)}>
                                My Tickets
                            </button>
                        </div>

                        <div className={styles.card}>
                            <h3 className={styles.cardTitle}>Recent Activity</h3>
                            <div className={styles.activityList}>
                                {transactions.length > 0 ? (
                                    transactions.map((txn) => (
                                        <div key={txn.txn_id} className={styles.activityItem}>
                                            <div className={styles.activityInfo}>
                                                <div className={styles.activityDesc}>{txn.description || txn.txn_type}</div>
                                                <div className={styles.activityDate}>
                                                    {new Date(txn.created_at).toLocaleDateString()}
                                                </div>
                                            </div>
                                            <div
                                                className={`${styles.activityAmount} ${txn.sender_id === profile?.user_id ? styles.debit : styles.credit
                                                    }`}
                                            >
                                                {txn.sender_id === profile?.user_id ? '-' : '+'}MWK {txn.amount.toFixed(2)}
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <p className={styles.emptyState}>No recent transactions</p>
                                )}
                            </div>
                            <a href="#" className={styles.showAll} style={{ display: 'block', marginTop: '20px', color: 'var(--pp-light-blue)', fontWeight: 700 }}>Show all</a>
                        </div>
                    </div>

                    {/* Right Column: Actions & Apps */}
                    <div className={styles.rightCol}>
                        <div className={styles.actionsGrid}>
                            <div className={styles.actionBtn} onClick={() => setShowScanner(true)}>
                                <div className={styles.actionIcon}><i className="fas fa-qrcode"></i></div>
                                <span className={styles.actionLabel}>Scan QR</span>
                            </div>
                            <div className={styles.actionBtn} onClick={() => setShowEvents(true)}>
                                <div className={styles.actionIcon}><i className="fas fa-ticket-alt"></i></div>
                                <span className={styles.actionLabel}>Buy Tickets</span>
                            </div>
                            <div className={styles.actionBtn} onClick={() => setShowCafes(true)}>
                                <div className={styles.actionIcon}><i className="fas fa-utensils"></i></div>
                                <span className={styles.actionLabel}>Campus Cafe</span>
                            </div>
                            <div className={styles.actionBtn} onClick={() => setShowBudget(true)}>
                                <div className={styles.actionIcon}><i className="fas fa-chart-pie"></i></div>
                                <span className={styles.actionLabel}>My Budget</span>
                            </div>
                            <div className={styles.actionBtn} onClick={() => setShowHelpDesk(true)}>
                                <div className={styles.actionIcon}><i className="fas fa-headset"></i></div>
                                <span className={styles.actionLabel}>Help Desk</span>
                            </div>
                            <div className={styles.actionBtn} onClick={() => setShowSendMoney(true)}>
                                <div className={styles.actionIcon}><i className="fas fa-paper-plane"></i></div>
                                <span className={styles.actionLabel}>Send money</span>
                            </div>
                        </div>

                        {budgetData && (
                            <div className={styles.card}>
                                <h3 className={styles.cardTitle}>Spending Insights</h3>
                                <BudgetChart data={budgetData} />
                            </div>
                        )}

                        <div className={styles.card}>
                            <h3 className={styles.cardTitle}>My Purchased Tickets</h3>
                            <div style={{ display: 'grid', gap: '10px' }}>
                                {tickets.slice(0, 3).map((ticket: any) => (
                                    <TicketCard key={ticket.ticket_id} ticket={ticket} />
                                ))}
                                {tickets.length > 3 && (
                                    <button className={styles.logoutBtn} style={{ color: 'var(--pp-blue)', textAlign: 'left' }} onClick={() => setShowTickets(true)}>
                                        View all tickets
                                    </button>
                                )}
                                {tickets.length === 0 && <p className={styles.emptyState}>No tickets purchased</p>}
                            </div>
                        </div>
                    </div>
                </main>

                {/* Modals */}
                <Modal isOpen={showSendMoney} onClose={() => setShowSendMoney(false)} title="Send Money">
                    <form onSubmit={handleSendMoney} className={styles.form}>
                        <div className={styles.formGroup}>
                            <label>Recipient Phone Number</label>
                            <input type="tel" value={sendMoneyData.phone} onChange={(e) => setSendMoneyData({ ...sendMoneyData, phone: e.target.value })} placeholder="265999123456" required className={styles.input} />
                        </div>
                        <div className={styles.formGroup}>
                            <label>Amount (MWK)</label>
                            <input type="number" value={sendMoneyData.amount} onChange={(e) => setSendMoneyData({ ...sendMoneyData, amount: e.target.value })} min="1" step="0.01" required className={styles.input} />
                        </div>
                        <button type="submit" className={styles.submitBtn}>Send Money</button>
                    </form>
                </Modal>

                <Modal isOpen={showEvents} onClose={() => setShowEvents(false)} title="Upcoming Events">
                    <div style={{ display: 'grid', gap: '15px' }}>
                        {events.map((event: any) => (
                            <EventCard key={event.event_id} event={event} onPurchase={setSelectedEvent} />
                        ))}
                        {events.length === 0 && <p>No upcoming events found.</p>}
                    </div>
                </Modal>

                <Modal isOpen={!!selectedEvent} onClose={() => setSelectedEvent(null)} title={`Purchase: ${selectedEvent?.event_name}`}>
                    <form onSubmit={handlePurchaseTicket} className={styles.form}>
                        <p>Price: MWK {selectedEvent?.ticket_price}</p>
                        <div className={styles.formGroup}>
                            <label>Enter PIN to Confirm</label>
                            <input type="password" value={userPin} onChange={(e) => setUserPin(e.target.value)} maxLength={4} required className={styles.input} />
                        </div>
                        <button type="submit" className={styles.submitBtn}>Confirm Purchase</button>
                    </form>
                </Modal>

                <Modal isOpen={showTickets} onClose={() => setShowTickets(false)} title="My Tickets">
                    <div style={{ display: 'grid', gap: '15px' }}>
                        {tickets.map((ticket: any) => (
                            <TicketCard key={ticket.ticket_id} ticket={ticket} />
                        ))}
                        {tickets.length === 0 && <p>You haven't purchased any tickets yet.</p>}
                    </div>
                </Modal>

                <Modal isOpen={showCafes} onClose={() => setShowCafes(false)} title="Campus Cafes">
                    <div style={{ display: 'grid', gap: '10px' }}>
                        {cafes.map((cafe: any) => (
                            <CafeCard key={cafe.cafe_id} cafe={cafe} onPay={setSelectedCafe} />
                        ))}
                    </div>
                </Modal>

                <Modal isOpen={!!selectedCafe} onClose={() => setSelectedCafe(null)} title={`Pay to ${selectedCafe?.cafe_name}`}>
                    <form onSubmit={handleCafePayment} className={styles.form}>
                        <div className={styles.formGroup}>
                            <label>Amount (MWK)</label>
                            <input type="number" value={cafeAmount} onChange={(e) => setCafeAmount(e.target.value)} min="1" required className={styles.input} />
                        </div>
                        <button type="submit" className={styles.submitBtn}>Pay Now</button>
                    </form>
                </Modal>

                <Modal isOpen={showHelpDesk} onClose={() => setShowHelpDesk(false)} title="Help Desk">
                    <form onSubmit={handleHelpSubmit} className={styles.form}>
                        <div className={styles.formGroup}>
                            <label>Subject</label>
                            <input type="text" value={helpData.subject} onChange={(e) => setHelpData({ ...helpData, subject: e.target.value })} required className={styles.input} />
                        </div>
                        <div className={styles.formGroup}>
                            <label>Message</label>
                            <textarea value={helpData.message} onChange={(e) => setHelpData({ ...helpData, message: e.target.value })} required className={styles.input} style={{ minHeight: '100px' }} />
                        </div>
                        <button type="submit" className={styles.submitBtn}>Submit Report</button>
                    </form>
                </Modal>

                <Modal isOpen={showScanner} onClose={() => setShowScanner(false)} title="Scan Merchant QR">
                    <QRScanner onScanSuccess={handleScanSuccess} isOpen={showScanner} />
                    <p style={{ textAlign: 'center', marginTop: '10px', color: '#666' }}>
                        Point your camera at a merchant's Kweza Pay QR code
                    </p>
                </Modal>

                <Modal isOpen={!!selectedMerchant} onClose={() => setSelectedMerchant(null)} title={`Pay to ${selectedMerchant?.business_name}`}>
                    <form onSubmit={handleMerchantPayment} className={styles.form}>
                        <div className={styles.formGroup}>
                            <label>Amount (MWK)</label>
                            <input type="number" value={merchantAmount} onChange={(e) => setMerchantAmount(e.target.value)} min="1" required className={styles.input} />
                        </div>
                        <button type="submit" className={styles.submitBtn}>Initialize Payment</button>
                    </form>
                </Modal>

                <Modal isOpen={showBudget} onClose={() => setShowBudget(false)} title="Budget Tracking">
                    {budgetData && <BudgetChart data={budgetData} />}
                </Modal>

                <Toast
                    message={toast.message}
                    type={toast.type}
                    isVisible={toast.show}
                    onClose={() => setToast({ ...toast, show: false })}
                />
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
            </div>
        </div>
    );
}

