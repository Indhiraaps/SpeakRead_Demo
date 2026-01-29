<?php
session_start();
$host = 'localhost'; 
$db = 'speakread_db'; 
$user = 'root'; 
$pass = 'skdn1418'; // Change this to your password

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']); 
        $grade = $_POST['grade'];
        $teacher_id = $_SESSION['user_id'];

        // 1. Check if email exists in Teachers table
        $stmtT = $pdo->prepare("SELECT Email FROM Teachers WHERE Email = ?");
        $stmtT->execute([$email]);
        
        // 2. Check if email exists in Students table
        $stmtS = $pdo->prepare("SELECT Email FROM Students WHERE Email = ?");
        $stmtS->execute([$email]);

        // If found in either, block the registration
        if ($stmtT->fetch() || $stmtS->fetch()) {
            echo "<script>alert('Error: This email is already registered by another Teacher or Student!'); window.history.back();</script>";
            exit();
        }

        // Hash the password for security (matches login logic)
        $hashed_password = hash('sha256', $password);

        // Use UPPERCASE column names to match your database schema
        $stmt = $pdo->prepare("INSERT INTO Students (Name, Email, Password, Grade, TID) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashed_password, $grade, $teacher_id]);

        echo "<script>alert('Student added successfully!'); window.location.href='lessons.php?grade=" . urlencode($grade) . "';</script>";

    } catch (PDOException $e) {
        // Fallback catch for DB constraints
        if ($e->getCode() == 23000) {
            echo "<script>alert('Error: Duplicate email detected in database!'); window.history.back();</script>";
        } else {
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        }
    }
}
?>