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
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .main-card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; margin-bottom: 24px; }
        .btn { background: #2563eb; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; }
        
        /* Modal Styles restored from original */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000; }
        .modal-content { background:white; padding:30px; border-radius:12px; width:400px; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; }

        /* Professional Toggle Positioning */
        .password-wrapper { position: relative; display: flex; align-items: center; width: 100%; }
        .password-wrapper input { padding-right: 45px; margin: 0; }
        .toggle-btn { position: absolute; right: 12px; background: none; border: none; cursor: pointer; color: #94a3b8; display: flex; align-items: center; }
        
        .long-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 24px; margin-bottom: 12px; cursor: pointer; transition: 0.2s; background: white; display: flex; align-items: center; text-decoration: none; color: #334155; font-weight: 600; }
        .num-circle { width: 32px; height: 32px; background-color: #ebf2ff; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; margin-right: 15px; flex-shrink: 0; }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="#" class="logo">SpeakRead</a>
        <a href="teacher_dashboard.php" style="color: #64748b; text-decoration: none; font-size: 14px;">← Back to Dashboard</a>
    </div>

    <div class="content">
        <div class="header-row">
            <h1 style="font-size: 32px; font-weight: 800; margin: 0;">Management: <?php echo htmlspecialchars($grade); ?></h1>
            <button class="btn" onclick="document.getElementById('studentModal').style.display='flex'">+ Add Student</button>
        </div>
        
        <div class="main-card">
            <div style="font-size: 18px; font-weight: 700; margin-bottom: 20px;">Select a Lesson</div>
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
                <div class="password-wrapper" style="margin: 10px 0;">
                    <input type="password" name="password" id="student_pass" placeholder="Password" required>
                    <button type="button" class="toggle-btn" onclick="togglePassword('student_pass', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
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

        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('svg');
            if (input.type === "password") {
                input.type = "text";
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = "password";
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }
    </script>
</body>
</html>