import { router, usePoll, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    AlertCircle,
    CheckCircle2,
    Crown,
    Play,
    Star,
    StopCircle,
    Timer,
    Zap,
} from 'lucide-react';
import { cancelDraft, startDraft } from '@/routes/rooms';
import { autoPick, pick } from '@/routes/rooms/draft';
import type { DraftPick, Player, Team } from '@/types/types';
import { teamLabel } from '@/types/team-labels';

const PICK_TIMER_MS = 2 * 60 * 1000;

interface DraftBoardRoom {
    id: number;
    status: string;
    team_size: number;
    num_teams: number;
    draft_order: number[] | null;
    current_pick_index: number;
    draft_started_at: string | null;
    players: Player[];
    teams: Team[];
    draft_picks: DraftPick[];
}

interface DraftBoardProps {
    room: DraftBoardRoom;
    isAdmin: boolean;
    currentUserId: number;
}

function currentTeamId(room: DraftBoardRoom): number | null {
    const order = room.draft_order;
    if (!order || order.length === 0) {
        return null;
    }
    const teamCount = order.length;
    const round = Math.floor(room.current_pick_index / teamCount);
    let position = room.current_pick_index % teamCount;
    if (round % 2 === 1) {
        position = teamCount - 1 - position;
    }
    return order[position] ?? null;
}

function formatRemaining(ms: number): string {
    const totalSeconds = Math.max(0, Math.floor(ms / 1000));
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
}

