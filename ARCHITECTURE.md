# Arsitektur & Panduan Pengembangan — Todos AI

Dokumen ini menjelaskan pola dan prinsip yang digunakan dalam proyek ini untuk memastikan kode terstruktur, mudah dipahami, dan dapat dipertahankan oleh pengembang di masa depan.

## Ringkasan Perubahan Arsitektur (Fase 1)

Refaktoring dilakukan pada **20 April 2026** untuk:

1. **Keamanan**: Menambahkan authorization checks dan validasi ownership
2. **Performa**: Mengurangi N+1 queries, menambahkan caching, composite indexes
3. **Kualitas Kode**: Mengekstrak konstanta, menggunakan FormRequest, helpers, dan policies
4. **Konsistensi**: Standardisasi response format, error handling, pattern pengembangan

---

## 1. Pola Konfigurasi (`config/`)

**Tujuan**: Mengekstrak konstanta dan setting dari dalam kode menjadi file konfigurasi yang terpusat, memudahkan perubahan tanpa edit kode.

### File-file:

#### `config/todos.php`
Konfigurasi untuk fitur todos: kuadran, prioritas, kategori, pagination, caching.

**Diakses via**: `config('todos.*')`

**Isi utama**:
- `kuadran`: Enum-like dengan ID, label, warna (DO_NOW=1, SCHEDULE=2, DELEGATE=3, ELIMINATE=4)
- `priority_colors`: Warna untuk high/medium/low priority
- `category_palette`: Palet warna default untuk kategori baru
- `per_page`: Pagination default (25 items)
- `stats_cache_ttl`: TTL cache statistik (default 60 detik)

**Contoh penggunaan**:
```php
$kuadran = config('todos.kuadran.do_now.id');
$ttl = config('todos.stats_cache_ttl');
```

#### `config/ai.php`
Konfigurasi untuk AI Assistant: API settings, limits, caching, retry policy.

**Isi utama**:
- `gemini.api_key`: API key Gemini (dari .env)
- `gemini.model`: Model yang digunakan (default: `gemini-2.0-flash`)
- `gemini.temperature`: Kreativitas response
- `context.limit`: Jumlah todos aktif yang disertakan (default 30)
- `context.cache_ttl`: TTL untuk cached context (default 300 detik = 5 menit)
- `history.per_page`: Pagination conversation history (default 50)
- `history.max_load`: Limit konversasi yang bisa dimuat sekaligus (default 200)
- `retry.max_attempts`: Jumlah retry untuk API errors
- `retry.delay_ms`: Delay antar retry (exponential backoff)

**Contoh penggunaan**:
```php
$limit = config('ai.context.limit');
$ttl = config('ai.context.cache_ttl');
```

#### `config/telegram.php`
Konfigurasi untuk Telegram Bot: credentials, webhook, reminders.

**Isi utama**:
- `bot_token`: Token bot dari Telegram
- `webhook_secret`: Secret untuk verifikasi webhook (wajib di production)
- `webhook_secret_required`: Boolean — apakah secret wajib (true di production)
- `reminder_times`: Waktu pengiriman reminder (array jam)
- `reminder_batch_size`: Batch size untuk reminder queries

**Contoh penggunaan**:
```php
$secret = config('telegram.webhook_secret');
$required = config('telegram.webhook_secret_required');
```

### Mengapa pola ini?

- **Centralized**: Semua setting di satu tempat
- **Environment-aware**: Bisa berbeda per development/production via `.env`
- **Type-safe**: Menggunakan array dengan key yang jelas
- **Reusable**: Import dari berbagai controller/service tanpa duplikasi

### Saat menambah fitur baru

1. Buat `config/feature_name.php` dengan struktur yang jelas
2. Definisikan default values
3. Gunakan via `config('feature_name.*')` di controller/service
4. Jangan hardcode konstanta di kode

---

## 2. Support Classes (`app/Support/`)

**Tujuan**: Mengekstrak logic cross-cutting (helper) ke class reusable.

### `app/Support/ApiResponse.php`

Static helper class untuk response JSON yang konsisten di seluruh API endpoint.

