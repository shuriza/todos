# 📁 Project Structure

```
todos/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── AiAssistantController.php    # AI chat & suggestions
│   │       ├── CategoryController.php       # Category CRUD
│   │       └── TodoController.php           # Todo CRUD & stats
│   ├── Models/
│   │   ├── AiConversation.php              # AI chat history
│   │   ├── AiSuggestion.php                # AI suggestions
│   │   ├── Category.php                    # Task categories
│   │   ├── Todo.php                        # Todo items
│   │   └── User.php                        # Users
│   ├── Policies/
│   │   ├── CategoryPolicy.php              # Category authorization
│   │   └── TodoPolicy.php                  # Todo authorization
│   └── Services/
│       └── AiAssistantService.php          # AI integration logic
│
├── config/
│   ├── firebase.php                        # Firebase configuration
│   └── services.php                        # Third-party services (OpenRouter)
│
├── database/
│   ├── migrations/
│   │   ├── 2024_11_10_000001_create_categories_table.php
│   │   ├── 2024_11_10_000002_create_todos_table.php
│   │   ├── 2024_11_10_000003_create_ai_conversations_table.php
│   │   └── 2024_11_10_000004_create_ai_suggestions_table.php
│   └── database.sqlite                     # SQLite database
│
├── resources/
│   ├── views/
│   │   ├── ai/
│   │   │   └── index.blade.php            # AI Assistant interface
│   │   ├── todos/
│   │   │   └── index.blade.php            # Todo dashboard
│   │   ├── auth/                          # Laravel Breeze auth views
│   │   ├── layouts/                       # Layout components
│   │   └── profile/                       # User profile
│   ├── css/
│   │   └── app.css                        # Tailwind CSS
│   └── js/
│       └── app.js                         # Alpine.js & interactions
│
├── routes/
│   ├── web.php                            # Web routes
│   └── auth.php                           # Authentication routes
│
├── public/
│   ├── build/                             # Compiled assets
│   ├── index.php                          # Entry point
│   └── robots.txt
│
├── storage/
│   ├── app/
│   ├── framework/
│   └── logs/
│       └── laravel.log                    # Application logs
│
├── .env                                   # Environment variables
├── .env.example                           # Environment template
├── .gitignore                             # Git ignore rules
├── artisan                                # Laravel CLI
├── composer.json                          # PHP dependencies
├── package.json                           # Node dependencies
├── vite.config.js                         # Vite configuration
│
└── Documentation/
    ├── README.md                          # Main documentation
    ├── QUICKSTART.md                      # Quick start guide
    ├── DEPLOYMENT.md                      # Deployment guide
    ├── MOBILE_SETUP.md                    # Mobile app guide
    ├── CHANGELOG.md                       # Version history
    └── PROJECT_STRUCTURE.md               # This file
```

## Key Components

### Backend (Laravel)

#### Controllers
- **TodoController**: Manage todos (CRUD, reorder, statistics)
- **CategoryController**: Manage categories
- **AiAssistantController**: AI chat and suggestions

#### Models
- **Todo**: Todo items with priorities, status, due dates
- **Category**: Task categories with colors and icons
- **AiConversation**: Chat history with AI
- **AiSuggestion**: AI-generated task suggestions
- **User**: User accounts

#### Services
- **AiAssistantService**: 
  - OpenRouter API integration
  - DeepSeek R1 model
  - Chat functionality
  - Suggestion generation
  - Daily planning

### Frontend

