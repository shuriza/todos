# Firebase Configuration for Mobile App (Android & iOS)

This project is ready for mobile deployment using Firebase and WebView.

## Setup Instructions

### 1. Create Firebase Project
1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Create a new project: "Todo AI Assistant"
3. Enable Authentication (Email/Password)
4. Enable Firestore (optional for offline sync)
5. Enable Cloud Messaging (for push notifications)

### 2. Android App Setup

#### Add Firebase to Android Project
1. Download `google-services.json` from Firebase Console
2. Place it in `android/app/` directory
3. Add Firebase SDK to `android/app/build.gradle`:

```gradle
dependencies {
    implementation platform('com.google.firebase:firebase-bom:32.7.0')
    implementation 'com.google.firebase:firebase-analytics'
    implementation 'com.google.firebase:firebase-messaging'
}
```

#### WebView Configuration
```kotlin
// MainActivity.kt
class MainActivity : AppCompatActivity() {
    private lateinit var webView: WebView
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)
        
        webView = findViewById(R.id.webView)
        webView.settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            databaseEnabled = true
        }
        
        // Load your Laravel app
        webView.loadUrl("https://your-app-url.com")
    }
}
```

### 3. iOS App Setup

#### Add Firebase to iOS Project
1. Download `GoogleService-Info.plist` from Firebase Console
2. Add it to your Xcode project
3. Install Firebase SDK via CocoaPods:

```ruby
pod 'Firebase/Analytics'
pod 'Firebase/Messaging'
```

#### WKWebView Configuration
```swift
// ViewController.swift
import UIKit
import WebKit

class ViewController: UIViewController {
    var webView: WKWebView!
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        let webConfiguration = WKWebViewConfiguration()
        webView = WKWebView(frame: .zero, configuration: webConfiguration)
        view = webView
        
        let url = URL(string: "https://your-app-url.com")!
        webView.load(URLRequest(url: url))
    }
}
```

### 4. Laravel API Configuration

Update `.env` for Firebase:
```env
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_DATABASE_URL=your-database-url
FIREBASE_STORAGE_BUCKET=your-storage-bucket
```

### 5. Push Notifications

#### Server Side (Laravel)
Install Firebase Admin SDK:
```bash
composer require kreait/firebase-php
```

Send notifications:
```php
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

$messaging = (new Factory)
    ->withServiceAccount(storage_path('firebase-credentials.json'))
    ->createMessaging();

$message = CloudMessage::withTarget('token', $deviceToken)
    ->withNotification([
        'title' => 'Task Reminder',
        'body' => 'You have 3 tasks due today!'
    ]);

$messaging->send($message);
```

### 6. Offline Support

Add service worker for PWA support:
```javascript
// public/sw.js
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open('todo-ai-v1').then((cache) => {
            return cache.addAll([
                '/',
                '/todos',
                '/ai',
                '/build/assets/app.css',
                '/build/assets/app.js'
            ]);
        })
    );
});
```

### 7. Build Commands

#### Android
```bash
cd android
./gradlew assembleRelease
# APK will be in: app/build/outputs/apk/release/
```

#### iOS
```bash
cd ios
xcodebuild -workspace TodoAI.xcworkspace -scheme TodoAI archive
# Export IPA from Xcode Organizer
```

### 8. Environment Variables for Mobile

Create `.env.mobile`:
```env
API_URL=https://your-app-url.com
FIREBASE_API_KEY=your-api-key
FIREBASE_AUTH_DOMAIN=your-auth-domain
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_STORAGE_BUCKET=your-storage-bucket
FIREBASE_MESSAGING_SENDER_ID=your-sender-id
FIREBASE_APP_ID=your-app-id
```

## Features Ready for Mobile

✅ Responsive design (Tailwind CSS)
✅ API endpoints for mobile consumption
✅ CORS enabled
✅ JWT authentication ready
✅ PWA capable
✅ Offline-first architecture ready
✅ Push notification endpoints

## Next Steps

1. Deploy Laravel app to production server
2. Configure Firebase project
3. Build Android APK & iOS IPA
4. Submit to Play Store & App Store
5. Enable analytics & crash reporting

## Cost Optimization

- Use Firebase free tier (Spark Plan)
- OpenRouter DeepSeek R1 is FREE
- Laravel hosting on cheap VPS ($5/month)
- Total cost: **~$5/month** 🎉

Selamat! Project siap untuk mobile deployment! 🚀
