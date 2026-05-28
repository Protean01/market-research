export type Question = {
    id: string | number;
    type: string;
    text: string;
    hint?: string;
    options?: (string | { id?: string | number; label: string; image_url?: string })[];
    required: boolean;
    logic?: Record<string, string | number>; // For simple jumps
    visibility?: {
        logic: 'and' | 'or';
        conditions: {
            type: 'answer' | 'trait';
            key: string;
            operator: 'eq' | 'neq' | 'contains';
            value: any;
        }[];
    };
    max?: number;
};

export type Survey = {
    id: number;
    user_id: number;
    title: string;
    description: string;
    reward_amount: number;
    reward_points: number;
    reward_type?: 'cash' | 'points' | 'prize_draw' | 'airtime';
    prize_name?: string;
    estimated_time?: number;
    status: string;
    is_active: boolean;
    draw_phase_active?: boolean;
    response_cap: number;
    response_count: number;
    questions: Question[];
    enrichment_questions: Question[];
    target_gender?: string;
    target_age_band?: string;
    target_location?: string;
    target_language?: string;
    target_employment?: string;
    target_income_band?: string;
    created_at: string;
    updated_at: string;
};
