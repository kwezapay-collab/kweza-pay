import React from 'react';
import styles from './analytics.module.css';

interface BudgetData {
    total_spent: number;
    this_month_spent: number;
    by_category: Record<string, number>;
}

export default function BudgetChart({ data }: { data: BudgetData }) {
    const maxVal = Math.max(...Object.values(data.by_category), 1);

    return (
        <div className={styles.budgetCard}>
            <h4 className={styles.chartTitle}>Spending by Category</h4>
            <div className={styles.chartContainer}>
                {Object.entries(data.by_category).map(([category, amount]) => (
                    <div key={category} className={styles.barRow}>
                        <div className={styles.barLabel}>{category.replace('_', ' ')}</div>
                        <div className={styles.barWrapper}>
                            <div
                                className={styles.bar}
                                style={{ width: `${(amount / maxVal) * 100}%` }}
                            ></div>
                        </div>
                        <div className={styles.barValue}>MWK {amount.toFixed(0)}</div>
                    </div>
                ))}
            </div>
            <div className={styles.budgetSummary}>
                <div className={styles.summaryItem}>
                    <span>Monthly Total</span>
                    <strong>MWK {data.this_month_spent.toFixed(2)}</strong>
                </div>
            </div>
        </div>
    );
}
