<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../frontend/login.html");
    exit();
}

$grade = $_GET['grade'] ?? 'Grade 3';

// 🎯 EXTRACT BASE GRADE (e.g., "Grade 3-A" → "Grade 3")
if (preg_match('/^(Grade\s+\d+)/i', $grade, $matches)) {
    $baseGrade = $matches[1];
} else {
    $baseGrade = $grade;
}

try {
    // 📚 FETCH LESSONS USING BASE GRADE (without section)
    $stmtL = $pdo->prepare("SELECT DISTINCT LessonNumber, LessonName FROM Lessons WHERE Grade = ? ORDER BY LessonNumber ASC");
    $stmtL->execute([$baseGrade]);
    $lessons = $stmtL->fetchAll(PDO::FETCH_ASSOC);
    
    // 📅 GET HOMEWORK SCHEDULE FOR THIS GRADE
    $scheduleStmt = $pdo->prepare("SELECT * FROM HomeworkSchedule WHERE Grade = ?");
    $scheduleStmt->execute([$baseGrade]);
    $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
    
    // Default: all days enabled if no schedule exists
    $days = [
        'Mon' => 1, 
        'Tue' => 1, 
        'Wed' => 1, 
        'Thu' => 1, 
        'Fri' => 1, 
        'Sat' => 1, 
        'Sun' => 1
    ];
    
    if ($schedule) {
        $days = [
            'Mon' => $schedule['Mon'],
            'Tue' => $schedule['Tue'],
            'Wed' => $schedule['Wed'],
            'Thu' => $schedule['Thu'],
            'Fri' => $schedule['Fri'],
            'Sat' => $schedule['Sat'],
            'Sun' => $schedule['Sun']
        ];
    }
    
} catch (PDOException $e) { 
    die("Error: " . $e->getMessage()); 
}
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
        .section-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: #1e293b; }
        .btn { background: #2563eb; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .btn:hover { background: #1d4ed8; }
        
        /* Success Message */
        .success-message {
            background: #dcfce7;
            color: #166534;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            font-weight: 600;
        }
        
        /* Weekly Homework Status */
        .week-grid { display: flex; gap: 12px; margin-bottom: 20px; }
        .day-box { 
            background: #eff6ff; 
            padding: 15px; 
            border-radius: 8px; 
            text-align: center; 
            flex: 1; 
            border: 2px solid #dbeafe;
            transition: all 0.3s;
            cursor: pointer;
        }
        .day-box:hover {
            background: #dbeafe;
        }
        .day-box.inactive {
            background: #f1f5f9;
            border-color: #cbd5e1;
            opacity: 0.6;
        }
        .day-box div:first-child { 
            font-size: 11px; 
            font-weight: 800; 
            color: #3b82f6; 
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .day-box.inactive div:first-child {
            color: #64748b;
        }
        .day-box input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .save-schedule-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
        }
        .save-schedule-btn:hover {
            background: #059669;
        }
        
        .schedule-info {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 15px;
            padding: 10px 15px;
            background: #f8fafc;
            border-left: 3px solid #3b82f6;
            border-radius: 4px;
        }
        
        /* Modal Styles */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000; }
        .modal-content { background:white; padding:30px; border-radius:12px; width:400px; }
        input[type="text"], input[type="email"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #cbd5e1; border-radius: 6px; }

        /* Password Wrapper */
        .password-wrapper { position: relative; display: flex; align-items: center; width: 100%; margin: 10px 0; }
        .password-wrapper input { padding-right: 45px; margin: 0; width: 100%; }
        .toggle-btn { position: absolute; right: 12px; background: none; border: none; cursor: pointer; color: #94a3b8; display: flex; align-items: center; }

        /* TWO CARDS SECTION */
        .cards-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
        .action-card { 
            background: white; 
            border: 2px solid #e2e8f0; 
            border-radius: 16px; 
            padding: 40px; 
            text-align: center; 
            cursor: pointer; 
            transition: all 0.3s ease;
        }
        .action-card:hover { 
            transform: translateY(-5px); 
            border-color: #2563eb; 
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.15);
        }
        .action-card .icon { font-size: 64px; margin-bottom: 20px; display: block; }
        .action-card h3 { font-size: 24px; font-weight: 800; color: #1e293b; margin: 0 0 10px 0; }
        .action-card p { color: #64748b; font-size: 14px; margin: 0; }

        /* LESSONS LIST (Hidden by default) */
        #lessonsSection { display: none; }
        .long-card { 
            border: 1px solid #e2e8f0; 
            border-radius: 12px; 
            padding: 16px 24px; 
            margin-bottom: 12px; 
            cursor: pointer; 
            transition: 0.2s; 
            background: white; 
            display: flex; 
            align-items: center; 
            text-decoration: none; 
            color: #334155; 
            font-weight: 600;
        }
        .long-card:hover { border-color: #2563eb; background: #f8fafc; }
        .num-circle { 
            width: 32px; height: 32px; background-color: #ebf2ff; color: #2563eb; 
            border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            font-size: 14px; font-weight: 800; margin-right: 15px; flex-shrink: 0;
        }

        /* Back Button */
        .back-link { 
            color: #64748b; text-decoration: none; font-size: 14px; 
            display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px;
        }
        .back-link:hover { color: #2563eb; }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="teacher_dashboard.php" class="logo">SpeakRead</a>
        <a href="teacher_dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>

    <div class="content">
        <div class="header-row">
            <h1 style="font-size: 32px; font-weight: 800; margin: 0;">Management: <?php echo htmlspecialchars($grade); ?></h1>
            <button class="btn" onclick="document.getElementById('studentModal').style.display='flex'">+ Add Student</button>
        </div>

        <!-- Success Message -->
        <div class="success-message" id="successMessage">
            ✓ Homework schedule updated successfully!
        </div>

        <!-- Weekly Homework Status -->
        <div class="main-card">
            <div class="section-title">Weekly Homework Status</div>
            <div class="schedule-info">
                ℹ️ Uncheck a day to disable homework assignment for that day. Students won't see homework on disabled days.
            </div>
            <form id="scheduleForm">
                <div class="week-grid">
                    <?php 
                    $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                    foreach($dayNames as $day): 
                        $isActive = $days[$day] == 1;
                    ?>
                        <div class="day-box <?= !$isActive ? 'inactive' : '' ?>" id="box-<?= $day ?>" onclick="toggleDayClick('<?= $day ?>')">
                            <div><?= $day ?></div>
                            <input 
                                type="checkbox" 
                                name="<?= $day ?>" 
                                id="check-<?= $day ?>"
                                value="1"
                                <?= $isActive ? 'checked' : '' ?>
                                onclick="event.stopPropagation(); toggleDayBox('<?= $day ?>', this.checked)"
                            >
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="save-schedule-btn" onclick="saveSchedule()">
                    💾 Save Schedule
                </button>
            </form>
        </div>

        <!-- TWO ACTION CARDS -->
        <div id="cardsSection">
            <div class="cards-grid">
                <div class="action-card" onclick="showLessons()">
                    <span class="icon">📚</span>
                    <h3>Lessons</h3>
                    <p>View and manage all lessons for this grade</p>
                </div>

                <div class="action-card" onclick="showReports()">
                    <span class="icon">📊</span>
                    <h3>Student Reports</h3>
                    <p>View student progress and performance data</p>
                </div>
            </div>
        </div>

        <!-- LESSONS LIST (Hidden initially) -->
        <div id="lessonsSection">
            <div class="main-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div class="section-title" style="margin: 0;">Select a Lesson</div>
                    <button class="btn" style="background: #64748b;" onclick="hideLessons()">← Back</button>
                </div>
                <?php if (empty($lessons)): ?>
                    <div style="text-align: center; padding: 40px; color: #64748b;">
                        <div style="font-size: 48px; margin-bottom: 15px;">📚</div>
                        <p>No lessons available for <?= htmlspecialchars($baseGrade) ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach($lessons as $l): ?>
                        <a href="paragraphs.php?grade=<?php echo urlencode($grade); ?>&lesson=<?php echo urlencode($l['LessonName']); ?>" class="long-card">
                            <div class="num-circle"><?php echo htmlspecialchars($l['LessonNumber']); ?></div>
                            <?php echo htmlspecialchars($l['LessonName']); ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
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
                <div class="password-wrapper">
                    <input type="password" name="password" id="student_pass" placeholder="Password" required>
                    <button type="button" class="toggle-btn" onclick="togglePassword('student_pass', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
                <button type="submit" class="btn" style="width:100%; margin-top: 10px;">Save Student</button>
            </form>

            <form id="form-bulk" action="upload_students.php" method="POST" enctype="multipart/form-data" style="display:none;">
                <input type="hidden" name="grade_name" value="<?php echo htmlspecialchars($grade); ?>">
                <p style="font-size:12px; color:#64748b;">Select CSV/Excel file with columns: Name, Email, Password</p>
                <input type="file" name="student_excel" accept=".csv, .xlsx" required>
                <button type="submit" class="btn" style="width:100%; margin-top: 10px;">Upload List</button>
            </form>

            <button type="button" class="btn" style="width:100%; background:#94a3b8; margin-top:10px;" onclick="document.getElementById('studentModal').style.display='none'">Cancel</button>
        </div>
    </div>

    <script>
    const grade = '<?= addslashes($baseGrade) ?>';

    function toggleDayClick(day) {
        const checkbox = document.getElementById('check-' + day);
        checkbox.checked = !checkbox.checked;
        toggleDayBox(day, checkbox.checked);
    }

    function toggleDayBox(day, isChecked) {
        const box = document.getElementById('box-' + day);
        if (isChecked) {
            box.classList.remove('inactive');
        } else {
            box.classList.add('inactive');
        }
    }

    function saveSchedule() {
        const formData = new FormData(document.getElementById('scheduleForm'));
        
        // Create schedule object (default all to 0, then set checked to 1)
        const schedule = {
            grade: grade,
            Mon: formData.get('Mon') ? 1 : 0,
            Tue: formData.get('Tue') ? 1 : 0,
            Wed: formData.get('Wed') ? 1 : 0,
            Thu: formData.get('Thu') ? 1 : 0,
            Fri: formData.get('Fri') ? 1 : 0,
            Sat: formData.get('Sat') ? 1 : 0,
            Sun: formData.get('Sun') ? 1 : 0
        };

        console.log('Saving schedule:', schedule);

        fetch('save_homework_schedule.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(schedule)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const msg = document.getElementById('successMessage');
                msg.style.display = 'block';
                setTimeout(() => {
                    msg.style.display = 'none';
                }, 3000);
                console.log('✅ Schedule saved:', data);
            } else {
                alert('Error saving schedule: ' + data.message);
                console.error('❌ Save failed:', data);
            }
        })
        .catch(err => {
            console.error('❌ Fetch error:', err);
            alert('Failed to save schedule. Please try again.');
        });
    }

    function showLessons() {
        document.getElementById('cardsSection').style.display = 'none';
        document.getElementById('lessonsSection').style.display = 'block';
    }

    function hideLessons() {
        document.getElementById('lessonsSection').style.display = 'none';
        document.getElementById('cardsSection').style.display = 'block';
    }

    function showReports() {
        window.location.href = 'student_reports.php?grade=' + encodeURIComponent('<?php echo $grade; ?>');
    }

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

    // Close modal when clicking outside
    document.getElementById('studentModal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
    </script>
</body>
</html>