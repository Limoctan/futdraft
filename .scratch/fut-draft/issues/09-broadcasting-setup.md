# 09: Broadcasting Setup (Laravel Reverb)

**What to build:** Laravel Reverb is installed and configured for WebSocket-based real-time updates. Room presence channel (`room.{id}`) is authorized so only room members can subscribe. Broadcasting events are defined for room updates, player changes, draft picks, chat messages, team updates, and payment status.

**Blocked by:** 02 (Room Management).

**Status:** ready-for-agent

- [ ] Install Laravel Reverb (`composer require laravel/reverb` and `php artisan reverb:install`)
- [ ] Install frontend dependencies (`laravel-echo`, `pusher-js`, `@laravel/echo-react`)
- [ ] Configure Echo via `configureEcho()` in the React app
- [ ] Define room presence channel authorization in `routes/channels.php`
- [ ] Create broadcasting events: RoomUpdated, PlayerAdded/Updated/Removed, DraftPickMade, DraftStarted, DraftCompleted, NewMessage, TeamUpdated, CaptainAssigned, PaymentMarked
- [ ] Broadcast events on correct channels
- [ ] Feature tests: events broadcast on correct channels, channel authorization works
