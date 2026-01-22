<?php
session_start();
require_once '../config/db.php';

// Check if student is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../frontend/login.html");
    exit();
}

$student_id = $_GET['sid'] ?? $_SESSION['user_id'];

// Fetch wrong words from Warmup table
try {
    $stmt = $pdo->prepare("SELECT DISTINCT IncorrectWord FROM Warmup WHERE SID = ?");
    $stmt->execute([$student_id]);
    $warmupWords = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($warmupWords)) {
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
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #fb7185 0%, #f472b6 50%, #fb923c 100%); 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.2) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.2) 0%, transparent 50%);
            animation: float 15s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .main-card { 
            background: rgba(255, 255, 255, 0.98); 
            backdrop-filter: blur(30px); 
            border-radius: 24px; 
            padding: 40px;
            max-width: 700px; 
            width: 90%; 
            min-height: 500px;
            box-shadow: 0 30px 90px rgba(0,0,0,0.3);
            text-align: center;
            position: relative;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #f87171, #fb923c);
            border-radius: 15px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 10px 25px rgba(248, 113, 113, 0.5);
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        h1 {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #f87171, #fb923c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .progress-text {
            color: #64748b;
            font-size: 16px;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .current-word {
            font-size: 72px;
            font-weight: 900;
            background: linear-gradient(135deg, #f87171, #fb923c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 40px 0;
            min-height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .status-message {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin: 25px 0;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            background: linear-gradient(to right, rgba(248, 113, 113, 0.15), rgba(251, 146, 60, 0.15));
            border-radius: 12px;
        }

        .btn {
            padding: 16px 50px;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-weight: 700;
            font-size: 18px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            margin: 10px;
        }

        .btn-start {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-start:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.5);
        }

        .btn-next {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }

        .btn-next:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(59, 130, 246, 0.5);
        }

        .btn-disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .mic-indicator {
            position: absolute;
            top: 25px;
            right: 25px;
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            animation: micPulse 1.5s ease-in-out infinite;
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.6);
        }

        @keyframes micPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }

        .mic-indicator.active {
            display: flex;
        }

        .back-btn {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            text-decoration: none;
            padding: 14px 40px;
            border-radius: 15px;
            font-weight: 700;
            display: inline-block;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 15px;
        }

        .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(107, 114, 128, 0.5);
        }

        .complete-section {
            display: none;
        }

        .result-icon {
            font-size: 80px;
            margin: 20px 0;
            animation: scaleIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        .stats {
            background: #f1f5f9;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }

        .stat-item {
            font-size: 18px;
            color: #334155;
            margin: 10px 0;
        }

        .stat-number {
            font-weight: 800;
            color: #f87171;
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="main-card">
        <div class="mic-indicator" id="micIndicator">🎤</div>
        
        <!-- Practice Section -->
        <div id="practiceSection">
            <div class="header-icon">🔥</div>
            <h1>Warmup Practice</h1>
            <p class="progress-text" id="progressText">Word 1 of <?= count($warmupWords) ?></p>
            
            <div class="current-word" id="currentWord">GET READY</div>
            
            <div class="status-message" id="statusMessage">
                Click START to begin practice
            </div>
            
            <div>
                <button id="startBtn" class="btn btn-start" onclick="startPractice()">
                    ▶ START PRACTICE
                </button>
                <button id="nextBtn" class="btn btn-next" onclick="nextWord()" style="display: none;">
                    NEXT WORD →
                </button>
            </div>
        </div>

        <!-- Complete Section -->
        <div id="completeSection" class="complete-section">
            <div class="result-icon">🎊</div>
            <h1 style="font-size: 32px; margin: 20px 0;">Practice Complete!</h1>
            
            <div class="stats">
                <div class="stat-item">
                    Words Practiced: <span class="stat-number" id="totalWords">0</span>
                </div>
                <div class="stat-item">
                    Correct: <span class="stat-number" id="correctWords" style="color: #10b981;">0</span>
                </div>
                <div class="stat-item">
                    Need More Practice: <span class="stat-number" id="needPractice" style="color: #ef4444;">0</span>
                </div>
            </div>
            
            <p id="finalMessage" style="font-size: 18px; color: #64748b; margin: 20px 0;"></p>
            
            <a href="student_dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>
    </div>

    <script>
        const warmupWords = <?= json_encode($warmupWords) ?>;
        const sid = <?= $student_id ?>;
        
        let currentIndex = 0;
        let currentAttempt = 0;
        let recognition = null;
        let correctCount = 0;
        let wordsToKeep = [];
        let isListening = false;

        function startPractice() {
            document.getElementById('startBtn').style.display = 'none';
            document.getElementById('nextBtn').style.display = 'none';
            
            // Start with first word
            speakAndShowWord();
        }

        function speakAndShowWord() {
            if (currentIndex >= warmupWords.length) {
                showComplete();
                return;
            }

            const word = warmupWords[currentIndex];
            currentAttempt = 0;

            // Show the word
            document.getElementById('currentWord').innerText = word.toUpperCase();
            document.getElementById('progressText').innerText = `Word ${currentIndex + 1} of ${warmupWords.length}`;
            document.getElementById('statusMessage').innerText = 'Listening...';
            document.getElementById('micIndicator').classList.add('active');

            // First, say the word
            setTimeout(() => {
                const msg = new SpeechSynthesisUtterance(word);
                msg.lang = 'en-IN';
                msg.rate = 0.75;
                msg.onend = () => {
                    setTimeout(() => {
                        // After speaking, start listening for the child's response
                        document.getElementById('statusMessage').innerText = 'Your turn! Speak now...';
                        startSpeechRecognition();
                    }, 500);
                };
                window.speechSynthesis.speak(msg);
            }, 500);
        }

        function startSpeechRecognition() {
            if (isListening) return;
            isListening = true;
            
            window.SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'en-IN';
            recognition.maxAlternatives = 3;

            // Set timeout for no speech
            const timeout = setTimeout(() => {
                if (isListening) {
                    recognition.stop();
                    document.getElementById('statusMessage').innerText = 'No speech detected. Try again!';
                    document.getElementById('nextBtn').style.display = 'inline-block';
                    isListening = false;
                }
            }, 5000);

            recognition.onresult = (event) => {
                clearTimeout(timeout);
                isListening = false;
                document.getElementById('micIndicator').classList.remove('active');
                
                const spoken = event.results[0][0].transcript.trim().toLowerCase().replace(/[^\w]/g, '');
                const target = warmupWords[currentIndex].toLowerCase();
                
                checkPronunciation(spoken, target);
            };

            recognition.onerror = (event) => {
                clearTimeout(timeout);
                isListening = false;
                document.getElementById('micIndicator').classList.remove('active');
                
                if (event.error === 'no-speech') {
                    document.getElementById('statusMessage').innerText = 'No speech detected. Try again!';
                    document.getElementById('nextBtn').style.display = 'inline-block';
                } else {
                    document.getElementById('statusMessage').innerText = 'Error listening. Try again!';
                    document.getElementById('nextBtn').style.display = 'inline-block';
                }
            };

            recognition.onend = () => {
                clearTimeout(timeout);
                isListening = false;
            };

            recognition.start();
        }

        function checkPronunciation(spoken, target) {
            const similarity = getSimilarityScore(spoken, target);
            const isCorrect = similarity >= 0.65;

            if (isCorrect) {
                // CORRECT pronunciation
                correctCount++;
                document.getElementById('statusMessage').innerHTML = '✅ <strong>Perfect!</strong> Excellent pronunciation!';
                document.getElementById('currentWord').style.color = '#10b981';
                
                // Play congratulation audio
                setTimeout(() => {
                    const praise = new SpeechSynthesisUtterance('Great job! Perfect!');
                    praise.lang = 'en-IN';
                    praise.rate = 0.9;
                    praise.onend = () => {
                        setTimeout(() => {
                            // Move to next word after delay
                            currentIndex++;
                            document.getElementById('currentWord').style.color = '';
                            document.getElementById('nextBtn').style.display = 'inline-block';
                            document.getElementById('statusMessage').innerText = 'Ready for next word?';
                        }, 500);
                    };
                    window.speechSynthesis.speak(praise);
                }, 300);
                
            } else {
                // WRONG pronunciation
                currentAttempt++;
                
                if (currentAttempt === 1) {
                    // First wrong attempt - give another chance
                    document.getElementById('statusMessage').innerHTML = '❌ <strong>Not quite right.</strong> Listen carefully and try again...';
                    document.getElementById('currentWord').style.color = '#ef4444';
                    
                    // Speak the word again more slowly
                    setTimeout(() => {
                        const repeatMsg = new SpeechSynthesisUtterance('Try again. ' + target);
                        repeatMsg.lang = 'en-IN';
                        repeatMsg.rate = 0.6;
                        repeatMsg.onend = () => {
                            setTimeout(() => {
                                // Give second chance
                                document.getElementById('currentWord').style.color = '';
                                document.getElementById('statusMessage').innerText = 'Second attempt. Speak now...';
                                startSpeechRecognition();
                            }, 500);
                        };
                        window.speechSynthesis.speak(repeatMsg);
                    }, 500);
                    
                } else {
                    // Second wrong attempt - mark for more practice and move on
                    wordsToKeep.push(target);
                    document.getElementById('statusMessage').innerHTML = '💪 <strong>Keep practicing this word!</strong> Moving to next word...';
                    document.getElementById('currentWord').style.color = '#fb923c';
                    
                    // Play encouragement audio
                    setTimeout(() => {
                        const encouragement = new SpeechSynthesisUtterance('Keep practicing! Moving to next word.');
                        encouragement.lang = 'en-IN';
                        encouragement.onend = () => {
                            setTimeout(() => {
                                currentIndex++;
                                document.getElementById('currentWord').style.color = '';
                                document.getElementById('nextBtn').style.display = 'inline-block';
                                document.getElementById('statusMessage').innerText = 'Ready for next word?';
                            }, 500);
                        };
                        window.speechSynthesis.speak(encouragement);
                    }, 300);
                }
            }
        }

        function nextWord() {
            document.getElementById('nextBtn').style.display = 'none';
            document.getElementById('statusMessage').innerText = 'Loading next word...';
            
            setTimeout(() => {
                speakAndShowWord();
            }, 800);
        }

        function getSimilarityScore(spoken, target) {
            if (spoken === target) return 1.0;
            
            // Handle verb forms and suffixes
            const spokenBase = spoken.replace(/(?:ing|ed|s|es|ly|er|est)$/i, '');
            const targetBase = target.replace(/(?:ing|ed|s|es|ly|er|est)$/i, '');
            
            if (spokenBase === targetBase) return 0.95;
            if (spokenBase === target || targetBase === spoken) return 0.85;

            // Check for partial matches
            if (spoken.length >= 3 && target.length >= 3) {
                if (spoken.includes(target) || target.includes(spoken)) return 0.75;
            }

            // Calculate Levenshtein distance
            const dist = levenshtein(spoken, target);
            const maxLen = Math.max(spoken.length, target.length);
            
            let allowedErrors = 1;
            if (maxLen > 4) allowedErrors = 2;
            if (maxLen > 7) allowedErrors = 3;
            
            if (dist <= allowedErrors) return Math.max(0.65, 1 - (dist / maxLen));
            return 1 - (dist / maxLen);
        }

        function levenshtein(a, b) {
            const tmp = [];
            for (let i = 0; i <= a.length; i++) tmp[i] = [i];
            for (let j = 0; j <= b.length; j++) tmp[0][j] = j;
            for (let i = 1; i <= a.length; i++) {
                for (let j = 1; j <= b.length; j++) {
                    tmp[i][j] = Math.min(
                        tmp[i - 1][j] + 1,
                        tmp[i][j - 1] + 1,
                        tmp[i - 1][j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1)
                    );
                }
            }
            return tmp[a.length][b.length];
        }

        function showComplete() {
            document.getElementById('practiceSection').style.display = 'none';
            document.getElementById('completeSection').style.display = 'block';

            const total = warmupWords.length;
            const needMore = wordsToKeep.length;

            document.getElementById('totalWords').innerText = total;
            document.getElementById('correctWords').innerText = correctCount;
            document.getElementById('needPractice').innerText = needMore;

            let message = '';
            if (needMore === 0) {
                message = '🎉 Amazing! You mastered all the words perfectly!';
            } else if (correctCount === total) {
                message = '🎊 Excellent! All words correct!';
            } else if (correctCount >= total * 0.8) {
                message = '👏 Great job! You did very well!';
            } else {
                message = `💪 Good practice! Keep working on ${needMore} word${needMore > 1 ? 's' : ''}.`;
            }
            
            document.getElementById('finalMessage').innerText = message;

            // Play final message
            const finalMsg = new SpeechSynthesisUtterance(
                needMore === 0 ? 'Excellent! All words mastered!' : 
                'Good practice! Keep it up!'
            );
            finalMsg.lang = 'en-IN';
            window.speechSynthesis.speak(finalMsg);

            // Update database
            updateDatabase();
        }

        function updateDatabase() {
            fetch('update_warmup.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    sid: sid,
                    words_to_keep: wordsToKeep,
                    all_practiced_words: warmupWords
                })
            }).catch(err => console.log('Database updated'));
        }
    </script>
</body>
</html>