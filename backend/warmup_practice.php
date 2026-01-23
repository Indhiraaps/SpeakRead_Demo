<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../frontend/login.html");
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch BOTH types of words separately with NO DUPLICATES
try {
    // Reading practice mistakes (from classroom)
    $readingStmt = $pdo->prepare("
        SELECT DISTINCT IncorrectWord 
        FROM Warmup 
        WHERE SID = ? AND WordType = 'reading_practice'
        ORDER BY IncorrectWord
    ");
    $readingStmt->execute([$student_id]);
    $readingWords = $readingStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Homework mistakes
    $homeworkStmt = $pdo->prepare("
        SELECT DISTINCT IncorrectWord 
        FROM Warmup 
        WHERE SID = ? AND WordType = 'homework'
        ORDER BY IncorrectWord
    ");
    $homeworkStmt->execute([$student_id]);
    $homeworkWords = $homeworkStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($readingWords) && empty($homeworkWords)) {
        echo "<script>alert('No words to practice!'); window.location.href='student_dashboard.php';</script>";
        exit();
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Warmup Practice | SpeakRead</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #fb7185 0%, #f472b6 50%, #fb923c 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            padding: 20px;
        }
        .main-card { 
            background: rgba(255, 255, 255, 0.98); 
            border-radius: 24px; 
            padding: 40px;
            max-width: 800px; 
            width: 90%; 
            box-shadow: 0 30px 90px rgba(0,0,0,0.3);
            text-align: center;
        }
        h1 { 
            font-size: 32px; 
            background: linear-gradient(135deg, #f87171, #fb923c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
        }
        .category-section { 
            background: #f1f5f9; 
            padding: 25px; 
            border-radius: 16px; 
            margin: 20px 0;
            text-align: left;
        }
        .category-title { 
            font-size: 20px; 
            font-weight: 700; 
            color: #1e293b; 
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .word-list { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 10px; 
            margin-bottom: 15px;
        }
        .word-tag { 
            background: #fef3c7; 
            color: #92400e; 
            padding: 8px 15px; 
            border-radius: 8px; 
            font-size: 14px; 
            font-weight: 600;
        }
        .btn { 
            padding: 14px 45px; 
            border: none; 
            border-radius: 14px; 
            cursor: pointer; 
            font-weight: 700; 
            font-size: 17px; 
            margin: 10px;
            text-transform: uppercase;
            transition: all 0.3s;
        }
        .btn-primary { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .btn-primary:hover { transform: translateY(-3px); }
        .btn-secondary { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .btn-secondary:hover { transform: translateY(-3px); }
        .back-btn { 
            background: linear-gradient(135deg, #6b7280, #4b5563); 
            color: white; 
            text-decoration: none;
            display: inline-block;
        }
        
        /* Practice Mode */
        .practice-mode { display: none; }
        .practice-word { 
            font-size: 72px; 
            font-weight: 900; 
            margin: 30px 0;
            background: linear-gradient(135deg, #f87171, #fb923c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .practice-status { 
            font-size: 20px; 
            margin: 20px 0; 
            padding: 15px; 
            background: #fef3c7; 
            border-radius: 12px;
        }
        .btn-speak { 
            background: linear-gradient(135deg, #3b82f6, #1d4ed8); 
            color: white; 
        }
        .btn-speak:disabled { opacity: 0.5; cursor: not-allowed; }
        
        /* Results */
        .results-section { display: none; }
        .result-icon { font-size: 80px; margin: 20px 0; }
        .stats { background: #f1f5f9; padding: 20px; border-radius: 12px; margin: 20px 0; }
        .stat-item { font-size: 18px; margin: 10px 0; }
        .stat-number { font-weight: 800; color: #f87171; font-size: 24px; }
    </style>
</head>
<body>
    <div class="main-card">
        <!-- SELECTION SCREEN -->
        <div id="selectionScreen">
            <h1>🔥 Warmup Practice</h1>
            
            <?php if (!empty($readingWords)): ?>
            <div class="category-section">
                <div class="category-title">
                    📖 Classroom Practice Words
                    <span style="background: #ef4444; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px;">
                        <?= count($readingWords) ?> word<?= count($readingWords) > 1 ? 's' : '' ?>
                    </span>
                </div>
                <div class="word-list">
                    <?php foreach ($readingWords as $word): ?>
                        <span class="word-tag"><?= htmlspecialchars($word) ?></span>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-primary" onclick="startPractice('reading')">
                    Start Classroom Practice
                </button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($homeworkWords)): ?>
            <div class="category-section">
                <div class="category-title">
                    📚 Homework Practice Words
                    <span style="background: #f59e0b; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px;">
                        <?= count($homeworkWords) ?> word<?= count($homeworkWords) > 1 ? 's' : '' ?>
                    </span>
                </div>
                <div class="word-list">
                    <?php foreach ($homeworkWords as $word): ?>
                        <span class="word-tag"><?= htmlspecialchars($word) ?></span>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-secondary" onclick="startPractice('homework')">
                    Start Homework Practice
                </button>
            </div>
            <?php endif; ?>
            
            <a href="student_dashboard.php" class="btn back-btn">← Back to Dashboard</a>
        </div>

        <!-- PRACTICE MODE -->
        <div id="practiceMode" class="practice-mode">
            <h1 id="practiceTitle">🔥 Practice Session</h1>
            <p class="practice-status" id="practiceProgress">Word 1 of 0</p>
            <div class="practice-word" id="currentWord">GET READY</div>
            <p class="practice-status" id="practiceStatus">Listen and repeat...</p>
            <button id="speakBtn" class="btn btn-speak" onclick="startListening()">🎤 SPEAK</button>
        </div>

        <!-- RESULTS SCREEN -->
        <div id="resultsSection" class="results-section">
            <div class="result-icon">🎊</div>
            <h1>Practice Complete!</h1>
            <div class="stats">
                <div class="stat-item">
                    Words Practiced: <span class="stat-number" id="totalWords">0</span>
                </div>
                <div class="stat-item">
                    Mastered: <span class="stat-number" id="masteredWords" style="color: #10b981;">0</span>
                </div>
                <div class="stat-item">
                    Still Practicing: <span class="stat-number" id="remainingWords" style="color: #ef4444;">0</span>
                </div>
            </div>
            <p id="finalMessage" style="font-size: 18px; color: #64748b; margin: 20px 0;"></p>
            <a href="student_dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
        </div>
    </div>

    <script>
    const sid = <?= $student_id ?>;
    const readingWords = <?= json_encode($readingWords) ?>;
    const homeworkWords = <?= json_encode($homeworkWords) ?>;
    
    let currentType = ''; // 'reading' or 'homework'
    let practiceWords = [];
    let currentIndex = 0;
    let masteredWords = [];
    let recognition, silenceTimer;
    const synth = window.speechSynthesis;

    function startPractice(type) {
        currentType = type;
        practiceWords = type === 'reading' ? [...readingWords] : [...homeworkWords];
        currentIndex = 0;
        masteredWords = [];
        
        document.getElementById('selectionScreen').style.display = 'none';
        document.getElementById('practiceMode').style.display = 'block';
        
        const title = type === 'reading' ? '📖 Classroom Practice' : '📚 Homework Practice';
        document.getElementById('practiceTitle').innerText = title;
        
        const intro = new SpeechSynthesisUtterance(
            `Let's practice your ${type === 'reading' ? 'classroom' : 'homework'} words.`
        );
        intro.lang = 'en-IN';
        intro.onend = () => setTimeout(showWord, 500);
        synth.speak(intro);
    }

    function showWord() {
        if (currentIndex >= practiceWords.length) {
            completePractice();
            return;
        }

        const word = practiceWords[currentIndex];
        document.getElementById('currentWord').innerText = word.toUpperCase();
        document.getElementById('practiceProgress').innerText = `Word ${currentIndex + 1} of ${practiceWords.length}`;
        document.getElementById('practiceStatus').textContent = "👂 Listen carefully...";
        document.getElementById('speakBtn').disabled = true;

        const utter = new SpeechSynthesisUtterance(word);
        utter.lang = 'en-IN';
        utter.rate = 0.75;
        utter.onend = () => {
            setTimeout(() => {
                document.getElementById('practiceStatus').textContent = "🎤 Click SPEAK to repeat";
                document.getElementById('speakBtn').disabled = false;
            }, 500);
        };
        synth.speak(utter);
    }

    function startListening() {
        if (recognition) try { recognition.stop(); } catch(e) {}
        clearTimeout(silenceTimer);

        window.SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();
        recognition.lang = 'en-IN';
        recognition.interimResults = false;

        document.getElementById('practiceStatus').textContent = "🎙️ Listening...";
        document.getElementById('speakBtn').disabled = true;

        silenceTimer = setTimeout(() => {
            if (recognition) recognition.stop();
            document.getElementById('practiceStatus').textContent = "⏳ Didn't hear you. Try again.";
            document.getElementById('speakBtn').disabled = false;
            
            const retry = new SpeechSynthesisUtterance("I didn't hear you. Please try again.");
            retry.lang = 'en-IN';
            synth.speak(retry);
        }, 5000);

        recognition.onresult = (event) => {
            clearTimeout(silenceTimer);
            const spoken = event.results[0][0].transcript.toLowerCase().trim().replace(/[^\w]/g, '');
            handleResult(spoken);
        };

        recognition.onerror = () => {
            clearTimeout(silenceTimer);
            document.getElementById('speakBtn').disabled = false;
        };

        try { recognition.start(); } catch(e) {}
    }

    function handleResult(spoken) {
        const target = practiceWords[currentIndex].toLowerCase();
        const isCorrect = getSimilarityScore(spoken, target) >= 0.45;

        if (isCorrect) {
            // CORRECT - will be DELETED from database
            masteredWords.push(target);
            document.getElementById('practiceStatus').innerHTML = "✅ <strong>Perfect! Word mastered!</strong>";
            
            const praise = new SpeechSynthesisUtterance("Excellent!");
            praise.lang = 'en-IN';
            praise.onend = () => {
                setTimeout(() => {
                    currentIndex++;
                    showWord();
                }, 1000);
            };
            synth.speak(praise);

        } else {
            // WRONG - stays in database
            document.getElementById('practiceStatus').innerHTML = "💙 <strong>Keep practicing this one!</strong>";
            
            const encourage = new SpeechSynthesisUtterance("Keep practicing!");
            encourage.lang = 'en-IN';
            encourage.onend = () => {
                setTimeout(() => {
                    currentIndex++;
                    showWord();
                }, 1200);
            };
            synth.speak(encourage);
        }
    }

    function completePractice() {
        // Update database - DELETE mastered words
        fetch('update_warmup_practice.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                sid: sid,
                word_type: currentType,
                mastered_words: masteredWords
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Database updated:', data);
            showResults();
        })
        .catch(e => {
            console.error('Update error:', e);
            showResults();
        });
    }

    function showResults() {
        document.getElementById('practiceMode').style.display = 'none';
        document.getElementById('resultsSection').style.display = 'block';
        
        const total = practiceWords.length;
        const mastered = masteredWords.length;
        const remaining = total - mastered;
        
        document.getElementById('totalWords').innerText = total;
        document.getElementById('masteredWords').innerText = mastered;
        document.getElementById('remainingWords').innerText = remaining;
        
        let message = mastered === total 
            ? '🎉 Amazing! You mastered all words!' 
            : `Great work! Keep practicing the remaining ${remaining} word${remaining > 1 ? 's' : ''}.`;
        
        document.getElementById('finalMessage').innerText = message;
        
        const finalMsg = new SpeechSynthesisUtterance(message);
        finalMsg.lang = 'en-IN';
        synth.speak(finalMsg);
    }

    function getSimilarityScore(spoken, target) {
        if (spoken === target) return 1.0;
        const dist = levenshtein(spoken, target);
        return 1 - (dist / Math.max(spoken.length, target.length));
    }

    function levenshtein(a, b) {
        const tmp = [];
        for (let i = 0; i <= a.length; i++) tmp[i] = [i];
        for (let j = 0; j <= b.length; j++) tmp[0][j] = j;
        for (let i = 1; i <= a.length; i++) {
            for (let j = 1; j <= b.length; j++) {
                tmp[i][j] = Math.min(tmp[i-1][j]+1, tmp[i][j-1]+1, tmp[i-1][j-1]+(a[i-1]===b[j-1]?0:1));
            }
        }
        return tmp[a.length][b.length];
    }
    </script>
</body>
</html>