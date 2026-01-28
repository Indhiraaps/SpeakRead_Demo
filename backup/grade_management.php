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

$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management: <?= htmlspecialchars($grade) ?> - SpeakRead</title>
    <style>
        * { box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { margin: 0; background: #f8fafc; display: flex; }
        
        /* Sidebar */
        .sidebar { 
            width: 260px; 
            background: #ffffff; 
            height: 100vh; 
            border-right: 1px solid #e2e8f0; 
            padding: 24px; 
            position: fixed; 
        }
        .logo { 
            font-size: 22px; 
            font-weight: 800; 
            color: #2563eb; 
            margin-bottom: 40px; 
        }
        .back-link {
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px;
            border-radius: 6px;
            transition: 0.2s;
        }
        .back-link:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        /* Main Content */
        .main-content { 
            margin-left: 260px; 
            padding: 40px; 
            width: calc(100% - 260px); 
        }
        
        /* Header */
        .header {
            margin-bottom: 40px;
        }
        .header h1 {
            font-size: 32px;
            font-weight: 800;
            color: #1e293b;
            margin: 0 0 8px 0;
        }
        .header p {
            color: #64748b;
            font-size: 14px;
            margin: 0;
        }
        
        /* Action Button */
        .action-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .action-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        
        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin-top: 32px;
        }
        
        /* Management Card */
        .management-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 280px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .management-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.15);
            border-color: #2563eb;
        }
        
        .card-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            font-size: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .card-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 12px 0;
        }
        
        .card-description {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .cards-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">SpeakRead</div>
    <a href="teacher_dashboard.php" class="back-link">
        â† Back to Dashboard
    </a>
</div>

<div class="main-content">
    <div class="header">
        <h1>Management: <?= htmlspecialchars($grade) ?></h1>
        <p>Choose an option to manage this class</p>
    </div>
    
    <!-- Weekly Homework Status -->
    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; margin-bottom: 32px;">
        <h2 style="font-size: 18px; font-weight: 700; margin: 0 0 20px 0; color: #1e293b;">Weekly Homework Status</h2>
        <div style="display: flex; gap: 12px;">
            <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
                <div style="background: #eff6ff; padding: 12px; border-radius: 8px; text-align: center; flex: 1; border: 1px solid #dbeafe;">
                    <div style="font-size: 11px; font-weight: 800; color: #3b82f6; margin-bottom: 5px;"><?php echo $d; ?></div>
                    <input type="checkbox" checked style="cursor: pointer;">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="cards-grid">
        <!-- Lessons Card -->
        <div class="management-card" onclick="window.location.href='lessons.php?grade=<?= urlencode($grade) ?>'">
            <div class="card-icon">ðŸ“š</div>
            <h2 class="card-title">Lessons</h2>
            <p class="card-description">View and manage all lessons for this grade</p>
        </div>
        
        <!-- Student Reports Card -->
        <div class="management-card" onclick="window.location.href='student_reports.php?grade=<?= urlencode($grade) ?>'">
            <div class="card-icon">ðŸ“Š</div>
            <h2 class="card-title">Student Reports</h2>
            <p class="card-description">View student progress and performance data</p>
        </div>
    </div>
</div>

</body>
</html>