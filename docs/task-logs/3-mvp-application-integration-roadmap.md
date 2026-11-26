# Roadmap 3 — MVP Application Architecture, Business Logic & Integration

## 1. Trạng thái tài liệu

- Đã được người dùng chốt làm roadmap tiếp theo sau Database và UI roadmap.
- `APP-001` đã hoàn tất; task kế tiếp đủ dependency là `APP-002`.
- Roadmap này coi các Blade screen hiện tại là prototype/baseline có thể tái sử dụng, không mặc định là production-ready.

## 2. Mục tiêu

Biến database schema và UI prototype hiện có thành một MVP tích hợp thực sự, dùng chung stack đã chốt `Laravel + Inertia + Vue 3 + TypeScript`, với business logic, authorization, state transitions, queue/retention và kiểm thử xuyên suốt.

Kết quả cuối roadmap:

- Student, Teacher và Admin dùng một frontend stack thống nhất.
- Luồng học, scene 2D, thi, authoring, Admin và AI dùng dữ liệu thật, policy thật và state machine rõ ràng.
- Không còn controller kiểm tra quyền rải rác hoặc mock data bị hiểu nhầm là dữ liệu production.
- Các tác vụ nền, retention và observability có thể vận hành an toàn.
- Có integration/E2E đủ để ký duyệt MVP kỹ thuật trước manual UAT.

## 3. Hiện trạng và khoảng trống

### 3.1. Đã có

- Database roadmap `DB-001..DB-010` và schema/model/factory nền tảng.
- UI shell, Blade prototype và một số controller/API cho học tập, thi, chat, Teacher và Admin.
- Pest, Vitest và Playwright đã được duyệt; Pest/Playwright hiện có test chạy được.
- Vite/Tailwind CSS 4 và security-header baseline.

### 3.2. Chưa đạt kiến trúc mục tiêu

- Chưa cài `inertiajs/inertia-laravel`, Vue 3, `@inertiajs/vue3` và TypeScript dù stack đã được duyệt.
- Nhiều screen còn Blade/static slug/mock state; chưa có page props contract thống nhất.
- Authorization chủ yếu nằm trong controller; chưa chuẩn hóa bằng Policy, middleware và action authorization.
- Progress, exam, editor và chat mới có vertical slice đơn giản; concurrency/error/idempotency chưa được kiểm chứng đầy đủ.
- Teacher editor chưa có autosave draft, optimistic conflict, preview và publish/version workflow hoàn chỉnh.
- AI hiện là local tutor prototype, chưa có provider/RAG/queue/cost control thật.
- Automated smoke đã có nhưng chưa thay thế full authenticated E2E, performance budget và manual UAT.

## 4. Nguyên tắc thực hiện

1. Audit trước khi migrate kiến trúc; không tiếp tục dựa trên trạng thái roadmap bị đánh dấu quá rộng.
2. Migrate theo vertical slice, giữ route/prototype cũ hoạt động cho tới khi page Inertia tương ứng đạt test.
3. Business rule ở Policy/Action/Service, không nhúng vào Vue component hoặc lặp lại trong controller.
4. Server là nguồn sự thật cho authorization, deadline, score, publish state và optimistic version.
5. Không thêm dependency mới ngoài phạm vi đã duyệt nếu chưa có lý do, bundle impact và test plan.
6. Mọi action ghi dữ liệu phải có validation, authorization, transaction/idempotency khi cần và audit phù hợp.
7. Không đánh dấu task `done` nếu mới tạo file mà chưa chạy test mục tiêu và đồng bộ roadmap/PM board.

## 5. Kiến trúc đích

### 5.1. Frontend

- Laravel Inertia adapter + Vue 3 + TypeScript.
- Shared layouts cho Public, Auth, Student, Teacher và Admin.
- Page props typed; data server-owned không lưu lặp trong client store.
- Component/composable test bằng Vitest; browser flow bằng Playwright.
- Blade prototype giữ ở route review riêng trong giai đoạn chuyển đổi rồi loại bỏ khi sign-off.

### 5.2. Backend

