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
            max-width: 800px; 
            width: 90%; 
            box-shadow: 0 30px 90px rgba(0,0,0,0.3);
            text-align: center;
        }
    /* Main heading */
h1 { 
    font-size: 28px; 
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 10px;
    font-weight: 800;
}

/* Practice Cards */
.practice-card { 
    background: white;
    padding: 40px;
    border-radius: 24px;
    margin: 20px 0;
    text-align: center;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.15);
    border: 2px solid #e2e8f0;
    transition: all 0.3s ease;
}

.practice-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 60px rgba(102, 126, 234, 0.25);
    border-color: #667eea;
}

.homework-card {
    border-color: #fbbf24;
}

.homework-card:hover {
    border-color: #f59e0b;
}

.card-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.card-title {
    font-size: 24px;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 8px;
}

.card-description {
    font-size: 16px;
    color: #64748b;
    margin-bottom: 20px;
}

.word-count-badge {
    display: inline-block;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 12px 24px;
    border-radius: 50px;
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 25px;
}

.homework-card .word-count-badge {
    background: linear-gradient(135deg, #f59e0b, #f97316);
}

.start-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 16px 40px;
    border-radius: 16px;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.start-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
}

.homework-card .start-btn {
    background: linear-gradient(135deg, #10b981, #14b8a6);
    box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);
}

