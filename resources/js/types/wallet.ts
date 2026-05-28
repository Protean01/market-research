export type WalletTransaction = {
    id: number;
    wallet_id: number;
    type: 'earn' | 'redeem';
    points: number;
    status: 'processing' | 'completed' | 'failed';
    meta?: any;
    created_at: string;
    updated_at: string;
};

export type Wallet = {
    id: number;
    user_id: number;
    balance: number;
    points: number;
    created_at: string;
    updated_at: string;
    transactions?: WalletTransaction[];
};
