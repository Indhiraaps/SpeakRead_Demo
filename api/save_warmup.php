<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['sid']) || !isset($data['wrong_words'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }
    
    $sid = (int)$data['sid'];
    $wrongWords = $data['wrong_words'];
    
    if ($sid <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
        exit;
    }
    
    if (empty($wrongWords)) {
        echo json_encode(['success' => true, 'message' => 'No mistakes to save', 'words_saved' => 0]);
        exit;
    }
    
    // Remove duplicates
    $wrongWords = array_unique(array_filter(array_map('trim', $wrongWords)));
    
    if (empty($wrongWords)) {
        echo json_encode(['success' => true, 'message' => 'No valid words to save', 'words_saved' => 0]);
        exit;
    }
    
    // Check if student already has a warmup record
    $checkStmt = $pdo->prepare("SELECT WID, reading_practice_mistakes FROM Warmup WHERE SID = ?");
    $checkStmt->execute([$sid]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // UPDATE existing record
        $currentWords = !empty($existing['reading_practice_mistakes']) ? 
                        array_map('trim', explode(',', $existing['reading_practice_mistakes'])) : [];
        
        $merged = array_unique(array_merge($currentWords, $wrongWords));
        $wordsString = implode(', ', $merged);
        
        $updateStmt = $pdo->prepare("UPDATE Warmup SET reading_practice_mistakes = ? WHERE SID = ?");
        $updateStmt->execute([$wordsString, $sid]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Reading mistakes updated',
            'words_saved' => count($wrongWords),
            'total_words' => count($merged)
        ]);
        
    } else {
        // INSERT new record
        $wordsString = implode(', ', $wrongWords);
        $insertStmt = $pdo->prepare("INSERT INTO Warmup (SID, reading_practice_mistakes) VALUES (?, ?)");
        $insertStmt->execute([$sid, $wordsString]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Reading mistakes saved',
            'words_saved' => count($wrongWords)
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>