import React from 'react';
import styles from './events.module.css';

interface Ticket {
    ticket_id: number;
    ticket_code: string;
    purchase_amount: number;
    qr_code_data: string;
    is_used: boolean;
    created_at: string;
    events: {
        event_name: string;
        event_date: string;
        event_location: string;
    };
}

export default function TicketCard({ ticket }: { ticket: Ticket }) {
    // Generate QR code URL using the qr_code_data
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(ticket.qr_code_data)}`;

    return (
        <div className={styles.ticketCard}>
            <div className={styles.ticketHeader}>
                <div>
                    <h4 className={styles.eventName}>{ticket.events.event_name}</h4>
                    <p className={styles.eventDate}>{new Date(ticket.events.event_date).toLocaleDateString()}</p>
                </div>
                <span className={`${styles.ticketStatus} ${ticket.is_used ? styles.statusUsed : styles.statusUnused}`}>
                    {ticket.is_used ? 'USED' : 'UNUSED'}
                </span>
            </div>

            <div className={styles.ticketQr}>
                <img src={qrUrl} alt="Ticket QR Code" />
            </div>

            <div className={styles.ticketCode}>
                {ticket.ticket_code}
            </div>
        </div>
    );
}
