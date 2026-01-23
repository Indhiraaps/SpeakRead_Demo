<?php
$host = 'localhost';
$user = 'root';
$pass = 'skdn1418'; // CHANGE THIS TO YOUR PASSWORD
$dbname = 'speakread_db';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create DB
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
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

    // Scores Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Scores (
            ScoreID INT AUTO_INCREMENT PRIMARY KEY,
            SID INT NOT NULL,
            HID INT,
            Accuracy DECIMAL(5,2) NOT NULL,
            DateCompleted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (SID) REFERENCES Students(SID) ON DELETE CASCADE,
            FOREIGN KEY (HID) REFERENCES Homework(HID) ON DELETE SET NULL
        )
    ");

    // CORRECTED Warmup Table - stores individual words per student per type
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Warmup (
            WID INT AUTO_INCREMENT PRIMARY KEY,
            SID INT NOT NULL,
            IncorrectWord VARCHAR(100) NOT NULL,
            WordType ENUM('reading_practice', 'homework') NOT NULL,
            DateAdded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (SID) REFERENCES Students(SID) ON DELETE CASCADE,
            UNIQUE KEY unique_word_per_student_type (SID, IncorrectWord, WordType)
        )
    ");

    echo "✅ Database & tables created successfully!<br>";
    echo "✅ Warmup table configured for reading_practice and homework mistakes<br>";
    echo "✅ Duplicate prevention with UNIQUE constraint per student per word type<br>";
    echo "✅ Scores table created for tracking accuracy<br>";

} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>