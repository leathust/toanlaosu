<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.2. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- This project uses the streamlined Laravel 11+ structure: register middleware, exceptions, and routing in `bootstrap/app.php` and service providers in `bootstrap/providers.php`. There is no `app/Http/Kernel.php` or `app/Console/Kernel.php`, and commands in `app/Console/Commands/` auto-register.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>

# Quy tắc làm việc dành cho Codex

File này áp dụng cho toàn bộ repository Laravel này. Codex phải đọc và tuân thủ các quy tắc dưới đây trong mọi phiên làm việc tiếp theo.

## 1. Nguyên tắc chung

- Đọc cấu trúc và code hiện tại trước khi sửa; không suy đoán tên route, bảng, cột, permission hoặc quan hệ model.
- Giữ nguyên các thay đổi không liên quan của người dùng trong working tree.
- Không đọc, sao chép hoặc đưa secret trong `.env` vào tài liệu, log, test output hay source code.
- Không chạy thao tác phá hủy dữ liệu như `migrate:fresh`, `db:wipe`, xóa storage hoặc reset Git nếu người dùng chưa yêu cầu rõ ràng.
- Ưu tiên thay đổi nhỏ, tương thích với Laravel 10 và kiến trúc hiện tại.
- Mọi chức năng admin mới phải tuân thủ authentication, trạng thái tài khoản và permission middleware hiện có.

## 2. Nguồn quản lý công việc

- Roadmap chi tiết nằm trong `docs/task-logs/*roadmap.md`.
- Dashboard quản lý trực quan nằm tại `docs/project-management-board.html`.
- Dữ liệu dashboard nằm tại `docs/assets/pm-board/pm-board.js`.
- Log triển khai dashboard nằm tại `docs/task-logs/project-management-dashboard.md`.
- Roadmap Markdown là nguồn mô tả, checklist và phạm vi nghiệp vụ chính.
- `pm-board.js` là bản phản ánh trực quan và phải luôn đồng bộ với tất cả roadmap.

## 3. Quy trình bắt buộc khi nhận một task roadmap

Trước khi code:

1. Đọc toàn bộ task được yêu cầu và các dependency liên quan trong roadmap.
2. Kiểm tra task tương ứng đã tồn tại trong mảng `tasks` của `pm-board.js` chưa.
3. Nếu chưa tồn tại, thêm task với ID duy nhất, module, priority, description, files dự kiến, checklist và trạng thái phù hợp.
4. Khi bắt đầu triển khai, đổi trạng thái dashboard sang `in_progress` nếu công việc kéo dài qua nhiều lượt. Không đánh dấu `[x]` trong roadmap ở bước này.
5. Nêu ngắn gọn phạm vi sẽ thay đổi và cách kiểm thử.

Trong khi code:

1. Bám đúng checklist và dependency của roadmap.
2. Nếu phát sinh thay đổi nghiệp vụ hoặc kiến trúc, cập nhật `decisions` trong `pm-board.js`.
3. Nếu phát hiện rủi ro, giới hạn hoặc technical debt chưa xử lý, cập nhật `risks` trong `pm-board.js`.
4. Không tự nhận task đã hoàn thành khi chức năng mới chỉ được tạo file nhưng chưa kiểm thử.

Sau khi code:

1. Chạy kiểm thử phù hợp với phạm vi thay đổi.
2. Nếu test đạt, đánh dấu task và checklist tương ứng `[x]` trong roadmap.
3. Cập nhật task tương ứng trong `pm-board.js`:
   - `status` thành `done`;
   - mô tả đúng kết quả đã triển khai;
   - danh sách `files` đúng với file thực tế;
   - `test` ghi kết quả kiểm thử thực tế;
   - `checklist` phản ánh các phần đã hoàn thành;
   - `decisions` ghi lại quy tắc nghiệp vụ quan trọng nếu có.
4. Thêm một mục mới vào `activities` trong `pm-board.js`, gồm loại hoạt động, task đã hoàn thành, kết quả chính và thời gian thực tế.
5. Cập nhật số liệu trong Test Center chỉ khi có kết quả lệnh test thực tế. Không được ước lượng hoặc bịa số test/assertion.
6. Nếu có migration mới hoặc vừa chạy migration, cập nhật danh sách/trạng thái migration trên dashboard theo kết quả thực tế.
7. Cập nhật `docs/task-logs/project-management-dashboard.md` nếu cấu trúc hoặc cách vận hành dashboard thay đổi.

