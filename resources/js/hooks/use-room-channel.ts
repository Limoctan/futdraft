import { router } from '@inertiajs/react';
import { useEchoPresence } from '@laravel/echo-react';

const roomEvents = [
    'CaptainAssigned',
    'DraftCompleted',
    'DraftPickMade',
    'DraftStarted',
    'NewMessage',
    'PaymentMarked',
    'PlayerAdded',
    'PlayerRemoved',
    'PlayerUpdated',
    'RoomUpdated',
    'TeamUpdated',
];

export function useRoomChannel(roomId: number): void {
    useEchoPresence(`room.${roomId}`, roomEvents, () => {
        router.reload({ only: ['room'] });
    });
}
