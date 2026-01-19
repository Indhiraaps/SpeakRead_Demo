<?php
session_start();
$host = 'localhost'; $db = 'speakread_db'; $user = 'root'; $pass = 'skdn1418';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];
        $password = hash('sha256', $_POST['password']);

        // Check Teachers Table
        $stmt = $pdo->prepare("SELECT * FROM Teachers WHERE Email = ? AND Password = ?");
        $stmt->execute([$email, $password]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['TID'];
            $_SESSION['teacher_name'] = $user['Name'];
            $_SESSION['role'] = 'teacher';
            header("Location: teacher_dashboard.php");
            exit();
        }

        // Check Students Table
        $stmt = $pdo->prepare("SELECT * FROM Students WHERE Email = ? AND Password = ?");
        $stmt->execute([$email, $password]);
        $student = $stmt->fetch();

        if ($student) {
            $_SESSION['user_id'] = $student['SID'];
            $_SESSION['role'] = 'student';
            header("Location: student_dashboard.php"); // Or wherever student goes
            exit();
        }

        echo "<script>alert('Invalid Credentials'); window.location.href='../frontend/login.html';</script>";
    }
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>