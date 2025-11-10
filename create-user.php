<?php
/**
 * Quick User Creator
 * Run: php create-user.php [name] [email] [password]
 * Example: php create-user.php "John Doe" john@example.com mypassword
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Get arguments
$name = $argv[1] ?? 'Demo User';
$email = $argv[2] ?? 'demo@example.com';
$password = $argv[3] ?? 'password';

try {
    // Check if user exists
    $existing = User::where('email', $email)->first();
    
    if ($existing) {
        echo "⚠️  User with email '$email' already exists!\n\n";
        echo "Try to login with:\n";
        echo "Email: $email\n";
        exit(0);
    }
    
    // Create user
    $user = User::create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
        'email_verified_at' => now(),
    ]);
    
    echo "\n✅ User created successfully!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "👤 Name: {$user->name}\n";
    echo "📧 Email: {$user->email}\n";
    echo "🔑 Password: {$password}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo "🚀 Now you can login at: http://localhost:8000/login\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
