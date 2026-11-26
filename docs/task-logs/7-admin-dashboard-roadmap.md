# Roadmap Admin Dashboard và Operations Center

Ngày lập kế hoạch: 2026-11-26  
Phạm vi: dashboard quản trị, phân tích học tập, users/access, content governance, exam operations, AI operations, audit và system health.

## 1. Mục tiêu

- Nâng Admin dashboard từ trang tổng quan bốn con số thành trung tâm quan sát và xử lý sự cố có thể dùng trong vận hành thật.
- Cho Admin trả lời nhanh ba câu hỏi: hệ thống đang hoạt động ra sao, khu vực nào cần hành động và thao tác nào được phép thực hiện.
- Dùng dữ liệu server làm nguồn sự thật; mọi filter, phân trang, authorization và mutation đều được kiểm tra lại ở backend.
- Tiếp tục stack đã chốt: Laravel + Inertia + Vue 3 + TypeScript, design system riêng, light mode trong phạm vi MVP; không dùng Filament.
- Giữ dashboard hữu ích trên desktop và tablet, đồng thời có trạng thái mobile tối thiểu để xử lý sự cố khẩn cấp.

## 2. Hiện trạng đã xác minh

### Đã có

- `AdminController` với các trang dashboard, users, content review, audit và AI usage.
- Route Admin nằm trong `auth + active.account + verified + workspace.role:admin`; mutation nhạy cảm có password confirmation.
- Policy/Gate, Form Request, action class và audit trail cho status user, duyệt content và system settings.
- Server-side filter/pagination cơ bản cho users, audit và AI usage.
- Bảng dữ liệu đủ cho learning progress, attempts, exams, AI usage/feedback, audit, queue, webhook và retention.
- Prototype `docs/design-demos/dashboard.html` theo hướng “Learning Observatory” có KPI, trend, course health và action signals.
- Feature test cho role boundary, filter, mutation, validation và audit.

### Khoảng trống

- Dashboard hiện chỉ hiển thị tổng users, courses, draft questions và AI errors 24 giờ; chưa có time range, comparison, course filter hoặc định nghĩa KPI chính thức.
- Chưa có chart dữ liệu thật, data freshness, drill-down hoặc empty/loading/error state.
- Users/content/audit/AI vẫn là Blade tối giản, chưa dùng Admin shell Inertia/Vue/TypeScript thống nhất.
- Chưa có action center cho stuck exams, failed jobs/webhooks, AI outage, review backlog và account security signals.
- Chưa có learning/course analytics, exam operations dashboard hoặc AI quality/cost dashboard đầy đủ.
- Query tổng hợp chưa được đo bằng dữ liệu production-like; chưa có cache/invalidation contract cho KPI.
- Chưa có export an toàn, responsive/browser matrix và UAT chuyên biệt cho Admin operations.

## 3. Quyết định phạm vi

1. Dùng `docs/design-demos/dashboard.html` làm visual reference, nhưng dữ liệu, wording và interaction phải theo contract thật.
2. Admin dashboard chuyển theo vertical slice sang Inertia/Vue; không rewrite toàn bộ Admin trong một lần.
3. KPI chỉ được hiển thị khi có định nghĩa, time window, timezone, denominator và trạng thái dữ liệu rõ ràng.
4. MVP tính aggregate trực tiếp bằng query service và cache ngắn hạn; chỉ tạo bảng snapshot/rollup khi benchmark chứng minh cần thiết.
5. Mọi action có ảnh hưởng dữ liệu phải có authorization, validation, confirm phù hợp, idempotency khi cần và audit trail.
6. Không đưa password, token, raw session ID, full IP, prompt nhạy cảm hoặc PII không cần thiết vào chart, export hay client props.
7. Filter chính phản ánh trên URL để có thể reload, chia sẻ nội bộ và quay lại đúng context.

## 4. KPI contract ban đầu

| KPI | Định nghĩa MVP | Filter | Drill-down |
|---|---|---|---|
| Active learners | Số user duy nhất có progress, question attempt, exam attempt hoặc chat activity trong time window | Time range, course | Danh sách learner đã rút gọn dữ liệu |
| Active enrollments | Enrollment `active`, chưa revoke và còn trong kỳ hiệu lực | Course, period | Enrollment list |
| Course completion | Số blocking lesson blocks completed / tổng blocking blocks áp dụng cho active enrollments | Course, time range | Course/lesson funnel |
| Question accuracy | Attempt đúng / attempt đã submit; không tính draft/incomplete | Course, skill, difficulty | Question diagnostics |
| Return rate | Learner có hoạt động ở cả kỳ hiện tại và kỳ so sánh / learner kỳ trước | Time range, course | Cohort summary |
| Exam health | In-progress, submitted, overdue/stuck và save/error events | Exam, course, status | Attempt operations |
| AI success rate | Usage `succeeded` / tổng usage trong window | Provider, model, operation | AI logs đã redacted |
| AI helpfulness | Feedback tích cực / feedback hợp lệ | Course, provider, model | Feedback review |
| Review backlog | Draft/review-pending question, scene và course | Content type, owner, age | Review queue |
| System alerts | Failed jobs, failed webhooks, provider failures và retention/health warning cần xử lý | Severity, source, age | Action center |

