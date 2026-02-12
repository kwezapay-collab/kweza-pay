'use client';

import { useEffect, useRef } from 'react';
import { Html5QrcodeScanner } from 'html5-qrcode';

interface QRScannerProps {
    onScanSuccess: (decodedText: string) => void;
    onScanError?: (errorMessage: string) => void;
    isOpen: boolean;
}

export default function QRScanner({ onScanSuccess, onScanError, isOpen }: QRScannerProps) {
    const scannerRef = useRef<Html5QrcodeScanner | null>(null);

    useEffect(() => {
        if (isOpen) {
            // Delay slightly to ensure modal is mounted and div is available
            const timer = setTimeout(() => {
                if (!scannerRef.current) {
                    scannerRef.current = new Html5QrcodeScanner(
                        "qr-reader",
                        { fps: 10, qrbox: { width: 250, height: 250 } },
                        /* verbose= */ false
                    );
                    scannerRef.current.render(onScanSuccess, onScanError);
                }
            }, 300);

            return () => {
                clearTimeout(timer);
                if (scannerRef.current) {
                    scannerRef.current.clear().catch(error => {
                        console.error("Failed to clear html5QrcodeScanner. ", error);
                    });
                    scannerRef.current = null;
                }
            };
        }
    }, [isOpen, onScanSuccess, onScanError]);

    if (!isOpen) return null;

    return (
        <div style={{ width: '100%', maxWidth: '500px', margin: '0 auto' }}>
            <div id="qr-reader" style={{ border: 'none' }}></div>
        </div>
    );
}
