import { Head, Link, router, useForm, usePage } from "@inertiajs/react";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
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
import { Plus, Users, Calendar, DollarSign, Copy, Check } from "lucide-react";
import { dashboard } from "@/routes";
import { store, join } from "@/routes/rooms";
import { show } from "@/routes/rooms";
import { formatCurrency } from "@/lib/utils";
import { currencies, statusColors, teamSizes } from "@/types/types";

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
    creator: { id: number; name: string; username: string };
    users: Array<{ id: number; pivot: { is_admin: boolean } }>;
}

export default function Dashboard({ rooms }: { rooms: Room[] }) {
    const [createOpen, setCreateOpen] = useState(false);
    const [joinOpen, setJoinOpen] = useState(false);
    const [copiedCode, setCopiedCode] = useState<string | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        date: "",
        team_size: "5",
        num_teams: "2",
        price_in_cents: "",
        currency: "USD",
    });

    const joinForm = useForm({ invite_code: "" });

    function handleCreate(e: React.FormEvent) {
        e.preventDefault();
        post(store.url(), {
            onSuccess: () => {
                reset();
                setCreateOpen(false);
            },
        });
    }

    function handleJoin(e: React.FormEvent) {
        e.preventDefault();
        joinForm.post(join.url(), {
            onSuccess: () => {
                joinForm.reset();
                setJoinOpen(false);
            },
        });
    }

    function copyCode(code: string) {
        navigator.clipboard.writeText(code);
        setCopiedCode(code);
        setTimeout(() => setCopiedCode(null), 2000);
    }

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">My Rooms</h1>
                    <div className="flex gap-2">
                        <Dialog open={joinOpen} onOpenChange={setJoinOpen}>
                            <DialogTrigger asChild>
                                <Button variant="outline">
                                    <Users className="mr-2 size-4" />
                                    Join Room
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <form onSubmit={handleJoin}>
                                    <DialogHeader>
                                        <DialogTitle>Join a Room</DialogTitle>
                                        <DialogDescription>
                                            Enter the 6-character invite code to
                                            join a room.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div className="mt-4">
                                        <Label htmlFor="invite_code">
                                            Invite Code
                                        </Label>
                                        <Input
                                            id="invite_code"
                                            value={joinForm.data.invite_code}
                                            onChange={(e) =>
                                                joinForm.setData(
                                                    "invite_code",
                                                    e.target.value.toUpperCase(),
                                                )
                                            }
                                            placeholder="ABC123"
                                            maxLength={6}
                                            className="mt-1 font-mono text-lg tracking-widest uppercase"
                                        />
                                        {joinForm.errors.invite_code && (
                                            <p className="mt-1 text-sm text-destructive">
                                                {joinForm.errors.invite_code}
                                            </p>
                                        )}
                                    </div>
                                    <DialogFooter className="mt-4">
                                        <Button
                                            type="submit"
                                            disabled={joinForm.processing}
                                        >
                                            {joinForm.processing
                                                ? "Joining..."
                                                : "Join Room"}
                                        </Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>

                        <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                            <DialogTrigger asChild>
                                <Button>
                                    <Plus className="mr-2 size-4" />
                                    Create Room
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="sm:max-w-md">
                                <form onSubmit={handleCreate}>
                                    <DialogHeader>
                                        <DialogTitle>Create a Room</DialogTitle>
                                        <DialogDescription>
                                            Set up a new soccer match room for
                                            your friends.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div className="mt-4 grid gap-4">
                                        <div>
                                            <Label htmlFor="name">
                                                Room Name
                                            </Label>
                                            <Input
                                                id="name"
                                                value={data.name}
                                                onChange={(e) =>
                                                    setData(
                                                        "name",
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Sunday Match"
                                                className="mt-1"
                                            />
                                            {errors.name && (
                                                <p className="mt-1 text-sm text-destructive">
                                                    {errors.name}
                                                </p>
                                            )}
                                        </div>
                                        <div>
                                            <Label htmlFor="date">Date</Label>
                                            <Input
                                                id="date"
                                                type="date"
                                                value={data.date}
                                                onChange={(e) =>
                                                    setData(
                                                        "date",
                                                        e.target.value,
                                                    )
                                                }
                                                className="mt-1"
                                            />
                                            {errors.date && (
                                                <p className="mt-1 text-sm text-destructive">
                                                    {errors.date}
                                                </p>
                                            )}
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <Label>Team Size</Label>
                                                <Select
                                                    value={data.team_size}
                                                    onValueChange={(v) =>
                                                        setData("team_size", v)
                                                    }
                                                >
                                                    <SelectTrigger className="mt-1">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {teamSizes.map(
                                                            (size) => (
                                                                <SelectItem
                                                                    key={size}
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
                                                {errors.team_size && (
                                                    <p className="mt-1 text-sm text-destructive">
                                                        {errors.team_size}
                                                    </p>
                                                )}
                                            </div>
                                            <div>
                                                <Label htmlFor="num_teams">
                                                    Number of Teams
                                                </Label>
                                                <Input
                                                    id="num_teams"
                                                    type="number"
                                                    min={2}
                                                    max={10}
                                                    value={data.num_teams}
                                                    onChange={(e) =>
                                                        setData(
                                                            "num_teams",
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="mt-1"
                                                />
                                                {errors.num_teams && (
                                                    <p className="mt-1 text-sm text-destructive">
                                                        {errors.num_teams}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <Label htmlFor="price">
                                                    Price (cents)
                                                </Label>
                                                <Input
                                                    id="price"
                                                    type="number"
                                                    min={0}
                                                    value={data.price_in_cents}
                                                    onChange={(e) =>
                                                        setData(
                                                            "price_in_cents",
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder="1000"
                                                    className="mt-1"
                                                />
                                                {errors.price_in_cents && (
                                                    <p className="mt-1 text-sm text-destructive">
                                                        {errors.price_in_cents}
                                                    </p>
                                                )}
                                            </div>
                                            <div>
                                                <Label>Currency</Label>
                                                <Select
                                                    value={data.currency}
                                                    onValueChange={(v) =>
                                                        setData("currency", v)
                                                    }
                                                >
                                                    <SelectTrigger className="mt-1">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {currencies.map(
                                                            (cur) => (
                                                                <SelectItem
                                                                    key={cur}
                                                                    value={cur}
                                                                >
                                                                    {cur}
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                                {errors.currency && (
                                                    <p className="mt-1 text-sm text-destructive">
                                                        {errors.currency}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    <DialogFooter className="mt-6">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing
                                                ? "Creating..."
                                                : "Create Room"}
                                        </Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                {rooms.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Users className="mb-4 size-12 text-muted-foreground" />
                            <p className="text-lg font-medium text-muted-foreground">
                                No rooms yet
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Create a room or join one with an invite code.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {rooms.map((room) => (
                            <Link key={room.id} href={show.url(room.id)}>
                                <Card className="transition-colors hover:bg-accent/50">
                                    <CardHeader className="pb-3">
                                        <div className="flex items-start justify-between">
                                            <CardTitle className="text-lg">
                                                {room.name}
                                            </CardTitle>
                                            <Badge
                                                variant={
                                                    statusColors[room.status] ??
                                                    "secondary"
                                                }
                                            >
                                                {room.status}
                                            </Badge>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Calendar className="size-4" />
                                            {new Date(
                                                room.date,
                                            ).toLocaleDateString()}
                                        </div>
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Users className="size-4" />
                                            {room.team_size}v{room.team_size} (
                                            {room.num_teams} teams)
                                        </div>
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <DollarSign className="size-4" />
                                            {formatCurrency(
                                                room.price_in_cents,
                                                room.currency,
                                            )}
                                        </div>
                                        <div className="flex items-center justify-between pt-2 border-t">
                                            <span className="text-xs text-muted-foreground">
                                                by {room.creator.username}
                                            </span>
                                            <button
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    e.stopPropagation();
                                                    copyCode(room.invite_code);
                                                }}
                                                className="flex items-center gap-1 text-xs font-mono text-muted-foreground hover:text-foreground"
                                            >
                                                {room.invite_code}
                                                {copiedCode ===
                                                room.invite_code ? (
                                                    <Check className="size-3 text-green-500" />
                                                ) : (
                                                    <Copy className="size-3" />
                                                )}
                                            </button>
                                        </div>
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: "Dashboard",
            href: dashboard(),
        },
    ],
};