.homework-card .start-btn:hover {
    box-shadow: 0 12px 30px rgba(245, 158, 11, 0.4);
}    .btn { 
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
        .warmup-icon {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, #f87171, #fb923c);
            border-radius: 18px; margin: 0 auto 25px; font-size: 36px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 20px rgba(248, 113, 113, 0.4);
        }
        .practice-word { 
            font-size: 72px; 
            font-weight: 900; 
            margin: 30px 0;
            background: linear-gradient(135deg, #f87171, #fb923c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: color 0.3s;
        }
        .practice-status { 
            font-size: 20px; 
            margin: 20px 0; 
            padding: 15px; 
            background: #fef3c7; 
            border-radius: 12px;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-speak { 
            background: linear-gradient(135deg, #3b82f6, #1d4ed8); 
            color: white; 
        }
        .btn-speak:disabled { opacity: 0.5; cursor: not-allowed; }
        
        /* Mic Indicator */
        .mic-indicator {
            position: fixed;
            top: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: #ef4444;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            z-index: 1000;
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
        .stats { background: #f1f5f9; padding: 20px; border-radius: 12px; margin: 20px 0; }
        .stat-item { font-size: 18px; margin: 10px 0; }
        .stat-number { font-weight: 800; color: #f87171; font-size: 24px; }
        
        @media (max-width: 768px) {
            .practice-word { font-size: 48px; }
            .btn { padding: 12px 30px; font-size: 15px; }
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
            
            <?php if (!empty($readingWords)): ?>
<div class="practice-card">
    <div class="card-icon">📖</div>
    <h2 class="card-title">Classroom Practice</h2>
    <p class="card-description">Master tricky words</p>
    <div class="word-count-badge">
        <?= count($readingWords) ?> word<?= count($readingWords) > 1 ? 's' : '' ?> to practice!
    </div>
    <button class="start-btn" onclick="startPractice('reading')">
        🎯 Start Practice →
    </button>
</div>
<?php endif; ?>

<?php if (!empty($homeworkWords)): ?>
<div class="practice-card homework-card">
    <div class="card-icon">📚</div>
    <h2 class="card-title">Homework Practice</h2>
    <p class="card-description">Master tricky words</p>
    <div class="word-count-badge">
        <?= count($homeworkWords) ?> word<?= count($homeworkWords) > 1 ? 's' : '' ?> to practice!
    </div>
    <button class="start-btn" onclick="startPractice('homework')">
        🎯 Start Practice →
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
                    <?php foreach (array_slice($homeworkWords, 0, 10) as $word): ?>
                        <span class="word-tag"><?= htmlspecialchars($word) ?></span>
                    <?php endforeach; ?>
                    <?php if (count($homeworkWords) > 10): ?>
                        <span class="word-tag">+<?= count($homeworkWords) - 10 ?> more</span>
                    <?php endif; ?>
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
            <div class="warmup-icon">🔥</div>
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
    const readingWords = <?= json_encode(array_values($readingWords)) ?>;
    const homeworkWords = <?= json_encode(array_values($homeworkWords)) ?>;
    
    let currentType = '';
    let practiceWords = [];
    let currentIndex = 0;
    let currentAttempt = 0;
    let masteredWords = [];
    let warmupRecognition = null;
    let warmupCorrectCount = 0;
    let wordsToSave = [];
    const synth = window.speechSynthesis;

    function startPractice(type) {
        currentType = type;
        practiceWords = type === 'reading' ? [...readingWords] : [...homeworkWords];
        currentIndex = 0;
        masteredWords = [];
        warmupCorrectCount = 0;
        wordsToSave = [];
        
        document.getElementById('selectionScreen').style.display = 'none';
        document.getElementById('practiceMode').style.display = 'block';
        
        const title = type === 'reading' ? '📖 Classroom Practice' : '📚 Homework Practice';
        document.getElementById('practiceTitle').innerText = title;
        
        const intro = new SpeechSynthesisUtterance(
            `Let's practice your ${type === 'reading' ? 'classroom' : 'homework'} words.`
        );
        intro.lang = 'en-IN';
        intro.onend = () => setTimeout(practiceWord, 500);
        synth.speak(intro);
    }

    // ========================================
    // 🎯 PRACTICE WORD - EXACT COPY FROM HOMEWORK
    // ========================================
    function practiceWord() {
        if (currentIndex >= practiceWords.length) {
            showComplete();
            return;
        }

        const word = practiceWords[currentIndex];
        currentAttempt = 0; // Reset to 0 for new word

        document.getElementById('currentWord').innerText = word.toUpperCase();
        document.getElementById('practiceProgress').innerText = `Word ${currentIndex + 1} of ${practiceWords.length}`;
        document.getElementById('practiceStatus').innerText = 'Listen to the word...';
        document.getElementById('speakBtn').disabled = true;

        // Speak the word first
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

    // ========================================
    // 🎤 START LISTENING - EXACT COPY FROM HOMEWORK
    // ========================================
    function startListening() {
    document.getElementById('speakBtn').disabled = true;
    document.getElementById('practiceStatus').innerText = '🎤 Listening...';
    document.getElementById('micIndicator').classList.add('active');

    window.SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    warmupRecognition = new SpeechRecognition();
    warmupRecognition.continuous = true;
    warmupRecognition.interimResults = false;
    warmupRecognition.lang = 'en-IN';

    let heard = false; // ✅ ADD THIS

    warmupRecognition.onresult = (event) => {
        if (heard) return; // ✅ ADD THIS - Prevent double processing
        heard = true; // ✅ ADD THIS
        
        warmupRecognition.stop(); // ✅ ADD THIS - Stop immediately
        document.getElementById('micIndicator').classList.remove('active'); // ✅ ADD THIS - Turn off mic
        
        const spoken = event.results[0][0].transcript.trim().toLowerCase().replace(/[^\w]/g, '');
        const target = practiceWords[currentIndex].toLowerCase();
        
        console.log('🎤 Heard:', spoken, '| Target:', target); // ✅ ADD THIS - Debug log
        
        checkPronunciation(spoken, target);
    };

    warmupRecognition.onerror = (event) => {
        console.error('Recognition error:', event.error);
        document.getElementById('micIndicator').classList.remove('active'); // ✅ ADD THIS
        if (!heard) { // ✅ ADD THIS
            handleNoSpeech();
        }
    };

    warmupRecognition.start();
}
    function handleNoSpeech() {
    document.getElementById('micIndicator').classList.remove('active');
    
    // FIRST ATTEMPT (currentAttempt = 0)
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
    } 
    // SECOND ATTEMPT - MOVE TO NEXT WORD
    else {
        wordsToSave.push(practiceWords[currentIndex]);
        moveToNextWord();
    }
}

    // ========================================
    // ✅ CHECK PRONUNCIATION - EXACT COPY FROM HOMEWORK
    // ========================================
    function checkPronunciation(spoken, target) {
        document.getElementById('micIndicator').classList.remove('active');
        warmupRecognition.stop();
        
        const similarity = getSimilarityScore(spoken, target);
        const isCorrect = similarity >= 0.55;
        
        // ✅ CORRECT - Move to next word
        if (isCorrect) {
    warmupCorrectCount++;
    masteredWords.push(target);
    document.getElementById('practiceStatus').innerText = '✅ Great job!';
    document.getElementById('currentWord').style.color = '#10b981';
    
    synth.cancel(); // ✅ Clear any pending speech
    const praise = new SpeechSynthesisUtterance('Great job!');
    praise.lang = 'en-IN';
    praise.rate = 1.2; // ✅ Speak faster
    praise.onend = () => {
        setTimeout(() => {
            document.getElementById('currentWord').style.color = '';
            currentIndex++;
            practiceWord();
        }, 600); // ✅ Reduced from 1200ms to 600ms
    };
    synth.speak(praise);
}
        // ❌ WRONG
        else {
            // FIRST ATTEMPT (currentAttempt = 0)
            if (currentAttempt === 0) {
    currentAttempt = 1;
    document.getElementById('practiceStatus').innerText = 'Try again!';
    document.getElementById('currentWord').style.color = '#ef4444';
    
    synth.cancel(); // ✅ Clear speech queue
    const encourage = new SpeechSynthesisUtterance('Try again!');
    encourage.lang = 'en-IN';
    encourage.rate = 1.2; // ✅ Faster speech
    encourage.onend = () => {
        setTimeout(() => {
            document.getElementById('currentWord').style.color = '';
            document.getElementById('practiceStatus').innerText = 'Click SPEAK and try again';
            document.getElementById('speakBtn').disabled = false;
        }, 400); // ✅ Reduced from 800ms to 400ms
    };
    synth.speak(encourage);
}

            
            // SECOND ATTEMPT - MOVE TO NEXT WORD
      else {
    wordsToSave.push(target);
    
    document.getElementById('practiceStatus').innerText = "Keep practicing!";
    document.getElementById('currentWord').style.color = '#fb923c';
    
    synth.cancel(); // ✅ Clear speech queue
    const encourage = new SpeechSynthesisUtterance("Keep practicing!");
    encourage.lang = 'en-IN';
    encourage.rate = 1.2; // ✅ Faster speech
    encourage.onend = () => {
        setTimeout(() => {
            document.getElementById('currentWord').style.color = '';
            moveToNextWord();
        }, 600); // ✅ Reduced from 1200ms to 600ms
    };
    synth.speak(encourage);
}
        }
    }

    // ========================================
    // ➡️ MOVE TO NEXT WORD - EXACT COPY FROM HOMEWORK
    // ========================================
    function moveToNextWord() {
        const isLastWord = (currentIndex === practiceWords.length - 1);
        
        if (isLastWord) {
            // 🏁 LAST WORD - Go directly to results
            setTimeout(() => showComplete(), 1000);
        } 
        else {
            // Continue to next word
            document.getElementById('practiceStatus').innerText = "Let's try the next word.";
            
            const next = new SpeechSynthesisUtterance("Let's try the next word.");
            next.lang = 'en-IN';
            next.onend = () => {
                setTimeout(() => {
                    currentIndex++;
                    practiceWord();
                }, 1200);
            };
            synth.speak(next);
        }
    }

    // ========================================
    // 🎊 SHOW COMPLETE - SAVE TO DATABASE
    // ========================================
    function showComplete() {
        synth.cancel(); // Stop all speech
        
        document.getElementById('practiceMode').style.display = 'none';
        document.getElementById('resultsSection').style.display = 'block';

        const total = practiceWords.length;
        const needMore = wordsToSave.length;

        document.getElementById('totalWords').innerText = total;
        document.getElementById('masteredWords').innerText = warmupCorrectCount;
        document.getElementById('remainingWords').innerText = needMore;

        let message = needMore === 0 
            ? '🎉 Amazing! You mastered all the words!' 
            : `Good job! Keep practicing the remaining ${needMore} word${needMore > 1 ? 's' : ''}.`;
        
        document.getElementById('finalMessage').innerText = message;

        const finalMsg = new SpeechSynthesisUtterance(
            needMore === 0 ? 'Excellent work!' : 'Good job! Keep practicing.'
        );
        finalMsg.lang = 'en-IN';
        synth.speak(finalMsg);

        // Save to database
        saveMasteredWords();
    }

    // ========================================
    // 💾 SAVE MASTERED WORDS TO DATABASE
    // ========================================
    function saveMasteredWords() {
        console.log('=== SAVING TO DATABASE ===');
        console.log('Student ID:', sid);
        console.log('Word Type:', currentType);
        console.log('Mastered Words:', masteredWords);
        
        if (masteredWords.length > 0) {
            fetch('update_warmup.php', {
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
                if (data.success) {
                    console.log('✅ Database updated:', data);
                } else {
                    console.error('❌ Save failed:', data.message);
                }
            })
            .catch(err => {
                console.error('❌ Fetch error:', err);
            });
        } else {
            console.log('ℹ️ No mastered words to save');
        }
    }

    // ========================================
    // 📊 VALIDATION FUNCTIONS - EXACT COPY FROM HOMEWORK
    // ========================================
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