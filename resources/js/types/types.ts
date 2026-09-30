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

export interface TeamUser {
    id: number;
    name: string;
    username: string;
    email: string;
}

export interface TeamPlayer extends Player {
    pivot?: {
        team_id: number;
        player_id: number;
        pick_number: number;
    };
}

export interface Team {
    id: number;
    room_id: number;
    captain_user_id: number;
    name: string | null;
    color: string;
    pick_order: number;
    captain: TeamUser;
    players: TeamPlayer[];
    created_at?: string;
    updated_at?: string;
}

export interface TeamColorOption {
    value: string;
    label: string;
}

export interface TeamViewRoom {
    id: number;
    teams: Team[];
}

export interface DraftPick {
    id: number;
    room_id: number;
    team_id: number;
    player_id: number;
    pick_number: number;
    picked_by_user_id: number | null;
    auto_picked: boolean;
    created_at?: string;
    updated_at?: string;
    player: Player;
    team: {
        id: number;
        pick_order: number;
        captain_user_id: number;
        captain: TeamUser;
    };
}
