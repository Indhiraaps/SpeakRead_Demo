<?php
session_start();
$host = 'localhost'; $db = 'speakread_db'; $user = 'root'; $pass = 'skdn1418';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']); 
        $grade = $_POST['grade'];
        $teacher_id = $_SESSION['user_id'];

        $stmt = $pdo->prepare("INSERT INTO Students (Name, Email, Password, Grade, TID) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $grade, $teacher_id]);

        echo "<script>alert('Student added successfully!'); window.location.href='lessons.php?grade=" . urlencode($grade) . "';</script>";
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>