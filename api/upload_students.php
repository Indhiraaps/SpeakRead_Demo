<?php
session_start();
$host = 'mysql-19588968-speakread000.g.aivencloud.com'; 
$db = 'defaultdb'; 
$port = '25249';
$user = 'avnadmin'; 
$pass = 'AVNS_-hJYen-fDyBu9ApXbxH'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Change this line:
$pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $grade = $_POST['grade_name'];
        $teacher_id = $_SESSION['user_id'];
        
        if (isset($_FILES["student_excel"]) && $_FILES["student_excel"]["size"] > 0) {
            $file = $_FILES["student_excel"]["tmp_name"];
            $fileName = $_FILES["student_excel"]["name"];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);

            if ($ext == "csv") {
                if (($handle = fopen($file, "r")) !== FALSE) {
                    fgetcsv($handle, 1000, ","); // Skip header row
                    
                    $insertStmt = $pdo->prepare("INSERT INTO Students (Name, Email, Password, Grade, TID) VALUES (?, ?, ?, ?, ?)");
                    // Pre-check statement using UNION for efficiency
                    $checkStmt = $pdo->prepare("SELECT Email FROM Students WHERE Email = ? UNION SELECT Email FROM Teachers WHERE Email = ?");
                    
                    $success_count = 0;
                    $skip_count = 0;
                    
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (empty($data[0]) || empty($data[1])) continue; 
                        
                        $name = trim($data[0]);
                        $email = trim($data[1]);
                        $password = trim($data[2]); // This is the plain text from CSV
                        
                        // Check both tables simultaneously
                        $checkStmt->execute([$email, $email]);
                        if ($checkStmt->fetch()) {
                            $skip_count++;
                            continue; // Skip this student and move to the next row
                        }
                        
                        // REWRITE: Removed hash() function to keep password as plain text
                        try {
                            $insertStmt->execute([$name, $email, $password, $grade, $teacher_id]);
                            $success_count++;
                        } catch (PDOException $e) {
                            $skip_count++;
                        }
                    }
                    fclose($handle);
                    
                    $message = "$success_count students added successfully.";
                    if ($skip_count > 0) {
                        $message .= " $skip_count duplicates were skipped.";
                    }
                    echo "<script>alert('$message'); window.location.href='teacher_dashboard.php';</script>";
                }
            } else {
                echo "<script>alert('Please use CSV format.'); window.history.back();</script>";
            }
        } else {
            // Handle class initialization with a unique placeholder email
            $placeholder_email = 'class_init_' . time() . '_' . rand(1000, 9999) . '@speakread.com';
            
            // REWRITE: Removed hash() function to store 'nopassword' as plain text
            $placeholder_password = 'nopassword';
            
            $stmt = $pdo->prepare("INSERT INTO Students (Name, Email, Password, Grade, TID) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['System Placeholder', $placeholder_email, $placeholder_password, $grade, $teacher_id]);
            
            echo "<script>alert('Class created successfully!'); window.location.href='teacher_dashboard.php';</script>";
        }
        
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>