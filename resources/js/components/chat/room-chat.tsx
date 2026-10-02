import { useEffect, useRef } from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { MessageSquare, Send } from 'lucide-react';
import { store } from '@/routes/rooms/messages';
import type { ChatMessage } from '@/types/types';

interface RoomChatProps {
    roomId: number;
    messages: ChatMessage[];
    currentUserId: number;
}

function formatTime(timestamp: string): string {
    return new Date(timestamp).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function RoomChat({ roomId, messages, currentUserId }: RoomChatProps) {
    const bottomRef = useRef<HTMLDivElement>(null);
    const form = useForm({ body: '' });

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages.length]);

    function handleSend(e: React.FormEvent) {
        e.preventDefault();

        const body = form.data.body.trim();
        if (body === '') return;

        form.post(store.url({ room: roomId }), {
            onSuccess: () => form.reset(),
        });
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-xl font-bold">
                    <MessageSquare className="text-primary h-5 w-5" />
                    Chat
                </CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
                <div className="max-h-96 min-h-48 space-y-3 overflow-y-auto pr-1">
                    {messages.length === 0 ? (
                        <div className="flex h-48 flex-col items-center justify-center text-center">
                            <MessageSquare className="text-muted-foreground/40 mb-3 h-10 w-10" />
                            <p className="text-sm font-semibold">
                                No messages yet
                            </p>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Start the conversation with the other players.
                            </p>
                        </div>
                    ) : (
                        messages.map((message) => {
                            const isOwn = message.user.id === currentUserId;

                            return (
                                <div
                                    key={message.id}
                                    className={`flex ${isOwn ? 'justify-end' : 'justify-start'}`}
                                >
                                    <div
                                        className={`max-w-[80%] rounded-lg px-3 py-2 ${
                                            isOwn
                                                ? 'bg-primary text-primary-foreground'
                                                : 'bg-muted'
                                        }`}
                                    >
                                        <div className="flex items-baseline gap-2">
                                            <span className="text-xs font-semibold">
                                                {message.user.username}
                                            </span>
                                            <span
                                                className={`text-xs ${isOwn ? 'text-primary-foreground/70' : 'text-muted-foreground'}`}
                                            >
                                                {formatTime(message.created_at)}
                                            </span>
                                        </div>
                                        <p className="mt-0.5 text-sm break-words whitespace-pre-wrap">
                                            {message.body}
                                        </p>
                                    </div>
                                </div>
                            );
                        })
                    )}
                    <div ref={bottomRef} />
                </div>

                <form onSubmit={handleSend} className="flex gap-2">
                    <Input
                        value={form.data.body}
                        onChange={(e) => form.setData('body', e.target.value)}
                        placeholder="Type a message..."
                        maxLength={1000}
                        aria-label="Message"
                        autoComplete="off"
                    />
                    <Button
                        type="submit"
                        size="icon"
                        disabled={
                            form.processing || form.data.body.trim() === ''
                        }
                        title="Send message"
                    >
                        <Send className="h-4 w-4" />
                        <span className="sr-only">Send message</span>
                    </Button>
                </form>
                {form.errors.body && (
                    <p className="text-destructive text-xs">
                        {form.errors.body}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