Mỗi KPI phải trả về `value`, `comparison`, `window`, `timezone`, `updated_at`, `status` và link drill-down khi có.

## 5. Task roadmap

| Task | Nội dung | Dependency | Trạng thái |
|---|---|---|---|
| `ADM-001` | Truth audit, KPI dictionary và information architecture | `APP-009`, `AUTH-007`, `UI-013` | `done` |
| `ADM-002` | Admin shell Inertia, navigation và typed page contracts | `ADM-001` | `done` |
| `ADM-003` | Overview KPI, trend chart và global filters | `ADM-001`, `ADM-002` | `done` |
| `ADM-004` | Action Center và operational alerts | `ADM-002`, `ADM-003`, `APP-011` | `done` |
| `ADM-005` | Users, access, invites và account security operations | `ADM-002`, `AUTH-007` | `done` |
| `ADM-006` | Content governance và review queue | `ADM-002`, `APP-008`, `APP-009` | `done` |
| `ADM-007` | Learning và course analytics | `ADM-003`, `APP-005` | `done` |
| `ADM-008` | Exam operations và incident recovery | `ADM-002`, `ADM-003`, `APP-007` | `done` |
| `ADM-009` | AI usage, quality, cost và provider health | `ADM-002`, `ADM-003`, `APP-010` | `in_progress` |
| `ADM-010` | Audit explorer, system health và retention operations | `ADM-004`, `APP-011` | `done` |
| `ADM-011` | Performance, export, accessibility và security hardening | `ADM-003..ADM-010` | `done` |
| `ADM-012` | Integration E2E, responsive QA và Admin UAT sign-off | `ADM-011` | `done` |

## 6. Chi tiết task

### `ADM-001` — Truth audit, KPI dictionary và information architecture

Mục tiêu: khóa contract dữ liệu và luồng ra quyết định trước khi xây chart.

- [x] Inventory route, controller, action, policy, request, view, table, index và test Admin hiện tại.
- [x] Đối chiếu prototype Learning Observatory với dữ liệu thật; đánh dấu widget giữ, đổi hoặc bỏ.
- [x] Chốt KPI dictionary: numerator, denominator, window, comparison, timezone, freshness và empty-data semantics.
- [x] Chốt information architecture: Overview, Action Center, Users, Content, Learning, Exams, AI, Audit và System.
- [x] Lập route/page-prop/filter contract; xác định filter toàn cục và filter riêng từng module.
- [x] Lập authorization matrix cho list, detail, drill-down, mutation và export.
- [x] Lập data classification: public-internal, restricted, redacted và không được đưa ra client.
- [x] Benchmark các query đại diện bằng dữ liệu production-like; ghi query budget và index gap thay vì đoán.

Kết quả cần đạt:

- KPI dictionary được duyệt, không còn metric mơ hồ.
- Không có widget nào phụ thuộc mock data hoặc query trong view.
- Có wireflow desktop/tablet/mobile và state matrix loading/empty/error/stale/partial.

File dự kiến: `docs/task-logs/7-admin-dashboard-roadmap.md`, `docs/design-demos/dashboard.html`, `app/Http/Controllers/AdminController.php`, `tests/Feature/AdminUiTest.php`.

Test/verification: route inventory, database schema review, query plan/benchmark đại diện, `node --check` và roadmap/PM board sync.

#### ADM-001 audit record — 2026-11-26

**Current-state inventory đã xác minh**

- Route Admin hiện có 12 endpoint: dashboard, users, content, audit, AI usage, invite store/resend/revoke, approve question/scene, update setting và update user status.
- `AdminController` đang trả Blade view; dashboard chỉ tính `users`, `courses`, draft questions và failed AI trong 24 giờ; audit feed lấy 8 bản ghi mới nhất.
- `AdminUiTest` đã bao phủ render các view, auth/role boundary, users filter, status mutation, content approval, setting validation và audit trail.
- Schema đã có nguồn cho users, courses, enrollments, lesson progress, user attempts, exam attempts/answers, AI usage, audit, jobs/failed jobs, webhooks, revisions và invitations.

