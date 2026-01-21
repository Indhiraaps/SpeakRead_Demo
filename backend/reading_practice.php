<?php
session_start();
$host = 'localhost'; $db = 'speakread_db'; $user = 'root'; $pass = 'skdn1418';
$lid = $_GET['lid'];
$sid = $_GET['sid'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT Para FROM Lessons WHERE LID = ?");
    $stmt->execute([$lid]);
    $lessonData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reading Session - SpeakRead</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: white; margin: 0; padding: 60px; text-align: center; }
        #readingArea { font-size: 48px; line-height: 1.5; margin-top: 80px; color: #0f172a; max-width: 1000px; margin-inline: auto; }
        .word-span { transition: color 0.3s ease; display: inline-block; margin-right: 12px; border-bottom: 2px solid transparent; }
        .btn-start { background: #2563eb; color: white; border: none; padding: 10px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .exit-btn { position: absolute; top: 30px; right: 40px; padding: 10px 20px; cursor: pointer; border: 1px solid #ddd; background: white; border-radius: 6px; }
    </style>
</head>
<body onload="startReading()">
    <button class="exit-btn" onclick="window.history.back()">Exit Session</button>
    
    <div id="readingArea"></div>
    <div id="t-hidden" style="display:none;"><?php echo htmlspecialchars($lessonData['Para']); ?></div>

    <div id="finalFeedback" style="margin-top:50px; display:none;">
        <h2 id="feedbackMsg" style="color:#2563eb;"></h2>
        <button class="btn-start" onclick="window.history.back()">Done</button>
    </div>

    <script>
        let recognition;
        let wordsArray;
        let wordIndex = 0;
        const currentLID = "<?php echo $lid; ?>";
        const currentSID = "<?php echo $sid; ?>";

        function startReading() {
            const text = document.getElementById('t-hidden').innerText;
            wordsArray = text.split(/\s+/);
            const display = document.getElementById('readingArea');
            display.innerHTML = wordsArray.map((w, i) => `<span id="w-${i}" class="word-span">${w}</span>`).join('');
            initSpeech();
        }

        function initSpeech() {
            window.SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.lang = 'en-IN';

            recognition.onresult = (event) => {
                const result = event.results[event.results.length - 1];
                if (result.isFinal) {
                    const spoken = result[0].transcript.trim().toLowerCase();
                    checkWord(spoken);
                }
            };
            recognition.start();
        }

        function checkWord(spoken) {
            if (wordIndex >= wordsArray.length) return;
            const target = wordsArray[wordIndex].toLowerCase().replace(/[^\w]/g, '');
            const spokenClean = spoken.replace(/[^\w]/g, '');
            const element = document.getElementById(`w-${wordIndex}`);

            if (spokenClean.includes(target)) {
                element.style.color = "#22c55e"; 
                wordIndex++;
            } else if (spokenClean !== "") {
                element.style.color = "#ef4444"; 
                saveToWarmup(wordsArray[wordIndex]); 
                wordIndex++;
            }

            if (wordIndex === wordsArray.length) finishSession();
        }

        function finishSession() {
            recognition.stop();
            const msg = "Wonderful effort! You have finished reading this paragraph.";
            const synth = window.speechSynthesis;
            const utter = new SpeechSynthesisUtterance(msg);
            utter.pitch = 1.5; utter.rate = 0.9;
            synth.speak(utter);

            document.getElementById('feedbackMsg').innerText = msg;
            document.getElementById('finalFeedback').style.display = 'block';
        }

        function saveToWarmup(word) {
            const data = new FormData();
            data.append('sid', currentSID);
            data.append('word', word);
            data.append('lid', currentLID);
            fetch('save_warmup.php', { method: 'POST', body: data });
        }
    </script>
</body>
</html>