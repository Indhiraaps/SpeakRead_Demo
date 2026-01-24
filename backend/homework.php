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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            width: 100vw;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        
        /* FULL SCREEN CARD */
        .main-card { 
            background: rgba(255, 255, 255, 0.98); 
            width: 100vw;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 40px;
            position: relative;
        }
        
        .header-section {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .header-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 15px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .homework-title {
            font-size: 26px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .status { 
            font-size: 20px; 
            font-weight: 600; 
            margin-bottom: 20px; 
            color: #4a5568;
            text-align: center;
        }
        
        .reading-text { 
            font-size: 28px; 
            line-height: 1.9; 
            padding: 35px 40px; 
            background: #ffffff;
            border-radius: 20px; 
            text-align: left; 
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15);
            border: 2px solid rgba(102, 126, 234, 0.1);
            max-height: 40vh;
            overflow-y: auto;
        }
        
        .word { 
            display: inline; 
            transition: all 0.3s;
            color: #2d3748; 
            padding: 5px 7px; 
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
        
        /* LIVE TRANSCRIPT SECTION */
        .transcript-section {
            margin-top: 20px;
            padding: 25px;
            background: #f8fafc;
            border-radius: 15px;
            border: 2px solid #e2e8f0;
            min-height: 150px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .transcript-title {
            font-size: 16px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .live-indicator {
            width: 10px;
            height: 10px;
            background: #ef4444;
            border-radius: 50%;
            animation: blink 1s infinite;
            display: none;
        }
        
        .live-indicator.active {
            display: block;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        .transcript-text {
            font-size: 22px;
            line-height: 1.8;
            color: #1e293b;
        }
        
        .transcript-word {
            display: inline;
            color: #1e293b;
            font-weight: 500;
        }
        
        /* LIVE SCORE DISPLAY */
        .score-display {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 15px;
            display: none;
        }
        
        .score-display.active {
            display: flex;
        }
        
        .score-item {
            text-align: center;
            color: white;
        }
        
        .score-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .score-value {
            font-size: 36px;
            font-weight: 900;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        #controls {
            text-align: center;
            margin-top: 20px;
        }
        
        .btn { 
            padding: 16px 50px; 
            border: none; 
            border-radius: 15px; 
            cursor: pointer; 
            font-weight: 700; 
            font-size: 18px;
            margin: 10px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
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
        
        .btn-speak {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Warmup Practice Styles */
        .warmup-section {
            display: none;
            text-align: center;
            padding: 40px;
        }
        
        .warmup-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #f87171, #fb923c);
            border-radius: 18px;
            margin: 0 auto 25px;
            font-size: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(248, 113, 113, 0.4);
        }
        
        .warmup-title {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #f87171, #fb923c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
        }
        
        .current-word {
            font-size: 80px;
            font-weight: 900;
            background: linear-gradient(135deg, #f87171, #fb923c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 40px 0;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .status-message {
            font-size: 22px;
            font-weight: 600;
            color: #1e293b;
            margin: 30px auto;
            max-width: 600px;
            min-height: 70px;
            padding: 20px;
            background: rgba(248, 113, 113, 0.15);
            border-radius: 15px;
        }
        
        .progress-info {
            font-size: 18px;
            color: #64748b;
            margin: 15px 0;
        }
        
        /* Complete Section */
        .complete-section { 
            display: none;
            text-align: center;
            padding: 40px;
        }
        
        .stats {
            background: #f1f5f9;
            padding: 30px;
            border-radius: 16px;
            margin: 30px auto;
            max-width: 600px;
        }
        
        .stat-item {
            font-size: 20px;
            margin: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stat-number {
            font-weight: 800;
            color: #f87171;
            font-size: 28px;
        }
        
        .back-btn { 
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white; 
            text-decoration: none; 
            padding: 16px 45px;
            border-radius: 15px; 
            font-weight: 700; 
            display: inline-block;
            margin-top: 20px;
            cursor: pointer;
            border: none;
            font-size: 18px;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
        }
        
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
        
        .mic-indicator.active { 
            display: flex; 
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .main-card {
                padding: 20px;
            }
            
            .reading-text {
                font-size: 24px;
                padding: 25px;
            }
            
            .transcript-text {
                font-size: 18px;
            }
            
            .btn {
                padding: 14px 35px;
                font-size: 16px;
            }
            
            .current-word {
                font-size: 60px;
            }
            
            .score-value {
                font-size: 28px;
            }
        }
    </style>
</head>
<body onload="welcomeVoice()">
    <div class="main-card">
        <div class="mic-indicator" id="micIndicator">🎤</div>
        
        <!-- READING SECTION -->
        <div id="readingSection">
            <div class="header-section">
                <div class="header-icon">📚</div>
                <h2 class="homework-title"><?= htmlspecialchars($hw['H_Topic']) ?></h2>
                <div id="status" class="status">Click START and read aloud</div>
            </div>
            
            <!-- ORIGINAL PASSAGE (Static) -->
            <div id="readingArea" class="reading-text"></div>

            <!-- LIVE TRANSCRIPT DISPLAY -->
            <div class="transcript-section" id="transcriptSection" style="display: none;">
                <div class="transcript-title">
                    <span class="live-indicator" id="liveIndicator"></span>
                    <span>🎤 What I'm Hearing:</span>
                </div>
                <div class="transcript-text" id="transcriptText">
                    <span style="color: #94a3b8; font-style: italic;">Listening...</span>
                </div>
            </div>

            <!-- LIVE SCORE DISPLAY -->
            <div class="score-display" id="scoreDisplay">
                <div class="score-item">
                    <div class="score-label">Correct</div>
                    <div class="score-value" id="correctScore">0</div>
                </div>
                <div class="score-item">
                    <div class="score-label">Total</div>
                    <div class="score-value" id="totalScore">0</div>
                </div>
                <div class="score-item">
                    <div class="score-label">Accuracy</div>
                    <div class="score-value" id="liveAccuracy">0%</div>
                </div>
            </div>
            
            <div id="controls">
                <button id="startBtn" class="btn btn-start" onclick="startSession()">▶ START</button>
                <button id="stopBtn" class="btn btn-stop" onclick="stopSession()">⏹ STOP</button>
            </div>
        </div>

        <!-- WARMUP PRACTICE SECTION -->
        <div id="warmupSection" class="warmup-section">
            <div class="warmup-icon">🔥</div>
            <h1 class="warmup-title">Let's Correct the Mistaken Words</h1>
            <p class="progress-info" id="progressText">Word 1 of 0</p>
            
            <div class="current-word" id="currentWord">READY</div>
            
            <div class="status-message" id="statusMessage">
                Listen to the word, then click SPEAK to repeat it
            </div>
            
            <button id="speakBtn" class="btn btn-speak" onclick="startListening()">
                🎤 SPEAK
            </button>
        </div>

        <!-- COMPLETE SECTION -->
        <div id="completeSection" class="complete-section">
            <div style="font-size: 90px; margin: 20px 0;">🎊</div>
            <h1 class="warmup-title" style="font-size: 36px;">Homework Complete!</h1>
            <div class="stats">
                <div class="stat-item">
                    <span>Reading Accuracy:</span>
                    <span class="stat-number" id="readingAccuracy">0%</span>
                </div>
                <div class="stat-item">
                    <span>Words Practiced:</span>
                    <span class="stat-number" id="totalWords">0</span>
                </div>
                <div class="stat-item">
                    <span>Corrected:</span>
                    <span class="stat-number" id="correctWords" style="color: #10b981;">0</span>
                </div>
                <div class="stat-item">
                    <span>Still Practicing:</span>
                    <span class="stat-number" id="needPractice" style="color: #ef4444;">0</span>
                </div>
            </div>
            <p id="finalMessage" style="font-size: 20px; color: #64748b; margin: 20px 0;"></p>
            <button onclick="moveToDashboard()" class="back-btn">← Back to Dashboard</button>
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
    let transcriptWords = []; // For live transcript display

    // Warmup variables
    let warmupWords = [];
    let currentIndex = 0;
    let currentAttempt = 0;
    let warmupRecognition = null;
    let warmupCorrectCount = 0;
    let wordsToSave = [];
    let listeningTimeout = null;

    const ignoreWords = new Set([
        'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 
        'of', 'with', 'by', 'as', 'is', 'are', 'was', 'were', 'be', 'been', 
        'he', 'she', 'it', 'we', 'they', 'i', 'you', 'me', 'us', 'him', 'her'
    ]);

    const synth = window.speechSynthesis;
    const readingArea = document.getElementById('readingArea');
    const transcriptText = document.getElementById('transcriptText');

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

    // Update total score display
    document.getElementById('totalScore').innerText = targetWords.length;

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
        document.getElementById('liveIndicator').classList.add('active');
        document.getElementById('transcriptSection').style.display = 'block';
        document.getElementById('scoreDisplay').classList.add('active');
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
            // Show interim results in real-time
            let interimTranscript = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                if (event.results[i].isFinal) {
                    const transcript = event.results[i][0].transcript.trim().toLowerCase();
                    processTranscript(transcript);
                    lastProcessedLength = i + 1;
                } else {
                    interimTranscript += event.results[i][0].transcript;
                }
            }
            
            // Update live transcript with interim results
            if (interimTranscript) {
                updateTranscriptDisplay(interimTranscript, false);
            }
        };

        recognition.onend = () => {
            if (document.getElementById('stopBtn').style.display !== 'none') {
                recognition.start();
            }
        };
        
        recognition.onerror = (event) => {
            console.error('Speech recognition error:', event.error);
        };
        
        recognition.start();
    }

    function updateTranscriptDisplay(text, isFinal) {
        if (transcriptWords.length === 0) {
            transcriptText.innerHTML = `<span class="transcript-word">${text}</span>`;
        }
    }

    function addToTranscript(word, isCorrect) {
        const wordSpan = document.createElement('span');
        wordSpan.className = 'transcript-word';
        wordSpan.innerText = word;
        
        transcriptWords.push(wordSpan);
        transcriptText.innerHTML = '';
        transcriptWords.forEach(span => {
            transcriptText.appendChild(span);
            transcriptText.appendChild(document.createTextNode(' '));
        });
        
        // Auto-scroll to bottom
        transcriptText.parentElement.scrollTop = transcriptText.parentElement.scrollHeight;
    }

    function updateLiveScore() {
        document.getElementById('correctScore').innerText = correctCount;
        const accuracy = targetWords.length > 0 
            ? Math.round((correctCount / targetWords.length) * 100) 
            : 0;
        document.getElementById('liveAccuracy').innerText = accuracy + '%';
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

            if (bestMatch !== -1 && bestScore >= 0.55) {
                markWord(bestMatch, true);
                addToTranscript(targetWords[bestMatch], true);
                currentWordPointer = bestMatch + 1;
            } else if (wordStates[currentWordPointer] === 'pending') {
                markWord(currentWordPointer, false);
                addToTranscript(cleanSpoken, false);
                currentWordPointer++;
            }
            
            updateLiveScore();
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
        readingAccuracy = Math.round((correctCount / targetWords.length) * 100);

        wrongWordsList = [...new Set(wrongWordsList)];
        
        if (wrongWordsList.length > 0) {
            startWarmupPractice();
        } else {
            saveScoreAndComplete();
        }
    }

    function startWarmupPractice() {
        document.getElementById('readingSection').style.display = 'none';
        document.getElementById('warmupSection').style.display = 'block';
        
        warmupWords = wrongWordsList;
        currentIndex = 0;
        warmupCorrectCount = 0;
        wordsToSave = [];

        const intro = new SpeechSynthesisUtterance("Let's correct the mistaken words");
        intro.lang = 'en-IN';
        intro.onend = () => {
            setTimeout(() => practiceWord(), 800);
        };
        synth.speak(intro);
    }

    function practiceWord() {
        if (currentIndex >= warmupWords.length) {
            showComplete();
            return;
        }

        const word = warmupWords[currentIndex];
        currentAttempt = 0;

        document.getElementById('currentWord').innerText = word.toUpperCase();
        document.getElementById('progressText').innerText = `Word ${currentIndex + 1} of ${warmupWords.length}`;
        document.getElementById('statusMessage').innerText = 'Listen to the word...';
        document.getElementById('speakBtn').disabled = true;

        setTimeout(() => {
            const msg = new SpeechSynthesisUtterance(word);
            msg.lang = 'en-IN';
            msg.rate = 0.75;
            msg.onend = () => {
                setTimeout(() => {
                    document.getElementById('statusMessage').innerText = 'Click SPEAK and repeat the word';
                    document.getElementById('speakBtn').disabled = false;
                }, 500);
            };
            synth.speak(msg);
        }, 500);
    }

    function startListening() {
        document.getElementById('speakBtn').disabled = true;
        document.getElementById('statusMessage').innerText = '🎤 Listening... (5 seconds)';
        document.getElementById('micIndicator').classList.add('active');

        window.SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        warmupRecognition = new SpeechRecognition();
        warmupRecognition.continuous = false;
        warmupRecognition.interimResults = false;
        warmupRecognition.lang = 'en-IN';

        let heard = false;

        warmupRecognition.onresult = (event) => {
            heard = true;
            clearTimeout(listeningTimeout);
            const spoken = event.results[0][0].transcript.trim().toLowerCase().replace(/[^\w]/g, '');
            const target = warmupWords[currentIndex].toLowerCase();
            checkPronunciation(spoken, target);
        };

        warmupRecognition.onerror = (event) => {
            clearTimeout(listeningTimeout);
            if (!heard) {
                handleNoSpeech();
            }
        };

        listeningTimeout = setTimeout(() => {
            if (!heard) {
                warmupRecognition.stop();
                handleNoSpeech();
            }
        }, 5000);

        warmupRecognition.start();
    }

    function handleNoSpeech() {
        document.getElementById('micIndicator').classList.remove('active');
        
        if (currentAttempt === 0) {
            document.getElementById('statusMessage').innerText = "I didn't hear you. Please speak again!";
            
            const retry = new SpeechSynthesisUtterance("I didn't hear you. Please speak again!");
            retry.lang = 'en-IN';
            retry.onend = () => {
                setTimeout(() => {
                    document.getElementById('speakBtn').disabled = false;
                }, 500);
            };
            synth.speak(retry);
        } else {
            wordsToSave.push(warmupWords[currentIndex]);
            document.getElementById('statusMessage').innerText = "It's okay, let's move to the next word";
            
            const moveOn = new SpeechSynthesisUtterance("It's okay, let's move to the next word");
            moveOn.lang = 'en-IN';
            moveOn.onend = () => {
                setTimeout(() => {
                    currentIndex++;
                    practiceWord();
                }, 1500);
            };
            synth.speak(moveOn);
        }
        
        currentAttempt++;
    }

    function checkPronunciation(spoken, target) {
        document.getElementById('micIndicator').classList.remove('active');
        
        const similarity = getSimilarityScore(spoken, target);
        const isCorrect = similarity >= 0.65;

        if (isCorrect) {
            warmupCorrectCount++;
            document.getElementById('statusMessage').innerText = '✅ Great work!';
            document.getElementById('currentWord').style.color = '#10b981';
            
            const praise = new SpeechSynthesisUtterance('Great work!');
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
            if (currentAttempt === 0) {
                currentAttempt = 1;
                document.getElementById('statusMessage').innerText = 'Not quite right. Try once more!';
                document.getElementById('currentWord').style.color = '#ef4444';
                
                const encourage = new SpeechSynthesisUtterance('Not quite right. Try once more!');
                encourage.lang = 'en-IN';
                encourage.onend = () => {
                    setTimeout(() => {
                        const repeat = new SpeechSynthesisUtterance(target);
                        repeat.lang = 'en-IN';
                        repeat.rate = 0.7;
                        repeat.onend = () => {
                            setTimeout(() => {
                                document.getElementById('currentWord').style.color = '';
                                document.getElementById('statusMessage').innerText = 'Click SPEAK and try again';
                                document.getElementById('speakBtn').disabled = false;
                            }, 500);
                        };
                        synth.speak(repeat);
                    }, 500);
                };
                synth.speak(encourage);

            } else {
                wordsToSave.push(target);
                document.getElementById('statusMessage').innerText = "It's okay, let's move to the next word";
                
                const moveOn = new SpeechSynthesisUtterance("It's okay, let's move to the next word");
                moveOn.lang = 'en-IN';
                moveOn.onend = () => {
                    setTimeout(() => {
                        document.getElementById('currentWord').style.color = '';
                        currentIndex++;
                        practiceWord();
                    }, 1500);
                };
                synth.speak(moveOn);
            }
        }
    }

    function showComplete() {
        document.getElementById('warmupSection').style.display = 'none';
        document.getElementById('completeSection').style.display = 'block';

        const total = warmupWords.length;
        const needMore = wordsToSave.length;

        document.getElementById('readingAccuracy').innerText = readingAccuracy + "%";
        document.getElementById('totalWords').innerText = total;
        document.getElementById('correctWords').innerText = warmupCorrectCount;
        document.getElementById('needPractice').innerText = needMore;

        let message = needMore === 0 
            ? '🎉 Amazing! You corrected all the words!' 
            : `Good job! Keep practicing the remaining ${needMore} word${needMore > 1 ? 's' : ''}.`;
        
        document.getElementById('finalMessage').innerText = message;

        const finalMsg = new SpeechSynthesisUtterance(
            needMore === 0 ? 'Amazing! You corrected all the words!' : 'Good job! Keep practicing.'
        );
        finalMsg.lang = 'en-IN';
        synth.speak(finalMsg);

        saveHomeworkMistakes();
    }

    function saveHomeworkMistakes() {
        console.log('=== SAVING HOMEWORK MISTAKES ===');
        console.log('Student ID:', sid);
        console.log('Words to save:', wordsToSave);
        
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
        .then(data => {
            console.log('✅ Score saved:', data);
        })
        .catch(err => {
            console.error('❌ Score save error:', err);
        });

        if (wordsToSave.length > 0) {
            fetch('save_homework_mistakes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    sid: sid,
                    wrong_words: wordsToSave
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('✅ Mistakes saved:', data);
                } else {
                    console.error('❌ Save failed:', data.message);
                }
            })
            .catch(err => {
                console.error('❌ Fetch error:', err);
            });
        } else {
            console.log('ℹ️ No mistakes to save - all words corrected!');
        }
    }

    function saveScoreAndComplete() {
        fetch('save_score.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                sid: sid,
                hid: hid,
                accuracy: readingAccuracy
            })
        }).then(() => {
            document.getElementById('readingSection').style.display = 'none';
            document.getElementById('completeSection').style.display = 'block';
            document.getElementById('readingAccuracy').innerText = readingAccuracy + "%";
            document.getElementById('totalWords').innerText = "0";
            document.getElementById('correctWords').innerText = "0";
            document.getElementById('needPractice').innerText = "0";
            document.getElementById('finalMessage').innerText = "Perfect reading! No mistakes!";
            
            const perfect = new SpeechSynthesisUtterance("Perfect reading! No mistakes!");
            perfect.lang = 'en-IN';
            synth.speak(perfect);
        });
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