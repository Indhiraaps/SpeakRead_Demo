<?php
session_start();

// Database Configuration - Using the password from your screenshot
$host = 'localhost'; 
$db   = 'speakread_db'; 
$user = 'root'; 
$pass = '12345678'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = trim($_POST['email']);
        // PHP hashes the input. Database MUST contain the hashed version to match.
        $password = hash('sha256', $_POST['password']);

        // 1. Check Teachers Table (Note: Use lowercase column names to match your terminal)
        $stmt = $pdo->prepare("SELECT * FROM teachers WHERE email = ? AND password = ?");
        $stmt->execute([$email, $password]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($teacher) {
            $_SESSION['user_id'] = $teacher['tid'];
            $_SESSION['teacher_name'] = $teacher['name'];
            $_SESSION['role'] = 'teacher';
            header("Location: teacher_dashboard.php");
            exit();
        }

        // 2. Check Students Table
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ? AND password = ?");
        $stmt->execute([$email, $password]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            $_SESSION['user_id'] = $student['sid'];
            $_SESSION['role'] = 'student';
            $_SESSION['grade'] = $student['grade']; 
            header("Location: student_dashboard.php");
            exit();
        }

        // 3. Failure Message
        echo "<script>
                alert('Invalid Credentials'); 
                window.location.href='../frontend/login.html';
              </script>";
    }
} catch (PDOException $e) {
    die("Connection Error: " . $e->getMessage());
}
?>