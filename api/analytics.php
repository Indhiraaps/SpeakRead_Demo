<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../frontend/login.html");
    exit();
}

$student_id = $_SESSION['user_id'];

try {
    // 1. DAILY CHART - Grouped by Day (Mon-Sun)
    $dailyStmt = $pdo->prepare("
        SELECT 
            DAYNAME(DateCompleted) as day_name, 
            AVG(Accuracy) as avg_acc,
            DAYOFWEEK(DateCompleted) as day_index
        FROM Scores 
        WHERE SID = ? AND DateCompleted >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY day_index, day_name
        ORDER BY day_index
    ");
    $dailyStmt->execute([$student_id]);
    $rawDailyData = $dailyStmt->fetchAll();

    $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $finalDailyLabels = [];
    $finalDailyData = [];

    foreach ($daysOfWeek as $day) {
        $found = false;
        foreach ($rawDailyData as $row) {
            if ($row['day_name'] == $day) {
                $finalDailyData[] = round($row['avg_acc'], 2);
                $found = true;
                break;
            }
        }
        if (!$found) $finalDailyData[] = 0; 
        $finalDailyLabels[] = $day;
    }

    // 2. WEEKLY TREND
    $weeklyStmt = $pdo->prepare("
        SELECT WEEK(DateCompleted) as wk, AVG(Accuracy) as avg_acc 
        FROM Scores 
        WHERE SID = ? AND MONTH(DateCompleted) = MONTH(CURDATE()) AND YEAR(DateCompleted) = YEAR(CURDATE())
        GROUP BY wk ORDER BY wk ASC
    ");
    $weeklyStmt->execute([$student_id]);
    $weeklyData = $weeklyStmt->fetchAll();

    // 3. YEARLY RECORD - Monthly Average (01, 02, etc.)
    $yearlyStmt = $pdo->prepare("
        SELECT 
            MONTH(DateCompleted) as m_num, 
            MONTHNAME(DateCompleted) as m_name, 
            AVG(Accuracy) as avg_acc
        FROM Scores 
        WHERE SID = ? AND YEAR(DateCompleted) = YEAR(CURDATE())
        GROUP BY m_num, m_name 
        ORDER BY m_num ASC
    ");
    $yearlyStmt->execute([$student_id]);
    $yearlyTable = $yearlyStmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Progress Analytics | SpeakRead</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6C63FF;
            --primary-light: #8A84FF;
            --secondary: #FF8A63;
            --accent: #63D2FF;
            --success: #4ECB71;
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
            padding: 30px 20px;
            color: var(--text);
            position: relative;
            overflow-x: hidden;
        }

        /* Floating Background Elements */
        .floating-elements {
            position: fixed;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            top: 0;
            left: 0;
        }

        .floating-elements div {
            position: absolute;
            background: rgba(108, 99, 255, 0.08);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite;
        }

        .floating-elements div:nth-child(1) {
            width: 120px;
            height: 120px;
            top: 15%;
            left: 8%;
            animation-delay: 0s;
        }

        .floating-elements div:nth-child(2) {
            width: 180px;
            height: 180px;
            top: 50%;
            right: 5%;
            background: rgba(255, 138, 99, 0.08);
            animation-delay: 2s;
        }

        .floating-elements div:nth-child(3) {
            width: 90px;
            height: 90px;
            bottom: 25%;
            left: 15%;
            background: rgba(99, 210, 255, 0.08);
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(10deg); }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* Header Section */
        .header {
            background: var(--card-bg);
            padding: 30px 40px;
            border-radius: 24px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(108, 99, 255, 0.1);
            border: 3px solid var(--primary);
            position: relative;
            overflow: hidden;
            animation: slideDown 0.6s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
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

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            box-shadow: 0 8px 20px rgba(108, 99, 255, 0.3);
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .header h1 {
            font-size: 32px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header p {
            font-size: 15px;
            color: var(--text-light);
            margin-top: 5px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            text-decoration: none;
            border-radius: 16px;
            font-weight: 800;
            font-size: 16px;
            box-shadow: 0 8px 20px rgba(107, 114, 128, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(107, 114, 128, 0.4);
        }

        /* Chart Cards */
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
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
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(108, 99, 255, 0.1);
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-title-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            box-shadow: 0 4px 12px rgba(108, 99, 255, 0.3);
        }

        .card-title h2 {
            font-size: 22px;
            font-weight: 800;
            color: var(--text);
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-badge {
            background: linear-gradient(135deg, var(--success), #5ED582);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(78, 203, 113, 0.3);
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Table Card */
        .table-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            animation: slideUp 0.6s ease-out 0.3s both;
        }

        .yearly-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
            border-radius: 12px;
        }

        .yearly-table thead {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
        }

        .yearly-table th {
            padding: 16px 12px;
            text-align: center;
            font-size: 14px;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .yearly-table tbody tr {
            transition: all 0.3s ease;
            background: white;
        }

        .yearly-table tbody tr:nth-child(even) {
            background: rgba(108, 99, 255, 0.03);
        }

        .yearly-table tbody tr:hover {
            background: rgba(108, 99, 255, 0.08);
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(108, 99, 255, 0.1);
        }

        .yearly-table td {
            padding: 18px 12px;
            text-align: center;
            font-weight: 700;
            font-size: 16px;
            color: var(--text);
            border-bottom: 1px solid rgba(226, 232, 240, 0.5);
        }

        .month-id {
            display: inline-block;
            background: linear-gradient(135deg, var(--accent), #7BDCFF);
            color: white;
            width: 36px;
            height: 36px;
            line-height: 36px;
            border-radius: 50%;
            font-size: 14px;
            font-weight: 900;
            box-shadow: 0 4px 12px rgba(99, 210, 255, 0.3);
        }

        .accuracy-cell {
            font-size: 20px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--success), #5ED582);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .month-name {
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 20px 15px;
            }

            .header {
                padding: 25px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .card {
                padding: 20px;
            }

            .card-title h2 {
                font-size: 18px;
            }

            .chart-container {
                height: 250px;
            }

            .yearly-table th,
            .yearly-table td {
                padding: 12px 8px;
                font-size: 13px;
            }

            .month-id {
                width: 32px;
                height: 32px;
                line-height: 32px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <div></div>
        <div></div>
        <div></div>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="header-title">
                    <div class="header-icon">📊</div>
                    <div>
                        <h1>My Progress Analytics</h1>
                        <p>Track your reading improvement journey</p>
                    </div>
                </div>
                <a href="student_dashboard.php" class="back-btn">
                    <span>←</span>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Chart Grid -->
        <div class="chart-grid">
            <!-- Daily Chart -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <div class="card-title-icon">📅</div>
                        <h2>Daily Performance</h2>
                    </div>
                    <span class="card-badge">This week</span>
                </div>
                <div class="chart-container">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>

            <!-- Weekly Chart -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <div class="card-title-icon">📈</div>
                        <h2><?= strtoupper(date('F')) ?> Progress</h2>
                    </div>
                    <span class="card-badge">This Month</span>
                </div>
                <div class="chart-container">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Yearly Records Table -->
        <div class="table-card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-title-icon">🏆</div>
                    <h2>Yearly Records</h2>
                </div>
                <span class="card-badge"><?= date('Y') ?></span>
            </div>

            <?php if (empty($yearlyTable)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📊</div>
                    <h3>No data yet for this year</h3>
                    <p>Complete some practice sessions to see your yearly progress!</p>
                </div>
            <?php else: ?>
                <table class="yearly-table">
                    <thead>
                        <tr>
                            <th>Month ID</th>
                            <th>Month Name</th>
                            <th>Average Accuracy</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($yearlyTable as $row): ?>
                        <tr>
                            <td>
                                <span class="month-id"><?= str_pad($row['m_num'], 2, '0', STR_PAD_LEFT) ?></span>
                            </td>
                            <td>
                                <span class="month-name"><?= strtoupper(substr($row['m_name'], 0, 3)) ?></span>
                            </td>
                            <td>
                                <span class="accuracy-cell"><?= round($row['avg_acc']) ?>%</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

   <script>
// Chart.js Configuration
Chart.defaults.maintainAspectRatio = false;
Chart.defaults.font.family = "'Nunito', sans-serif";
Chart.defaults.font.size = 13;
Chart.defaults.color = '#718096';

// 1. Daily Bar Chart
const dailyCtx = document.getElementById('dailyChart').getContext('2d');

// Convert full day names to short forms
const fullDays = <?= json_encode($finalDailyLabels) ?>;
const shortDays = fullDays.map(day => day.substring(0, 3)); // Mon, Tue, Wed, etc.

new Chart(dailyCtx, {
    type: 'bar',
    data: {
        labels: shortDays, // Changed from full names to short names
        datasets: [{
            label: 'Accuracy %',
            data: <?= json_encode($finalDailyData) ?>,
            backgroundColor: 'rgba(108, 99, 255, 0.8)',
            borderColor: '#6C63FF',
            borderWidth: 2,
            borderRadius: 8,
            barThickness: 40,
            hoverBackgroundColor: '#8A84FF'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(45, 55, 72, 0.95)',
                padding: 12,
                titleFont: { size: 14, weight: '700' },
                bodyFont: { size: 13 },
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return 'Accuracy: ' + context.parsed.y + '%';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    },
                    font: { size: 12, weight: '600' }
                },
                grid: {
                    color: 'rgba(226, 232, 240, 0.5)',
                    drawBorder: false
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: { size: 12, weight: '700' },
                    maxRotation: 0, // Keep labels straight (not rotated)
                    minRotation: 0  // Keep labels straight (not rotated)
                }
            }
        }
    }
});

// 2. Weekly Line Chart
const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
const gradient = weeklyCtx.createLinearGradient(0, 0, 0, 300);
gradient.addColorStop(0, 'rgba(99, 210, 255, 0.4)');
gradient.addColorStop(1, 'rgba(99, 210, 255, 0.0)');

new Chart(weeklyCtx, {
    type: 'line',
    data: {
        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
        datasets: [{
            label: 'Accuracy %',
            data: <?= json_encode(array_column($weeklyData, 'avg_acc')) ?>,
            borderColor: '#63D2FF',
            backgroundColor: gradient,
            pointBackgroundColor: '#63D2FF',
            pointBorderColor: '#fff',
            pointBorderWidth: 3,
            pointRadius: 6,
            pointHoverRadius: 8,
            fill: true,
            tension: 0.4,
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(45, 55, 72, 0.95)',
                padding: 12,
                titleFont: { size: 14, weight: '700' },
                bodyFont: { size: 13 },
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return 'Accuracy: ' + context.parsed.y.toFixed(1) + '%';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    },
                    font: { size: 12, weight: '600' }
                },
                grid: {
                    color: 'rgba(226, 232, 240, 0.5)',
                    drawBorder: false
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: { size: 12, weight: '700' }
                }
            }
        }
    }
});
</script>
</body>
</html>