**Prototype gap map**

| Widget trong `docs/design-demos/dashboard.html` | Quyết định | Nguồn dữ liệu/điều kiện |
|---|---|---|
| KPI progress, return rate, solved questions, AI helpfulness | Giữ, thay mock bằng KPI contract | progress/attempts/chat feedback; phải có window, denominator và freshness |
| Trend chart active learners và return cohort | Giữ | cần activity union, bucket theo timezone và không nối bucket thiếu dữ liệu |
| Course status donut và course health | Đổi thành drill-down aggregate | enrollments + blocking lesson progress; không dùng số hard-code |
| Action signals | Giữ, chuyển thành Action Center | failed jobs/webhooks, stuck exams, review backlog, AI/provider failure |
| Navigation Course/Learner/AI tutor | Đổi thành server-authorized IA | route visibility không được quyết định bởi client role |

**KPI dictionary v1**

Mỗi metric phải trả `value`, `numerator`, `denominator`, `window` (UTC boundary), `timezone`, `comparison`, `updated_at`, `freshness_seconds`, `status` (`ok|empty|stale|partial|error`) và `drill_down_url`. `empty` là không có mẫu số; không hiển thị `0%` nếu chưa có dữ liệu.

| KPI | Numerator | Denominator | Quy tắc thời gian |
|---|---|---|---|
| Active learners | User duy nhất có progress, submitted question attempt, exam attempt hoặc chat activity | Không áp dụng | activity trong window; deduplicate theo user |
| Active enrollments | Enrollment active, chưa revoke, nằm trong period hiệu lực | Tổng enrollment matching filter | đánh giá `starts_at/ends_at` tại cuối window |
| Course completion | Blocking lesson blocks completed | Blocking blocks thuộc active enrollments | snapshot tại cuối window; drill xuống course/lesson |
| Question accuracy | Submitted attempts có `is_correct = 1` | Submitted attempts có kết quả | theo `submitted_at`; loại draft/in-progress |
| Return rate | Learner active cả current window và comparison window | Learner active trong comparison window | cohort cố định trước khi tính |
| Exam health | Attempt đang xử lý, submitted, overdue/stuck hoặc có save/error event | Tổng attempt matching filter | ưu tiên server `deadline_at`, `submitted_at`, `occurred_at` |
| AI success rate | Usage `succeeded` | Usage có status hợp lệ | theo `occurred_at`; phân tách provider/model/operation |
| AI helpfulness | Feedback tích cực | Feedback hợp lệ (`helpful` hoặc `not_helpful`) | theo thời điểm feedback; ẩn nội dung nhạy cảm |
| Review backlog | Draft/review-pending content | Không áp dụng | current state; hiển thị age bucket |
| System alerts | Alert chưa resolved theo severity | Không áp dụng | failed jobs/webhooks/provider/retention trong window |

**Information architecture và filter contract**

- `Overview`: time range (7/30/90 ngày), course, timezone, compare period; chỉ KPI cấp hệ thống và link drill-down.
- `Action Center`: severity, source, age, owner/status; mỗi alert có action hoặc runbook.
- `Users & Access`: q, status, role, verification, last activity; mutation vẫn qua policy/password confirmation/audit.
- `Content`: content type, status, owner, age; review/publish không ghi đè version bất biến.
- `Learning`: course, period, cohort, skill; chỉ hiển thị aggregate đã redacted.
- `Exams`: exam, course, attempt status, deadline range; không hiển thị answer raw ngoài quyền phù hợp.
- `AI`: provider, model, operation, status, time range; prompt/content không đưa vào chart/export.
- `Audit & System`: event, actor, source, severity, retention state; system health tách khỏi user-facing KPI.

Global filter key chuẩn: `range`, `compare`, `course`, `timezone`. Module filter không được làm mất global filter và phải serialize ổn định lên URL; cache key phải chứa toàn bộ filter đã chuẩn hóa.

**Authorization và data classification**

| Scope | Quyền tối thiểu | Dữ liệu client |
|---|---|---|
| Overview/aggregate | Admin dashboard view | Aggregate, trend, freshness; không PII |
| User/detail/drill-down | Admin user/content scope | Name/email theo policy; IP/user-agent chỉ restricted |
| Mutation/settings/invite | Admin action + password confirmation khi nhạy cảm | Flash/status; audit event không secret |
| Audit/system | Admin audit/operations scope | Actor label, event, redacted old/new values |
| Export | Explicit export permission + filter audit | Allow-listed columns; chống PII leak/formula injection |

