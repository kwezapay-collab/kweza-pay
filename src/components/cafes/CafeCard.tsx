import React from 'react';
import styles from './cafes.module.css';

interface Cafe {
    cafe_id: number;
    cafe_name: string;
    cafe_description: string;
    cafe_logo: string | null;
}

interface CafeCardProps {
    cafe: Cafe;
    onPay: (cafe: Cafe) => void;
}

export default function CafeCard({ cafe, onPay }: CafeCardProps) {
    return (
        <div className={styles.cafeCard}>
            <div className={styles.cafeLogo}>
                <img
                    src={cafe.cafe_logo || 'https://via.placeholder.com/80?text=Cafe'}
                    alt={cafe.cafe_name}
                    onError={(e) => {
                        e.currentTarget.src = 'https://via.placeholder.com/80?text=Cafe';
                    }}
                />
            </div>
            <div className={styles.cafeInfo}>
                <h4 className={styles.cafeName}>{cafe.cafe_name}</h4>
                <p className={styles.cafeDesc}>{cafe.cafe_description}</p>
                <button
                    className={styles.payBtn}
                    onClick={() => onPay(cafe)}
                >
                    Pay at Cafe
                </button>
            </div>
        </div>
    );
}