**Methods**:
- `ok($data, $message)` — 200 OK dengan data + message
- `created($data, $message)` — 201 Created dengan data + message
- `error($message, $code = 400)` — 400 Bad Request dengan error message
- `forbidden($message)` — 403 Forbidden
- `notFound($message)` — 404 Not Found
- `validationError($errors, $message = null)` — 422 Unprocessable Entity dengan validation errors

**Format response**:
```json
{
  "success": true/false,
  "data": {},
  "message": "Teks pesan"
}
```

**Contoh**:
```php
return ApiResponse::ok($todo, 'Tugas berhasil diperbarui');
return ApiResponse::forbidden('Anda tidak punya akses ke tugas ini');
```

**Keuntungan**:
- Response format konsisten di semua endpoint
- Mudah ubah format global (cukup edit satu file)
- Mengurangi duplikasi response code di controller

### `app/Support/Kuadran.php`

Enum-like class yang mendefinisikan Eisenhower Matrix kuadran + helper methods.

**Constants**:
```php
const DO_NOW = 1;        // Urgent + Important
const SCHEDULE = 2;      // Not Urgent + Important
const DELEGATE = 3;      // Urgent + Not Important
const ELIMINATE = 4;     // Not Urgent + Not Important
```

**Methods**:
- `label($kuadran)` — Label display (Indonesian)
- `shortLabel($kuadran)` — Label singkat
- `color($kuadran)` — CSS color untuk rendering
- `key($kuadran)` — Key name untuk array
- `isValid($kuadran)` — Validasi apakah kuadran valid

**Contoh**:
```php
$label = Kuadran::label(Todo::KUADRAN_DO_NOW);  // "Lakukan Sekarang"
$color = Kuadran::color(Todo::KUADRAN_DO_NOW);  // "#ff5252"
```

**Keuntungan**:
- Satu source of truth untuk kuadran metadata
- Mudah translate ke bahasa lain (edit satu file)
- Prevent hardcoded string di view/JS

### Saat menambah helper baru

1. Buat file baru di `app/Support/HelperName.php`
2. Gunakan static methods atau constants
3. Import di controller/service yang membutuhkan
4. Dokumentasikan public interface dengan komentar

---

## 3. Validasi Ownership — Custom Rules (`app/Rules/`)

**Tujuan**: Memastikan user hanya bisa access/modify resource yang dia punya.

### `app/Rules/OwnedByUser.php`

Custom validation rule yang mengecek apakah foreign key milik user yang authenticated.

**Keamanan**: Mencegah authorization bypass seperti:
```
// ❌ TIDAK AMAN (tanpa ownership check)
$request->validate([
    'todo_id' => 'exists:todos,id'  // User bisa assign todo orang lain
]);

// ✅ AMAN (dengan OwnedByUser rule)
$request->validate([
    'todo_id' => ['required', new OwnedByUser('todos', 'id')]
]);
```

**Cara kerja**:
```php
new OwnedByUser($table, $column, $userColumn = 'user_id')
```
Melakukan query:
```sql
SELECT COUNT(*) FROM {$table} 
WHERE {$column} = {value} 
AND {$userColumn} = {auth_id}
```

**Contoh penggunaan di FormRequest**:
```php
class ChatRequest extends FormRequest {
    public function rules() {
        return [
            'todo_id' => [
                'sometimes',
                'nullable',
                new OwnedByUser('todos', 'id')
            ]
        ];
    }
}
```

**Keuntungan**:
- Validasi ownership di FormRequest, bukan manual di controller
- Reusable di berbagai endpoint
- Error message konsisten

### Pattern ini berlaku untuk:

- `todo_id` → verify against `todos` table
- `category_id` → verify against `categories` table
- `course_id` → verify against `courses` table
- Setiap foreign key yang sensitive

### Saat membuat endpoint baru yang menerima FK

1. Gunakan `OwnedByUser` rule di FormRequest
2. Jangan andalkan hanya `exists:` rule
3. Dokumentasikan ownership check di formRequest docblock

---

## 4. Authorization dengan Policies (`app/Policies/`)

