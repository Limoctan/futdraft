import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Crown, Save, UserCheck } from 'lucide-react';
import { assignCaptains } from '@/routes/rooms';
import type { Player, Team, TeamUser } from '@/types/types';

interface CaptainAssignmentProps {
    room: {
        id: number;
        status: string;
        num_teams: number;
        users: TeamUser[];
        players: Player[];
        teams: Team[];
    };
    isAdmin: boolean;
}

interface CaptainSlot {
    user_id: string;
    player_id: string;
}

const EMPTY_SLOT: CaptainSlot = { user_id: '', player_id: '' };

function TeamSummaryCard({ team }: { team: Team }) {
    return (
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className="flex items-center justify-between text-base">
                    <span className="flex items-center gap-2">
                        <Crown className="size-4" />
                        Team {team.pick_order}
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
            <CardContent className="text-muted-foreground text-sm">
                Self-pick:{' '}
                <span className="text-foreground font-medium">
                    {team.players[0]?.name ?? 'Not assigned'}
                </span>
            </CardContent>
        </Card>
    );
}

export function CaptainAssignment({ room, isAdmin }: CaptainAssignmentProps) {
    const isLocked = room.status === 'drafting' || room.status === 'completed';
    const canEdit = isAdmin && !isLocked;
    const allAssigned =
        room.teams.length === room.num_teams &&
        room.teams.every(
            (team) => team.captain_user_id && team.players.length > 0,
        );

    const initialSlots: CaptainSlot[] = Array.from(
        { length: room.num_teams },
        (_, index) => {
            const team = room.teams[index];
            if (!team) {
                return EMPTY_SLOT;
            }
            return {
                user_id: String(team.captain_user_id ?? ''),
                player_id: String(team.players[0]?.id ?? ''),
            };
        },
    );

    const [slots, setSlots] = useState<CaptainSlot[]>(initialSlots);

    const form = useForm<{ captains: CaptainSlot[] }>({
        captains: initialSlots,
    });

    useEffect(() => {
        setSlots(initialSlots);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [room.teams, room.num_teams]);

    function updateSlot(index: number, patch: Partial<CaptainSlot>) {
        setSlots((current) =>
            current.map((slot, i) =>
                i === index ? { ...slot, ...patch } : slot,
            ),
        );
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.setData(
            'captains',
            slots.map((slot) => ({
                user_id: slot.user_id,
                player_id: slot.player_id,
            })),
        );
        form.post(assignCaptains.url(room.id), {
            preserveScroll: true,
        });
    }

    if (!canEdit) {
        return (
            <div className="space-y-4">
                {room.teams.length === 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Crown className="size-4" />
                                Captains
                            </CardTitle>
                            <CardDescription>
                                Captains have not been assigned yet.
                            </CardDescription>
                        </CardHeader>
                    </Card>
                ) : (
                    room.teams.map((team) => (
                        <TeamSummaryCard key={team.id} team={team} />
                    ))
                )}
            </div>
        );
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Crown className="size-4" />
                        Assign Captains
                    </CardTitle>
                    <CardDescription>
                        Select a captain (room member) and their self-pick
                        player for each team. Self-picks count as the first
                        pick.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {slots.map((slot, index) => {
                        const rowError = (
                            form.errors as Record<string, string>
                        )[`captains.${index}.user_id`];
                        const playerError = (
                            form.errors as Record<string, string>
                        )[`captains.${index}.player_id`];

                        return (
                            <div
                                key={index}
                                className="grid gap-3 rounded-lg border p-3 sm:grid-cols-2"
                            >
                                <div>
                                    <Label>Captain — Team {index + 1}</Label>
                                    <Select
                                        value={slot.user_id || undefined}
                                        onValueChange={(value) =>
                                            updateSlot(index, {
                                                user_id: value,
                                            })
                                        }
                                    >
                                        <SelectTrigger className="mt-1">
                                            <SelectValue placeholder="Select a member" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {room.users.map((user) => (
                                                <SelectItem
                                                    key={user.id}
                                                    value={String(user.id)}
                                                >
                                                    {user.username}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {rowError && (
                                        <p className="text-destructive mt-1 text-sm">
                                            {rowError}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label>Self-pick — Team {index + 1}</Label>
                                    <Select
                                        value={slot.player_id || undefined}
                                        onValueChange={(value) =>
                                            updateSlot(index, {
                                                player_id: value,
                                            })
                                        }
                                    >
                                        <SelectTrigger className="mt-1">
                                            <SelectValue placeholder="Select a player" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {room.players.map((player) => (
                                                <SelectItem
                                                    key={player.id}
                                                    value={String(player.id)}
                                                >
                                                    {player.name} (★
                                                    {player.rating})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {playerError && (
                                        <p className="text-destructive mt-1 text-sm">
                                            {playerError}
                                        </p>
                                    )}
                                </div>
                            </div>
                        );
                    })}

                    {(form.errors.captains ||
                        (form.errors as Record<string, string>).room) && (
                        <p className="text-destructive text-sm">
                            {form.errors.captains ??
                                (form.errors as Record<string, string>).room}
                        </p>
                    )}

                    <div className="flex items-center justify-between">
                        <p className="text-muted-foreground flex items-center gap-1 text-sm">
                            <UserCheck className="size-4" />
                            {allAssigned
                                ? 'All captains assigned'
                                : 'All captains must be assigned before the draft can start'}
                        </p>
                        <Button type="submit" disabled={form.processing}>
                            <Save className="mr-2 size-4" />
                            {form.processing ? 'Saving...' : 'Save Captains'}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            {room.teams.length > 0 && (
                <div className="grid gap-4 sm:grid-cols-2">
                    {room.teams.map((team) => (
                        <TeamSummaryCard key={team.id} team={team} />
                    ))}
                </div>
            )}
        </form>
    );
}
