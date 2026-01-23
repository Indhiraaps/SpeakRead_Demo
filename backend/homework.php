<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../frontend/login.html");
    exit();
}

if (!isset($_GET['hid'])) {
    header("Location: student_dashboard.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$hid = $_GET['hid'];

$stmt = $pdo->prepare("SELECT * FROM Homework WHERE HID = ?");
$stmt->execute([$hid]);
$hw = $stmt->fetch();

if (!$hw) { die("Homework not found."); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Homework Practice | SpeakRead</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            padding: 20px;
        }
        .main-card { 
            background: rgba(255, 255, 255, 0.98); 
            border-radius: 24px; 
            padding: 30px 40px;
            max-width: 900px; 
            width: 90%; 
            max-height: 90vh;
            box-shadow: 0 30px 90px rgba(0,0,0,0.25);
            text-align: center;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .header-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        .homework-title {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        .status { 
            font-size: 18px; 
            font-weight: 600; 
            margin-bottom: 15px; 
            color: #4a5568; 
        }
        .reading-text { 
            font-size: 26px; 
            line-height: 1.8; 
            margin: 15px 0; 
            padding: 25px 30px; 
            background: #ffffff;
            border-radius: 20px; 
            text-align: left; 
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15);
            min-height: 180px;
            max-height: 40vh;
            overflow-y: auto;
            border: 2px solid rgba(102, 126, 234, 0.1);
            flex: 1;
        }
        .word { 
            display: inline; 
            transition: all 0.3s;
            color: #2d3748; 
            padding: 4px 6px; 
            border-radius: 6px;
        }
        .word.correct { 
            color: #10b981 !important; 
            font-weight: 700;
            background: rgba(16, 185, 129, 0.1);
        } 
        .word.incorrect { 
            color: #ef4444 !important; 
            font-weight: 700;
            background: rgba(239, 68, 68, 0.1);
        }
        .btn { 
            padding: 14px 45px; 
            border: none; 
            border-radius: 14px; 
            cursor: pointer; 
            font-weight: 700; 
            font-size: 17px;
            margin: 10px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }
        .btn-start { 
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        .btn-stop { 
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white; 
            display: none;
        }
        
        /* Reading Results Screen - Shows accuracy and button to continue to warmup */
        .reading-results {
            display: none;
        }
        .accuracy-display {
            font-size: 80px;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 20px 0;
        }
        .remarks {
            font-size: 22px;
            font-weight: 600;
            color: #1e293b;
            margin: 20px 0;
            padding: 20px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 12px;
        }
        
        /* Warmup Practice Styles */
        .warmup-section {
            display: none;
        }
        .warmup-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #f87171, #fb923c);
            border-radius: 15px;
            margin: 0 auto 20px;
            font-size: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .warmup-title {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #f87171, #fb923c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
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
        }
        .status-message {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin: 25px 0;
            min-height: 60px;
            padding: 15px;
            background: rgba(248, 113, 113, 0.15);
            border-radius: 12px;
        }
        
        .complete-section { 
            display: none; 
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
        }
        .continue-btn {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        .mic-indicator {
            position: absolute;
            top: 25px;
            right: 25px;
            width: 45px;
            height: 45px;
            background: #ef4444;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
        }
        .mic-indicator.active { display: flex; }
    </style>
</head>
<body onload="welcomeVoice()">
    <div class="main-card">
        <div class="mic-indicator" id="micIndicator">🎤</div>
        
        <!-- READING SECTION -->
        <div id="readingSection">
            <div class="header-icon">📚</div>
            <h2 class="homework-title"><?= htmlspecialchars($hw['H_Topic']) ?></h2>
            <div id="status" class="status">Click START and read aloud</div>
            <div id="readingArea" class="reading-text"></div>
            <div id="controls">
                <button id="startBtn" class="btn btn-start" onclick="startSession()">▶ START</button>
                <button id="stopBtn" class="btn btn-stop" onclick="stopSession()">⏹ STOP</button>
            </div>
        </div>

        <!-- READING RESULTS - Show accuracy, then option to do warmup -->
        <div id="readingResults" class="reading-results">
            <div style="font-size: 60px; margin: 20px 0;" id="resultIcon">🎉</div>
            <h2 style="font-size: 28px; color: #1e293b; margin-bottom: 10px;">Reading Complete!</h2>
            <div class="accuracy-display" id="accuracyDisplay">0%</div>
            <div class="remarks" id="remarksDisplay"></div>
            
            <div id="warmupOption" style="margin-top: 20px;">
                <p style="font-size: 18px; color: #64748b; margin-bottom: 15px;">
                    You had <strong id="mistakeCount">0</strong> mistake(s). Would you like to practice them?
                </p>
                <button class="btn continue-btn" onclick="startWarmupFromResults()">
                    🔥 Start Warmup Practice
                </button>
                <button class="btn back-btn" onclick="skipWarmup()">
                    Skip & Go to Dashboard
                </button>
            </div>

            <div id="noMistakesOption" style="display: none; margin-top: 20px;">
                <p style="font-size: 18px; color: #10b981; margin-bottom: 15px;">
                    ✨ Perfect! No mistakes to practice!
                </p>
                <button class="btn btn-start" onclick="moveToDashboard()">
                    ← Back to Dashboard
                </button>
            </div>
        </div>

        <!-- WARMUP PRACTICE SECTION -->
        <div id="warmupSection" class="warmup-section">
            <div class="warmup-icon">🔥</div>
            <h1 class="warmup-title">Warmup Practice</h1>
            <p class="status-message" id="progressText">Word 1 of 0</p>
            <div class="current-word" id="currentWord">GET READY</div>
            <div class="status-message" id="statusMessage">Listen carefully...</div>
            <button id="readyBtn" class="btn btn-start" onclick="startWarmupListening()" style="display: none;">
                🎤 I'm Ready to Repeat
            </button>
        </div>

        <!-- COMPLETE SECTION - After warmup -->
        <div id="completeSection" class="complete-section">
            <div style="font-size: 80px; margin: 20px 0;">🎊</div>
            <h1 class="warmup-title" style="font-size: 32px;">All Done!</h1>
            <div style="background: #f1f5f9; padding: 20px; border-radius: 12px; margin: 20px 0;">
                <div style="font-size: 18px; margin: 10px 0;">
                    Reading Accuracy: <span style="font-weight: 800; color: #667eea; font-size: 24px;" id="finalAccuracy">0%</span>
                </div>
                <div style="font-size: 18px; margin: 10px 0;">
                    Words Practiced: <span style="font-weight: 800; color: #f87171; font-size: 24px;" id="totalWords">0</span>
                </div>
                <div style="font-size: 18px; margin: 10px 0;">
                    Mastered: <span style="font-weight: 800; color: #10b981; font-size: 24px;" id="masteredWords">0</span>
                </div>
                <div style="font-size: 18px; margin: 10px 0;">
                    Need More Practice: <span style="font-weight: 800; color: #ef4444; font-size: 24px;" id="needPractice">0</span>
                </div>
            </div>
            <p id="finalMessage" style="font-size: 18px; color: #64748b; margin: 20px 0;"></p>
            <button class="btn btn-start" onclick="moveToDashboard()">← Back to Dashboard</button>
        </div>
    </div>

    <script>
    const fullPara = `<?php echo addslashes($hw['H_Para']); ?>`;
    const sid = <?php echo $student_id; ?>;
    const hid = <?php echo $hid; ?>;
    
    let recognition;
    let correctCount = 0;
    let wrongWordsList = [];
    let targetWords = []; 
    let wordStates = {};
    let currentWordPointer = 0;
    let readingAccuracy = 0;

    // Warmup variables
    let warmupWords = [];
    let currentIndex = 0;
    let attemptCount = {};
    let warmupRecognition = null;
    let warmupCorrectCount = 0;
    let wordsToSave = [];

    const ignoreWords = new Set([
        'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 
        'of', 'with', 'by', 'as', 'is', 'are', 'was', 'were', 
        'he', 'she', 'it', 'we', 'they', 'i', 'you', 'me', 'us', 'him', 'her'
    ]);

    const synth = window.speechSynthesis;
    const readingArea = document.getElementById('readingArea');

    // Setup paragraph
    const tokens = fullPara.split(/(\s+)/); 
    readingArea.innerHTML = tokens.map((token) => {
        if (token.trim().length === 0) return token;
        const clean = token.toLowerCase().replace(/[^\w]/g, '');
        if (clean.length === 0) return token;
        
        const idx = targetWords.length;
        targetWords.push(clean);
        wordStates[idx] = 'pending';
        return `<span id="word-${idx}" class="word">${token}</span>`;
    }).join('');

    function welcomeVoice() {
        const msg = new SpeechSynthesisUtterance("Click Start button and read aloud.");
        msg.lang = 'en-IN';
        synth.speak(msg);
    }

    function startSession() {
        document.getElementById('startBtn').style.display = 'none';
        document.getElementById('stopBtn').style.display = 'inline-block';
        document.getElementById('status').innerText = "🎙️ Reading in progress...";
        document.getElementById('micIndicator').classList.add('active');
        initSpeech();
    }

    function initSpeech() {
        window.SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = 'en-IN';

        let lastProcessedLength = 0;

        recognition.onresult = (event) => {
            for (let i = lastProcessedLength; i < event.results.length; i++) {
                if (event.results[i].isFinal) {
                    const transcript = event.results[i][0].transcript.trim().toLowerCase();
                    processTranscript(transcript);
                    lastProcessedLength = i + 1;
                }
            }
        };

        recognition.onend = () => {
            if (document.getElementById('stopBtn').style.display !== 'none') {
                recognition.start();
            }
        };
        recognition.start();
    }

    function processTranscript(transcript) {
        const spokenWords = transcript.split(/\s+/).filter(w => w.length > 0);
        
        spokenWords.forEach(spokenWord => {
            const cleanSpoken = spokenWord.replace(/[^\w]/g, '');
            if (cleanSpoken.length === 0 || currentWordPointer >= targetWords.length) return;

            let bestMatch = -1;
            let bestScore = 0;

            for (let i = currentWordPointer; i < Math.min(currentWordPointer + 4, targetWords.length); i++) {
                if (wordStates[i] === 'pending') {
                    const score = getSimilarityScore(cleanSpoken, targetWords[i]);
                    if (score > bestScore) {
                        bestScore = score;
                        bestMatch = i;
                    }
                }
            }

            if (bestMatch !== -1 && bestScore >= 0.45) {
                markWord(bestMatch, true);
                currentWordPointer = bestMatch + 1;
            } else if (wordStates[currentWordPointer] === 'pending') {
                markWord(currentWordPointer, false);
                currentWordPointer++;
            }
        });
    }

    function markWord(index, isCorrect) {
        if (wordStates[index] !== 'pending') return;

        const element = document.getElementById(`word-${index}`);
        if (!element) return;

        wordStates[index] = isCorrect ? 'correct' : 'incorrect';
        element.className = 'word ' + (isCorrect ? 'correct' : 'incorrect');
        
        if (isCorrect) {
            correctCount++;
        } else {
            const word = targetWords[index];
            if (word.length > 2 && !ignoreWords.has(word)) {
                wrongWordsList.push(word);
            }
        }
    }

    function getSimilarityScore(spoken, target) {
        if (spoken === target) return 1.0;
        const dist = levenshtein(spoken, target);
        return 1 - (dist / Math.max(spoken.length, target.length));
    }

    function stopSession() {
        if (recognition) {
            recognition.stop();
            recognition.onend = null;
        }

        document.getElementById('micIndicator').classList.remove('active');
        
        // Calculate reading accuracy
        readingAccuracy = Math.round((correctCount / targetWords.length) * 100);
        
        // Remove duplicates
        wrongWordsList = [...new Set(wrongWordsList)];
        
        // Save reading accuracy to database NOW
        saveReadingAccuracy();
        
        // Show reading results
        showReadingResults();
    }

    function showReadingResults() {
        document.getElementById('readingSection').style.display = 'none';
        document.getElementById('readingResults').style.display = 'block';
        
        // Display accuracy
        document.getElementById('accuracyDisplay').innerText = readingAccuracy + "%";
        document.getElementById('remarksDisplay').innerText = getRemarks(readingAccuracy);
        
        // Set icon based on accuracy
        let icon = '🎉';
        if (readingAccuracy >= 95) icon = '🏆';
        else if (readingAccuracy >= 85) icon = '🌟';
        else if (readingAccuracy >= 75) icon = '👍';
        else if (readingAccuracy >= 60) icon = '💪';
        else icon = '📖';
        document.getElementById('resultIcon').innerText = icon;
        
        // Show warmup option or no mistakes option
        if (wrongWordsList.length > 0) {
            document.getElementById('mistakeCount').innerText = wrongWordsList.length;
            document.getElementById('warmupOption').style.display = 'block';
            document.getElementById('noMistakesOption').style.display = 'none';
        } else {
            document.getElementById('warmupOption').style.display = 'none';
            document.getElementById('noMistakesOption').style.display = 'block';
        }
        
        // Voice feedback
        const msg = new SpeechSynthesisUtterance(getRemarks(readingAccuracy));
        msg.lang = 'en-IN';
        synth.speak(msg);
    }

    function getRemarks(accuracy) {
        if (accuracy >= 95) return "Outstanding! Perfect reading with excellent clarity!";
        if (accuracy >= 85) return "Excellent work! You read very well!";
        if (accuracy >= 75) return "Great job! Keep practicing to improve further!";
        if (accuracy >= 60) return "Good effort! Practice more to read better!";
        return "Keep practicing! You can do better next time!";
    }

    function saveReadingAccuracy() {
        // Save ONLY reading accuracy to Scores table
        fetch('save_score.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                sid: sid,
                hid: hid,
                accuracy: readingAccuracy
            })
        })
        .then(response => response.json())
        .then(data => console.log('Reading accuracy saved:', data))
        .catch(err => console.error('Save error:', err));
    }

    function startWarmupFromResults() {
        document.getElementById('readingResults').style.display = 'none';
        document.getElementById('warmupSection').style.display = 'block';
        
        warmupWords = wrongWordsList;
        currentIndex = 0;
        warmupCorrectCount = 0;
        wordsToSave = [];
        attemptCount = {};

        setTimeout(() => practiceWord(), 1000);
    }

    function skipWarmup() {
        // User chose to skip warmup - go directly to dashboard
        moveToDashboard();
    }

    function practiceWord() {
        if (currentIndex >= warmupWords.length) {
            showComplete();
            return;
        }

        const word = warmupWords[currentIndex];
        if (!attemptCount[word]) attemptCount[word] = 0;

        document.getElementById('currentWord').innerText = word.toUpperCase();
        document.getElementById('progressText').innerText = `Word ${currentIndex + 1} of ${warmupWords.length}`;
        document.getElementById('statusMessage').innerText = 'Listen carefully...';
        document.getElementById('readyBtn').style.display = 'none';

        setTimeout(() => {
            const msg = new SpeechSynthesisUtterance(word);
            msg.lang = 'en-IN';
            msg.rate = 0.75;
            msg.onend = () => {
                setTimeout(() => {
                    document.getElementById('statusMessage').innerText = 'Now repeat the word! Click when ready.';
                    document.getElementById('readyBtn').style.display = 'inline-block';
                }, 800);
            };
            synth.speak(msg);
        }, 500);
    }

    function startWarmupListening() {
        document.getElementById('readyBtn').style.display = 'none';
        document.getElementById('statusMessage').innerText = '🎤 Listening...';
        document.getElementById('micIndicator').classList.add('active');

        window.SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        warmupRecognition = new SpeechRecognition();
        warmupRecognition.continuous = false;
        warmupRecognition.interimResults = false;
        warmupRecognition.lang = 'en-IN';

        warmupRecognition.onresult = (event) => {
            const spoken = event.results[0][0].transcript.trim().toLowerCase().replace(/[^\w]/g, '');
            const target = warmupWords[currentIndex].toLowerCase();
            checkPronunciation(spoken, target);
        };

        warmupRecognition.onerror = () => {
            document.getElementById('micIndicator').classList.remove('active');
            document.getElementById('statusMessage').innerText = 'Could not hear you. Try again!';
            document.getElementById('readyBtn').style.display = 'inline-block';
        };

        warmupRecognition.start();
    }

    function checkPronunciation(spoken, target) {
        document.getElementById('micIndicator').classList.remove('active');
        
        const similarity = getSimilarityScore(spoken, target);
        const isCorrect = similarity >= 0.65;
        
        attemptCount[target]++;

        if (isCorrect) {
            // CORRECT - will NOT be saved to database
            warmupCorrectCount++;
            document.getElementById('statusMessage').innerText = '✅ Perfect! Excellent job!';
            document.getElementById('currentWord').style.color = '#10b981';
            
            const praise = new SpeechSynthesisUtterance('Great job!');
            praise.lang = 'en-IN';
            praise.onend = () => {
                setTimeout(() => {
                    document.getElementById('currentWord').style.color = '';
                    currentIndex++;
                    practiceWord();
                }, 1500);
            };
            synth.speak(praise);

        } else {
            if (attemptCount[target] === 1) {
                // First attempt failed - try again
                document.getElementById('statusMessage').innerText = '❌ Not quite right. Listen again...';
                document.getElementById('currentWord').style.color = '#ef4444';
                
                const encourage = new SpeechSynthesisUtterance('Try again!');
                encourage.lang = 'en-IN';
                encourage.onend = () => {
                    setTimeout(() => {
                        const repeat = new SpeechSynthesisUtterance(target);
                        repeat.lang = 'en-IN';
                        repeat.rate = 0.65;
                        repeat.onend = () => {
                            setTimeout(() => {
                                document.getElementById('currentWord').style.color = '';
                                document.getElementById('statusMessage').innerText = 'Try one more time!';
                                document.getElementById('readyBtn').style.display = 'inline-block';
                            }, 500);
                        };
                        synth.speak(repeat);
                    }, 500);
                };
                synth.speak(encourage);

            } else {
                // Second attempt failed - SAVE TO DATABASE
                wordsToSave.push(target);
                document.getElementById('statusMessage').innerText = '💪 Keep practicing! Moving to next word...';
                
                const next = new SpeechSynthesisUtterance('Keep practicing!');
                next.lang = 'en-IN';
                next.onend = () => {
                    setTimeout(() => {
                        document.getElementById('currentWord').style.color = '';
                        currentIndex++;
                        practiceWord();
                    }, 1800);
                };
                synth.speak(next);
            }
        }
    }

    function showComplete() {
        document.getElementById('warmupSection').style.display = 'none';
        document.getElementById('completeSection').style.display = 'block';

        const total = warmupWords.length;
        const mastered = warmupCorrectCount;
        const needMore = wordsToSave.length;

        document.getElementById('finalAccuracy').innerText = readingAccuracy + "%";
        document.getElementById('totalWords').innerText = total;
        document.getElementById('masteredWords').innerText = mastered;
        document.getElementById('needPractice').innerText = needMore;

        let message = needMore === 0 
            ? '🎉 Amazing! You mastered all the words!' 
            : `Good practice! Keep working on the remaining ${needMore} word${needMore > 1 ? 's' : ''}.`;
        
        document.getElementById('finalMessage').innerText = message;

        const finalMsg = new SpeechSynthesisUtterance(
            needMore === 0 ? 'Excellent! All words mastered!' : 'Good practice! Keep it up!'
        );
        finalMsg.lang = 'en-IN';
        synth.speak(finalMsg);

        // Save ONLY words that failed after 2 attempts
        if (wordsToSave.length > 0) {
            saveHomeworkMistakes();
        }
    }

    function saveHomeworkMistakes() {
        // Save ONLY words that need more practice (failed 2 attempts)
        fetch('save_homework_mistakes.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                sid: sid,
                wrong_words: wordsToSave
            })
        })
        .then(response => response.json())
        .then(data => console.log('Homework mistakes saved:', data))
        .catch(err => console.error('Save error:', err));
    }

    function moveToDashboard() {
        window.location.href = 'student_dashboard.php';
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