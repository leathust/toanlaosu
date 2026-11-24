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
- `UI-001..UI-015` đã hoàn tất checklist. Đã chạy browser E2E public/protected routes, desktop/mobile matrix và UAT proxy; UI roadmap sign-off hoàn tất. Full suite đạt 60 tests/229 assertions; Playwright 4 tests passed, Vite build và `node --check` đạt.
