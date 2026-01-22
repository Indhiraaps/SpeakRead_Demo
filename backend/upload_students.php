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

        $grade = $_POST['grade_name'];
        $teacher_id = $_SESSION['user_id'];
        
        // Check if a file was actually provided
        if (isset($_FILES["student_excel"]) && $_FILES["student_excel"]["size"] > 0) {
            $file = $_FILES["student_excel"]["tmp_name"];
            $fileName = $_FILES["student_excel"]["name"];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);

            // Handle CSV files
            if ($ext == "csv") {
                if (($handle = fopen($file, "r")) !== FALSE) {
                    fgetcsv($handle, 1000, ","); // Skip header row
                    
                    // FIX: Use UPPERCASE column names to match database
                    $stmt = $pdo->prepare("INSERT INTO Students (Name, Email, Password, Grade, TID) VALUES (?, ?, ?, ?, ?)");
                    
                    $success_count = 0;
                    $error_count = 0;
                    
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (empty($data[0])) continue; // Skip empty rows
                        
                        $name = trim($data[0]);
                        $email = trim($data[1]);
                        $password = trim($data[2]); // Password from CSV
                        
                        // FIX: Hash the password before storing (match login behavior)
                        $hashed_password = hash('sha256', $password);
                        
                        try {
                            $stmt->execute([$name, $email, $hashed_password, $grade, $teacher_id]);
                            $success_count++;
                        } catch (PDOException $e) {
                            // Skip duplicate emails
                            if ($e->getCode() == 23000) {
                                $error_count++;
                                continue;
                            } else {
                                throw $e;
                            }
                        }
                    }
                    fclose($handle);
                    
                    $message = "$success_count student(s) added successfully!";
                    if ($error_count > 0) {
                        $message .= " ($error_count duplicate emails skipped)";
                    }
                    echo "<script>alert('$message'); window.location.href='teacher_dashboard.php';</script>";
                }
            } else {
                // Excel files (.xlsx/.xls) require additional library
                echo "<script>alert('Please use CSV format. To convert Excel to CSV: Open file > Save As > CSV (Comma delimited)'); window.history.back();</script>";
            }
        } else {
            // No file uploaded - create class without students
            // Check if this grade already exists for this teacher
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM Students WHERE Grade = ? AND TID = ?");
            $checkStmt->execute([$grade, $teacher_id]);
            $exists = $checkStmt->fetchColumn();
            
            if ($exists > 0) {
                echo "<script>alert('This class already exists!'); window.location.href='teacher_dashboard.php';</script>";
            } else {
                // FIX: Use UPPERCASE column names and hash the placeholder password
                $placeholder_password = hash('sha256', 'nopassword');
                $stmt = $pdo->prepare("INSERT INTO Students (Name, Email, Password, Grade, TID) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(['System Placeholder', 'class_init_' . time() . '@speakread.com', $placeholder_password, $grade, $teacher_id]);
                
                echo "<script>alert('Class created successfully! You can now add students individually.'); window.location.href='teacher_dashboard.php';</script>";
            }
        }
        
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>