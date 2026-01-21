<?php
session_start();
$host = 'localhost'; $db = 'speakread_db'; $user = 'root'; $pass = '12345678';
$lid = $_GET['lid'];
$sid = $_GET['sid'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT Para FROM Lessons WHERE LID = ?");
    $stmt->execute([$lid]);
    $para = $stmt->fetch(PDO::FETCH_ASSOC)['Para'];
    
} catch (PDOException $e) { 
    die("Error: " . $e->getMessage()); 
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reading Practice - SpeakRead</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', -apple-system, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            height: 100vh; display: flex; color: #333;
        }
        .sidebar { 
            width: 260px; height: 100vh; position: fixed; left: 0; top: 0; 
            background: rgba(255,255,255,0.95); backdrop-filter: blur(20px);
            border-right: 1px solid #e2e8f0; padding: 24px; 
        }
        .content { 
            margin-left: 260px; padding: 40px; width: calc(100% - 260px); 
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .logo { color: #2563eb; font-weight: 800; font-size: 24px; text-decoration: none; margin-bottom: 40px; }
        .main-card { 
            background: rgba(255,255,255,0.95); backdrop-filter: blur(20px); 
            border: 1px solid rgba(255,255,255,0.2); border-radius: 24px; 
            padding: 60px; max-width: 900px; width: 90%; box-shadow: 0 25px 50px rgba(0,0,0,0.15); 
            text-align: center;
        }
        .status { 
            font-size: 24px; font-weight: 700; margin-bottom: 30px; min-height: 40px; 
            background: linear-gradient(45deg, #10b981, #059669); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .mic-container { margin: 40px 0; }
        .mic-btn { 
            width: 160px; height: 160px; border-radius: 50%; border: none; 
            font-size: 56px; cursor: pointer; transition: all 0.4s; 
            box-shadow: 0 15px 40px rgba(0,0,0,0.2); display: none;
        }
        .mic-ready { background: linear-gradient(45deg, #10b981, #059669); color: white; animation: pulse 2s infinite; }
        .mic-listening { background: linear-gradient(45deg, #f59e0b, #d97706); color: white; animation: bounce 0.6s infinite alternate; }
        @keyframes pulse { 0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.7);} 50%{box-shadow:0 0 0 25px rgba(16,185,129,0);} }
        @keyframes bounce { 0%{transform:scale(1);} 100%{transform:scale(1.1);} }
        .reading-text { 
            font-size: 36px; line-height: 1.6; margin: 40px 0; padding: 50px; 
            background: white; border-radius: 20px; min-height: 280px; box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            text-align: left; user-select: none; position: relative;
        }
        .word { 
            display: inline; padding: 4px 2px; margin: 0 1px; 
            transition: all 0.3s; border-radius: 6px; position: relative;
        }
        .word.correct { 
            color: #10b981; font-weight: 700; 
            animation: glow-green 0.5s ease-out; text-shadow: 0 0 10px rgba(16,185,129,0.5);
        }
        .word.incorrect { 
            color: #ef4444; font-weight: 700; 
            animation: shake 0.4s ease-out; text-shadow: 0 0 8px rgba(239,68,68,0.4);
        }
        .word.pending { color: #374151; }
        @keyframes glow-green { 0%{transform:scale(1); text-shadow:0 0 5px #10b981;} 50%{transform:scale(1.05); text-shadow:0 0 15px #10b981;} 100%{transform:scale(1);} }
        @keyframes shake { 0%,100%{transform:translateX(0);} 25%{transform:translateX(-2px);} 75%{transform:translateX(2px);} }
        .results { display: none; text-align: center; }
        .accuracy { 
            font-size: 72px; font-weight: 900; margin: 30px 0; 
            background: linear-gradient(45deg, #10b981, #2563eb); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .remarks { 
            font-size: 28px; font-weight: 600; margin: 20px 0; color: #1e293b;
            padding: 20px; background: rgba(255,255,255,0.8); border-radius: 16px;
        }
        .back-btn { 
            background: #6b7280; color: white; border: none; padding: 16px 32px; 
            border-radius: 16px; cursor: pointer; font-weight: 700; font-size: 18px; 
            text-decoration: none; display: inline-block; margin-top: 30px;
        }
        .back-btn:hover { background: #4b5563; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="#" class="logo">SpeakRead</a>
        <button onclick="window.history.back()" style="background:none;border:1px solid #ddd;padding:12px 18px;border-radius:8px;cursor:pointer;width:100%;margin-bottom:20px;">← Back</button>
    </div>

    <div class="content">
        <div class="main-card">
            <div class="status" id="status">Welcome! Get ready to read! 🎉</div>
            
            <div class="reading-text" id="readingText"><?php echo nl2br(htmlspecialchars($para)); ?></div>
            
            <div class="mic-container">
                <button id="micBtn" class="mic-btn" onclick="toggleListening()">🎤</button>
            </div>
            
            <div class="results" id="results">
                <div class="accuracy" id="accuracy">85%</div>
                <div class="remarks" id="remarks">Great job! You're almost perfect! 🌟</div>
                <a href="javascript:window.history.back()" class="back-btn">← Back to Lessons</a>
            </div>
        </div>
    </div>

    <script>
        const originalPara = `<?php echo addslashes($para); ?>`;
        const studentId = <?php echo $sid; ?>;
        const lessonId = <?php echo $lid; ?>;
        let recognition, synthesis = window.speechSynthesis;
        let isListening = false, correctCount = 0, totalWords = 0, wordStates = {};
        let wordsElements = [], currentWordIndex = 0;

        // Auto-start on page load
        document.addEventListener('DOMContentLoaded', function() {
            initWords();
            setTimeout(autoStartReading, 1500);
        });

        function initWords() {
            const textDiv = document.getElementById('readingText');
            const words = originalPara.replace(/[^\w\s\.\,\!\?]/g, '').split(/\s+/);
            totalWords = words.length;
            
            // Mark word boundaries for highlighting
            const highlightedPara = originalPara.replace(/\b\w+\b/g, (match) => {
                const cleanWord = match.toLowerCase().replace(/[^\w]/g, '');
                wordStates[cleanWord] = 'pending';
                return `<span class="word pending" data-word="${cleanWord}">${match}</span>`;
            });
            
            textDiv.innerHTML = highlightedPara;
            wordsElements = textDiv.querySelectorAll('.word');
        }

        function autoStartReading() {
            speak("Ready? Please read this passage aloud!", 1.0, 1.3);
            document.getElementById('status').textContent = "🎙️ Start speaking now!";
            document.getElementById('micBtn').style.display = 'block';
            setTimeout(startListening, 2000);
        }

        function startListening() {
            isListening = true;
            document.getElementById('micBtn').className = 'mic-btn mic-listening';
            document.getElementById('status').textContent = '🎙️ Listening... Speak clearly!';
            
            if (!initSpeech()) return;
            recognition.start();
        }

        function initSpeech() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) {
                document.getElementById('status').textContent = '❌ Speech not supported';
                return false;
            }
            
            recognition = new SpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.lang = 'en-IN';
            
            recognition.onresult = function(event) {
                let spokenText = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    spokenText += event.results[i][0].transcript.toLowerCase();
                }
                processSpeech(spokenText);
            };
            
            recognition.onerror = function(event) {
                console.log('Speech error:', event.error);
            };
            
            recognition.onend = function() {
                if (isListening) setTimeout(() => recognition.start(), 200);
            };
            return true;
        }

        function processSpeech(spokenText) {
            const spokenWords = spokenText.split(/\s+/).filter(w => w.length > 1);
            
            spokenWords.forEach(spokenWord => {
                // Find closest matching target word
                for (let element of wordsElements) {
                    const targetWord = element.dataset.word;
                    if (!wordStates[targetWord] || wordStates[targetWord] === 'pending') {
                        const distance = levenshteinDistance(spokenWord, targetWord);
                        if (distance <= 2 || spokenWord.includes(targetWord) || targetWord.includes(spokenWord)) {
                            markWord(element, targetWord, true);
                            break;
                        }
                    }
                }
            });
        }

        function markWord(element, word, isCorrect) {
            if (wordStates[word] !== 'pending') return;
            
            wordStates[word] = isCorrect ? 'correct' : 'incorrect';
            element.className = isCorrect ? 'word correct' : 'word incorrect';
            
            if (isCorrect) correctCount++;
            
            // Check if reading complete
            const processedWords = Object.values(wordStates).filter(state => state !== 'pending').length;
            if (processedWords >= totalWords * 0.8) {
                stopListening();
            }
        }

        function toggleListening() {
            if (isListening) {
                stopListening();
            }
        }

        function stopListening() {
            isListening = false;
            if (recognition) recognition.stop();
            document.getElementById('micBtn').className = 'mic-btn';
            document.getElementById('status').textContent = '🎉 Analyzing your reading...';
            
            setTimeout(showResults, 1500);
            
            // Save results silently
            saveSession();
        }

        function showResults() {
            const accuracy = Math.round((correctCount / totalWords) * 100);
            
            document.getElementById('readingText').style.display = 'none';
            document.getElementById('mic-container').style.display = 'none';
            document.getElementById('results').style.display = 'block';
            
            const accuracyEl = document.getElementById('accuracy');
            accuracyEl.textContent = accuracy + '%';
            
            const remarksEl = document.getElementById('remarks');
            if (accuracy >= 90) {
                remarksEl.textContent = '⭐ PERFECT! You\'re a reading superstar! 🌟';
                speak('Perfect! You are a reading superstar!', 1.0, 1.4);
            } else if (accuracy >= 75) {
                remarksEl.textContent = '🎉 Excellent! So close to perfect! Keep practicing! ✨';
                speak('Excellent work! You are almost perfect!', 1.1);
            } else if (accuracy >= 60) {
                remarksEl.textContent = '👍 Good job! Practice a little more and you\'ll be amazing! 🚀';
                speak('Good job! Keep practicing and you will be amazing!');
            } else {
                remarksEl.textContent = '💪 Great effort! Try again and read slowly. You can do it! 🌈';
                speak('Great effort! Try reading slowly next time. You can do it!');
            }
        }

        function saveSession() {
            fetch('wrong_words.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    sid: studentId,
                    lid: lessonId,
                    accuracy: Math.round((correctCount / totalWords) * 100),
                    wrong_words: [] // Simplified - just save accuracy for now
                })
            }).catch(err => console.log('Save ok'));
        }

        function levenshteinDistance(str1, str2) {
            const matrix = [];
            for (let i = 0; i <= str2.length; i++) matrix[i] = [i];
            for (let j = 0; j <= str1.length; j++) matrix[0][j] = j;
            for (let i = 1; i <= str2.length; i++) {
                for (let j = 1; j <= str1.length; j++) {
                    if (str2.charAt(i-1) === str1.charAt(j-1)) {
                        matrix[i][j] = matrix[i-1][j-1];
                    } else {
                        matrix[i][j] = Math.min(matrix[i-1][j-1], matrix[i][j-1], matrix[i-1][j]) + 1;
                    }
                }
            }
            return matrix[str2.length][str1.length];
        }

        function speak(text, rate = 1.0, pitch = 1.2) {
            synthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.rate = rate;
            utterance.pitch = pitch;
            utterance.volume = 1.0;
            utterance.lang = 'en-IN';
            synthesis.speak(utterance);
        }
    </script>
</body>
</html>
