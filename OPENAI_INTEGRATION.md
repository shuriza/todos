# OpenAI ChatGPT Integration

## Overview
Todo × AI Assistant menggunakan OpenAI ChatGPT API untuk memberikan bantuan AI dalam mengelola todos, planning, dan productivity assistance.

## Configuration

### 1. Environment Variables (.env)
```env
OPENAI_API_KEY=your-openai-api-key-here
OPENAI_MODEL=gpt-4o-mini
OPENAI_MAX_TOKENS=1000
```

### 2. Supported Models
- **gpt-4o-mini** (Recommended) - Cepat, efisien, dan cost-effective
- **gpt-4o** - Model terkuat untuk reasoning yang kompleks
- **gpt-3.5-turbo** - Model lama yang masih reliable

### 3. Configuration (config/services.php)
```php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'max_tokens' => env('OPENAI_MAX_TOKENS', 1000),
],
```

## Features

### 1. AI Chat Assistant
Pengguna dapat chat dengan AI untuk:
- Meminta saran productivity
- Planning harian
- Breakdown task kompleks
- Time management tips

**Endpoint**: `POST /ai/chat`

**Request**:
```json
{
  "message": "Bagaimana cara saya menyelesaikan tugas kuliah yang menumpuk?",
  "session_id": "optional-session-id",
  "todo_id": null
}
```

**Response**:
```json
{
  "success": true,
  "message": "AI response here...",
  "session_id": "session_123"
}
```

### 2. Todo Suggestions
AI menganalisis todo dan memberikan saran:
- Task breakdown
- Time estimation
- Priority recommendation
- Related tasks

**Endpoint**: `GET /ai/suggestions/{todoId}`

### 3. Daily Planning
AI membantu planning harian berdasarkan todos aktif:
- Prioritized action plan
- Time management suggestions
- Productivity tips
- Focus areas

**Endpoint**: `GET /ai/daily-planning`

## Usage Example

### Chat with AI
```javascript
const response = await fetch('/ai/chat', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({
        message: 'Help me prioritize my tasks today',
        session_id: currentSessionId
    })
});
```

### Get Todo Suggestions
```javascript
const response = await fetch(`/ai/suggestions/${todoId}`);
const data = await response.json();
```

### Get Daily Planning
```javascript
const response = await fetch('/ai/daily-planning');
const data = await response.json();
```

## Service Class

### AiAssistantService
Located at: `app/Services/AiAssistantService.php`

**Methods**:
- `chat(string $message, int $userId, ?string $sessionId, ?int $todoId)` - Chat dengan AI
- `generateSuggestions(int $todoId, int $userId)` - Generate suggestions untuk todo
- `getDailyPlanning(int $userId)` - Dapatkan daily planning assistance

## Database Schema

### ai_conversations
Menyimpan history chat dengan AI:
```sql
- id
- user_id
- todo_id (nullable)
- session_id
- role (user/assistant)
- message
- metadata (JSON)
- created_at
- updated_at
```

### ai_suggestions
Menyimpan AI suggestions untuk todos:
```sql
- id
- user_id
- todo_id
- type (task_analysis, priority, etc)
- suggestion (text)
- is_applied (boolean)
- created_at
- updated_at
```

## Best Practices

### 1. Token Management
- Set `max_tokens` sesuai kebutuhan (default: 1000)
- Monitor usage via OpenAI dashboard
- Gunakan model yang sesuai dengan budget

### 2. Error Handling
Service sudah handle:
- API timeout (60s)
- Network errors
- Invalid responses
- Rate limiting

### 3. Cost Optimization
- Gunakan `gpt-4o-mini` untuk efisiensi cost
- Limit `max_tokens` untuk kontrol spending
- Cache responses jika memungkinkan
- Batch requests ketika possible

### 4. Security
- ✅ API key disimpan di .env (tidak commit ke git)
- ✅ User authentication required
- ✅ Input validation dan sanitization
- ✅ Rate limiting di route level

## Pricing (OpenAI GPT-4o-mini)
- **Input**: $0.150 per 1M tokens
- **Output**: $0.600 per 1M tokens

**Estimasi**:
- 1 chat message (~500 tokens) = $0.0004
- 1000 messages = ~$0.40
- Sangat affordable untuk personal use!

## Testing

### Test AI Chat
```bash
curl -X POST http://localhost:8000/ai/chat \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-csrf-token" \
  -d '{"message": "Hello AI!"}'
```

### Check Configuration
```bash
php artisan tinker
>>> config('services.openai')
```

## Troubleshooting

### Error: "API key not found"
- Pastikan `OPENAI_API_KEY` sudah diset di .env
- Run `php artisan config:clear`

### Error: "Rate limit exceeded"
- Check OpenAI dashboard untuk quota
- Implement request throttling
- Upgrade OpenAI plan jika perlu

### Error: "Model not found"
- Verifikasi model name yang valid
- Check OpenAI docs untuk available models

## Migration from OpenRouter

Aplikasi ini sebelumnya menggunakan OpenRouter dengan DeepSeek R1 (free model). 
Perubahan ke OpenAI:

1. ✅ Updated `.env` configuration
2. ✅ Updated `config/services.php`
3. ✅ Updated `AiAssistantService.php`
4. ✅ Changed API endpoint ke OpenAI
5. ✅ Added `max_tokens` configuration
6. ✅ Updated system prompt

## Future Enhancements
- [ ] Stream responses untuk real-time output
- [ ] Function calling untuk advanced features
- [ ] Vision API untuk image analysis
- [ ] Voice input/output integration
- [ ] Custom fine-tuning untuk domain specific
- [ ] Multi-language support enhancement

## Support
Untuk issue atau pertanyaan, silakan buat issue di GitHub repository.

---
**Last Updated**: November 10, 2025
**Author**: Shuriza
**Version**: 1.0.0