- Thin controllers gọi Form Request, Policy và Action/Service.
- Policies cho Course, Lesson, Question, VisualScene, Exam, ChatThread và Admin operation.
- Transaction boundary rõ cho progress, attempts, autosave, publish và destructive operation.
- Queue cho AI, export, cleanup và tác vụ có latency cao.

### 5.3. Integration boundary

- Learning: Enrollment → Course → Lesson → Block → Progress/Attempt.
- Exam: Exam window → Frozen version → Attempt → Autosave → Submit → Grade → Release.
- Authoring: Draft → Autosave/version → Review → Publish → Immutable published version.
- AI: Context → Retrieval → Provider → Stream → Citation/Usage/Feedback → Retention.

## 6. Task breakdown và dependency

| ID | Task | Dependency | Trạng thái |
| --- | --- | --- | --- |
| `APP-001` | Audit hiện trạng, phân loại prototype/MVP/production và sửa contract roadmap | `DB-010`, `UI-015` | `done` |
| `APP-002` | Cài và cấu hình Inertia + Vue 3 + TypeScript, shared layouts và typed page props | `APP-001` | `done` |
| `APP-003` | Chuẩn hóa authentication, RBAC, Policies và workspace middleware | `APP-002`, `DB-002` | `done` |
| `APP-004` | Core domain models và enrollment | `APP-003`, `DB-003`, `DB-004`, `DB-005` | `done` |
| `APP-005` | Vertical slice học tập thật: dashboard → lesson → progress → question attempt | `APP-004`, `UI-003`, `UI-004`, `UI-005`, `UI-006` | `done` |
| `APP-006` | Scene 2D typed runtime, persistence, authoring, versioning và fallback | `APP-004`, `DB-004`, `UI-007` | `in_progress` |
| `APP-007` | Exam lifecycle, concurrency, grading, release và failure recovery | `APP-004`, `DB-006`, `UI-008`, `UI-009` | `done` |
| `APP-008` | Teacher editor: preview, autosave draft, conflict, history và publish workflow | `APP-004`, `DB-003`, `DB-004`, `UI-011` | `done` |
| `APP-009` | Admin workflow: filters/URL state, approvals, destructive confirmation và audit | `APP-003`, `DB-009`, `UI-013` | `done` |
| `APP-010` | AI provider/RAG thật, streaming, retry, citations, allow-list và cost control | `APP-004`, `DB-008`, `UI-010` | `done` |
| `APP-011` | Queue, scheduler, retention cleanup, observability và operational recovery | `APP-005`, `APP-006`, `APP-007`, `APP-008`, `APP-009`, `APP-010` | `done` |
| `APP-012` | Full integration/E2E, performance budget, security review và MVP technical sign-off | `APP-011` | `done` |

## 7. Checklist chi tiết theo task

### `APP-001` — Repository và roadmap truth audit

- [x] Lập inventory route/controller/view/JS/test hiện có theo Student, Teacher, Admin.
- [x] Đánh dấu từng screen/API là mock, prototype, partial MVP hoặc production-ready.
- [x] Đối chiếu stack đã duyệt với dependency thực tế và ghi gap cụ thể.
- [x] Đối chiếu các checklist roll-up chưa đạt; sửa trạng thái task/PM board nếu đang quá lạc quan.
- [x] Chốt migration map từ Blade prototype sang Inertia pages mà không làm mất route đang dùng.
- [x] Chạy baseline full Pest, Playwright, Vite build, route list và ghi số liệu thực tế.

#### Biên bản audit ngày 2026-11-24

**Bằng chứng repository:** `php artisan route:list --except-vendor` có 38 route; 7 controller nghiệp vụ; 31 model; 24 Blade view; 6 file JavaScript trong `resources/js`; 20 Feature test và 3 Playwright spec. Baseline đạt `php artisan test --compact` với 60 tests/229 assertions, `npm.cmd run test:e2e` với 4 tests passed và `npm.cmd run build` thành công.

**Phân loại hiện trạng:**

