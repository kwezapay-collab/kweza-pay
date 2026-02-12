/**
 * PayChanger Payment Service
 * Converts PHP PayChangerService.php to TypeScript
 */

interface PayChangerConfig {
    publicKey: string;
    secretKey: string;
    mode: 'test' | 'live';
    baseUrl: string;
    currency: string;
}

interface PaymentRequest {
    mobileNumber: string;
    amount: number;
    reference: string;
    description?: string;
}

interface PaymentResponse {
    success: boolean;
    data?: any;
    message?: string;
}

export class PayChangerService {
    private config: PayChangerConfig;

    constructor() {
        const mode = (process.env.PAYCHANGER_MODE || 'test') as 'test' | 'live';

        this.config = {
            publicKey: process.env.PAYCHANGER_PUBLIC_KEY || '',
            secretKey: process.env.PAYCHANGER_SECRET_KEY || '',
            mode,
            baseUrl: mode === 'test'
                ? (process.env.PAYCHANGER_BASE_URL_TEST || 'https://api.paychanger.com/v1')
                : (process.env.PAYCHANGER_BASE_URL_LIVE || 'https://api.paychanger.com/v1'),
            currency: 'MWK',
        };

        if (!this.config.publicKey || !this.config.secretKey) {
            throw new Error('PayChanger API keys are not configured');
        }
    }

    /**
     * Initiate a Mobile Money Payment (USSD Push)
     */
    async initiatePayment({
        mobileNumber,
        amount,
        reference,
        description = 'Payment via Kweza Pay',
    }: PaymentRequest): Promise<PaymentResponse> {
        try {
            const payload = {
                amount,
                currency: this.config.currency,
                customer_phone: mobileNumber,
                reference,
                description,
                callback_url: `${process.env.NEXT_PUBLIC_APP_URL}/api/webhooks/paychanger`,
            };

            console.log('[PayChanger] Initiating payment:', { reference, amount, mobileNumber });

            const response = await fetch(`${this.config.baseUrl}/payments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.config.secretKey}`,
                    'X-Public-Key': this.config.publicKey,
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok) {
                console.error('[PayChanger] API Error:', data);
                throw new Error(data.message || 'Payment initiation failed');
            }

            console.log('[PayChanger] Payment initiated successfully:', data);

            return {
                success: true,
                data,
            };
        } catch (error) {
            console.error('[PayChanger] Error:', error);
            return {
                success: false,
                message: error instanceof Error ? error.message : 'Unknown error',
            };
        }
    }

    /**
     * Verify a payment status
     */
    async verifyPayment(reference: string): Promise<PaymentResponse> {
        try {
            const response = await fetch(`${this.config.baseUrl}/payments/${reference}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${this.config.secretKey}`,
                    'X-Public-Key': this.config.publicKey,
                },
            });

            const data = await response.json();

            return {
                success: response.ok,
                data,
            };
        } catch (error) {
            console.error('[PayChanger] Verification error:', error);
            return {
                success: false,
                message: error instanceof Error ? error.message : 'Verification failed',
            };
        }
    }
}

// Singleton instance
export const payChangerService = new PayChangerService();
