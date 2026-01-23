<?php
$host = 'localhost';
$user = 'root';
$pass = '12345678'; // CHANGE THIS TO YOUR PASSWORD
$dbname = 'speakread_db';

try {
    // Connect to MySQL server (no DB yet)
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create DB
    $pdo->exec("
        CREATE DATABASE IF NOT EXISTS $dbname
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci
    ");

    // Select DB
    $pdo->exec("USE $dbname");

    // Teachers Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Teachers (
            TID INT AUTO_INCREMENT PRIMARY KEY,
            Name VARCHAR(100) NOT NULL,
            Email VARCHAR(100) NOT NULL UNIQUE,
            Password VARCHAR(255) NOT NULL
        )
    ");

    // Students Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Students (
            SID INT AUTO_INCREMENT PRIMARY KEY,
            Name VARCHAR(100) NOT NULL,
            Email VARCHAR(100) NOT NULL UNIQUE,
            Password VARCHAR(255) NOT NULL,
            Grade VARCHAR(20),
            TID INT,
            FOREIGN KEY (TID) REFERENCES Teachers(TID) ON DELETE SET NULL
        )
    ");

    // Lessons Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Lessons (
            LID INT AUTO_INCREMENT PRIMARY KEY,
            Grade VARCHAR(20) NOT NULL,
            LessonNumber INT NOT NULL,
            LessonName VARCHAR(150) NOT NULL,
            ParaNumber INT NOT NULL,
            Para TEXT NOT NULL
        )
    ");

    // Homework Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Homework (
            HID INT AUTO_INCREMENT PRIMARY KEY,
            Grade VARCHAR(20) NOT NULL,
            H_Date DATE NOT NULL,
            H_Topic VARCHAR(150) NOT NULL,
            H_Para TEXT NOT NULL,
            IsActive TINYINT(1) DEFAULT 1
        )
    ");

    // Scores Table - For tracking reading session results
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Scores (
            Score_ID INT AUTO_INCREMENT PRIMARY KEY,
            SID INT NOT NULL,
            HID INT NOT NULL,
            Accuracy DECIMAL(5,2) NOT NULL,
            DateCompleted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (SID) REFERENCES Students(SID) ON DELETE CASCADE,
            FOREIGN KEY (HID) REFERENCES Homework(HID) ON DELETE CASCADE
        )
    ");

    // Warmup Table - For tracking incorrect words
    // Modified to match your actual schema
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Warmup (
            WID INT AUTO_INCREMENT PRIMARY KEY,
            SID INT,
            homework_mistakes TEXT,
            reading_practice_mistakes TEXT,
            FOREIGN KEY (SID) REFERENCES Students(SID) ON DELETE CASCADE
        )
    ");

    echo "✓ Database & tables created successfully!<br>";
    echo "✓ Schema matches your database structure<br>";
    echo "<br>Next steps:<br>";
    echo "1. Create a teacher account using the login page<br>";
    echo "2. Teacher can then create classes and add students<br>";

} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>