# Operations recovery runbook

## Routine checks

- Run `php artisan ops:health-check` and confirm `status=ok`, queue depth and failed job count.
- Run `php artisan ops:cleanup-retention --dry-run` before the scheduled cleanup when investigating retention.
- The scheduler dispatches `RunRetentionCleanup` daily at 02:30; inspect `storage/logs/laravel.log` for `retention.cleanup.completed`.

## Failed jobs

1. Inspect the exception and payload with `php artisan queue:failed`.
2. Retry a safe, idempotent job with `php artisan queue:retry <id>`.
3. If the job is unsafe to retry, record the incident and delete only that failed job with `php artisan queue:forget <id>`.

## Stuck exams

- Verify the attempt owner, deadline and `save_version` before intervention.
- Do not edit answers directly; preserve the exam event trail and escalate to an Admin/CSKH workflow.
- If the attempt is genuinely stuck, record the reason in an audit event before applying a controlled status change.

## Provider outage

- Check `ops:health-check`, provider error logs and the AI circuit state.
- Keep exam AI disabled; allow tutor requests to return retryable `503` while the circuit is open.
- After recovery, retry idempotent requests with the same `request_id` to avoid duplicate assistant messages.

## Manual deletion via CSKH

- Users cannot self-delete retained audit, exam or AI/chat records.
- CSKH must verify identity, scope and legal retention requirements, create an audit record with the reason, then run a reviewed deletion operation.

## Authentication recovery

### Khóa tài khoản khẩn cấp

1. Admin mở User operations, xác minh yêu cầu và chuyển account sang `suspended` hoặc `disabled` kèm reason.
2. Kiểm tra audit event `user.status_updated` và xác nhận middleware `active.account` đã chặn workspace.
3. Không chỉnh trực tiếp password/token trong database; nếu nghi ngờ lộ credential, yêu cầu người dùng reset password qua flow chính thức.

### Reset thủ công có kiểm soát

- Dùng `php artisan db:seed --class=UatSeeder --force` chỉ trên local/UAT để phục hồi tài khoản test; không dùng seeder này trong production.
- Production reset phải bắt đầu bằng password broker hoặc invite mới, sau đó xác nhận session cũ đã bị revoke và audit event `password.reset`/`invite.accepted` không chứa token.
- Không gửi password tạm thời qua log, ticket công khai hoặc chat; chuyển người dùng qua link reset có expiry.

### Revoke session

- Người dùng vào Account settings để revoke một session hoặc toàn bộ session khác sau khi xác nhận password.
- Nếu người dùng mất quyền truy cập, Admin khóa account trước; CSKH ghi nhận identity verification và chuyển escalation cho Admin, không xóa raw session ID khỏi audit.
- Kiểm tra audit event `account.session_revoked` hoặc `account.sessions_revoked`, đồng thời không thu hồi nhầm session hiện tại.

### Mail outage

1. Kiểm tra mailer/queue health và log delivery; không retry hàng loạt nếu chưa xác định nguyên nhân.
2. Giữ thông báo forgot-password trung tính, không tiết lộ email có tồn tại hay không.
3. Sau khi mail hồi phục, gửi lại verification/invite/reset theo throttle; xác nhận token cũ vẫn single-use/expired và không ghi raw token vào log.

### Mail delivery rehearsal trên staging

Chỉ đánh dấu rehearsal đạt khi có staging URL, mail sandbox/provider credentials và một mailbox kiểm thử được duyệt.

1. Trên staging, đặt `MAIL_MAILER` về provider sandbox hoặc SMTP test; không dùng mailbox người dùng thật.
2. Tạo một tài khoản test có email trong mailbox đó, rồi kiểm tra lần lượt forgot-password, invite và verification resend.
3. Xác nhận email đến đúng mailbox, link dùng đúng host staging, expiry/throttle hoạt động và token không xuất hiện trong application log/audit/database plaintext.
4. Mở từng link một lần, xác nhận trạng thái thành công; thử dùng lại link và xác nhận bị từ chối an toàn.
5. Ghi lại thời gian, provider, mailbox test, kết quả và message IDs đã redacted trong sign-off; sau rehearsal chuyển staging về sandbox mặc định.

Hiện local đang dùng `MAIL_MAILER=log` và SMTP test `127.0.0.1:2525` chưa reachable, nên không được coi là staging rehearsal.
