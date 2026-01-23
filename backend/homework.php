<?php
session_start();
require_once '../config/db.php';

// Check if student is logged in
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
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
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
            background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
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
            padding: 30px 40px;
            max-width: 900px; 
            width: 90%; 
            max-height: 90vh;
            box-shadow: 0 30px 90px rgba(0,0,0,0.25);
            text-align: center;
            position: relative;
            animation: slideUp 0.6s ease-out;
            display: flex;
            flex-direction: column;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
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
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
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
            min-height: 25px;
        }

        .reading-text { 
            font-size: 26px; 
            line-height: 1.8; 
            margin: 15px 0; 
            padding: 25px 30px; 
            background: linear-gradient(to bottom right, #ffffff, #f8f9ff);
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
            transition: all 0.3s ease;
            color: #2d3748; 
            padding: 4px 6px; 
            border-radius: 6px;
            font-weight: 500;
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

        .controls { 
            margin-top: 20px; 
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn { 
            padding: 14px 45px; 
            border: none; 
            border-radius: 14px; 
            cursor: pointer; 
            font-weight: 700; 
            font-size: 17px; 
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }

        .btn-start { 
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-start:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
        }

        .btn-stop { 
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white; 
            display: none;
        }

        .btn-stop:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(239, 68, 68, 0.5);
        }
        
        .results { 
            display: none;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Warmup Styles */
        .warmup-container {
            display: none;
        }

        .warmup-word-display {
            font-size: 72px;
            font-weight: 900;
            margin: 30px 0;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            background: linear-gradient(135deg, #f87171, #fb923c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: color 0.3s ease;
        }

        .warmup-status {
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

        .warmup-progress {
            color: #64748b;
            font-size: 16px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .btn-speak {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }

        .btn-speak:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(59, 130, 246, 0.5);
        }

        .btn-speak:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .result-icon {
            font-size: 60px;
            margin-bottom: 10px;
            animation: scaleIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        .accuracy { 
            font-size: 70px; 
            font-weight: 900; 
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 10px 0;
        }

        .remarks { 
            font-size: 20px; 
            font-weight: 600; 
            color: #1e293b; 
            margin: 15px 0;
            padding: 15px;
            background: linear-gradient(to right, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 14px;
            border-left: 4px solid #667eea;
        }

        .back-btn { 
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white; 
            text-decoration: none; 
            padding: 12px 35px; 
            border-radius: 14px; 
            font-weight: 700; 
            display: inline-block;
            margin-top: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 15px;
        }

        .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(107, 114, 128, 0.4);
        }

        .mic-indicator {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            animation: micPulse 1.5s ease-in-out infinite;
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.5);
        }

        @keyframes micPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .mic-indicator.active {
            display: flex;
        }

        .progress-bar {
            width: 100%;
            height: 5px;
            background: rgba(102, 126, 234, 0.2);
            border-radius: 10px;
            margin-bottom: 12px;
            overflow: hidden;
            display: none;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .main-card { padding: 25px 20px; }
            .reading-text { font-size: 22px; padding: 20px; }
            .warmup-word-display { font-size: 48px; }
            .accuracy { font-size: 60px; }
        }
    </style>
</head>
<body onload="welcomeVoice()">

    <div class="main-card">
        <div class="header-icon">📚</div>
        <div class="mic-indicator" id="micIndicator">🎤</div>
        
        <h2 class="homework-title"><?= htmlspecialchars($hw['H_Topic']) ?></h2>
        <div id="status" class="status">Click START and read aloud</div>
        <div class="progress-bar" id="progressBar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
        <div id="readingArea" class="reading-text"></div>

        <div class="controls" id="controls">
            <button id="startBtn" class="btn btn-start" onclick="startSession()">▶ START</button>
            <button id="stopBtn" class="btn btn-stop" onclick="stopSession()">⏹ STOP</button>
        </div>

        <div id="results" class="results">
            <div class="result-icon" id="resultIcon">🎉</div>
            <div class="accuracy" id="accDisplay">0%</div>
            <div class="remarks" id="remDisplay"></div>
            <a href="student_dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <!-- Warmup Practice Section -->
        <div id="warmupContainer" class="warmup-container">
            <div class="header-icon" style="background: linear-gradient(135deg, #f87171, #fb923c);">🔥</div>
            <h2 class="homework-title" style="background: linear-gradient(135deg, #f87171, #fb923c); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                Let's Correct Mistaken Words
            </h2>
            
            <div class="warmup-progress" id="warmupProgress">Word 1 of 0</div>
            <div class="warmup-word-display" id="currentWordDisplay">GET READY</div>
            <div class="warmup-status" id="warmupStatus">Click SPEAK button to repeat the word...</div>
            
            <div class="controls">
                <button id="speakBtn" class="btn btn-speak" onclick="startWarmupListening()">
                    🎤 SPEAK
                </button>
            </div>
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

    // Warmup variables
    let warmupWords = [];
    let currentWarmupIndex = 0;
    let readingPracticeMistakes = [];
    let warmupRecognition;
    let silenceTimer;

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
        document.getElementById('progressBar').style.display = 'block';
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
                    updateProgress();
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

            if (bestMatch !== -1 && bestScore >= 0.55) {
                markWord(bestMatch, true);
                currentWordPointer = bestMatch + 1;
            } else if (wordStates[currentWordPointer] === 'pending') {
                markWord(currentWordPointer, false);
                currentWordPointer++;
            }
        });
    }

    function markWord(index, isCorrect) {
        const element = document.getElementById(`word-${index}`);
        if(!element) return;
        wordStates[index] = isCorrect ? 'correct' : 'incorrect';
        element.className = 'word ' + (isCorrect ? 'correct' : 'incorrect');
        if (isCorrect) correctCount++;
        else wrongWordsList.push(targetWords[index]);
    }

    function updateProgress() {
        const progress = (currentWordPointer / targetWords.length) * 100;
        document.getElementById('progressFill').style.width = progress + '%';
    }

    function stopSession() {
        if (recognition) { 
            recognition.stop(); 
            recognition.onend = null; 
        }
        
        document.getElementById('readingArea').style.display = 'none';
        document.getElementById('controls').style.display = 'none';
        document.getElementById('status').style.display = 'none';
        document.getElementById('micIndicator').classList.remove('active');
        document.getElementById('progressBar').style.display = 'none';

        // Remove duplicates from wrong words
        wrongWordsList = [...new Set(wrongWordsList)];
        
        const accuracy = Math.round((correctCount / targetWords.length) * 100);

        if (wrongWordsList.length > 0) {
            // Start warmup practice
            startWarmupPractice();
        } else {
            // No mistakes - show results
            showFinalResults(accuracy, "Perfect! All words correct!");
        }
    }
/******************
     * ENHANCED WARMUP PRACTICE
     ******************/
    function startWarmupPractice() {
        document.getElementById('results').style.display = 'none';
        document.getElementById('readingArea').style.display = 'none';
        document.getElementById('warmupContainer').style.display = 'block';
        
        warmupWords = [...wrongWordsList];
        currentWarmupIndex = 0;
        readingPracticeMistakes = [];
        
        // Introductory Voice Instruction
        const intro = new SpeechSynthesisUtterance("Great effort on the reading! Now, let's practice the words you missed to help you improve.");
        intro.lang = 'en-IN';
        intro.onend = () => {
            setTimeout(showWarmupWord, 500);
        };
        synth.speak(intro);
    }

    function showWarmupWord() {
        if (currentWarmupIndex >= warmupWords.length) {
            completeWarmupPractice();
            return;
        }

        const word = warmupWords[currentWarmupIndex];
        const display = document.getElementById('currentWordDisplay');
        const status = document.getElementById('warmupStatus');
        
        // Reset Visuals
        display.innerText = word.toUpperCase();
        display.style.transform = "scale(1)";
        display.style.opacity = "1";
        display.style.color = ""; // Resets to gradient
        
        document.getElementById('warmupProgress').innerText = `Word ${currentWarmupIndex + 1} of ${warmupWords.length}`;
        status.innerHTML = "👂 <strong>Listen...</strong>";
        document.getElementById('speakBtn').disabled = true;

        // Teacher's pronunciation
        const utter = new SpeechSynthesisUtterance(word);
        utter.lang = 'en-IN';
        utter.rate = 0.8; // Slightly slower for clarity
        utter.onend = () => {
            setTimeout(() => {
                status.innerHTML = "🎤 <strong>Your turn! Click SPEAK and repeat the word.</strong>";
                document.getElementById('speakBtn').disabled = false;
                // Subtle pulse to call attention to the button
                document.getElementById('speakBtn').style.animation = "bounce 1s infinite";
            }, 400);
        };
        synth.speak(utter);
    }

    function startWarmupListening() {
        if (warmupRecognition) { try { warmupRecognition.stop(); } catch(e) {} }
        clearTimeout(silenceTimer);

        document.getElementById('speakBtn').style.animation = "none";
        
        window.SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        warmupRecognition = new SpeechRecognition();
        warmupRecognition.lang = 'en-IN';
        
        document.getElementById('micIndicator').classList.add('active');
        document.getElementById('warmupStatus').innerHTML = "🎙️ <strong>Listening...</strong>";
        document.getElementById('speakBtn').disabled = true;

        // Auto-stop if no sound heard in 5 seconds
        silenceTimer = setTimeout(() => {
            if (warmupRecognition) {
                warmupRecognition.stop();
                handleNoSpeech();
            }
        }, 5000);

        warmupRecognition.onresult = (event) => {
            clearTimeout(silenceTimer);
            document.getElementById('micIndicator').classList.remove('active');
            const spoken = event.results[0][0].transcript.toLowerCase().trim().replace(/[^\w]/g, '');
            handleWarmupResult(spoken);
        };

        warmupRecognition.onerror = () => {
            clearTimeout(silenceTimer);
            document.getElementById('micIndicator').classList.remove('active');
            document.getElementById('speakBtn').disabled = false;
        };

        warmupRecognition.start();
    }

    function handleNoSpeech() {
        document.getElementById('micIndicator').classList.remove('active');
        document.getElementById('warmupStatus').textContent = "⚠️ I didn't hear that. Try again!";
        document.getElementById('speakBtn').disabled = false;
        const msg = new SpeechSynthesisUtterance("I didn't hear anything. Please click speak and try again.");
        msg.lang = 'en-IN';
        synth.speak(msg);
    }

    function handleWarmupResult(spoken) {
        const target = warmupWords[currentWarmupIndex].toLowerCase();
        const similarity = getSimilarityScore(spoken, target);
        const isCorrect = similarity >= 0.70; // Slightly stricter for single word practice

        const display = document.getElementById('currentWordDisplay');
        const status = document.getElementById('warmupStatus');

        if (isCorrect) {
            display.style.color = '#10b981';
            display.style.transform = "scale(1.1)";
            status.innerHTML = "🌟 <strong>Perfect Correction!</strong>";
            
            const praise = new SpeechSynthesisUtterance("Excellent!");
            praise.lang = 'en-IN';
            praise.onend = () => {
                setTimeout(() => {
                    currentWarmupIndex++;
                    showWarmupWord();
                }, 800);
            };
            synth.speak(praise);
        } else {
            // Store the word as a persistent mistake to show in dashboard later
            readingPracticeMistakes.push(target);
            display.style.color = '#ef4444';
            status.innerHTML = "💪 <strong>Nice try! We'll keep practicing this one.</strong>";
            
            const encourage = new SpeechSynthesisUtterance("Good try! Let's move to the next one.");
            encourage.lang = 'en-IN';
            encourage.onend = () => {
                setTimeout(() => {
                    currentWarmupIndex++;
                    showWarmupWord();
                }, 1200);
            };
            synth.speak(encourage);
        }
    }

    function completeWarmupPractice() {
        const accuracy = Math.round((correctCount / targetWords.length) * 100);
        
        // Save the results of this warmup session
        saveWarmupResults(accuracy);

        let finalRemarks = "";
        if (readingPracticeMistakes.length === 0) {
            finalRemarks = "Incredible! You corrected every single mistake!";
        } else {
            finalRemarks = `Session complete! You've improved your reading accuracy.`;
        }

        showFinalResults(accuracy, finalRemarks);
    }

    function showFinalResults(accuracy, message) {
        document.getElementById('warmupContainer').style.display = 'none';
        document.getElementById('results').style.display = 'block';
        document.getElementById('accDisplay').innerText = accuracy + "%";
        document.getElementById('remDisplay').innerText = message;
        
        let icon = accuracy >= 95 ? '🏆' : accuracy >= 85 ? '🌟' : accuracy >= 75 ? '👍' : '📖';
        document.getElementById('resultIcon').innerText = icon;
        
        const finalMsg = new SpeechSynthesisUtterance(message);
        finalMsg.lang = 'en-IN';
        synth.speak(finalMsg);
    }

    /******************
     * UTILITIES
     ******************/
    function getSimilarityScore(spoken, target) {
        if (spoken === target) return 1.0;
        
        // Check root words
        const spokenBase = spoken.replace(/(?:ing|ed|s|es|ly|er)$/i, '');
        const targetBase = target.replace(/(?:ing|ed|s|es|ly|er)$/i, '');
        if (spokenBase === targetBase) return 0.95;

        // Substring match
        if (spoken.length >= 3 && target.length >= 3) {
            if (spoken.includes(target) || target.includes(spoken)) return 0.75;
        }

        // Levenshtein distance
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
                    tmp[i-1][j]+1, 
                    tmp[i][j-1]+1, 
                    tmp[i-1][j-1]+(a[i-1]===b[j-1]?0:1)
                );
            }
        }
        return tmp[a.length][b.length];
    }

    /******************
     * DATABASE SAVES
     ******************/
    
    function saveWarmupResults(finalAccuracy) {
        // 1. Save score to the Scores table
        fetch('save_score.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                sid: sid,
                hid: hid,
                accuracy: finalAccuracy
            })
        }).catch(err => console.error('Score Save Failed'));

        // 2. Save remaining mistakes to the Warmup/Mistakes table
        fetch('save_warmup.php', { 
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ 
                sid: sid,
                hid: hid,
                reading_practice_mistakes: readingPracticeMistakes
            })
        })
        .then(res => res.json())
        .then(data => console.log('Mistakes updated:', data))
        .catch(err => console.error('Warmup Save Failed'));
    }
    </script>
</body>
</html>