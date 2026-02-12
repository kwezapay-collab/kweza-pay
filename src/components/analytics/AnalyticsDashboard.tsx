import React from 'react';
import styles from './analytics.module.css';

interface AnalyticsData {
    users: {
        total: number;
        by_type: Record<string, number>;
    };
    transactions: {
        total_count: number;
        total_volume: number;
        this_month_volume: number;
        by_type: Record<string, number>;
    };
    events: {
        total_events: number;
        tickets_sold: number;
    };
    cafes: {
        total_cafes: number;
    };
}

export default function AnalyticsDashboard({ data }: { data: AnalyticsData }) {
    return (
        <div className={styles.analyticsContainer}>
            <div className={styles.analyticsGrid}>
                <div className={styles.statCard}>
                    <div className={styles.statValue}>MWK {data.transactions.total_volume.toLocaleString()}</div>
                    <div className={styles.statLabel}>Total Volume</div>
                </div>
                <div className={styles.statCard}>
                    <div className={styles.statValue}>{data.users.total}</div>
                    <div className={styles.statLabel}>Total Users</div>
                </div>
                <div className={styles.statCard}>
                    <div className={styles.statValue}>{data.events.total_events}</div>
                    <div className={styles.statLabel}>Events</div>
                </div>
                <div className={styles.statCard}>
                    <div className={styles.statValue}>{data.events.tickets_sold}</div>
                    <div className={styles.statLabel}>Tickets Sold</div>
                </div>
            </div>

            {/* Volume by Type */}
            <div className={styles.statCard}>
                <h4 className={styles.chartTitle}>Volume by Transaction Type</h4>
                <div className={styles.chartContainer}>
                    {Object.entries(data.transactions.by_type).map(([type, amount]) => (
                        <div key={type} className={styles.barRow}>
                            <div className={styles.barLabel}>{type.replace('_', ' ')}</div>
                            <div className={styles.barWrapper}>
                                <div
                                    className={styles.bar}
                                    style={{ width: `${(amount / data.transactions.total_volume) * 100}%` }}
                                ></div>
                            </div>
                            <div className={styles.barValue}>MWK {amount.toFixed(0)}</div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
