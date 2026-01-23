<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['sid']) || !isset($data['words_to_keep']) || !isset($data['all_practiced_words'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }
    
    $sid = (int)$data['sid'];
    $wordsToKeep = $data['words_to_keep'];
    $allPracticedWords = $data['all_practiced_words'];
    
    // Get current warmup data
    $selectStmt = $pdo->prepare("SELECT homework_mistakes, reading_practice_mistakes FROM Warmup WHERE SID = ?");
    $selectStmt->execute([$sid]);
    $current = $selectStmt->fetch();
    
    if (!$current) {
        echo json_encode(['success' => true, 'message' => 'No warmup data found']);
        exit;
    }
    
    // Combine all existing mistakes
    $allMistakes = [];
    if (!empty($current['homework_mistakes'])) {
        $allMistakes = array_merge($allMistakes, explode(',', $current['homework_mistakes']));
    }
    if (!empty($current['reading_practice_mistakes'])) {
        $allMistakes = array_merge($allMistakes, explode(',', $current['reading_practice_mistakes']));
    }
    $allMistakes = array_map('trim', $allMistakes);
    
    // Remove all practiced words
    $allPracticedWords = array_map('trim', $allPracticedWords);
    $remainingWords = array_diff($allMistakes, $allPracticedWords);
    
    // Add back words that need more practice
    $wordsToKeep = array_map('trim', $wordsToKeep);
    $finalWords = array_unique(array_merge($remainingWords, $wordsToKeep));
    $finalWords = array_values(array_filter($finalWords)); // Remove empty values
    
    // Update database
    if (empty($finalWords)) {
        // No words left - delete the warmup record
        $deleteStmt = $pdo->prepare("DELETE FROM Warmup WHERE SID = ?");
        $deleteStmt->execute([$sid]);
        $message = 'All words mastered! Warmup cleared.';
    } else {
        // Update with remaining words (store all in homework_mistakes column for simplicity)
        $wordsString = implode(',', $finalWords);
        $updateStmt = $pdo->prepare("UPDATE Warmup SET homework_mistakes = ?, reading_practice_mistakes = NULL WHERE SID = ?");
        $updateStmt->execute([$wordsString, $sid]);
        $message = 'Warmup updated successfully';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'words_mastered' => count($allPracticedWords) - count($wordsToKeep),
        'words_remaining' => count($finalWords)
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