| Khu vực | Routes/screens chính | Phân loại | Bằng chứng và gap |
|---|---|---|---|
| Student learning | `/student`, lesson/practice prototype, completion/attempt endpoints | Partial MVP | Dashboard đã đọc enrollment thật khi đăng nhập; lesson/practice vẫn route view tĩnh, hiển thị `Mock progress`/`Mock attempt`, chưa nối lesson/question version thật. |
| Student exam | exam show, save answer, submit, result, history | Partial MVP | Có attempt snapshot, server-derived timer, optimistic save và idempotent submit; còn thiếu policy chuẩn hóa, concurrency/recovery đầy đủ và E2E authenticated. |
| Student AI | chat show/send/stream/feedback/command | Partial MVP | Có persistence, SSE prototype và allow-list command; usage ghi provider `local`, chưa có provider abstraction/RAG/chi phí thật. |
| Scene 2D | `/ui/prototype`, `resources/js/scenes.js` | Prototype | Có 4 scene SVG động, controls và fallback để review UI; chưa load `visual_scene_versions` hoặc có authoring/publish workflow. |
| Teacher | dashboard/course/lesson/question/scene/exam/report/export | Partial MVP | Đọc/ghi dữ liệu thật và có scope thủ công; chưa có Policy, Form Request, autosave/conflict/version history và publish workflow. |
| Admin | dashboard/users/content/audit/ai-usage | Partial MVP | Có list dữ liệu thật và role guard thủ công; thiếu filter/pagination URL, policy/action contract, destructive confirmation và audit đầy đủ. |
| Auth/workspace | login/logout/workspace/forgot/verify | Partial MVP | Session login/logout và role redirect hoạt động; UI còn disclosure `Prototype UI`, chưa có Inertia shared auth props. |
| UI shell | `/ui/prototype`, `/ui/components` | Prototype | Đúng phạm vi UI roadmap: shell/design system/state review, không phải màn hình nghiệp vụ production. |

**Stack gap đã xác nhận:** `composer show --direct` không có `inertiajs/inertia-laravel`; `package.json` chỉ có Vite/Tailwind/Axios/Playwright/Vitest, không có Vue 3, `@inertiajs/vue3` hay TypeScript; chưa có `tsconfig.json` và chưa có `resources/js/Pages`. Đây là gap bắt buộc của APP-002, không phải lỗi của baseline UI prototype.

**Migration map đã chốt:** giữ nguyên named route và Blade screens trong giai đoạn chuyển đổi; thêm Inertia middleware/entrypoint trước, sau đó migrate theo vertical slice `auth/workspace → Student learning → scene → exam → Teacher → Admin → AI`. Mỗi slice giữ Blade fallback cho đến khi authenticated E2E và parity test đạt; không thay toàn bộ route bằng một big-bang migration.

**Quyết định về trạng thái cũ:** `UI-001..UI-015` vẫn `done` trong phạm vi prototype/UI hardening đã được kiểm thử. Không coi các trạng thái đó là production-ready; APP-002 trở đi là nguồn theo dõi tích hợp thật. `APP-002` được mở từ `backlog` lên `ready` sau khi audit hoàn tất.

### `APP-002` — Inertia/Vue/TypeScript foundation

- [x] Xác nhận version package hiện có trước khi cài.
- [x] Cài Laravel Inertia adapter, Vue 3, `@inertiajs/vue3` và TypeScript.
- [x] Cấu hình Vite entry, SSR decision, type checking và shared page props.
- [x] Tạo typed layouts/navigation cho Student, Teacher và Admin.
- [x] Migrate một page pilot và giữ Blade prototype làm fallback review.
- [x] Thêm Vitest/type-check/build test; đo bundle trước/sau.

#### Kết quả APP-002 ngày 2026-11-24

- Composer: `inertiajs/inertia-laravel` `v3.8.0`.
- NPM: `@inertiajs/vue3` `3.11.0`, Vue `3.5.46`, `@vitejs/plugin-vue` `6.0.11`, TypeScript `5.9.3`, `vue-tsc` `3.3.14`; các version được ghi trong `package.json`/`package-lock.json`.
- Laravel đã đăng ký `HandleInertiaRequests`, root view `resources/views/app.blade.php`, shared props `app`/`auth`, và tắt SSR mặc định cho MVP vì chưa có SSR bundle.
- Đã tạo `StudentLayout`, `TeacherLayout`, `AdminLayout` dùng shared `AppLayout`; pilot có route `ui.inertia-pilot` và Blade prototype cũ vẫn giữ nguyên.
- Đã thêm `test:unit`, `typecheck`, `vitest.config.ts`, Inertia feature test và Vitest smoke test cho workspace contract.