#### Views (Blade)
- **todos/index**: Main dashboard with todo list
- **ai/index**: AI assistant chat interface
- **auth/***: Login, register, password reset
- **layouts/app**: Main application layout

#### Assets
- **Tailwind CSS**: Utility-first styling
- **Alpine.js**: Reactive interactions
- **Custom JS**: Todo management, AI chat

### Database Schema

#### users
- id, name, email, password, timestamps

#### categories
- id, user_id, name, color, icon, order, timestamps

#### todos
- id, user_id, category_id
- title, description
- priority (low/medium/high)
- status (todo/in_progress/completed)
- due_date, completed_at
- tags (JSON), order
- timestamps

#### ai_conversations
- id, user_id, todo_id
- session_id, role (user/assistant/system)
- message, metadata (JSON)
- timestamps

#### ai_suggestions
- id, user_id, todo_id
- type, suggestion
- is_applied, applied_at
- timestamps

## Routes

### Web Routes
```
GET  /                    → redirect to /todos
GET  /todos               → TodoController@index
POST /todos               → TodoController@store
PUT  /todos/{todo}        → TodoController@update
DELETE /todos/{todo}      → TodoController@destroy
POST /todos/reorder       → TodoController@reorder
GET  /todos/statistics    → TodoController@statistics

GET  /categories          → CategoryController@index
POST /categories          → CategoryController@store
PUT  /categories/{cat}    → CategoryController@update
DELETE /categories/{cat}  → CategoryController@destroy

GET  /ai                  → AiAssistantController@index
POST /ai/chat             → AiAssistantController@chat
GET  /ai/history/{sid}    → AiAssistantController@history
GET  /ai/sessions         → AiAssistantController@sessions
GET  /ai/suggestions/{id} → AiAssistantController@suggestions
GET  /ai/daily-planning   → AiAssistantController@dailyPlanning
```

### Auth Routes (Laravel Breeze)
- Login, Register, Logout
- Password Reset
- Email Verification
- Profile Management

## API Integration

### OpenRouter (AI)
- **Endpoint**: https://openrouter.ai/api/v1/chat/completions
- **Model**: deepseek/deepseek-r1 (free)
- **Features**: 
  - Chat completion
  - Reasoning capability
  - Context management

### Firebase (Mobile - Future)
- Authentication
- Cloud Messaging
- Analytics
- Firestore (optional)

## Configuration Files

### .env
```env
APP_NAME="Todo × AI Assistant"
APP_ENV=local|production
APP_DEBUG=true|false
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
OPENROUTER_API_KEY=sk-or-v1-...
OPENROUTER_MODEL=deepseek/deepseek-r1

FIREBASE_PROJECT_ID=...
FIREBASE_API_KEY=...
```

### composer.json
- Laravel 12.x
- Laravel Breeze
- Guzzle HTTP

### package.json
- Vite
- Tailwind CSS
- Alpine.js

## Development Workflow

1. **Backend Development**
   ```bash
   php artisan serve
   ```

2. **Frontend Development**
   ```bash
   npm run dev
   ```

3. **Database Changes**
   ```bash
   php artisan make:migration create_table_name
   php artisan migrate
   ```

4. **Clear Caches**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

## Code Organization

### Follows Laravel Best Practices
- ✅ MVC Architecture
- ✅ Service Layer Pattern
- ✅ Repository Pattern (Models)
- ✅ Policy-based Authorization
- ✅ Dependency Injection
- ✅ RESTful API design
- ✅ Blade Components
- ✅ Database Migrations
- ✅ Environment Configuration

### Custom Additions
- AI Service Layer
- Alpine.js Components
- Custom Tailwind Configuration
- Firebase Integration Structure

## Testing Structure (Future)

```
tests/
├── Feature/
│   ├── TodoTest.php
│   ├── CategoryTest.php
│   └── AiAssistantTest.php
└── Unit/
    └── AiAssistantServiceTest.php
```

## Deployment Structure

```
Production Server:
/var/www/todos/
├── Current (symlink)
├── Releases/
│   ├── 20251110_120000/
│   └── 20251110_130000/
├── Storage/ (shared)
└── .env (shared)
```

## Mobile App Structure (Future)

```
mobile/
├── android/
│   ├── app/
│   │   ├── src/
│   │   └── build.gradle
│   └── google-services.json
└── ios/
    ├── TodoAI/
    ├── TodoAI.xcworkspace
    └── GoogleService-Info.plist
```

---

**Architecture Philosophy**: Keep it simple, scalable, and maintainable! 🚀
