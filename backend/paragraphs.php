<?php
session_start();
$host = 'localhost'; $db = 'speakread_db'; $user = 'root'; $pass = 'skdn1418';
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
        .btn-start { background: #2563eb; color: white; border: none; padding: 10px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; }

        /* Reading Overlay */
        #overlay { display: none; position: fixed; inset: 0; background: white; z-index: 1000; padding: 60px; text-align: center; }
        #readingArea { font-size: 48px; line-height: 1.5; margin-top: 80px; color: #0f172a; max-width: 1000px; margin-inline: auto; }
        .word-span { transition: color 0.3s ease; display: inline-block; margin-right: 12px; border-bottom: 2px solid transparent; }
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
                    <div class="para-text" id="t-<?php echo $p['LID']; ?>"><?php echo htmlspecialchars($p['Para']); ?></div>
                    
                    <div id="assign-<?php echo $p['LID']; ?>" class="assign-section" onclick="event.stopPropagation()">
                        <div style="display:flex; gap:12px; align-items:center;">
                            <select id="student-<?php echo $p['LID']; ?>">
                                <?php foreach($students as $s) echo "<option value='{$s['SID']}'>{$s['Name']}</option>"; ?>
                            </select>
                            <button class="btn-start" onclick="startReading(<?php echo $p['LID']; ?>)">Start Session</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="overlay">
        <button onclick="stopSession()" style="position:absolute; top:30px; right:40px; padding:10px 20px; cursor:pointer;">Exit Session</button>
        <div id="readingArea"></div>
        <div id="finalFeedback" style="margin-top:50px; display:none;">
            <h2 id="feedbackMsg" style="color:#2563eb;"></h2>
            <button class="btn-start" onclick="stopSession()">Done</button>
        </div>
    </div>

    <script>
        let recognition;
        let currentLID, currentSID, wordsArray;
        let wordIndex = 0;

        function toggleAssign(lid) {
            document.querySelectorAll('.assign-section').forEach(el => el.style.display = 'none');
            const el = document.getElementById('assign-' + lid);
            el.style.display = 'block';
        }

        function startReading(lid) {
            currentLID = lid;
            currentSID = document.getElementById('student-' + lid).value;
            const text = document.getElementById('t-' + lid).innerText;
            wordsArray = text.split(/\s+/);
            wordIndex = 0;

            const display = document.getElementById('readingArea');
            display.innerHTML = wordsArray.map((w, i) => `<span id="w-${i}" class="word-span">${w}</span>`).join('');
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('finalFeedback').style.display = 'none';

            initSpeech();
        }

        function initSpeech() {
            window.SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.lang = 'en-IN';

            recognition.onresult = (event) => {
                const result = event.results[event.results.length - 1];
                if (result.isFinal) {
                    const spoken = result[0].transcript.trim().toLowerCase();
                    checkWord(spoken);
                }
            };
            recognition.start();
        }

        function checkWord(spoken) {
            if (wordIndex >= wordsArray.length) return;

            const target = wordsArray[wordIndex].toLowerCase().replace(/[^\w]/g, '');
            const spokenClean = spoken.replace(/[^\w]/g, '');
            const element = document.getElementById(`w-${wordIndex}`);

            if (spokenClean.includes(target)) {
                element.style.color = "#22c55e"; // Green
                wordIndex++;
            } else if (spokenClean !== "") {
                element.style.color = "#ef4444"; // Red
                saveToWarmup(wordsArray[wordIndex]);
                wordIndex++;
            }

            // Check if finished
            if (wordIndex === wordsArray.length) {
                finishSession();
            }
        }

        function finishSession() {
            recognition.stop();
            const msg = "Excellent reading! You finished the paragraph.";
            const synth = window.speechSynthesis;
            const utter = new SpeechSynthesisUtterance(msg);
            utter.pitch = 1.5; 
            utter.rate = 0.9;
            synth.speak(utter);

            document.getElementById('feedbackMsg').innerText = msg;
            document.getElementById('finalFeedback').style.display = 'block';
        }

        function saveToWarmup(word) {
            const data = new FormData();
            data.append('sid', currentSID);
            data.append('word', word);
            data.append('lid', currentLID);
            fetch('save_warmup.php', { method: 'POST', body: data });
        }

        function stopSession() {
            if (recognition) recognition.stop();
            document.getElementById('overlay').style.display = 'none';
        }
    </script>
</body>
</html>