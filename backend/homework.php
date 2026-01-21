<?php
session_start();
require_once '../config/db.php';

// Check if student is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../frontend/login.html");
    exit();
}

if (!isset($_GET['hid'])) {
    header("Location: student_dashboard.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM Homework WHERE HID = ?");
$stmt->execute([$_GET['hid']]);
$hw = $stmt->fetch();

if (!$hw) { die("Homework not found."); }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Doing Homework | SpeakRead</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        .container { max-width: 800px; margin: auto; padding: 40px; }
        .reading-area { 
            font-size: 24px; 
            line-height: 1.8; 
            padding: 40px; 
            background: white; 
            border-radius: 15px; 
            margin-top: 20px; 
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .btn-start { 
            display: inline-block;
            background: #2563eb; 
            color: white; 
            padding: 18px 30px; 
            font-size: 20px; 
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px; 
            margin-top: 30px; 
            transition: background 0.2s;
        }
        .btn-start:hover { background: #1d4ed8; }
        .back-link { color: #64748b; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="student_dashboard.php" class="back-link">⬅ Back to Dashboard</a>
        
        <h1>Homework: <?= htmlspecialchars($hw['H_Topic']) ?></h1>
        
        <div class="reading-area">
            <?= htmlspecialchars($hw['H_Para']) ?>
        </div>

        <a href="reading_practice.php?hid=<?= urlencode($hw['HID']) ?>" class="btn-start">
            Proceed to Reading Practice
        </a>
    </div>
</body>
</html>