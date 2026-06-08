# AGENTS.md

## Multi Brain (MANDATORY)

- Read `.multibrain/session.md` before starting work.
- Use `.multibrain/session.md` as the master index only.
- Open only the `.multibrain/indexes/*.md` bucket files that match the current task.
- Open `.multibrain/context/*.md` only when the selected bucket points to deeper context that matters.
- After meaningful work, update the relevant named bucket and refresh the master index if needed.

## Project Overview

Laravel 12 task management app for Indonesian university students. Google OAuth only (no email/password). Integrates Google Classroom, Telegram Bot, and Google Gemini AI. Production: `https://todosxai.ninja/`. All UI text is **Bahasa Indonesia**.

## Commands

```bash
# Full dev environment (server + queue + logs + vite)
composer dev

# Build frontend for production
npm run build

# Run tests
composer test

# Setup from scratch
composer setup

# Manual commands (scheduler is production-only)
php artisan notification:send-reminders --type=deadline
php artisan notification:send-reminders --type=overdue --dry-run
php artisan todos:recalculate-kuadran --dry-run
php artisan classroom:sync --user=1
php artisan telegram:set-webhook  # BLOCKED in local env without explicit URL
```

## Critical Architecture Decisions

- **Database**: MySQL only. Raw SQL uses `CURDATE()`, `TIMESTAMPDIFF()`, `WEEK()`, `DATE_FORMAT()`. Never use SQLite-specific syntax.
- **AI**: Google Gemini via native REST API (`generativelanguage.googleapis.com`). Config in `config/services.php` key `gemini` + `config/ai.php`. NOT OpenAI.
- **Auth**: Google OAuth only. Login page has no email/password form. All Breeze password/register routes are removed.
- **Scheduler**: All scheduled tasks wrapped in `if (app()->environment('production'))`. Local env has zero scheduled tasks to prevent conflicts with production.
- **Telegram webhook**: Only ONE webhook per bot. `telegram:set-webhook` is blocked in local env. Never run it without an explicit ngrok URL.

## Safety Guards (localhost vs production)

These prevent localhost from breaking production when using the same Google account:

| Guard | File | Behavior |
|-------|------|----------|
| OAuth prompt | `GoogleAuthController.php` | `select_account` in local (won't revoke production refresh token), `consent` in production |
| Scheduler | `routes/console.php` | All `Schedule::command()` inside `if (production)` — zero cron in local |
| Webhook | `SetTelegramWebhook.php` | Refuses to set webhook in local without explicit URL argument |
| Bot processing | `TelegramBotController.php` | Synchronous in local (terminating callback doesn't fire in `artisan serve`), deferred in production |

## Conventions

- **Controller**: Lean orchestrator. Business logic in `app/Services/`. Max ~200 lines.
- **Validation**: Always `FormRequest` in `app/Http/Requests/{Domain}/`. Never validate in controller.
- **FK ownership**: Use `App\Rules\OwnedByUser($table)` for any user-owned foreign key.
- **Authorization**: Policy + `$this->authorize()`. Existing: `TodoPolicy`, `CategoryPolicy`, `CoursePolicy`, `AiConversationPolicy`, `AiSuggestionPolicy`.
- **JSON response**: Use `App\Support\ApiResponse` helper (`ok`, `created`, `error`). Never raw `response()->json()`.
- **Cache pattern**: `cache()->remember("user:{$userId}:{key}", $ttl, $resolver)`. Invalidate via `TodoController::forgetStatsCache()`.
- **Kuadran Eisenhower**: Auto-calculated via `Todo::hitungKuadran($priority, $dueDate)`. Re-calculated on page load (`refreshKuadranForUser`) and hourly via scheduler. Threshold: ≤1 day = urgent (from `config('todos.urgency_days')`).
- **Language**: UI copy in Indonesian. Code/variables in English. Commit prefix English (`feat:`, `fix:`, `refactor:`, `UI:`, `docs:`, `cleanup:`), body in Indonesian.
- **Telegram messages**: Always escape user input with `htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8')` via `$this->esc()` helper in `TelegramBotService`.

## Key Files

| Purpose | File |
|---------|------|
| All web routes | `routes/web.php` |
| Auth routes (Google only) | `routes/auth.php` |
| Scheduler | `routes/console.php` |
| Eisenhower algorithm | `app/Models/Todo.php` → `hitungKuadran()`, `refreshKuadranForUser()` |
| AI chat + task parsing | `app/Services/AiAssistantService.php` |
| Telegram bot commands | `app/Services/TelegramBotService.php` |
| Notification sending | `app/Services/TelegramService.php` |
| Classroom sync | `app/Services/GoogleClassroomService.php` |
| Report aggregation | `app/Services/ReportService.php` |
| Archive queries | `app/Services/ArchiveService.php` |
| Shared JS helpers | `resources/js/helpers.js` |
| Toast system | `resources/js/app.js` → `toastManager()` |
| Layout + sidebar | `resources/views/layouts/app.blade.php` |

## Environment Variables (required for full functionality)

```
GOOGLE_CLIENT_ID=         # Google Cloud Console OAuth
GOOGLE_CLIENT_SECRET=     # Google Cloud Console OAuth
GEMINI_API_KEY=           # Google AI Studio
TELEGRAM_BOT_TOKEN=       # @BotFather
TELEGRAM_BOT_USERNAME=    # Without @
TELEGRAM_WEBHOOK_SECRET=  # php -r "echo bin2hex(random_bytes(32));"
```

## MCP Tools

External tools available via Model Context Protocol (configured in `opencode.json`):

- **context7**: Search documentation (Laravel, PHP, Telegram, Google APIs, etc.). Use when you need to look up API references or framework features.
- **gh_grep**: Search code examples on GitHub. Use when you're unsure how to implement something and need real-world examples.

**Usage**: Add `use context7` or `use gh_grep` to your prompts, or the AI will automatically use them when appropriate.

## LSP Servers

OpenCode LSP aktif dengan auto-detect berdasarkan file extension. Konfigurasi di `opencode.json`.

- **PHP** (`intelephense`) — auto-install saat membuka file `.php`. Dipakai untuk Laravel codebase.
- **Python** (`pyright`) — install via `npm install -g pyright`. Dipakai untuk script di `.opencode/scripts/`. Config strictness ada di `.opencode/scripts/pyrightconfig.json` (typeCheckingMode `basic`, beberapa rule diturunkan untuk python-docx false positives).
- **YAML, Bash, JSON** — auto-detect kalau file ada.

Yang **dinonaktifkan**:
- `typescript`, `deno` — project tidak pakai TypeScript
- `eslint`, `oxlint` — project tidak pakai linter JavaScript

## Gotchas

- `Todo.due_date` is cast as `date` (Carbon, midnight). `Todo.due_time` is a raw string `H:i:s`. The `deadline` accessor combines both.
- `notification_logs.pesan` stores the full HTML message. Cooldown checks use `LIKE '%pattern%'` against this field — pattern must match the actual sent message exactly.
- `refreshKuadranForUser()` runs on every Dashboard and Todos page load. Uses `updateQuietly()` (no events fired).
- Overdue summary creates log entries **per-todo** (not just one summary log) so cooldown works per-task.
- `config/services.php` has `gemini.*` keys used by `AiAssistantService`. `config/ai.php` has context/cache/retry settings. Don't mix them up.
- Blade views use `@json()` for embedded data (not `{!! json_encode() !!}`) to prevent XSS.
