# MVP sign-off checklist

## Deploy rehearsal

1. Backup database and verify `php artisan migrate:status` before deployment.
2. Run `composer install --no-dev --optimize-autoloader`, `php artisan config:cache`, `php artisan route:cache` and `npm run build` in the release artifact.
3. Run `php artisan migrate --force`; never use `migrate:fresh` in a shared environment.
4. Start queue workers and scheduler; verify `php artisan ops:health-check`.
5. Roll back by restoring the previous artifact and database backup; do not roll back a migration that has already changed production data without a reviewed down migration.

## Security and quality gates

- Pest, Vitest, TypeScript and Playwright suites pass.
- Verify security headers, IDOR/policy scope, CSRF, rate limits, export authorization and no secrets in logs/build artifacts.
- Verify retention dry-run before enabling scheduled cleanup.

## Manual UAT handoff

Create local UAT accounts with:

```powershell
php artisan db:seed --class=UatSeeder --force
```

The seeder creates `uat.student@example.com`, `uat.teacher@example.com` and `uat.admin@example.com`. The default local password is `password`; set `UAT_PASSWORD` to override it. Do not use these defaults in production.

- Student: login, learn lesson, interactive scene, question, exam autosave/reconnect and chat citation.
- Teacher: create/edit draft, autosave conflict compare/duplicate, preview, publish and exam export.
- Admin: filter users/content/audit/AI usage, approve content, update setting, inspect audit and confirm destructive/reason actions.

Record tester, environment, browser/device, date, result and defects before final sign-off.

### UAT session — 2026-11-24

- Environment: local Laravel app at `http://localhost:8000`, Microsoft Edge, desktop viewport.
- Student (`uat.student@example.com`): login and `/student` dashboard loaded with live enrollment data; lesson resume card and 2D scene prototype were reachable. Full exam autosave/reconnect and chat-citation checks were not completed in this session.
- Teacher (`uat.teacher@example.com`): `/teacher` showed only the scoped `E2E Radar Course`; `/teacher/courses/1` opened the course outline and authoring actions. Draft conflict/publish/export actions were not executed.
- Admin (`uat.admin@example.com`): `/admin/audit` opened and displayed the six-month audit retention policy. Filtering, approvals, settings and destructive-action confirmation were not executed.
- Result: role/access smoke passed; final manual UAT remains open until the remaining non-destructive flows are exercised and recorded.

### UAT sign-off — 2026-11-25

- Người dùng xác nhận đã hoàn tất và đạt toàn bộ checklist Student, Teacher và Admin trên môi trường local bằng Microsoft Edge.
- Kết luận: Manual UAT đạt; không ghi nhận defect blocking cho final sign-off.
