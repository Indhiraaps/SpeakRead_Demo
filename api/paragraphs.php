<?php
session_start();
$host = 'mysql-19588968-speakread000.g.aivencloud.com'; $db = 'defaultdb';$port = '25249'; $user = 'avnadmin'; $pass = 'AVNS_-hJYen-fDyBu9ApXbxH';
$grade = $_GET['grade'] ?? '';
$lesson = $_GET['lesson'] ?? '';

if (empty($grade) || empty($lesson)) {
    die("Missing grade or lesson parameter");
}

// ⭐ CRITICAL FIX: Extract base grade (e.g., "Grade 3 - A" → "Grade 3")
// This allows lessons stored as "Grade 3" to match students in "Grade 3 - A", "Grade 3 - B", etc.
if (preg_match('/^(Grade\s+\d+)/i', $grade, $matches)) {
    $baseGrade = $matches[1]; // Results in "Grade 3"
} else {
    $baseGrade = $grade; // Fallback to original if pattern doesn't match
}

try {
    // Change this line:
$pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get students for this FULL grade (with section: "Grade 3 - A")
    $stmtS = $pdo->prepare("SELECT SID, Name FROM Students WHERE LOWER(Grade) = LOWER(?) ORDER BY Name");
    $stmtS->execute([$grade]); // Use original grade for students
    $students = $stmtS->fetchAll(PDO::FETCH_ASSOC);

    // ⭐ FIX: Get paragraphs using BASE GRADE (without section: "Grade 3")
    // This is the key fix - lessons are stored without section suffixes
    $stmtP = $pdo->prepare("SELECT LID, ParaNumber, Para FROM Lessons 
                            WHERE LOWER(TRIM(LessonName)) = LOWER(TRIM(?)) 
                            AND LOWER(TRIM(Grade)) = LOWER(TRIM(?))
                            ORDER BY ParaNumber ASC");
    $stmtP->execute([$lesson, $baseGrade]); // Use baseGrade instead of $grade
    $paras = $stmtP->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) { 
    die("Database Error: " . $e->getMessage()); 
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($lesson); ?> - SpeakRead</title>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; margin: 0; display: flex; }
        .sidebar { 
            width: 260px; height: 100vh; position: fixed; left: 0; top: 0; 
            background: white; border-right: 1px solid #e2e8f0; padding: 24px; 
            box-sizing: border-box; 
        }
        .content { 
            margin-left: 260px; padding: 40px; 
            width: calc(100% - 260px); box-sizing: border-box; 
        }
        .logo { 
            color: #2563eb; font-weight: 800; font-size: 24px; 
            text-decoration: none; display: block; margin-bottom: 40px; 
        }
        .back-btn {
            background: none; border: 1px solid #e2e8f0; 
            padding: 10px 16px; border-radius: 8px; cursor: pointer;
            color: #64748b; font-size: 14px; transition: 0.2s;
        }
        .back-btn:hover {
            background: #f8fafc; border-color: #cbd5e1;
        }
        .main-card { 
            background: white; border: 1px solid #e2e8f0; 
            border-radius: 16px; padding: 32px; margin-bottom: 24px; 
        }
        .para-card { 
            border: 1px solid #e2e8f0; border-radius: 12px; 
            padding: 24px; margin-bottom: 16px; background: white; 
            transition: 0.2s; cursor: pointer; 
        }
        .para-card:hover { 
            border-color: #2563eb; 
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }
        .para-header {
            font-weight: 800; color: #2563eb; font-size: 13px; 
            margin-bottom: 12px; text-transform: uppercase; 
            letter-spacing: 0.5px;
        }
        .para-text { 
            color: #475569; font-size: 16px; line-height: 1.7; 
        }
        .assign-section { 
            display: none; margin-top: 20px; padding-top: 20px; 
            border-top: 1px solid #f1f5f9; 
        }
        .assign-section.active {
            display: block;
        }
        .assign-controls {
            display: flex; gap: 12px; align-items: center;
        }
        select { 
            padding: 10px 12px; border-radius: 8px; 
            border: 1px solid #cbd5e1; flex: 1; font-size: 14px;
            background: white; cursor: pointer;
        }
        select:focus {
            outline: none; border-color: #2563eb;
        }
        .btn-start { 
            background: #2563eb; color: white; border: none; 
            padding: 10px 24px; border-radius: 8px; cursor: pointer; 
            font-weight: 600; font-size: 14px; transition: 0.2s;
            white-space: nowrap;
        }
        .btn-start:hover {
            background: #1d4ed8;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center; padding: 80px 20px; color: #64748b;
        }
        .empty-state-icon {
            font-size: 80px; margin-bottom: 20px; opacity: 0.4;
        }
        .empty-state h3 {
            font-size: 24px; color: #1e293b; margin-bottom: 10px;
        }
        .empty-state p {
            font-size: 16px; max-width: 500px; margin: 0 auto;
            line-height: 1.6;
        }
        
        .warning-box {
            background: #fef3c7; border: 1px solid #fbbf24;
            padding: 12px 16px; border-radius: 8px; 
            font-size: 14px; color: #92400e; margin-top: 15px;
        }
        
        /* Debug info (remove in production) */
        .debug-info {
            background: #f0f9ff; border: 1px solid #bae6fd;
            padding: 12px 16px; border-radius: 8px;
            font-size: 12px; color: #0c4a6e; margin-bottom: 20px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="#" class="logo">SpeakRead</a>
        <button class="back-btn" onclick="window.history.back()">← Back</button>
    </div>

    <div class="content">
        <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 30px;">
            <?php echo htmlspecialchars($lesson); ?>
        </h1>
        
        <!-- DEBUG INFO (remove in production) -->
        <?php if (isset($_GET['debug'])): ?>
        <div class="debug-info">
            <strong>🔍 Debug Information:</strong><br>
            Full Grade (from URL): <?= htmlspecialchars($grade) ?><br>
            Base Grade (for query): <?= htmlspecialchars($baseGrade) ?><br>
            Lesson Name: <?= htmlspecialchars($lesson) ?><br>
            Paragraphs Found: <?= count($paras) ?><br>
            Students Found: <?= count($students) ?>
        </div>
        <?php endif; ?>
        
        <div class="main-card">
            <?php if (empty($paras)): ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-state-icon">📄</div>
                    <h3>No Paragraphs Found</h3>
                    <p>
                        There are no paragraphs available for <strong><?= htmlspecialchars($lesson) ?></strong> 
                        in <strong><?= htmlspecialchars($baseGrade) ?></strong>.
                    </p>
                    <p style="margin-top: 15px; font-size: 14px; color: #94a3b8;">
                        Tip: Make sure lessons are added to the database for "<?= htmlspecialchars($baseGrade) ?>" (without section suffix).
                    </p>
                </div>
            <?php else: ?>
                <!-- Paragraph Cards -->
                <?php foreach($paras as $p): ?>
                    <div class="para-card" onclick="toggleAssign(<?php echo $p['LID']; ?>)">
                        <div class="para-header">
                            Paragraph <?php echo $p['ParaNumber']; ?>
                        </div>
                        <div class="para-text">
                            <?php echo nl2br(htmlspecialchars($p['Para'])); ?>
                        </div>
                        
                        <?php if (!empty($students)): ?>
                        <div id="assign-<?php echo $p['LID']; ?>" class="assign-section" onclick="event.stopPropagation()">
                            <div class="assign-controls">
                                <select id="student-<?php echo $p['LID']; ?>">
                                    <option value="">Select a student...</option>
                                    <?php foreach($students as $s): ?>
                                        <option value="<?= $s['SID'] ?>"><?= htmlspecialchars($s['Name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn-start" onclick="goToPractice(<?php echo $p['LID']; ?>)">
                                    Start Session
                                </button>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="warning-box">
                            ⚠️ No students enrolled in <?= htmlspecialchars($grade) ?>. 
                            Please add students to this class first.
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleAssign(lid) {
            // Close all other assign sections
            document.querySelectorAll('.assign-section').forEach(el => {
                el.classList.remove('active');
            });
            
            // Toggle the clicked section
            const section = document.getElementById('assign-' + lid);
            section.classList.add('active');
        }

        function goToPractice(lid) {
            const selectEl = document.getElementById('student-' + lid);
            const sid = selectEl.value;
            
            if (!sid || sid === "") {
                alert('Please select a student first');
                selectEl.focus();
                return;
            }
            
            window.location.href = `reading_practice.php?lid=${lid}&sid=${sid}`;
        }
    </script>
</body>
</html>