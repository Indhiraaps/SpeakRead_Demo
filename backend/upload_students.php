<?php
session_start();
$host = 'localhost'; $db = 'speakread_db'; $user = 'root'; $pass = 'skdn1418';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["student_excel"])) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $grade = $_POST['grade_name'];
        $teacher_id = $_SESSION['user_id'];
        $file = $_FILES["student_excel"]["tmp_name"];

        // Open the CSV file
        if (($handle = fopen($file, "r")) !== FALSE) {
            // Skip the first row if it contains headers
            fgetcsv($handle, 1000, ",");

            $stmt = $pdo->prepare("INSERT INTO Students (Name, Email, Password, Grade, TID) VALUES (?, ?, ?, ?, ?)");

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $name = $data[0];
                $email = $data[1];
                // Default password is a hashed version of their name (lowercase) or a fixed string
                $password = hash('sha256', 'password123'); 
                
                $stmt->execute([$name, $email, $password, $grade, $teacher_id]);
            }
            fclose($handle);
            echo "<script>alert('Class created and students imported successfully!'); window.location.href='teacher_dashboard.php';</script>";
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>