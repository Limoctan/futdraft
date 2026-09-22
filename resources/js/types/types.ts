export const statusColors: Record<
    string,
    "default" | "secondary" | "outline" | "destructive"
> = {
    waiting: "secondary",
    full: "default",
    drafting: "destructive",
    completed: "outline",
};

export const currencies = ["USD", "EUR", "COP", "ARS", "MXN", "CLP", "BRL"];
export const teamSizes = [5, 7, 11];

export interface Player {
    id: number;
    room_id: number;
    name: string;
    rating: number;
    is_captain: boolean;
    captain_user_id: number | null;
    created_at?: string;
    updated_at?: string;
}