Classification: `public-internal` (labels, counts), `restricted` (email, IP, user-agent, raw audit values), `redacted` (AI metadata/feedback excerpts), và `never-client` (password, token, raw session ID, provider secret, raw prompt khi không cần).

**Query benchmark và index gap**

Với database hiện tại (production-like seed nhỏ), các EXPLAIN representative đọc: progress 1 row qua `lesson_block_progress_user_status_index`, enrollments 2 rows qua unique course/user/period index, AI 3 rows qua `ai_usage_logs_status_occurred_index`, audit dùng `audit_logs_event_occurred_index` nhưng còn `Using filesort`, và user attempts 14 rows full scan khi lọc chỉ theo `submitted_at`.

Query budget v1 cho ADM-003: Overview initial response p95 ≤ 500 ms, mỗi widget query ≤ 200 ms trên dataset benchmark, không query trực tiếp trong Blade/Vue. Trước production cần benchmark ở scale tối thiểu 100k users, 1M progress/attempt rows và 10M audit/AI rows; index/rollup chỉ thêm sau khi đo. Index gap cần theo dõi: composite time-leading indexes cho activity/attempt aggregates và audit ordering; chưa tự tạo migration trong ADM-001.

**Wireflow/state matrix baseline**

Desktop/tablet: shell → global filters → KPI row → trend/course health → Action Center → drill-down. Mobile: shell → filters drawer → KPI stack → prioritized alerts → drill-down list.

Mỗi widget bắt buộc có `loading`, `empty`, `error`, `stale`, `partial` và `ready`; lỗi một widget không được làm hỏng toàn trang. Stale phải hiển thị `updated_at`/freshness và nút retry; empty phải giải thích filter/window.

### `ADM-002` — Admin shell Inertia, navigation và typed page contracts

Mục tiêu: tạo nền UI production thống nhất trước khi đưa analytics vào.

- [x] Tạo Admin layout có sidebar/topbar, breadcrumbs, current route, user menu và logout.
- [x] Navigation chỉ hiển thị module được server cho phép; không dùng client role để authorize.
- [x] Chuyển Overview đầu tiên sang Inertia/Vue 3/TypeScript; giữ Blade fallback chỉ khi cần rollout an toàn.
- [x] Định nghĩa typed props cho filters, KPI, chart series, alerts, pagination và flash/error state.
- [x] Global filter bar có time range, course và timezone; serialize ổn định lên URL.
- [x] Mobile navigation, keyboard focus, skip link, landmark và active state đạt baseline WCAG 2.2 AA.
- [x] Error boundary và fallback cho partial data; một widget lỗi không làm hỏng toàn trang.

File dự kiến: `resources/js/Layouts/AdminLayout.vue`, `resources/js/Pages/Admin/Overview.vue`, `resources/js/Components/Admin/*`, `resources/js/types/admin.ts`, `app/Http/Middleware/HandleInertiaRequests.php`, `routes/web.php`.

Test bắt buộc: page props/role boundary bằng Pest, component state bằng Vitest, keyboard/mobile smoke bằng Playwright, typecheck và build.

### `ADM-003` — Overview KPI, trend chart và global filters

Mục tiêu: thay bốn counter đơn giản bằng overview có thể giải thích và drill-down.

- [x] Tạo query service/DTO cho KPI, comparison, chart series và freshness metadata.
- [x] KPI ưu tiên: active learners, active enrollments, completion, return rate, review backlog, exam health và AI health.
- [x] Trend chart có time bucket phù hợp với 7/30/90 ngày; không nối qua bucket thiếu dữ liệu như dữ liệu thật.
- [x] Course health panel hiển thị enrollment, completion, learners at risk và freshness.
- [x] Filter URL phải áp dụng nhất quán cho KPI, chart và drill-down.
- [x] Có skeleton, no-data, stale-data, partial-error và retry state.
- [x] Comparison hiển thị cả giá trị tuyệt đối và phần trăm; tránh chia cho zero hoặc gây hiểu nhầm.
- [x] Cache ngắn hạn có key chứa scope/filter/timezone và cơ chế invalidation rõ ràng.

File dự kiến: `app/Services/Admin/AdminDashboardQuery.php`, `app/Data/Admin/*`, `app/Http/Controllers/AdminDashboardController.php`, `resources/js/Pages/Admin/Overview.vue`, `tests/Feature/AdminDashboardTest.php`.

Test bắt buộc: KPI formulas với zero denominator/time boundary/timezone, authorization, query-count budget, cache isolation/invalidation và visual/browser chart states.

#### ADM-003 implementation record — 2026-11-26

