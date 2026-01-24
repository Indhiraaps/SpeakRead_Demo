<?php
// Centralized Database Configuration
// IMPORTANT: Update this password to match YOUR database
$host = 'localhost';
$dbname = 'speakread_db';
$user = 'root';
$pass = '12345678'; // CHANGE THIS TO YOUR PASSWORD

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>