**Tujuan**: Centralize authorization logic untuk model tertentu.

### Pattern

1. **Buat Policy untuk model**:
```bash
php artisan make:policy TodoPolicy --model=Todo
```

2. **Implementasikan methods** sesuai action:
```php
public function view(User $user, Todo $todo): bool
public function create(User $user): bool
public function update(User $user, Todo $todo): bool
public function delete(User $user, Todo $todo): bool
```

3. **Gunakan di controller** via `$this->authorize()`:
```php
public function update(UpdateTodoRequest $request, Todo $todo) {
    $this->authorize('update', $todo);  // Cek policy
    $todo->update($request->validated());
}
```

### Existing Policies

- `AiConversationPolicy`: Cek ownership conversation + session
- `AiSuggestionPolicy`: Cek ownership suggestion

### Saat menambah model baru

1. Buat Policy (artisan command)
2. Register di `app/Providers/AuthServiceProvider.php` (Laravel otomatis kalau class-nya sesuai nama)
3. Implementasikan methods yang diperlukan
4. Gunakan `$this->authorize()` di controller

---

## 5. FormRequest Validation (`app/Http/Requests/`)

**Tujuan**: Mengekstrak validation logic dari controller ke class dedicated.

### Struktur direktori

```
app/Http/Requests/
├── Todo/
│   ├── StoreTodoRequest.php
│   ├── UpdateTodoRequest.php
│   └── ReorderTodoRequest.php
├── Category/
│   ├── StoreCategoryRequest.php
│   └── UpdateCategoryRequest.php
├── Ai/
│   ├── ChatRequest.php
│   └── ConfirmTasksRequest.php
```

### Contoh: `StoreTodoRequest`

```php
class StoreTodoRequest extends FormRequest {
    public function authorize(): bool {
        return true;  // Policy check di controller
    }
    
    public function rules(): array {
        return [
            'title' => 'required|string|max:255',
            'description' => 'sometimes|string|nullable',
            'priority' => 'sometimes|in:high,medium,low',
            'category_id' => [
                'sometimes',
                new OwnedByUser('categories', 'id')
            ],
            'due_date' => 'sometimes|date|after:today',
            'course_id' => [
                'sometimes',
                'nullable',
                new OwnedByUser('courses', 'id')
            ],
        ];
    }
}
```

### Keuntungan

- Controller fokus pada business logic, bukan validation
- Validation bisa di-share antar method
- Error message customizable (via `messages()` method)
- Type-safe: `$request->validated()` return array yang sudah diverifikasi

### Pattern di controller

```php
public function store(StoreTodoRequest $request) {
    $validated = $request->validated();  // Array yang sudah valid
    
    // Tidak perlu check $request->has() lagi
    Todo::create([...$validated, 'user_id' => auth()->id()]);
}
```

### Saat menambah endpoint baru

1. Buat FormRequest di direktori yang sesuai
2. Implementasikan `rules()` dengan OwnedByUser jika perlu
3. Custom messages di `messages()` jika diperlukan
4. Inject di controller method signature
5. Gunakan `$request->validated()` untuk data

---

## 6. Service Layer & Caching

**Tujuan**: Business logic besar dipindah ke Service, jauh dari controller. Expensive operations di-cache.

### Caching Pattern untuk Context-Heavy Operations

#### Contoh: `AiAssistantService::buildTaskContext()`

```php
protected function buildTaskContext(int $userId): string {
    $ttl = config('ai.context.cache_ttl');  // Default 300s = 5 menit
    $cacheKey = "user:{$userId}:ai_task_context";
    
    $resolver = function () use ($userId) {
        // Fetch todos (expensive)
        $todos = Todo::where('user_id', $userId)
            ->where('status', '!=', 'completed')
            ->limit(config('ai.context.limit'))
            ->select(['id', 'title', 'priority', 'kuadran', 'due_date', 'status'])
            ->get();
        
        // Format jadi string context
        return $this->formatTasksAsContext($todos);
    };
    
    if ($ttl <= 0) {
        return $resolver();  // No caching jika ttl=0
    }
    
    return cache()->remember($cacheKey, $ttl, $resolver);
}
```

