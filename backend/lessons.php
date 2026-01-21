<?php
session_start();
$host = 'localhost'; $db = 'speakread_db'; $user = 'root'; $pass = 'skdn1418';
$grade = $_GET['grade'] ?? 'Grade 3';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmtL = $pdo->prepare("SELECT DISTINCT LessonNumber, LessonName FROM Lessons WHERE Grade = ? ORDER BY LessonNumber ASC");
    $stmtL->execute([$grade]);
    $lessons = $stmtL->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Management - <?php echo htmlspecialchars($grade); ?></title>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; margin: 0; display: flex; }
        .sidebar { width: 260px; height: 100vh; position: fixed; left: 0; top: 0; background: white; border-right: 1px solid #e2e8f0; padding: 24px; box-sizing: border-box; }
        .content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); box-sizing: border-box; }
        .logo { color: #2563eb; font-weight: 800; font-size: 24px; text-decoration: none; display: block; margin-bottom: 40px; }
        .back-link { color: #64748b; text-decoration: none; font-size: 14px; }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .main-card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; margin-bottom: 24px; }
        .section-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: #1e293b; }
        .btn { background: #2563eb; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; }
        .long-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 24px; margin-bottom: 12px; cursor: pointer; transition: 0.2s; background: white; display: flex; align-items: center; text-decoration: none; color: #334155; font-weight: 600; }
        .long-card:hover { border-color: #2563eb; background: #f8fafc; transform: translateX(5px); }
        .num-circle { width: 32px; height: 32px; background-color: #ebf2ff; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; margin-right: 15px; flex-shrink: 0; }
        
        /* Modal Styles */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000; }
        .modal-content { background:white; padding:30px; border-radius:12px; width:400px; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="#" class="logo">SpeakRead</a>
        <a href="teacher_dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>

    <div class="content">
        <div class="header-row">
            <h1 style="font-size: 32px; font-weight: 800; margin: 0;">Management: <?php echo htmlspecialchars($grade); ?></h1>
            <button class="btn" onclick="document.getElementById('studentModal').style.display='flex'">+ Add Student</button>
        </div>
        
        <div class="main-card">
            <div class="section-title">Weekly Homework Status</div>
            <div style="display: flex; gap: 12px;">
                <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
                    <div style="background: #eff6ff; padding: 12px; border-radius: 8px; text-align: center; flex: 1; border: 1px solid #dbeafe;">
                        <div style="font-size: 11px; font-weight: 800; color: #3b82f6; margin-bottom: 5px;"><?php echo $d; ?></div>
                        <input type="checkbox" checked>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="main-card">
            <div class="section-title">Select a Lesson</div>
            <?php foreach($lessons as $l): ?>
                <a href="paragraphs.php?grade=<?php echo urlencode($grade); ?>&lesson=<?php echo urlencode($l['LessonName']); ?>" class="long-card">
                    <div class="num-circle"><?php echo htmlspecialchars($l['LessonNumber']); ?></div>
                    <?php echo htmlspecialchars($l['LessonName']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="studentModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-top:0;">Add Student(s)</h2>
            <div style="display:flex; border-bottom:1px solid #eee; margin-bottom:15px;">
                <button onclick="toggleTab('single')" id="tab-single" style="flex:1; padding:10px; border:none; background:none; cursor:pointer; font-weight:bold; border-bottom:2px solid #2563eb;">Individual</button>
                <button onclick="toggleTab('bulk')" id="tab-bulk" style="flex:1; padding:10px; border:none; background:none; cursor:pointer; color:#64748b;">Bulk Upload</button>
            </div>

            <form id="form-single" action="add_student_action.php" method="POST">
                <input type="hidden" name="grade" value="<?php echo htmlspecialchars($grade); ?>">
                <input type="text" name="name" placeholder="Student Name" required>
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="btn" style="width:100%;">Save Student</button>
            </form>

            <form id="form-bulk" action="upload_students.php" method="POST" enctype="multipart/form-data" style="display:none;">
                <input type="hidden" name="grade_name" value="<?php echo htmlspecialchars($grade); ?>">
                <p style="font-size:12px; color:#64748b;">Select CSV/Excel file with columns: Name, Email, Password</p>
                <input type="file" name="student_excel" accept=".csv, .xlsx" required>
                <button type="submit" class="btn" style="width:100%;">Upload List</button>
            </form>

            <button type="button" class="btn" style="width:100%; background:#94a3b8; margin-top:10px;" onclick="document.getElementById('studentModal').style.display='none'">Cancel</button>
        </div>
    </div>

    <script>
        function toggleTab(type) {
            const singleForm = document.getElementById('form-single');
            const bulkForm = document.getElementById('form-bulk');
            const singleTab = document.getElementById('tab-single');
            const bulkTab = document.getElementById('tab-bulk');

            if (type === 'single') {
                singleForm.style.display = 'block';
                bulkForm.style.display = 'none';
                singleTab.style.borderBottom = '2px solid #2563eb';
                singleTab.style.color = '#000';
                bulkTab.style.borderBottom = 'none';
                bulkTab.style.color = '#64748b';
            } else {
                singleForm.style.display = 'none';
                bulkForm.style.display = 'block';
                bulkTab.style.borderBottom = '2px solid #2563eb';
                bulkTab.style.color = '#000';
                singleTab.style.borderBottom = 'none';
                singleTab.style.color = '#64748b';
            }
        }
    </script>
</body>
</html>