<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../frontend/login.html");
    exit();
}

$grade = $_GET['grade'] ?? '';

if (empty($grade)) {
    header("Location: teacher_dashboard.php");
    exit();
}

$host = 'localhost'; $db = 'speakread_db'; $user = 'root'; $pass = '12345678';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL Query: Joining Students with Scores to get the Average Accuracy
    $stmt = $pdo->prepare("
        SELECT 
            s.SID, 
            s.Name, 
            s.Email, 
            s.Password, 
            ROUND(AVG(sc.Accuracy), 1) as AvgAccuracy
        FROM Students s
        LEFT JOIN Scores sc ON s.SID = sc.SID
        WHERE s.Grade = ?
        GROUP BY s.SID
        ORDER BY s.Name
    ");
    $stmt->execute([$grade]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate Class Average for the Stat Card
    $totalAccuracy = 0;
    $count = 0;
    foreach ($students as $st) {
        if ($st['AvgAccuracy'] !== null) {
            $totalAccuracy += $st['AvgAccuracy'];
            $count++;
        }
    }
    $classAvg = $count > 0 ? round($totalAccuracy / $count, 1) : 0;
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Reports - <?= htmlspecialchars($grade) ?></title>
    <style>
        * { box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { margin: 0; background: #f8fafc; display: flex; }
        
        .sidebar { width: 260px; background: #ffffff; height: 100vh; border-right: 1px solid #e2e8f0; padding: 24px; position: fixed; }
        .logo { font-size: 22px; font-weight: 800; color: #2563eb; margin-bottom: 40px; }
        .back-link { color: #64748b; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 8px; padding: 10px; border-radius: 6px; }
        
        .main-content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }
        .header { margin-bottom: 32px; }
        .header h1 { font-size: 32px; font-weight: 800; color: #1e293b; margin: 0; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; }
        .stat-label { font-size: 14px; color: #64748b; margin-bottom: 8px; }
        .stat-value { font-size: 32px; font-weight: 800; color: #2563eb; }
        
        .students-table { background: white; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .table-header { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 16px 24px; background: #f8fafc; color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        td { padding: 16px 24px; border-top: 1px solid #e2e8f0; color: #1e293b; font-size: 14px; }
        
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        
        /* Style for the plain text password */
        .pass-display { font-family: monospace; color: #475569; background: #f1f5f9; padding: 4px 8px; border-radius: 4px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">SpeakRead</div>
    <a href="grade_management.php?grade=<?= urlencode($grade) ?>" class="back-link">
        ← Back to Management
    </a>
</div>

<div class="main-content">
    <div class="header">
        <h1>Student Reports: <?= htmlspecialchars($grade) ?></h1>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Students</div>
            <div class="stat-value"><?= count($students) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Class Average</div>
            <div class="stat-value"><?= $classAvg ?>%</div>
        </div>
    </div>
    
    <div class="students-table">
        <div class="table-header">
            <h2 style="margin:0; font-size:18px;">Student Roster</h2>
        </div>
        <?php if (empty($students)): ?>
            <div style="text-align:center; padding:40px; color:#64748b;">No students found.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>Avg. Accuracy</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td style="font-weight:600;"><?= htmlspecialchars($student['Name']) ?></td>
                            <td><?= htmlspecialchars($student['Email']) ?></td>
                            <td><span class="pass-display"><?= htmlspecialchars($student['Password']) ?></span></td>
                            <td>
                                <?php if ($student['AvgAccuracy'] !== null): ?>
                                    <span class="badge <?= $student['AvgAccuracy'] >= 75 ? 'badge-success' : 'badge-warning' ?>">
                                        <?= $student['AvgAccuracy'] ?>%
                                    </span>
                                <?php else: ?>
                                    <span style="color:#cbd5e1;">No attempts</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

</body>
</html>