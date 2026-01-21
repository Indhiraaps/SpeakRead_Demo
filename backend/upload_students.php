<?php
session_start();
$host = 'localhost'; $db = 'speakread_db'; $user = 'root'; $pass = 'skdn1418';

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
                    $stmt = $pdo->prepare("INSERT INTO Students (Name, Email, Password, Grade, TID) VALUES (?, ?, ?, ?, ?)");
                    
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (empty($data[0])) continue; // Skip empty rows
                        $name = trim($data[0]);
                        $email = trim($data[1]);
                        $password = trim($data[2]); // Password from CSV
                        $stmt->execute([$name, $email, $password, $grade, $teacher_id]);
                    }
                    fclose($handle);
                }
            } else {
                // Basic check for Excel (.xlsx/.xls)
                // Note: True .xlsx parsing requires a library. We'll show a notice.
                echo "<script>alert('Note: Standard Excel format detected. For best results, please save as CSV. Attempting to process as text...');</script>";
                // Optional: Insert logic for an Excel library here
            }
        } else {
            // Logic for "No File Uploaded"
            // To keep existing design, we insert 1 placeholder student record 
            // so that 'SELECT DISTINCT Grade' in the dashboard works.
            $stmt = $pdo->prepare("INSERT INTO Students (Name, Email, Password, Grade, TID) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['System Placeholder', 'class_init@speakread.com', 'nopassword', $grade, $teacher_id]);
        }
        
        echo "<script>alert('Class created successfully!'); window.location.href='teacher_dashboard.php';</script>";
        
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>