<?php
session_start();
$host = 'localhost'; $db = 'speakread_db'; $user = 'root'; $pass = '12345678';

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
                // Skip empty rows if any
                if (empty($data[0])) continue;

                $name = trim($data[0]);
                $email = trim($data[1]);
                
                // CHANGE: Take the password directly from the 3rd column of CSV
                $password = trim($data[2]); 
                
                $stmt->execute([$name, $email, $password, $grade, $teacher_id]);
            }
            fclose($handle);
            
            // Redirect using the correct grade variable to avoid double upload and show correct data
            echo "<script>alert('Class created and students imported successfully!'); window.location.href='teacher_dashboard.php';</script>";
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>