import { useState } from "react";
import { useForm, router } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Plus,
    Pencil,
    Trash2,
    Star,
    Users,
    CheckCircle2,
    AlertCircle,
    Shield,
    Sparkles,
    UserCheck,
} from "lucide-react";
import { store, update, destroy } from "@/routes/rooms/players";
import type { Player } from "@/types/types";

interface PlayerListProps {
    room: {
        id: number;
        status: string;
        team_size: number;
        num_teams: number;
        players: Player[];
    };
    isMember: boolean;
    isAdmin?: boolean;
}

export function PlayerList({ room, isMember, isAdmin }: PlayerListProps) {
    const [addOpen, setAddOpen] = useState(false);
    const [editingPlayer, setEditingPlayer] = useState<Player | null>(null);
    const [deletingPlayer, setDeletingPlayer] = useState<Player | null>(null);

    const requiredSlots = room.team_size * room.num_teams;
    const currentCount = room.players.length;
    const isLocked = room.status === "drafting" || room.status === "completed";
    const isFull = currentCount >= requiredSlots;
    const reserveCount = Math.max(0, currentCount - requiredSlots);

    // Add Player Form
    const addForm = useForm({
        name: "",
        rating: 3,
    });

    // Edit Player Form
    const editForm = useForm({
        name: "",
        rating: 3,
    });

    function openAddModal() {
        addForm.reset();
        addForm.clearErrors();
        setAddOpen(true);
    }

    function handleAdd(e: React.FormEvent) {
        e.preventDefault();
        addForm.post(store.url({ room: room.id }), {
            onSuccess: () => {
                setAddOpen(false);
                addForm.reset();
            },
        });
    }

    function openEditModal(player: Player) {
        setEditingPlayer(player);
        editForm.setData({
            name: player.name,
            rating: player.rating,
        });
        editForm.clearErrors();
    }

    function handleEdit(e: React.FormEvent) {
        e.preventDefault();
        if (!editingPlayer) return;

        editForm.patch(
            update.url({ room: room.id, player: editingPlayer.id }),
            {
                onSuccess: () => {
                    setEditingPlayer(null);
                    editForm.reset();
                },
            },
        );
    }

    function handleDelete() {
        if (!deletingPlayer) return;

        router.delete(
            destroy.url({ room: room.id, player: deletingPlayer.id }),
            {
                onSuccess: () => {
                    setDeletingPlayer(null);
                },
            },
        );
    }

    return (
        <div className="space-y-6">
            {/* Header & Capacity Card */}
            <Card>
                <CardHeader className="pb-3">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle className="flex items-center gap-2 text-xl font-bold">
                                <Users className="h-5 w-5 text-primary" />
                                Player Roster
                            </CardTitle>
                            <CardDescription className="mt-1">
                                {room.num_teams} teams &times; {room.team_size}{" "}
                                players = {requiredSlots} required draft slots
                            </CardDescription>
                        </div>

                        {isMember && !isLocked && (
                            <Button
                                onClick={openAddModal}
                                className="flex items-center gap-1.5 self-start sm:self-auto"
                            >
                                <Plus className="h-4 w-4" />
                                Add Player
                            </Button>
                        )}
                    </div>
                </CardHeader>
                <CardContent>
                    {/* Progress Bar & Status Badges */}
                    <div className="space-y-2">
                        <div className="flex items-center justify-between text-sm font-medium">
                            <span className="flex items-center gap-2">
                                <span>
                                    {currentCount} of {requiredSlots} Required
                                    Slots Filled
                                </span>
                                {isFull ? (
                                    <Badge
                                        variant="default"
                                        className="bg-emerald-600 text-white"
                                    >
                                        <CheckCircle2 className="mr-1 h-3.5 w-3.5" />
                                        Full
                                    </Badge>
                                ) : (
                                    <Badge variant="secondary">
                                        {requiredSlots - currentCount} more needed
                                    </Badge>
                                )}
                            </span>
                            {reserveCount > 0 && (
                                <Badge
                                    variant="outline"
                                    className="border-amber-500/40 text-amber-600 dark:text-amber-400"
                                >
                                    +{reserveCount} Reserve
                                </Badge>
                            )}
                        </div>

                        {/* Progress bar */}
                        <div className="h-2.5 w-full overflow-hidden rounded-full bg-secondary">
                            <div
                                className={`h-full transition-all duration-300 ${
                                    isFull ? "bg-emerald-500" : "bg-primary"
                                }`}
                                style={{
                                    width: `${Math.min(
                                        100,
                                        (currentCount / requiredSlots) * 100,
                                    )}%`,
                                }}
                            />
                        </div>
                    </div>

                    {isLocked && (
                        <div className="mt-4 flex items-center gap-2 rounded-lg border border-amber-500/20 bg-amber-50/50 p-3 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-950/20 dark:text-amber-300">
                            <AlertCircle className="h-4 w-4 shrink-0" />
                            <span>
                                Player modifications are locked while the match
                                is in {room.status} status.
                            </span>
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Players Table */}
            <Card>
                <CardContent className="p-0">
                    {room.players.length === 0 ? (
                        <div className="flex flex-col items-center justify-center p-12 text-center">
                            <Users className="h-12 w-12 text-muted-foreground/40 mb-3" />
                            <h3 className="text-base font-semibold">
                                No Players Added Yet
                            </h3>
                            <p className="mt-1 text-sm text-muted-foreground max-w-sm">
                                Start adding players with their soccer skill
                                ratings (1–5) to build the match roster.
                            </p>
                            {isMember && !isLocked && (
                                <Button
                                    onClick={openAddModal}
                                    variant="outline"
                                    className="mt-4 flex items-center gap-1.5"
                                >
                                    <Plus className="h-4 w-4" />
                                    Add First Player
                                </Button>
                            )}
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-muted/40 text-xs font-semibold uppercase text-muted-foreground">
                                    <tr>
                                        <th className="py-3.5 pl-4 pr-2 sm:pl-6 w-12 text-center">
                                            #
                                        </th>
                                        <th className="px-3 py-3.5">Player</th>
                                        <th className="px-3 py-3.5">Rating</th>
                                        <th className="px-3 py-3.5">Slot Type</th>
                                        {isMember && !isLocked && (
                                            <th className="py-3.5 pl-3 pr-4 sm:pr-6 text-right">
                                                Actions
                                            </th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {room.players.map((player, index) => {
                                        const isReserve = index >= requiredSlots;
                                        return (
                                            <tr
                                                key={player.id}
                                                className={`transition-colors hover:bg-muted/30 ${
                                                    isReserve
                                                        ? "bg-amber-500/5 dark:bg-amber-500/10"
                                                        : ""
                                                }`}
                                            >
                                                <td className="py-3 pl-4 pr-2 sm:pl-6 font-mono text-xs text-muted-foreground text-center">
                                                    {index + 1}
                                                </td>
                                                <td className="px-3 py-3 font-medium text-foreground">
                                                    <div className="flex items-center gap-2">
                                                        <span>{player.name}</span>
                                                        {player.is_captain && (
                                                            <Badge
                                                                variant="outline"
                                                                className="border-amber-400 bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 text-[10px] py-0 px-1.5"
                                                            >
                                                                <Shield className="mr-0.5 h-2.5 w-2.5 inline" />
                                                                Captain
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-3 py-3">
                                                    <div className="flex items-center gap-1">
                                                        {[1, 2, 3, 4, 5].map(
                                                            (star) => (
                                                                <Star
                                                                    key={star}
                                                                    className={`h-3.5 w-3.5 ${
                                                                        star <=
                                                                        player.rating
                                                                            ? "fill-amber-400 text-amber-400"
                                                                            : "text-muted-foreground/25"
                                                                    }`}
                                                                />
                                                            ),
                                                        )}
                                                        <span className="ml-1 text-xs text-muted-foreground font-mono">
                                                            ({player.rating}/5)
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-3 py-3">
                                                    {isReserve ? (
                                                        <Badge
                                                            variant="outline"
                                                            className="border-amber-500/40 bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300 text-xs"
                                                        >
                                                            <Sparkles className="mr-1 h-3 w-3" />
                                                            Reserve List
                                                        </Badge>
                                                    ) : (
                                                        <Badge
                                                            variant="secondary"
                                                            className="text-xs"
                                                        >
                                                            <UserCheck className="mr-1 h-3 w-3" />
                                                            Draft Pool
                                                        </Badge>
                                                    )}
                                                </td>
                                                {isMember && !isLocked && (
                                                    <td className="py-3 pl-3 pr-4 sm:pr-6 text-right">
                                                        <div className="flex items-center justify-end gap-1">
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() =>
                                                                    openEditModal(
                                                                        player,
                                                                    )
                                                                }
                                                                className="h-8 w-8 p-0 text-muted-foreground hover:text-foreground"
                                                                title="Edit Player"
                                                            >
                                                                <Pencil className="h-3.5 w-3.5" />
                                                                <span className="sr-only">
                                                                    Edit
                                                                </span>
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() =>
                                                                    setDeletingPlayer(
                                                                        player,
                                                                    )
                                                                }
                                                                className="h-8 w-8 p-0 text-muted-foreground hover:text-destructive"
                                                                title="Remove Player"
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                                <span className="sr-only">
                                                                    Remove
                                                                </span>
                                                            </Button>
                                                        </div>
                                                    </td>
                                                )}
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Add Player Dialog */}
            <Dialog open={addOpen} onOpenChange={setAddOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add New Player</DialogTitle>
                        <DialogDescription>
                            Enter the player's name and estimate their soccer skill
                            rating (1 to 5).
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleAdd} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="player-name">Player Name</Label>
                            <Input
                                id="player-name"
                                placeholder="e.g. Alex Morgan"
                                value={addForm.data.name}
                                onChange={(e) =>
                                    addForm.setData("name", e.target.value)
                                }
                                autoFocus
                            />
                            {addForm.errors.name && (
                                <p className="text-xs text-destructive">
                                    {addForm.errors.name}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="player-rating">
                                Rating (1–5 Stars)
                            </Label>
                            <Select
                                value={String(addForm.data.rating)}
                                onValueChange={(val) =>
                                    addForm.setData("rating", Number(val))
                                }
                            >
                                <SelectTrigger id="player-rating">
                                    <SelectValue placeholder="Select rating" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="5">
                                        5 Stars — Elite / Pro
                                    </SelectItem>
                                    <SelectItem value="4">
                                        4 Stars — Advanced
                                    </SelectItem>
                                    <SelectItem value="3">
                                        3 Stars — Intermediate / Average
                                    </SelectItem>
                                    <SelectItem value="2">
                                        2 Stars — Casual
                                    </SelectItem>
                                    <SelectItem value="1">
                                        1 Star — Beginner
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            {addForm.errors.rating && (
                                <p className="text-xs text-destructive">
                                    {addForm.errors.rating}
                                </p>
                            )}
                        </div>

                        {currentCount >= requiredSlots && (
                            <div className="rounded-md border border-amber-500/30 bg-amber-50/50 p-2.5 text-xs text-amber-800 dark:bg-amber-950/20 dark:text-amber-300">
                                <strong>Note:</strong> Required slots (
                                {requiredSlots}) are already full. This player
                                will be added to the <strong>Reserve List</strong>.
                            </div>
                        )}

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setAddOpen(false)}
                                disabled={addForm.processing}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={addForm.processing}
                            >
                                {addForm.processing
                                    ? "Adding..."
                                    : "Add Player"}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Edit Player Dialog */}
            <Dialog
                open={editingPlayer !== null}
                onOpenChange={(open) => !open && setEditingPlayer(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Player</DialogTitle>
                        <DialogDescription>
                            Update this player's name and skill rating.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleEdit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="edit-player-name">Player Name</Label>
                            <Input
                                id="edit-player-name"
                                value={editForm.data.name}
                                onChange={(e) =>
                                    editForm.setData("name", e.target.value)
                                }
                                autoFocus
                            />
                            {editForm.errors.name && (
                                <p className="text-xs text-destructive">
                                    {editForm.errors.name}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="edit-player-rating">
                                Rating (1–5 Stars)
                            </Label>
                            <Select
                                value={String(editForm.data.rating)}
                                onValueChange={(val) =>
                                    editForm.setData("rating", Number(val))
                                }
                            >
                                <SelectTrigger id="edit-player-rating">
                                    <SelectValue placeholder="Select rating" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="5">
                                        5 Stars — Elite / Pro
                                    </SelectItem>
                                    <SelectItem value="4">
                                        4 Stars — Advanced
                                    </SelectItem>
                                    <SelectItem value="3">
                                        3 Stars — Intermediate / Average
                                    </SelectItem>
                                    <SelectItem value="2">
                                        2 Stars — Casual
                                    </SelectItem>
                                    <SelectItem value="1">
                                        1 Star — Beginner
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            {editForm.errors.rating && (
                                <p className="text-xs text-destructive">
                                    {editForm.errors.rating}
                                </p>
                            )}
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setEditingPlayer(null)}
                                disabled={editForm.processing}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={editForm.processing}
                            >
                                {editForm.processing
                                    ? "Saving..."
                                    : "Save Changes"}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <Dialog
                open={deletingPlayer !== null}
                onOpenChange={(open) => !open && setDeletingPlayer(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Remove Player</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to remove{" "}
                            <strong className="text-foreground">
                                {deletingPlayer?.name}
                            </strong>{" "}
                            from this room?
                            {isFull && (
                                <span className="block mt-2 text-amber-600 dark:text-amber-400">
                                    If player count drops below {requiredSlots},
                                    the room status will return to "Waiting".
                                </span>
                            )}
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter className="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDeletingPlayer(null)}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={handleDelete}
                        >
                            Remove Player
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
