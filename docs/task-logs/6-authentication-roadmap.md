# Roadmap hoàn thiện Identity và Authentication

Ngày lập kế hoạch: 2026-11-25  
Phạm vi: đăng nhập, đăng xuất, khôi phục mật khẩu, kích hoạt/xác minh tài khoản, workspace theo role, session và bảo mật truy cập.

## 1. Mục tiêu

- Đưa authentication từ trạng thái “đăng nhập cơ bản + UI prototype” thành luồng production-ready cho Student, Teacher và Admin.
- Đảm bảo mọi workspace và dữ liệu học tập đều yêu cầu authentication, trạng thái tài khoản hợp lệ và role/scope phù hợp.
- Loại bỏ mật khẩu mặc định trong quy trình cấp tài khoản thật; thay bằng invite/initial password setup có thời hạn.
- Hoàn thiện password reset, email verification, session management, audit và rate limiting.
- Giữ một guard session `web`; role/permission tiếp tục được kiểm tra bằng middleware và Policy hiện có.

## 2. Hiện trạng đã xác minh

### Đã có

- Laravel 12.73, session guard `web`, database session và bảng `password_reset_tokens`.
- Login/logout thật bằng `Auth::attempt()`, session regeneration và session invalidation khi logout.
- `UserStatus`, `locked_at`, role, middleware `active.account` và `workspace.role`.
- Password cast `hashed`, `remember_token`, `email_verified_at`, `last_login_at` và `last_login_ip`.
- Feature test cho login Student, redirect Teacher/Admin, account suspended/locked và logout.

### Khoảng trống cần xử lý

- `/student`, lesson/practice prototype và `/workspace` hiện là route công khai.
- Login Teacher/Admin redirect đến workspace chooser tĩnh; chooser không đọc role thật và chưa mở workspace tương ứng.
- Login page vẫn ghi “Prototype UI” dù backend authentication đã hoạt động.
- Forgot password và verify email chỉ là view tĩnh; chưa gửi mail, chưa xác thực token, chưa reset password.
- `last_login_at` và `last_login_ip` chưa được cập nhật khi login thành công.
- Login dùng throttle chung `6,1`; chưa có limiter riêng theo email chuẩn hóa + IP.
- Chưa có `guest` middleware, intended redirect an toàn, password confirmation và quản lý/revoke session.
- Chưa có invite/initial password setup cho tài khoản do Admin cấp.

## 3. Quyết định phạm vi đã chốt

- Không mở self-registration trong giai đoạn này. Tài khoản Student/Teacher/Admin được Admin hoặc quy trình nghiệp vụ cấp.
- Không thêm Laravel Breeze/Fortify/Jetstream ở bước đầu; dùng API authentication sẵn có của Laravel và kiến trúc hiện tại.
- Không triển khai social login hoặc SSO trong roadmap này.
- Để 2FA cho Teacher/Admin ở phase sau, chỉ bắt đầu khi login/reset/invite/session đã ổn định.
- Không ghi password, token reset, token invite hoặc cookie/session ID vào log/audit.

## 4. Task roadmap

| Task | Nội dung | Dependency | Trạng thái |
|---|---|---|---|
| `AUTH-001` | Audit auth truth, threat model và contract | `APP-012` | `done` |
| `AUTH-002` | Login/logout, route protection và session hardening | `AUTH-001` | `done` |
| `AUTH-003` | Role-aware workspace và auth UI production | `AUTH-002` | `done` |
| `AUTH-004` | Forgot/reset password hoàn chỉnh | `AUTH-002` | `done` |
| `AUTH-005` | Invite và initial password setup | `AUTH-002` | `done` |
| `AUTH-006` | Email verification và account settings | `AUTH-003`, `AUTH-004`, `AUTH-005` | `done` |
| `AUTH-007` | Session/device security, password confirmation và audit | `AUTH-002`, `AUTH-005` | `done` |
| `AUTH-008` | Security regression, browser E2E và UAT sign-off | `AUTH-003..AUTH-007` | `in_progress` |

## 5. Chi tiết task

### `AUTH-001` — Auth truth audit và security contract

Trạng thái: `done`.

Mục tiêu: khóa phạm vi và hành vi trước khi sửa luồng đăng nhập.

