<?php
session_start();

// Database Configuration
$host = 'mysql-19588968-speakread000.g.aivencloud.com'; 
$db   = 'defaultdb'; 
$port = '25249';
$user = 'avnadmin'; 
$pass = 'AVNS_-hJYen-fDyBu9ApXbxH'; // ⚠️ CHANGE TO YOUR PASSWORD

try {
   // Change this line:
$pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        // Validate inputs
        if (empty($email) || empty($password)) {
            echo "<script>alert('Please enter both email and password.'); window.location.href='../frontend/login.html';</script>";
            exit();
        }
        
        // Hash the input password
        $hashed_password = hash('sha256', $password);

        // ========================================
        // 1. CHECK TEACHERS TABLE
        // ========================================
        $stmt = $pdo->prepare("SELECT TID, Name, Email, Password FROM Teachers WHERE Email = ?");
        $stmt->execute([$email]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($teacher) {
            // Check password (support both hashed and plain text for backward compatibility)
            if ($teacher['Password'] === $hashed_password || $teacher['Password'] === $password) {
                
                // 🔧 FIX: Ensure TID is properly retrieved and stored
                $teacher_id = isset($teacher['TID']) ? (int)$teacher['TID'] : 
                             (isset($teacher['tid']) ? (int)$teacher['tid'] : 0);
                
                if ($teacher_id > 0) {
                    $_SESSION['user_id'] = $teacher_id;
                    $_SESSION['teacher_name'] = $teacher['Name'];
                    $_SESSION['role'] = 'teacher';
                    $_SESSION['email'] = $teacher['Email'];
                    
                    // Clear any student-specific session data
                    unset($_SESSION['user_grade']);
                    unset($_SESSION['user_name']);
                    
                    header("Location: teacher_dashboard.php");
                    exit();
                } else {
                    echo "<script>alert('Account error. Please contact administrator.'); window.location.href='../frontend/login.html';</script>";
                    exit();
                }
            }
        }

        // ========================================
        // 2. CHECK STUDENTS TABLE
        // ========================================
        $stmt = $pdo->prepare("SELECT SID, Name, Email, Password, Grade FROM Students WHERE Email = ?");
        $stmt->execute([$email]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            // Check password (support both hashed and plain text for backward compatibility)
            if ($student['Password'] === $hashed_password || $student['Password'] === $password) {
                
                // 🔧 FIX: Ensure SID is properly retrieved and stored
                $student_id = isset($student['SID']) ? (int)$student['SID'] : 
                             (isset($student['sid']) ? (int)$student['sid'] : 0);
                
                if ($student_id > 0) {
                    $_SESSION['user_id'] = $student_id;
                    $_SESSION['user_name'] = $student['Name'];
                    $_SESSION['role'] = 'student';
                    $_SESSION['user_grade'] = $student['Grade'];
                    $_SESSION['email'] = $student['Email'];
                    
                    // Clear any teacher-specific session data
                    unset($_SESSION['teacher_name']);
                    
                    header("Location: student_dashboard.php");
                    exit();
                } else {
                    echo "<script>alert('Account error. Please contact administrator.'); window.location.href='../frontend/login.html';</script>";
                    exit();
                }
            }
        }

        // ========================================
        // 3. LOGIN FAILED
        // ========================================
        echo "<script>
                alert('Invalid Credentials. Please check your email and password.'); 
                window.location.href='../frontend/login.html';
              </script>";
    }
} catch (PDOException $e) {
    // Log error for debugging (don't show to user in production)
    error_log("Login Error: " . $e->getMessage());
    echo "<script>
            alert('A system error occurred. Please try again later.'); 
            window.location.href='../frontend/login.html';
          </script>";
}
?>