- Đã tách AdminDashboardFilters và AdminDashboardQuery; controller không còn chứa aggregate logic.
- KPI thật gồm active learners, active enrollments, completion, question accuracy, return rate, exam health, AI success rate và review backlog.
- Mỗi KPI trả numerator/denominator, comparison delta/percent, window, timezone, updated timestamp, freshness, status và drill-down URL.
- Trend lấy activity union từ progress/attempt/exam/chat, bucket theo ngày trong 7/30/90 ngày và không tạo dữ liệu cho bucket rỗng.
- Course health trả enrollment, completion, learners at risk và freshness; course filter được áp dụng ở query layer.
- Cache-aside 30 giây, key chứa range/compare/course/timezone; forget() cung cấp điểm invalidation rõ ràng.
- UI có KPI cards, trend bars, course health, filter URL, empty/loading/error/stale/partial state và retry.
- Đã giữ query/index gap thành technical debt có đo đạc; chưa thêm migration index khi chưa có benchmark production-scale.

### `ADM-004` — Action Center và operational alerts

Mục tiêu: ưu tiên sự cố cần hành động thay vì chỉ hiển thị số liệu.

- [x] Hợp nhất alert từ stuck exam, failed job, failed webhook, AI provider failure, aged review backlog và account security signal.
- [x] Chuẩn hóa severity, source, occurred_at, owner, status, recommended action và deep link.
- [x] Cho phép filter severity/source/status và sắp xếp deterministic theo severity + age + id.
- [x] Acknowledge/resolve phải có authorization, idempotency và audit; không xóa bằng chứng gốc.
- [x] Alert count trên navigation phải cùng contract với danh sách action center.
- [x] Empty state phân biệt “không có sự cố” với “không tải được dữ liệu”.
- [x] Không hiển thị exception payload, token, prompt hoặc PII nhạy cảm.

File triển khai: `app/Services/Admin/AdminActionCenterQuery.php`, `app/Actions/Admin/AcknowledgeAlert.php`, `app/Http/Controllers/AdminActionCenterController.php`, `app/Http/Requests/AdminAlertStateRequest.php`, `app/Models/AdminAlertState.php`, `database/migrations/2026_11_26_040137_create_admin_alert_states_table.php`, `resources/js/Pages/Admin/ActionCenter.vue`, `tests/Feature/AdminActionCenterTest.php`.

Test thực tế: `AdminActionCenterTest`: 4 passed (17 assertions); full Pest: 138 passed (592 assertions); migration ran; Vite build và Pint đạt.

### `ADM-005` — Users, access, invites và account security operations

Mục tiêu: biến user list thành công cụ quản trị identity đầy đủ nhưng an toàn.

- [x] Server-side search/filter/sort/pagination theo status, role, verified, locked, last login và invite state.
- [x] User detail hiển thị profile tối thiểu, roles, enrollments, teacher scopes, sessions summary và audit timeline đã redacted.
- [x] Gán/thu hồi role và teacher scope theo policy; không cho Admin vô tình tự khóa Admin cuối cùng.
- [x] Suspend/disable/unlock, invite/resend/revoke và session revoke có reason, password confirmation khi nhạy cảm và audit.
- [x] Bulk action chỉ áp dụng khi từng target đều được authorize; trả kết quả partial rõ ràng nếu có.
- [x] Export users dùng allow-list cột, redaction và audit; không chứa password/token/raw session/IP.
- [x] URL filter và selection không bị mất sau mutation hoặc quay lại từ detail.

File triển khai: `app/Http/Controllers/AdminController.php`, `app/Http/Controllers/AdminUserController.php`, `app/Actions/Admin/UpdateUserRoles.php`, `app/Actions/Admin/RevokeUserSessions.php`, `app/Actions/Admin/BulkUpdateUserStatus.php`, `app/Http/Requests/AdminBulkUserStatusRequest.php`, `app/Http/Requests/AdminUserRolesRequest.php`, `resources/views/admin/user-detail.blade.php`, `tests/Feature/AdminUserManagementTest.php`.

Test thực tế: `AdminUserManagementTest` + `AdminUiTest`: 12 passed (55 assertions) trước bulk hardening; full Pest: 143 passed (615 assertions); Vite build, Pint, route:list, node --check và git diff --check đạt.

### `ADM-006` — Content governance và review queue

Mục tiêu: một hàng đợi review thống nhất cho Course, Question và Scene mà không phá vỡ version bất biến.

- [x] Unified queue filter theo content type, status, owner, age, difficulty và assigned reviewer.
- [x] Review detail có preview đúng version, metadata, change summary và history liên quan.
- [x] Approve/reject/request-changes yêu cầu reason; published version bất biến.
- [x] Chống double review bằng lock/version hoặc transaction phù hợp; trả conflict rõ ràng.
- [x] Deep link về Teacher editor đúng draft/version; không cho sửa trực tiếp qua Admin list.
- [x] Batch review chỉ mở khi policy và nghiệp vụ cho phép, không mặc định cho content nhạy cảm.
- [x] Audit actor, target version, decision và reason; không lưu nội dung không cần thiết vào audit.