#### Invalidating Cache saat Data Berubah

```php
// Di TodoController setelah update/delete
protected function forgetStatsCache(int $userId): void {
    cache()->forget("user:{$userId}:todo_stats:basic");
    cache()->forget("user:{$userId}:todo_stats:full");
    AiAssistantService::forgetTaskContextCache($userId);
}
```

### Contoh: Stats Aggregation dengan Cache

```php
protected function computeStats(int $userId): array {
    $ttl = config('todos.stats_cache_ttl');
    $cacheKey = "user:{$userId}:todo_stats:basic";
    
    $resolver = function () use ($userId) {
        return Todo::where('user_id', $userId)
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status != 'completed' THEN 1 ELSE 0 END) AS pending
            ")
            ->first()
            ->toArray();
    };
    
    return cache()->remember($cacheKey, $ttl, $resolver);
}
```

### Kapan Gunakan Cache

- ✅ Aggregate queries (COUNT, SUM dengan CASE WHEN)
- ✅ Context/metadata yang fetch multiple rows (AI context)
- ✅ Data yang read-heavy (stats, categories)
- ✅ External API calls (Google Classroom, Gemini)
- ❌ Jangan cache data yang frequent update
- ❌ Jangan cache sensitive data tanpa enkripsi

### Service Layer Organization

Saat service terlalu besar (>300 baris), split ke:

```
app/Services/
├── AiAssistant/
│   ├── TaskParsingService.php    # Parse AI response → todo objects
│   ├── ConversationService.php   # Load/save conversations
│   ├── PromptBuilder.php         # Build system prompt
│   └── GeminiClient.php          # Direct API communication
└── AiAssistantService.php        # Orchestrator
```

---

## 7. Controller Pattern

**Tujuan**: Controller hanya orchestrate, jangan business logic.

### Lean Controller

```php
public function store(StoreTodoRequest $request) {
    // 1. Authorize (via Policy)
    $this->authorize('create', Todo::class);
    
    // 2. Get validated data
    $validated = $request->validated();
    
    // 3. Call service (jika ada)
    $todo = Todo::create([...$validated, 'user_id' => auth()->id()]);
    
    // 4. Invalidate cache
    $this->forgetStatsCache(auth()->id());
    
    // 5. Response
    return ApiResponse::created($todo, 'Tugas berhasil dibuat');
}
```

### Response Format

Selalu gunakan `ApiResponse` helper untuk consistency:

```php
return ApiResponse::ok($data, 'Success message');          // 200
return ApiResponse::created($data, 'Created message');     // 201
return ApiResponse::error('Error message', 400);           // 400
return ApiResponse::forbidden('Not allowed');              // 403
return ApiResponse::notFound('Resource not found');        // 404
return ApiResponse::validationError($errors);              // 422
```

### Pattern untuk List dengan Pagination

```php
public function index(Request $request) {
    $query = Todo::where('user_id', auth()->id());
    
    // Filters
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
    
    // Paginate
    $todos = $query->paginate(config('todos.per_page'))->withQueryString();
    
    // Response
    return view('todos.index', compact('todos'));
}
```

---

## 8. Database Indexing Strategy

**Tujuan**: Optimize query performance dengan composite indexes.

### Migration: `2026_04_20_000001_add_performance_indexes.php`

Composite indexes dibuat pada column combinations yang frequently queried bersama:

```php
$table->index(['user_id', 'status'], 'todos_user_status_idx');
$table->index(['user_id', 'due_date'], 'todos_user_due_date_idx');
$table->index(['user_id', 'kuadran', 'status'], 'todos_user_kuadran_idx');
```

### Query Optimization Benefits

**Sebelum** (tanpa index):
```
SELECT * FROM todos WHERE user_id = 1 AND status = 'todo'
→ Full table scan: O(n)
```

**Sesudah** (dengan index `(user_id, status)`):
```
→ Index range scan: O(log n)
```

### Index Order Matters

