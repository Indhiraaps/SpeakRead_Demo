<?php
session_start();
$host = 'localhost'; $db = 'speakread_db'; $user = 'root'; $pass = 'skdn1418';
$grade = $_GET['grade'] ?? 'Grade 3';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmtL = $pdo->prepare("SELECT DISTINCT LessonName FROM Lessons WHERE Grade = ?");
    $stmtL->execute([$grade]);
    $lessons = $stmtL->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Management - <?php echo $grade; ?></title>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; margin: 0; display: flex; }
        .sidebar { 
            width: 260px; height: 100vh; position: fixed; left: 0; top: 0; 
            background: white; border-right: 1px solid #e2e8f0; padding: 24px; box-sizing: border-box; 
        }
        .content { 
            margin-left: 260px; /* FIX: Prevents sidebar from hiding content */
            padding: 40px; width: calc(100% - 260px); box-sizing: border-box; 
        }
        .logo { color: #2563eb; font-weight: 800; font-size: 24px; text-decoration: none; display: block; margin-bottom: 40px; }
        .back-link { color: #64748b; text-decoration: none; font-size: 14px; }
        .main-card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; margin-bottom: 24px; }
        .section-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: #1e293b; }
        .hw-row { display: flex; gap: 12px; }
        .day-box { background: #eff6ff; padding: 12px; border-radius: 8px; text-align: center; flex: 1; border: 1px solid #dbeafe; }
        .day-label { font-size: 11px; font-weight: 800; color: #3b82f6; margin-bottom: 5px; }
        
        /* The Long Card */
        .long-card { 
            border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px 24px; 
            margin-bottom: 12px; cursor: pointer; transition: 0.2s; background: white; 
            display: flex; align-items: center; text-decoration: none; color: #334155; font-weight: 600; 
        }
        .long-card:hover { border-color: #2563eb; background: #f8fafc; transform: translateX(5px); }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="#" class="logo">SpeakRead</a>
        <a href="teacher_dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
    <div class="content">
        <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 30px;">Management: <?php echo htmlspecialchars($grade); ?></h1>
        
        <div class="main-card">
            <div class="section-title">Weekly Homework Status</div>
            <div class="hw-row">
                <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
                    <div class="day-box"><div class="day-label"><?php echo $d; ?></div><input type="checkbox" checked></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="main-card">
            <div class="section-title">Select a Lesson</div>
            <?php foreach($lessons as $l): ?>
                <a href="paragraphs.php?grade=<?php echo urlencode($grade); ?>&lesson=<?php echo urlencode($l['LessonName']); ?>" class="long-card">
                    <span style="margin-right: 15px;"></span> <?php echo $l['LessonName']; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>