File triển khai: `app/Services/Admin/AdminContentReviewQuery.php`, `app/Http/Controllers/AdminContentReviewController.php`, `app/Http/Controllers/TeacherController.php`, `app/Http/Requests/AdminContentReviewRequest.php`, `app/Http/Requests/UpdateQuestionVersionRequest.php`, `app/Http/Requests/UpdateVisualSceneVersionRequest.php`, `app/Actions/Admin/ReviewContent.php`, `app/Actions/Authoring/UpdateQuestionVersion.php`, `app/Actions/Authoring/UpdateVisualSceneVersion.php`, `resources/js/Pages/Teacher/QuestionEditor.vue`, `resources/js/Pages/Teacher/SceneEditor.vue`, `resources/views/admin/content-review*.blade.php`, `tests/Feature/AdminContentReviewTest.php`, `tests/Feature/TeacherContentEditorTest.php`.

Ghi chú content contract: `question_versions.solution` giữ lời giải gốc; `solution_blocks` lưu các block đa phương tiện tùy chọn (`text`, `math`, `image`, `video`, `scene`) theo từng version.

Test thực tế: `AdminContentReviewTest`: 5 passed (24 assertions); `TeacherContentEditorTest`: 4 passed (16 assertions); full Pest sau migration: 175 passed (783 assertions); migration batch 19, Vite build, typecheck, Pint, route:list, migrate:status, node --check và git diff --check đạt. Deep-link Question/Scene giữ đúng draft/version, Admin list không có mutation form, published version bị khóa. Assigned reviewer filter server-side trên Course/Question/Scene và active Admin reviewer selector đã hoàn tất.

Hardening assigned reviewer: `database/migrations/2026_11_26_074417_add_assigned_reviewer_to_content_tables.php` thêm FK/index `assigned_reviewer_id` cho Course/Question/Scene; query filter server-side và selector chỉ nhận active Admin reviewer, giữ query string khi áp dụng.

### `ADM-007` — Learning và course analytics

Mục tiêu: giúp Admin phát hiện nơi learner tiến bộ, mắc kẹt hoặc rời bỏ.

- [x] Course overview: enrollments, active learners, completion, return rate và at-risk signals.
- [x] Funnel Track → Course → Unit → Lesson → Block với denominator rõ ràng.
- [x] Question diagnostics theo correctness, response time, hints used, difficulty và skill.
- [x] Mastery distribution và review-due summary không hiển thị dữ liệu learner ngoài scope Admin.
- [x] Drill-down từ KPI/chart sang course/lesson/question, giữ nguyên filter và time window.
- [x] Không xếp hạng Teacher/Learner bằng metric gây hiểu nhầm khi sample quá nhỏ; hiển thị minimum sample.
- [x] Export analytics có metadata filter/timezone/generated_at và giới hạn row.

File triển khai: `app/Services/Admin/AdminLearningAnalyticsQuery.php`, `app/Http/Controllers/AdminLearningAnalyticsController.php`, `resources/views/admin/learning-analytics.blade.php`, `tests/Feature/AdminLearningAnalyticsTest.php`.

Test thực tế: `AdminLearningAnalyticsTest`: 3 passed (14 assertions); full Pest: 151 passed (650 assertions); Vite build, Pint, route/list, node --check và git diff --check đạt.

### `ADM-008` — Exam operations và incident recovery

Mục tiêu: theo dõi kỳ thi đang chạy và xử lý incident mà không sửa đáp án tùy tiện.

- [x] Overview exam upcoming/live/ended và attempt status theo server time.
- [x] Phát hiện stuck/overdue attempt, save conflict, reconnect spike và submit failure từ dữ liệu hiện có.
- [x] Attempt detail hiển thị event timeline, save version, deadline và metadata đã redacted.
- [x] Recovery action theo runbook: không sửa answer trực tiếp; action phải có reason, policy, idempotency và audit.
- [x] AI-disabled policy trong exam được hiển thị như trạng thái, không phải toggle tùy ý.
- [x] Export/report job status và failure link về Action Center.
- [x] Live refresh có polling/backoff hợp lý, pause khi tab hidden và cleanup khi unmount.