Untuk index `(user_id, status)`:
- ✅ `WHERE user_id = 1 AND status = 'todo'` — bisa gunakan index sepenuhnya
- ✅ `WHERE user_id = 1` — bisa gunakan index partial (user_id part)
- ❌ `WHERE status = 'todo'` — TIDAK bisa gunakan index (status bukan first column)

### Saat menambah query baru

1. Gunakan Laravel Debugbar/Telescope untuk inspect queries
2. Jika query sering dijalankan dengan same WHERE columns, pertimbangkan index
3. Index order: `(user_id, ..., frequent_filter_column)`
4. Avoid over-indexing (slow inserts, boros disk)

---

## 9. Keamanan: Secrets & Sensitive Data

### ✅ Setelah Refactoring

1. **Secrets di `.env`**: Google OAuth, Gemini key, Telegram token, webhook secret
2. **Tidak di-commit**: `.env` ada di `.gitignore`
3. **Webhook verification**: `TELEGRAM_WEBHOOK_SECRET` wajib di production
4. **Ownership validation**: `OwnedByUser` rule mencegah cross-user access
5. **Authorization**: Policy + `$this->authorize()` mencegah unauthorized action

### ⚠️ Future Improvements (Fase 2)

1. **Encrypt sensitive tokens**: Google `access_token`, `refresh_token`, `telegram_chat_id` → gunakan Eloquent `encrypted` cast
2. **Rotate secrets**: Bila `.env` pernah ter-commit
3. **Audit logging**: Track siapa yang access sensitive data
4. **Rate limiting**: Protect endpoint dari brute force

---

## 10. Query Optimization Techniques

### Aggregate Queries dengan CASE WHEN

**Menggabungkan multiple COUNT queries**:

```php
// ❌ 6 query terpisah (N+1 pattern)
$total = Todo::where('user_id', $userId)->count();
$completed = Todo::where('user_id', $userId)->where('status', 'completed')->count();
$pending = Todo::where('user_id', $userId)->where('status', '!=', 'completed')->count();
// ... lebih banyak lagi

// ✅ 1 query dengan CASE WHEN
$stats = Todo::where('user_id', $userId)
    ->selectRaw("
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status != 'completed' THEN 1 ELSE 0 END) AS pending
    ")
    ->first();
```

### Grouping in PHP vs Database

```php
// ❌ 4 query terpisah
$doNow = Todo::where('user_id', $userId)->where('kuadran', 1)->get();
$schedule = Todo::where('user_id', $userId)->where('kuadran', 2)->get();
$delegate = Todo::where('user_id', $userId)->where('kuadran', 3)->get();
$eliminate = Todo::where('user_id', $userId)->where('kuadran', 4)->get();

// ✅ 1 query, group di PHP
$todos = Todo::where('user_id', $userId)
    ->whereIn('kuadran', [1, 2, 3, 4])
    ->get()
    ->groupBy('kuadran');

$doNow = $todos->get(1, collect());
$schedule = $todos->get(2, collect());
```

---

## 11. Dokumentasi & Future Contributors

### Bagian untuk README/New Dev Onboarding

```markdown
## Getting Started

1. Clone repo
2. `composer install` && `npm install`
3. Copy `.env.example` → `.env`
4. `php artisan key:generate`
5. `php artisan migrate`
6. Start dev server: `php artisan serve`

## Architecture Overview

Lihat `ARCHITECTURE.md` untuk:
- Pola development yang digunakan
- Bagaimana menambah fitur baru
- Optimization techniques
```

### Pre-commit Hooks (Optional)

Untuk prevent secrets commit:
```bash
# .git/hooks/pre-commit
git diff-index --check --cached HEAD -- '*.php' '*.js' '*.env'
```

---

## 12. Checklist untuk Fitur Baru

Saat menambah fitur baru, ensure:

### Security
- [ ] Gunakan `FormRequest` dengan OwnedByUser rules untuk FK
- [ ] Gunakan Policy + `$this->authorize()` untuk sensitive actions
- [ ] Validasi input di boundary (request), jangan trust internal code
- [ ] Jangan hardcode secrets