### `APP-003` — Authentication, RBAC và Policies

- [x] Chuẩn hóa auth/session/account-state middleware.
- [x] Tạo Policies cho tài nguyên cốt lõi và thay controller guard thủ công.
- [x] Workspace redirect dựa trên role/permission thật.
- [x] Kiểm tra course teacher scope, enrollment period và owner question ở list/detail/action.
- [x] Test IDOR, inactive/locked account, revoked scope và unauthorized action.

#### Kết quả APP-003 ngày 2026-11-24

- Thêm `EnsureActiveAccount` để logout/invalidate session khi tài khoản suspended/disabled/locked; JSON trả 403, browser redirect về login.
- Thêm `EnsureWorkspaceRole` và alias `workspace.role`; route Teacher/Admin không còn chỉ dựa trên `auth`.
- Thêm `UserPolicy`, `CoursePolicy`, `ExamPolicy`, `ChatThreadPolicy`, `QuestionPolicy` và `VisualScenePolicy`; controller dùng `authorize`/`Gate::authorize` thay guard thủ công.
- Chuẩn hóa `User::hasRole`, `hasAnyRole`, `isActive`; login redirect và account-state dùng cùng contract.
- Kiểm thử revoked teacher scope, cross-workspace role, chat owner IDOR và inactive account.

### `APP-004` — Core domain models và enrollment

- [x] Xác định application boundary cho enrollment; các domain action khác nằm trong vertical task tương ứng.
- [x] Chuyển validation enrollment quan trọng sang `StoreEnrollmentRequest`.
- [x] Chuẩn hóa transaction, idempotency theo khóa course/user/period và domain validation response cho enrollment.
- [x] Chuẩn hóa audit event `enrollment.created` với retention 6 tháng.
- [x] Feature tests cho action boundary, active-period scope, idempotency và validation.

#### Kết quả APP-004 (phạm vi core domain/enrollment) ngày 2026-11-24

- Thêm `Enrollment::activeAt()`/`isActiveAt()` và quan hệ `activeEnrollments()` cho User/Course, tôn trọng starts_at, ends_at, status và revoked_at.
- Thêm `EnrollStudent` action với Policy authorization, transaction, idempotent repeat theo unique course/user/period và audit log retention 6 tháng.
- Thêm `StoreEnrollmentRequest`, `EnrollmentController` và route Teacher để ghi danh theo từng đợt học.
- Các domain action cho exam, authoring, Admin và AI tiếp tục được hoàn thiện ở các vertical task tương ứng.

### `APP-005` — Learning vertical slice

- [x] Student dashboard dùng enrollment/progress thật, không hard-code course card.
- [x] Route course/unit/lesson dùng public identifier phù hợp, không dùng slug mẫu cố định.
- [x] Lesson block renderer nhận typed payload cho video/material/note/question/visual.
- [x] Progress/resume/unlock policy idempotent và giữ trạng thái qua reload/device.
- [x] Single/multiple/numeric grading theo version/scoring policy, không điểm âm MVP.
- [x] Authenticated E2E: login → học → trả lời → reload → resume đúng.

#### Kết quả APP-005 (vertical slice Inertia học tập) ngày 2026-11-24

- Thêm `Student/LearningDashboard` và `Student/Lesson` bằng Inertia/Vue/TypeScript; Blade prototype cũ vẫn giữ làm fallback.
- Dashboard đọc active enrollment, course/lesson thật và tính progress từ `lesson_block_progress`.
- Lesson dùng route public identifier `course.slug + lesson.slug`, kiểm tra enrollment trước khi trả page props.
- Block renderer nhận payload typed cho note/text/question và các block khác có fallback; progress completion gọi API idempotent.
- Feature tests chứng minh dashboard/lesson Inertia, scope chống lộ bài và trạng thái block.
- Thêm `E2eLearningSeeder` và Playwright global setup idempotent để tái tạo fixture course/lesson/question/enrollment trước browser test.
- Authenticated Playwright đạt 5/5: login → mở khóa → mở lesson → hoàn thành block → nộp câu hỏi → reload giữ trạng thái.

