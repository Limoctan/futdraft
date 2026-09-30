import type { TeamColorOption, TeamViewRoom } from "@/types/types";
import { TeamCard } from "./team-card";
import { Share2, Download } from "lucide-react";
import { Button } from "../ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { TeamImageCard } from "./team-image-card";
import { useRef, useState } from "react";
import html2canvas from "html2canvas-pro";
import { toast } from "sonner";

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
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "");

    return `${slug || "team"}-team.png`;
}

export function TeamView({
    room,
    roomName,
    teamColors,
    currentUserId,
    isAdmin,
}: TeamViewProps) {
    const [shareOpen, setShareOpen] = useState(false);
    const [generating, setGenerating] = useState(false);
    const imageRef = useRef<HTMLDivElement>(null);

    async function downloadImage() {
        const node = imageRef.current;

        if (!node) {
            return;
        }

        setGenerating(true);

        try {
            const canvas = await html2canvas(node, {
                backgroundColor: "#ffffff",
            });

            const link = document.createElement("a");
            link.download = imageFileName("Teams " + roomName);
            link.href = canvas.toDataURL("image/png");
            link.click();
        } catch (e) {
            console.error(e);
            toast.error("Could not generate the team image.");
        } finally {
            setGenerating(false);
        }
    }

    return (
        <div>
            <div className="grid gap-4 sm:grid-cols-2 mb-4">
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
            <Dialog open={shareOpen} onOpenChange={setShareOpen}>
                <DialogTrigger asChild>
                    <Button variant="outline" size="sm">
                        <Share2 className="mr-2 size-4" />
                        Share teams
                    </Button>
                </DialogTrigger>
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Teams {roomName}</DialogTitle>
                        <DialogDescription>
                            Preview your team and download it as a PNG to share
                            it.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="flex justify-center overflow-x-auto">
                        <TeamImageCard
                            teams={room.teams}
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
                            {generating ? "Generating..." : "Download PNG"}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
