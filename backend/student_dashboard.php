<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../frontend/login.html");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['user_name'] ?? 'Student';
$grade = $_SESSION['user_grade'];
$today = date('Y-m-d');

try {
    // 1. Fetch Today's Homework
    $hwStmt = $pdo->prepare("SELECT HID, H_Topic, H_Para FROM Homework WHERE Grade = ? AND H_Date = ? AND IsActive = 1");
    $hwStmt->execute([$grade, $today]);
    $homework = $hwStmt->fetch();

    // 2. Count Warmup Words from both columns
    $warmupStmt = $pdo->prepare("SELECT homework_mistakes, reading_practice_mistakes FROM Warmup WHERE SID = ?");
    $warmupStmt->execute([$student_id]);
    $warmupData = $warmupStmt->fetch();
    
    $warmupWords = [];
    if ($warmupData) {
        if (!empty($warmupData['homework_mistakes'])) {
            $warmupWords = array_merge($warmupWords, explode(',', $warmupData['homework_mistakes']));
        }
        if (!empty($warmupData['reading_practice_mistakes'])) {
            $warmupWords = array_merge($warmupWords, explode(',', $warmupData['reading_practice_mistakes']));
        }
        $warmupWords = array_unique(array_filter(array_map('trim', $warmupWords)));
    }
    $warmupCount = count($warmupWords);
    
    // Get first 5 words for display
    $recentWords = array_slice($warmupWords, 0, 5);

    // 3. Calculate Stats from Scores table
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_sessions,
            AVG(Accuracy) as avg_accuracy
        FROM Scores 
        WHERE SID = ?
    ");
    $statsStmt->execute([$student_id]);
    $stats = $statsStmt->fetch();
    
    $total_sessions = $stats['total_sessions'] ?? 0;
    $accuracy = $total_sessions > 0 ? round($stats['avg_accuracy']) : 0;
    
    // Calculate total words read (estimate based on sessions)
    $total_words_read = $total_sessions * 50; // Assuming ~50 words per session

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpeakRead - My Learning Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Comic+Neue:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6C63FF;
            --primary-light: #8A84FF;
            --secondary: #FF8A63;
            --accent: #63D2FF;
            --success: #4ECB71;
            --warning: #FFB74D;
            --background: #F8F9FF;
            --card-bg: #FFFFFF;
            --text: #2D3748;
            --text-light: #718096;
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: var(--background);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(108, 99, 255, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(255, 138, 99, 0.05) 0%, transparent 20%);
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
            color: var(--text);
        }

        .floating-elements {
            position: fixed;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .floating-elements div {
            position: absolute;
            background: rgba(108, 99, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .floating-elements div:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }

        .floating-elements div:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 10%;
            background: rgba(255, 138, 99, 0.1);
            animation-delay: 1s;
        }

        .floating-elements div:nth-child(3) {
            width: 80px;
            height: 80px;
            bottom: 20%;
            left: 20%;
            background: rgba(99, 210, 255, 0.1);
            animation-delay: 2s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .header {
            background: var(--card-bg);
            padding: 25px 35px;
            border-radius: 24px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(108, 99, 255, 0.1);
            border: 3px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--accent), var(--secondary));
        }

        .welcome h1 {
            font-size: 32px;
            font-weight: 900;
            color: var(--primary);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .welcome h1::before {
            content: '🎯';
            font-size: 40px;
            animation: bounce 2s infinite;
        }

        .welcome p {
            color: var(--text-light);
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .welcome p::before {
            content: '📚';
        }

        .grade-badge {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-left: 10px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 2px solid transparent;
            animation: slideUp 0.6s ease-out both;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(108, 99, 255, 0.15);
            border-color: var(--primary-light);
        }

        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card:nth-child(3) { animation-delay: 0.3s; }

        @keyframes slideUp {
            from { 
                opacity: 0; 
                transform: translateY(30px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .card-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            flex-shrink: 0;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: 0 8px 20px rgba(108, 99, 255, 0.3);
        }

        .card.warmup .card-icon {
            background: linear-gradient(135deg, var(--warning), #FF9A3D);
            box-shadow: 0 8px 20px rgba(255, 183, 77, 0.3);
        }

        .card.accuracy .card-icon {
            background: linear-gradient(135deg, var(--accent), #7BDCFF);
            box-shadow: 0 8px 20px rgba(99, 210, 255, 0.3);
        }

        .card-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--text);
            margin: 0;
        }

        .card-subtitle {
            color: var(--text-light);
            font-size: 14px;
            margin-top: 4px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--success), #5ED582);
            color: white;
            box-shadow: 0 4px 15px rgba(78, 203, 113, 0.3);
        }

        .status-badge::before {
            content: '✨';
        }

        .status-none {
            background: linear-gradient(135deg, #CBD5E0, #A0AEC0);
            box-shadow: 0 4px 15px rgba(160, 174, 192, 0.3);
        }

        .status-none::before {
            content: '🌟';
        }

        .hw-topic {
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
            margin: 15px 0;
            padding-left: 15px;
            border-left: 4px solid var(--primary);
        }

        .hw-preview {
            font-size: 16px;
            color: var(--text-light);
            line-height: 1.6;
            background: rgba(108, 99, 255, 0.05);
            padding: 15px;
            border-radius: 12px;
            margin: 15px 0;
            border-left: 3px solid var(--primary-light);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 32px;
            border-radius: 16px;
            font-weight: 800;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            font-family: 'Nunito', sans-serif;
            min-width: 200px;
            position: relative;
            overflow: hidden;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::after {
            left: 100%;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #5ED582);
            color: white;
            box-shadow: 0 10px 25px rgba(78, 203, 113, 0.4);
        }

        .btn-success:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 35px rgba(78, 203, 113, 0.6);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning), #FF9A3D);
            color: white;
            box-shadow: 0 10px 25px rgba(255, 183, 77, 0.4);
        }

        .btn-warning:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 35px rgba(255, 183, 77, 0.6);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: 0 10px 25px rgba(108, 99, 255, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 35px rgba(108, 99, 255, 0.6);
        }

        .btn-disabled {
            background: linear-gradient(135deg, #E2E8F0, #CBD5E0);
            color: #94A3B8;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .word-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }

        .word-tag {
            background: linear-gradient(135deg, #FFB74D, #FFA726);
            color: #5D4037;
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(255, 183, 77, 0.3);
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
        }

        .word-tag:hover {
            transform: scale(1.05) rotate(2deg);
        }

        .word-tag::before {
            content: '🔤';
            font-size: 14px;
        }

        .word-tag.more {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: white;
        }

        .word-tag.more::before {
            content: '➕';
        }

        .accuracy-display {
            text-align: center;
            margin: 25px 0;
        }

        .accuracy-circle {
            width: 160px;
            height: 160px;
            margin: 0 auto;
            position: relative;
        }

        .accuracy-number {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 48px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--accent), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 4px 8px rgba(99, 210, 255, 0.2);
        }

        .accuracy-label {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-light);
            margin-top: 10px;
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .progress-ring-circle {
            stroke: rgba(99, 210, 255, 0.2);
            fill: transparent;
            stroke-width: 10;
        }

        .progress-ring-progress {
            stroke: url(#progress-gradient);
            fill: transparent;
            stroke-width: 10;
            stroke-linecap: round;
            transition: stroke-dashoffset 1s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 25px 0;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: rgba(108, 99, 255, 0.05);
            border-radius: 16px;
            border: 2px solid rgba(108, 99, 255, 0.1);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 900;
            color: var(--primary);
            display: block;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-light);
            margin-top: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .stat-label::before {
            content: '📈';
            font-size: 12px;
        }

        .logout-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #FF6B6B, #FF5252);
            color: white;
            padding: 18px 28px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 800;
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 3px solid white;
        }

        .logout-btn:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 40px rgba(255, 107, 107, 0.6);
        }

        .no-content {
            text-align: center;
            padding: 30px;
            color: var(--text-light);
            font-size: 16px;
        }

        .no-content::before {
            content: '🎉';
            font-size: 40px;
            display: block;
            margin-bottom: 15px;
        }

        .card-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome h1 {
                font-size: 24px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .btn {
                width: 100%;
            }
            
            .logout-btn {
                bottom: 20px;
                right: 20px;
                padding: 15px 20px;
                font-size: 14px;
            }
        }

        /* Speech bubble effect for homework */
        .speech-bubble {
            position: relative;
            background: var(--card-bg);
            border-radius: 20px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 8px 25px rgba(108, 99, 255, 0.1);
            border: 2px dashed var(--primary-light);
        }

        .speech-bubble::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 30px;
            border-width: 15px 15px 0;
            border-style: solid;
            border-color: var(--primary-light) transparent;
        }

        /* Star rating for accuracy */
        .star-rating {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin: 15px 0;
        }

        .star {
            font-size: 24px;
            color: #FFD700;
            animation: bounce 1.5s infinite;
        }

        .star:nth-child(2) { animation-delay: 0.2s; }
        .star:nth-child(3) { animation-delay: 0.4s; }
        .star:nth-child(4) { animation-delay: 0.6s; }
        .star:nth-child(5) { animation-delay: 0.8s; }
    </style>
</head>
<body>
    <div class="floating-elements">
        <div></div>
        <div></div>
        <div></div>
    </div>
    
    <div class="container">
        <div class="header">
            <div class="welcome">
                <h1>Hello, <?= htmlspecialchars($student_name) ?>!</h1>
                <p>
                    Ready to practice pronunciation today? 
                    <span class="grade-badge"><?= htmlspecialchars($grade) ?></span>
                    • <?= date('l, F j, Y') ?>
                </p>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Homework Card -->
            <div class="card homework">
                <div class="card-header">
                    <div class="card-icon">📝</div>
                    <div>
                        <h3 class="card-title">Today's Homework</h3>
                        <p class="card-subtitle">Practice reading aloud</p>
                    </div>
                </div>
                
                <?php if ($homework): ?>
                    <span class="status-badge">New Assignment Available!</span>
                    
                    <div class="speech-bubble">
                        <h4 class="hw-topic"><?= htmlspecialchars($homework['H_Topic']) ?></h4>
                        <div class="hw-preview">"<?= htmlspecialchars(substr($homework['H_Para'], 0, 120)) ?>..."</div>
                    </div>
                    
                    <div class="card-actions">
                        <a href="homework.php?hid=<?= $homework['HID'] ?>" class="btn btn-success">
                            🎤 Start Reading
                            <span style="font-size: 20px;">→</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="no-content">
                        <p>No homework assigned for today!</p>
                        <p style="margin-top: 10px; font-size: 14px;">Keep practicing your previous lessons! 🎯</p>
                    </div>
                    <button class="btn btn-disabled" disabled>No New Homework</button>
                <?php endif; ?>
            </div>

            <!-- Warmup Card -->
            <div class="card warmup">
                <div class="card-header">
                    <div class="card-icon">🔥</div>
                    <div>
                        <h3 class="card-title">Word Practice</h3>
                        <p class="card-subtitle">Master tricky words</p>
                    </div>
                </div>
                
                <?php if ($warmupCount > 0): ?>
                    <span class="status-badge" style="background: linear-gradient(135deg, var(--warning), #FF9A3D);">
                        <?= $warmupCount ?> word<?= $warmupCount > 1 ? 's' : '' ?> to practice!
                    </span>
                    
                    <p style="color: var(--text-light); margin: 20px 0 10px;">Practice these words to improve:</p>
                    
                    <div class="word-list">
                        <?php foreach ($recentWords as $word): ?>
                            <span class="word-tag"><?= htmlspecialchars($word) ?></span>
                        <?php endforeach; ?>
                        <?php if ($warmupCount > 5): ?>
                            <span class="word-tag more">+<?= $warmupCount - 5 ?> more</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-actions">
                        <a href="warmup_practice.php" class="btn btn-warning">
                            🎯 Start Practice
                            <span style="font-size: 20px;">→</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="no-content">
                        <p>Awesome! All words mastered! 🏆</p>
                        <p style="margin-top: 10px; font-size: 14px;">You're doing great! Keep it up!</p>
                    </div>
                    <button class="btn btn-disabled" disabled>All Words Mastered!</button>
                <?php endif; ?>
            </div>

            <!-- Progress Card -->
            <div class="card accuracy">
                <div class="card-header">
                    <div class="card-icon">📊</div>
                    <div>
                        <h3 class="card-title">My Progress</h3>
                        <p class="card-subtitle">Track your improvement</p>
                    </div>
                </div>
                
                <div class="accuracy-display">
                    <div class="accuracy-circle">
                        <svg width="160" height="160" class="progress-ring">
                            <defs>
                                <linearGradient id="progress-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color: var(--accent);" />
                                    <stop offset="100%" style="stop-color: var(--primary);" />
                                </linearGradient>
                            </defs>
                            <circle class="progress-ring-circle" cx="80" cy="80" r="70"></circle>
                            <circle class="progress-ring-progress" cx="80" cy="80" r="70"
                                stroke-dasharray="<?= 2 * 3.14159 * 70 ?>"
                                stroke-dashoffset="<?= 2 * 3.14159 * 70 * (1 - $accuracy/100) ?>">
                            </circle>
                        </svg>
                        <div class="accuracy-number"><?= $accuracy ?>%</div>
                    </div>
                    <div class="accuracy-label">Reading Accuracy</div>
                    
                    <div class="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star"><?= $i <= round($accuracy/20) ? '⭐' : '☆' ?></span>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-value"><?= $total_sessions ?></span>
                        <span class="stat-label">Sessions</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?= $accuracy ?>%</span>
                        <span class="stat-label">Accuracy</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?= number_format($total_words_read) ?></span>
                        <span class="stat-label">Words Read</span>
                    </div>
                </div>
                
                <div class="card-actions">
                    <a href="#" class="btn btn-primary">
                        📈 View Details
                        <span style="font-size: 20px;">→</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <a href="logout.php" class="logout-btn">
        <span>🚪</span>
        Logout
    </a>
</body>
</html>