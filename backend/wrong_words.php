<?php
session_start();
<<<<<<< HEAD

// Database configuration
$host = 'localhost';
$db = 'speakread_db';
$user = 'root';
$pass = 'skdn1418';


try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate required data
    if (!isset($data['sid']) || !isset($data['wrong_words'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }
    
    // Extract data
    $sid = (int)$data['sid'];
    $wrong_words = $data['wrong_words'];
    
    // If there are no wrong words, just return success
    if (empty($wrong_words)) {
        echo json_encode(['success' => true, 'message' => 'No wrong words to save']);
        exit;
    }
    
    // Prepare statement to insert wrong words
    $stmt = $pdo->prepare("INSERT INTO Warmup (SID, IncorrectWord) VALUES (?, ?)");
    
    // Insert each wrong word
    $inserted_count = 0;
    foreach ($wrong_words as $word) {
        if (!empty(trim($word))) {
            $stmt->execute([$sid, trim($word)]);
            $inserted_count++;
        }
    }
    
    // Send success response
    echo json_encode([
        'success' => true, 
        'message' => 'Wrong words saved successfully',
        'words_saved' => $inserted_count
    ]);
    
} catch (PDOException $e) {
    // Database error
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    // General error
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>