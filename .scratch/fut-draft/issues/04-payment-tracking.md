# 04: Payment Tracking

**What to build:** Users can mark a Player's payment as paid by uploading a reference image. Admins can mark paid without requiring an image. All room members can see payment status and view reference images for verification.

**Blocked by:** 03 (Player Management).

**Status:** completed

- [x] Create `payments` table migration (id, player_id FK cascade, marked_by_user_id FK, reference_image_path nullable, paid_at timestamp, timestamps)
- [x] Create `Payment` model with relationships (belongsTo player, belongsTo markedByUser)
- [x] Add `markPaid` action to `PlayerController`
- [x] Implement image upload validation (MIME types) and storage in `storage/app/payments/{room_id}/`
- [x] Admin can call markPaid endpoint without image (nullable path)
- [x] Display payment status in `PlayerList` component
- [x] Show reference image for verification
- [x] Feature tests: mark paid with image, mark paid as admin without image, non-admin cannot bypass image requirement