- [x] Inventory route/controller/view/middleware/config/session/mail/auth hiện tại.
- [x] Lập state diagram: guest → credentials → account state → verified state → role workspace.
- [x] Threat model cho credential stuffing, user enumeration, session fixation, CSRF, open redirect và privilege escalation.
- [x] Chốt redirect matrix cho Student, Teacher, Admin và tài khoản đa role.
- [x] Chốt chính sách password, remember device, session lifetime, reset token và invite token.
- [x] Chốt response contract HTML/JSON và nội dung lỗi không làm lộ tài khoản tồn tại.
- [x] Ghi test matrix và migration impact trước khi code.

Kết quả cần đạt:

- Không còn quyết định mở ảnh hưởng kiến trúc cho `AUTH-002..AUTH-008`.
- Route matrix và security contract được duyệt.

Kế hoạch test: `php artisan route:list --except-vendor`, review config thực tế và kiểm tra roadmap/dashboard đồng bộ.

Kết quả AUTH-001: audit và contract đã ghi tại `docs/task-logs/6-authentication-audit.md`; không thay đổi runtime code trong task này.

### `AUTH-002` — Login/logout, route protection và session hardening

Trạng thái: `done`.

Mục tiêu: login đúng, mọi route cần bảo vệ đều bị khóa và session chống fixation.

- [x] Tạo Form Request cho login; chuẩn hóa email trước authentication.
- [x] Tạo named rate limiter theo email chuẩn hóa + IP; trả về giới hạn 429/Retry-After.
- [x] Thêm `guest` cho login/reset routes; `auth + active.account` cho workspace và toàn bộ Student/Teacher/Admin routes.
- [x] Loại bỏ các route Student prototype công khai; các route production hiện nằm sau auth boundary.
- [x] Regenerate session sau login; invalidate session và regenerate CSRF token khi logout.
- [x] Hỗ trợ remember device có chủ đích; không bật mặc định.
- [x] Cập nhật `last_login_at`, `last_login_ip` sau login thành công và không ghi credential vào telemetry.
- [x] Hỗ trợ intended redirect nhưng chỉ tới route nội bộ mà role hiện tại được phép truy cập.
- [x] Giữ lỗi credential chung, không phân biệt email không tồn tại và password sai.

Kết quả cần đạt:

- Guest không truy cập được bất kỳ workspace hoặc dữ liệu học tập thật.
- Session ID thay đổi sau login và bị vô hiệu sau logout.

Test bắt buộc:

- Valid/invalid credentials, email case normalization, missing fields.
- Suspended/disabled/locked account.
- Throttle theo email + IP và không khóa nhầm tài khoản khác.
- Guest route matrix, intended redirect, external redirect rejection.
- Session regeneration, remember cookie và logout invalidation.

Kết quả AUTH-002: đã triển khai `LoginRequest`, named `login` limiter, route protection cho production workspace/learning, safe intended redirect, login telemetry và session lifecycle. Test auth/UI liên quan đạt 16 tests/77 assertions; riêng AuthenticationTest đạt 8 tests/43 assertions.

### `AUTH-003` — Role-aware workspace và auth UI production

Trạng thái: `done`.

Mục tiêu: bỏ chooser tĩnh và đưa người dùng vào đúng workspace thật.

- [x] Login page bỏ toàn bộ nội dung “Prototype UI”.
- [x] Workspace page đọc role từ server; chỉ hiển thị workspace người dùng thật sự có quyền.
- [x] Student đi thẳng đến learning dashboard; Teacher/Admin đi tới workspace tương ứng hoặc chooser khi có nhiều role.
- [x] Tài khoản không có role nhận trạng thái rõ ràng và hướng dẫn liên hệ hỗ trợ.
- [x] Form có loading, validation error, throttle state, password visibility và keyboard/focus hoàn chỉnh.
- [x] Shared auth props typed cho Inertia/Vue; không tin role từ client.
- [x] Responsive 375/768/1440 và WCAG 2.2 AA cho luồng chính.

Kết quả cần đạt:

- Sau login không còn dead-end hoặc workspace giả.
- UI phản ánh chính xác authentication/account/role state từ server.

Test bắt buộc: feature redirect matrix, Vitest cho form state, Playwright login/logout cho ba role và keyboard smoke.