### `APP-006` — Scene 2D runtime và authoring

- [x] Typed scene schema/version validation phía server và client.
- [x] Runtime play/pause/seek/reset/parameter update, reduced motion và cleanup.
- [x] Bốn scene trọng tâm dùng config persisted thay vì markup demo.
- [x] Fallback ảnh/text và accessibility description.
- [ ] Teacher scene editor có preview, draft version và allowed command validation.
- [x] Vitest/feature/E2E smoke cho state controller, invalid config và resource cleanup.

#### Kết quả APP-006 (scene 2D runtime/persistence) ngày 2026-11-24

- Thêm `ValidateSceneConfiguration`, `SceneController` và routes GET/command chỉ đọc published version, kiểm tra schema type và allow-list command phía server.
- Thêm typed client schema/state controller, `SceneRuntime` Vue component với play/pause/seek/reset, requestAnimationFrame và cleanup khi unmount.
- Lesson Inertia nhận scene version persisted và render fallback SVG/text accessibility description.
- Test bốn scene type, invalid config, command rejection, Vitest state controller và Playwright scene smoke đạt.
- Teacher editor preview/draft version sẽ hoàn thiện ở APP-008.

### `APP-007` — Exam lifecycle

- [x] Kiểm tra exam window, late entry, max attempts và frozen question snapshot.
- [x] Timer server-derived, autosave optimistic concurrency và reconnect reconciliation.
- [x] Submit idempotent; deadline/grace period không thể kéo dài bằng client.
- [x] Grading, result/answer release policy và retention một năm.
- [x] AI/chat bị vô hiệu hóa trong exam context.
- [x] Test concurrent save, duplicate submit, expiry, IDOR và network recovery.

#### Kết quả APP-007 (exam lifecycle/concurrency) ngày 2026-11-24

- Tách `SaveExamAnswer` và `SubmitExamAttempt` actions; autosave dùng row lock + optimistic `save_version`, submit dùng idempotent lock.
- Attempt được tạo trong transaction với frozen question snapshot, server deadline và retention một năm; submit chấm điểm theo question version, không điểm âm.
- Deadline được kiểm tra phía server cho autosave/submit; max attempts được kiểm tra trong transaction; result/answer release policy hiện giữ nguyên.
- Test duplicate submit, stale save, expiry, max attempts, grading và IDOR đạt; AI/chat bị chặn với exam context; exam draft được lưu local để retry khi reconnect.
- Authenticated Playwright offline/reconnect đạt: local draft được giữ, save retry thành công và reload đọc lại answer từ server.
- Đã làm fixture `E2eLearningSeeder` deterministic bằng cách dọn attempt/question answer/event cũ trước mỗi lần seed; Playwright chạy lặp lại không còn lỗi `409` do `save_version` stale.

### `APP-008` — Teacher editor và publish workflow

Trạng thái: `done`.

- [x] Lesson/question/scene editor dùng typed Inertia forms.
- [x] Draft autosave có debounce, save status và optimistic `lock_version`.
- [x] Conflict UI hỗ trợ reload/compare/duplicate draft, không ghi đè im lặng.
- [x] Preview dùng đúng draft version hiện tại.
- [x] Version history và review/publish workflow; published version bất biến.
- [x] Authorization/audit test cho editor, reviewer và owner.

#### Tiến độ APP-008 ngày 2026-11-24

- Đã thêm action cập nhật lesson draft trong transaction, kiểm tra `lock_version` và trả `409` khi conflict; không ghi đè bản mới hơn.
- Đã ghi `ContentRevision` cho draft/publish, thêm endpoint đọc version history và audit event với retention 6 tháng.
- Đã thêm publish action có CoursePolicy authorization, cập nhật trạng thái/published timestamp và test owner/editor/non-editor.
- Đã thêm `Teacher/LessonEditor.vue` với typed form, debounce 600ms, save status, preview draft hiện tại và banner conflict reload.
- Conflict UI hiện hỗ trợ reload, compare bản local/server và tạo draft copy; lesson đã publish bị khóa bất biến.
- Version history API/sidebar và publish workflow đã được kiểm thử; APP-008 hoàn tất.

