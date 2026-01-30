<?php
// Centralized Database Configuration for Aiven Cloud
$host = 'mysql-19588968-speakread000.g.aivencloud.com';
$port = '25249'; // Aiven's specific port from your screenshot
$dbname = 'defaultdb'; // Aiven's default database name
$user = 'avnadmin'; 
$pass = 'AVNS_-hJYen-fDyBu9ApXbxH'; // Click the copy icon in Aiven for this

try {
    // The DSN string now includes the 'port' parameter for cloud connectivity
    // Change this line:
$pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // If this fails, double-check that your Aiven service status is "Running"
    die("Cloud database connection failed: " . $e->getMessage());
}