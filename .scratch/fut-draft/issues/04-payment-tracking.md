# 04: Payment Tracking

**What to build:** Users can mark a Player's payment as paid by uploading a reference image. Admins can mark paid without requiring an image. All room members can see payment status and view reference images for verification.

**Blocked by:** 03 (Player Management).

**Status:** ready-for-agent

- [ ] Create `payments` table migration (id, player_id FK cascade, marked_by_user_id FK, reference_image_path nullable, paid_at timestamp, timestamps)
- [ ] Create `Payment` model with relationships (belongsTo player, belongsTo markedByUser)
- [ ] Add `markPaid` action to `PlayerController`
- [ ] Implement image upload validation (MIME types) and storage in `storage/app/payments/{room_id}/`
- [ ] Admin can call markPaid endpoint without image (nullable path)
- [ ] Display payment status in `PlayerList` component
- [ ] Show reference image for verification
- [ ] Feature tests: mark paid with image, mark paid as admin without image, non-admin cannot bypass image requirement