### `APP-009` — Admin workflow

- Trạng thái: `done`.
- [x] Server-side filter/sort/pagination và URL state cho users/content/audit/AI usage.
- [x] Role/account/content approval actions dùng Policy và Form Request.
- [x] Destructive action có explicit confirmation, reason và audit trail.
- [x] System setting update có typed value, validation và rollback an toàn.
- [x] Test admin-only, audit completeness và filter scope.

#### Tiến độ APP-009 ngày 2026-11-24

- Đã thêm filter server-side cho users, content, audit và AI usage; paginator giữ query string để URL state không mất khi chuyển trang.
- Đã thêm Form Request và action cho user status, content approval và system setting; các action dùng Gate/Policy boundary và audit retention 6 tháng.
- Đã thêm test admin-only, filter scope, status approval và audit completeness.
- Đã thêm schema kiểu dữ liệu cho system settings, chặn key không hợp lệ, validation giá trị âm và transaction rollback; APP-009 hoàn tất.

### `APP-010` — AI/RAG integration

- Trạng thái: `done`.
- [x] Provider abstraction, timeout/retry/circuit-breaker và secret-safe configuration.
- [x] Text retrieval theo quyết định MVP; citation bắt buộc trỏ đúng knowledge chunk.
- [x] Streaming không tạo duplicate assistant message khi retry/reconnect.
- [x] Scene tool commands qua allow-list và server validation; vô hiệu trong exam.
- [x] Usage/cost/budget/rate limit và retention một năm.
- [x] Fake-provider tests cho success, timeout, retry, citation và budget exceeded.

#### Tiến độ APP-010 ngày 2026-11-24

- Đã thêm `GenerativeProvider` abstraction, local provider mặc định, text retriever trên knowledge chunks indexed và citations gắn đúng chunk.
- Đã thêm retry hai lần cho lỗi provider, daily token budget, usage log retention một năm và HTTP 429 khi vượt budget; fake-provider test đã bao phủ success/retry/citation/budget.
- Scene command allow-list/exam block được giữ qua boundary hiện có; request idempotency chống duplicate assistant khi retry/reconnect.
- Timeout config, retry/circuit breaker, secret-safe provider config và fake timeout test đã hoàn tất; APP-010 hoàn tất.

### `APP-011` — Operations và retention

- Trạng thái: `done`.
- [x] Queue workers, failed jobs, scheduler và health checks.
- [x] Cleanup audit 6 tháng; exam attempt/answer và AI/chat usage một năm.
- [x] Export/AI/cleanup jobs idempotent, retry-safe và observable.
- [x] Structured logging/metrics cho exam autosave, AI errors và queue lag.
- [x] Runbook cho failed job, stuck exam, provider outage và manual deletion qua CSKH.

#### Tiến độ APP-011 ngày 2026-11-24

- Đã thêm `ops:cleanup-retention` với `--dry-run`, service cleanup transaction-safe cho audit 6 tháng và exam/AI/chat một năm.
- Đã thêm queued `RunRetentionCleanup` với retry 3 lần, scheduler daily 02:30 và `ops:health-check` kiểm tra DB/cache/queue/failed jobs.
- Cleanup test xác nhận xóa đúng record hết hạn, giữ record active và dry-run không mutate; runbook vận hành còn mở.
- Đã thêm `ProcessExportJob`/`QueueExport` idempotent theo `idempotency_key`, retry-safe và structured logs cho export, cleanup, exam autosave và AI provider errors.
- Runbook vận hành đã viết tại `docs/task-logs/4-operations-recovery-runbook.md`; APP-011 hoàn tất.

### `APP-012` — Integration sign-off

- Trạng thái: `done`.
- [x] Full Pest regression và authenticated Playwright E2E theo role.
- [x] Device/browser matrix, WCAG 2.2 AA audit và reduced-motion verification.
- [x] Bundle budget, Core Web Vitals target và long-running scene memory check.
- [x] Security review: IDOR, CSRF, rate limit, mass assignment, upload/export và secret exposure.
- [x] Migration/deploy rehearsal, rollback plan và production checklist.
- [x] Manual UAT với ít nhất một Student, một Teacher và một Admin trước final sign-off.

