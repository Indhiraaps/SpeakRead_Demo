<?php
session_start();
$host = 'localhost'; $db = 'speakread_db'; $user = 'root'; $pass = '12345678';
$grade = $_GET['grade'];
$lesson = $_GET['lesson'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmtS = $pdo->prepare("SELECT SID, Name FROM Students WHERE Grade = ?");
    $stmtS->execute([$grade]);
    $students = $stmtS->fetchAll(PDO::FETCH_ASSOC);

    $stmtP = $pdo->prepare("SELECT LID, ParaNumber, Para FROM Lessons WHERE LessonName = ? AND Grade = ?");
    $stmtP->execute([$lesson, $grade]);
    $paras = $stmtP->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $lesson; ?> - SpeakRead</title>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; margin: 0; display: flex; }
        .sidebar { width: 260px; height: 100vh; position: fixed; left: 0; top: 0; background: white; border-right: 1px solid #e2e8f0; padding: 24px; box-sizing: border-box; }
        .content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); box-sizing: border-box; }
        .logo { color: #2563eb; font-weight: 800; font-size: 24px; text-decoration: none; display: block; margin-bottom: 40px; }
        .main-card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; margin-bottom: 24px; }
        .para-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 16px; background: white; transition: 0.2s; cursor: pointer; }
        .para-card:hover { border-color: #2563eb; }
        .para-text { color: #475569; font-size: 16px; line-height: 1.6; }
        .assign-section { display: none; margin-top: 20px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
        select { padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; flex: 1; }
        .btn-start { background: #2563eb; color: white; border: none; padding: 10px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="#" class="logo">SpeakRead</a>
        <button onclick="window.history.back()" style="background:none; border:1px solid #ddd; padding:8px 15px; border-radius:6px; cursor:pointer;">← Back</button>
    </div>

    <div class="content">
        <h1 style="font-size: 32px; font-weight: 800;"><?php echo htmlspecialchars($lesson); ?></h1>
        <div class="main-card">
            <?php foreach($paras as $p): ?>
                <div class="para-card" onclick="toggleAssign(<?php echo $p['LID']; ?>)">
                    <div style="font-weight: 800; color: #2563eb; font-size: 13px; margin-bottom: 10px;">PARAGRAPH <?php echo $p['ParaNumber']; ?></div>
                    <div class="para-text"><?php echo htmlspecialchars($p['Para']); ?></div>
                    
                    <div id="assign-<?php echo $p['LID']; ?>" class="assign-section" onclick="event.stopPropagation()">
                        <div style="display:flex; gap:12px; align-items:center;">
                            <select id="student-<?php echo $p['LID']; ?>">
                                <?php foreach($students as $s) echo "<option value='{$s['SID']}'>{$s['Name']}</option>"; ?>
                            </select>
                            <button class="btn-start" onclick="goToPractice(<?php echo $p['LID']; ?>)">Start Session</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function toggleAssign(lid) {
            document.querySelectorAll('.assign-section').forEach(el => el.style.display = 'none');
            document.getElementById('assign-' + lid).style.display = 'block';
        }

        function goToPractice(lid) {
            const sid = document.getElementById('student-' + lid).value;
            window.location.href = `reading_practice.php?lid=${lid}&sid=${sid}`;
        }
    </script>
</body>
</html>