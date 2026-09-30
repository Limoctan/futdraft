import { teamLabel, sortTeamPlayers, captainLabel } from '@/types/team-labels';
import { Team, TeamColorOption, TeamViewRoom } from '@/types/types';
import { router } from '@inertiajs/react';
import { Users, Crown, Save } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '../ui/button';
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from '../ui/card';
import { Input } from '../ui/input';
import { update as updateTeam } from '@/routes/rooms/teams';

interface TeamCardProps {
    room: TeamViewRoom;
    team: Team;
    teamColors: TeamColorOption[];
    currentUserId: number;
    isAdmin: boolean;
    takenColors: Set<string>;
}

export function TeamCard({
    room,
    team,
    teamColors,
    currentUserId,
    isAdmin,
    takenColors,
}: TeamCardProps) {
    const canManage = isAdmin || team.captain_user_id === currentUserId;
    const [nameDraft, setNameDraft] = useState(team.name ?? '');
    const [colorPending, setColorPending] = useState<string | null>(null);
    const [colorError, setColorError] = useState<string | null>(null);
    const [nameError, setNameError] = useState<string | null>(null);
    const [savingColor, setSavingColor] = useState(false);
    const [savingName, setSavingName] = useState(false);

    useEffect(() => {
        setColorPending(null);
    }, [team.color]);

    const label = teamLabel(team);
    const players = sortTeamPlayers(team.players);
    const selectedColor = colorPending ?? team.color;
    const teamUrl = updateTeam.url({ room: room.id, team: team.id });

    function selectColor(color: string) {
        if (savingColor || color === selectedColor) {
            return;
        }

        setColorPending(color);
        setColorError(null);
        setSavingColor(true);

        router.patch(
            teamUrl,
            { color },
            {
                preserveScroll: true,
                onError: (errors) => {
                    setColorPending(null);
                    setColorError(
                        errors.color ?? 'Could not update the team color.',
                    );
                },
                onFinish: () => setSavingColor(false),
            },
        );
    }

    function saveName(e: React.FormEvent) {
        e.preventDefault();

        if (savingName || nameDraft === (team.name ?? '')) {
            return;
        }

        setNameError(null);
        setSavingName(true);

        router.patch(
            teamUrl,
            { name: nameDraft },
            {
                preserveScroll: true,
                onError: (errors) => {
                    setNameError(
                        errors.name ?? 'Could not update the team name.',
                    );
                },
                onFinish: () => setSavingName(false),
            },
        );
    }

    return (
        <Card>
            <CardHeader className="pb-3">
                <CardTitle className="flex items-center justify-between text-base">
                    <span className="flex items-center gap-2">
                        <span
                            className="size-4 rounded-full border"
                            style={{ backgroundColor: team.color }}
                        />
                        {label}
                    </span>
                    <span className="text-muted-foreground flex items-center gap-1 text-sm font-normal">
                        <Users className="size-4" />
                        {players.length}
                    </span>
                </CardTitle>
                <CardDescription className="flex items-center gap-1">
                    <Crown className="size-3.5" />
                    {captainLabel(team)}
                </CardDescription>
            </CardHeader>

            <CardContent className="space-y-4">
                <ul className="space-y-2">
                    {players.map((player, index) => (
                        <li
                            key={player.id}
                            className="flex items-center justify-between rounded-lg border px-3 py-2 text-sm"
                        >
                            <span className="flex items-center gap-2 font-medium">
                                <span
                                    className="flex size-6 items-center justify-center rounded-full text-xs font-bold text-white"
                                    style={{ backgroundColor: team.color }}
                                >
                                    {index + 1}
                                </span>
                                {player.name}
                            </span>
                            <span className="text-amber-500">
                                ★ {player.rating}
                            </span>
                        </li>
                    ))}
                </ul>

                {canManage && (
                    <div className="space-y-2">
                        <p className="text-sm font-medium">Team color</p>
                        <div className="flex flex-wrap gap-2">
                            {teamColors.map((option) => {
                                const isSelected =
                                    selectedColor === option.value;
                                const isTaken =
                                    takenColors.has(option.value) &&
                                    !isSelected;

                                return (
                                    <button
                                        key={option.value}
                                        type="button"
                                        onClick={() =>
                                            selectColor(option.value)
                                        }
                                        disabled={isTaken || savingColor}
                                        title={
                                            isTaken
                                                ? `${option.label} — already in use`
                                                : option.label
                                        }
                                        aria-label={`Set team color ${option.label}`}
                                        className={`size-8 rounded-full border-2 transition-transform ${
                                            isSelected
                                                ? 'border-foreground scale-110'
                                                : 'border-transparent hover:scale-110'
                                        } ${isTaken ? 'cursor-not-allowed opacity-30' : ''}`}
                                        style={{
                                            backgroundColor: option.value,
                                        }}
                                    />
                                );
                            })}
                        </div>
                        {colorError && (
                            <p className="text-destructive text-sm">
                                {colorError}
                            </p>
                        )}
                    </div>
                )}

                {canManage && (
                    <form onSubmit={saveName} className="flex gap-2">
                        <Input
                            value={nameDraft}
                            onChange={(e) => setNameDraft(e.target.value)}
                            placeholder={`Team ${team.pick_order}`}
                            aria-label="Team name"
                            maxLength={255}
                        />
                        <Button
                            type="submit"
                            variant="outline"
                            size="icon"
                            disabled={savingName}
                            title="Save team name"
                        >
                            <Save className="size-4" />
                        </Button>
                    </form>
                )}

                {canManage && nameError && (
                    <p className="text-destructive text-sm">{nameError}</p>
                )}
            </CardContent>
        </Card>
    );
}