#### Tiến độ APP-012 ngày 2026-11-25

- Full regression đạt Pest 98/375, Vitest 3/3, TypeScript check, Vite build và authenticated Playwright 6/6; `php artisan migrate:status` xác nhận toàn bộ migration đã Ran.
- Browser smoke đã kiểm tra desktop/mobile, focus keyboard, reduced-motion baseline, security headers và scene runtime; bundle lớn nhất app JS khoảng 212.85 kB (gzip 71.81 kB), CSS 74.72 kB (gzip 14.34 kB).
- Security boundary đã có feature coverage cho IDOR/RBAC, CSRF/session, mass assignment/Form Request, export scope, exam AI block và secret-safe config; chưa có production DAST hoặc real device lab.
- Deploy rehearsal/rollback checklist và production checklist đã ghi trong `docs/task-logs/5-mvp-signoff-checklist.md`; manual UAT với ba vai trò còn chờ người dùng thực hiện.
- Đã tạo và chạy `UatSeeder`, cung cấp ba tài khoản local cho Student/Teacher/Admin. Người dùng xác nhận ngày 2026-11-25 đã test đạt toàn bộ flow Student, Teacher và Admin; APP-012 đủ điều kiện final sign-off.

## 8. Kế hoạch kiểm thử

### 8.1. Laravel/Pest

- Policies, Form Requests, Actions và transaction rollback.
- Learning progress, grading, exam concurrency/release, authoring conflict và Admin audit.
- AI provider fake, citation ownership, rate limit và retry idempotency.

### 8.2. Vitest/TypeScript

- Composable/state machine cho lesson, scene, exam timer/autosave và chat streaming.
- Typed props/input normalization và conflict resolution.
- Cleanup timer, AbortController, EventSource và animation frame.

### 8.3. Playwright

- Student: login → lesson → question → scene → resume.
- Exam: start → autosave → offline/reconnect → deadline → submit → release.
- Teacher: draft → autosave → conflict → preview → publish.
- Admin: filter → role/content action → confirm → audit record.
- AI: streaming → citation → retry → scene command; bị chặn trong exam.

## 9. Cổng duyệt

### Gate A — Sau `APP-001`

- Inventory và trạng thái prototype/MVP được người dùng duyệt.
- Migration map sang Inertia không làm mất chức năng hiện có.

### Gate B — Sau `APP-005`

- Một learning vertical slice chạy hoàn toàn bằng Inertia/Vue/TypeScript và dữ liệu thật.
- Policies và tests chứng minh không lộ scope.

### Gate C — Sau `APP-010`

- Learning, scene, exam, Teacher/Admin và AI đều có business flow thật.
- Không còn mock/local provider bị hiểu nhầm là production integration.

### Gate D — Sau `APP-012`

- Automated test, performance/security review, deploy rehearsal và manual UAT đạt.
- Roadmap mới được ký `done` khi không còn checklist bắt buộc chưa hoàn tất.

## 10. Rủi ro trọng yếu

- Chuyển Blade sang Inertia diện rộng có thể gây regression nếu không migrate theo vertical slice.
- Controller hiện có chứa business logic và guard thủ công; refactor thiếu test dễ tạo IDOR hoặc sai scope.
- Exam autosave/concurrency và AI streaming có race condition khó phát hiện bằng happy-path test.
- Scene runtime và editor có thể làm bundle/memory tăng nếu không lazy-load và cleanup.
- Trạng thái `done` của roadmap cũ có thể gây hiểu nhầm; `APP-001` phải là nguồn sự thật mới.

## 11. Điều kiện hoàn thành roadmap

- `APP-001..APP-012` đều `done` và roadmap/PM board đồng bộ.
- Full regression, Vitest, Playwright, build/type-check và security/performance gates đạt.
- Không còn mock data hoặc local prototype trong luồng production mà không có nhãn/fallback rõ.
- Manual UAT và deployment rehearsal được ghi nhận bằng kết quả thực tế.
