# 📝 Todo × AI Assistant

> Alternatif Notion yang hemat! Todo app dengan AI Assistant menggunakan OpenAI ChatGPT

![Laravel](https://img.shields.io/badge/Laravel-12.x-red)
![PHP](https://img.shields.io/badge/PHP-8.3+-blue)
![OpenAI](https://img.shields.io/badge/OpenAI-ChatGPT-green)
![License](https://img.shields.io/badge/License-MIT-green)

## 🎯 Motivation

Notion udah mulai mahal untuk AI nya. Saya kaum kalok ada gratis dan efisien kita sikat wkwk hemat cost! Jadi jangan samakan dengan kalian yg berani jor-joran dana buat beli premium nya AI.

Jadinya buat web nya sendiri deh, saya juga bisa custom sesuai kemauan saya dalam membuat planner dan idea dengan bantuan AI ASISTEN yg saya buat. Lumayan punya temen diskusi buat ngejalanin hari-hari kedepan nya.

## ✨ Features

- ✅ **Todo Management** - CRUD todos with priorities, categories, due dates
- 🤖 **AI Assistant** - Chat dengan OpenAI ChatGPT (GPT-4o-mini)
- 📊 **Statistics Dashboard** - Track your progress dengan visual cards
- 🎨 **Modern UI** - Clean design dengan Tailwind CSS + Alpine.js
- 🔐 **Authentication** - Secure login dengan Laravel Breeze
- 📱 **Mobile Ready** - Responsive & ready for Android/iOS webview
- 🔥 **Firebase Support** - Configuration untuk mobile app
- 💾 **MySQL Database** - Reliable & scalable
- 🚀 **Affordable AI** - OpenAI GPT-4o-mini (~$0.0004 per chat)

## 🛠️ Tech Stack

### Backend

- **Laravel 12** - PHP Framework
- **MySQL** - Database
- **Laravel Breeze** - Authentication

### Frontend

- **Blade Templates** - Server-side rendering
- **Tailwind CSS** - Utility-first CSS
- **Alpine.js** - Lightweight JavaScript framework

### AI Integration

- **OpenAI** - ChatGPT API
- **GPT-4o-mini** - Fast & cost-effective model
- **Custom AI Service** - Laravel service untuk AI chat & suggestions

### Future Mobile

- **Firebase** - Push notifications, analytics
- **WebView** - Android & iOS native wrapper

## 📦 Installation

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM
- SQLite extension enabled

### Setup

1. **Clone repository**

```bash
git clone https://github.com/shuriza/todos.git
cd todos
```

2. **Install dependencies**

```bash
composer install
npm install
```

3. **Environment setup**

```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure OpenRouter API**

Get free API key dari [OpenRouter](https://openrouter.ai/):

```env
OPENROUTER_API_KEY=your-api-key-here
OPENROUTER_MODEL=deepseek/deepseek-r1
```

5. **Run migrations**

```bash
php artisan migrate
```

6. **Build assets**

```bash
npm run build
# or for development
npm run dev
```

7. **Start server**

```bash
php artisan serve
```

Visit: `http://localhost:8000`

## 🚀 Usage

### Create Account

1. Click "Register" on homepage
2. Fill in your details
3. Login & start managing todos!

### Add Todos

- Quick add from the input field
- Set priority (Low, Medium, High)
- Add due date
- Categorize tasks

### AI Assistant

- Click "AI Assistant" in sidebar
- Chat dengan AI untuk:
  - Daily planning & time management
  - Task breakdown & subtasks
  - Productivity tips & strategies
  - Priority recommendations
  - General productivity advice
- Get AI suggestions untuk specific todos
- Review conversation history

### Quick Actions

- ✅ Check/uncheck to complete todos
- 📝 Edit todos dengan hover actions
- 🗑️ Delete unwanted tasks
- 📊 View statistics on dashboard
- 🔍 Filter by date (Hari Ini, 7 Hari, 30 Hari, Semua)
- 🏷️ Filter by category (Pekerjaan, Kuliah, Daily Activity)

## 💰 Cost Breakdown

**Total: ~$5-10/month** 🎉

- **Laravel Hosting**: $5/month (VPS shared)
- **MySQL Database**: FREE (included in VPS)
- **OpenAI API**: ~$0.10-5/month (depending usage)
  - GPT-4o-mini: $0.150 per 1M input tokens
  - GPT-4o-mini: $0.600 per 1M output tokens
  - Estimasi: 1000 chats = ~$0.40
- **Domain**: ~$10/year (opsional)

**VS Notion AI**: $10-20/month per user 😱

**Saving**: 50-80% cheaper! 🚀
- **OpenRouter DeepSeek R1**: **FREE** ✨
- **Firebase (Spark Plan)**: **FREE**
- **Domain**: ~$10/year

**Total: ~$5-6/month** vs Notion AI Plus ($10/month per user)

## 📱 Mobile App (Coming Soon)

Ready untuk deployment as Android & iOS app menggunakan WebView + Firebase.

See [MOBILE_SETUP.md](MOBILE_SETUP.md) for detailed instructions.

## 🙏 Credits

Proses buat cuma 2 hari thanks to:

- **Claude Sonnet 4.5** - AI pair programming
- **Grok 4** - Problem solving
- **Droid + Cursor** - Code editor
- **MCP Context7** - Context management
- **OpenRouter** - AI gateway (DeepSeek R1 free!)

## 👨‍💻 Author

**Shuriza**

- GitHub: [@shuriza](https://github.com/shuriza)
- Facebook: [Post about this project](https://www.facebook.com/share/v/1BvZBNfweA/)

---

Made with ❤️ and ☕ by someone who loves free stuff!

**Kalau suka, kasih star ya! ⭐**

## 🔮 Roadmap

- [ ] Dark mode
- [ ] Drag & drop todo reordering
- [ ] Categories management UI
- [ ] Tags system
- [ ] Export todos (PDF, CSV)
- [ ] Recurring tasks
- [ ] Team collaboration
- [ ] Android app (WebView)
- [ ] iOS app (WebView)
- [ ] Push notifications
- [ ] Offline support
- [ ] Multi-language support

## 💡 Tips

1. **API Key**: Daftar OpenRouter, verifikasi email, langsung dapat free credits!
2. **Hosting**: Pake VPS murah aja (DigitalOcean, Vultr, Hetzner)
3. **Domain**: Freenom atau domain murah dari Namecheap
4. **SSL**: Let's Encrypt (gratis!)
5. **Backup**: Setup automatic DB backup ke Google Drive

## ❓ FAQ

**Q: Apakah benar-benar gratis?**
A: DeepSeek R1 via OpenRouter gratis! Hosting aja yang bayar (~$5/month)

**Q: Berapa limit API calls?**
A: Check OpenRouter dashboard untuk free tier limits

**Q: Bisa dipakai tim?**
A: Saat ini per-user, tapi bisa dikembangkan untuk team collaboration

**Q: Mobile app kapan release?**
A: Setup udah ready, tinggal build APK/IPA. Soon!

**Q: Kenapa pakai DeepSeek R1?**
A: Free, punya reasoning capability, dan performanya bagus untuk task planning!

---

**"If it's free and effective, we take it!" 😎**

## 📄 License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
