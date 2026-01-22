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
                        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 40% 20%, rgba(255,255,255,0.08) 0%, transparent 50%);
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
            box-shadow: 0 30px 90px rgba(0,0,0,0.25), 
                        0 0 0 1px rgba(255,255,255,0.3) inset;
            text-align: center;
            position: relative;
            animation: slideUp 0.6s ease-out;
            display: flex;
            flex-direction: column;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            height: 25px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .reading-text { 
            font-size: 26px; 
            line-height: 1.8; 
            margin: 15px 0; 
            padding: 25px 30px; 
            background: linear-gradient(to bottom right, #ffffff, #f8f9ff);
            border-radius: 20px; 
            text-align: left; 
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15),
                        inset 0 2px 8px rgba(255,255,255,0.9);
            min-height: 180px;
            max-height: 40vh;
            overflow-y: auto;
            border: 2px solid rgba(102, 126, 234, 0.1);
            position: relative;
            flex: 1;
        }

        .reading-text::before {
            content: '📖';
            position: absolute;
            font-size: 80px;
            opacity: 0.03;
            right: 20px;
            bottom: 10px;
            pointer-events: none;
        }

        .word { 
            display: inline; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: #2d3748; 
            padding: 4px 6px; 
            border-radius: 6px;
            font-weight: 500;
        }

        .word.correct { 
            color: #10b981 !important; 
            font-weight: 700;
            background: rgba(16, 185, 129, 0.1);
            transform: scale(1.05);
        } 

        .word.incorrect { 
            color: #ef4444 !important; 
            font-weight: 700;
            background: rgba(239, 68, 68, 0.1);
            transform: scale(1.05);
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
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-start { 
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-start:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
        }

        .btn-start:active {
            transform: translateY(-1px);
        }

        .btn-stop { 
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white; 
            display: none;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3); }
            50% { box-shadow: 0 8px 35px rgba(239, 68, 68, 0.6); }
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

        /* Modal Overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-overlay.active {
            display: flex;
        }

        .practice-modal {
            background: white;
            border-radius: 24px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 30px 90px rgba(0,0,0,0.4);
            animation: modalSlideUp 0.4s ease-out;
            position: relative;
        }

        @keyframes modalSlideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 24px;
            color: #94a3b8;
            cursor: pointer;
            padding: 5px 10px;
        }

        .modal-close:hover {
            color: #ef4444;
        }

        .modal-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #f87171, #fb923c);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            box-shadow: 0 10px 30px rgba(248, 113, 113, 0.4);
            animation: bounce 2s ease-in-out infinite;
        }

        .modal-title {
            font-size: 28px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 15px;
        }

        .modal-message {
            font-size: 16px;
            color: #64748b;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .modal-words-section {
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            padding: 20px;
            border-radius: 16px;
            margin: 25px 0;
        }

        .modal-words-label {
            font-size: 14px;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 12px;
        }

        .modal-word-count {
            font-size: 32px;
            font-weight: 900;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .btn-modal-practice {
            flex: 1;
            padding: 16px 30px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-modal-practice:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.5);
        }

        .btn-modal-later {
            flex: 1;
            padding: 16px 30px;
            background: #e2e8f0;
            color: #475569;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-modal-later:hover {
            background: #cbd5e1;
            transform: translateY(-2px);
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
            background-clip: text;
            margin: 10px 0;
            text-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
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
            .main-card { padding: 25px 20px; max-height: 92vh; }
            .reading-text { font-size: 22px; padding: 20px; max-height: 35vh; }
            .accuracy { font-size: 60px; }
            .remarks { font-size: 18px; }
            .btn { padding: 12px 30px; font-size: 16px; }
            .header-icon { width: 40px; height: 40px; font-size: 24px; }
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
            <a href="student_dashboard.php" class="back-btn" id="dashboardBtn">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Practice Modal -->
    <div id="practiceModal" class="modal-overlay">
        <div class="practice-modal">
            <button class="modal-close" onclick="closeModal()">✕</button>
            
            <div class="modal-icon">🎯</div>
            
            <h2 class="modal-title">Let's Fix Those Words!</h2>
            
            <p class="modal-message">
                Want to fix wrong words? I will read once, you repeat after me!
            </p>
            
            <div class="modal-words-section">
                <div class="modal-words-label">Listen and Repeat:</div>
                <div class="modal-word-count" id="modalWordCount">0</div>
                <div style="font-size: 14px; color: #78350f; font-weight: 600;">
                    words to practice
                </div>
            </div>
            
            <div class="modal-buttons">
                <button class="btn-modal-practice" onclick="startPractice()">
                    ✓ Start Practice
                </button>
                <button class="btn-modal-later" onclick="maybeLater()">
                    Maybe Later
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

        const readingArea = document.getElementById('readingArea');
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
            window.speechSynthesis.speak(msg);
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
            recognition.maxAlternatives = 3;

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

            recognition.onerror = (event) => {
                if (event.error !== 'no-speech') {
                    console.error('Speech recognition error:', event.error);
                }
            };

            recognition.onend = () => {
                if (document.getElementById('stopBtn').style.display !== 'none') {
                    recognition.start();
                }
            };

            recognition.start();
        }

        function updateProgress() {
            const progress = (currentWordPointer / targetWords.length) * 100;
            document.getElementById('progressFill').style.width = progress + '%';
        }

        function processTranscript(transcript) {
            const spokenWords = transcript.split(/\s+/).filter(w => w.length > 0);
            
            spokenWords.forEach(spokenWord => {
                const cleanSpoken = spokenWord.replace(/[^\w]/g, '');
                if (cleanSpoken.length === 0) return;
                if (currentWordPointer >= targetWords.length) return;

                let bestMatch = -1;
                let bestScore = 0;
                const lookAheadWindow = 4;

                for (let i = currentWordPointer; i < Math.min(currentWordPointer + lookAheadWindow, targetWords.length); i++) {
                    if (wordStates[i] === 'pending') {
                        const score = getSimilarityScore(cleanSpoken, targetWords[i]);
                        if (score > bestScore) {
                            bestScore = score;
                            bestMatch = i;
                        }
                    }
                }

                const goodThreshold = 0.55;
                
                if (bestMatch !== -1 && bestScore >= goodThreshold) {
                    markWord(bestMatch, true);
                    currentWordPointer = bestMatch + 1;
                } else {
                    if (currentWordPointer < targetWords.length && wordStates[currentWordPointer] === 'pending') {
                        markWord(currentWordPointer, false);
                        currentWordPointer++;
                    }
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
                wrongWordsList.push(targetWords[index]);
            }
        }

        function getSimilarityScore(spoken, target) {
            if (spoken === target) return 1.0;
            
            const spokenBase = spoken.replace(/(?:ing|ed|s|es|ly|er)$/i, '');
            const targetBase = target.replace(/(?:ing|ed|s|es|ly|er)$/i, '');
            
            if (spokenBase === targetBase) return 0.95;

            if (spoken.length >= 3 && target.length >= 3) {
                if (spoken.includes(target) || target.includes(spoken)) {
                    return 0.75;
                }
            }

            const dist = levenshtein(spoken, target);
            const maxLen = Math.max(spoken.length, target.length);
            
            let allowedErrors = 1;
            if (maxLen > 4) allowedErrors = 2;
            if (maxLen > 7) allowedErrors = 3;
            if (maxLen > 10) allowedErrors = 4;
            
            if (dist <= allowedErrors) {
                return Math.max(0.55, 1 - (dist / maxLen));
            }
            
            return 1 - (dist / maxLen);
        }

        function stopSession() {
            if (recognition) {
                recognition.stop();
                recognition.onend = null;
            }

            // DON'T add pending words - only incorrect words are already in wrongWordsList
            // wrongWordsList already contains only RED (incorrect) words from markWord() function

            document.getElementById('readingArea').style.display = 'none';
            document.getElementById('controls').style.display = 'none';
            document.getElementById('status').style.display = 'none';
            document.getElementById('micIndicator').classList.remove('active');
            document.getElementById('progressBar').style.display = 'none';
            
            const total = targetWords.length;
            const accuracy = total > 0 ? Math.round((correctCount / total) * 100) : 0;
            
            document.getElementById('results').style.display = 'block';
            document.getElementById('accDisplay').innerText = accuracy + "%";
            
            let icon = '🎉';
            if (accuracy >= 95) icon = '🏆';
            else if (accuracy >= 85) icon = '🌟';
            else if (accuracy >= 75) icon = '👍';
            else if (accuracy >= 60) icon = '👏';
            else if (accuracy >= 40) icon = '💪';
            else icon = '📖';
            document.getElementById('resultIcon').innerText = icon;
            
            const remarks = getRemarks(accuracy);
            document.getElementById('remDisplay').innerText = remarks;
            
            const finalMsg = new SpeechSynthesisUtterance(remarks);
            finalMsg.lang = 'en-IN'; // Indian accent
            window.speechSynthesis.speak(finalMsg);

            // Show modal if there are wrong words
            if (wrongWordsList.length > 0) {
                const uniqueWrongWords = [...new Set(wrongWordsList)];
                wrongWordsList = uniqueWrongWords;
                
                // Wait for voice to finish, then show modal
                finalMsg.onend = () => {
                    setTimeout(() => {
                        showPracticeModal(uniqueWrongWords.length);
                    }, 1000);
                };
            }

            // Save wrong words to database
            saveData(accuracy, correctCount, total);
        }

        function showPracticeModal(wordCount) {
            document.getElementById('modalWordCount').innerText = wordCount;
            document.getElementById('practiceModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('practiceModal').classList.remove('active');
        }

        function startPractice() {
            window.location.href = 'warmup_practice.php?sid=' + sid;
        }

        function maybeLater() {
            closeModal();
            // Voice feedback
            const laterMsg = new SpeechSynthesisUtterance("No problem! You can practice later from your dashboard.");
            laterMsg.lang = 'en-IN'; // Indian accent
            window.speechSynthesis.speak(laterMsg);
        }

        function getRemarks(accuracy) {
            if (accuracy >= 95) {
                return "Outstanding! Perfect reading with excellent clarity!";
            } else if (accuracy >= 85) {
                return "Excellent work! You read very well!";
            } else if (accuracy >= 75) {
                return "Great job! Keep practicing to improve further!";
            } else if (accuracy >= 60) {
                return "Good effort! Practice more to read better!";
            } else if (accuracy >= 40) {
                return "Nice try! Keep practicing, you'll get better!";
            } else {
                return "Keep practicing! You can do better next time!";
            }
        }

        function saveData(acc, correct, total) {
            fetch('wrong_words.php', { 
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    sid: sid,
                    wrong_words: wrongWordsList
                })
            }).catch(err => console.log('Save completed'));
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

        // ============ WARMUP PRACTICE FUNCTIONALITY ============
        let warmupWords = [];
        let currentWarmupIndex = 0;
        let currentAttempt = 0;
        let warmupRecognition = null;
        let wordsToKeepInWarmup = [];
        let correctWarmupCount = 0;

        function startWarmupPractice() {
            warmupWords = [...wrongWordsList];
            currentWarmupIndex = 0;
            currentAttempt = 0;
            wordsToKeepInWarmup = [];
            correctWarmupCount = 0;

            document.getElementById('results').style.display = 'none';
            document.getElementById('warmupPractice').style.display = 'block';

            practiceNextWord();
        }

        function practiceNextWord() {
            if (currentWarmupIndex >= warmupWords.length) {
                showPracticeComplete();
                return;
            }

            const currentWord = warmupWords[currentWarmupIndex];
            currentAttempt = 0;

            document.getElementById('currentWordDisplay').innerText = currentWord.toUpperCase();
            document.getElementById('warmupProgress').innerText = `Word ${currentWarmupIndex + 1} of ${warmupWords.length}`;
            document.getElementById('warmupStatus').innerText = 'Listen carefully...';
            document.getElementById('warmupStartBtn').style.display = 'none';

            // Speak the word
            setTimeout(() => {
                const speakMsg = new SpeechSynthesisUtterance(currentWord);
                speakMsg.lang = 'en-IN';
                speakMsg.rate = 0.8; // Slower for clarity
                speakMsg.onend = () => {
                    setTimeout(() => {
                        document.getElementById('warmupStatus').innerText = 'Now you try! Click the button when ready.';
                        document.getElementById('warmupStartBtn').style.display = 'inline-block';
                    }, 500);
                };
                window.speechSynthesis.speak(speakMsg);
            }, 500);
        }

        function startListening() {
            document.getElementById('warmupStartBtn').style.display = 'none';
            document.getElementById('warmupStatus').innerText = '🎤 Listening...';

            window.SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            warmupRecognition = new SpeechRecognition();
            warmupRecognition.continuous = false;
            warmupRecognition.interimResults = false;
            warmupRecognition.lang = 'en-IN';
            warmupRecognition.maxAlternatives = 3;

            warmupRecognition.onresult = (event) => {
                const spokenText = event.results[0][0].transcript.trim().toLowerCase();
                const cleanSpoken = spokenText.replace(/[^\w]/g, '');
                const targetWord = warmupWords[currentWarmupIndex].toLowerCase();
                
                checkWarmupPronunciation(cleanSpoken, targetWord);
            };

            warmupRecognition.onerror = (event) => {
                console.error('Recognition error:', event.error);
                document.getElementById('warmupStatus').innerText = 'Could not hear you. Try again!';
                document.getElementById('warmupStartBtn').style.display = 'inline-block';
            };

            warmupRecognition.start();
        }

        function checkWarmupPronunciation(spoken, target) {
            const similarity = getSimilarityScore(spoken, target);
            const isCorrect = similarity >= 0.65; // Slightly more lenient for warmup

            if (isCorrect) {
                // CORRECT!
                correctWarmupCount++;
                document.getElementById('warmupStatus').innerText = '✅ Perfect! Great job!';
                
                const praiseMsg = new SpeechSynthesisUtterance('Great job!');
                praiseMsg.lang = 'en-IN';
                praiseMsg.onend = () => {
                    setTimeout(() => {
                        currentWarmupIndex++;
                        practiceNextWord();
                    }, 1000);
                };
                window.speechSynthesis.speak(praiseMsg);

                // Don't add to warmup - will be deleted

            } else {
                // WRONG
                currentAttempt++;
                
                if (currentAttempt === 1) {
                    // First attempt wrong - give another chance
                    document.getElementById('warmupStatus').innerText = '❌ Try again! Listen once more...';
                    
                    const encourageMsg = new SpeechSynthesisUtterance('Try again!');
                    encourageMsg.lang = 'en-IN';
                    encourageMsg.onend = () => {
                        setTimeout(() => {
                            // Say the word again
                            const repeatMsg = new SpeechSynthesisUtterance(target);
                            repeatMsg.lang = 'en-IN';
                            repeatMsg.rate = 0.7;
                            repeatMsg.onend = () => {
                                setTimeout(() => {
                                    document.getElementById('warmupStatus').innerText = 'Now you try again!';
                                    document.getElementById('warmupStartBtn').style.display = 'inline-block';
                                }, 500);
                            };
                            window.speechSynthesis.speak(repeatMsg);
                        }, 500);
                    };
                    window.speechSynthesis.speak(encourageMsg);

                } else {
                    // Second attempt also wrong - keep in warmup and move on
                    wordsToKeepInWarmup.push(target);
                    document.getElementById('warmupStatus').innerText = '💪 Keep practicing! Moving to next word...';
                    
                    const nextMsg = new SpeechSynthesisUtterance('Keep practicing!');
                    nextMsg.lang = 'en-IN';
                    nextMsg.onend = () => {
                        setTimeout(() => {
                            currentWarmupIndex++;
                            practiceNextWord();
                        }, 1500);
                    };
                    window.speechSynthesis.speak(nextMsg);
                }
            }
        }

        function showPracticeComplete() {
            document.getElementById('warmupPractice').style.display = 'none';
            document.getElementById('practiceComplete').style.display = 'block';

            const totalWords = warmupWords.length;
            const resultsText = `You practiced ${totalWords} word${totalWords > 1 ? 's' : ''}. You got ${correctWarmupCount} correct! ${wordsToKeepInWarmup.length > 0 ? `Keep practicing the remaining ${wordsToKeepInWarmup.length} word${wordsToKeepInWarmup.length > 1 ? 's' : ''} in your next session.` : 'All words mastered!'}`;
            
            document.getElementById('practiceResults').innerText = resultsText;

            const completeMsg = new SpeechSynthesisUtterance(wordsToKeepInWarmup.length === 0 ? 'Excellent! All words mastered!' : 'Good practice! Keep it up!');
            completeMsg.lang = 'en-IN';
            window.speechSynthesis.speak(completeMsg);

            // Update warmup table - delete correct words, keep wrong words
            updateWarmupTable();
        }

        function updateWarmupTable() {
            // Send updated list to server
            fetch('update_warmup.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    sid: sid,
                    words_to_keep: wordsToKeepInWarmup,
                    all_practiced_words: warmupWords
                })
            }).catch(err => console.log('Warmup update completed'));
        }
    </script>
</body>
</html>