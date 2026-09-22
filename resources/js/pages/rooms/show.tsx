import { Head, router, useForm, usePage } from "@inertiajs/react";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import {
    Users,
    Calendar,
    DollarSign,
    Copy,
    Check,
    Settings,
    Shield,
    ShieldOff,
    UserMinus,
} from "lucide-react";
import { dashboard } from "@/routes";
import {
    show,
    update,
    assignAdmin,
    revokeAdmin,
    removeUser,
} from "@/routes/rooms";
import { formatCurrency } from "@/lib/utils";
import { currencies, statusColors, teamSizes, type Player } from "@/types/types";
import { PlayerList } from "@/components/players/player-list";

interface User {
    id: number;
    name: string;
    username: string;
    email: string;
    pivot: { is_admin: boolean };
}

interface Room {
    id: number;
    name: string;
    date: string;
    invite_code: string;
    team_size: number;
    num_teams: number;
    price_in_cents: number;
    currency: string;
    status: string;
    creator: User;
    users: User[];
    players: Player[];
}

export default function RoomShow({ room }: { room: Room }) {
    const { auth } = usePage().props as { auth: { user: { id: number } } };
    const [editOpen, setEditOpen] = useState(false);
    const [copiedCode, setCopiedCode] = useState(false);
    const [activeTab, setActiveTab] = useState("players");

    const isAdmin = room.users.some(
        (u) => u.id === auth.user.id && u.pivot.is_admin,
    );
    const isMember = room.users.some((u) => u.id === auth.user.id);
    const currentUser = room.users.find((u) => u.id === auth.user.id);

    const editForm = useForm({
        name: room.name,
        date: room.date.split("T")[0],
        team_size: String(room.team_size),
        num_teams: String(room.num_teams),
        price_in_cents: String(room.price_in_cents),
        currency: room.currency,
    });

    function handleEdit(e: React.FormEvent) {
        e.preventDefault();
        editForm.patch(update.url(room.id), {
            onSuccess: () => setEditOpen(false),
        });
    }

    function copyCode() {
        navigator.clipboard.writeText(room.invite_code);
        setCopiedCode(true);
        setTimeout(() => setCopiedCode(false), 2000);
    }

    function handleAssignAdmin(userId: number) {
        router.post(assignAdmin.url(room.id), { user_id: userId });
    }

    function handleRevokeAdmin(userId: number) {
        router.post(revokeAdmin.url(room.id), { user_id: userId });
    }

    function handleRemoveUser(userId: number) {
        router.delete(removeUser.url({ room: room.id, user: userId }));
    }

    return (
        <>
            <Head title={room.name} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex items-start justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold">{room.name}</h1>
                            <Badge
                                variant={
                                    statusColors[room.status] ?? "secondary"
                                }
                            >
                                {room.status}
                            </Badge>
                        </div>
                        <div className="mt-2 flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                            <span className="flex items-center gap-1">
                                <Calendar className="size-4" />
                                {new Date(room.date).toLocaleDateString()}
                            </span>
                            <span className="flex items-center gap-1">
                                <Users className="size-4" />
                                {room.team_size}v{room.team_size} (
                                {room.num_teams} teams)
                            </span>
                            <span className="flex items-center gap-1">
                                <DollarSign className="size-4" />
                                {formatCurrency(
                                    room.price_in_cents,
                                    room.currency,
                                )}
                            </span>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={copyCode}
                            className="flex items-center gap-2 rounded-md border px-3 py-2 font-mono text-sm hover:bg-accent"
                        >
                            {room.invite_code}
                            {copiedCode ? (
                                <Check className="size-4 text-green-500" />
                            ) : (
                                <Copy className="size-4" />
                            )}
                        </button>
                        {isAdmin && (
                            <Dialog open={editOpen} onOpenChange={setEditOpen}>
                                <DialogTrigger asChild>
                                    <Button variant="outline" size="icon">
                                        <Settings className="size-4" />
                                    </Button>
                                </DialogTrigger>
                                <DialogContent className="sm:max-w-md">
                                    <form onSubmit={handleEdit}>
                                        <DialogHeader>
                                            <DialogTitle>
                                                Edit Room Settings
                                            </DialogTitle>
                                            <DialogDescription>
                                                Update the room details before
                                                the draft starts.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div className="mt-4 grid gap-4">
                                            <div>
                                                <Label htmlFor="edit-name">
                                                    Room Name
                                                </Label>
                                                <Input
                                                    id="edit-name"
                                                    value={editForm.data.name}
                                                    onChange={(e) =>
                                                        editForm.setData(
                                                            "name",
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="mt-1"
                                                />
                                                {editForm.errors.name && (
                                                    <p className="mt-1 text-sm text-destructive">
                                                        {editForm.errors.name}
                                                    </p>
                                                )}
                                            </div>
                                            <div>
                                                <Label htmlFor="edit-date">
                                                    Date
                                                </Label>
                                                <Input
                                                    id="edit-date"
                                                    type="date"
                                                    value={editForm.data.date}
                                                    onChange={(e) =>
                                                        editForm.setData(
                                                            "date",
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="mt-1"
                                                />
                                                {editForm.errors.date && (
                                                    <p className="mt-1 text-sm text-destructive">
                                                        {editForm.errors.date}
                                                    </p>
                                                )}
                                            </div>
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <Label>Team Size</Label>
                                                    <Select
                                                        value={
                                                            editForm.data
                                                                .team_size
                                                        }
                                                        onValueChange={(v) =>
                                                            editForm.setData(
                                                                "team_size",
                                                                v,
                                                            )
                                                        }
                                                    >
                                                        <SelectTrigger className="mt-1">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {teamSizes.map(
                                                                (size) => (
                                                                    <SelectItem
                                                                        key={
                                                                            size
                                                                        }
                                                                        value={String(
                                                                            size,
                                                                        )}
                                                                    >
                                                                        {size}{" "}
                                                                        players
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div>
                                                    <Label htmlFor="edit-num_teams">
                                                        Number of Teams
                                                    </Label>
                                                    <Input
                                                        id="edit-num_teams"
                                                        type="number"
                                                        min={2}
                                                        max={10}
                                                        value={
                                                            editForm.data
                                                                .num_teams
                                                        }
                                                        onChange={(e) =>
                                                            editForm.setData(
                                                                "num_teams",
                                                                e.target.value,
                                                            )
                                                        }
                                                        className="mt-1"
                                                    />
                                                </div>
                                            </div>
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <Label htmlFor="edit-price">
                                                        Price (cents)
                                                    </Label>
                                                    <Input
                                                        id="edit-price"
                                                        type="number"
                                                        min={0}
                                                        value={
                                                            editForm.data
                                                                .price_in_cents
                                                        }
                                                        onChange={(e) =>
                                                            editForm.setData(
                                                                "price_in_cents",
                                                                e.target.value,
                                                            )
                                                        }
                                                        className="mt-1"
                                                    />
                                                </div>
                                                <div>
                                                    <Label>Currency</Label>
                                                    <Select
                                                        value={
                                                            editForm.data
                                                                .currency
                                                        }
                                                        onValueChange={(v) =>
                                                            editForm.setData(
                                                                "currency",
                                                                v,
                                                            )
                                                        }
                                                    >
                                                        <SelectTrigger className="mt-1">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {currencies.map(
                                                                (cur) => (
                                                                    <SelectItem
                                                                        key={
                                                                            cur
                                                                        }
                                                                        value={
                                                                            cur
                                                                        }
                                                                    >
                                                                        {cur}
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            </div>
                                        </div>
                                        <DialogFooter className="mt-6">
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
                        )}
                    </div>
                </div>

                <Tabs value={activeTab} onValueChange={setActiveTab}>
                    <TabsList>
                        <TabsTrigger value="players">Players</TabsTrigger>
                        <TabsTrigger value="draft">Draft</TabsTrigger>
                        <TabsTrigger value="teams">Teams</TabsTrigger>
                        <TabsTrigger value="chat">Chat</TabsTrigger>
                    </TabsList>

                    <TabsContent value="players" className="mt-4">
                        <PlayerList
                            room={room}
                            isMember={isMember}
                            isAdmin={isAdmin}
                        />
                    </TabsContent>

                    <TabsContent value="draft" className="mt-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Draft</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    Draft functionality will be implemented in a
                                    future ticket.
                                </p>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="teams" className="mt-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Teams</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    Team management will be implemented in a
                                    future ticket.
                                </p>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="chat" className="mt-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Chat</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    Chat functionality will be implemented in a
                                    future ticket.
                                </p>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            <span>Members ({room.users.length})</span>
                            {isAdmin && (
                                <Badge variant="outline">
                                    <Shield className="mr-1 size-3" />
                                    Admin
                                </Badge>
                            )}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {room.users.map((user) => (
                                <div
                                    key={user.id}
                                    className="flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div className="flex items-center gap-3">
                                        <div className="flex size-8 items-center justify-center rounded-full bg-muted text-sm font-medium">
                                            {user.username[0].toUpperCase()}
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium">
                                                {user.username}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {user.email}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {user.pivot.is_admin && (
                                            <Badge variant="secondary">
                                                <Shield className="mr-1 size-3" />
                                                Admin
                                            </Badge>
                                        )}
                                        {isAdmin &&
                                            user.id !== auth.user.id && (
                                                <div className="flex gap-1">
                                                    {user.pivot.is_admin ? (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8"
                                                            onClick={() =>
                                                                handleRevokeAdmin(
                                                                    user.id,
                                                                )
                                                            }
                                                            title="Revoke admin"
                                                        >
                                                            <ShieldOff className="size-4" />
                                                        </Button>
                                                    ) : (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8"
                                                            onClick={() =>
                                                                handleAssignAdmin(
                                                                    user.id,
                                                                )
                                                            }
                                                            title="Make admin"
                                                        >
                                                            <Shield className="size-4" />
                                                        </Button>
                                                    )}
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8 text-destructive"
                                                        onClick={() =>
                                                            handleRemoveUser(
                                                                user.id,
                                                            )
                                                        }
                                                        title="Remove from room"
                                                    >
                                                        <UserMinus className="size-4" />
                                                    </Button>
                                                </div>
                                            )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

RoomShow.layout = {
    breadcrumbs: [
        {
            title: "Dashboard",
            href: dashboard(),
        },
    ],
};
