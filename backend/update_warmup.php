<?php
session_start();
require_once '../config/db.php';

// Set header for JSON response
header('Content-Type: application/json');

try {
    // Get POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate required data
    if (!isset($data['sid']) || !isset($data['words_to_keep']) || !isset($data['all_practiced_words'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }
    
    $sid = (int)$data['sid'];
    $wordsToKeep = $data['words_to_keep']; // Words that were wrong even after 2 attempts
    $allPracticedWords = $data['all_practiced_words']; // All words that were practiced
    
    // Step 1: Delete all practiced words from Warmup table
    if (!empty($allPracticedWords)) {
        $placeholders = str_repeat('?,', count($allPracticedWords) - 1) . '?';
        $deleteStmt = $pdo->prepare("DELETE FROM Warmup WHERE SID = ? AND IncorrectWord IN ($placeholders)");
        $params = array_merge([$sid], $allPracticedWords);
        $deleteStmt->execute($params);
    }
    
    // Step 2: Re-insert only the words that need more practice (wrong after 2 attempts)
    if (!empty($wordsToKeep)) {
        $insertStmt = $pdo->prepare("INSERT INTO Warmup (SID, IncorrectWord) VALUES (?, ?)");
        foreach ($wordsToKeep as $word) {
            if (!empty(trim($word))) {
                $insertStmt->execute([$sid, trim($word)]);
            }
        }
    }
    
    // Send success response
    echo json_encode([
        'success' => true,
        'message' => 'Warmup updated successfully',
        'words_deleted' => count($allPracticedWords) - count($wordsToKeep),
        'words_kept' => count($wordsToKeep)
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