<?php
session_start();
$host = 'localhost'; $db = 'speakread_db'; $user = 'root'; $pass = '12345678';
$lid = $_GET['lid'] ?? 0;
$sid = $_GET['sid'] ?? 0;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT Para FROM Lessons WHERE LID = ?");
    $stmt->execute([$lid]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $para = $result ? $result['Para'] : "No content found.";
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reading Practice - SpeakRead</title>
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
            <a href="lessons.php" class="back-btn">← Back to Lessons</a>
        </div>
    </div>

    <script>
        const fullPara = `<?php echo addslashes($para); ?>`;
        const sid = <?php echo $sid; ?>;
        const lid = <?php echo $lid; ?>;
        
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

                // Check current word and look ahead
                let bestMatch = -1;
                let bestScore = 0;
                const lookAheadWindow = 4;

                // Find best match in current and upcoming words
                for (let i = currentWordPointer; i < Math.min(currentWordPointer + lookAheadWindow, targetWords.length); i++) {
                    if (wordStates[i] === 'pending') {
                        const score = getSimilarityScore(cleanSpoken, targetWords[i]);
                        if (score > bestScore) {
                            bestScore = score;
                            bestMatch = i;
                        }
                    }
                }

                const goodThreshold = 0.55;  // Correct pronunciation
                
                if (bestMatch !== -1 && bestScore >= goodThreshold) {
                    // GOOD match found - mark GREEN and move pointer
                    markWord(bestMatch, true);
                    currentWordPointer = bestMatch + 1;
                } else {
                    // No good match found
                    // This means: either wrong word spoken OR word skipped
                    // Mark CURRENT word as RED (wrong attempt)
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

            // Check if one contains the other
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

            // Collect remaining unread words for warmup
            for (let i = 0; i < targetWords.length; i++) {
                if (wordStates[i] === 'pending') {
                    wrongWordsList.push(targetWords[i]);
                }
            }

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
            else if (accuracy >= 75) icon = '👏';
            else if (accuracy >= 60) icon = '👍';
            else if (accuracy >= 40) icon = '💪';
            else icon = '📖';
            document.getElementById('resultIcon').innerText = icon;
            
            const remarks = getRemarks(accuracy);
            document.getElementById('remDisplay').innerText = remarks;
            
            const finalMsg = new SpeechSynthesisUtterance(remarks);
            finalMsg.lang = 'en-IN';
            window.speechSynthesis.speak(finalMsg);

            saveData(accuracy, correctCount, total);
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
            // Save wrong words
            fetch('wrong_words.php', { 
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    sid: sid,
                    wrong_words: wrongWordsList,
                    source: 'reading_practice'
                })
            }).catch(err => console.log('Words saved'));

            // Save score
            fetch('save_score.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    sid: sid,
                    hid: hid,
                    accuracy: acc
                })
            }).catch(err => console.log('Score saved'));
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
    </script>
</body>
</html>