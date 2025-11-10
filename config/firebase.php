<?php

return [
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'database_url' => env('FIREBASE_DATABASE_URL'),
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
    'credentials' => env('FIREBASE_CREDENTIALS', storage_path('firebase-credentials.json')),
    
    // For mobile app
    'api_key' => env('FIREBASE_API_KEY'),
    'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
    'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
    'app_id' => env('FIREBASE_APP_ID'),
];
