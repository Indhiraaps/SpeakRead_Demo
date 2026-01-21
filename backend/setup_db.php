<?php
$host = 'localhost';
$root_user = 'root'; // Your MySQL username
$root_pass = 'skdn1418';     // Your MySQL password
$dbname = 'speakread_db';

try {
    // 1. Connect to MySQL without a database selected
    $pdo = new PDO("mysql:host=$host", $root_user, $root_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Create the Database
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "Database '$dbname' created or already exists.<br>";

    // 3. Connect to the newly created database
    $pdo->exec("USE $dbname");

    // 4. Define Table Queries
    $tables = [
        "Teachers" => "CREATE TABLE IF NOT EXISTS Teachers (
            TID INT AUTO_INCREMENT PRIMARY KEY,
            Name VARCHAR(100) NOT NULL,
            Email VARCHAR(100) NOT NULL UNIQUE,
            Password VARCHAR(255) NOT NULL
        )",
        "Students" => "CREATE TABLE IF NOT EXISTS Students (
            SID INT AUTO_INCREMENT PRIMARY KEY,
            Name VARCHAR(100) NOT NULL,
            Email VARCHAR(100) NOT NULL UNIQUE,
            Password VARCHAR(255) NOT NULL,
            Grade VARCHAR(20),
            TID INT,
            FOREIGN KEY (TID) REFERENCES Teachers(TID) ON DELETE SET NULL
        )",
        "Lessons" => "CREATE TABLE IF NOT EXISTS Lessons (
            LID INT AUTO_INCREMENT PRIMARY KEY,
            Grade VARCHAR(20) NOT NULL,
            LessonNumber INT NOT NULL,
            LessonName VARCHAR(150) NOT NULL,
            ParaNumber	INT NOT NULL,
            Para TEXT NOT NULL
        )",
        "Homework" => "CREATE TABLE IF NOT EXISTS Homework (
            HID INT AUTO_INCREMENT PRIMARY KEY,
            Grade VARCHAR(20) NOT NULL,
            H_Date DATE NOT NULL,
            H_Topic VARCHAR(150) NOT NULL,
            H_Para TEXT NOT NULL,
            IsActive TINYINT(1) DEFAULT 1
        )",
        "Warmup" => "CREATE TABLE IF NOT EXISTS Warmup (
            WID INT AUTO_INCREMENT PRIMARY KEY,
            SID INT,
            IncorrectWord VARCHAR(100),
            FOREIGN KEY (SID) REFERENCES Students(SID) ON DELETE CASCADE
        )"
    ];

    // 5. Execute Table Creation
    foreach ($tables as $tableName => $sql) {
        $pdo->exec($sql);
        echo "Table '$tableName' created successfully.<br>";
    }

    echo "<br><strong>System Ready: Database and Tables are set up.</strong>";

} catch (PDOException $e) {
    die("DB Setup Error: " . $e->getMessage());
}
?>