export function DraftBoard({ room, isAdmin, currentUserId }: DraftBoardProps) {
    const { errors } = usePage().props as {
        errors: Record<string, string>;
    };
    const isDrafting = room.status === 'drafting';
    const isCompleted = room.status === 'completed';
    const requiredSlots = room.team_size * room.num_teams;
    const playerCount = room.players.length;
    const isFull = playerCount >= requiredSlots;

    usePoll(3000);

    const autoPickFired = useRef(false);

    const activeTeamId = currentTeamId(room);
    const activeTeam = room.teams.find((team) => team.id === activeTeamId);
    const isMyTurn =
        isDrafting &&
        activeTeam !== undefined &&
        activeTeam.captain_user_id === currentUserId;

    const allCaptainsAssigned =
        room.teams.length === room.num_teams &&
        room.teams.every(
            (team) => team.captain_user_id && (team.players?.length ?? 0) > 0,
        );

    const assignedPlayerIds = useMemo(() => {
        const ids = new Set<number>();
        for (const team of room.teams) {
            for (const player of team.players ?? []) {
                ids.add(player.id);
            }
        }
        return ids;
    }, [room.teams]);

    const availablePlayers = useMemo(() => {
        const sorted = [...room.players].sort((a, b) => a.id - b.id);
        const mainIds = new Set(
            sorted.slice(0, requiredSlots).map((player) => player.id),
        );
        const available = sorted.filter(
            (player) => !assignedPlayerIds.has(player.id),
        );
        const availableMain = available.filter((player) =>
            mainIds.has(player.id),
        );
        return availableMain.length > 0 ? availableMain : available;
    }, [room.players, assignedPlayerIds, requiredSlots]);

    const pickDeadline = useMemo(() => {
        if (!room.draft_started_at) {
            return null;
        }
        let lastPickAt = new Date(room.draft_started_at).getTime();
        for (const draftPick of room.draft_picks) {
            if (draftPick.created_at) {
                const at = new Date(draftPick.created_at).getTime();
                if (at > lastPickAt) {
                    lastPickAt = at;
                }
            }
        }
        return lastPickAt + PICK_TIMER_MS;
    }, [room.draft_started_at, room.draft_picks]);

    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        if (!isDrafting) {
            return;
        }
        const interval = setInterval(() => setNow(Date.now()), 1000);
        return () => clearInterval(interval);
    }, [isDrafting]);

    const remainingMs = pickDeadline ? pickDeadline - now : 0;
    const timerExpired =
        isDrafting && pickDeadline !== null && remainingMs <= 0;

    useEffect(() => {
        if (!timerExpired) {
            autoPickFired.current = false;
            return;
        }
        if (!(isMyTurn || isAdmin)) {
            return;
        }
        if (autoPickFired.current) {
            return;
        }
        autoPickFired.current = true;
        router.post(
            autoPick.url(room.id),
            {},
            { preserveScroll: true, preserveState: true },
        );
    }, [timerExpired, isMyTurn, isAdmin, room.id]);

    function handleStartDraft() {
        router.post(startDraft.url(room.id), {}, { preserveScroll: true });
    }

    function handleCancelDraft() {
        router.post(cancelDraft.url(room.id), {}, { preserveScroll: true });
    }

    function handlePick(playerId: number) {
        router.post(
            pick.url(room.id),
            { player_id: playerId },
            { preserveScroll: true },
        );
    }

    function handleManualAutoPick() {
        router.post(
            autoPick.url(room.id),
            {},
            { preserveScroll: true, preserveState: true },
        );
    }

    if (room.status === 'waiting' || room.status === 'full') {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Play className="size-4" />
                        Start Draft
                    </CardTitle>
                    <CardDescription>
                        {isFull
                            ? 'The room is full. Assign captains and self-picks, then start the draft.'
                            : `Waiting for ${requiredSlots - playerCount} more player${requiredSlots - playerCount === 1 ? '' : 's'} before the draft can start.`}
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {!isFull && (
                        <p className="text-muted-foreground text-sm">
                            Player count: {playerCount}/{requiredSlots}
                        </p>
                    )}
                    {isAdmin && isFull && (
                        <div className="flex items-center justify-between gap-4">
                            <p className="text-muted-foreground text-sm">
                                {allCaptainsAssigned
                                    ? 'Ready to draft.'
                                    : 'Assign all captains with self-picks first.'}
                            </p>
                            <Button
                                onClick={handleStartDraft}
                                disabled={!allCaptainsAssigned}
                            >
                                <Play className="mr-2 size-4" />
                                Start Draft
                            </Button>
                        </div>
                    )}
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="space-y-4">
            <Card>
                <CardHeader className="pb-3">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <CardTitle className="flex items-center gap-2">
                            <Crown className="size-4" />
                            {isCompleted
                                ? 'Draft Complete'
                                : 'Draft in Progress'}
                        </CardTitle>
                        <div className="flex items-center gap-2">
                            {isCompleted && (
                                <Badge variant="secondary">
                                    <CheckCircle2 className="size-3" />
                                    Completed
                                </Badge>
                            )}
                            {isAdmin && isDrafting && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={handleCancelDraft}
                                >
                                    <StopCircle className="mr-1 size-4" />
                                    Cancel Draft
                                </Button>
                            )}
                        </div>
                    </div>
                    {!isCompleted && activeTeam && (
                        <CardDescription className="flex flex-wrap items-center gap-3 text-sm">
                            <span>
                                On the clock:{' '}
                                <span className="text-foreground font-medium">
                                    Team {activeTeam.pick_order} (
                                    {activeTeam.captain?.username ??
                                        activeTeam.captain?.name ??
                                        'Captain'}
                                    )
                                </span>
                            </span>
                            {timerExpired ? (
                                <span className="text-destructive flex items-center gap-1 font-medium">
                                    <AlertCircle className="size-4" />
                                    Timer expired — auto-picking…
                                </span>
                            ) : (
                                <span className="flex items-center gap-1 font-mono">
                                    <Timer className="size-4" />
                                    {formatRemaining(remainingMs)}
                                </span>
                            )}
                            {isMyTurn && !timerExpired && (
                                <Badge>Your turn</Badge>
                            )}
                        </CardDescription>
                    )}
                </CardHeader>
                <CardContent className="space-y-4">
                    {(errors.draft || errors.player_id) && (
                        <p className="text-destructive text-sm">
                            {errors.draft ?? errors.player_id}
                        </p>
                    )}

                    {isDrafting && (
                        <div>
                            <div className="mb-2 flex items-center justify-between">
                                <h3 className="text-sm font-medium">
                                    Available Players
                                </h3>
                                {!isMyTurn && (
                                    <span className="text-muted-foreground text-xs">
                                        Waiting for the current captain…
                                    </span>
                                )}
                            </div>
                            {availablePlayers.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    No players left to draft.
                                </p>
                            ) : (
                                <ul className="grid gap-2 sm:grid-cols-2">
                                    {availablePlayers.map((player) => (
                                        <li key={player.id}>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    handlePick(player.id)
                                                }
                                                disabled={
                                                    !isMyTurn || timerExpired
                                                }
                                                className="hover:bg-accent flex w-full items-center justify-between rounded-lg border p-3 text-left text-sm disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                <span className="font-medium">
                                                    {player.name}
                                                </span>
                                                <span className="text-muted-foreground flex items-center gap-1">
                                                    <Star className="size-3 fill-current text-amber-500" />
                                                    {player.rating}
                                                </span>
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                            {timerExpired && !isMyTurn && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="mt-3"
                                    onClick={handleManualAutoPick}
                                >
                                    <Zap className="mr-2 size-4" />
                                    Force Auto-Pick
                                </Button>
                            )}
                        </div>
                    )}

                    <div>
                        <h3 className="mb-2 text-sm font-medium">
                            Draft History
                        </h3>
                        {room.draft_picks.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No picks yet.
                            </p>
                        ) : (
                            <ol className="space-y-1">
                                {room.draft_picks.map((draftPick) => (
                                    <li
                                        key={draftPick.id}
                                        className="flex items-center justify-between rounded-md border px-3 py-2 text-sm"
                                    >
                                        <span className="flex items-center gap-2">
                                            <Badge variant="outline">
                                                #{draftPick.pick_number}
                                            </Badge>
                                            <span className="font-medium">
                                                {draftPick.player?.name ??
                                                    'Unknown'}
                                            </span>
                                            {draftPick.auto_picked && (
                                                <Badge variant="secondary">
                                                    <Zap className="size-3" />
                                                    auto
                                                </Badge>
                                            )}
                                        </span>
                                        <span className="text-muted-foreground">
                                            Team{' '}
                                            {draftPick.team?.pick_order ?? '?'}
                                        </span>
                                    </li>
                                ))}
                            </ol>
                        )}
                    </div>
                </CardContent>
            </Card>

            <div className="grid gap-4 sm:grid-cols-2">
                {room.teams.map((team) => (
                    <Card key={team.id}>
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center justify-between text-base">
                                <span className="flex items-center gap-2">
                                    <Crown className="size-4" />
                                    {teamLabel(team)}
                                </span>
                                <span
                                    className="size-4 rounded-full border"
                                    style={{ backgroundColor: team.color }}
                                />
                            </CardTitle>
                            <CardDescription>
                                {team.captain?.username ??
                                    team.captain?.name ??
                                    'No captain'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ul className="text-muted-foreground space-y-1 text-sm">
                                {(team.players ?? []).map((player) => (
                                    <li
                                        key={player.id}
                                        className="flex items-center justify-between"
                                    >
                                        <span>{player.name}</span>
                                        <span className="text-xs">
                                            ★{player.rating}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
}
