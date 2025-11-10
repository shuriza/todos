<?php
/**
 * Database Creator Script
 * Run: php create-database.php
 */

$host = '127.0.0.1';
$user = 'root';
$password = '';
$dbname = 'todos_ai';

try {
    // Connect to MySQL without database
    $pdo = new PDO("mysql:host=$host", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "✅ Database '$dbname' created successfully!\n";
    echo "\nNext steps:\n";
    echo "1. php artisan migrate\n";
    echo "2. php artisan serve\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    echo "Make sure MySQL is running and credentials are correct in .env file\n";
    exit(1);
}