## 4. Quy tắc khi tạo roadmap mới

Khi tạo file roadmap mới trong `docs/task-logs/`, Codex phải đồng thời:

1. Đặt tên file theo dạng `<số thứ tự>-<module>-roadmap.md`.
2. Sử dụng task ID không trùng với bất kỳ roadmap hoặc task nào trong `pm-board.js`.
3. Khai báo rõ mục tiêu, dependency, checklist, kết quả cần đạt và kế hoạch test.
4. Thêm toàn bộ task mới vào mảng `tasks` của `pm-board.js`.
5. Thêm phase mới vào mảng `phases` nếu roadmap thuộc một giai đoạn mới.
6. Để task chưa bắt đầu ở `backlog`; task kế tiếp đã đủ dependency ở `ready`.
7. Kiểm tra tiến độ tổng, phase progress, module filter và KPI vẫn được tính đúng.

## 5. Ánh xạ trạng thái

Các trạng thái hợp lệ trong `pm-board.js`:

- `backlog`: chưa sẵn sàng hoặc chưa đến lượt.
- `ready`: dependency đã hoàn tất, có thể bắt đầu.
- `in_progress`: đang code.
- `review`: đang rà soát code hoặc nghiệp vụ.
- `testing`: đang chạy test/QA.
- `done`: code và kiểm thử đã đạt, roadmap đã được cập nhật.
- `blocked`: có trở ngại cụ thể cần người dùng hoặc hệ thống bên ngoài xử lý.

Không chuyển task sang `done` nếu:

- Còn checklist bắt buộc chưa hoàn thành.
- Test liên quan đang thất bại.
- Migration cần thiết chưa được tạo hoặc chưa được xác nhận.
- Roadmap và `pm-board.js` chưa đồng bộ.

## 6. Quy tắc kiểm thử

- Chạy test mục tiêu trước, ví dụ:

```powershell
php artisan test --filter=AdminOrderTest
```

- Chạy full regression khi thay đổi ảnh hưởng nhiều module hoặc khi hoàn tất một phase:

```powershell
php artisan test
```

- Kiểm tra cú pháp dữ liệu dashboard sau mỗi lần sửa:

```powershell
node --check docs/assets/pm-board/pm-board.js
```

- Khi thay đổi route, kiểm tra route liên quan:

```powershell
php artisan route:list
```

- Khi thay đổi migration, ưu tiên kiểm tra trạng thái trước:

```powershell
php artisan migrate:status
```

- Không chạy `migrate:fresh` trên database hiện có trừ khi người dùng cho phép rõ ràng.
- Báo cáo chính xác lệnh đã chạy, số test/assertion và lỗi còn lại. Nếu không thể chạy test, phải nói rõ lý do và không ghi task là đã kiểm thử đạt.

## 7. Quy tắc dữ liệu dashboard

- Không hard-code số phần trăm tiến độ; để JavaScript tính từ trạng thái task.
- Không tạo hai task có cùng `id`.
- Tên file trong task phải là đường dẫn tương đối từ project root.
- Mọi số liệu test, migration và activity phải phản ánh trạng thái repository/database thực tế tại thời điểm cập nhật.
- Không đưa tài khoản, mật khẩu, API key, token, email riêng tư hoặc nội dung `.env` vào dashboard.
- Sau khi cập nhật, xác nhận tối thiểu:
  - `node --check` thành công;
  - task xuất hiện đúng cột;
  - module filter chứa module mới;
  - phase progress và KPI được tính đúng;
  - roadmap và dashboard có cùng trạng thái task.

## 8. Báo cáo hoàn thành cho người dùng

Khi bàn giao một task, Codex phải báo cáo ngắn gọn:

1. Chức năng đã hoàn thành.
2. Các file chính đã tạo hoặc sửa.
3. Roadmap nào đã được cập nhật.
4. Task nào trong `pm-board.js` đã được cập nhật.
5. Các lệnh kiểm thử đã chạy và kết quả thực tế.
6. Migration hoặc command người dùng cần chạy, nếu có.
7. Rủi ro hoặc phần việc tiếp theo còn lại.

Nếu task chưa hoàn thành, không đánh dấu `[x]` hoặc `done`; ghi đúng trạng thái hiện tại và nguyên nhân.

