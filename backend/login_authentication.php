<?php
session_start();

// Database Configuration
$host = 'localhost'; 
$db   = 'speakread_db'; 
$user = 'root'; 
$pass = 'skdn1418'; // Change this to your password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = trim($_POST['email']);
        $password = hash('sha256', $_POST['password']); // Hash the input password

        // 1. Check Teachers Table - USE UPPERCASE COLUMN NAMES
        $stmt = $pdo->prepare("SELECT * FROM Teachers WHERE Email = ? AND Password = ?");
        $stmt->execute([$email, $password]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($teacher) {
            // FIX: Use UPPERCASE keys to match database columns
            $_SESSION['user_id'] = $teacher['TID'];      // Was: $teacher['tid']
            $_SESSION['teacher_name'] = $teacher['Name']; // Was: $teacher['name']
            $_SESSION['role'] = 'teacher';
            header("Location: teacher_dashboard.php");
            exit();
        }

        // 2. Check Students Table - USE UPPERCASE COLUMN NAMES
        $stmt = $pdo->prepare("SELECT * FROM Students WHERE Email = ? AND Password = ?");
        $stmt->execute([$email, $password]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            // FIX: Use UPPERCASE keys to match database columns
            $_SESSION['user_id'] = $student['SID'];      // Was: $student['sid']
            $_SESSION['user_name'] = $student['Name'];   // Added this for student dashboard
            $_SESSION['role'] = 'student';
            $_SESSION['user_grade'] = $student['Grade']; // Was: $student['grade'], also changed to 'user_grade'
            header("Location: student_dashboard.php");
            exit();
        }

        // 3. Failure Message
        echo "<script>
                alert('Invalid Credentials. Please check your email and password.'); 
                window.location.href='../frontend/login.html';
              </script>";
    }
} catch (PDOException $e) {
    die("Connection Error: " . $e->getMessage());
}
?>