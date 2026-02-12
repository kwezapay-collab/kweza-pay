// TypeScript types for database tables
// Generated from PostgreSQL schema

export type Json =
    | string
    | number
    | boolean
    | null
    | { [key: string]: Json | undefined }
    | Json[]

export interface Database {
    public: {
        Tables: {
            users: {
                Row: {
                    user_id: number
                    phone_number: string
                    email: string | null
                    registration_number: string | null
                    full_name: string
                    university: string | null
                    pin_hash: string
                    user_type: 'Student' | 'Merchant' | 'Admin' | 'StudentUnion' | 'Person'
                    wallet_balance: number
                    verification_code: string | null
                    is_verified: boolean
                    verification_expires_at: string | null
                    profile_pic: string | null
                    created_at: string
                    updated_at: string
                }
                Insert: {
                    user_id?: number
                    phone_number: string
                    email?: string | null
                    registration_number?: string | null
                    full_name: string
                    university?: string | null
                    pin_hash: string
                    user_type?: 'Student' | 'Merchant' | 'Admin' | 'StudentUnion' | 'Person'
                    wallet_balance?: number
                    verification_code?: string | null
                    is_verified?: boolean
                    verification_expires_at?: string | null
                    profile_pic?: string | null
                    created_at?: string
                    updated_at?: string
                }
                Update: {
                    user_id?: number
                    phone_number?: string
                    email?: string | null
                    registration_number?: string | null
                    full_name?: string
                    university?: string | null
                    pin_hash?: string
                    user_type?: 'Student' | 'Merchant' | 'Admin' | 'StudentUnion' | 'Person'
                    wallet_balance?: number
                    verification_code?: string | null
                    is_verified?: boolean
                    verification_expires_at?: string | null
                    profile_pic?: string | null
                    created_at?: string
                    updated_at?: string
                }
            }
            user_roles: {
                Row: {
                    id: number
                    user_id: number
                    role: 'Person' | 'Student' | 'Merchant' | 'Admin' | 'StudentUnion'
                    created_at: string
                }
                Insert: {
                    id?: number
                    user_id: number
                    role: 'Person' | 'Student' | 'Merchant' | 'Admin' | 'StudentUnion'
                    created_at?: string
                }
                Update: {
                    id?: number
                    user_id?: number
                    role?: 'Person' | 'Student' | 'Merchant' | 'Admin' | 'StudentUnion'
                    created_at?: string
                }
            }
            merchants: {
                Row: {
                    merchant_id: number
                    user_id: number
                    business_name: string
                    qr_code_token: string
                    agent_code: string | null
                    is_approved: boolean
                    fee_paid: boolean
                }
                Insert: {
                    merchant_id?: number
                    user_id: number
                    business_name: string
                    qr_code_token: string
                    agent_code?: string | null
                    is_approved?: boolean
                    fee_paid?: boolean
                }
                Update: {
                    merchant_id?: number
                    user_id?: number
                    business_name?: string
                    qr_code_token?: string
                    agent_code?: string | null
                    is_approved?: boolean
                    fee_paid?: boolean
                }
            }
            transactions: {
                Row: {
                    txn_id: number
                    txn_type: 'QR_PAY' | 'P2P' | 'TOP_UP' | 'SU_FEE' | 'WITHDRAWAL' | 'SYSTEM_FEE' | 'EVENT_TICKET' | 'CAFE_PAYMENT' | 'CAFE_PAY'
                    sender_id: number
                    receiver_id: number
                    amount: number
                    reference_code: string
                    description: string | null
                    status: 'pending' | 'completed' | 'failed' | 'cancelled'
                    created_at: string
                }
                Insert: {
                    txn_id?: number
                    txn_type: 'QR_PAY' | 'P2P' | 'TOP_UP' | 'SU_FEE' | 'WITHDRAWAL' | 'SYSTEM_FEE' | 'EVENT_TICKET' | 'CAFE_PAYMENT' | 'CAFE_PAY'
                    sender_id: number
                    receiver_id: number
                    amount: number
                    reference_code: string
                    description?: string | null
                    status?: 'pending' | 'completed' | 'failed' | 'cancelled'
                    created_at?: string
                }
                Update: {
                    txn_id?: number
                    txn_type?: 'QR_PAY' | 'P2P' | 'TOP_UP' | 'SU_FEE' | 'WITHDRAWAL' | 'SYSTEM_FEE' | 'EVENT_TICKET' | 'CAFE_PAYMENT' | 'CAFE_PAY'
                    sender_id?: number
                    receiver_id?: number
                    amount?: number
                    reference_code?: string
                    description?: string | null
                    status?: 'pending' | 'completed' | 'failed' | 'cancelled'
                    created_at?: string
                }
            }
            events: {
                Row: {
                    event_id: number
                    event_name: string
                    event_description: string | null
                    event_picture: string | null
                    ticket_price: number
                    ticket_template: string | null
                    event_date: string | null
                    event_location: string | null
                    airtel_money_code: string | null
                    airtel_money_id: string | null
                    airtel_qr_image: string | null
                    max_tickets: number | null
                    tickets_sold: number
                    is_active: boolean
                    created_by: number | null
                    created_at: string
                    updated_at: string
                }
                Insert: {
                    event_id?: number
                    event_name: string
                    event_description?: string | null
                    event_picture?: string | null
                    ticket_price: number
                    ticket_template?: string | null
                    event_date?: string | null
                    event_location?: string | null
                    airtel_money_code?: string | null
                    airtel_money_id?: string | null
                    airtel_qr_image?: string | null
                    max_tickets?: number | null
                    tickets_sold?: number
                    is_active?: boolean
                    created_by?: number | null
                    created_at?: string
                    updated_at?: string
                }
                Update: {
                    event_id?: number
                    event_name?: string
                    event_description?: string | null
                    event_picture?: string | null
                    ticket_price?: number
                    ticket_template?: string | null
                    event_date?: string | null
                    event_location?: string | null
                    airtel_money_code?: string | null
                    airtel_money_id?: string | null
                    airtel_qr_image?: string | null
                    max_tickets?: number | null
                    tickets_sold?: number
                    is_active?: boolean
                    created_by?: number | null
                    created_at?: string
                    updated_at?: string
                }
            }
            event_tickets: {
                Row: {
                    ticket_id: number
                    event_id: number
                    user_id: number
                    ticket_code: string
                    purchase_amount: number
                    qr_code_data: string | null
                    is_used: boolean
                    used_at: string | null
                    created_at: string
                }
                Insert: {
                    ticket_id?: number
                    event_id: number
                    user_id: number
                    ticket_code: string
                    purchase_amount: number
                    qr_code_data?: string | null
                    is_used?: boolean
                    used_at?: string | null
                    created_at?: string
                }
                Update: {
                    ticket_id?: number
                    event_id?: number
                    user_id?: number
                    ticket_code?: string
                    purchase_amount?: number
                    qr_code_data?: string | null
                    is_used?: boolean
                    used_at?: string | null
                    created_at?: string
                }
            }
            event_ticket_inventory: {
                Row: {
                    inventory_id: number
                    event_id: number
                    serial_number: string
                    is_assigned: boolean
                    assigned_at: string | null
                    ticket_id: number | null
                    created_at: string
                }
                Insert: {
                    inventory_id?: number
                    event_id: number
                    serial_number: string
                    is_assigned?: boolean
                    assigned_at?: string | null
                    ticket_id?: number | null
                    created_at?: string
                }
                Update: {
                    inventory_id?: number
                    event_id?: number
                    serial_number?: string
                    is_assigned?: boolean
                    assigned_at?: string | null
                    ticket_id?: number | null
                    created_at?: string
                }
            }
            campus_cafes: {
                Row: {
                    cafe_id: number
                    cafe_name: string
                    cafe_description: string | null
                    cafe_logo: string | null
                    airtel_money_code: string
                    qr_code_image: string | null
                    is_active: boolean
                    user_id: number | null
                    created_at: string
                    updated_at: string
                }
                Insert: {
                    cafe_id?: number
                    cafe_name: string
                    cafe_description?: string | null
                    cafe_logo?: string | null
                    airtel_money_code: string
                    qr_code_image?: string | null
                    is_active?: boolean
                    user_id?: number | null
                    created_at?: string
                    updated_at?: string
                }
                Update: {
                    cafe_id?: number
                    cafe_name?: string
                    cafe_description?: string | null
                    cafe_logo?: string | null
                    airtel_money_code?: string
                    qr_code_image?: string | null
                    is_active?: boolean
                    user_id?: number | null
                    created_at?: string
                    updated_at?: string
                }
            }
            help_reports: {
                Row: {
                    report_id: number
                    user_id: number
                    user_type: string
                    subject: string
                    message: string
                    status: 'NEW' | 'VIEWED' | 'RESOLVED'
                    admin_notes: string | null
                    resolved_by: number | null
                    resolved_at: string | null
                    created_at: string
                    updated_at: string
                }
                Insert: {
                    report_id?: number
                    user_id: number
                    user_type: string
                    subject: string
                    message: string
                    status?: 'NEW' | 'VIEWED' | 'RESOLVED'
                    admin_notes?: string | null
                    resolved_by?: number | null
                    resolved_at?: string | null
                    created_at?: string
                    updated_at?: string
                }
                Update: {
                    report_id?: number
                    user_id?: number
                    user_type?: string
                    subject?: string
                    message?: string
                    status?: 'NEW' | 'VIEWED' | 'RESOLVED'
                    admin_notes?: string | null
                    resolved_by?: number | null
                    resolved_at?: string | null
                    created_at?: string
                    updated_at?: string
                }
            }
            student_union: {
                Row: {
                    su_id: number
                    receipt_type: string
                    date: string
                    reference_number: string
                    student_name: string
                    student_id: string
                    program: string
                    year: number
                    university: string
                    description: string | null
                    amount_paid: number
                    service_fee: number
                    total_amount: number
                    recipient: string
                    created_at: string
                }
                Insert: {
                    su_id?: number
                    receipt_type: string
                    date: string
                    reference_number: string
                    student_name: string
                    student_id: string
                    program: string
                    year: number
                    university: string
                    description?: string | null
                    amount_paid: number
                    service_fee?: number
                    total_amount: number
                    recipient: string
                    created_at?: string
                }
                Update: {
                    su_id?: number
                    receipt_type?: string
                    date?: string
                    reference_number?: string
                    student_name?: string
                    student_id?: string
                    program?: string
                    year?: number
                    university?: string
                    description?: string | null
                    amount_paid?: number
                    service_fee?: number
                    total_amount?: number
                    recipient?: string
                    created_at?: string
                }
            }
        }
        Views: {
            [_ in never]: never
        }
        Functions: {
            [_ in never]: never
        }
        Enums: {
            [_ in never]: never
        }
    }
}