Kết quả AUTH-003: đã thay chooser tĩnh bằng workspace server-owned, xử lý single-role/multi-role/no-role, đưa login về production UI với validation/throttle/loading/password visibility, và bổ sung typed shared auth roles cho Inertia. Full Pest đạt 104 tests/414 assertions; Vitest đạt 3 tests; Playwright đạt 7 tests; Vite build, typecheck, Pint và kiểm tra dashboard đạt.

### `AUTH-004` — Forgot/reset password hoàn chỉnh

Trạng thái: `done`.

Mục tiêu: người dùng tự khôi phục mật khẩu bằng token có thời hạn.

- [x] POST forgot-password gọi password broker và luôn trả thông báo trung tính.
- [x] Rate limit gửi reset link; dùng notification/mail channel theo config.
- [x] GET/POST reset-password xác thực email, token, password policy và confirmation.
- [x] Token hết hạn, token đã dùng và token sai bị từ chối an toàn.
- [x] Sau reset: rotate `remember_token`, revoke session cũ và tạo audit event.
- [x] Mail notification có URL tuyệt đối, expiry theo broker và hướng dẫn nếu người dùng không yêu cầu.
- [x] Local/test dùng Notification fake; production dùng mailer theo config.

Test bắt buộc: Mail fake, anti-enumeration, throttle, valid/expired/reused token, password validation, session revocation và browser happy path.

Kết quả AUTH-004: đã triển khai `PasswordController`, `ForgotPasswordRequest`, `ResetPasswordRequest`, broker routes, limiter `password-reset`, notification URL tuyệt đối và giao diện forgot/reset production. Test PasswordReset đạt 6 tests/31 assertions; Playwright forgot-password đạt 1 test; full Pest đạt 110 tests/445 assertions.

### `AUTH-005` — Invite và initial password setup

Trạng thái: `done`.

Mục tiêu: tài khoản được cấp không phải dùng mật khẩu mặc định.

- [x] Admin tạo/gửi lại/revoke invite theo policy hiện có.
- [x] Invite token single-use, có expiry và lưu dạng hash.
- [x] Invite chỉ cho phép đặt mật khẩu và xác nhận thông tin được phép; không cho đổi role/scope.
- [x] Accept invite kích hoạt tài khoản theo policy, xác minh email khi phù hợp và revoke token.
- [x] Gửi lại invite không làm token cũ tiếp tục hợp lệ.
- [x] Audit actor, target, thời gian và kết quả; không audit raw token.
- [x] Seeder UAT vẫn dùng credential local; không dùng credential này cho production.

Test bắt buộc: authorized Admin, forbidden Teacher/Student, expired/reused/revoked token, resend invalidation, mass-assignment và audit completeness.

Kết quả AUTH-005: đã thêm `user_invitations` với token hash/expiry/state, Admin create/resend/revoke routes, accept invite tạo user và gán role immutable, notification email, audit redaction và initial password form. InvitationTest đạt 5 tests/28 assertions; full Pest đạt 115 tests/473 assertions; migration đã chạy thành công.

### `AUTH-006` — Email verification và account settings

Trạng thái: `done`.

Mục tiêu: email và password của tài khoản có lifecycle rõ ràng.

- [x] User triển khai contract email verification của Laravel.
- [x] Signed verification route, resend endpoint có throttle và trang trạng thái thật.
- [x] Chốt route nào bắt buộc `verified`; tối thiểu exam, AI/chat và Teacher/Admin operations.
- [x] Thay đổi email đưa tài khoản về trạng thái cần verify lại.
- [x] Change password yêu cầu current password, password policy và confirmation.
- [x] Password change revoke các session khác và ghi audit.

Test bắt buộc: signed/invalid/expired verification, resend throttle, unverified route matrix, current password validation và session revocation.

Kết quả AUTH-006: đã bật `MustVerifyEmail`, signed verification/resend throttle, verified route matrix cho Student learning/exam/chat và Teacher/Admin operations; bổ sung account settings đổi email/password, re-verify email, revoke session khác và audit. `EmailVerificationTest` đạt 6 tests/27 assertions; full Pest đạt 121 tests/500 assertions.

### `AUTH-007` — Session/device security, password confirmation và audit

Trạng thái: `done`.

Mục tiêu: người dùng nhìn và kiểm soát được session; thao tác nhạy cảm cần xác nhận lại.

