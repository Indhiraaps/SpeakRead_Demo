<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../frontend/login.html");
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch words from BOTH columns separately
try {
    $stmt = $pdo->prepare("SELECT homework_mistakes, reading_practice_mistakes FROM Warmup WHERE SID = ?");
    $stmt->execute([$student_id]);
    $warmupData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $readingWords = [];
    $homeworkWords = [];
    
    if ($warmupData) {
        // Parse reading practice mistakes
        if (!empty($warmupData['reading_practice_mistakes'])) {
            $readingWords = array_unique(array_filter(array_map('trim', explode(',', $warmupData['reading_practice_mistakes']))));
        }
        
        // Parse homework mistakes
        if (!empty($warmupData['homework_mistakes'])) {
            $homeworkWords = array_unique(array_filter(array_map('trim', explode(',', $warmupData['homework_mistakes']))));
        }
    }
    
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            max-width: 900px; 
            width: 95%; 
            box-shadow: 0 30px 90px rgba(0,0,0,0.3);
            text-align: center;
        }
        
        /* Main heading */
        h1 { 
            font-size: 32px; 
            background: linear-gradient(135deg, #f87171, #fb923c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
            font-weight: 900;
        }
        
        /* Practice Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .practice-card { 
            background: white;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            border: 3px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .practice-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        .practice-card.homework-card::before {
            background: linear-gradient(135deg, #10b981, #14b8a6);
        }
        
        .practice-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(102, 126, 234, 0.25);
            border-color: #667eea;
        }
        
        .practice-card.homework-card:hover {
            border-color: #10b981;
        }
        
        .card-icon {
            font-size: 56px;
            margin-bottom: 15px;
        }
        
        .card-title {
            font-size: 22px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .card-description {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 15px;
        }
        
        .word-count-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .homework-card .word-count-badge {
            background: linear-gradient(135deg, #10b981, #14b8a6);
        }
        
        .start-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
            width: 100%;
        }
        
        .start-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .homework-card .start-btn {
            background: linear-gradient(135deg, #10b981, #14b8a6);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }
        
        .homework-card .start-btn:hover {
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
        }
        
        /* Combined Practice Button */
        .combined-practice {
            background: linear-gradient(135deg, #f59e0b, #f97316);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 16px;
            font-size: 18px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
            margin-bottom: 20px;
            width: 100%;
            max-width: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .combined-practice:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(245, 158, 11, 0.6);
        }
        
        .combined-badge {
            background: rgba(255, 255, 255, 0.3);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        /* Back Button */
        .back-btn { 
            background: linear-gradient(135deg, #6b7280, #4b5563); 
            color: white; 
            text-decoration: none;
            display: inline-block;
            padding: 14px 40px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
        }
        
        /* Practice Mode */
        .practice-mode { display: none; }
        .warmup-icon {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, #f87171, #fb923c);
            border-radius: 18px; margin: 0 auto 25px; font-size: 36px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 20px rgba(248, 113, 113, 0.4);
        }
        .practice-word { 
            font-size: 80px; 
            font-weight: 900; 
            margin: 30px 0;
            background: linear-gradient(135deg, #f87171, #fb923c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .practice-status { 
            font-size: 20px; 
            margin: 20px 0; 
            padding: 20px; 
            background: rgba(248, 113, 113, 0.15);
            border-radius: 12px;
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #1e293b;
        }
        .btn-speak { 
            background: linear-gradient(135deg, #3b82f6, #1d4ed8); 
            color: white; 
            border: none;
            padding: 16px 50px;
            border-radius: 14px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-speak:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-speak:not(:disabled):hover { transform: translateY(-2px); }
        
        /* Mic Indicator */
        .mic-indicator {
            position: fixed; top: 30px; right: 30px;
            width: 50px; height: 50px; background: #ef4444;
            border-radius: 50%; display: none;
            align-items: center; justify-content: center;
            color: white; font-size: 24px; z-index: 1000;
            animation: pulse 1.5s infinite;
        }
        .mic-indicator.active { display: flex; }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        /* Results */
        .results-section { display: none; }
        .result-icon { font-size: 80px; margin: 20px 0; }
        .stats { background: #f1f5f9; padding: 25px; border-radius: 12px; margin: 25px 0; }
        .stat-item { 
            font-size: 20px; margin: 12px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-number { font-weight: 800; color: #f87171; font-size: 28px; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .cards-grid { grid-template-columns: 1fr; }
            .practice-word { font-size: 56px; }
            .combined-practice { font-size: 16px; padding: 14px 28px; }
        }
    </style>
</head>
<body>
    <!-- Mic Indicator -->
    <div class="mic-indicator" id="micIndicator">🎤</div>
    
    <div class="main-card">
        <!-- SELECTION SCREEN -->
        <div id="selectionScreen">
            <h1>🔥 Warmup Practice</h1>
            
            <!-- Individual Practice Cards -->
            <div class="cards-grid">
                <?php if (!empty($readingWords)): ?>
                <div class="practice-card">
                    <div class="card-icon">📖</div>
                    <h2 class="card-title">Classroom Practice</h2>
                    <p class="card-description">Master words from reading sessions</p>
                    <div class="word-count-badge">
                        <?= count($readingWords) ?> word<?= count($readingWords) > 1 ? 's' : '' ?>
                    </div>
                    <button class="start-btn" onclick="startPractice('reading')">
                        🎯 Start Practice
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($homeworkWords)): ?>
                <div class="practice-card homework-card">
                    <div class="card-icon">📚</div>
                    <h2 class="card-title">Homework Practice</h2>
                    <p class="card-description">Master words from homework</p>
                    <div class="word-count-badge">
                        <?= count($homeworkWords) ?> word<?= count($homeworkWords) > 1 ? 's' : '' ?>
                    </div>
                    <button class="start-btn" onclick="startPractice('homework')">
                        🎯 Start Practice
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Combined Practice (Only show if BOTH types exist) -->
            <?php if (!empty($readingWords) && !empty($homeworkWords)): ?>
            <center><button class="combined-practice" onclick="startPractice('both')">
                🔥 Practice All Words
                <span class="combined-badge">
                    <?= count($readingWords) + count($homeworkWords) ?> total
                </span>
            </button></center>
            <?php endif; ?>
            
            <a href="student_dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <!-- PRACTICE MODE -->
        <div id="practiceMode" class="practice-mode">
            <div class="warmup-icon">🔥</div>
            <h1 id="practiceTitle">🔥 Practice Session</h1>
            <p style="font-size: 16px; color: #64748b; margin-bottom: 20px;" id="practiceProgress">Word 1 of 0</p>
            <div class="practice-word" id="currentWord">GET READY</div>
            <p class="practice-status" id="practiceStatus">Listen and repeat...</p>
            <button id="speakBtn" class="btn-speak" onclick="startListening()">🎤 SPEAK</button>
        </div>

        <!-- RESULTS SCREEN -->
        <div id="resultsSection" class="results-section">
            <div class="result-icon">🎊</div>
            <h1>Practice Complete!</h1>
            <div class="stats">
                <div class="stat-item">
                    <span>Words Practiced:</span>
                    <span class="stat-number" id="totalWords">0</span>
                </div>
                <div class="stat-item">
                    <span>Mastered:</span>
                    <span class="stat-number" id="masteredWords" style="color: #10b981;">0</span>
                </div>
                <div class="stat-item">
                    <span>Still Practicing:</span>
                    <span class="stat-number" id="remainingWords" style="color: #ef4444;">0</span>
                </div>
            </div>
            <p id="finalMessage" style="font-size: 18px; color: #64748b; margin: 20px 0;"></p>
            <a href="student_dashboard.php" class="btn-speak" style="text-decoration: none; display: inline-block;">← Back to Dashboard</a>
        </div>
    </div>

    <script>
    const sid = <?= $student_id ?>;
    const readingWords = <?= json_encode(array_values($readingWords)) ?>;
    const homeworkWords = <?= json_encode(array_values($homeworkWords)) ?>;
    
    let currentType = '';
    let practiceWords = [];
    let currentIndex = 0;
    let currentAttempt = 0;
    let masteredWords = [];
    let warmupRecognition = null;
    let warmupCorrectCount = 0;
    const synth = window.speechSynthesis;

    function startPractice(type) {
        currentType = type;
        currentIndex = 0;
        masteredWords = [];
        warmupCorrectCount = 0;
        
        // Set practice words based on type
        if (type === 'reading') {
            practiceWords = [...readingWords];
        } else if (type === 'homework') {
            practiceWords = [...homeworkWords];
        } else if (type === 'both') {
            practiceWords = [...readingWords, ...homeworkWords];
        }
        
        document.getElementById('selectionScreen').style.display = 'none';
        document.getElementById('practiceMode').style.display = 'block';
        
        // Set title based on type
        let title = '🔥 Practice Session';
        let message = "Let's practice your words.";
        
        if (type === 'reading') {
            title = '📖 Classroom Practice';
            message = "Let's practice your classroom words.";
        } else if (type === 'homework') {
            title = '📚 Homework Practice';
            message = "Let's practice your homework words.";
        } else if (type === 'both') {
            title = '🔥 Complete Practice';
            message = "Let's practice all your words.";
        }
        
        document.getElementById('practiceTitle').innerText = title;
        
        const intro = new SpeechSynthesisUtterance(message);
        intro.lang = 'en-IN';
        intro.onend = () => setTimeout(practiceWord, 500);
        synth.speak(intro);
    }

    function practiceWord() {
        if (currentIndex >= practiceWords.length) {
            showComplete();
            return;
        }

        const word = practiceWords[currentIndex];
        currentAttempt = 0;

        document.getElementById('currentWord').innerText = word.toUpperCase();
        document.getElementById('practiceProgress').innerText = `Word ${currentIndex + 1} of ${practiceWords.length}`;
        document.getElementById('practiceStatus').innerText = 'Listen to the word...';
        document.getElementById('speakBtn').disabled = true;

        setTimeout(() => {
            const msg = new SpeechSynthesisUtterance(word);
            msg.lang = 'en-IN';
            msg.rate = 0.75;
            msg.onend = () => {
                setTimeout(() => {
                    document.getElementById('practiceStatus').innerText = 'Click SPEAK and repeat the word';
                    document.getElementById('speakBtn').disabled = false;
                }, 500);
            };
            synth.speak(msg);
        }, 500);
    }

    function startListening() {
        document.getElementById('speakBtn').disabled = true;
        document.getElementById('practiceStatus').innerText = '🎤 Listening...';
        document.getElementById('micIndicator').classList.add('active');

        window.SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        warmupRecognition = new SpeechRecognition();
        warmupRecognition.continuous = false;
        warmupRecognition.interimResults = false;
        warmupRecognition.lang = 'en-IN';

        let heard = false;

        warmupRecognition.onresult = (event) => {
            if (heard) return;
            heard = true;
            warmupRecognition.stop();
            document.getElementById('micIndicator').classList.remove('active');
            
            const spoken = event.results[0][0].transcript.trim().toLowerCase().replace(/[^\w]/g, '');
            const target = practiceWords[currentIndex].toLowerCase();
            checkPronunciation(spoken, target);
        };

        warmupRecognition.onerror = (event) => {
            document.getElementById('micIndicator').classList.remove('active');
            if (!heard) handleNoSpeech();
        };

        warmupRecognition.start();
    }

    function handleNoSpeech() {
        if (currentAttempt === 0) {
            currentAttempt = 1;
            document.getElementById('practiceStatus').innerText = "I couldn't hear you. Try again.";
            
            const retry = new SpeechSynthesisUtterance("I couldn't hear you. Try again.");
            retry.lang = 'en-IN';
            retry.onend = () => {
                setTimeout(() => {
                    document.getElementById('speakBtn').disabled = false;
                }, 800);
            };
            synth.speak(retry);
        } else {
            moveToNextWord();
        }
    }

    function checkPronunciation(spoken, target) {
        const similarity = getSimilarityScore(spoken, target);
        const isCorrect = similarity >= 0.65;
        
        if (isCorrect) {
            warmupCorrectCount++;
            masteredWords.push(target);
            document.getElementById('practiceStatus').innerText = '✅ Great job!';
            document.getElementById('currentWord').style.color = '#10b981';
            
            synth.cancel();
            const praise = new SpeechSynthesisUtterance('Great job!');
            praise.lang = 'en-IN';
            praise.rate = 1.2;
            praise.onend = () => {
                setTimeout(() => {
                    document.getElementById('currentWord').style.color = '';
                    currentIndex++;
                    practiceWord();
                }, 600);
            };
            synth.speak(praise);
        } else {
            if (currentAttempt === 0) {
                currentAttempt = 1;
                document.getElementById('practiceStatus').innerText = 'Try again!';
                document.getElementById('currentWord').style.color = '#ef4444';
                
                synth.cancel();
                const encourage = new SpeechSynthesisUtterance('Try again!');
                encourage.lang = 'en-IN';
                encourage.rate = 1.2;
                encourage.onend = () => {
                    setTimeout(() => {
                        document.getElementById('currentWord').style.color = '';
                        document.getElementById('practiceStatus').innerText = 'Click SPEAK and try again';
                        document.getElementById('speakBtn').disabled = false;
                    }, 400);
                };
                synth.speak(encourage);
            } else {
                document.getElementById('practiceStatus').innerText = "Keep practicing!";
                document.getElementById('currentWord').style.color = '#fb923c';
                
                synth.cancel();
                const encourage = new SpeechSynthesisUtterance("Keep practicing!");
                encourage.lang = 'en-IN';
                encourage.rate = 1.2;
                encourage.onend = () => {
                    setTimeout(() => {
                        document.getElementById('currentWord').style.color = '';
                        moveToNextWord();
                    }, 600);
                };
                synth.speak(encourage);
            }
        }
    }

    function moveToNextWord() {
        currentIndex++;
        if (currentIndex < practiceWords.length) {
            practiceWord();
        } else {
            setTimeout(() => showComplete(), 800);
        }
    }

    function showComplete() {
        synth.cancel();
        
        document.getElementById('practiceMode').style.display = 'none';
        document.getElementById('resultsSection').style.display = 'block';

        const total = practiceWords.length;
        const remaining = total - warmupCorrectCount;

        document.getElementById('totalWords').innerText = total;
        document.getElementById('masteredWords').innerText = warmupCorrectCount;
        document.getElementById('remainingWords').innerText = remaining;

        let message = remaining === 0 
            ? '🎉 Amazing! You mastered all the words!' 
            : `Good job! Keep practicing the remaining ${remaining} word${remaining > 1 ? 's' : ''}.`;
        
        document.getElementById('finalMessage').innerText = message;

        const finalMsg = new SpeechSynthesisUtterance(
            remaining === 0 ? 'Excellent work!' : 'Good job! Keep practicing.'
        );
        finalMsg.lang = 'en-IN';
        synth.speak(finalMsg);

        saveMasteredWords();
    }

    function saveMasteredWords() {
        if (masteredWords.length === 0) return;
        
        // Separate mastered words by type
        const readingMastered = masteredWords.filter(w => readingWords.includes(w));
        const homeworkMastered = masteredWords.filter(w => homeworkWords.includes(w));
        
        // Save reading words
        if (readingMastered.length > 0) {
            fetch('update_warmup.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    sid: sid,
                    word_type: 'reading',
                    mastered_words: readingMastered
                })
            })
            .then(response => response.json())
            .then(data => console.log('✅ Reading words saved:', data))
            .catch(err => console.error('❌ Reading save error:', err));
        }
        
        // Save homework words
        if (homeworkMastered.length > 0) {
            fetch('update_warmup.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    sid: sid,
                    word_type: 'homework',
                    mastered_words: homeworkMastered
                })
            })
            .then(response => response.json())
            .then(data => console.log('✅ Homework words saved:', data))
            .catch(err => console.error('❌ Homework save error:', err));
        }
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
                tmp[i][j] = Math.min(
                    tmp[i-1][j] + 1,
                    tmp[i][j-1] + 1,
                    tmp[i-1][j-1] + (a[i-1] === b[j-1] ? 0 : 1)
                );
            }
        }
        return tmp[a.length][b.length];
    }
    </script>
</body>
</html>