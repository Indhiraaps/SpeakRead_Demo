<?php
session_start();
require_once '../config/db.php';

$lid = $_GET['lid'] ?? 0;
$sid = $_GET['sid'] ?? 0;

// Validate student ID
if ($sid <= 0) {
    die("Invalid student ID");
}

try {
    $stmt = $pdo->prepare("SELECT Para FROM Lessons WHERE LID = ?");
    $stmt->execute([$lid]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $para = $result ? $result['Para'] : "No content found.";
} catch (PDOException $e) { 
    die("Error: " . $e->getMessage()); 
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reading Practice - SpeakRead</title>
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
        
        .results { 
            display: none;
            text-align: center;
            padding: 40px;
        }
        
        .accuracy { 
            font-size: 80px; 
            font-weight: 900; 
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 20px 0;
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
            
            .score-value {
                font-size: 28px;
            }
        }
    </style>
</head>
<body onload="welcomeVoice()">
    <div class="main-card">
        <div class="mic-indicator" id="micIndicator">🎤</div>
        
        <div class="header-section">
            <div class="header-icon">📚</div>
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

        <div id="results" class="results">
            <div style="font-size: 70px; margin: 20px 0;">🎉</div>
            <div class="accuracy" id="accDisplay">0%</div>
            <div id="remDisplay" style="font-size: 22px; margin: 20px 0; color: #64748b;"></div>
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
        let transcriptWords = []; // For live transcript display

        // ============================================
// NUMBER NORMALIZATION FUNCTIONS
// ============================================

// Convert number words to digits
function wordToNumber(word) {
    const numbers = {
        'zero': '0', 'one': '1', 'two': '2', 'three': '3', 'four': '4',
        'five': '5', 'six': '6', 'seven': '7', 'eight': '8', 'nine': '9',
        'ten': '10', 'eleven': '11', 'twelve': '12', 'thirteen': '13',
        'fourteen': '14', 'fifteen': '15', 'sixteen': '16', 'seventeen': '17',
        'eighteen': '18', 'nineteen': '19', 'twenty': '20', 'thirty': '30',
        'forty': '40', 'fifty': '50', 'sixty': '60', 'seventy': '70',
        'eighty': '80', 'ninety': '90', 'hundred': '100', 'thousand': '1000'
    };
    return numbers[word.toLowerCase()] || word;
}

// Convert digits to number words
function numberToWord(num) {
    const words = {
        '0': 'zero', '1': 'one', '2': 'two', '3': 'three', '4': 'four',
        '5': 'five', '6': 'six', '7': 'seven', '8': 'eight', '9': 'nine',
        '10': 'ten', '11': 'eleven', '12': 'twelve', '13': 'thirteen',
        '14': 'fourteen', '15': 'fifteen', '16': 'sixteen', '17': 'seventeen',
        '18': 'eighteen', '19': 'nineteen', '20': 'twenty', '30': 'thirty',
        '40': 'forty', '50': 'fifty', '60': 'sixty', '70': 'seventy',
        '80': 'eighty', '90': 'ninety', '100': 'hundred', '1000': 'thousand'
    };
    return words[num] || num;
}

// ============================================
// MULTI-WORD MATCHING FOR COMPOUND WORDS
// ============================================

// Check if multiple spoken words can match a single target word
function checkMultiWordMatch(spokenWords, startIndex, target) {
    // Try combining 2-3 spoken words to match target
    for (let numWords = 1; numWords <= 3; numWords++) {
        if (startIndex + numWords > spokenWords.length) break;
        
        // Combine spoken words (remove spaces)
        const combined = spokenWords
            .slice(startIndex, startIndex + numWords)
            .map(w => w.replace(/[^\w]/g, ''))
            .join('');
        
        // Check if combined matches target
        const score = getSimilarityScore(combined, target);
        if (score >= 0.50) {
            return { matched: true, wordsUsed: numWords, score: score };
        }
    }
    
    return { matched: false, wordsUsed: 0, score: 0 };
}

// Normalize compound words (handles spacing variations)
function normalizeCompound(word) {
    const compounds = {
        'sometime': ['sometime', 'some time'],
        'someone': ['someone', 'some one'],
        'something': ['something', 'some thing'],
        'somewhere': ['somewhere', 'some where'],
        'anyone': ['anyone', 'any one'],
        'anything': ['anything', 'any thing'],
        'anywhere': ['anywhere', 'any where'],
        'everyone': ['everyone', 'every one'],
        'everything': ['everything', 'every thing'],
        'everywhere': ['everywhere', 'every where'],
        'maybe': ['maybe', 'may be'],
        'cannot': ['cannot', 'can not'],
        'into': ['into', 'in to'],
        'onto': ['onto', 'on to'],
        'outside': ['outside', 'out side'],
        'inside': ['inside', 'in side']
    };
    
    // Remove spaces for comparison
    const normalized = word.replace(/\s+/g, '');
    
    // Check if it matches any compound variation
    for (let [base, variations] of Object.entries(compounds)) {
        for (let variant of variations) {
            if (normalized === variant.replace(/\s+/g, '')) {
                return base; // Return base form
            }
        }
    }
    
    return normalized;
}

// Check if two words match (considering number variations)
function wordsMatch(spoken, target) {
    // Direct match
    if (spoken === target) return true;
    
    // Check if spoken digit matches target word (4 matches "four")
    if (numberToWord(spoken) === target) return true;
    
    // Check if spoken word matches target digit ("four" matches 4)
    if (wordToNumber(spoken) === target) return true;
    
    return false;
}
        // Common words to ignore
        const ignoreWords = new Set([
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 
            'of', 'with', 'by', 'as', 'is', 'are', 'was', 'were', 'be', 'been', 
            'he', 'she', 'it', 'we', 'they', 'i', 'you', 'me', 'us', 'him', 'her'
        ]);

        const readingArea = document.getElementById('readingArea');
        const transcriptText = document.getElementById('transcriptText');
        const synth = window.speechSynthesis;

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
    recognition.maxAlternatives = 1;

    let restartTimeout;
    let isManualStop = false;

    recognition.onresult = (event) => {
        // Show interim results in real-time
        let interimTranscript = '';
        for (let i = event.resultIndex; i < event.results.length; i++) {
            if (event.results[i].isFinal) {
                const transcript = event.results[i][0].transcript.trim().toLowerCase();
                processTranscript(transcript);
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
        console.log('🎤 Recognition ended');
        
        // Clear any pending restart
        if (restartTimeout) {
            clearTimeout(restartTimeout);
        }
        
        // Only restart if STOP button is still visible (session active)
        if (!isManualStop && document.getElementById('stopBtn').style.display !== 'none') {
            console.log('🔄 Auto-restarting recognition...');
            
            // Add small delay before restart to avoid errors
            restartTimeout = setTimeout(() => {
                try {
                    recognition.start();
                    console.log('✅ Recognition restarted');
                } catch (e) {
                    console.error('❌ Restart failed:', e);
                    // Try again after longer delay
                    setTimeout(() => {
                        try {
                            recognition.start();
                        } catch (e2) {
                            console.error('❌ Second restart failed:', e2);
                        }
                    }, 500);
                }
            }, 100);
        }
    };
    
    recognition.onerror = (event) => {
        console.error('🔴 Speech recognition error:', event.error);
        
        // Handle different error types
        if (event.error === 'no-speech') {
            console.log('⚠️ No speech detected - will auto-restart');
            // Don't do anything - onend will handle restart
        } else if (event.error === 'aborted') {
            console.log('⚠️ Recognition aborted - will auto-restart');
        } else if (event.error === 'network') {
            console.error('❌ Network error - check internet connection');
            alert('Network error. Please check your internet connection.');
        } else if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
            console.error('❌ Microphone permission denied');
            alert('Microphone access denied. Please allow microphone access.');
        }
    };
    
    // Add function to stop recognition cleanly
    window.stopRecognitionCleanly = function() {
        isManualStop = true;
        if (restartTimeout) {
            clearTimeout(restartTimeout);
        }
        if (recognition) {
            recognition.stop();
        }
    };
    
    try {
        recognition.start();
        console.log('✅ Recognition started');
    } catch (e) {
        console.error('❌ Failed to start recognition:', e);
    }
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
    let spokenIndex = 0;
    
    while (spokenIndex < spokenWords.length && currentWordPointer < targetWords.length) {
        const spokenWord = spokenWords[spokenIndex];
        const cleanSpoken = spokenWord.replace(/[^\w]/g, '');
        
        if (cleanSpoken.length === 0) {
            spokenIndex++;
            continue;
        }

        // Try to find best match in next 4 target words
        let bestMatch = -1;
        let bestScore = 0;
        let wordsToSkip = 1; // How many spoken words to consume

        for (let i = currentWordPointer; i < Math.min(currentWordPointer + 4, targetWords.length); i++) {
            if (wordStates[i] === 'pending') {
                // Check single word match
                const singleScore = getSimilarityScore(cleanSpoken, targetWords[i]);
                if (singleScore > bestScore) {
                    bestScore = singleScore;
                    bestMatch = i;
                    wordsToSkip = 1;
                }
                
                // Check multi-word match (e.g., "some time" vs "sometime")
                const multiMatch = checkMultiWordMatch(spokenWords, spokenIndex, targetWords[i]);
                if (multiMatch.matched && multiMatch.score > bestScore) {
                    bestScore = multiMatch.score;
                    bestMatch = i;
                    wordsToSkip = multiMatch.wordsUsed;
                }
            }
        }

        // Lower threshold to 0.50 for more flexibility
        if (bestMatch !== -1 && bestScore >= 0.50) {
            markWord(bestMatch, true);
            addToTranscript(targetWords[bestMatch], true);
            currentWordPointer = bestMatch + 1;
            spokenIndex += wordsToSkip; // Skip consumed words
        } else if (wordStates[currentWordPointer] === 'pending') {
            markWord(currentWordPointer, false);
            addToTranscript(cleanSpoken, false);
            currentWordPointer++;
            spokenIndex++;
        } else {
            spokenIndex++;
        }
        
        updateLiveScore();
    }
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
    // Perfect match including number variations
    if (wordsMatch(spoken, target)) return 1.0;
    
    // Normalize compound words (handles "some time" vs "sometime")
    const normalizedSpoken = normalizeCompound(spoken);
    const normalizedTarget = normalizeCompound(target);
    
    if (normalizedSpoken === normalizedTarget) return 1.0;
    
    // Calculate similarity score using Levenshtein distance
    const dist = levenshtein(normalizedSpoken, normalizedTarget);
    return 1 - (dist / Math.max(normalizedSpoken.length, normalizedTarget.length));
}

        function stopSession() {
    // Clean stop of recognition
    if (window.stopRecognitionCleanly) {
        window.stopRecognitionCleanly();
    } else if (recognition) {
        recognition.stop();
        recognition.onend = null;
    }

    // Collect remaining unread words
    for (let i = 0; i < targetWords.length; i++) {
        if (wordStates[i] === 'pending') {
            const word = targetWords[i];
            if (word.length > 2 && !ignoreWords.has(word)) {
                wrongWordsList.push(word);
            }
        }
    }

    document.getElementById('readingArea').style.display = 'none';
    document.getElementById('transcriptSection').style.display = 'none';
    document.getElementById('scoreDisplay').style.display = 'none';
    document.getElementById('controls').style.display = 'none';
    document.getElementById('status').style.display = 'none';
    document.getElementById('micIndicator').classList.remove('active');
    
    const accuracy = Math.round((correctCount / targetWords.length) * 100);
    
    document.getElementById('results').style.display = 'block';
    document.getElementById('accDisplay').innerText = accuracy + "%";
    document.getElementById('remDisplay').innerText = getRemarks(accuracy);
    
    // Save wrong words
    saveWrongWords();
}
        function getRemarks(accuracy) {
            if (accuracy >= 95) return "Outstanding! Perfect reading!";
            if (accuracy >= 85) return "Excellent work!";
            if (accuracy >= 75) return "Great job! Keep practicing!";
            if (accuracy >= 60) return "Good effort!";
            return "Keep practicing!";
        }

        function saveWrongWords() {
            const uniqueWords = [...new Set(wrongWordsList)];
            
            if (uniqueWords.length === 0) {
                console.log('No wrong words to save');
                return;
            }
            
            fetch('save_warmup.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    sid: sid,
                    wrong_words: uniqueWords
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Words saved:', data);
            })
            .catch(err => console.error('Save error:', err));
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