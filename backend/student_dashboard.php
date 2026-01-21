<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../frontend/login.html");
    exit();
}

$grade = $_SESSION['user_grade'];
$today = date('Y-m-d');

try {
    // 1. Check for Today's Homework
    $hwStmt = $pdo->prepare("SELECT HID, H_Topic FROM Homework WHERE Grade = :grade AND H_Date = :today AND IsActive = 1");
    $hwStmt->execute(['grade' => $grade, 'today' => $today]);
    $homework = $hwStmt->fetch();

    // 2. Fetch Practice Lessons
    $lessonStmt = $pdo->prepare("SELECT DISTINCT LessonName FROM Lessons WHERE Grade = :grade");
    $lessonStmt->execute(['grade' => $grade]);
    $lessons = $lessonStmt->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | SpeakRead</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; padding: 20px; }
        .container { max-width: 1000px; margin: auto; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .card { background: white; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .hw-active { border-left: 8px solid #22c55e; cursor: pointer; transition: 0.2s; }
        .hw-active:hover { background: #f0fdf4; }
        .hw-inactive { border-left: 8px solid #94a3b8; opacity: 0.7; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .btn-start { background: #2563eb; color: white; padding: 10px; border-radius: 6px; text-decoration: none; display: inline-block; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></h1>

        <div class="grid">
            <div class="card <?= $homework ? 'hw-active' : 'hw-inactive' ?>" 
                 onclick="<?= $homework ? "window.location.href='homework.php?hid=".$homework['HID']."'" : "" ?>">
                <h2>📅 Daily Homework</h2>
                <?php if ($homework): ?>
                    <span class="status-badge" style="background: #dcfce7; color: #166534;">Available</span>
                    <p style="margin-top:15px;"><strong>Topic:</strong> <?= htmlspecialchars($homework['H_Topic']) ?></p>
                    <small>Click this card to start your daily practice!</small>
                <?php else: ?>
                    <span class="status-badge" style="background: #f1f5f9; color: #475569;">No Task</span>
                    <p style="margin-top:15px;">Your teacher hasn't assigned homework for today.</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>📈 Your Progress</h2>
                <p>Accuracy: <strong>--%</strong></p>
                <p>Lessons Completed: <strong>0</strong></p>
            </div>
        </div>
    </div>
</body>
</html>