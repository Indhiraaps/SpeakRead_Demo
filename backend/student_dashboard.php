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

    // 2. Count Warmup Words (incorrect words to practice)
    $warmupStmt = $pdo->prepare("SELECT COUNT(DISTINCT IncorrectWord) as word_count FROM Warmup WHERE SID = ?");
    $warmupStmt->execute([$student_id]);
    $warmupData = $warmupStmt->fetch();
    $warmupCount = $warmupData['word_count'] ?? 0;

    // 3. Get Recent Warmup Words (for display)
    $recentWordsStmt = $pdo->prepare("SELECT DISTINCT IncorrectWord FROM Warmup WHERE SID = ? LIMIT 5");
    $recentWordsStmt->execute([$student_id]);
    $recentWords = $recentWordsStmt->fetchAll();

    // 4. Calculate Accuracy from homework reading sessions
    // For now, set to 0 until we create the reading results tracking
    $accuracy = 0;
    $total_sessions = 0;
    $total_words_read = 0;
    
    // TODO: Later we'll create a ReadingResults table to track:
    // - Session date
    // - Total words in paragraph
    // - Correct words read
    // - Accuracy percentage per session

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard | SpeakRead</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
            animation: float 15s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 25px 35px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: slideDown 0.6s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .welcome {
            flex: 1;
        }

        .welcome h1 {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .welcome p {
            color: #64748b;
            font-size: 14px;
        }

        .streak-badge {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 15px 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
        }

        .streak-badge .number {
            font-size: 36px;
            font-weight: 800;
            display: block;
        }

        .streak-badge .label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 18px;
            padding: 25px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.12);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
            animation-fill-mode: both;
        }

        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card:nth-child(3) { animation-delay: 0.3s; }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 80px rgba(0,0,0,0.25);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .card.homework::before {
            background: linear-gradient(90deg, #10b981, #059669);
        }

        .card.warmup::before {
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }

        .card.accuracy::before {
            background: linear-gradient(90deg, #3b82f6, #2563eb);
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 18px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
        }

        .homework .card-icon {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .warmup .card-icon {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .accuracy .card-icon {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        .card h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
        }

        .card-content {
            margin-bottom: 18px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }

        .status-available {
            background: #dcfce7;
            color: #166534;
        }

        .status-none {
            background: #f1f5f9;
            color: #64748b;
        }

        .hw-topic {
            font-size: 16px;
            font-weight: 600;
            color: #334155;
            margin: 10px 0;
        }

        .hw-preview {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
            max-height: 60px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.5);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.5);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(245, 158, 11, 0.5);
        }

        .btn-disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-disabled:hover {
            transform: none;
        }

        .word-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 15px 0;
        }

        .word-tag {
            background: #fef3c7;
            color: #92400e;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .accuracy-circle {
            width: 130px;
            height: 130px;
            margin: 15px auto;
            position: relative;
        }

        .accuracy-number {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 42px;
            font-weight: 900;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .progress-ring-circle {
            stroke: #e0e7ff;
            fill: transparent;
            stroke-width: 8;
        }

        .progress-ring-progress {
            stroke: url(#gradient);
            fill: transparent;
            stroke-width: 8;
            stroke-linecap: round;
            transition: stroke-dashoffset 1s ease;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .stat-row:last-child {
            border-bottom: none;
        }

        .stat-label {
            color: #64748b;
            font-size: 14px;
        }

        .stat-value {
            font-weight: 700;
            color: #1e293b;
            font-size: 14px;
        }

        .logout-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.4);
            transition: all 0.3s ease;
            z-index: 100;
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(239, 68, 68, 0.5);
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .welcome h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <div class="welcome">
                <h1>Welcome back, <?= htmlspecialchars($student_name) ?>! 👋</h1>
                <p>Grade: <?= htmlspecialchars($grade) ?> • Today: <?= date('l, F j, Y') ?></p>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Homework Card -->
            <div class="card homework">
                <div class="card-icon">📚</div>
                <h2>Today's Homework</h2>
                <div class="card-content">
                    <?php if ($homework): ?>
                        <span class="status-badge status-available">✓ Available</span>
                        <div class="hw-topic"><?= htmlspecialchars($homework['H_Topic']) ?></div>
                        <div class="hw-preview"><?= htmlspecialchars(substr($homework['H_Para'], 0, 100)) ?>...</div>
                    <?php else: ?>
                        <span class="status-badge status-none">No Task Today</span>
                        <p style="color: #64748b; margin-top: 15px;">Your teacher hasn't assigned any homework for today. Check back later!</p>
                    <?php endif; ?>
                </div>
                <?php if ($homework): ?>
                    <a href="homework.php?hid=<?= $homework['HID'] ?>" class="btn btn-success">Start Homework →</a>
                <?php else: ?>
                    <button class="btn btn-disabled" disabled>No Homework</button>
                <?php endif; ?>
            </div>

            <!-- Warmup Card -->
            <div class="card warmup">
                <div class="card-icon">🔥</div>
                <h2>Warmup Session</h2>
                <div class="card-content">
                    <?php if ($warmupCount > 0): ?>
                        <span class="status-badge" style="background: #fef3c7; color: #92400e;">
                            <?= $warmupCount ?> Word<?= $warmupCount > 1 ? 's' : '' ?> to Practice
                        </span>
                        <p style="color: #64748b; margin: 15px 0;">Practice these words to improve your reading:</p>
                        <div class="word-list">
                            <?php foreach ($recentWords as $word): ?>
                                <span class="word-tag"><?= htmlspecialchars($word['IncorrectWord']) ?></span>
                            <?php endforeach; ?>
                            <?php if ($warmupCount > 5): ?>
                                <span class="word-tag">+<?= $warmupCount - 5 ?> more</span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <span class="status-badge status-none">All Clear!</span>
                        <p style="color: #64748b; margin-top: 15px;">Great job! You have no words to practice right now. Keep reading to maintain your skills!</p>
                    <?php endif; ?>
                </div>
                <?php if ($warmupCount > 0): ?>
                    <a href="warmup_session.php" class="btn btn-warning">Start Warmup →</a>
                <?php else: ?>
                    <button class="btn btn-disabled" disabled>No Practice Needed</button>
                <?php endif; ?>
            </div>

            <!-- Accuracy Card -->
            <div class="card accuracy">
                <div class="card-icon">📊</div>
                <h2>Your Progress</h2>
                <div class="card-content">
                    <div class="accuracy-circle">
                        <svg width="130" height="130" class="progress-ring">
                            <defs>
                                <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#2563eb;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <circle class="progress-ring-circle" cx="65" cy="65" r="55"></circle>
                            <circle class="progress-ring-progress" cx="65" cy="65" r="55"
                                stroke-dasharray="<?= 2 * 3.14159 * 55 ?>"
                                stroke-dashoffset="<?= 2 * 3.14159 * 55 * (1 - $accuracy/100) ?>">
                            </circle>
                        </svg>
                        <div class="accuracy-number"><?= $accuracy ?>%</div>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Reading Accuracy</span>
                        <span class="stat-value"><?= $accuracy ?>%</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Sessions Completed</span>
                        <span class="stat-value"><?= $total_sessions ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Total Words Read</span>
                        <span class="stat-value"><?= number_format($total_words_read) ?></span>
                    </div>
                </div>
                <a href="view_progress.php" class="btn btn-primary">View Details →</a>
            </div>
        </div>
    </div>

    <a href="logout.php" class="logout-btn">🚪 Logout</a>
</body>
</html>