- [x] Hiển thị session hiện tại và các session khác bằng dữ liệu tối thiểu: thiết bị, IP rút gọn, last activity.
- [x] Revoke một session hoặc toàn bộ session khác sau khi xác nhận password.
- [x] Password confirmation cho thay đổi account, Admin destructive actions và security settings.
- [x] Cấu hình production cookie: secure, httpOnly, sameSite, domain/path và lifetime được kiểm tra trong deploy checklist.
- [x] Audit login success/failure theo dữ liệu an toàn, logout, reset, invite, verify, password change và session revoke.
- [x] Không để log/audit trở thành kênh user enumeration hoặc chứa credential/token.

Test bắt buộc: ownership session, cross-user rejection, password confirmation timeout, revoke behavior, cookie/security headers và audit redaction.

Kết quả AUTH-007: đã bổ sung session inventory với device/IP rút gọn và last activity, revoke từng session/toàn bộ session khác qua password confirmation, bảo vệ các thao tác destructive của Admin, bật `auth.session`, cấu hình cookie policy an toàn và audit login/logout/email verification/session revoke không chứa credential/token. `SessionSecurityTest` đạt 4 tests/31 assertions; full Pest đạt 125 tests/531 assertions.

### `AUTH-008` — Security regression, E2E và UAT sign-off

Trạng thái: `in_progress`.

Mục tiêu: nghiệm thu toàn bộ auth flow trước khi mở chức năng Post-MVP khác.

- [x] Full Pest regression và các test auth mục tiêu.
- [x] Playwright cho login/logout, intended redirect, reset password, invite, verify và session revoke.
- [x] UAT Student, Teacher, Admin trên Edge desktop; thêm mobile viewport cho Student.
- [x] Kiểm tra CSRF, rate limit, session fixation, open redirect, user enumeration và role escalation.
- [ ] Mail delivery rehearsal trên staging; không gửi tới email thật trong automated test.
- [x] Cập nhật runbook: khóa tài khoản, reset thủ công, revoke session và xử lý mail outage.
- [x] Roadmap, PM board và Test Center phản ánh đúng kết quả lệnh thực tế.

Gate hoàn thành:

- Không còn route workspace/dữ liệu thật công khai.
- Login/reset/invite/verify/session flow đạt automated test và UAT.
- Không còn nội dung “prototype” trong UI authentication production.
- `AUTH-001..AUTH-008` đều `done` và không còn defect security blocking.

Tiến độ AUTH-008: full Pest đạt 129 tests/547 assertions; Vitest đạt 3 tests, Playwright đạt 12 tests với login/logout, intended redirect, reset, invite invalid-state, verification notice/resend và session revoke qua hai browser context; typecheck/build đạt. Regression security bổ sung CSRF middleware route coverage, credential enumeration, role escalation và session fixation. Manual UAT Student/Teacher/Admin đã được ghi nhận đạt trong `docs/task-logs/5-mvp-signoff-checklist.md`. Còn mở mail delivery rehearsal trên staging trước final sign-off.

## 6. Thứ tự triển khai đề xuất

1. `AUTH-001` audit và chốt contract.
2. `AUTH-002` khóa route + harden login/session trước mọi thay đổi UI.
3. `AUTH-003` làm role-aware UI và workspace thật.
4. Thực hiện song song theo dependency: `AUTH-004` reset password và `AUTH-005` invite.
5. `AUTH-006` verification/account settings.
6. `AUTH-007` session/device/password confirmation/audit.
7. `AUTH-008` regression, browser E2E và manual UAT.

## 7. Rủi ro chính

- Sửa route/middleware có thể khóa nhầm toàn bộ Student/Teacher/Admin; bắt buộc test role matrix trước khi đổi redirect mặc định.
- Reset/invite phụ thuộc mail delivery; cần log/array mailer ở local và staging rehearsal trước production.
- Database session cho phép revoke nhưng phải tránh lộ raw session ID và tránh xóa nhầm session hiện tại.
- Email verification áp dụng quá sớm có thể khóa tài khoản seed/legacy; cần migration/backfill policy rõ ràng.
- Rate limiter sai key có thể cho phép credential stuffing hoặc gây denial-of-service vào một tài khoản.

## 8. Ngoài phạm vi

- Self-registration công khai.
- Social login, OAuth, SSO/SAML.
- TOTP/WebAuthn 2FA.
- Native mobile biometric login.
- Passwordless/magic-link login.

Các mục trên chỉ mở roadmap mới sau khi `AUTH-008` đạt.
