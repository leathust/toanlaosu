# Project Management Dashboard

## Mục đích

Dashboard tại `docs/project-management-board.html` phản ánh trực quan trạng thái roadmap. Nguồn dữ liệu duy nhất của dashboard là `docs/assets/pm-board/pm-board.js`.

## Cấu trúc dữ liệu

- `phases`: Các giai đoạn của dự án.
- `tasks`: Task ID duy nhất, module, phase, priority, status, dependency, file, checklist và kết quả test.
- `decisions`: Quyết định nghiệp vụ/kiến trúc cần lưu vết.
- `risks`: Rủi ro chưa xử lý.
- `activities`: Nhật ký thay đổi thực tế.
- `testCenter`: Chỉ cập nhật sau khi có lệnh test thật.
- `migrations`: Migration đã chạy và các wave được lên kế hoạch.

## Quy tắc cập nhật

1. Roadmap và `tasks` phải có cùng ID/trạng thái.
2. Không đánh dấu `done` nếu checklist hoặc test bắt buộc chưa đạt.
3. Không tự nhập phần trăm; HTML tính từ task status.
4. Sau khi sửa dữ liệu, chạy:

```powershell
node --check docs/assets/pm-board/pm-board.js
```

## Khởi tạo

- Ngày: 2026-11-23.
- Roadmap đầu tiên: `docs/task-logs/1-database_schema.md`.
- Task hiện tại: `DB-001` đến `DB-010` ở trạng thái `done`; database roadmap đã sign-off.
- DB-008 đã chạy migration batch 12, schema AI knowledge/chat/citations đã được xác nhận và full test suite đạt 25 tests/74 assertions.
- DB-009 đã chạy migration batch 13, schema audit/system settings đã được xác nhận và full test suite đạt 28 tests/82 assertions.
- DB-010 đã review index/EXPLAIN, rehearsal toàn bộ migration trên SQLite in-memory và xác nhận schema sign-off.
- Roadmap giao diện: `docs/task-logs/2-ui-roadmap.md` với `UI-001..UI-015`.
- `UI-001..UI-015` đã hoàn tất checklist. Đã chạy browser E2E public/protected routes, desktop/mobile matrix và UAT proxy; UI roadmap sign-off hoàn tất. Full suite hiện đạt 61 tests/235 assertions; Playwright 4 tests passed, Vitest 1 test passed, Vite build và `node --check` đạt.
- Roadmap tích hợp ứng dụng: `docs/task-logs/3-mvp-application-integration-roadmap.md` với `APP-001..APP-012`.
- `APP-001..APP-005` đã hoàn tất; `APP-006..APP-010` ở `ready` theo dependency và `APP-011..APP-012` ở `backlog`. APP-003 đã chuẩn hóa account-state, workspace role và Policies chống IDOR; APP-004 đã hoàn thiện core enrollment boundary.
- Current execution status: APP-006 is `in_progress` with typed persisted scene runtime; APP-007..APP-010 are `ready` and APP-011..APP-012 remain `backlog`.
- Current execution status update: APP-007 is now `in_progress` with transactional exam autosave/submit and grading; APP-008..APP-010 remain `ready`.
- APP-007 update: AI exam context is blocked server-side and exam drafts persist locally for retry; full network-recovery browser E2E remains open.
- APP-007 completed: authenticated offline/reconnect E2E passed; APP-007 is now `done`.
- APP-008 started: lesson draft optimistic locking, revision history, publish authorization/audit và conflict response API đã được triển khai; typed Inertia editor, preview và UI conflict handling còn mở.
- APP-008 UI update: đã thêm typed Teacher LessonEditor, debounce autosave 600ms, save status, preview draft và conflict reload; full Pest 85/321, typecheck/build/Pint đạt. Compare/duplicate conflict và published immutability còn mở.
- APP-007 follow-up completed: E2eLearningSeeder now cleans prior exam attempts, answers và events trước khi seed; full Pest 85/321 và Playwright 6/6 passed ổn định, loại lỗi `409` do fixture stale `save_version`.
- APP-008 completed: conflict UI hỗ trợ reload/compare/duplicate draft, published lesson bất biến, revision history và publish workflow; full Pest 86/328, typecheck/build/Pint đạt.
- APP-009 started: đã triển khai admin filters/URL pagination, approval/status actions qua Form Request + Gate, reason/audit trail và test scope; typed system settings/rollback còn mở.
- APP-009 completed: typed system setting schema, validation key/value, transaction rollback và admin operations đã đạt; full Pest 90/345, Pint và node --check đạt.
- APP-010 started: provider abstraction/local provider, text retrieval + citations, retry, budget/usage retention và fake-provider tests đã đạt; timeout/circuit-breaker/stream dedupe còn mở.
- APP-010 completed: timeout/retry/circuit config, secret-safe provider settings, request idempotency chống duplicate assistant và fake timeout test đã đạt; full Pest 95/356.
- APP-011 started: retention cleanup, dry-run, queued retry job, daily scheduler và ops health check đã đạt; full Pest 97/372, recovery runbook còn mở.
- APP-011 completed: idempotent export job, structured logs, health check và recovery runbook đã hoàn thiện; full Pest 98/375, Pint và node --check đạt.
- APP-012 started: full Pest/Vitest/Playwright, typecheck/build, migration status và automated security/performance smoke đã đạt; manual UAT Student/Teacher/Admin còn mở.
- APP-012 UAT preparation: đã thêm và chạy `UatSeeder` tạo tài khoản local cho ba vai trò; chưa đánh dấu UAT pass trước khi có xác nhận người dùng.
