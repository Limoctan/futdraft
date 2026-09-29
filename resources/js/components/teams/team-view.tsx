import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Crown, Download, Save, Share2, Users } from 'lucide-react';
import { update as updateTeam } from '@/routes/rooms/teams';
import type { Team, TeamColorOption } from '@/types/types';
import { captainLabel, sortTeamPlayers, teamLabel } from './team-labels';
import { TeamImageCard } from './team-image-card';

interface TeamViewRoom {
    id: number;
    teams: Team[];
}

interface TeamViewProps {
    room: TeamViewRoom;
    roomName: string;
    teamColors: TeamColorOption[];
    currentUserId: number;
    isAdmin: boolean;
}

function imageFileName(label: string): string {
    const slug = label
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    return `${slug || 'team'}-team.png`;
}

interface TeamCardProps {
    room: TeamViewRoom;
    roomName: string;
    team: Team;
    teamColors: TeamColorOption[];
    currentUserId: number;
    isAdmin: boolean;
    takenColors: Set<string>;
}

function TeamCard({
    room,
    roomName,
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
    const [shareOpen, setShareOpen] = useState(false);
    const [generating, setGenerating] = useState(false);
    const imageRef = useRef<HTMLDivElement>(null);

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

    async function downloadImage() {
        const node = imageRef.current;

        if (!node) {
            return;
        }

        setGenerating(true);

        try {
            const { default: html2canvas } = await import('html2canvas');
            const canvas = await html2canvas(node, {
                backgroundColor: '#ffffff',
            });

            const link = document.createElement('a');
            link.download = imageFileName(label);
            link.href = canvas.toDataURL('image/png');
            link.click();
        } catch {
            toast.error('Could not generate the team image.');
        } finally {
            setGenerating(false);
        }
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

                <Dialog open={shareOpen} onOpenChange={setShareOpen}>
                    <DialogTrigger asChild>
                        <Button variant="outline" size="sm">
                            <Share2 className="mr-2 size-4" />
                            Share image
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="sm:max-w-xl">
                        <DialogHeader>
                            <DialogTitle>{label}</DialogTitle>
                            <DialogDescription>
                                Preview your team and download it as a PNG to
                                share it.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="flex justify-center overflow-x-auto">
                            <TeamImageCard
                                team={team}
                                roomName={roomName}
                                imageRef={imageRef}
                            />
                        </div>

                        <DialogFooter>
                            <Button
                                onClick={() => void downloadImage()}
                                disabled={generating}
                            >
                                <Download className="mr-2 size-4" />
                                {generating ? 'Generating...' : 'Download PNG'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </CardContent>
        </Card>
    );
}

export function TeamView({
    room,
    roomName,
    teamColors,
    currentUserId,
    isAdmin,
}: TeamViewProps) {
    return (
        <div className="grid gap-4 sm:grid-cols-2">
            {room.teams.map((team) => (
                <TeamCard
                    key={team.id}
                    room={room}
                    roomName={roomName}
                    team={team}
                    teamColors={teamColors}
                    currentUserId={currentUserId}
                    isAdmin={isAdmin}
                    takenColors={
                        new Set(
                            room.teams
                                .filter((other) => other.id !== team.id)
                                .map((other) => other.color),
                        )
                    }
                />
            ))}
        </div>
    );
}
