import type { Ref } from "react";
import { Crown, Star } from "lucide-react";
import type { Team } from "@/types/types";
import { captainLabel, sortTeamPlayers, teamLabel } from "@/types/team-labels";

interface TeamImageCardProps {
    teams: Team[];
    roomName: string;
    imageRef: Ref<HTMLDivElement>;
}

export function TeamImageCard({
    teams,
    roomName,
    imageRef,
}: TeamImageCardProps) {
    return (
        <div
            ref={imageRef}
            className="grid gap-4 sm:grid-cols-2 w-150 overflow-hidden rounded-2xl border border-neutral-200 bg-white text-neutral-900"
        >
            {teams.map((team, index) => {
                const players = sortTeamPlayers(team.players);

                return (
                    <div className="px-6 py-5" key={team.id}>
                        <div className="flex items-center justify-between rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3">
                            <span className="flex items-center gap-2 text-sm text-neutral-500">
                                <Crown
                                    className="size-4"
                                    style={{ color: team.color }}
                                />
                                Captain
                            </span>
                            <span className="text-sm font-semibold">
                                {captainLabel(team)}
                            </span>
                        </div>

                        <ul className="mt-4 space-y-2">
                            {players.map((player, index) => (
                                <li
                                    key={player.id}
                                    className="flex items-center justify-between rounded-lg border border-neutral-200 px-4 py-2.5"
                                >
                                    <span className="flex items-center gap-3 text-sm font-medium">
                                        <span
                                            className="flex size-6 items-center justify-center rounded-full text-xs font-bold text-white"
                                            style={{
                                                backgroundColor: team.color,
                                            }}
                                        >
                                            {index + 1}
                                        </span>
                                        {player.name}
                                    </span>
                                    <span className="flex items-center gap-1 text-sm text-amber-500">
                                        <Star className="size-3.5 fill-current" />
                                        {player.rating}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                );
            })}
        </div>
    );
}
