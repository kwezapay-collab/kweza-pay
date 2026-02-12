import React from 'react';
import styles from './events.module.css';

interface Event {
    event_id: number;
    event_name: string;
    event_description: string;
    event_picture: string | null;
    ticket_price: number;
    event_date: string;
    event_location: string;
    airtel_qr_image: string | null;
}

interface EventCardProps {
    event: Event;
    onPurchase: (event: Event) => void;
}

export default function EventCard({ event, onPurchase }: EventCardProps) {
    return (
        <div className={styles.eventCard}>
            <div className={styles.eventImage}>
                <img
                    src={event.event_picture || 'https://via.placeholder.com/300x150?text=Event'}
                    alt={event.event_name}
                    onError={(e) => {
                        e.currentTarget.src = 'https://via.placeholder.com/300x150?text=Event';
                    }}
                />
            </div>
            <div className={styles.eventInfo}>
                <h4 className={styles.eventName}>{event.event_name}</h4>
                <p className={styles.eventDate}>
                    <i className="far fa-calendar-alt"></i> {new Date(event.event_date).toLocaleDateString()}
                </p>
                <p className={styles.eventLocation}>
                    <i className="fas fa-map-marker-alt"></i> {event.event_location}
                </p>
                <div className={styles.eventFooter}>
                    <span className={styles.eventPrice}>MWK {Number(event.ticket_price).toFixed(2)}</span>
                    <button
                        className={styles.buyBtn}
                        onClick={() => onPurchase(event)}
                    >
                        Buy Ticket
                    </button>
                </div>
            </div>
        </div>
    );
}