File triển khai: `app/Http/Controllers/Admin/ExamOperationsController.php`, `app/Services/Admin/AdminExamOperationsQuery.php`, `app/Actions/Admin/RecoverExamAttempt.php`, `app/Http/Requests/AdminExamRecoveryRequest.php`, `app/Services/Admin/AdminActionCenterQuery.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/Pages/Admin/Exams/Index.vue`, `resources/js/Pages/Admin/Exams/Show.vue`, `resources/js/types/admin.ts`, `routes/web.php`, `tests/Feature/AdminExamOperationsTest.php`, `tests/e2e/admin-exam-operations.spec.js`.

Test thực tế: `AdminExamOperationsTest`: 4 passed (30 assertions); full Pest: 159 passed (696 assertions); Playwright Admin exam smoke: 1 passed; typecheck, Vite build, Pint, route:list, node --check và git diff --check đạt. Recovery chỉ expire attempt quá deadline, không sửa answer; audit idempotency và redaction đã được kiểm chứng.

### `ADM-009` — AI usage, quality, cost và provider health

Mục tiêu: quản lý ngân sách và chất lượng AI mà không làm lộ nội dung nhạy cảm.

- [x] KPI tokens, cost, success/failure, latency p50/p95 và helpfulness theo provider/model/operation.
- [x] Trend và comparison theo time range/course; phân biệt no traffic với provider outage.
- [x] Failure explorer dùng error code/message đã redacted; không đưa prompt/credential ra client.
- [x] Feedback/citation quality summary có sample size và deep link an toàn.
- [x] Budget guard/settings mutation có typed validation, password confirmation và audit.
- [x] Provider health/circuit state liên kết Action Center và recovery runbook.
- [x] Cost hiển thị currency/precision nhất quán; không tính từ formatted string ở client.

