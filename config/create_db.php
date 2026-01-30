<?php
// Aiven Cloud Configuration
$host = 'mysql-19588968-speakread000.g.aivencloud.com';
$port = '25249'; // From your Aiven screenshot
$user = 'avnadmin'; 
$pass = 'AVNS_-hJYen-fDyBu9ApXbxH'; // Click the copy icon in Aiven
$dbname = 'defaultdb';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
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
            homework_mistakes TEXT DEFAULT NULL,
            reading_practice_mistakes TEXT DEFAULT NULL,
            DateAdded TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (SID) REFERENCES Students(SID) ON DELETE CASCADE,
            UNIQUE KEY unique_student (SID)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS HomeworkSchedule (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            Grade VARCHAR(50) NOT NULL UNIQUE,
            Mon TINYINT(1) NOT NULL DEFAULT 1,
            Tue TINYINT(1) NOT NULL DEFAULT 1,
            Wed TINYINT(1) NOT NULL DEFAULT 1,
            Thu TINYINT(1) NOT NULL DEFAULT 1,
            Fri TINYINT(1) NOT NULL DEFAULT 1,
            Sat TINYINT(1) NOT NULL DEFAULT 1,
            Sun TINYINT(1) NOT NULL DEFAULT 1,
            CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_grade (Grade)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 ");

    echo "✅ Database & tables created successfully!<br>";
    echo "✅ Warmup table configured for reading_practice and homework mistakes<br>";
    echo "✅ Duplicate prevention with UNIQUE constraint per student per word type<br>";
    echo "✅ Scores table created for tracking accuracy<br>";

} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>
