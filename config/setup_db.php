<?php
$host = 'localhost';
$user = 'root';
$pass = 'skdn1418';
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

    // Tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Teachers (
            TID INT AUTO_INCREMENT PRIMARY KEY,
            Name VARCHAR(100) NOT NULL,
            Email VARCHAR(100) NOT NULL UNIQUE,
            Password VARCHAR(255) NOT NULL
        )
    ");

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

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Lessons (
            LID INT AUTO_INCREMENT PRIMARY KEY,
            Grade VARCHAR(20) NOT NULL,
            LessonNumber INT NOT NULL,
            LessonName VARCHAR(150) NOT NULL,
            ParaNumber	INT NOT NULL,
            Para TEXT NOT NULL
        )
    ");

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

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Warmup (
            WID INT AUTO_INCREMENT PRIMARY KEY,
            SID INT,
            IncorrectWord VARCHAR(100),
            FOREIGN KEY (SID) REFERENCES Students(SID) ON DELETE CASCADE
        )
    ");

    echo "✔ Database & tables ready";

} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
