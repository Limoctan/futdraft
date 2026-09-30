import type { Team, TeamPlayer } from '@/types/types';

export function teamLabel(team: Team): string {
    return team.name?.trim() ? team.name : `Team ${team.pick_order}`;
}

export function captainLabel(team: Team): string {
    return team.captain?.username ?? team.captain?.name ?? 'No captain';
}

export function sortTeamPlayers(players: TeamPlayer[]): TeamPlayer[] {
    return [...players].sort(
        (a, b) => (a.pivot?.pick_number ?? 0) - (b.pivot?.pick_number ?? 0),
    );
}
