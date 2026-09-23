export const statusColors: Record<
    string,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    waiting: 'secondary',
    full: 'default',
    drafting: 'destructive',
    completed: 'outline',
};

export const currencies = ['USD', 'EUR', 'COP', 'ARS', 'MXN', 'CLP', 'BRL'];
export const teamSizes = [5, 7, 11];

export interface Payment {
    id: number;
    player_id: number;
    marked_by_user_id: number;
    reference_image_path: string | null;
    paid_at: string;
    created_at?: string;
    updated_at?: string;
}

export interface Player {
    id: number;
    room_id: number;
    name: string;
    rating: number;
    is_captain: boolean;
    captain_user_id: number | null;
    payment: Payment | null;
    created_at?: string;
    updated_at?: string;
}