### Performance
- [ ] Check query count dengan Laravel Debugbar (target: <10 queries per page)
- [ ] Gunakan eager loading (`.with()`) untuk relations
- [ ] Aggregate queries dengan `selectRaw()` untuk stats
- [ ] Cache expensive operations (lihat pattern di AiAssistantService)
- [ ] Add composite index jika frequent WHERE combinations

### Code Quality
- [ ] Gunakan FormRequest untuk validation
- [ ] Gunakan ApiResponse helper untuk response
- [ ] Controller max ~200 lines, split ke Service jika lebih besar
- [ ] Dokumentasikan public interface dengan komentar singkat
- [ ] Gunakan config file untuk konstanta, bukan hardcode

### UX/UI
- [ ] Response via ApiResponse (consistent format)
- [ ] Validasi error ditampilkan dengan field-level error
- [ ] Loading state ditampilkan untuk async operations
- [ ] Empty state ditampilkan saat tidak ada data
- [ ] Success/error toast atau notification

### Testing
- [ ] Feature tests untuk happy path
- [ ] Test authorization (Policy)
- [ ] Test validation (FormRequest)
- [ ] Test caching invalidation

---

## 13. Troubleshooting & FAQ

### Q: Bagaimana jika user coba akses todo orang lain?

**A**: Beberapa layer protection:
1. FormRequest: `OwnedByUser` validation di `todo_id` field
2. Controller: `$this->authorize('view', $todo)` check Policy
3. Model scope: Query selalu `->where('user_id', auth()->id())`

### Q: Cache invalidation—kapan saya forget cache?

**A**: Setiap kali ada CREATE/UPDATE/DELETE yang affect cache key. Contoh:
```php
public function store(StoreTodoRequest $request) {
    $todo = Todo::create([...]);
    $this->forgetStatsCache(auth()->id());  // Invalidate cache
}
```

### Q: Service terlalu besar, bagaimana split?

**A**: Lihat section 6 (Service Layer). Kriteria split:
- File > 300 baris
- >5 public methods yang unrelated
- Bisa extract ke class standalone (TaskParsingService, ConversationService, dll)

### Q: Bagaimana performance debugging?

**A**: Gunakan tools:
- **Laravel Debugbar**: `php artisan tinker` + `DB::listen()`
- **Telescope**: `php artisan telescope:install`
- **Query Profiler**: `.with(function($q) { $q->addSelect(DB::raw('...')) })`

---

## Kontribusi & Improvement

Roadmap Fase 2 (belum diimplementasikan):

1. **Service Refactoring**:
   - `TelegramBotService` (951 lines) → split ke CommandHandler, MessageProcessor
   - `AiAssistantService` (485 lines) → split ke TaskParsingService, ConversationService
   - `GoogleClassroomService` (434 lines) → split ke API client vs sync engine

2. **Frontend Modernization**:
   - Global toast component (replace `alert()`)
   - Optimistic updates (replace full `location.reload()`)
   - Streaming AI response (replace polling)
   - Session sidebar untuk conversation history

3. **Security Hardening**:
   - Encrypt Google tokens + Telegram chat_id dengan Eloquent `encrypted` cast
   - Add rate limiting di webhook endpoints
   - Audit logging untuk sensitive operations
   - Secret rotation policy

4. **Performance Improvements**:
   - Classroom sync batching + caching (currently 100+ API calls)
   - Conversation history pagination (currently load all)
   - Classroom todos pagination
   - Pre-computed stats per hourly batch job

5. **UX Polish**:
   - Classroom sync progress indicator + job status polling
   - Google token expiry warning + silent refresh flow
   - Telegram user onboarding flow (help → setup)
   - Standardize language (all UI ke Indonesian, code English)

---

## Dokumen Referensi

- [Laravel Policies](https://laravel.com/docs/authorization#creating-policies)
- [FormRequest Validation](https://laravel.com/docs/validation#form-request-validation)
- [Custom Validation Rules](https://laravel.com/docs/validation#custom-validation-rules)
- [Caching](https://laravel.com/docs/cache)
- [Query Optimization](https://laravel.com/docs/queries)

---

**Last Updated**: 20 April 2026 | **Version**: 1.0 | **Author**: Architecture Refactoring Phase 1
