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
    
    // Insert words as homework type for THIS SPECIFIC STUDENT ONLY
    $insertStmt = $pdo->prepare("
        INSERT INTO Warmup (SID, IncorrectWord, WordType) 
        VALUES (?, ?, 'homework')
        ON DUPLICATE KEY UPDATE DateAdded = CURRENT_TIMESTAMP
    ");
    
    $inserted = 0;
    foreach ($wrongWords as $word) {
        if (!empty($word) && strlen($word) > 2) {
            $insertStmt->execute([$sid, strtolower($word)]);
            $inserted++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Homework mistakes saved for student',
        'words_saved' => $inserted,
        'student_id' => $sid
    ]);
    
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