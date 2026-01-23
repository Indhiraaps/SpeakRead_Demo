<?php
session_start();
$host = 'localhost'; 
$db = 'speakread_db'; 
$user = 'root'; 
$pass = '12345678'; // Change this to your password

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']); 
        
        // FIX: Hash the password before storing (match login behavior)
        $hashed_password = hash('sha256', $password);
        
        $grade = $_POST['grade'];
        $teacher_id = $_SESSION['user_id'];

        // FIX: Use UPPERCASE column names to match database
        $stmt = $pdo->prepare("INSERT INTO Students (Name, Email, Password, Grade, TID) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashed_password, $grade, $teacher_id]);

        echo "<script>alert('Student added successfully!'); window.location.href='lessons.php?grade=" . urlencode($grade) . "';</script>";
    } catch (PDOException $e) {
        // Check if it's a duplicate email error
        if ($e->getCode() == 23000) {
            echo "<script>alert('Error: Email already exists!'); window.history.back();</script>";
        } else {
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        }
    }
}
?>