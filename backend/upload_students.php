<?php
session_start();
// Redirect if not a teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../frontend/login.html");
    exit();
}

$host = 'localhost'; 
$db = 'speakread_db'; 
$user = 'root'; 
$pass = '12345678'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $grade = trim($_POST['grade_name']);
        $teacher_id = $_SESSION['user_id'];
        
        // 1. Check if the grade already exists for this teacher
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM Students WHERE Grade = ? AND TID = ?");
        $checkStmt->execute([$grade, $teacher_id]);
        $exists = $checkStmt->fetchColumn();

        // 2. If it doesn't exist, create a Placeholder to "register" the class
        if ($exists == 0) {
            $placeholder_email = "class_init_" . time() . "_" . $teacher_id . "@speakread.com";
            $placeholder_pass = hash('sha256', 'nopassword');
            $initStmt = $pdo->prepare("INSERT INTO Students (Name, Email, Password, Grade, TID) VALUES (?, ?, ?, ?, ?)");
            $initStmt->execute(['System Placeholder', $placeholder_email, $placeholder_pass, $grade, $teacher_id]);
        }

        // 3. Handle CSV File Upload if provided
        if (isset($_FILES["student_excel"]) && $_FILES["student_excel"]["size"] > 0) {
            $file = $_FILES["student_excel"]["tmp_name"];
            $ext = pathinfo($_FILES["student_excel"]["name"], PATHINFO_EXTENSION);

            if ($ext == "csv") {
                if (($handle = fopen($file, "r")) !== FALSE) {
                    fgetcsv($handle, 1000, ","); // Skip header row
                    
                    $stmt = $pdo->prepare("INSERT INTO Students (Name, Email, Password, Grade, TID) VALUES (?, ?, ?, ?, ?)");
                    
                    $success_count = 0;
                    $error_count = 0;
                    
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (empty($data[0])) continue; 
                        
                        $name = trim($data[0]);
                        $email = trim($data[1]);
                        $password = trim($data[2]); 
                        $hashed_password = hash('sha256', $password);
                        
                        try {
                            $stmt->execute([$name, $email, $hashed_password, $grade, $teacher_id]);
                            $success_count++;
                        } catch (PDOException $e) {
                            if ($e->getCode() == 23000) { $error_count++; } 
                            else { throw $e; }
                        }
                    }
                    fclose($handle);
                    
                    $msg = "Class updated: $success_count students added.";
                    if($error_count > 0) $msg .= " ($error_count duplicates skipped).";
                    echo "<script>alert('$msg'); window.location.href='teacher_dashboard.php';</script>";
                }
            } else {
                echo "<script>alert('Please use CSV format.'); window.history.back();</script>";
            }
        } else {
            // No file, just the placeholder was created
            echo "<script>alert('New class created: $grade'); window.location.href='teacher_dashboard.php';</script>";
        }
        
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>