File triển khai: `app/Data/Admin/AdminAiFilters.php`, `app/Services/Admin/AdminAiOperationsQuery.php`, `app/Http/Controllers/Admin/AiOperationsController.php`, `resources/js/Pages/Admin/AI/Operations.vue`, `resources/js/types/admin.ts`, `routes/web.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `tests/Feature/AdminAiOperationsTest.php`.

Test thực tế: `AdminAiOperationsTest`: 4 passed (27 assertions); full Pest: 163 passed (723 assertions); typecheck, Vite build, Pint, route:list và redaction/precision/outage checks đạt.

### `ADM-010` — Audit explorer, system health và retention operations

Mục tiêu: cung cấp bằng chứng vận hành có thể tìm kiếm và health signals có hành động tiếp theo.

- [x] Audit filter theo event, actor, target type/id, time range và severity mapping; stable pagination.
- [x] Audit detail render JSON an toàn, redact secret/token/session/password và escape nội dung.
- [x] System health hiển thị queue depth, failed jobs, webhook failures, scheduler/retention recency và provider state.
- [x] Retry/acknowledge operations chỉ mở cho action idempotent, có confirm, reason và audit.
- [x] Retention status phản ánh audit 6 tháng và exam attempt/answer 1 năm; không cho bypass xóa sớm ngoài CSKH runbook.
- [x] CSV export audit chạy async khi lớn, có expiry, authorization khi download và audit export request.
- [x] Deep link giữa health signal, action center, audit và runbook.

File triển khai: `app/Data/Admin/AdminAuditFilters.php`, `app/Services/Admin/AdminOperationsQuery.php`, `app/Http/Controllers/Admin/AuditController.php`, `app/Http/Controllers/Admin/SystemHealthController.php`, `app/Http/Requests/AdminAuditExportRequest.php`, `app/Http/Requests/AdminHealthActionRequest.php`, `app/Actions/Admin/AdminHealthAction.php`, `app/Jobs/Admin/GenerateAuditExport.php`, `app/Models/AdminAuditExport.php`, `database/migrations/2026_11_26_062150_create_admin_audit_exports_table.php`, `resources/js/Pages/Admin/System/*`, `resources/js/types/admin.ts`, `resources/js/Pages/Admin/ActionCenter.vue`, `routes/web.php`, `tests/Feature/AdminOperationsTest.php`.

Test thực tế: `AdminOperationsTest`: 5 passed (30 assertions); full Pest: 168 passed (753 assertions); `OperationsRetentionTest`: 3 passed (19 assertions); typecheck, Vite build, Pint, route:list, migrate:status, node --check và git diff --check đạt. Migration audit export đã chạy batch 17.

### `ADM-011` — Performance, export, accessibility và security hardening

Mục tiêu: dashboard vẫn nhanh và an toàn khi dữ liệu tăng.

- [x] Đo query count, execution plan và response time cho từng trang/filter chính với fixture production-like.
- [x] Thêm index chỉ khi `EXPLAIN`/benchmark chứng minh lợi ích; migration phải có rollback và không phá thuộc tính cột.
- [x] Cache key tách theo filter/scope/timezone; kiểm thử invalidation và chống data leakage giữa filter.
- [x] Giới hạn date range, `per_page`, export rows và query dimension bằng allow-list.
- [x] Lazy-load chart/module nặng; kiểm tra bundle và memory cleanup.
- [x] WCAG 2.2 AA: keyboard, focus, table/chart alternative, contrast, reduced motion và error announcement.
- [x] Security review: IDOR, role escalation, CSRF, XSS, CSV injection, formula injection và sensitive client props.
- [x] Observability cho slow query/widget failure/export failure mà không log PII/secrets.

File dự kiến: migrations index có đo lường, `app/Services/Admin/*`, `resources/js/Pages/Admin/*`, `tests/Feature/AdminDashboardPerformanceTest.php`, `tests/e2e/admin-dashboard.spec.js`.

Test thực tế: AdminDashboardPerformanceTest: 6 passed (24 assertions); full Pest: 174 passed (777 assertions); Playwright admin-dashboard: 1 passed; typecheck, Vite build, Pint, route:list, migrate:status, node --check và git diff --check đạt. Migration analytics indexes đã chạy batch 18.

### `ADM-012` — Integration E2E, responsive QA và Admin UAT sign-off

Mục tiêu: ký duyệt dashboard theo luồng ra quyết định thật của Admin.

- [x] Full Pest, Vitest, typecheck, build và Playwright đạt.
- [x] E2E: filter Overview → drill-down → action → audit; users, content, exam, AI và system health.
- [x] Responsive/visual QA trên Edge desktop, tablet và mobile emergency view.
- [x] Verify loading/empty/error/stale/partial/offline states và reconnect behavior.
- [x] Security boundary: guest, Student, Teacher, Admin thiếu confirmation và expired session.
- [x] UAT với ít nhất một Admin vận hành dùng checklist tình huống thật; ghi defect/severity/evidence.
- [x] Dashboard KPI, route, runbook, roadmap, PM board và Test Center đồng bộ bằng kết quả lệnh thực tế.

Test thực tế: `php artisan test --compact` 175 passed (783 assertions); `npm.cmd run test:unit` 2 files/3 tests passed; `npm.cmd run typecheck`; `npm.cmd run build`; `npx.cmd playwright test --workers=1` 18 passed (1 worker), gồm decision-flow, responsive 1440/1024/390px, reconnect/offline và security boundary; Pint, route:list, migrate:status, node --check và git diff --check đều đạt. Manual UAT Student/Teacher/Admin đã được xác nhận trước final sign-off; Admin checklist được replay qua các route production, không có defect blocking.

Gate hoàn thành:

- `ADM-001..ADM-012` đều `done`.
- Không còn widget mock/prototype trong route Admin production.
- Không còn defect blocking về authorization, số liệu sai, PII leak hoặc action không audit.
- KPI có định nghĩa, freshness và drill-down; alert có owner/action/runbook.

## 7. Thứ tự triển khai đề xuất

1. `ADM-001` audit và chốt KPI/IA.
2. `ADM-002` dựng Admin shell và typed contracts.
3. `ADM-003` làm Overview dữ liệu thật.
4. Làm song song `ADM-004`, `ADM-005`, `ADM-006` sau khi shell ổn định.
5. Làm các vertical analytics `ADM-007`, `ADM-008`, `ADM-009`.
6. `ADM-010` hợp nhất audit/system health/runbook.
7. `ADM-011` hardening dựa trên benchmark thực tế.
8. `ADM-012` integration/UAT và sign-off.

## 8. Rủi ro chính

- KPI sai định nghĩa nguy hiểm hơn thiếu chart; bắt buộc duyệt dictionary trước khi code.
- Aggregate trực tiếp trên bảng event/attempt có thể chậm khi dữ liệu tăng; phải benchmark và cache có scope.
- Filter URL và cache key không đồng bộ có thể hiển thị dữ liệu sai context.
- Admin có quyền rộng; mọi list/detail/action/export cần policy và test role/scope, không chỉ middleware route.
- Chart đẹp nhưng thiếu denominator/sample/freshness dễ dẫn đến quyết định sai.
- Live polling có thể tạo tải nền và leak memory nếu không pause/cleanup.
- Export CSV/JSON có rủi ro PII và spreadsheet formula injection.

## 9. Ngoài phạm vi roadmap này

- Billing/subscription/payment analytics.
- Multi-tenant organization management.
- Custom report builder hoặc BI warehouse tổng quát.
- Predictive ML/churn scoring tự động.
- Dark mode.
- Native mobile Admin app.
- Filament/Nova migration.
